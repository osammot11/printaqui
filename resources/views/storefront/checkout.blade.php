@extends('layouts.app', ['title' => 'Checkout - Printaqui'])

@section('content')
    <section class="section">
        <div class="wrap product-layout">
            <form class="panel" method="post" action="{{ route('checkout.store') }}">
                @csrf
                <div class="hp-field" aria-hidden="true">
                    <label>Sito web</label>
                    <input type="text" name="website" tabindex="-1" autocomplete="off">
                </div>

                <h2>Checkout</h2>
                @if (($cartWarnings ?? collect())->isNotEmpty())
                    <div class="errors">
                        @foreach ($cartWarnings as $warning)
                            <div>{{ $warning }}</div>
                        @endforeach
                    </div>
                @endif

                <div class="form-grid top-margin-large">
                    <div><label>Email</label><input type="email" name="email" required value="{{ old('email') }}"></div>
                    <div><label>Telefono</label><input name="phone" value="{{ old('phone') }}"></div>
                    <div><label>Nome</label><input name="first_name" required value="{{ old('first_name') }}"></div>
                    <div><label>Cognome</label><input name="last_name" required value="{{ old('last_name') }}"></div>
                    <div style="grid-column:1/-1"><label>Indirizzo</label><input name="address" required value="{{ old('address') }}"></div>
                    <div><label>Citta</label><input name="city" required value="{{ old('city') }}"></div>
                    <div><label>CAP</label><input name="postal_code" required value="{{ old('postal_code') }}"></div>
                    <div><label>Paese ISO</label><input name="country" maxlength="2" required value="{{ old('country', 'IT') }}" data-country-input></div>
                </div>

                <div class="coupon-box top-margin-large">
                    <label>Coupon</label>
                    <div class="coupon-row">
                        <input name="coupon" value="{{ old('coupon', $appliedCoupon['code']) }}" placeholder="Inserisci codice" data-coupon-input>
                        <button class="button secondary button-compact" type="button" data-coupon-apply-url="{{ route('checkout.coupon.apply') }}" data-coupon-apply>Applica</button>
                        <button class="button danger button-compact" type="button" data-coupon-remove-url="{{ route('checkout.coupon.remove') }}" data-coupon-remove @if(! $appliedCoupon['code']) hidden @endif>Rimuovi</button>
                    </div>
                    <small class="muted" data-coupon-message @if(! $appliedCoupon['code']) hidden @endif>Coupon {{ $appliedCoupon['code'] }} applicato.</small>
                    <small class="coupon-error" data-coupon-error hidden></small>
                </div>

                <div class="row" style="margin-top:14px;">
                    <label>Spedizione</label>
                    <select name="shipping_rate_id" required>
                        @foreach ($shippingRates as $rate)
                            <option
                                value="{{ $rate->id }}"
                                data-price="{{ $rate->is_free_shipping ? 0 : $rate->price_cents }}"
                                data-countries="{{ implode(',', $rate->countryCodes()) }}"
                            >
                                {{ $rate->name }} - € {{ number_format(($rate->is_free_shipping ? 0 : $rate->price_cents) / 100, 2, ',', '.') }}
                            </option>
                        @endforeach
                    </select>
                    <small class="muted" data-shipping-country-message></small>
                </div>
                <div class="panel top-margin-mid">
                    <div class="muted">Consegna stimata</div>
                    <strong>{{ $deliveryEstimate }}</strong>
                </div>
                <button class="top-margin-mid mobile-fullwidth">Vai al pagamento</button>
            </form>

            <aside class="panel">
                <h2 style="font-size:24px;">Riepilogo</h2>
                @foreach ($items as $item)
                    <div class="top-margin-mid" style="border-bottom:1px solid var(--line); padding:10px 0;">
                        <h6>{{ $item['product_name'] }}</h6>
                        <small class="muted top-margin-mid">{{ $item['quantity'] }} pezzi | Prezzo unitario: € {{ number_format($item['line_total_cents'] / 100, 2, ',', '.') }}</small>
                    </div>
                @endforeach
                <div class="top-margin-large">
                    <div class="muted">Subtotale</div>
                    <div class="price">€ {{ number_format($subtotal_cents / 100, 2, ',', '.') }}</div>
                </div>
                <div class="top-margin-mid">
                    <div class="muted">Spedizione</div>
                    <strong id="checkout-shipping">€ 0,00</strong>
                </div>
                <div class="top-margin-mid" data-discount-row @if($discount_cents <= 0) hidden @endif>
                    <div class="muted" data-discount-label>Coupon {{ $appliedCoupon['code'] }}</div>
                    <strong data-discount-value>- € {{ number_format($discount_cents / 100, 2, ',', '.') }}</strong>
                </div>
                <div class="top-margin-mid">
                    <div class="muted">Totale stimato</div>
                    <div class="price" id="checkout-total" data-subtotal="{{ max(0, $subtotal_cents - $discount_cents) }}">€ {{ number_format(max(0, $subtotal_cents - $discount_cents) / 100, 2, ',', '.') }}</div>
                </div>
            </aside>
        </div>
    </section>
@endsection

@push('scripts')
<script>
    const shippingSelect = document.querySelector('[name="shipping_rate_id"]');
    const countryInput = document.querySelector('[data-country-input]');
    const shippingOutput = document.getElementById('checkout-shipping');
    const totalOutput = document.getElementById('checkout-total');
    const shippingCountryMessage = document.querySelector('[data-shipping-country-message]');
    const couponInput = document.querySelector('[data-coupon-input]');
    const couponApply = document.querySelector('[data-coupon-apply]');
    const couponRemove = document.querySelector('[data-coupon-remove]');
    const couponMessage = document.querySelector('[data-coupon-message]');
    const couponError = document.querySelector('[data-coupon-error]');
    const discountRow = document.querySelector('[data-discount-row]');
    const discountLabel = document.querySelector('[data-discount-label]');
    const discountValue = document.querySelector('[data-discount-value]');
    const csrfToken = document.querySelector('input[name="_token"]')?.value;

    function formatCents(cents) {
        return new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(cents / 100);
    }

    function refreshCheckoutTotal() {
        if (!shippingSelect || !shippingOutput || !totalOutput) return;

        const shipping = Number(shippingSelect.selectedOptions[0]?.dataset.price || 0);
        const subtotal = Number(totalOutput.dataset.subtotal || 0);

        shippingOutput.textContent = formatCents(shipping);
        totalOutput.textContent = formatCents(subtotal + shipping);
    }

    function optionMatchesCountry(option, country) {
        const countries = (option.dataset.countries || '').split(',').map((code) => code.trim().toUpperCase()).filter(Boolean);

        return countries.length === 0 || countries.includes(country);
    }

    function filterShippingRates() {
        if (!shippingSelect || !countryInput) return;

        const country = countryInput.value.trim().toUpperCase();
        let firstAvailable = null;

        Array.from(shippingSelect.options).forEach((option) => {
            const isAvailable = country.length === 2 && optionMatchesCountry(option, country);

            option.hidden = !isAvailable;
            option.disabled = !isAvailable;

            if (isAvailable && !firstAvailable) {
                firstAvailable = option;
            }
        });

        if (!shippingSelect.selectedOptions[0] || shippingSelect.selectedOptions[0].disabled) {
            shippingSelect.value = firstAvailable?.value || '';
        }

        if (shippingCountryMessage) {
            shippingCountryMessage.textContent = firstAvailable
                ? 'Spedizioni disponibili per ' + country + '.'
                : 'Nessuna spedizione disponibile per questo paese.';
        }

        refreshCheckoutTotal();
    }

    shippingSelect?.addEventListener('change', refreshCheckoutTotal);
    countryInput?.addEventListener('input', filterShippingRates);
    filterShippingRates();

    async function sendCouponRequest(url, payload = {}) {
        const body = new FormData();

        Object.entries(payload).forEach(([key, value]) => body.append(key, value));

        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body,
        });

        const data = await response.json();

        if (!response.ok) {
            throw data;
        }

        return data;
    }

    function setCouponBusy(isBusy) {
        if (couponApply) couponApply.disabled = isBusy;
        if (couponRemove) couponRemove.disabled = isBusy;
    }

    function showCouponError(message) {
        if (!couponError) return;

        couponError.textContent = message;
        couponError.hidden = false;
    }

    function clearCouponError() {
        if (!couponError) return;

        couponError.textContent = '';
        couponError.hidden = true;
    }

    function applyCouponState(data) {
        const hasDiscount = Number(data.discount_cents || 0) > 0;

        totalOutput.dataset.subtotal = String(data.subtotal_after_discount_cents || 0);

        if (couponMessage) {
            couponMessage.textContent = data.message;
            couponMessage.hidden = false;
        }

        if (couponInput && data.code) {
            couponInput.value = data.code;
        }

        if (couponRemove) {
            couponRemove.hidden = !data.code;
        }

        if (discountRow && discountLabel && discountValue) {
            discountRow.hidden = !hasDiscount;
            discountLabel.textContent = data.code ? `Coupon ${data.code}` : 'Coupon';
            discountValue.textContent = `- ${data.discount_formatted}`;
        }

        refreshCheckoutTotal();
    }

    couponApply?.addEventListener('click', async () => {
        clearCouponError();
        setCouponBusy(true);

        try {
            const data = await sendCouponRequest(couponApply.dataset.couponApplyUrl, {
                coupon: couponInput?.value || '',
            });

            applyCouponState(data);
        } catch (error) {
            showCouponError(error?.errors?.coupon?.[0] || error?.message || 'Coupon non valido.');
        } finally {
            setCouponBusy(false);
        }
    });

    couponRemove?.addEventListener('click', async () => {
        clearCouponError();
        setCouponBusy(true);

        try {
            const data = await sendCouponRequest(couponRemove.dataset.couponRemoveUrl);

            if (couponInput) couponInput.value = '';
            if (couponMessage) couponMessage.hidden = true;

            applyCouponState(data);
        } catch (error) {
            showCouponError(error?.message || 'Impossibile rimuovere il coupon.');
        } finally {
            setCouponBusy(false);
        }
    });
</script>
@endpush
