<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations to establish the centralized Audit Trail,
     * Inventory Movement Ledger, and domain accountability columns.
     */
    public function up(): void
    {
        // 1. Centralized Append-Only Audit Trail Table
        if (!Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();
                $table->uuid('event_uuid')->unique();
                $table->string('correlation_id', 64)->nullable()->index();

                // Authenticated Actor Identity & Historical Snapshots
                $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('actor_name_snapshot', 150)->nullable();
                $table->string('actor_role_snapshot', 80)->nullable();

                // Event Classification & Target Entity
                $table->string('module', 80)->index();
                $table->string('action', 100)->index();
                $table->string('entity_type', 100)->nullable()->index();
                $table->string('entity_id', 64)->nullable()->index();
                $table->string('transaction_reference', 120)->nullable()->index();

                // Outcome & Approval Tracking
                $table->string('status', 40)->default('completed')->index();
                $table->string('approval_status', 40)->default('not_required');
                $table->foreignId('approver_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('approver_name_snapshot', 150)->nullable();
                $table->timestamp('approved_at')->nullable();

                // State Snapshots & Context
                $table->json('before_values')->nullable();
                $table->json('after_values')->nullable();
                $table->text('reason')->nullable();

                // Request & Source Metadata
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->string('source_channel', 50)->default('web');

                $table->timestamps();
                $table->index('created_at');
            });
        }

        // 2. Dedicated Append-Only Inventory Movement Ledger Table
        if (!Schema::hasTable('inventory_movements')) {
            Schema::create('inventory_movements', function (Blueprint $table) {
                $table->id();
                $table->uuid('movement_uuid')->unique();
                $table->string('reference_no', 120)->nullable()->index();

                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->string('product_name_snapshot', 150);
                $table->string('movement_type', 60)->index();

                // Filled Stock Snapshot & Delta
                $table->integer('filled_qty_before')->default(0);
                $table->integer('filled_qty_change')->default(0);
                $table->integer('filled_qty_after')->default(0);

                // Empty Shell Snapshot & Delta
                $table->integer('empty_qty_before')->default(0);
                $table->integer('empty_qty_change')->default(0);
                $table->integer('empty_qty_after')->default(0);

                // Pricing / Valuation Snapshot at Movement Time
                $table->decimal('unit_cost', 10, 2)->nullable();
                $table->decimal('unit_price', 10, 2)->nullable();

                // Polymorphic-Style Source Link (Order, StockIn, StockOut, Product)
                $table->string('source_type', 80)->nullable()->index();
                $table->unsignedBigInteger('source_id')->nullable()->index();

                // Accountability & Approval
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('reason')->nullable();
                $table->string('status', 40)->default('completed');

                $table->timestamps();
                $table->index('created_at');
            });
        }

        // 3. Order Items: Historical Unit Price Snapshot
        if (Schema::hasTable('order_items') && !Schema::hasColumn('order_items', 'unit_price')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->decimal('unit_price', 10, 2)->nullable()->after('quantity');
            });

            // Backfill unit_price for existing order_items where quantity > 0
            DB::table('order_items')
                ->whereNull('unit_price')
                ->where('quantity', '>', 0)
                ->update([
                    'unit_price' => DB::raw('ROUND(subtotal / quantity, 2)'),
                ]);
        }

        // 4. Stock-Ins: Actor, Approver, Supplier, Cost & Before/After Snapshots
        if (Schema::hasTable('stock_ins')) {
            Schema::table('stock_ins', function (Blueprint $table) {
                if (!Schema::hasColumn('stock_ins', 'user_id')) {
                    $table->foreignId('user_id')->nullable()->after('product_id')->constrained('users')->nullOnDelete();
                }
                if (!Schema::hasColumn('stock_ins', 'approved_by')) {
                    $table->foreignId('approved_by')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
                }
                if (!Schema::hasColumn('stock_ins', 'reference_no')) {
                    $table->string('reference_no', 100)->nullable()->after('approved_by');
                }
                if (!Schema::hasColumn('stock_ins', 'supplier_name')) {
                    $table->string('supplier_name', 150)->nullable()->after('reference_no');
                }
                if (!Schema::hasColumn('stock_ins', 'unit_cost')) {
                    $table->decimal('unit_cost', 10, 2)->nullable()->after('empty_returned_qty');
                }
                if (!Schema::hasColumn('stock_ins', 'filled_qty_before')) {
                    $table->integer('filled_qty_before')->nullable()->after('unit_cost');
                    $table->integer('filled_qty_after')->nullable()->after('filled_qty_before');
                    $table->integer('empty_qty_before')->nullable()->after('filled_qty_after');
                    $table->integer('empty_qty_after')->nullable()->after('empty_qty_before');
                }
            });
        }

        // 5. Stock-Outs: Actor, Approver, Reference, Category & Before/After Snapshots
        if (Schema::hasTable('stock_outs')) {
            Schema::table('stock_outs', function (Blueprint $table) {
                if (!Schema::hasColumn('stock_outs', 'user_id')) {
                    $table->foreignId('user_id')->nullable()->after('product_id')->constrained('users')->nullOnDelete();
                }
                if (!Schema::hasColumn('stock_outs', 'approved_by')) {
                    $table->foreignId('approved_by')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
                }
                if (!Schema::hasColumn('stock_outs', 'reference_no')) {
                    $table->string('reference_no', 100)->nullable()->after('approved_by');
                }
                if (!Schema::hasColumn('stock_outs', 'movement_category')) {
                    $table->string('movement_category', 60)->default('manual_adjustment')->after('reference_no');
                }
                if (!Schema::hasColumn('stock_outs', 'filled_qty_before')) {
                    $table->integer('filled_qty_before')->nullable()->after('empty_quantity_removed');
                    $table->integer('filled_qty_after')->nullable()->after('filled_qty_before');
                    $table->integer('empty_qty_before')->nullable()->after('filled_qty_after');
                    $table->integer('empty_qty_after')->nullable()->after('empty_qty_before');
                }
            });
        }

        // 6. Products: SKU, Cost Price, Reorder Level & Creator/Updater Accountability
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (!Schema::hasColumn('products', 'sku')) {
                    $table->string('sku', 80)->nullable()->unique()->after('name');
                }
                if (!Schema::hasColumn('products', 'cost_price')) {
                    $table->decimal('cost_price', 10, 2)->default(0.00)->after('price');
                }
                if (!Schema::hasColumn('products', 'reorder_level')) {
                    $table->integer('reorder_level')->default(5)->after('empty_quantity');
                }
                if (!Schema::hasColumn('products', 'created_by')) {
                    $table->foreignId('created_by')->nullable()->after('standard_capacity_kg')->constrained('users')->nullOnDelete();
                }
                if (!Schema::hasColumn('products', 'updated_by')) {
                    $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
                }
            });
        }

        // 7. Credit Accounts: Credit Limit & Approval Lifecycle Columns
        if (Schema::hasTable('credit_accounts')) {
            Schema::table('credit_accounts', function (Blueprint $table) {
                if (!Schema::hasColumn('credit_accounts', 'credit_limit')) {
                    $table->decimal('credit_limit', 12, 2)->nullable()->after('customer_id');
                }
                if (!Schema::hasColumn('credit_accounts', 'status')) {
                    $table->string('status', 40)->default('approved')->after('is_active');
                }
                if (!Schema::hasColumn('credit_accounts', 'requested_by')) {
                    $table->foreignId('requested_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
                }
                if (!Schema::hasColumn('credit_accounts', 'approved_by')) {
                    $table->foreignId('approved_by')->nullable()->after('requested_by')->constrained('users')->nullOnDelete();
                }
                if (!Schema::hasColumn('credit_accounts', 'approved_at')) {
                    $table->timestamp('approved_at')->nullable()->after('approved_by');
                }
                if (!Schema::hasColumn('credit_accounts', 'approval_remarks')) {
                    $table->text('approval_remarks')->nullable()->after('approved_at');
                }
            });
        }

        // 8. Payments: Collector User, Method, Reference, Notes & Balance Snapshots
        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                if (!Schema::hasColumn('payments', 'user_id')) {
                    $table->foreignId('user_id')->nullable()->after('credit_account_id')->constrained('users')->nullOnDelete();
                }
                if (!Schema::hasColumn('payments', 'payment_method')) {
                    $table->string('payment_method', 50)->default('Cash')->after('amount');
                }
                if (!Schema::hasColumn('payments', 'reference_no')) {
                    $table->string('reference_no', 100)->nullable()->after('payment_method');
                }
                if (!Schema::hasColumn('payments', 'notes')) {
                    $table->text('notes')->nullable()->after('reference_no');
                }
                if (!Schema::hasColumn('payments', 'balance_before')) {
                    $table->decimal('balance_before', 12, 2)->nullable()->after('notes');
                    $table->decimal('balance_after', 12, 2)->nullable()->after('balance_before');
                }
            });
        }

        // 9. Credit Ledgers: Actor User, Notes & Balance Snapshots
        if (Schema::hasTable('credit_ledgers')) {
            Schema::table('credit_ledgers', function (Blueprint $table) {
                if (!Schema::hasColumn('credit_ledgers', 'user_id')) {
                    $table->foreignId('user_id')->nullable()->after('payment_id')->constrained('users')->nullOnDelete();
                }
                if (!Schema::hasColumn('credit_ledgers', 'balance_before')) {
                    $table->decimal('balance_before', 12, 2)->nullable()->after('user_id');
                    $table->decimal('balance_after', 12, 2)->nullable()->after('balance_before');
                }
                if (!Schema::hasColumn('credit_ledgers', 'notes')) {
                    $table->text('notes')->nullable()->after('balance_after');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('credit_ledgers')) {
            Schema::table('credit_ledgers', function (Blueprint $table) {
                $table->dropConstrainedForeignIdIfExists('user_id');
                $table->dropColumn(['balance_before', 'balance_after', 'notes']);
            });
        }

        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropConstrainedForeignIdIfExists('user_id');
                $table->dropColumn(['payment_method', 'reference_no', 'notes', 'balance_before', 'balance_after']);
            });
        }

        if (Schema::hasTable('credit_accounts')) {
            Schema::table('credit_accounts', function (Blueprint $table) {
                $table->dropConstrainedForeignIdIfExists('requested_by');
                $table->dropConstrainedForeignIdIfExists('approved_by');
                $table->dropColumn(['credit_limit', 'status', 'approved_at', 'approval_remarks']);
            });
        }

        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropUnique(['sku']);
                $table->dropConstrainedForeignIdIfExists('created_by');
                $table->dropConstrainedForeignIdIfExists('updated_by');
                $table->dropColumn(['sku', 'cost_price', 'reorder_level']);
            });
        }

        if (Schema::hasTable('stock_outs')) {
            Schema::table('stock_outs', function (Blueprint $table) {
                $table->dropConstrainedForeignIdIfExists('user_id');
                $table->dropConstrainedForeignIdIfExists('approved_by');
                $table->dropColumn([
                    'reference_no',
                    'movement_category',
                    'filled_qty_before',
                    'filled_qty_after',
                    'empty_qty_before',
                    'empty_qty_after',
                ]);
            });
        }

        if (Schema::hasTable('stock_ins')) {
            Schema::table('stock_ins', function (Blueprint $table) {
                $table->dropConstrainedForeignIdIfExists('user_id');
                $table->dropConstrainedForeignIdIfExists('approved_by');
                $table->dropColumn([
                    'reference_no',
                    'supplier_name',
                    'unit_cost',
                    'filled_qty_before',
                    'filled_qty_after',
                    'empty_qty_before',
                    'empty_qty_after',
                ]);
            });
        }

        if (Schema::hasTable('order_items') && Schema::hasColumn('order_items', 'unit_price')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->dropColumn('unit_price');
            });
        }

        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('audit_logs');
    }
};
