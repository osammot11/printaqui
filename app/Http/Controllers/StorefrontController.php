<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class StorefrontController extends Controller
{
    public function home()
    {
        return view('storefront.home', [
            'categories' => Category::where('is_active', true)->orderBy('sort_order')->get(),
            'products' => Product::with(['category', 'variants', 'activePrintZones'])
                ->where('is_active', true)
                ->latest()
                ->get(),
        ]);
    }

    public function contatti()
    {
        return view('storefront.contatti');
    }

    public function collection(?string $slug = null)
    {
        $category = $slug ? Category::where('slug', $slug)->where('is_active', true)->firstOrFail() : null;

        $products = Product::with(['category', 'variants', 'activePrintZones'])
            ->where('is_active', true)
            ->when($category, fn ($query) => $query->where('category_id', $category->id))
            ->when(request('q'), fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->when(request('sort') === 'price_asc', fn ($query) => $query->orderBy('base_price_cents'))
            ->when(request('sort') === 'price_desc', fn ($query) => $query->orderByDesc('base_price_cents'))
            ->latest()
            ->get();

        return view('storefront.collection', compact('category', 'products'));
    }

    public function search(Request $request)
    {
        $search = trim((string) $request->query('q'));

        $products = collect();

        if ($search !== '') {
            $products = Product::with(['category', 'variants', 'activePrintZones'])
                ->where('is_active', true)
                ->where(function ($query) use ($search) {
                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('category', fn ($categoryQuery) => $categoryQuery->where('name', 'like', "%{$search}%"));
                })
                ->when($request->query('sort') === 'price_asc', fn ($query) => $query->orderBy('base_price_cents'))
                ->when($request->query('sort') === 'price_desc', fn ($query) => $query->orderByDesc('base_price_cents'))
                ->latest()
                ->get();
        }

        return view('storefront.search', [
            'products' => $products,
            'search' => $search,
        ]);
    }

    public function collections()
    {
        return view('storefront.collections', [
            'categories' => Category::with([
                'activeProducts' => fn ($query) => $query->latest(),
            ])
                ->withCount('activeProducts')
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function product(Product $product)
    {
        abort_unless($product->is_active, 404);

        $product->load(['category', 'variants' => fn ($query) => $query->where('is_active', true), 'activePrintZones']);

        $relatedProducts = Product::with(['category', 'variants'])
            ->where('is_active', true)
            ->whereKeyNot($product->id)
            ->when($product->category_id, fn ($query) => $query->where('category_id', $product->category_id))
            ->latest()
            ->limit(3)
            ->get();

        if ($relatedProducts->count() < 3) {
            $fallbackProducts = Product::with(['category', 'variants'])
                ->where('is_active', true)
                ->whereKeyNot($product->id)
                ->whereNotIn('id', $relatedProducts->pluck('id'))
                ->latest()
                ->limit(3 - $relatedProducts->count())
                ->get();

            $relatedProducts = $relatedProducts->concat($fallbackProducts);
        }

        $productFaqs = [
            [
                'question' => 'Posso ordinare il prodotto senza stampa?',
                'answer' => 'Si. Se non selezioni zone di stampa, il prodotto viene aggiunto al carrello come blank.',
            ],
            [
                'question' => 'Che file posso caricare per la stampa?',
                'answer' => 'Puoi caricare PNG, JPG, JPEG, AVIF, PDF o SVG. Ogni file puo pesare fino a 20MB.',
            ],
            [
                'question' => 'Come funziona l ordine bulk per taglie?',
                'answer' => 'Scegli il colore, inserisci le quantita sulle taglie disponibili e usa la stessa configurazione stampa per tutte le quantita selezionate.',
            ],
            [
                'question' => 'Quando viene calcolato il prezzo finale?',
                'answer' => 'Il prezzo unitario si aggiorna mentre selezioni le zone di stampa. Nel carrello trovi il totale basato su quantita, stampa e spedizione.',
            ],
        ];

        return view('storefront.product', compact('product', 'relatedProducts', 'productFaqs'));
    }
}
