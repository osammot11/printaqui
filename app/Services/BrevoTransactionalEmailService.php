<?php

namespace App\Services;

use App\Models\Order;
use App\Models\QuoteRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class BrevoTransactionalEmailService
{
    public function __construct(private readonly TrackingLinkService $trackingLinks)
    {
    }

    public function isConfigured(): bool
    {
        return filled(config('services.brevo.api_key'))
            && filled(config('services.brevo.sender_email'));
    }

    /**
     * @throws RequestException
     */
    public function sendOrderConfirmation(Order $order): bool
    {
        $order->loadMissing(['customer', 'items']);

        if (! $this->isConfigured() || ! $order->customer?->email) {
            return false;
        }

        $payload = [
            'sender' => [
                'name' => (string) config('services.brevo.sender_name', config('app.name')),
                'email' => (string) config('services.brevo.sender_email'),
            ],
            'to' => [[
                'email' => $order->customer->email,
                'name' => trim($order->customer->first_name.' '.$order->customer->last_name),
            ]],
            'params' => $this->orderParams($order),
        ];

        $templateId = config('services.brevo.order_confirmation_template_id');

        if (filled($templateId)) {
            $payload['templateId'] = (int) $templateId;
        } else {
            $payload['subject'] = 'Conferma ordine '.$order->number;
            $payload['htmlContent'] = $this->fallbackHtml($order);
        }

        Http::withHeaders([
            'accept' => 'application/json',
            'api-key' => (string) config('services.brevo.api_key'),
        ])
            ->asJson()
            ->post('https://api.brevo.com/v3/smtp/email', $payload)
            ->throw();

        return true;
    }

    /**
     * @throws RequestException
     */
    public function sendTrackingUpdate(Order $order): bool
    {
        $order->loadMissing('customer');

        if (! $this->isConfigured() || ! $order->customer?->email || ! $order->tracking_url) {
            return false;
        }

        $payload = [
            'sender' => [
                'name' => (string) config('services.brevo.sender_name', config('app.name')),
                'email' => (string) config('services.brevo.sender_email'),
            ],
            'to' => [[
                'email' => $order->customer->email,
                'name' => trim($order->customer->first_name.' '.$order->customer->last_name),
            ]],
            'params' => $this->trackingParams($order),
        ];

        $templateId = config('services.brevo.tracking_update_template_id');

        if (filled($templateId)) {
            $payload['templateId'] = (int) $templateId;
        } else {
            $payload['subject'] = 'Tracking ordine '.$order->number;
            $payload['htmlContent'] = $this->trackingFallbackHtml($order);
        }

        Http::withHeaders([
            'accept' => 'application/json',
            'api-key' => (string) config('services.brevo.api_key'),
        ])
            ->asJson()
            ->post('https://api.brevo.com/v3/smtp/email', $payload)
            ->throw();

        return true;
    }

    /**
     * @throws RequestException
     */
    public function sendQuoteRequestAdminNotification(QuoteRequest $quote): bool
    {
        if (! $this->isConfigured() || blank(config('services.brevo.quote_request_recipient_email'))) {
            return false;
        }

        $payload = [
            'sender' => $this->sender(),
            'to' => [[
                'email' => (string) config('services.brevo.quote_request_recipient_email'),
                'name' => 'Printaqui',
            ]],
            'replyTo' => [
                'email' => $quote->email,
                'name' => $quote->fullName(),
            ],
            'params' => $this->quoteParams($quote),
        ];

        $templateId = config('services.brevo.quote_request_admin_template_id');

        if (filled($templateId)) {
            $payload['templateId'] = (int) $templateId;
        } else {
            $payload['subject'] = 'Nuova richiesta preventivo '.$quote->number;
            $payload['htmlContent'] = $this->quoteAdminFallbackHtml($quote);
        }

        if ($attachment = $this->quoteAttachment($quote)) {
            $payload['attachment'] = [$attachment];
        }

        $this->send($payload);

        return true;
    }

    /**
     * @throws RequestException
     */
    public function sendQuoteRequestCustomerConfirmation(QuoteRequest $quote): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        $payload = [
            'sender' => $this->sender(),
            'to' => [[
                'email' => $quote->email,
                'name' => $quote->fullName(),
            ]],
            'params' => $this->quoteParams($quote),
        ];

        $templateId = config('services.brevo.quote_request_customer_template_id');

        if (filled($templateId)) {
            $payload['templateId'] = (int) $templateId;
        } else {
            $payload['subject'] = 'Abbiamo ricevuto la tua richiesta preventivo '.$quote->number;
            $payload['htmlContent'] = $this->quoteCustomerFallbackHtml($quote);
        }

        $this->send($payload);

        return true;
    }

    private function orderParams(Order $order): array
    {
        return [
            'order_number' => $order->number,
            'first_name' => $order->customer?->first_name,
            'last_name' => $order->customer?->last_name,
            'email' => $order->customer?->email,
            'total' => number_format($order->total_cents / 100, 2, ',', '.').' EUR',
            'subtotal' => number_format($order->subtotal_cents / 100, 2, ',', '.').' EUR',
            'discount' => number_format($order->discount_cents / 100, 2, ',', '.').' EUR',
            'shipping' => number_format($order->shipping_cents / 100, 2, ',', '.').' EUR',
            'discount_code' => $order->discount_code,
            'items' => $order->items->map(fn ($item) => [
                'name' => $item->product_name,
                'sku' => $item->product_sku,
                'quantity' => $item->quantity,
                'total' => number_format($item->line_total_cents / 100, 2, ',', '.').' EUR',
            ])->values()->all(),
            'order_url' => route('checkout.thank-you', $order),
        ];
    }

    private function sender(): array
    {
        return [
            'name' => (string) config('services.brevo.sender_name', config('app.name')),
            'email' => (string) config('services.brevo.sender_email'),
        ];
    }

    private function fallbackHtml(Order $order): string
    {
        $items = $order->items
            ->map(fn ($item) => '<li>'.e($item->product_name).' x '.$item->quantity.'</li>')
            ->implode('');

        return '<h1>Ordine ricevuto</h1>'
            .'<p>Ciao '.e($order->customer?->first_name ?: '').', grazie per il tuo ordine.</p>'
            .'<p><strong>Numero ordine:</strong> '.e($order->number).'</p>'
            .'<ul>'.$items.'</ul>'
            .'<p><strong>Totale:</strong> '.number_format($order->total_cents / 100, 2, ',', '.').' EUR</p>';
    }

    private function trackingParams(Order $order): array
    {
        return [
            'order_number' => $order->number,
            'first_name' => $order->customer?->first_name,
            'last_name' => $order->customer?->last_name,
            'email' => $order->customer?->email,
            'carrier' => $this->trackingLinks->carrierName((string) $order->carrier),
            'tracking_number' => $order->tracking_number,
            'tracking_url' => $order->tracking_url,
            'tracking_button_url' => $order->tracking_url,
        ];
    }

    private function trackingFallbackHtml(Order $order): string
    {
        return '<h1>Il tuo ordine e in viaggio</h1>'
            .'<p>Ciao '.e($order->customer?->first_name ?: '').', il tuo ordine '.e($order->number).' e stato affidato al corriere.</p>'
            .'<p><strong>Corriere:</strong> '.e($this->trackingLinks->carrierName((string) $order->carrier)).'</p>'
            .'<p><strong>Tracking:</strong> '.e((string) $order->tracking_number).'</p>'
            .'<p><a href="'.e((string) $order->tracking_url).'" style="display:inline-block;padding:12px 18px;background:#111;color:#fff;text-decoration:none;border-radius:999px;">Segui spedizione</a></p>';
    }

    private function quoteParams(QuoteRequest $quote): array
    {
        return [
            'quote_number' => $quote->number,
            'first_name' => $quote->first_name,
            'last_name' => $quote->last_name,
            'full_name' => $quote->fullName(),
            'email' => $quote->email,
            'phone' => $quote->phone,
            'company' => $quote->company,
            'product_type' => $quote->product_type,
            'quantity' => $quote->quantity,
            'print_positions' => $quote->print_positions,
            'deadline' => $quote->deadline?->format('d/m/Y'),
            'message' => $quote->message,
            'artwork_original_name' => $quote->artwork_original_name,
        ];
    }

    private function quoteAdminFallbackHtml(QuoteRequest $quote): string
    {
        return '<h1>Nuova richiesta preventivo</h1>'
            .'<p><strong>Numero:</strong> '.e($quote->number).'</p>'
            .'<p><strong>Cliente:</strong> '.e($quote->fullName()).' - '.e($quote->email).'</p>'
            .'<p><strong>Telefono:</strong> '.e((string) $quote->phone).'</p>'
            .'<p><strong>Azienda:</strong> '.e((string) $quote->company).'</p>'
            .'<p><strong>Prodotto:</strong> '.e($quote->product_type).'</p>'
            .'<p><strong>Quantita:</strong> '.e((string) $quote->quantity).'</p>'
            .'<p><strong>Zone stampa:</strong> '.e((string) $quote->print_positions).'</p>'
            .'<p><strong>Scadenza:</strong> '.e((string) $quote->deadline?->format('d/m/Y')).'</p>'
            .'<p><strong>Messaggio:</strong></p><p>'.nl2br(e($quote->message)).'</p>';
    }

    private function quoteCustomerFallbackHtml(QuoteRequest $quote): string
    {
        return '<h1>Richiesta preventivo ricevuta</h1>'
            .'<p>Ciao '.e($quote->first_name).', abbiamo ricevuto la tua richiesta per un ordine custom.</p>'
            .'<p><strong>Numero richiesta:</strong> '.e($quote->number).'</p>'
            .'<p><strong>Prodotto:</strong> '.e($quote->product_type).'</p>'
            .'<p><strong>Quantita:</strong> '.e((string) $quote->quantity).'</p>'
            .'<p>Ti risponderemo il prima possibile con una proposta dedicata.</p>';
    }

    private function quoteAttachment(QuoteRequest $quote): ?array
    {
        if (blank($quote->artwork_path) || ! Storage::exists($quote->artwork_path)) {
            return null;
        }

        return [
            'name' => $quote->artwork_original_name ?: basename($quote->artwork_path),
            'content' => base64_encode(Storage::get($quote->artwork_path)),
        ];
    }

    /**
     * @throws RequestException
     */
    private function send(array $payload): void
    {
        Http::withHeaders([
            'accept' => 'application/json',
            'api-key' => (string) config('services.brevo.api_key'),
        ])
            ->asJson()
            ->post('https://api.brevo.com/v3/smtp/email', $payload)
            ->throw();
    }
}
