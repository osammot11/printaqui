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
            $table->unsignedBigInteger('discount_code_id')->nullable()->index()->after('currency');
            $table->string('discount_code')->nullable()->after('discount_code_id');
            $table->timestamp('discount_usage_recorded_at')->nullable()->after('stock_decremented_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['discount_code_id', 'discount_code', 'discount_usage_recorded_at']);
        });
    }
};
