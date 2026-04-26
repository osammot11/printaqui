# Deploy Printaqui su VPS IONOS

Guida pensata per una VPS Ubuntu con Nginx, PHP-FPM, MySQL/MariaDB, Composer, Node.js e Git.

## 1. Requisiti server

Pacchetti indicativi:

```bash
sudo apt update
sudo apt install -y nginx mysql-server git unzip curl
sudo apt install -y php8.3-fpm php8.3-cli php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip php8.3-bcmath php8.3-intl
```

Installa Composer e Node.js in base alla configurazione della VPS. Per Laravel 13 serve PHP 8.3 o superiore.

## 2. Database

Esempio MySQL:

```sql
CREATE DATABASE printaqui CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'printaqui'@'localhost' IDENTIFIED BY 'PASSWORD_FORTE';
GRANT ALL PRIVILEGES ON printaqui.* TO 'printaqui'@'localhost';
FLUSH PRIVILEGES;
```

## 3. Clone progetto

```bash
sudo mkdir -p /var/www
sudo chown -R $USER:www-data /var/www
cd /var/www
git clone https://github.com/osammot11/printaqui.git
cd printaqui
```

## 4. File `.env`

```bash
cp .env.example .env
nano .env
```

Valori minimi produzione:

```env
APP_NAME=Printaqui
APP_ENV=production
APP_DEBUG=false
APP_URL=https://TUO-DOMINIO.IT

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=printaqui
DB_USERNAME=printaqui
DB_PASSWORD=PASSWORD_FORTE

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database

STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=

BREVO_API_KEY=
BREVO_SENDER_EMAIL=
BREVO_SENDER_NAME="${APP_NAME}"
QUOTE_REQUEST_RECIPIENT_EMAIL=infoprintaqui@gmail.com
```

Poi:

```bash
php artisan key:generate
```

## 5. Primo deploy

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo chown -R www-data:www-data storage bootstrap/cache
```

## 6. Nginx

Usa `deploy/nginx-printaqui.conf.example` come base.

La document root deve puntare a:

```text
/var/www/printaqui/public
```

Dopo aver creato il file Nginx:

```bash
sudo nginx -t
sudo systemctl reload nginx
```

## 7. Deploy successivi

Dalla cartella `/var/www/printaqui`:

```bash
bash deploy/deploy.sh
```

Lo script fa pull da GitHub, install dipendenze, build asset, migration, cache e permessi.

## 8. Webhook Stripe

Endpoint da configurare in Stripe:

```text
https://TUO-DOMINIO.IT/webhooks/stripe
```

Eventi utili:

- `payment_intent.succeeded`
- `payment_intent.processing`
- `payment_intent.payment_failed`
- `payment_intent.canceled`
- `payment_intent.requires_action`

Inserisci il signing secret in:

```env
STRIPE_WEBHOOK_SECRET=
```

## 9. Cose da controllare dopo il deploy

- Homepage visibile.
- Login admin funzionante.
- `php artisan migrate:status`.
- Upload immagini prodotto.
- Checkout Stripe in test.
- Email Brevo di ordine, tracking e preventivo.
- Report admin CSV.
