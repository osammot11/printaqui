@extends('layouts.app', ['title' => 'Stato ordine - Printaqui'])

@section('content')
    <section class="section">
        <div class="wrap">
            <div class="panel order-lookup-panel">
                <h2>Stato ordine</h2>
                <p class="muted top-margin-small">Inserisci numero ordine ed email usata in checkout.</p>

                <form method="post" action="{{ route('orders.lookup.show') }}" class="top-margin-large">
                    @csrf
                    <div class="hp-field" aria-hidden="true">
                        <label>Sito web</label>
                        <input type="text" name="website" tabindex="-1" autocomplete="off">
                    </div>

                    <div class="form-grid">
                        <div class="row">
                            <label>Numero ordine</label>
                            <input name="number" required value="{{ old('number') }}" placeholder="PA-20260425-ABC123">
                        </div>

                        <div class="row">
                            <label>Email</label>
                            <input type="email" name="email" required value="{{ old('email') }}" placeholder="email@example.com">
                        </div>
                    </div>

                    <button class="top-margin-mid">Controlla ordine</button>
                </form>
            </div>
        </div>
    </section>
@endsection
