<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class OrderLookupController extends Controller
{
    public function create()
    {
        return view('storefront.order-lookup');
    }

    public function show(Request $request)
    {
        $validated = $request->validate([
            'number' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email'],
        ]);

        $order = Order::with(['customer', 'items.printFiles'])
            ->where('number', trim($validated['number']))
            ->whereHas('customer', fn ($query) => $query->where('email', strtolower($validated['email'])))
            ->first();

        if (! $order) {
            return back()
                ->withErrors(['number' => 'Ordine non trovato. Controlla numero ordine ed email.'])
                ->withInput();
        }

        return view('storefront.order-status', compact('order'));
    }
}
