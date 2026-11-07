<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('stock_ins') && !Schema::hasColumn('stock_ins', 'empty_received_qty')) {
            Schema::table('stock_ins', function (Blueprint $table) {
                $table->integer('empty_received_qty')->default(0)->after('quantity_received');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('stock_ins') && Schema::hasColumn('stock_ins', 'empty_received_qty')) {
            Schema::table('stock_ins', function (Blueprint $table) {
                $table->dropColumn('empty_received_qty');
            });
        }
    }
};
