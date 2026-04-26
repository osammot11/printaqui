<?php

namespace Tests\Feature;

use App\Models\QuoteRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QuoteRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_quote_request_page_is_available(): void
    {
        $this->get(route('quote.create'))
            ->assertOk()
            ->assertSee('Preventivo custom')
            ->assertSee('Invia richiesta preventivo');
    }

    public function test_customer_can_submit_quote_request_and_brevo_emails_are_sent(): void
    {
        Storage::fake('local');
        Config::set('services.brevo.api_key', 'test-brevo-key');
        Config::set('services.brevo.sender_email', 'shop@example.test');
        Config::set('services.brevo.sender_name', 'Printaqui');
        Config::set('services.brevo.quote_request_recipient_email', 'infoprintaqui@gmail.com');

        Http::fake([
            'api.brevo.com/v3/smtp/email' => Http::response(['messageId' => 'brevo-quote-message'], 201),
        ]);

        $file = UploadedFile::fake()->create('logo.pdf', 200, 'application/pdf');

        $this->post(route('quote.store'), [
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
            'email' => 'cliente@example.test',
            'phone' => '+39 333 1234567',
            'company' => 'Azienda Test',
            'product_type' => 'Hoodie premium',
            'quantity' => 150,
            'print_positions' => 'Fronte cuore, retro grande',
            'deadline' => '2026-06-15',
            'message' => 'Vorrei un preventivo per una felpa custom.',
            'artwork' => $file,
        ])
            ->assertRedirect(route('quote.create'))
            ->assertSessionHas('status');

        $quote = QuoteRequest::firstOrFail();

        $this->assertStringStartsWith('PQ-', $quote->number);
        $this->assertSame('cliente@example.test', $quote->email);
        $this->assertSame(150, $quote->quantity);
        $this->assertSame('logo.pdf', $quote->artwork_original_name);
        $this->assertNotNull($quote->admin_notification_sent_at);
        $this->assertNotNull($quote->customer_confirmation_sent_at);
        Storage::assertExists($quote->artwork_path);

        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => $request->url() === 'https://api.brevo.com/v3/smtp/email'
            && $request->hasHeader('api-key', 'test-brevo-key')
            && $request['to'][0]['email'] === 'infoprintaqui@gmail.com'
            && $request['replyTo']['email'] === 'cliente@example.test'
            && $request['params']['quote_number'] === $quote->number
            && ($request['attachment'][0]['name'] ?? null) === 'logo.pdf');
        Http::assertSent(fn ($request) => $request->url() === 'https://api.brevo.com/v3/smtp/email'
            && $request['to'][0]['email'] === 'cliente@example.test'
            && $request['params']['quote_number'] === $quote->number);
    }

    public function test_unconfigured_brevo_does_not_block_quote_request_storage(): void
    {
        Config::set('services.brevo.api_key', null);
        Config::set('services.brevo.sender_email', null);
        Http::fake();

        $this->post(route('quote.store'), [
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
            'email' => 'cliente@example.test',
            'product_type' => 'T-shirt',
            'quantity' => 80,
            'message' => 'Richiesta senza Brevo configurato.',
        ])
            ->assertRedirect(route('quote.create'))
            ->assertSessionHas('status');

        $quote = QuoteRequest::firstOrFail();

        $this->assertNull($quote->admin_notification_sent_at);
        $this->assertNull($quote->customer_confirmation_sent_at);
        Http::assertNothingSent();
    }

    public function test_honeypot_blocks_spam_quote_requests(): void
    {
        Config::set('services.brevo.api_key', null);
        Config::set('services.brevo.sender_email', null);

        $this
            ->from(route('quote.create'))
            ->post(route('quote.store'), [
                'website' => 'https://spam.example',
                'first_name' => 'Mario',
                'last_name' => 'Rossi',
                'email' => 'cliente@example.test',
                'product_type' => 'T-shirt',
                'quantity' => 80,
                'message' => 'Richiesta compilata da bot.',
            ])
            ->assertRedirect(route('quote.create'))
            ->assertSessionHasErrors('website');

        $this->assertDatabaseCount('quote_requests', 0);
    }
}
