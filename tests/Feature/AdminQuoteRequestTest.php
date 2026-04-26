<?php

namespace Tests\Feature;

use App\Models\QuoteRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminQuoteRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_quote_request_list_and_detail(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $quote = $this->quoteRequest([
            'company' => 'Azienda Test',
            'product_type' => 'Hoodie premium',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.quote-requests.index'))
            ->assertOk()
            ->assertSee('Preventivi')
            ->assertSee($quote->number)
            ->assertSee('Azienda Test')
            ->assertSee('Hoodie premium');

        $this->actingAs($admin)
            ->get(route('admin.quote-requests.show', $quote))
            ->assertOk()
            ->assertSee($quote->number)
            ->assertSee('Messaggio cliente')
            ->assertSee('Gestione interna');
    }

    public function test_admin_can_update_quote_request_status_and_notes(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $quote = $this->quoteRequest();

        $this->actingAs($admin)
            ->patch(route('admin.quote-requests.update', $quote), [
                'status' => 'responded',
                'internal_notes' => 'Preventivo inviato a 12 euro cad.',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Richiesta preventivo aggiornata.');

        $quote->refresh();

        $this->assertSame('responded', $quote->status);
        $this->assertSame('Preventivo inviato a 12 euro cad.', $quote->internal_notes);
        $this->assertNotNull($quote->responded_at);
    }

    public function test_admin_can_filter_quote_requests_by_status_and_search(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $visible = $this->quoteRequest([
            'status' => 'reviewing',
            'company' => 'Studio Milano',
            'number' => 'PQ-FILTER-OK',
        ]);
        $this->quoteRequest([
            'status' => 'new',
            'company' => 'Altro Cliente',
            'number' => 'PQ-FILTER-NO',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.quote-requests.index', [
                'status' => 'reviewing',
                'q' => 'Milano',
            ]))
            ->assertOk()
            ->assertSee($visible->number)
            ->assertDontSee('PQ-FILTER-NO');
    }

    public function test_admin_can_download_quote_request_artwork(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create(['is_admin' => true]);
        $quote = $this->quoteRequest([
            'artwork_original_name' => 'mockup.pdf',
            'artwork_path' => 'quote-requests/1/mockup.pdf',
            'artwork_mime_type' => 'application/pdf',
            'artwork_size_bytes' => 128,
        ]);

        Storage::put($quote->artwork_path, 'pdf-content');

        $this->actingAs($admin)
            ->get(route('admin.quote-requests.artwork.download', $quote))
            ->assertOk()
            ->assertDownload('mockup.pdf');
    }

    private function quoteRequest(array $overrides = []): QuoteRequest
    {
        return QuoteRequest::create(array_merge([
            'number' => 'PQ-TEST-123',
            'status' => 'new',
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
            'email' => 'cliente@example.test',
            'phone' => '+39 333 1234567',
            'company' => null,
            'product_type' => 'T-shirt',
            'quantity' => 100,
            'print_positions' => 'Fronte cuore',
            'message' => 'Vorrei un preventivo custom.',
        ], $overrides));
    }
}
