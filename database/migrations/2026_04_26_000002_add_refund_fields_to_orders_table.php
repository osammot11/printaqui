<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('stripe_refund_id')->nullable()->after('stripe_payment_intent_id');
            $table->unsignedInteger('refunded_cents')->default(0)->after('total_cents');
            $table->timestamp('refunded_at')->nullable()->after('fulfilled_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['stripe_refund_id', 'refunded_cents', 'refunded_at']);
        });
    }
};
