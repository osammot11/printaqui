<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DiscountCode;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DiscountCodeController extends Controller
{
    public function index()
    {
        return view('admin.discounts.index', [
            'discounts' => DiscountCode::latest()->paginate(30),
        ]);
    }

    public function create()
    {
        return view('admin.discounts.form', [
            'discount' => new DiscountCode(),
        ]);
    }

    public function store(Request $request)
    {
        DiscountCode::create($this->validatedDiscount($request));

        return redirect()->route('admin.discounts.index')->with('status', 'Coupon creato.');
    }

    public function edit(DiscountCode $discount)
    {
        return view('admin.discounts.form', compact('discount'));
    }

    public function update(Request $request, DiscountCode $discount)
    {
        $discount->update($this->validatedDiscount($request, $discount));

        return redirect()->route('admin.discounts.edit', $discount)->with('status', 'Coupon aggiornato.');
    }

    public function destroy(DiscountCode $discount)
    {
        $discount->update(['is_active' => false]);

        return redirect()->route('admin.discounts.index')->with('status', 'Coupon disattivato.');
    }

    private function validatedDiscount(Request $request, ?DiscountCode $discount = null): array
    {
        $request->merge([
            'code' => Str::upper(trim((string) $request->input('code'))),
        ]);

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:80',
                Rule::unique('discount_codes', 'code')->ignore($discount),
            ],
            'type' => ['required', Rule::in(['percent', 'fixed'])],
            'value' => ['required', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
        ]);

        return [
            'code' => Str::upper(trim($validated['code'])),
            'type' => $validated['type'],
            'value' => $this->storedValue($validated['type'], (float) $validated['value']),
            'is_active' => $request->boolean('is_active'),
            'starts_at' => $validated['starts_at'] ?? null,
            'ends_at' => $validated['ends_at'] ?? null,
            'usage_limit' => $validated['usage_limit'] ?? null,
        ];
    }

    private function storedValue(string $type, float $value): int
    {
        if ($type === 'percent') {
            return min(100, (int) round($value));
        }

        return (int) round($value * 100);
    }
}
