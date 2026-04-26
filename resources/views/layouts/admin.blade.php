<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Admin Printaqui' }}</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body class="admin-shell-page">
    <div class="shell">
        <aside class="admin-sidebar stack-mid" id="admin-sidebar">
            <a href="{{ route('admin.dashboard') }}">
                <div style="padding-left: 20px;" class="stack-mid">
                    <h2>PrintaQui</h2>
                    <small>Pannello di amministrazione</small>
                </div>
            </a>
            <nav aria-label="Navigazione admin">
                <a class="{{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}" href="{{ route('admin.dashboard') }}">Dashboard</a>
                <a class="{{ request()->routeIs('admin.products.*') ? 'is-active' : '' }}" href="{{ route('admin.products.index') }}">Prodotti</a>
                <a class="{{ request()->routeIs('admin.categories.*') ? 'is-active' : '' }}" href="{{ route('admin.categories.index') }}">Categorie</a>
                <a class="{{ request()->routeIs('admin.shipping-rates.*') ? 'is-active' : '' }}" href="{{ route('admin.shipping-rates.index') }}">Spedizioni</a>
                <a class="{{ request()->routeIs('admin.discounts.*') ? 'is-active' : '' }}" href="{{ route('admin.discounts.index') }}">Coupon</a>
                <a class="{{ request()->routeIs('admin.orders.*') ? 'is-active' : '' }}" href="{{ route('admin.orders.index') }}">Ordini</a>
                <a class="{{ request()->routeIs('admin.reports.*') ? 'is-active' : '' }}" href="{{ route('admin.reports.sales-by-country') }}">Report</a>
                <a class="{{ request()->routeIs('admin.quote-requests.*') ? 'is-active' : '' }}" href="{{ route('admin.quote-requests.index') }}">Preventivi</a>
                <a class="{{ request()->routeIs('admin.customers.*') ? 'is-active' : '' }}" href="{{ route('admin.customers.index') }}">Clienti</a>
                <a class="{{ request()->routeIs('admin.settings.*') ? 'is-active' : '' }}" href="{{ route('admin.settings.edit') }}">Impostazioni</a>
                <a href="{{ route('home') }}">Storefront</a>
            </nav>
        </aside>

        <main class="admin-main">

            <div class="mobile-panel admin-mobile-panel mobile-only" id="admin-drawer" hidden>
                <nav class="mobile-links" aria-label="Navigazione admin mobile">
                    <a class="{{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}" href="{{ route('admin.dashboard') }}">Dashboard</a>
                    <a class="{{ request()->routeIs('admin.products.*') ? 'is-active' : '' }}" href="{{ route('admin.products.index') }}">Prodotti</a>
                    <a class="{{ request()->routeIs('admin.categories.*') ? 'is-active' : '' }}" href="{{ route('admin.categories.index') }}">Categorie</a>
                    <a class="{{ request()->routeIs('admin.shipping-rates.*') ? 'is-active' : '' }}" href="{{ route('admin.shipping-rates.index') }}">Spedizioni</a>
                    <a class="{{ request()->routeIs('admin.discounts.*') ? 'is-active' : '' }}" href="{{ route('admin.discounts.index') }}">Coupon</a>
                    <a class="{{ request()->routeIs('admin.orders.*') ? 'is-active' : '' }}" href="{{ route('admin.orders.index') }}">Ordini</a>
                    <a class="{{ request()->routeIs('admin.reports.*') ? 'is-active' : '' }}" href="{{ route('admin.reports.sales-by-country') }}">Report</a>
                    <a class="{{ request()->routeIs('admin.quote-requests.*') ? 'is-active' : '' }}" href="{{ route('admin.quote-requests.index') }}">Preventivi</a>
                    <a class="{{ request()->routeIs('admin.customers.*') ? 'is-active' : '' }}" href="{{ route('admin.customers.index') }}">Clienti</a>
                    <a class="{{ request()->routeIs('admin.settings.*') ? 'is-active' : '' }}" href="{{ route('admin.settings.edit') }}">Impostazioni</a>
                    <a href="{{ route('home') }}">Storefront</a>
                </nav>
            </div>

            @if (session('status'))
                <div class="alert">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="errors">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <script>
        document.querySelectorAll('[data-toggle]').forEach((button) => {
            button.addEventListener('click', () => {
                const panel = document.getElementById(button.dataset.toggle);
                const isOpen = button.getAttribute('aria-expanded') === 'true';

                button.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
                panel.hidden = isOpen;
                button.classList.toggle('is-open', !isOpen);
            });
        });
    </script>
</body>
</html>
