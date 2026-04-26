<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_requests', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->index();
            $table->string('phone')->nullable();
            $table->string('company')->nullable();
            $table->string('product_type');
            $table->unsignedInteger('quantity');
            $table->string('print_positions')->nullable();
            $table->date('deadline')->nullable();
            $table->text('message');
            $table->string('artwork_original_name')->nullable();
            $table->string('artwork_path')->nullable();
            $table->string('artwork_mime_type')->nullable();
            $table->unsignedInteger('artwork_size_bytes')->nullable();
            $table->timestamp('admin_notification_sent_at')->nullable();
            $table->timestamp('admin_notification_failed_at')->nullable();
            $table->timestamp('customer_confirmation_sent_at')->nullable();
            $table->timestamp('customer_confirmation_failed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_requests');
    }
};
