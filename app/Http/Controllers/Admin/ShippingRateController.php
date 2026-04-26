<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShippingRate;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ShippingRateController extends Controller
{
    public function index()
    {
        return view('admin.shipping-rates.index', [
            'shippingRates' => ShippingRate::orderByDesc('is_active')
                ->orderBy('price_cents')
                ->orderBy('name')
                ->paginate(30),
        ]);
    }

    public function create()
    {
        return view('admin.shipping-rates.form', [
            'shippingRate' => new ShippingRate(),
        ]);
    }

    public function store(Request $request)
    {
        ShippingRate::create($this->validatedShippingRate($request));

        return redirect()->route('admin.shipping-rates.index')->with('status', 'Tariffa spedizione creata.');
    }

    public function edit(ShippingRate $shippingRate)
    {
        return view('admin.shipping-rates.form', compact('shippingRate'));
    }

    public function update(Request $request, ShippingRate $shippingRate)
    {
        $shippingRate->update($this->validatedShippingRate($request));

        return redirect()->route('admin.shipping-rates.edit', $shippingRate)->with('status', 'Tariffa spedizione aggiornata.');
    }

    public function destroy(ShippingRate $shippingRate)
    {
        $shippingRate->update(['is_active' => false]);

        return redirect()->route('admin.shipping-rates.index')->with('status', 'Tariffa spedizione disattivata.');
    }

    private function validatedShippingRate(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'country_codes' => ['nullable', 'string', 'max:500'],
            'zone' => ['nullable', 'string', 'max:80'],
            'price' => ['required', 'numeric', 'min:0'],
            'is_free_shipping' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $isFreeShipping = $request->boolean('is_free_shipping');
        $this->assertValidCountryCodes($validated['country_codes'] ?? null);
        $countryCodes = $this->countryCodesFromInput($validated['country_codes'] ?? null);

        return [
            'name' => $validated['name'],
            'country_code' => $countryCodes[0] ?? null,
            'country_codes' => $countryCodes ?: null,
            'zone' => ($validated['zone'] ?? null) ?: 'worldwide',
            'price_cents' => $isFreeShipping ? 0 : (int) round($validated['price'] * 100),
            'is_free_shipping' => $isFreeShipping,
            'is_active' => $request->boolean('is_active'),
        ];
    }

    private function countryCodesFromInput(?string $input): array
    {
        return collect(preg_split('/[\s,;]+/', (string) $input))
            ->filter()
            ->map(fn ($code) => strtoupper(trim($code)))
            ->filter(fn ($code) => preg_match('/^[A-Z]{2}$/', $code))
            ->unique()
            ->values()
            ->all();
    }

    private function assertValidCountryCodes(?string $input): void
    {
        $invalid = collect(preg_split('/[\s,;]+/', (string) $input))
            ->filter()
            ->map(fn ($code) => strtoupper(trim($code)))
            ->reject(fn ($code) => preg_match('/^[A-Z]{2}$/', $code))
            ->values();

        if ($invalid->isNotEmpty()) {
            throw ValidationException::withMessages([
                'country_codes' => 'Usa solo codici ISO a 2 lettere. Codici non validi: '.$invalid->implode(', '),
            ]);
        }
    }
}
