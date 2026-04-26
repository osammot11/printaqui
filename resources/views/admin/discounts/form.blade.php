@extends('layouts.admin', ['title' => $discount->exists ? 'Modifica coupon' : 'Nuovo coupon'])

@section('content')
    <div class="top">
        <div>
            <h2>{{ $discount->exists ? 'Modifica coupon' : 'Nuovo coupon' }}</h2>
            <div class="muted">Configura codice, valore e finestre di validita.</div>
        </div>
        <a class="button secondary" href="{{ route('admin.discounts.index') }}">Lista coupon</a>
    </div>

    <form method="post" action="{{ $discount->exists ? route('admin.discounts.update', $discount) : route('admin.discounts.store') }}">
        @csrf
        @if ($discount->exists)
            @method('put')
        @endif

        @php
            $selectedType = old('type', $discount->type ?: 'percent');
            $displayValue = old('value', $discount->exists && $discount->type !== 'percent' ? $discount->value / 100 : $discount->value);
        @endphp

        <div class="panel">
            <div class="form-grid">
                <div class="row">
                    <label>Codice</label>
                    <input name="code" required value="{{ old('code', $discount->code) }}" placeholder="WELCOME10">
                </div>

                <div class="row">
                    <label>Tipo</label>
                    <select name="type" required>
                        <option value="percent" @selected($selectedType === 'percent')>Percentuale</option>
                        <option value="fixed" @selected($selectedType === 'fixed')>Importo fisso</option>
                    </select>
                </div>

                <div class="row">
                    <label>Valore</label>
                    <input type="number" step="0.01" min="0" name="value" required value="{{ $displayValue }}">
                    <small class="muted">Percentuale: inserisci 10 per 10%. Importo fisso: inserisci euro, es. 5.00.</small>
                </div>

                <div class="row">
                    <label>Limite utilizzi</label>
                    <input type="number" min="1" name="usage_limit" value="{{ old('usage_limit', $discount->usage_limit) }}" placeholder="Illimitato">
                </div>

                <div class="row">
                    <label>Inizio validita</label>
                    <input type="datetime-local" name="starts_at" value="{{ old('starts_at', $discount->starts_at?->format('Y-m-d\TH:i')) }}">
                </div>

                <div class="row">
                    <label>Fine validita</label>
                    <input type="datetime-local" name="ends_at" value="{{ old('ends_at', $discount->ends_at?->format('Y-m-d\TH:i')) }}">
                </div>

                <div class="row">
                    <label>Attivo</label>
                    <input style="width:auto" type="checkbox" name="is_active" value="1" @checked(old('is_active', $discount->is_active ?? true))>
                </div>

                @if ($discount->exists)
                    <div class="row">
                        <label>Utilizzi registrati</label>
                        <input disabled value="{{ $discount->used_count }}">
                    </div>
                @endif
            </div>
        </div>

        <div class="admin-actions-row">
            <button>Salva coupon</button>

            @if ($discount->exists && $discount->is_active)
                <button class="button danger" type="submit" form="delete-discount-form">Disattiva</button>
            @endif
        </div>
    </form>

    @if ($discount->exists && $discount->is_active)
        <form id="delete-discount-form" method="post" action="{{ route('admin.discounts.destroy', $discount) }}">
            @csrf
            @method('delete')
        </form>
    @endif
@endsection
