<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('order_confirmation_sent_at')->nullable()->after('discount_usage_recorded_at');
            $table->timestamp('order_confirmation_failed_at')->nullable()->after('order_confirmation_sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['order_confirmation_sent_at', 'order_confirmation_failed_at']);
        });
    }
};
