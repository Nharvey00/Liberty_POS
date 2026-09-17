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
        Schema::create('credit_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credit_account_id')->constrained('credit_accounts')->cascadeOnDelete();
            $table->string('transaction_type'); // 'Charge' (Debt goes up) or 'Payment' (Debt goes down)
            $table->decimal('amount', 10, 2);
            
            // Link to exact transaction source:
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete(); // If it was a Charge
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete(); // If it was a Payment
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_ledgers');
    }
};
