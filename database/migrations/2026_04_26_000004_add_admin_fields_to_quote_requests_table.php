<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quote_requests', function (Blueprint $table) {
            $table->string('status')->default('new')->after('number')->index();
            $table->text('internal_notes')->nullable()->after('message');
            $table->timestamp('responded_at')->nullable()->after('customer_confirmation_failed_at');
        });
    }

    public function down(): void
    {
        Schema::table('quote_requests', function (Blueprint $table) {
            $table->dropColumn(['status', 'internal_notes', 'responded_at']);
        });
    }
};
