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
        Schema::table('stock_ins', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_ins', 'empty_shells_received')) {
                $table->integer('empty_shells_received')->default(0)->after('empty_returned_qty');
            }
            if (!Schema::hasColumn('stock_ins', 'is_voided')) {
                $table->boolean('is_voided')->default(false)->after('remarks');
            }
            if (!Schema::hasColumn('stock_ins', 'voided_at')) {
                $table->timestamp('voided_at')->nullable()->after('is_voided');
            }
            if (!Schema::hasColumn('stock_ins', 'voided_by')) {
                $table->foreignId('voided_by')->nullable()->after('voided_at')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('stock_ins', 'void_reason')) {
                $table->text('void_reason')->nullable()->after('voided_by');
            }
        });

        Schema::table('stock_outs', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_outs', 'is_voided')) {
                $table->boolean('is_voided')->default(false)->after('reason');
            }
            if (!Schema::hasColumn('stock_outs', 'voided_at')) {
                $table->timestamp('voided_at')->nullable()->after('is_voided');
            }
            if (!Schema::hasColumn('stock_outs', 'voided_by')) {
                $table->foreignId('voided_by')->nullable()->after('voided_at')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('stock_outs', 'void_reason')) {
                $table->text('void_reason')->nullable()->after('voided_by');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_ins', function (Blueprint $table) {
            if (Schema::hasColumn('stock_ins', 'voided_by')) {
                $table->dropConstrainedForeignId('voided_by');
            }
            $cols = array_filter(['empty_shells_received', 'is_voided', 'voided_at', 'void_reason'], fn ($c) => Schema::hasColumn('stock_ins', $c));
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });

        Schema::table('stock_outs', function (Blueprint $table) {
            if (Schema::hasColumn('stock_outs', 'voided_by')) {
                $table->dropConstrainedForeignId('voided_by');
            }
            $cols = array_filter(['is_voided', 'voided_at', 'void_reason'], fn ($c) => Schema::hasColumn('stock_outs', $c));
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
