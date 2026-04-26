@extends('layouts.admin', ['title' => 'Coupon'])

@section('content')
    <div class="top">
        <div class="stack-mid">
            <h2>Coupon</h2>
            <div class="muted">Codici sconto percentuali o a importo fisso, con limiti e date di validita.</div>
        </div>
        <a class="button" href="{{ route('admin.discounts.create') }}">Nuovo coupon</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>Codice</th>
                <th>Tipo</th>
                <th>Valore</th>
                <th>Uso</th>
                <th>Validita</th>
                <th>Stato</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($discounts as $discount)
                <tr>
                    <td><strong>{{ $discount->code }}</strong></td>
                    <td>{{ $discount->type === 'percent' ? 'Percentuale' : 'Importo fisso' }}</td>
                    <td>
                        @if ($discount->type === 'percent')
                            {{ $discount->value }}%
                        @else
                            € {{ number_format($discount->value / 100, 2, ',', '.') }}
                        @endif
                    </td>
                    <td>{{ $discount->used_count }}@if($discount->usage_limit) / {{ $discount->usage_limit }}@endif</td>
                    <td>
                        <span class="muted">
                            {{ $discount->starts_at?->format('d/m/Y') ?? 'Subito' }}
                            -
                            {{ $discount->ends_at?->format('d/m/Y') ?? 'Senza scadenza' }}
                        </span>
                    </td>
                    <td>{{ $discount->is_active ? 'Attivo' : 'Disattivato' }}</td>
                    <td><a class="button secondary" href="{{ route('admin.discounts.edit', $discount) }}">Modifica</a></td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">Nessun coupon creato.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top:14px;">{{ $discounts->links() }}</div>
@endsection
