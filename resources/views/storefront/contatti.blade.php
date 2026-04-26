@extends('layouts.app', ['title' => 'Contatti Printaqui - Custom apparel'])

@section('content')
    <section class="wrap top-margin">
            <div class="center-text">
                <h1>Contatti</h1>
                <p class="top-margin-mid">Per qualsiasi richiesta di informazioni o preventivi personalizzati, non esitare a contattarci.</p>
            </div>
    </section>

    <section class="section">
        <div class="wrap">
            <div class="grid-2 top-margin">
                <div class="card">
                    <h3>Scrivici una mail</h3>
                    <a class="top-margin-mid">infoprintaqui@gmail.com</a>
                    <div class="top-margin-large">
                      <a href="mailto:infoprintaqui@gmail.com" class="button mobile-fullwidth">Scrivici una mail</a>
                    </div>
                </div>

                <div class="card">
                    <h3>Scrivici su Whatsapp</h3>
                    <a class="top-margin-mid">351 741 3571</a>
                    <div class="top-margin-large">
                      <a href="tel:3517413571" class="button mobile-fullwidth">Whatsapp</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection