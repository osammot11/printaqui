# Printaqui

E-commerce Laravel per vendita di blank apparel e capi personalizzati DTF.

## Funzioni principali

- Storefront con shop, collezioni, ricerca, carrello e checkout Stripe.
- Configurazione prodotto con varianti, quantita bulk e zone di stampa.
- Upload file stampa per posizione.
- Admin per prodotti, categorie, spedizioni, coupon, ordini, preventivi, clienti e report.
- Email transazionali Brevo per conferma ordine, tracking e richieste preventivo.
- Report CSV giornaliero per paese, pensato per il commercialista.

## Setup locale

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
npm run build
php artisan serve
```

Il seeder crea anche l'utente admin iniziale. Configura prima in `.env`:

```env
ADMIN_NAME="Printaqui Admin"
ADMIN_EMAIL=admin@printaqui.test
ADMIN_PASSWORD=password
```

Poi rilancia `php artisan db:seed` se cambi queste credenziali prima del primo deploy.

## Variabili importanti

Stripe:

```env
STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=
STRIPE_CURRENCY=EUR
STRIPE_PAYMENT_METHODS=card
```

Brevo:

```env
BREVO_API_KEY=
BREVO_SENDER_EMAIL=
BREVO_SENDER_NAME="${APP_NAME}"
BREVO_ORDER_CONFIRMATION_TEMPLATE_ID=
BREVO_TRACKING_UPDATE_TEMPLATE_ID=
BREVO_QUOTE_REQUEST_ADMIN_TEMPLATE_ID=
BREVO_QUOTE_REQUEST_CUSTOMER_TEMPLATE_ID=
QUOTE_REQUEST_RECIPIENT_EMAIL=infoprintaqui@gmail.com
```

## Deploy VPS

Guida completa per IONOS/Ubuntu:

```text
docs/deploy-ionos-vps.md
```

Flusso consigliato:

```bash
git pull origin main
composer install --no-dev --optimize-autoloader
npm install
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link
```

Permessi tipici:

```bash
chmod -R ug+rw storage bootstrap/cache
```

La VPS deve puntare la document root alla cartella `public`.

## Test

```bash
php artisan test
```
