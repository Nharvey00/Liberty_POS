<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add discount classification and invoice tracking columns.
     * - discount_type: categorizes the discount (regular, senior, pwd, promo)
     * - senior_id: Senior Citizen ID number (required when discount_type = 'senior')
     * - invoice_number: auto-generated reference for loan/payment tracking
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('discount_type')->nullable()->after('discount_amount');
            $table->string('senior_id')->nullable()->after('discount_type');
            $table->string('invoice_number')->nullable()->unique()->after('senior_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['discount_type', 'senior_id', 'invoice_number']);
        });
    }
};
