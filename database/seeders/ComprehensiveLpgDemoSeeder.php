<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\CreditAccount;
use App\Models\CreditLedger;
use App\Models\Customer;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\StatementOfAccount;
use App\Models\StockIn;
use App\Models\StockOut;
use App\Models\TankLoan;
use App\Models\TankLoanReturn;
use App\Models\User;
use App\Services\AuditTrailService;
use App\Services\InventoryLedgerService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ComprehensiveLpgDemoSeeder extends Seeder
{
    public function run(): void
    {
        // Production safety guard: never inject demo data outside local/testing environments
        if (!app()->environment(['local', 'testing'])) {
            return;
        }

        // Idempotency guard: skip if already seeded
        if (Product::withTrashed()->where('sku', 'LPG-SOL-11QR')->exists()) {
            return;
        }

        DB::transaction(function () {
            $owner    = User::where('email', 'admin@gmail.com')->first() ?? User::first();
            $manager  = User::where('email', 'manager@libertylpg.com')->first() ?? $owner;
            $cashier1 = User::where('email', 'cashier1@libertylpg.com')->first() ?? $owner;
            $cashier2 = User::where('email', 'cashier2@libertylpg.com')->first() ?? $cashier1;

            $now = Carbon::now();

            // ====================================================================
            // 1. 15 DIVERSE LPG PRODUCTS (11 CYLINDERS & 4 ACCESSORIES)
            // ====================================================================
            $productSpecs = [
                // 11 LPG Cylinders (standard_capacity_kg > 0, dual-stock: filled stock_quantity + empty_quantity)
                [
                    'key'                  => 'solane_11_qr',
                    'name'                 => 'Solane 11kg Quick-Release',
                    'sku'                  => 'LPG-SOL-11QR',
                    'price'                => 980.00,
                    'cost_price'           => 860.00,
                    'new_cylinder_price'   => 2280.00,
                    'stock_quantity'       => 48,
                    'empty_quantity'       => 24,
                    'reorder_level'        => 15,
                    'standard_capacity_kg' => 11.00,
                ],
                [
                    'key'                  => 'solane_11_st',
                    'name'                 => 'Solane 11kg Screw-Type',
                    'sku'                  => 'LPG-SOL-11ST',
                    'price'                => 980.00,
                    'cost_price'           => 860.00,
                    'new_cylinder_price'   => 2280.00,
                    'stock_quantity'       => 35,
                    'empty_quantity'       => 18,
                    'reorder_level'        => 12,
                    'standard_capacity_kg' => 11.00,
                ],
                [
                    'key'                  => 'pryce_11_pol',
                    'name'                 => 'Prycegas 11kg POL',
                    'sku'                  => 'LPG-PRY-11POL',
                    'price'                => 950.00,
                    'cost_price'           => 830.00,
                    'new_cylinder_price'   => 2200.00,
                    'stock_quantity'       => 40,
                    'empty_quantity'       => 20,
                    'reorder_level'        => 15,
                    'standard_capacity_kg' => 11.00,
                ],
                [
                    'key'                  => 'pryce_11_cmp',
                    'name'                 => 'Prycegas 11kg Compact',
                    'sku'                  => 'LPG-PRY-11CMP',
                    'price'                => 950.00,
                    'cost_price'           => 830.00,
                    'new_cylinder_price'   => 2200.00,
                    'stock_quantity'       => 28,
                    'empty_quantity'       => 15,
                    'reorder_level'        => 10,
                    'standard_capacity_kg' => 11.00,
                ],
                [
                    'key'                  => 'phoenix_11',
                    'name'                 => 'Phoenix Super LPG 11kg',
                    'sku'                  => 'LPG-PHX-11SR',
                    'price'                => 940.00,
                    'cost_price'           => 820.00,
                    'new_cylinder_price'   => 2180.00,
                    'stock_quantity'       => 32,
                    'empty_quantity'       => 14,
                    'reorder_level'        => 10,
                    'standard_capacity_kg' => 11.00,
                ],
                [
                    'key'                  => 'isla_11_pol',
                    'name'                 => 'Isla Gas 11kg POL',
                    'sku'                  => 'LPG-ISL-11POL',
                    'price'                => 960.00,
                    'cost_price'           => 840.00,
                    'new_cylinder_price'   => 2220.00,
                    'stock_quantity'       => 22,
                    'empty_quantity'       => 10,
                    'reorder_level'        => 10,
                    'standard_capacity_kg' => 11.00,
                ],
                [
                    'key'                  => 'solane_50_ind',
                    'name'                 => 'Solane 50kg Industrial POL',
                    'sku'                  => 'LPG-SOL-50IND',
                    'price'                => 4200.00,
                    'cost_price'           => 3750.00,
                    'new_cylinder_price'   => 8500.00,
                    'stock_quantity'       => 12,
                    'empty_quantity'       => 6,
                    'reorder_level'        => 5,
                    'standard_capacity_kg' => 50.00,
                ],
                [
                    'key'                  => 'pryce_50_ind',
                    'name'                 => 'Prycegas 50kg Industrial',
                    'sku'                  => 'LPG-PRY-50IND',
                    'price'                => 4100.00,
                    'cost_price'           => 3680.00,
                    'new_cylinder_price'   => 8300.00,
                    'stock_quantity'       => 10,
                    'empty_quantity'       => 5,
                    'reorder_level'        => 5,
                    'standard_capacity_kg' => 50.00,
                ],
                [
                    'key'                  => 'solane_27_cmp',
                    'name'                 => 'Solane 2.7kg Camping Tank',
                    'sku'                  => 'LPG-SOL-27CMP',
                    'price'                => 330.00,
                    'cost_price'           => 270.00,
                    'new_cylinder_price'   => 1150.00,
                    'stock_quantity'       => 18,
                    'empty_quantity'       => 12,
                    'reorder_level'        => 8,
                    'standard_capacity_kg' => 2.70,
                ],
                [
                    'key'                  => 'pryce_27_pwr',
                    'name'                 => 'Prycegas 2.7kg Powergas',
                    'sku'                  => 'LPG-PRY-27PWR',
                    'price'                => 320.00,
                    'cost_price'           => 260.00,
                    'new_cylinder_price'   => 1100.00,
                    'stock_quantity'       => 15,
                    'empty_quantity'       => 9,
                    'reorder_level'        => 8,
                    'standard_capacity_kg' => 2.70,
                ],
                [
                    'key'                  => 'gasul_11_snp',
                    'name'                 => 'Gasul 11kg Snap-On',
                    'sku'                  => 'LPG-GSL-11SNP',
                    'price'                => 970.00,
                    'cost_price'           => 850.00,
                    'new_cylinder_price'   => 2250.00,
                    'stock_quantity'       => 25,
                    'empty_quantity'       => 11,
                    'reorder_level'        => 10,
                    'standard_capacity_kg' => 11.00,
                ],

                // 4 Accessories & Parts (standard_capacity_kg = null, only stock_quantity, empty_quantity = 0)
                [
                    'key'                  => 'acc_hose_15m',
                    'name'                 => 'Heavy Duty LPG Hose (1.5m)',
                    'sku'                  => 'ACC-HOSE-15M',
                    'price'                => 180.00,
                    'cost_price'           => 110.00,
                    'new_cylinder_price'   => null,
                    'stock_quantity'       => 35,
                    'empty_quantity'       => 0,
                    'reorder_level'        => 10,
                    'standard_capacity_kg' => null,
                ],
                [
                    'key'                  => 'acc_reg_lpqr',
                    'name'                 => 'Low Pressure Quick-Release Regulator',
                    'sku'                  => 'ACC-REG-LPQR',
                    'price'                => 380.00,
                    'cost_price'           => 240.00,
                    'new_cylinder_price'   => null,
                    'stock_quantity'       => 22,
                    'empty_quantity'       => 0,
                    'reorder_level'        => 8,
                    'standard_capacity_kg' => null,
                ],
                [
                    'key'                  => 'acc_reg_hpind',
                    'name'                 => 'High Pressure Industrial Regulator',
                    'sku'                  => 'ACC-REG-HPIND',
                    'price'                => 750.00,
                    'cost_price'           => 490.00,
                    'new_cylinder_price'   => null,
                    'stock_quantity'       => 14,
                    'empty_quantity'       => 0,
                    'reorder_level'        => 5,
                    'standard_capacity_kg' => null,
                ],
                [
                    'key'                  => 'acc_clamps',
                    'name'                 => 'Stainless Hose Clamps (Pair)',
                    'sku'                  => 'ACC-CLMP-SS2',
                    'price'                => 45.00,
                    'cost_price'           => 20.00,
                    'new_cylinder_price'   => null,
                    'stock_quantity'       => 60,
                    'empty_quantity'       => 0,
                    'reorder_level'        => 20,
                    'standard_capacity_kg' => null,
                ],
            ];

            /** @var array<string, Product> $products */
            $products = [];
            /** @var array<string, array{stock: int, empty: int}> $targetStocks */
            $targetStocks = [];

            $initialSeededAt = $now->copy()->subDays(65)->setTime(8, 0, 0);

            foreach ($productSpecs as $idx => $spec) {
                $targetStocks[$spec['key']] = [
                    'stock' => (int) $spec['stock_quantity'],
                    'empty' => (int) $spec['empty_quantity'],
                ];

                $product = new Product([
                    'name'                 => $spec['name'],
                    'sku'                  => $spec['sku'],
                    'price'                => $spec['price'],
                    'cost_price'           => $spec['cost_price'],
                    'new_cylinder_price'   => $spec['new_cylinder_price'],
                    'stock_quantity'       => $spec['stock_quantity'],
                    'empty_quantity'       => $spec['empty_quantity'],
                    'reorder_level'        => $spec['reorder_level'],
                    'standard_capacity_kg' => $spec['standard_capacity_kg'],
                    'created_by'           => $owner->id,
                    'updated_by'           => $owner->id,
                ]);
                $product->created_at = $initialSeededAt;
                $product->updated_at = $initialSeededAt;
                $product->save();

                $movement = InventoryLedgerService::recordMovement($product, [
                    'reference_no'      => 'INIT-' . str_pad((string) ($idx + 1), 4, '0', STR_PAD_LEFT),
                    'movement_type'     => InventoryMovement::TYPE_INITIAL_STOCK,
                    'filled_qty_before' => 0,
                    'filled_qty_change' => (int) $spec['stock_quantity'],
                    'filled_qty_after'  => (int) $spec['stock_quantity'],
                    'empty_qty_before'  => 0,
                    'empty_qty_change'  => (int) $spec['empty_quantity'],
                    'empty_qty_after'   => (int) $spec['empty_quantity'],
                    'unit_cost'         => $spec['cost_price'],
                    'unit_price'        => $spec['price'],
                    'source_type'       => 'Product',
                    'source_id'         => $product->id,
                    'user_id'           => $owner->id,
                    'approved_by'       => $owner->id,
                    'reason'            => 'Initial physical inventory registration (' . ((int) $spec['stock_quantity'] + (int) $spec['empty_quantity']) . ' total units)',
                    'status'            => 'completed',
                ]);
                DB::table('inventory_movements')->where('id', $movement->id)->update([
                    'created_at' => $initialSeededAt,
                    'updated_at' => $initialSeededAt,
                ]);

                AuditTrailService::record([
                    'module'                => 'inventory',
                    'action'                => 'product.created',
                    'entity_type'           => 'Product',
                    'entity_id'             => (string) $product->id,
                    'transaction_reference' => $spec['sku'],
                    'after_values'          => [
                        'name'           => $product->name,
                        'sku'            => $product->sku,
                        'price'          => (float) $product->price,
                        'cost_price'     => (float) $product->cost_price,
                        'stock_quantity' => (int) $product->stock_quantity,
                        'empty_quantity' => (int) $product->empty_quantity,
                    ],
                    'reason'                => 'Initial catalog & physical stock registration',
                ], $owner);

                $products[$spec['key']] = $product;
            }

            // ====================================================================
            // 2. 10 REALISTIC CUSTOMERS (TESTING ALL DISCOUNT CONFIGURATIONS)
            // ====================================================================
            $customerSpecs = [
                // 1. Juan Dela Cruz — Household, default_discount_type: 'regular', default_discount_percentage: 30.00 (Fixed ₱30)
                [
                    'key'                         => 'juan',
                    'first_name'                  => 'Juan',
                    'middle_name'                 => 'Santos',
                    'last_name'                   => 'Dela Cruz',
                    'business_name'               => null,
                    'customer_type'               => 'Household',
                    'phone'                       => '0917-412-8831',
                    'address'                     => 'Blk 4 Lot 12, Deca Homes Cabantian, Davao City',
                    'default_discount_type'       => 'regular',
                    'default_discount_percentage' => 30.00,
                    'default_discount_ref_name'   => null,
                    'default_discount_ref_id'     => null,
                ],
                // 2. Roberto 'Mang Bert' Gomez — Household, default_discount_type: 'senior', default_discount_percentage: 20.00 (Senior 20%)
                [
                    'key'                         => 'roberto',
                    'first_name'                  => 'Roberto',
                    'middle_name'                 => "'Mang Bert'",
                    'last_name'                   => 'Gomez',
                    'business_name'               => null,
                    'customer_type'               => 'Household',
                    'phone'                       => '0918-553-2094',
                    'address'                     => '42 San Pedro Ext., Brgy. 3-A, Davao City',
                    'default_discount_type'       => 'senior',
                    'default_discount_percentage' => 20.00,
                    'default_discount_ref_name'   => "Roberto 'Mang Bert' Gomez",
                    'default_discount_ref_id'     => 'OSCA-DVO-2021-4418',
                ],
                // 3. Elena Bautista — Household, default_discount_type: 'pwd', default_discount_percentage: 20.00 (PWD 20%)
                [
                    'key'                         => 'elena',
                    'first_name'                  => 'Elena',
                    'middle_name'                 => 'Reyes',
                    'last_name'                   => 'Bautista',
                    'business_name'               => null,
                    'customer_type'               => 'Household',
                    'phone'                       => '0922-884-1940',
                    'address'                     => '18 Tulip Drive, Matina Crossing, Davao City',
                    'default_discount_type'       => 'pwd',
                    'default_discount_percentage' => 20.00,
                    'default_discount_ref_name'   => 'Elena Bautista',
                    'default_discount_ref_id'     => 'PWD-XI-2023-0892',
                ],
                // 4. Davao Southern Grills & BBQ — Commercial, default_discount_type: 'regular', default_discount_percentage: 50.00 (Fixed ₱50)
                [
                    'key'                         => 'southern_grills',
                    'first_name'                  => 'Marco',
                    'middle_name'                 => null,
                    'last_name'                   => 'Villareal',
                    'business_name'               => 'Davao Southern Grills & BBQ',
                    'tin_number'                  => '312-849-110-000',
                    'customer_type'               => 'Commercial',
                    'phone'                       => '0917-701-5520',
                    'address'                     => 'Roxas Avenue Night Market Strip, Poblacion, Davao City',
                    'default_discount_type'       => 'regular',
                    'default_discount_percentage' => 50.00,
                    'default_discount_ref_name'   => null,
                    'default_discount_ref_id'     => null,
                ],
                // 5. Matina Metal Fabrication Works — Industrial, default_discount_type: 'industrial', default_discount_percentage: 150.00 (Fixed ₱150)
                [
                    'key'                         => 'matina_metal',
                    'first_name'                  => 'Ramon',
                    'middle_name'                 => null,
                    'last_name'                   => 'Tolentino',
                    'business_name'               => 'Matina Metal Fabrication Works',
                    'tin_number'                  => '409-221-775-000',
                    'customer_type'               => 'Industrial',
                    'phone'                       => '0919-660-4812',
                    'address'                     => 'Km 7 McArthur Highway, Matina Pangi, Davao City',
                    'default_discount_type'       => 'industrial',
                    'default_discount_percentage' => 150.00,
                    'default_discount_ref_name'   => null,
                    'default_discount_ref_id'     => null,
                ],
                // 6. Liberty Carinderia & Catering — Commercial, default_discount_type: 'tertiary', default_discount_percentage: 40.00 (Fixed ₱40)
                [
                    'key'                         => 'liberty_catering',
                    'first_name'                  => 'Lorna',
                    'middle_name'                 => null,
                    'last_name'                   => 'Magsaysay',
                    'business_name'               => 'Liberty Carinderia & Catering',
                    'tin_number'                  => '288-514-903-000',
                    'customer_type'               => 'Commercial',
                    'phone'                       => '0917-339-6104',
                    'address'                     => 'Quirino Avenue cor. Bonifacio St., Davao City',
                    'default_discount_type'       => 'tertiary',
                    'default_discount_percentage' => 40.00,
                    'default_discount_ref_name'   => null,
                    'default_discount_ref_id'     => null,
                ],
                // 7. Buhangin Golden Bakery & Eatery — Tertiary, default_discount_type: 'tertiary', default_discount_percentage: 35.00 (Fixed ₱35)
                [
                    'key'                         => 'buhangin_bakery',
                    'first_name'                  => 'Teresita',
                    'middle_name'                 => null,
                    'last_name'                   => 'Lim',
                    'business_name'               => 'Buhangin Golden Bakery & Eatery',
                    'tin_number'                  => '194-602-338-000',
                    'customer_type'               => 'Tertiary',
                    'phone'                       => '0920-914-7723',
                    'address'                     => 'Km 5 Buhangin Road, Brgy. Buhangin Proper, Davao City',
                    'default_discount_type'       => 'tertiary',
                    'default_discount_percentage' => 35.00,
                    'default_discount_ref_name'   => null,
                    'default_discount_ref_id'     => null,
                ],
                // 8. Coca-Cola Beverages Davao Plant — Coke (Residual), default_discount_type: 'industrial', default_discount_percentage: 200.00 (Fixed ₱200)
                [
                    'key'                         => 'coke_plant',
                    'first_name'                  => 'Arturo',
                    'middle_name'                 => null,
                    'last_name'                   => 'Soriano',
                    'business_name'               => 'Coca-Cola Beverages Davao Plant',
                    'tin_number'                  => '000-418-992-001',
                    'customer_type'               => 'Coke (Residual)',
                    'phone'                       => '0917-880-1129',
                    'address'                     => 'McArthur Highway, Ulas, Talomo District, Davao City',
                    'default_discount_type'       => 'industrial',
                    'default_discount_percentage' => 200.00,
                    'default_discount_ref_name'   => null,
                    'default_discount_ref_id'     => null,
                ],
                // 9. Toril Highway Gas & Auto Care — Service Station, default_discount_type: 'promo', default_discount_percentage: 75.00 (Fixed ₱75)
                [
                    'key'                         => 'toril_station',
                    'first_name'                  => 'Dennis',
                    'middle_name'                 => null,
                    'last_name'                   => 'Tan',
                    'business_name'               => 'Toril Highway Gas & Auto Care',
                    'tin_number'                  => '512-773-401-000',
                    'customer_type'               => 'Service Station',
                    'phone'                       => '0918-245-9901',
                    'address'                     => 'Saavedra St., Toril Poblacion, Davao City',
                    'default_discount_type'       => 'promo',
                    'default_discount_percentage' => 75.00,
                    'default_discount_ref_name'   => null,
                    'default_discount_ref_id'     => null,
                ],
                // 10. Maria Clara Mendoza — Household, no pre-configured discount (tests unlocked manual discount UI in POS)
                [
                    'key'                         => 'maria_clara',
                    'first_name'                  => 'Maria Clara',
                    'middle_name'                 => 'Lopez',
                    'last_name'                   => 'Mendoza',
                    'business_name'               => null,
                    'customer_type'               => 'Household',
                    'phone'                       => '0927-609-3318',
                    'address'                     => 'Phase 2 Ecoland Subd., Brgy. 76-A, Davao City',
                    'default_discount_type'       => null,
                    'default_discount_percentage' => 0.00,
                    'default_discount_ref_name'   => null,
                    'default_discount_ref_id'     => null,
                ],
            ];

            /** @var array<string, Customer> $customers */
            $customers = [];
            foreach ($customerSpecs as $spec) {
                $key = $spec['key'];
                unset($spec['key']);

                $customer = Customer::create($spec);
                $customers[$key] = $customer;

                AuditTrailService::record([
                    'module'                => 'customers',
                    'action'                => 'customer.created',
                    'entity_type'           => 'Customer',
                    'entity_id'             => (string) $customer->id,
                    'transaction_reference' => $customer->business_name ?: $customer->name,
                    'after_values'          => [
                        'name'                        => $customer->name,
                        'business_name'               => $customer->business_name,
                        'customer_type'               => $customer->customer_type,
                        'default_discount_type'       => $customer->default_discount_type,
                        'default_discount_percentage' => (float) $customer->default_discount_percentage,
                    ],
                    'reason'                => 'Customer profile registration',
                ], $manager);
            }

            // Establish Credit Accounts for B2B customers
            $creditSpecs = [
                'southern_grills'  => ['agreed_monthly_payment' => 15000.00, 'is_active' => true],
                'matina_metal'     => ['agreed_monthly_payment' => 35000.00, 'is_active' => true],
                'liberty_catering' => ['agreed_monthly_payment' => 12000.00, 'is_active' => true],
                'buhangin_bakery'  => ['agreed_monthly_payment' => 10000.00, 'is_active' => true],
                'coke_plant'       => ['agreed_monthly_payment' => 50000.00, 'is_active' => true],
                'toril_station'    => ['agreed_monthly_payment' => 20000.00, 'is_active' => false], // 1 suspended account for UI testing
            ];

            /** @var array<string, CreditAccount> $creditAccounts */
            $creditAccounts = [];
            foreach ($creditSpecs as $cKey => $cSpec) {
                $creditAccounts[$cKey] = CreditAccount::create([
                    'customer_id'            => $customers[$cKey]->id,
                    'agreed_monthly_payment' => $cSpec['agreed_monthly_payment'],
                    'is_active'              => $cSpec['is_active'],
                ]);
            }

            // ====================================================================
            // HELPER CLOSURES FOR STOCK-ACCURATE TRANSACTIONS
            // ====================================================================
            $recordStockIn = function (
                Product $product,
                int $filledReceived,
                int $emptyReceived,
                int $emptyReturnedToSupplier,
                string $supplierName,
                string $referenceNo,
                string $remarks,
                Carbon $when,
                User $actor
            ) {
                $product->refresh();
                $filledBefore = (int) $product->stock_quantity;
                $emptyBefore  = (int) $product->empty_quantity;

                $filledChange = $filledReceived;
                $emptyChange  = $emptyReceived - $emptyReturnedToSupplier;

                $filledAfter  = $filledBefore + $filledChange;
                $emptyAfter   = max(0, $emptyBefore + $emptyChange);
                $actualEmptyChange = $emptyAfter - $emptyBefore;

                $product->stock_quantity = $filledAfter;
                $product->empty_quantity = $emptyAfter;
                $product->save();

                $stockIn = new StockIn([
                    'product_id'            => $product->id,
                    'user_id'               => $actor->id,
                    'approved_by'           => $actor->id,
                    'reference_no'          => $referenceNo,
                    'supplier_name'         => $supplierName,
                    'quantity_received'     => $filledReceived,
                    'empty_received_qty'    => $emptyReceived,
                    'empty_shells_received' => $emptyReceived,
                    'empty_returned_qty'    => $emptyReturnedToSupplier,
                    'unit_cost'             => $product->cost_price,
                    'filled_qty_before'     => $filledBefore,
                    'filled_qty_after'      => $filledAfter,
                    'empty_qty_before'      => $emptyBefore,
                    'empty_qty_after'       => $emptyAfter,
                    'remarks'               => $remarks,
                    'is_voided'             => false,
                ]);
                $stockIn->created_at = $when;
                $stockIn->updated_at = $when;
                $stockIn->save();

                $movement = InventoryLedgerService::recordMovement($product, [
                    'reference_no'      => $referenceNo,
                    'movement_type'     => InventoryMovement::TYPE_STOCK_IN,
                    'filled_qty_before' => $filledBefore,
                    'filled_qty_change' => $filledChange,
                    'filled_qty_after'  => $filledAfter,
                    'empty_qty_before'  => $emptyBefore,
                    'empty_qty_change'  => $actualEmptyChange,
                    'empty_qty_after'   => $emptyAfter,
                    'unit_cost'         => $product->cost_price,
                    'unit_price'        => $product->price,
                    'source_type'       => 'StockIn',
                    'source_id'         => $stockIn->id,
                    'user_id'           => $actor->id,
                    'approved_by'       => $actor->id,
                    'reason'            => $remarks,
                    'status'            => 'completed',
                ]);
                DB::table('inventory_movements')->where('id', $movement->id)->update([
                    'created_at' => $when,
                    'updated_at' => $when,
                ]);

                return $stockIn;
            };

            $recordStockOut = function (
                Product $product,
                int $filledRemoved,
                int $emptyRemoved,
                string $category,
                string $referenceNo,
                string $reason,
                Carbon $when,
                User $actor,
                ?string $notes = null
            ) {
                $product->refresh();
                $filledBefore = (int) $product->stock_quantity;
                $emptyBefore  = (int) $product->empty_quantity;

                $filledAfter  = max(0, $filledBefore - $filledRemoved);
                $emptyAfter   = max(0, $emptyBefore - $emptyRemoved);

                $product->stock_quantity = $filledAfter;
                $product->empty_quantity = $emptyAfter;
                $product->save();

                $stockOut = new StockOut([
                    'product_id'             => $product->id,
                    'user_id'                => $actor->id,
                    'approved_by'            => $actor->id,
                    'reference_no'           => $referenceNo,
                    'movement_category'      => $category,
                    'quantity_removed'       => $filledRemoved,
                    'empty_quantity_removed' => $emptyRemoved,
                    'filled_qty_before'      => $filledBefore,
                    'filled_qty_after'       => $filledAfter,
                    'empty_qty_before'       => $emptyBefore,
                    'empty_qty_after'        => $emptyAfter,
                    'reason'                 => $reason,
                    'is_voided'              => false,
                ]);
                $stockOut->created_at = $when;
                $stockOut->updated_at = $when;
                $stockOut->save();

                $movement = InventoryLedgerService::recordMovement($product, [
                    'reference_no'      => $referenceNo,
                    'movement_type'     => $category,
                    'filled_qty_before' => $filledBefore,
                    'filled_qty_change' => -($filledBefore - $filledAfter),
                    'filled_qty_after'  => $filledAfter,
                    'empty_qty_before'  => $emptyBefore,
                    'empty_qty_change'  => -($emptyBefore - $emptyAfter),
                    'empty_qty_after'   => $emptyAfter,
                    'unit_cost'         => $product->cost_price,
                    'unit_price'        => $product->price,
                    'source_type'       => 'StockOut',
                    'source_id'         => $stockOut->id,
                    'user_id'           => $actor->id,
                    'approved_by'       => $actor->id,
                    'reason'            => $notes ?: $reason,
                    'status'            => 'completed',
                ]);
                DB::table('inventory_movements')->where('id', $movement->id)->update([
                    'created_at' => $when,
                    'updated_at' => $when,
                ]);

                return $stockOut;
            };

            // ====================================================================
            // 3. STOCK IN, STOCK OUT & PHASE 2 ADMIN OWNER VOID LOGS
            // ====================================================================
            // Valid Stock Outs (Damaged & Supplier Return)
            $recordStockOut(
                $products['pryce_11_pol'],
                1,
                0,
                InventoryMovement::TYPE_DAMAGED_WRITEOFF,
                'SO-2026-0001',
                'Damaged / Leak',
                $now->copy()->subDays(18)->setTime(10, 15),
                $manager,
                'Damaged POL valve thread detected during safety check'
            );

            $recordStockOut(
                $products['solane_27_cmp'],
                0,
                2,
                InventoryMovement::TYPE_SUPPLIER_RETURN,
                'SO-2026-0002',
                'Supplier Return',
                $now->copy()->subDays(12)->setTime(14, 30),
                $manager,
                'Returned 2 dented 2.7kg empty shells to Isla LPG depot for hydro-testing'
            );

            // Voided Stock Out (Phase 2 Admin Owner Void showcase)
            $voidedSoTime = $now->copy()->subDays(9)->setTime(11, 0);
            $soToVoid = $recordStockOut(
                $products['gasul_11_snp'],
                2,
                0,
                InventoryMovement::TYPE_DAMAGED_WRITEOFF,
                'SO-2026-0003',
                'Damaged / Leak',
                $voidedSoTime,
                $manager,
                'Suspected snap-on collar leak on 2 cylinders'
            );

            // Admin Owner voids SO-2026-0003 1 hour later
            $products['gasul_11_snp']->refresh();
            $gFilledBefore = (int) $products['gasul_11_snp']->stock_quantity;
            $gEmptyBefore  = (int) $products['gasul_11_snp']->empty_quantity;
            $products['gasul_11_snp']->increment('stock_quantity', 2);
            $soToVoid->update([
                'is_voided'   => true,
                'voided_at'   => $voidedSoTime->copy()->addHour(),
                'voided_by'   => $owner->id,
                'void_reason' => 'Passed soap-bubble pressure re-test; O-ring replaced and restored to active filled stock.',
            ]);
            $soVoidMovement = InventoryLedgerService::recordMovement($products['gasul_11_snp'], [
                'reference_no'      => 'VOID-SO-2026-0003',
                'movement_type'     => InventoryMovement::TYPE_MANUAL_ADJUSTMENT,
                'filled_qty_before' => $gFilledBefore,
                'filled_qty_change' => 2,
                'filled_qty_after'  => $gFilledBefore + 2,
                'empty_qty_before'  => $gEmptyBefore,
                'empty_qty_change'  => 0,
                'empty_qty_after'   => $gEmptyBefore,
                'source_type'       => 'StockOut',
                'source_id'         => $soToVoid->id,
                'user_id'           => $owner->id,
                'approved_by'       => $owner->id,
                'reason'            => 'Voided Stock Out SO-2026-0003: Passed soap-bubble pressure re-test',
                'status'            => 'completed',
            ]);
            DB::table('inventory_movements')->where('id', $soVoidMovement->id)->update([
                'created_at' => $voidedSoTime->copy()->addHour(),
                'updated_at' => $voidedSoTime->copy()->addHour(),
            ]);

            // Voided Stock In (Phase 2 Admin Owner Void showcase)
            $voidedSiTime = $now->copy()->subDays(7)->setTime(9, 20);
            $siToVoid = $recordStockIn(
                $products['phoenix_11'],
                10,
                0,
                10,
                'Phoenix LPG Davao Depot',
                'SI-2026-0001',
                'Morning delivery batch DR #4419 (encoded twice by mistake)',
                $voidedSiTime,
                $manager
            );

            // Admin Owner voids SI-2026-0001 30 mins later
            $products['phoenix_11']->refresh();
            $pFilledBefore = (int) $products['phoenix_11']->stock_quantity;
            $pEmptyBefore  = (int) $products['phoenix_11']->empty_quantity;
            $products['phoenix_11']->decrement('stock_quantity', 10);
            $products['phoenix_11']->increment('empty_quantity', 10);
            $siToVoid->update([
                'is_voided'   => true,
                'voided_at'   => $voidedSiTime->copy()->addMinutes(30),
                'voided_by'   => $owner->id,
                'void_reason' => 'Duplicate delivery entry — already logged under manifest DR #4418.',
            ]);
            $siVoidMovement = InventoryLedgerService::recordMovement($products['phoenix_11'], [
                'reference_no'      => 'VOID-SI-2026-0001',
                'movement_type'     => InventoryMovement::TYPE_MANUAL_ADJUSTMENT,
                'filled_qty_before' => $pFilledBefore,
                'filled_qty_change' => -10,
                'filled_qty_after'  => $pFilledBefore - 10,
                'empty_qty_before'  => $pEmptyBefore,
                'empty_qty_change'  => 10,
                'empty_qty_after'   => $pEmptyBefore + 10,
                'source_type'       => 'StockIn',
                'source_id'         => $siToVoid->id,
                'user_id'           => $owner->id,
                'approved_by'       => $owner->id,
                'reason'            => 'Voided Stock In SI-2026-0001: Duplicate delivery entry',
                'status'            => 'completed',
            ]);
            DB::table('inventory_movements')->where('id', $siVoidMovement->id)->update([
                'created_at' => $voidedSiTime->copy()->addMinutes(30),
                'updated_at' => $voidedSiTime->copy()->addMinutes(30),
            ]);

            // ====================================================================
            // 4. PHASE 4: CUSTOMER LOANED TANKS (HURAM) & RETURNS
            // ====================================================================
            // Huram Loan 1: Davao Southern Grills & BBQ — 3 Filled Solane 11kg QR (Active Borrowed)
            $loan1Date = $now->copy()->subDays(11)->setTime(15, 0);
            $soLoan1 = $recordStockOut(
                $products['solane_11_qr'],
                3,
                0,
                InventoryMovement::TYPE_MANUAL_ADJUSTMENT,
                'SO-HURAM-0001',
                'Loaned (Huram)',
                $loan1Date,
                $manager,
                'Backup cylinders loaned for Kadayawan / weekend grill catering surge'
            );
            TankLoan::create([
                'customer_id'        => $customers['southern_grills']->id,
                'product_id'         => $products['solane_11_qr']->id,
                'stock_out_id'       => $soLoan1->id,
                'tank_condition_out' => 'filled',
                'quantity_borrowed'  => 3,
                'quantity_returned'  => 0,
                'status'             => 'borrowed',
                'notes'              => 'Backup cylinders loaned for Kadayawan / weekend grill catering surge',
                'created_by'         => $manager->id,
                'borrowed_at'        => $loan1Date,
            ]);

            // Huram Loan 2: Matina Metal Fabrication Works — 4 Empty Shells Solane 50kg Ind (Partially Returned: 2 of 4 returned)
            $loan2Date = $now->copy()->subDays(14)->setTime(9, 45);
            $soLoan2 = $recordStockOut(
                $products['solane_50_ind'],
                0,
                4,
                InventoryMovement::TYPE_MANUAL_ADJUSTMENT,
                'SO-HURAM-0002',
                'Loaned (Huram)',
                $loan2Date,
                $owner,
                'Loaned 4 industrial shells for manifold rack fitting & rotation'
            );
            $tankLoan2 = TankLoan::create([
                'customer_id'        => $customers['matina_metal']->id,
                'product_id'         => $products['solane_50_ind']->id,
                'stock_out_id'       => $soLoan2->id,
                'tank_condition_out' => 'empty_shell',
                'quantity_borrowed'  => 4,
                'quantity_returned'  => 2,
                'status'             => 'partially_returned',
                'notes'              => 'Loaned 4 industrial shells for manifold rack fitting & rotation',
                'created_by'         => $owner->id,
                'borrowed_at'        => $loan2Date,
            ]);

            // Partial return of 2 empty shells for Huram Loan 2
            $loan2ReturnDate = $now->copy()->subDays(5)->setTime(16, 10);
            $siLoan2Return = $recordStockIn(
                $products['solane_50_ind'],
                0,
                2,
                0,
                $customers['matina_metal']->business_name,
                'HURAM-RET-0001',
                'Huram Empty Shell Return (Loan #' . $tankLoan2->id . ') — 2 of 4 shells returned',
                $loan2ReturnDate,
                $manager
            );
            TankLoanReturn::create([
                'tank_loan_id'      => $tankLoan2->id,
                'stock_in_id'       => $siLoan2Return->id,
                'quantity_returned' => 2,
                'returned_as'       => 'empty_shell',
                'received_by'       => $manager->id,
                'remarks'           => 'Returned 2 empty 50kg shells in good condition; 2 still on site',
                'returned_at'       => $loan2ReturnDate,
                'is_voided'         => false,
            ]);

            // Huram Loan 3: Liberty Carinderia & Catering — 2 Filled Prycegas 11kg POL (Active Borrowed)
            $loan3Date = $now->copy()->subDays(4)->setTime(8, 30);
            $soLoan3 = $recordStockOut(
                $products['pryce_11_pol'],
                2,
                0,
                InventoryMovement::TYPE_MANUAL_ADJUSTMENT,
                'SO-HURAM-0003',
                'Loaned (Huram)',
                $loan3Date,
                $cashier1,
                'Loaned for corporate buffet catering at Bajada'
            );
            TankLoan::create([
                'customer_id'        => $customers['liberty_catering']->id,
                'product_id'         => $products['pryce_11_pol']->id,
                'stock_out_id'       => $soLoan3->id,
                'tank_condition_out' => 'filled',
                'quantity_borrowed'  => 2,
                'quantity_returned'  => 0,
                'status'             => 'borrowed',
                'notes'              => 'Loaned for corporate buffet catering at Bajada',
                'created_by'         => $cashier1->id,
                'borrowed_at'        => $loan3Date,
            ]);

            // Huram Loan 4: Juan Dela Cruz — 1 Filled Phoenix 11kg (Fully Returned)
            $loan4Date = $now->copy()->subDays(16)->setTime(13, 15);
            $soLoan4 = $recordStockOut(
                $products['phoenix_11'],
                1,
                0,
                InventoryMovement::TYPE_MANUAL_ADJUSTMENT,
                'SO-HURAM-0004',
                'Loaned (Huram)',
                $loan4Date,
                $cashier1,
                'Borrowed spare cylinder for family fiesta'
            );
            $tankLoan4 = TankLoan::create([
                'customer_id'        => $customers['juan']->id,
                'product_id'         => $products['phoenix_11']->id,
                'stock_out_id'       => $soLoan4->id,
                'tank_condition_out' => 'filled',
                'quantity_borrowed'  => 1,
                'quantity_returned'  => 1,
                'status'             => 'returned',
                'notes'              => 'Borrowed spare cylinder for family fiesta',
                'created_by'         => $cashier1->id,
                'borrowed_at'        => $loan4Date,
                'returned_at'        => $now->copy()->subDays(6)->setTime(10, 0),
            ]);
            $siLoan4Return = $recordStockIn(
                $products['phoenix_11'],
                0,
                1,
                0,
                $customers['juan']->name,
                'HURAM-RET-0002',
                'Huram Empty Shell Return (Loan #' . $tankLoan4->id . ') — fully settled',
                $now->copy()->subDays(6)->setTime(10, 0),
                $cashier1
            );
            TankLoanReturn::create([
                'tank_loan_id'      => $tankLoan4->id,
                'stock_in_id'       => $siLoan4Return->id,
                'quantity_returned' => 1,
                'returned_as'       => 'empty_shell',
                'received_by'       => $cashier1->id,
                'remarks'           => 'Returned clean empty shell after family fiesta',
                'returned_at'       => $now->copy()->subDays(6)->setTime(10, 0),
                'is_voided'         => false,
            ]);

            // ====================================================================
            // 5. POS ORDERS (MULTI-MONTH + CURRENT MONTH + TODAY + VOIDED ORDER)
            // ====================================================================
            $orderCounter = 1;

            $createDemoOrder = function (
                Customer $customer,
                User $cashier,
                array $lineDefs,
                string $paymentMethod,
                ?string $discountType,
                float $discountAmount,
                Carbon $orderDate,
                bool $voidAfter = false
            ) use (&$orderCounter, $owner) {
                $grossSubtotal = 0.0;
                $preparedItems = [];

                foreach ($lineDefs as $line) {
                    /** @var Product $prod */
                    $prod       = $line['product'];
                    $qty        = (int) $line['qty'];
                    $isSwap     = (bool) ($line['is_swap'] ?? $prod->isCylinder());
                    $residualKg = isset($line['residual_kg']) ? (float) $line['residual_kg'] : null;

                    if ($prod->isCylinder() && $isSwap && $residualKg !== null && $residualKg > 0 && (float) $prod->standard_capacity_kg > 0) {
                        $consumedKg = max(0.0, (float) $prod->standard_capacity_kg - $residualKg);
                        $unitPrice  = round(($consumedKg / (float) $prod->standard_capacity_kg) * (float) $prod->price, 2);
                        $subtotal   = round($unitPrice * $qty, 2);
                    } else {
                        $unitPrice  = ($prod->isCylinder() && !$isSwap && $prod->new_cylinder_price !== null)
                            ? (float) $prod->new_cylinder_price
                            : (float) $prod->price;
                        $consumedKg = $prod->isCylinder() ? (float) $prod->standard_capacity_kg : null;
                        $subtotal   = round($unitPrice * $qty, 2);
                    }

                    $grossSubtotal += $subtotal;
                    $preparedItems[] = [
                        'product'            => $prod,
                        'quantity'           => $qty,
                        'unit_price'         => $unitPrice,
                        'is_swap'            => $isSwap,
                        'residual_kg'        => $residualKg,
                        'actual_consumed_kg' => $consumedKg,
                        'subtotal'           => $subtotal,
                    ];
                }

                $appliedDiscount = min(round($discountAmount, 2), round($grossSubtotal, 2));
                $finalTotal      = max(0.0, round($grossSubtotal - $appliedDiscount, 2));
                $invoiceNo       = 'INV-' . $orderDate->format('Y') . '-' . str_pad((string) $orderCounter++, 5, '0', STR_PAD_LEFT);

                $refName = in_array($discountType, ['senior', 'pwd'], true) ? ($customer->default_discount_ref_name ?: $customer->name) : null;
                $refId   = in_array($discountType, ['senior', 'pwd'], true) ? ($customer->default_discount_ref_id ?: 'OSCA-DVO-9910') : null;

                $order = new Order([
                    'customer_id'             => $customer->id,
                    'user_id'                 => $cashier->id,
                    'invoice_number'          => $invoiceNo,
                    'total_amount'            => $finalTotal,
                    'discount_type'           => $discountType,
                    'discount_amount'         => $appliedDiscount,
                    'discount_reference_name' => $refName,
                    'discount_reference_id'   => $refId,
                    'senior_id'               => $refId,
                    'payment_method'          => $paymentMethod,
                    'status'                  => 'completed',
                ]);
                $order->created_at = $orderDate;
                $order->updated_at = $orderDate;
                $order->save();

                foreach ($preparedItems as $itemData) {
                    /** @var Product $prod */
                    $prod = $itemData['product'];
                    $prod->refresh();

                    $filledBefore = (int) $prod->stock_quantity;
                    $emptyBefore  = (int) $prod->empty_quantity;
                    $qty          = (int) $itemData['quantity'];
                    $isSwap       = $prod->isCylinder() && (bool) $itemData['is_swap'];

                    $filledAfter  = max(0, $filledBefore - $qty);
                    $emptyAfter   = $isSwap ? ($emptyBefore + $qty) : $emptyBefore;

                    $prod->stock_quantity = $filledAfter;
                    $prod->empty_quantity = $emptyAfter;
                    $prod->save();

                    $orderItem = new OrderItem([
                        'order_id'           => $order->id,
                        'product_id'         => $prod->id,
                        'quantity'           => $qty,
                        'unit_price'         => $itemData['unit_price'],
                        'is_swap'            => $isSwap,
                        'residual_kg'        => $itemData['residual_kg'],
                        'actual_consumed_kg' => $itemData['actual_consumed_kg'],
                        'subtotal'           => $itemData['subtotal'],
                    ]);
                    $orderItem->created_at = $orderDate;
                    $orderItem->updated_at = $orderDate;
                    $orderItem->save();

                    $mv = InventoryLedgerService::recordMovement($prod, [
                        'reference_no'      => $invoiceNo,
                        'movement_type'     => InventoryMovement::TYPE_SALE_OUT,
                        'filled_qty_before' => $filledBefore,
                        'filled_qty_change' => -($filledBefore - $filledAfter),
                        'filled_qty_after'  => $filledAfter,
                        'empty_qty_before'  => $emptyBefore,
                        'empty_qty_change'  => $emptyAfter - $emptyBefore,
                        'empty_qty_after'   => $emptyAfter,
                        'unit_cost'         => $prod->cost_price,
                        'unit_price'        => $itemData['unit_price'],
                        'source_type'       => 'Order',
                        'source_id'         => $order->id,
                        'user_id'           => $cashier->id,
                        'reason'            => $isSwap ? 'POS Refill Swap Sale' : 'POS Outright / Accessory Sale',
                        'status'            => 'completed',
                    ]);
                    DB::table('inventory_movements')->where('id', $mv->id)->update([
                        'created_at' => $orderDate,
                        'updated_at' => $orderDate,
                    ]);
                }

                // Credit Ledger entry if payment_method === 'Credit'
                if ($paymentMethod === 'Credit') {
                    $creditAccount = CreditAccount::where('customer_id', $customer->id)->first();
                    if ($creditAccount) {
                        $prevCharges = (float) CreditLedger::where('credit_account_id', $creditAccount->id)
                            ->where('transaction_type', 'Charge')
                            ->sum('amount');
                        $prevPayments = (float) CreditLedger::where('credit_account_id', $creditAccount->id)
                            ->where('transaction_type', 'Payment')
                            ->sum('amount');
                        $balBefore = max(0.0, round($prevCharges - $prevPayments, 2));
                        $balAfter  = round($balBefore + $finalTotal, 2);

                        $ledger = new CreditLedger([
                            'credit_account_id' => $creditAccount->id,
                            'order_id'          => $order->id,
                            'user_id'           => $cashier->id,
                            'transaction_type'  => 'Charge',
                            'amount'            => $finalTotal,
                            'balance_before'    => $balBefore,
                            'balance_after'     => $balAfter,
                            'notes'             => 'Credit charge for ' . $invoiceNo,
                        ]);
                        $ledger->created_at = $orderDate;
                        $ledger->updated_at = $orderDate;
                        $ledger->save();
                    }
                }

                // Optional voiding (for Phase 3 Voided Order audit showcase)
                if ($voidAfter) {
                    $voidTime = $orderDate->copy()->addMinutes(40);
                    $order->update([
                        'status'      => 'voided',
                        'voided_by'   => $owner->id,
                        'voided_at'   => $voidTime,
                        'void_reason' => 'Customer requested switch from Screw-Type to Quick-Release valve before dispatch.',
                    ]);

                    foreach ($preparedItems as $itemData) {
                        /** @var Product $prod */
                        $prod = $itemData['product'];
                        $prod->refresh();
                        $fBefore = (int) $prod->stock_quantity;
                        $eBefore = (int) $prod->empty_quantity;
                        $qty     = (int) $itemData['quantity'];
                        $isSwap  = $prod->isCylinder() && (bool) $itemData['is_swap'];

                        $fAfter = $fBefore + $qty;
                        $eAfter = $isSwap ? max(0, $eBefore - $qty) : $eBefore;

                        $prod->stock_quantity = $fAfter;
                        $prod->empty_quantity = $eAfter;
                        $prod->save();

                        $vMv = InventoryLedgerService::recordMovement($prod, [
                            'reference_no'      => 'VOID-' . $invoiceNo,
                            'movement_type'     => InventoryMovement::TYPE_VOID_RESTOCK,
                            'filled_qty_before' => $fBefore,
                            'filled_qty_change' => $qty,
                            'filled_qty_after'  => $fAfter,
                            'empty_qty_before'  => $eBefore,
                            'empty_qty_change'  => -($eBefore - $eAfter),
                            'empty_qty_after'   => $eAfter,
                            'unit_cost'         => $prod->cost_price,
                            'unit_price'        => $itemData['unit_price'],
                            'source_type'       => 'Order',
                            'source_id'         => $order->id,
                            'user_id'           => $owner->id,
                            'approved_by'       => $owner->id,
                            'reason'            => 'Voided Order ' . $invoiceNo,
                            'status'            => 'completed',
                        ]);
                        DB::table('inventory_movements')->where('id', $vMv->id)->update([
                            'created_at' => $voidTime,
                            'updated_at' => $voidTime,
                        ]);
                    }
                }

                return $order;
            };

            // --- Historical Orders (2 Months Ago & Last Month) for Monthly Audit & Sales Chart ---
            $twoMonthsAgo = $now->copy()->subMonthsNoOverflow(2)->startOfMonth()->addDays(11)->setTime(10, 30);
            $lastMonth1   = $now->copy()->subMonthsNoOverflow(1)->startOfMonth()->addDays(5)->setTime(9, 15);
            $lastMonth2   = $now->copy()->subMonthsNoOverflow(1)->startOfMonth()->addDays(18)->setTime(14, 45);

            // Juan Dela Cruz: 2 months ago, last month, and current month
            $createDemoOrder($customers['juan'], $cashier1, [
                ['product' => $products['solane_11_qr'], 'qty' => 1, 'is_swap' => true],
                ['product' => $products['acc_clamps'],   'qty' => 1, 'is_swap' => false],
            ], 'Cash', 'regular', 30.00, $twoMonthsAgo);

            $createDemoOrder($customers['juan'], $cashier1, [
                ['product' => $products['solane_11_qr'], 'qty' => 1, 'is_swap' => true],
                ['product' => $products['acc_hose_15m'], 'qty' => 1, 'is_swap' => false],
            ], 'Cash', 'regular', 30.00, $lastMonth1);

            // Roberto 'Mang Bert' Gomez (Senior 20%): last month & current month
            $createDemoOrder($customers['roberto'], $cashier1, [
                ['product' => $products['pryce_11_cmp'], 'qty' => 1, 'is_swap' => true], // 950 * 20% = 190
            ], 'Cash', 'senior', 190.00, $lastMonth2);

            // Davao Southern Grills & BBQ (Commercial Credit, Fixed ₱50): last month
            $createDemoOrder($customers['southern_grills'], $cashier2, [
                ['product' => $products['solane_11_qr'], 'qty' => 4, 'is_swap' => true],
                ['product' => $products['pryce_11_pol'], 'qty' => 3, 'is_swap' => true],
            ], 'Credit', 'regular', 50.00, $lastMonth1->copy()->addDays(3));

            // Matina Metal Fabrication Works (Industrial Credit, Fixed ₱150): last month
            $createDemoOrder($customers['matina_metal'], $cashier2, [
                ['product' => $products['solane_50_ind'], 'qty' => 2, 'is_swap' => true],
                ['product' => $products['acc_reg_hpind'], 'qty' => 1, 'is_swap' => false],
            ], 'Credit', 'industrial', 150.00, $lastMonth2->copy()->addDays(2));

            // --- Current Month Orders (Recent Days) ---
            // 1. Juan Dela Cruz (Fixed ₱30) — 5 days ago
            $createDemoOrder($customers['juan'], $cashier1, [
                ['product' => $products['solane_11_qr'], 'qty' => 1, 'is_swap' => true],
                ['product' => $products['acc_reg_lpqr'], 'qty' => 1, 'is_swap' => false],
            ], 'Cash', 'regular', 30.00, $now->copy()->subDays(5)->setTime(11, 20));

            // 2. Juan Dela Cruz — Voided Order 3 days ago (tests Phase 3 Voided exclusion in Customer Monthly Audit)
            $createDemoOrder($customers['juan'], $cashier1, [
                ['product' => $products['solane_11_st'], 'qty' => 1, 'is_swap' => true],
            ], 'Cash', 'regular', 30.00, $now->copy()->subDays(3)->setTime(15, 10), true);

            // 3. Elena Bautista (PWD 20% discount) — 4 days ago
            $createDemoOrder($customers['elena'], $cashier2, [
                ['product' => $products['phoenix_11'], 'qty' => 1, 'is_swap' => true], // 940 * 20% = 188.00
            ], 'Cash', 'pwd', 188.00, $now->copy()->subDays(4)->setTime(10, 5));

            // 4. Liberty Carinderia & Catering (Tertiary Fixed ₱40, Credit) — 3 days ago
            $createDemoOrder($customers['liberty_catering'], $cashier1, [
                ['product' => $products['pryce_11_pol'], 'qty' => 3, 'is_swap' => true],
                ['product' => $products['isla_11_pol'],  'qty' => 2, 'is_swap' => true],
            ], 'Credit', 'tertiary', 40.00, $now->copy()->subDays(3)->setTime(8, 50));

            // 5. Buhangin Golden Bakery & Eatery (Tertiary Fixed ₱35, Credit) — 2 days ago
            $createDemoOrder($customers['buhangin_bakery'], $cashier2, [
                ['product' => $products['gasul_11_snp'], 'qty' => 2, 'is_swap' => true],
                ['product' => $products['solane_11_st'], 'qty' => 2, 'is_swap' => true],
            ], 'Credit', 'tertiary', 35.00, $now->copy()->subDays(2)->setTime(13, 40));

            // 6. Coca-Cola Beverages Davao Plant (Coke Residual kg calculation + Fixed ₱200, Credit) — 2 days ago
            $createDemoOrder($customers['coke_plant'], $manager, [
                ['product' => $products['pryce_50_ind'], 'qty' => 2, 'is_swap' => true, 'residual_kg' => 5.00],
            ], 'Credit', 'industrial', 200.00, $now->copy()->subDays(2)->setTime(16, 15));

            // --- Today's Orders (Populates Dashboard "Today's Revenue" & "Orders Today") ---
            $todayMorning = Carbon::today()->addHours(8)->addMinutes(15);
            if ($todayMorning->greaterThan($now)) {
                $todayMorning = $now->copy()->subMinutes(45);
            }

            // 7. Roberto 'Mang Bert' Gomez (Senior 20% today)
            $createDemoOrder($customers['roberto'], $cashier1, [
                ['product' => $products['solane_11_qr'], 'qty' => 1, 'is_swap' => true], // 980 * 20% = 196.00
            ], 'Cash', 'senior', 196.00, $todayMorning);

            // 8. Davao Southern Grills & BBQ (Commercial Fixed ₱50 today, Cash)
            $createDemoOrder($customers['southern_grills'], $cashier1, [
                ['product' => $products['solane_11_qr'],  'qty' => 3, 'is_swap' => true],
                ['product' => $products['solane_27_cmp'], 'qty' => 2, 'is_swap' => true],
            ], 'Cash', 'regular', 50.00, $todayMorning->copy()->addMinutes(15));

            // 9. Maria Clara Mendoza (Walk-in / Household with manual Promo ₱25 discount today)
            $createDemoOrder($customers['maria_clara'], $cashier2, [
                ['product' => $products['pryce_27_pwr'], 'qty' => 1, 'is_swap' => false], // New camping cylinder outright
                ['product' => $products['acc_hose_15m'], 'qty' => 1, 'is_swap' => false],
                ['product' => $products['acc_clamps'],   'qty' => 2, 'is_swap' => false],
            ], 'Cash', 'promo', 25.00, $todayMorning->copy()->addMinutes(28));

            // 10. Matina Metal Fabrication Works (Industrial Fixed ₱150 today, Credit)
            $createDemoOrder($customers['matina_metal'], $cashier2, [
                ['product' => $products['solane_50_ind'], 'qty' => 1, 'is_swap' => true],
                ['product' => $products['pryce_50_ind'],  'qty' => 1, 'is_swap' => true],
            ], 'Credit', 'industrial', 150.00, $todayMorning->copy()->addMinutes(35));

            // ====================================================================
            // 6. PAYMENTS & STATEMENTS OF ACCOUNT (UTANG & BILLING)
            // ====================================================================
            $recordDemoPayment = function (
                CreditAccount $account,
                float $amount,
                string $method,
                string $refNo,
                string $notes,
                Carbon $paidAt,
                User $actor
            ) {
                $charges = (float) CreditLedger::where('credit_account_id', $account->id)
                    ->where('transaction_type', 'Charge')
                    ->sum('amount');
                $payments = (float) CreditLedger::where('credit_account_id', $account->id)
                    ->where('transaction_type', 'Payment')
                    ->sum('amount');

                $balBefore = max(0.0, round($charges - $payments, 2));
                $balAfter  = max(0.0, round($balBefore - $amount, 2));

                $payment = new Payment([
                    'credit_account_id' => $account->id,
                    'user_id'           => $actor->id,
                    'amount'            => $amount,
                    'payment_method'    => $method,
                    'reference_no'      => $refNo,
                    'notes'             => $notes,
                    'balance_before'    => $balBefore,
                    'balance_after'     => $balAfter,
                ]);
                $payment->created_at = $paidAt;
                $payment->updated_at = $paidAt;
                $payment->save();

                $ledger = new CreditLedger([
                    'credit_account_id' => $account->id,
                    'payment_id'        => $payment->id,
                    'user_id'           => $actor->id,
                    'transaction_type'  => 'Payment',
                    'amount'            => $amount,
                    'balance_before'    => $balBefore,
                    'balance_after'     => $balAfter,
                    'notes'             => $notes,
                ]);
                $ledger->created_at = $paidAt;
                $ledger->updated_at = $paidAt;
                $ledger->save();
            };

            // Partial payment from Davao Southern Grills & BBQ
            $recordDemoPayment(
                $creditAccounts['southern_grills'],
                4000.00,
                'GCash',
                'GC-2026-994812',
                'Partial settlement for last month grill LPG deliveries',
                $now->copy()->subDays(10)->setTime(15, 20),
                $cashier1
            );

            // Partial payment from Matina Metal Fabrication Works
            $recordDemoPayment(
                $creditAccounts['matina_metal'],
                5000.00,
                'Bank Transfer',
                'BDO-TRF-773104',
                'Check / bank transfer installment on 50kg industrial cylinders',
                $now->copy()->subDays(6)->setTime(11, 45),
                $manager
            );

            // Statements of Account
            StatementOfAccount::create([
                'credit_account_id'    => $creditAccounts['southern_grills']->id,
                'billing_period_start' => $now->copy()->subMonthsNoOverflow(1)->startOfMonth()->toDateString(),
                'billing_period_end'   => $now->copy()->subMonthsNoOverflow(1)->endOfMonth()->toDateString(),
                'total_due'            => 2720.00,
                'is_paid'              => false,
            ]);

            StatementOfAccount::create([
                'credit_account_id'    => $creditAccounts['matina_metal']->id,
                'billing_period_start' => $now->copy()->startOfMonth()->toDateString(),
                'billing_period_end'   => $now->toDateString(),
                'total_due'            => 12150.00,
                'is_paid'              => false,
            ]);

            StatementOfAccount::create([
                'credit_account_id'    => $creditAccounts['liberty_catering']->id,
                'billing_period_start' => $now->copy()->subMonthsNoOverflow(2)->startOfMonth()->toDateString(),
                'billing_period_end'   => $now->copy()->subMonthsNoOverflow(2)->endOfMonth()->toDateString(),
                'total_due'            => 3800.00,
                'is_paid'              => true,
            ]);

            // ====================================================================
            // 7. SUPPLIER REPLENISHMENT DELIVERIES (RESTORES EXACT TARGET STOCKS)
            // ====================================================================
            // Replenish any sold/loaned/adjusted quantities via a realistic supplier restock
            // so that each of the 15 products finishes at its exact specification stock & empty count
            // while maintaining 100% InventoryMovement ledger reconciliation (0 discrepancy).
            $replenishTime = $now->copy()->subHours(2);
            $drCounter = 10;

            foreach ($products as $key => $product) {
                $product->refresh();
                $targetFilled = $targetStocks[$key]['stock'];
                $targetEmpty  = $targetStocks[$key]['empty'];

                $currentFilled = (int) $product->stock_quantity;
                $currentEmpty  = (int) $product->empty_quantity;

                $filledNeeded = $targetFilled - $currentFilled;
                $emptyDelta   = $targetEmpty - $currentEmpty;

                if ($filledNeeded === 0 && $emptyDelta === 0) {
                    continue;
                }

                $filledReceived          = max(0, $filledNeeded);
                $emptyReceived           = $emptyDelta > 0 ? $emptyDelta : 0;
                $emptyReturnedToSupplier = $emptyDelta < 0 ? abs($emptyDelta) : 0;

                $recordStockIn(
                    $product,
                    $filledReceived,
                    $emptyReceived,
                    $emptyReturnedToSupplier,
                    $product->isCylinder() ? 'Mindanao LPG Bulk Terminal Depot' : 'Davao Industrial Gas Fittings Supply',
                    'SI-2026-00' . ($drCounter++),
                    'Scheduled weekly depot replenishment & empty cylinder turn-in',
                    $replenishTime,
                    $manager
                );
            }
        });
    }
}
