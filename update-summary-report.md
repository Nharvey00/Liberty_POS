# Liberty POS - System Updates & Release Notes
**Date:** September 30, 2026
**Status:** Completed, Verified & Deployed
**Branch:** `main` (Merged with `origin/Legendslangnakakaalam`)

This document summarizes the comprehensive integration, UI/UX overhaul, navigation upgrades, and critical bug fixes applied to the Liberty POS system.

---

## 🚀 Navigation & "Orphaned View" UI/UX Overhaul

### 1. Unified Navigation Bar & Role-Based Access
Previous versions hid functional pages such as Stock In History, Stock Out History, Reports, and Void Audit Logs. The top navigation bar (`resources/views/layouts/navigation.blade.php`) has been overhauled into logical, categorized dropdown menus with responsive mobile support:
*   **Inventory Control:**
    *   *Managers & Owners:* Products Catalog (`/products`), Stock In History (`/stock-ins`), Stock Out History (`/stock-outs`).
    *   *Cashiers (Role 1):* Direct link to "Inventory Catalog [View]" (`/products`) with read-only permissions.
*   **Sales & Orders:**
    *   All Transactions & Orders (`/orders`).
    *   Void Audit Logs shortcut (`/orders?status=voided`) for immediate review of voided receipts.
*   **Credit & Billing:**
    *   Customers Directory (`/customers`).
    *   Credit (Utang) Accounts (`/credit-accounts`).
    *   Statements of Account (`/statements`).
*   **Reports & Analytics (Manager & Owner Only):**
    *   Overview Hub (`/reports`).
    *   Sales Report (`/reports/sales`).
    *   Inventory Report (`/reports/inventory`).
    *   Utang (Credit) Report (`/reports/utang`).
    *   Monthly Discounts Summary (`/reports/discounts`).
*   **Administration (Owner Only):**
    *   User Accounts & Staff Management (`/users`).

### 2. Cohesive Sub-Navigation Tabs
*   **Inventory Views:** Added unified header tabs across `/products`, `/stock-ins`, and `/stock-outs` allowing instant tab switching and live item counts without returning to main navigation.
*   **Reports Suite:** Added cohesive tab headers across all report sub-views (`/reports`, `/reports/sales`, `/reports/inventory`, `/reports/utang`, `/reports/discounts`) with active state indicators.

### 3. Search & Filter Toolbars with Reset
*   Implemented live search query filters on **Products Catalog**, **Stock In History**, **Stock Out History**, and **Orders / Transactions History**.
*   Added clear/reset buttons (`X` or "Reset") across all search filters to immediately return to full un-filtered lists.

### 4. Direct Receipt Linking in Customer History
*   In `resources/views/customers/show.blade.php`, order history items now feature a direct receipt link (`View Receipt -> /pos/{id}/receipt`) with clear void status badges (`VOIDED`) for immediate cashier and management verification.

### 5. Dead Code Cleanup
*   Safely deleted unrouted, obsolete legacy view `resources/views/welcome.blade.php`.

---

## 🔒 Preserved Core Business Logic During Merge

During the merge with `origin/Legendslangnakakaalam`, all local core protections were maintained:
1.  **Customer Schema Refactoring:** Preserved split `first_name` and `last_name` schema, along with backwards-compatible Eloquent accessors and mutators (`name`).
2.  **POS Pricing Math:** Protected the `is_swap` ternary logic:
    ```php
    $unitPrice = !is_null($product->new_cylinder_price) ? $product->new_cylinder_price : $product->price;
    ```
    ensuring non-swap new cylinder purchases bill the proper cylinder package rate, and cylinder swap orders correctly increment empty tank inventory.
3.  **Suspended Account Utang Collection:** Maintained the rule that suspended credit accounts cannot incur new debt at checkout, but can pay down existing utang via cash payments (`/credit-accounts/{account}/payments`).

---

## 🛠️ Critical Bug Fixes & Engine Stability

### 1. PostgreSQL Boolean & PDO Type Mismatch Fix
*   **Root Cause:** With PDO emulation enabled (`PDO::ATTR_EMULATE_PREPARES => true`) for Supabase transaction pooling, PDO sends boolean values as integers (`1` / `0`), causing PostgreSQL error `SQLSTATE[42804]: Datatype mismatch: column is of type boolean but expression is of type integer`.
*   **Resolution:** Implemented explicit Postgres-compatible boolean mutators and query formatting (`'true'` / `'false'`) on `CreditAccount` (`is_active`), `StatementOfAccount` (`is_paid`), and `OrderItem` (`is_swap`).

### 2. Route Collision Resolution (`/products/create` vs `/products/{product}`)
*   **Root Cause:** Route `Route::get('/products/{product}')` was defined before `Route::resource('products')` without an ID constraint, causing Laravel to capture `/products/create` as a product model lookup for id `'create'`, resulting in a 404 error instead of hitting route middleware.
*   **Resolution:** Added `->whereNumber('product')` constraint on `products.show`, properly routing `/products/create` to management route-level middleware (`role:2,3`) which cleanly yields 403 Forbidden for Cashiers and 200 OK for Managers/Owners.

### 3. Report Controller Case-Insensitivity
*   Normalized transaction type (`'Charge'`, `'Payment'`) and payment method comparisons using `strcasecmp` and `LOWER()` to ensure seamless query matching across Supabase PostgreSQL records.

---

## ✅ Automated Verification & Test Results
Full automated test suite executed via `php artisan test`:
*   **Total Tests:** 46 passed (0 failed).
*   **Total Assertions:** 191 assertions passed.
*   **Key Test Suites Verified:**
    *   `ProductionClearanceAuditTest`: Full RBAC hierarchy, walk-in cash, cylinder pricing, corporate residual, stock limits, discount bounds, and SOA zero-balance transitions.
    *   `RbacAndNameRefactorTest`: Name compatibility and role restrictions.
    *   `Step3IntegrationCheckTest`: Cashier view-only inventory access, Order Void stock/credit ledger reversal, and Senior ID dynamic checkout enforcement.
