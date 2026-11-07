# DECISIONS.md — LibertyPOS Permanent Decision Log

> **CRITICAL INSTRUCTION FOR AI AGENTS:**
> Every entry in this log represents a settled, finalized decision. Do **not** re-propose discarded alternatives, revert these configurations, or redesign workflows that contradict this log unless the user explicitly instructs you to override a specific decision entry by number.

---

## Decision 1: Retain and Evaluate the Existing IT 12/L LibertyPOS System for IT 14/L

* **Decision:** Use the existing **Sales & Inventory System for Liberty LPG Davao (LibertyPOS)** developed in IT 12/L as the baseline system for the IT 14/L retrospective, requirements analysis, testing evaluation, and system improvement lifecycle.
* **Alternatives Considered:**
  * Starting a brand-new software project from scratch for IT 14/L.
* **Reason for Choice:** Course guidelines explicitly prohibit starting from scratch and require evaluating an existing IT 12/L system's actual development history, current implementation state, and real-world operational gaps to drive software engineering improvements.
* **Status:** Final (do not revisit without explicit new instruction)

---

## Decision 2: Core Application & Hosting Stack (Laravel + Supabase PostgreSQL + DigitalOcean via Laravel Forge)

* **Decision:** Run LibertyPOS as a monolithic server-rendered **Laravel (PHP 8.2 + Blade + Eloquent ORM)** web application backed by a cloud-hosted **Supabase PostgreSQL** database and deployed on a dedicated **DigitalOcean Ubuntu 24.04 Droplet (`159.89.204.80`)** managed through **Laravel Forge (`tionkolpg-server`, ID: `1263262`)** from GitHub (`Nharvey00/Liberty_POS:main`).
* **Alternatives Considered:**
  * Continuing with disconnected paper logsheets and manual spreadsheet encoding.
  * Hosting the application on free-tier sleeping containers (e.g., Render free tier) or local-only store machines without centralized cloud database persistence.
* **Reason for Choice:** Provides reliable, real-time web access for the Owner and Store Staff with automated GitHub deployment via Laravel Forge, dedicated compute resources on DigitalOcean, and managed relational data storage on Supabase PostgreSQL.
* **Status:** Final (do not revisit without explicit new instruction)

---

## Decision 3: Single-Branch Initial Production Scope (Catalunan Main Branch First)

* **Decision:** Scope the current production release strictly to **single-branch operations** for the Catalunan main branch, deferring Multi-Branch Support (across `Bangkal`, `Catalunan`, and `Victoria Plaza`) to the future roadmap (Phase 2).
* **Alternatives Considered:**
  * Building multi-branch data isolation, inter-branch cylinder transfer tracking, and a consolidated multi-branch owner dashboard during the initial development phase.
* **Reason for Choice:** Focusing on the main branch first enabled the team to fully implement and stabilize all 7 core functional requirements (POS, dual-state inventory, low-stock flags, customer *utang* credit & Statements of Account, reports, Excel export, and RBAC) before introducing multi-branch complexity.
* **Status:** Final (do not revisit without explicit new instruction)

---

## Decision 4: Dual-State LPG Inventory Tracking (Filled Tanks vs. Empty Shells)

* **Decision:** Track LPG inventory across two distinct physical states—**Filled Tanks** and **Empty Shells**—and automatically deduct sold filled tanks while simultaneously adding returned empty shells whenever a sale is completed at the Point of Sale, alongside dedicated **Stock In** and **Stock Out** logs for manual adjustments.
* **Alternatives Considered:**
  * Tracking only a single generic item quantity column (standard retail inventory model) or updating empty cylinder counts manually on paper at the end of the day.
* **Reason for Choice:** LPG retail and distribution requires tracking physical cylinder shells returned by customers during refill swaps alongside filled gas stock so the owner has accurate, real-time visibility of both sellable gas and empty cylinders available for refilling.
* **Status:** Final (do not revisit without explicit new instruction)

---

## Decision 5: Non-Destructive Sale Voiding & Suspended Credit (*Utang*) Blocking

* **Decision:** Enforce strict financial and staff accountability rules:
  1. Every sale is tied to the authenticated staff member who recorded it (`Owner/Admin`, `Manager`, or `Cashier`).
  2. Voided sales are never hard-deleted from the database; they remain on record in `Sales History` for auditability.
  3. Customers with unpaid balances are tracked in `Credit Accounts (Utang)` with generated Statements of Account (SOA), and any account marked as **Suspended** is strictly blocked from making new credit purchases.
* **Alternatives Considered:**
  * Hard-deleting canceled/voided transactions via SQL `DELETE`.
  * Allowing staff to record credit (*utang*) sales without automated account status checks.
* **Reason for Choice:** Prevents unrecorded stock/cash discrepancies by preserving an immutable audit trail of voided transactions and prevents bad debt accumulation on delinquent customer accounts.
* **Status:** Final (do not revisit without explicit new instruction)

---

## Decision 6: Accounting Integration via Excel Export (`Sync to Excel`)

* **Decision:** Bridge daily POS operations with the company's accounting team by providing an Excel export feature (`Sync to Excel` and report exports) rather than building a full internal double-entry accounting/payroll system.
* **Alternatives Considered:**
  * Having accounting staff continue re-typing daily sales from paper logsheets into accounting spreadsheets.
  * Expanding project scope to build a full internal general ledger and accounting software suite.
* **Reason for Choice:** Directly eliminates manual double-encoding errors across 15–20 daily transactions while allowing Liberty LPG Center's 3-person accounting team to import clean daily figures into their existing accounting workflows.
* **Status:** Final (do not revisit without explicit new instruction)

---

## Decision 7: Usability Alignment with Store Terminology & ISO/IEC 25010 Evaluation

* **Decision:** Evaluate the system using the **ISO/IEC 25010** software quality framework and Likert-scale User Acceptance Testing (UAT) with the Owner, Operations Manager, store staff, and accounting staff, and update UI labels on the restocking page (and *Utang* credit views) to match the exact everyday terms used in the store.
* **Alternatives Considered:**
  * Keeping generic software/developer labels on the inventory restocking pages.
  * Relying solely on informal developer testing without stakeholder UAT.
* **Reason for Choice:** Aligning interface labels (such as `Utang` and store-specific restocking terms) with staff vocabulary reduces training friction and encoding mistakes during daily operations.
* **Status:** Final (do not revisit without explicit new instruction)

---

## Decision 8: In-Place PHP 8.2 Security Patching on Production

* **Decision:** Resolve the PHP security vulnerabilities (`CVE-2026-109618` and `CVE-2026-109619`) flagged by Laravel Forge by applying an **in-place update to PHP 8.2** (`8.2.x` $\rightarrow$ `8.2.35+`) via the Laravel Forge dashboard rather than upgrading the live server to PHP 8.3 or PHP 8.4.
* **Alternatives Considered:**
  * Upgrading the live production server to a new minor/major PHP branch (PHP 8.3 or PHP 8.4) without prior staging tests.
  * Leaving PHP 8.2 unpatched on the public internet until after the academic presentation.
* **Reason for Choice:** An in-place PHP 8.2 patch eliminates the critical Remote Code Execution vulnerabilities and cleanly reloads Nginx and PHP-FPM while guaranteeing 100% backward compatibility with the existing Laravel application and Composer dependencies.
* **Status:** Final (do not revisit without explicit new instruction)

---

## Decision 9: DNS Management via Namecheap BasicDNS & Ignoring Bundled Private Email Trial

* **Decision:** Configure `tionkolpg.com` in Namecheap to use **Namecheap BasicDNS** (`dns1.registrar-servers.com`, `dns2.registrar-servers.com`) with explicit `A` records for `@` and `www` pointing to `159.89.204.80` (`TTL: Automatic`), and leave Namecheap's expiring "Private Email - Scale Email" trial ($71.88/year) unrenewed.
* **Alternatives Considered:**
  * Keeping the Namecheap Nameservers dropdown set to `Custom DNS` with `dns1.registrar-servers.com` and `dns2.registrar-servers.com` entered manually.
  * Paying to renew the Namecheap Private Email subscription.
* **Reason for Choice:** Leaving Namecheap's own default nameservers under the `Custom DNS` setting locked the `Advanced DNS` Host Records table after the ICANN suspension was lifted. Switching to `Namecheap BasicDNS` unlocked the Host Records table to restore routing to `159.89.204.80`, and the custom email hosting trial has zero connection to the POS web application.
* **Status:** Final (do not revisit without explicit new instruction)

---

## Decision 10: Non-Destructive DNS Propagation Handling After ICANN Verification

* **Decision:** After verifying the Registrant email with ICANN/Namecheap and confirming live resolution via mobile data, allow local Wi-Fi ISP DNS caches to expire naturally (or use local DNS cache flushing) rather than modifying Laravel Forge SSL certificates or repeatedly re-saving Namecheap DNS records.
* **Alternatives Considered:**
  * Requesting a new Let's Encrypt SSL certificate in Laravel Forge while local DNS was still resolving to Namecheap's verification server.
  * Repeatedly deleting and re-creating `A` records in Namecheap to try to speed up local Wi-Fi resolution.
* **Reason for Choice:** Triggering Let's Encrypt while DNS is still propagating causes validation failures and rate-limiting, and re-saving DNS records resets TTL timers. Waiting for TTL expiration (while verifying on mobile data) preserves the working server configuration safely.
* **Status:** Final (do not revisit without explicit new instruction)

---

## Decision 11: Discount Logic Update (Fixed Peso vs. Senior/PWD Percentage, Editable on Both Customers & POS)

* **Decision:**
  1. `Regular`, `Tertiary`, and `Industrial` (plus `promo`/`custom`) discount types use a **fixed Peso (`₱`)** deduction, whereas `Senior` (`senior`) and `PWD` (`pwd`) remain **percentage (`%`)** discounts (`0–100%`).
  2. Retain `customers.default_discount_percentage` as the database column name for backwards compatibility (storing `%` when `default_discount_type` is `senior`/`pwd` and flat `₱` when `regular`/`tertiary`/`industrial`/`promo`/`custom`).
  3. Make discount type and value editable both when creating/editing a customer (`customers/create.blade.php`, `customers/edit.blade.php`) AND per transaction on the POS checkout screen (`pos/create.blade.php`), auto-populating from the customer's saved default upon selection while capping any discount at the order subtotal (`min($discountAmount, $subtotal)`) so the transaction total never drops below `₱0.00`.
* **Status:** Final (do not revisit without explicit new instruction)

---

## Decision 12: Non-Destructive Voidable Stock In & Stock Out Restricted Strictly to Level 3 Admin Owner

* **Decision:**
  1. Restrict voiding of `Stock In` (`POST /stock-ins/{stockIn}/void`) and `Stock Out` (`POST /stock-outs/{stockOut}/void`) strictly to `Level 3` (`Admin Owner`, `role:3` middleware and `auth()->user()->isOwner()`).
  2. Store non-destructive void audit columns (`is_voided`, `voided_at`, `voided_by`, `void_reason`) on `stock_ins` and `stock_outs`, plus `empty_shells_received` on `stock_ins`.
  3. Enforce `DB::transaction()` with `lockForUpdate()` safeguards: abort voiding a `Stock In` if reversing `quantity_received` or `empty_shells_received` would drop current `stock_quantity` or `empty_quantity` below `0`; reverse linked `TankLoanReturn` counts when voiding a Huram return `Stock In`; block voiding a Huram `Stock Out` if `quantity_returned > 0` and mark the linked `TankLoan` as `voided` when `quantity_returned == 0`.
* **Status:** Final (do not revisit without explicit new instruction)

---

## Decision 13: Customer Monthly Purchase & Discount Audit Log

* **Decision:** Enhance `CustomerController::show` and `resources/views/customers/show.blade.php` with a **Monthly Purchase & Discount Summary Breakdown** (grouping non-voided orders by `Y-m` to display total items/tanks purchased `pila nakuha`, gross purchase amount, total discounts given `pilay na discount`, and net spend) and a filterable **Itemized Purchase Audit Log** showing exact timestamps (`kanus-a nagkuha`), receipt/ref numbers, itemized products and quantities (`unsa nga mga item iya napalit`), discount applied (`₱` or `%`), payment/utang status, and recording staff member.
* **Status:** Final (do not revisit without explicit new instruction)

---

## Decision 14: Customer Loaned Tanks ("Huram" Tanks Module)

* **Decision:**
  1. Track customer-borrowed cylinders and empty shells at `₱0.00` monetary charge using dedicated `tank_loans` and `tank_loan_returns` tables (`role:2,3` access).
  2. Place `Loaned Tanks` as the 3rd item under `CREDIT & RECEIVABLES` in the sidebar navigation (`resources/views/layouts/navigation.blade.php`) and display a `Borrowed: X Tank(s)` badge on `customers/index.blade.php` and `customers/show.blade.php`.
  3. Support two synchronized atomic entry points (`Stock Out` with reason `Loaned Tank (Huram)` and `+ New Loaned Tank` on the `Loaned Tanks` module) and partial/full tank returns requiring explicit selection of `Returned as: Empty Shell` (incrementing `products.empty_quantity` via `stock_ins.empty_shells_received` / `empty_received_qty`) or `Returned as: Filled Tank` (incrementing `products.stock_quantity` via `stock_ins.quantity_received`).
* **Status:** Final (do not revisit without explicit new instruction)

---

## Decision 15: Centralized Audit Trail, Unified Inventory Movement Ledger & Empty Shell Synchronization

* **Decision:**
  1. Maintain a centralized `audit_logs` table (`AuditTrailService`) and a unified `inventory_movements` ledger (`InventoryLedgerService`) capturing before/after stock (`filled_qty_before/after`, `empty_qty_before/after`) and credit balance (`balance_before/after`) snapshots across POS sales, order voids, multi-item `StockIn`/`StockOut` batches, Level 3 Admin Owner `StockIn`/`StockOut` voids, and `Huram` tank loans/returns.
  2. Synchronize `stock_ins.empty_received_qty` and `stock_ins.empty_shells_received` to a single authoritative integer (`max((int)empty_received_qty, (int)empty_shells_received)`) via `StockIn::booted()` and controller logic, never adding the two columns together.
* **Status:** Final (do not revisit without explicit new instruction)