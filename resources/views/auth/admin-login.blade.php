@extends('layouts.app', ['title' => 'Login admin - Printaqui'])

@section('content')
    <section class="section">
        <div class="wrap auth-wrap">
            <form class="panel auth-card" method="post" action="{{ route('admin.login.store') }}">
                @csrf
                <div class="hp-field" aria-hidden="true">
                    <label>Sito web</label>
                    <input type="text" name="website" tabindex="-1" autocomplete="off">
                </div>

                <div>
                    <h2>Area admin</h2>
                    <p class="muted top-margin-mid">Accedi per gestire prodotti, ordini e clienti Printaqui.</p>
                </div>

                <div class="row top-margin-large">
                    <label>Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
                </div>

                <div class="row">
                    <label>Password</label>
                    <input type="password" name="password" autocomplete="current-password" required>
                </div>

                <label class="checkbox-row">
                    <input type="checkbox" name="remember" value="1">
                    <span>Ricordami</span>
                </label>

                <button class="top-margin-mid mobile-fullwidth">Entra</button>
            </form>
        </div>
    </section>
@endsection
