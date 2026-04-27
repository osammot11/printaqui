<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Printaqui' }}</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
@php
    $cartCount = collect(session('cart', []))->sum('quantity');
    $contactUrl = 'https://www.instagram.com/printaqui.it/';
@endphp
<body class="site-shell">
    <header class="site-header">
        <div class="wrap navbar">
            <a href="{{ route('home') }}" class="brand">Printaqui</a>

            <nav class="desktop-nav" aria-label="Navigazione principale">
                <a class="{{ request()->routeIs('home') ? 'is-active' : '' }}" href="{{ route('home') }}">Home</a>
                <a class="{{ request()->routeIs('shop.*', 'products.*') ? 'is-active' : '' }}" href="{{ route('shop.index') }}">Shop</a>
                <a class="{{ request()->routeIs('collections.*') ? 'is-active' : '' }}" href="{{ route('collections.index') }}">Collezioni</a>
                <a class="{{ request()->routeIs('search') ? 'is-active' : '' }}" href="{{ route('search') }}">Cerca</a>
                <a class="{{ request()->routeIs('orders.lookup*') ? 'is-active' : '' }}" href="{{ route('orders.lookup') }}">Ordine</a>
                <a href="{{ route('contatti') }}" rel="noreferrer">Contatti</a>
            </nav>

            <div class="nav-actions">
                <a href="{{ route('quote.create') }}" class="button mobile-hide">Preventivo</a>
                <a class="cart-icon-link {{ request()->routeIs('cart.*') ? 'is-active' : '' }}" href="{{ route('cart.show') }}" aria-label="Carrello, {{ $cartCount }} articoli">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M6.2 6h15l-1.7 8.5a2 2 0 0 1-2 1.5H9.1a2 2 0 0 1-2-1.6L5.3 3H2.8" />
                        <path d="M9 20.2h.01" />
                        <path d="M17 20.2h.01" />
                    </svg>
                    <span class="cart-icon-count">{{ $cartCount }}</span>
                </a>

                <button class="menu-toggle" type="button" aria-label="Apri menu" aria-expanded="false">
                    <span></span>
                    <span></span>
                </button>
            </div>
        </div>

        <div class="mobile-menu">
            <div class="mobile-menu-inner">
                <nav class="mobile-nav" aria-label="Navigazione mobile">
                    <a class="{{ request()->routeIs('home') ? 'is-active' : '' }}" href="{{ route('home') }}">Home</a>
                    <a class="{{ request()->routeIs('shop.*', 'products.*') ? 'is-active' : '' }}" href="{{ route('shop.index') }}">Shop</a>
                    <a class="{{ request()->routeIs('collections.*') ? 'is-active' : '' }}" href="{{ route('collections.index') }}">Collezioni</a>
                    <a class="{{ request()->routeIs('search') ? 'is-active' : '' }}" href="{{ route('search') }}">Cerca</a>
                    <a class="{{ request()->routeIs('orders.lookup*') ? 'is-active' : '' }}" href="{{ route('orders.lookup') }}">Ordine</a>
                    <a class="{{ request()->routeIs('quote.*') ? 'is-active' : '' }}" href="{{ route('quote.create') }}">Preventivo</a>
                    <a href="{{ route('contatti') }}" class="button top-margin-mid" rel="noreferrer">Scrivici</a>
                </nav>
            </div>
        </div>
    </header>

    <main>
        <div class="wrap">
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
        </div>
        @yield('content')
    </main>

    <footer class="site-footer-clean">
        <div class="wrap footer-shell mobile-fullwidth">
            <div class="footer-top">
                <div class="footer-lead">
                    <a href="{{ route('home') }}" class="brand footer-brand">PrintaQui</a>
                    <p>Il partner perfetto per i capi personalizzati della tua azienda.</p>
                </div>

                <div class="footer-links">
                    <div class="footer-column">
                        <span>Esplora</span>
                        <a href="{{ route('home') }}">Home</a>
                        <a href="{{ route('shop.index') }}">Shop</a>
                        <a href="{{ route('collections.index') }}">Collezioni</a>
                        <a href="{{ route('search') }}">Cerca</a>
                        <a href="{{ route('quote.create') }}">Preventivo bulk</a>
                        <a href="{{ route('cart.show') }}">Carrello</a>
                        <a href="{{ route('orders.lookup') }}">Stato ordine</a>
                    </div>

                    <div class="footer-column">
                        <span>Workspace</span>
                        <a href="{{ route('checkout.show') }}">Checkout</a>
                        <a href="{{ route('admin.dashboard') }}">Admin</a>
                        <a href="{{ route('admin.orders.index') }}">Ordini</a>
                    </div>

                    <div class="footer-column">
                        <span>Contatti</span>
                        <a href="{{ $contactUrl }}" target="_blank" rel="noreferrer">Instagram</a>
                        <span class="footer-note">Whatsapp</span>
                    </div>
                </div>
            </div>

            <div class="footer-meta">
                <span>© {{ now()->year }} Printaqui</span>
                <span>La stampa DTF come non l'hai mai provata</span>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const menuToggle = document.querySelector(".menu-toggle");
            const mobileMenu = document.querySelector(".mobile-menu");
            const mobileLinks = document.querySelectorAll(".mobile-nav a");

            if (!menuToggle || !mobileMenu) return;

            function openMenu() {
                mobileMenu.classList.add("is-open");
                menuToggle.classList.add("is-active");
                menuToggle.setAttribute("aria-expanded", "true");
                document.body.style.overflow = "hidden";
            }

            function closeMenu() {
                mobileMenu.classList.remove("is-open");
                menuToggle.classList.remove("is-active");
                menuToggle.setAttribute("aria-expanded", "false");
                document.body.style.overflow = "";
            }

            menuToggle.addEventListener("click", function () {
                if (mobileMenu.classList.contains("is-open")) {
                    closeMenu();
                } else {
                    openMenu();
                }
            });

            mobileMenu.addEventListener("click", function (e) {
                if (e.target === mobileMenu) {
                    closeMenu();
                }
            });

            mobileLinks.forEach(function (link) {
                link.addEventListener("click", function () {
                    closeMenu();
                });
            });

            document.addEventListener("keydown", function (e) {
                if (e.key === "Escape" && mobileMenu.classList.contains("is-open")) {
                    closeMenu();
                }
            });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/ScrollTrigger.min.js" defer></script>
    <script src="{{ asset('js/storefront-animations.js') }}" defer></script>
    @stack('scripts')
</body>
</html>
