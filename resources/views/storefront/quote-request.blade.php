@extends('layouts.app', ['title' => 'Preventivo ordini custom - Printaqui'])

@section('content')
    <section class="wrap top-margin">
        <div class="center-text">
            <h1>Preventivo custom</h1>
            <p class="top-margin-mid">Per ordini bulk, divise aziendali, capsule personalizzate o richieste con piu posizioni di stampa.</p>
        </div>
    </section>

    <section class="section">
        <div class="wrap product-layout">
            <form class="panel" method="post" action="{{ route('quote.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="hp-field" aria-hidden="true">
                    <label>Sito web</label>
                    <input type="text" name="website" tabindex="-1" autocomplete="off">
                </div>

                <h2 style="font-size:24px;">Dettagli richiesta</h2>

                <div class="form-grid top-margin-large">
                    <div class="row">
                        <label>Nome</label>
                        <input name="first_name" required value="{{ old('first_name') }}">
                    </div>

                    <div class="row">
                        <label>Cognome</label>
                        <input name="last_name" required value="{{ old('last_name') }}">
                    </div>

                    <div class="row">
                        <label>Email</label>
                        <input type="email" name="email" required value="{{ old('email') }}">
                    </div>

                    <div class="row">
                        <label>Telefono</label>
                        <input name="phone" value="{{ old('phone') }}">
                    </div>

                    <div class="row">
                        <label>Azienda</label>
                        <input name="company" value="{{ old('company') }}">
                    </div>

                    <div class="row">
                        <label>Tipo prodotto</label>
                        <input name="product_type" required value="{{ old('product_type') }}" placeholder="T-shirt, hoodie, tote bag...">
                    </div>

                    <div class="row">
                        <label>Quantita indicativa</label>
                        <input type="number" min="1" name="quantity" required value="{{ old('quantity') }}" placeholder="100">
                    </div>

                    <div class="row">
                        <label>Scadenza desiderata</label>
                        <input type="date" name="deadline" value="{{ old('deadline') }}">
                    </div>

                    <div class="row" style="grid-column:1/-1">
                        <label>Posizioni di stampa</label>
                        <input name="print_positions" value="{{ old('print_positions') }}" placeholder="Fronte cuore, retro grande, manica...">
                    </div>

                    <div class="row" style="grid-column:1/-1">
                        <label>File o mockup</label>
                        <input type="file" name="artwork" accept=".png,.jpg,.jpeg,.avif,.pdf,.svg">
                        <small class="muted">PNG, JPG, AVIF, PDF o SVG. Max 20MB.</small>
                    </div>

                    <div class="row" style="grid-column:1/-1">
                        <label>Descrizione richiesta</label>
                        <textarea name="message" rows="6" required placeholder="Raccontaci capi, colori, taglie, utilizzo, budget indicativo o qualsiasi dettaglio utile.">{{ old('message') }}</textarea>
                    </div>
                </div>

                <button class="mobile-fullwidth">Invia richiesta preventivo</button>
            </form>

            <aside class="panel stack-mid">
                <h2 style="font-size:24px;">Cosa succede dopo</h2>
                <p class="muted">Ricevi subito una conferma via email. Noi riceviamo la richiesta completa e ti rispondiamo con una proposta dedicata.</p>

                <div class="pillrow">
                    <span class="pill">Ordini bulk</span>
                    <span class="pill">DTF custom</span>
                    <span class="pill">File allegati</span>
                    <span class="pill">Risposta manuale</span>
                </div>

                <div class="top-margin-large">
                    <h4>Consiglio</h4>
                    <p class="muted top-margin-small">Piu dettagli inserisci su quantita, posizioni e deadline, piu preciso sara il preventivo.</p>
                </div>
            </aside>
        </div>
    </section>
@endsection
