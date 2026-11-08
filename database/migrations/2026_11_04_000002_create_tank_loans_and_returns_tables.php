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
        if (!Schema::hasTable('tank_loans')) {
            Schema::create('tank_loans', function (Blueprint $table) {
                $table->id();
                $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
                $table->foreignId('stock_out_id')->nullable()->constrained('stock_outs')->nullOnDelete();
                $table->string('tank_condition_out'); // 'filled' or 'empty_shell'
                $table->integer('quantity_borrowed');
                $table->integer('quantity_returned')->default(0);
                $table->string('status')->default('borrowed'); // 'borrowed', 'partially_returned', 'returned', 'voided'
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
                $table->timestamp('borrowed_at')->useCurrent();
                $table->timestamp('returned_at')->nullable();
                $table->timestamp('voided_at')->nullable();
                $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('void_reason')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('tank_loan_returns')) {
            Schema::create('tank_loan_returns', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tank_loan_id')->constrained('tank_loans')->cascadeOnDelete();
                $table->foreignId('stock_in_id')->nullable()->constrained('stock_ins')->nullOnDelete();
                $table->integer('quantity_returned');
                $table->string('returned_as'); // 'empty_shell' or 'filled'
                $table->foreignId('received_by')->constrained('users')->restrictOnDelete();
                $table->text('remarks')->nullable();
                $table->timestamp('returned_at')->useCurrent();
                $table->boolean('is_voided')->default(false);
                $table->timestamp('voided_at')->nullable();
                $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('void_reason')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tank_loan_returns');
        Schema::dropIfExists('tank_loans');
    }
};
