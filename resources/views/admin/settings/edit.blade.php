@extends('layouts.admin', ['title' => 'Impostazioni'])

@section('content')
    <div class="top">
        <div class="stack-mid">
            <h2>Impostazioni</h2>
            <div class="muted">Configura i valori globali usati nello storefront e nel checkout.</div>
        </div>
        <a class="button secondary" href="{{ route('home') }}">Vedi storefront</a>
    </div>

    <form method="post" action="{{ route('admin.settings.update') }}">
        @csrf
        @method('patch')

        <div class="panel">
            <div class="form-grid">
                <div class="row">
                    <label>Tempo di consegna stimato</label>
                    <input name="delivery_estimate" required value="{{ old('delivery_estimate', $deliveryEstimate) }}" placeholder="7-10 giorni lavorativi">
                    <small class="muted">Usato come default nei nuovi prodotti e mostrato nel checkout.</small>
                </div>
            </div>
        </div>

        <div class="admin-actions-row">
            <button>Salva impostazioni</button>
        </div>
    </form>
@endsection
