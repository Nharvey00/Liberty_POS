<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add void/redo audit columns to orders.
     * - status: tracks whether the order is completed or voided
     * - voided_by: which manager/owner performed the void
     * - voided_at: timestamp of the void action
     * - void_reason: mandatory explanation for audit trail
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('status')->default('completed')->after('discount_amount');
            $table->foreignId('voided_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable()->after('voided_by');
            $table->text('void_reason')->nullable()->after('voided_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['voided_by']);
            $table->dropColumn(['status', 'voided_by', 'voided_at', 'void_reason']);
        });
    }
};
