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
            $table->string('tracking_number')->nullable()->after('carrier');
            $table->timestamp('tracking_notification_sent_at')->nullable()->after('order_confirmation_failed_at');
            $table->timestamp('tracking_notification_failed_at')->nullable()->after('tracking_notification_sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['tracking_number', 'tracking_notification_sent_at', 'tracking_notification_failed_at']);
        });
    }
};
