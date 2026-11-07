# PROJECT.md — LibertyPOS Ground Truth Specification

> **CRITICAL INSTRUCTION FOR AI AGENTS:**
> This document is the single source of truth for the **LibertyPOS** project. Read this file completely before modifying code, database schemas, server configurations, or documentation. Do **not** invent features, assume default business logic, or alter infrastructure routing outside the constraints defined below. If a requirement you need is listed in Section 5 (*Open Questions / Needs Confirmation*), stop and ask the user for clarification before implementing it.

---

## 1. Project Overview

* **System Name:** LibertyPOS (also referred to as the **Liberty POS Web System** or **Sales & Inventory System for Liberty LPG Davao**).
* **Purpose:** A web-based Point of Sale (POS), inventory management, and customer credit (*utang*) tracking system built for **Liberty LPG Center**, an LPG (Liquefied Petroleum Gas) retail and distribution business in Davao City, Philippines, founded in 2020 and owned by Jose Maria S. Tionko.
* **Core Problem Solved:**
  * Previously, store staff recorded daily sales (15–20 transactions/day), physical cylinder stock counts, and customer credit (*utang* — unpaid balances bought on credit) on separate paper logsheets.
  * Accounting staff then had to manually retype paper records into accounting spreadsheets, causing delayed stock visibility for the owner, manual calculation errors, and difficulty retrieving customer credit balances and Statements of Account (SOA).
  * LibertyPOS replaces these paper logsheets with a single real-time web application that links sales recording, inventory updates, customer credit tracking, and Excel report exports.
* **Academic & Development Context:**
  * Originally developed as an IT 12/L mini project by Nunilon Harvey H. Morong and Gerald Wynn R. Belena.
  * Currently maintained and evaluated under **IT 14/L (Professional Track 5: Service Management 1 — Software Engineering)** using the **ISO/IEC 25010** software quality model and Likert-scale User Acceptance Testing (UAT).
* **Technology Stack & Live Infrastructure:**
  * **Application Framework:** Laravel (PHP MVC web framework using Blade templates for server-rendered views, Eloquent ORM for database queries, and Composer for dependency management).
  * **Runtime:** PHP 8.2 running on Ubuntu 24.04 LTS (requires in-place security patch `8.2.35+` to remediate `CVE-2026-109618` and `CVE-2026-109619`).
  * **Database:** Supabase (cloud-hosted PostgreSQL database).
  * **Server & Deployment:** Hosted on a DigitalOcean Droplet (`tionkolpg-server`, Server ID: `1263262`, Region: Singapore, Public IP: `159.89.204.80`, Private IP: `10.104.0.3`) provisioned and managed via **Laravel Forge** from the GitHub repository `Nharvey00/Liberty_POS` (`main` branch).
  * **Domain & DNS:** `tionkolpg.com` registered via Namecheap, using **Namecheap BasicDNS** (`dns1.registrar-servers.com`, `dns2.registrar-servers.com`) with `A` records for `@` and `www` pointing directly to `159.89.204.80`.

---

## 2. Core Actors, Entities, and Roles

### A. System User Roles (Authenticated Actors)
The system enforces role-based access control across three operational tiers (shown in the UI with role levels such as `Admin Owner · Level 3`):

1. **Owner / Admin (`Admin Owner`):**
   * Has full unrestricted access to the system.
   * Views the executive Dashboard (Today's Sales, Low Stock Alerts, Total Customers, Active Credit Accounts, and Weekly/Monthly/Quarterly sales charts).
   * Reviews sales, inventory, customer credit (*utang*), and discount reports.
   * Exports data to Excel (`Sync to Excel`).
   * Manages staff user accounts and role assignments.
2. **Manager (Operations Manager):**
   * Oversees operational workflows, inventory levels, customer credit (*utang*) accounts, and staff transactions according to role permissions.
3. **Cashier / Store Staff:**
   * Executes daily front-line store operations.
   * Records walk-in and delivery sales at the Point of Sale (POS), applies manual discounts, registers and manages customer profiles, and logs manual **Stock In** (restocking) and **Stock Out** (manual deduction) entries.

### B. Business Stakeholders (Indirect / Downstream Actors)
1. **Accounting Staff (3 employees):**
   * Uses the system's exported Excel sales reports to import daily sales figures into accounting records without retyping from paper logsheets; participates in UAT evaluation.
2. **Delivery Staff (3 employees):**
   * Fulfills customer delivery orders recorded by store staff in the POS.

### C. External Customer Types (Recorded in the Customer Registry)
Customers do **not** log into the web system directly; store staff record transactions on their behalf (primarily call-in orders and walk-in purchases):
1. **Household Customers:** Standard retail buyers purchasing LPG refills or cylinders.
2. **Senior Citizens:** Retail customers eligible for senior citizen discounts.
3. **Tertiary Businesses (Small Enterprises):** Commercial clients eligible for business discounts and credit (*utang*) terms.
4. **Industrial / Factory Clients:** B2B bulk clients eligible for industrial discounts and credit (*utang*) terms.

### D. Core Domain Entities
1. **Sales Transaction:** A record of a completed or voided walk-in or delivery order, linked to a customer (or walk-in), payment type (cash or credit/*utang*), discount type and amount (percentage for `senior`/`pwd`; fixed Peso `₱` for `regular`/`tertiary`/`industrial`/`promo`/`custom`, capped at the order subtotal), line items, timestamp, and the specific staff user who created it.
2. **Inventory Item (LPG Stock):** Represents physical store inventory, distinguishing between **Filled Tanks** (available for sale) and **Empty Shells** (returned cylinders), along with stock movement logs (**Stock In** / **Stock Out** with non-destructive Level 3 Admin Owner voiding) and low-stock alert thresholds.
3. **Customer & Credit (*Utang*) Account:** Represents a registered client, their contact/category details, their configurable default discount (`default_discount_type` and `default_discount_percentage`, storing `%` for Senior/PWD and flat `₱` for Regular/Tertiary/Industrial), their monthly purchase & discount audit history, their running unpaid balance, their credit eligibility status (Active vs. Suspended), and their generated **Statements of Account (SOA)**.
4. **Customer Loaned Tank (*Huram*):** Represents an LPG cylinder or empty shell borrowed by a customer at `₱0.00` monetary charge (`tank_loans` and `tank_loan_returns`), linked atomically to **Stock Out** (when borrowed) and **Stock In** (when returned as an `empty_shell` or `filled` tank).

---

## 3. Non-Negotiable Business Rules

### Rule 1: Dual-State Inventory Update on Sale Completion
* **Constraint:** Whenever a sale is completed at the Point of Sale, the system **MUST** automatically deduct the sold **filled tanks** from current inventory AND simultaneously add the returned **empty shells** to inventory in the same workflow.

### Rule 2: Explicit Stock In and Stock Out Logging & Level 3 Voiding Safeguards
* **Constraint:** Any inventory change outside of a POS sale **MUST** be recorded through dedicated **Stock In** (restocking / Huram returns) or **Stock Out** (manual deduction / Huram loans) records tied to the specific item.
* **Constraint (Admin Owner Voiding Only):** Voiding a **Stock In** or **Stock Out** record is strictly restricted to the **Admin Owner (`Level 3`)** role. Voided records are never hard-deleted (`is_voided`, `voided_at`, `voided_by`, `void_reason`).
* **Hard Safeguards:**
  * Voiding a **Stock In** checks that current `stock_quantity >= quantity_received` and `empty_quantity >= empty_shells_received` before reversing; if reversing would drop stock below `0`, the void is blocked. If linked to a Huram return (`tank_loan_returns.stock_in_id`), voiding automatically reverses the return count on the parent `TankLoan`.
  * Voiding a **Stock Out** linked to a `TankLoan` is blocked if `quantity_returned > 0` (requiring the return `StockIn` to be voided first) and automatically marks the `TankLoan` as `voided` when `quantity_returned == 0`.

### Rule 3: Real-Time Low-Stock Flagging
* **Constraint:** The Dashboard and Inventory Hub **MUST** reflect real-time stock counts immediately after any sale or stock adjustment and **MUST** display low-stock alert flags whenever an item hits or falls below its reorder threshold.

### Rule 4: Order Channels, Payment Types, and Configurable Discounts (Fixed Peso vs. Senior Percentage)
* **Constraint:** The Point of Sale **MUST** support both **walk-in** and **delivery** order types, **MUST** support both **cash** and **credit (*utang*)** payment methods, and **MUST** allow staff/owner to configure customer default discounts on the Customers page and edit/override them per transaction at POS checkout:
  * **Percentage (`%`):** `Senior` (`senior`) and `PWD` (`pwd`) calculate `subtotal * (discount_rate / 100)`.
  * **Fixed Peso (`₱`):** `Regular`, `Tertiary`, `Industrial` (and `promo`/`custom`) deduct a flat Peso amount directly from the order subtotal.
  * **Hard Floor:** Any discount is capped at the current order subtotal (`min($discountAmount, $subtotal)`) so a transaction total can **never** become negative (`< ₱0.00`).

### Rule 5: Customer Credit (*Utang*) Tracking and Statements of Account
* **Constraint:** Every credit sale **MUST** increment the specific customer's unpaid *utang* balance, and the system **MUST** be able to generate a Statement of Account (SOA) for that customer.
* **EXPLICIT EXCEPTION (Suspended Credit Accounts):** If a customer's credit account status is **Suspended**, the system **MUST** block that customer from completing any new credit (*utang*) purchase.

### Rule 6: Staff Accountability, Non-Destructive Sale Voiding, and Customer Monthly Audit
* **Constraint:** Every recorded sale **MUST** be permanently associated with the authenticated staff member who processed it.
* **EXPLICIT EXCEPTION (Voided Sales):** When a sale is voided, the system **MUST NOT** hard-delete the transaction row from the database. Voided sales **MUST** remain stored on record for audit and accountability tracking, and **MUST** be excluded from customer monthly purchase volume (`pila nakuha`), gross spend, discount (`pilay na discount`), and net spend totals.

### Rule 7: Mandatory Authentication and Role-Based Access Control (RBAC)
* **Constraint:** All application routes and pages **MUST** require an authenticated login session. Access to pages and sensitive actions **MUST** be restricted according to the user's assigned role (`Owner/Admin` Level 3, `Manager` Level 2, `Cashier` Level 1).

### Rule 8: Accounting Excel Export
* **Constraint:** The system **MUST** provide an Excel export function (`Sync to Excel` / report export) for daily sales and management reports so accounting staff can import data without manual re-entry.

### Rule 9: Production Infrastructure & Domain Safety Constraints
* **Constraint:** The production server (`159.89.204.80`) is managed by Laravel Forge. PHP updates on production **MUST** be performed as in-place minor patches (e.g., patching PHP `8.2.x` via Forge) unless full dependency compatibility testing has been verified for a major version upgrade.
* **Constraint:** The Registrant Contact Email on Namecheap for `tionkolpg.com` **MUST NOT** be changed arbitrarily, as doing so triggers an automatic ICANN verification cycle that places the domain on `clientHold` suspension if unverified.

### Rule 10: Customer Loaned Tanks (*Huram*) Synchronization
* **Constraint:** Customer-borrowed tanks (`Huram`, `₱0.00` charge) can be logged from either **Stock Out** (`reason = 'Loaned Tank (Huram)'`) or **Loaned Tanks** (`+ New Loaned Tank`), atomically deducting `stock_quantity` (if `filled`) or `empty_quantity` (if `empty_shell`) and displaying a `Borrowed: X Tank(s)` badge on the customer's record. Returning a borrowed tank requires selecting `Returned as: Empty Shell` (incrementing `products.empty_quantity` via `stock_ins.empty_shells_received`) or `Returned as: Filled Tank` (incrementing `products.stock_quantity` via `stock_ins.quantity_received`).

---

## 4. Explicit Scope Boundaries

### IN SCOPE (Current Implemented System)
* **Single-Branch Operations:** Managing sales, inventory, and customer credit for a single branch (Catalunan main branch).
* **Navigation & Modules Visible in Production UI:**
  * `Dashboard` (Today's Sales, Low Stock Alerts, Total Customers, Active Credit Accounts, quick action cards for `+ New Sale`, `Stock In`, `Stock Out`, and Weekly/Monthly/Quarterly sales bar charts).
  * `Point of Sale` (Walk-in and delivery checkout with auto-populated, per-transaction editable Fixed ₱ or Senior % discount support).
  * `Sales History` (Transaction log including staff attribution and voided sales).
  * `Customers` (Customer registry, `Borrowed: X Tank(s)` indicator, Monthly Purchase & Discount Summary Breakdown, and Itemized Purchase Audit Log).
  * `Inventory Hub` (Real-time stock monitoring, filled vs. empty shell tracking, low-stock flags, Stock In, Stock Out, and Level 3 Admin Owner voiding).
  * `Credit Accounts (Utang)` (Customer credit ledger and account suspension enforcement).
  * `Statements of Account` (SOA generation for credit customers).
  * `Loaned Tanks` (Customer borrowed/`Huram` tank tracking under `CREDIT & RECEIVABLES`, supporting dual entry points and partial/full returns as Empty Shell or Filled Tank).
  * `Reports & Analytics` (Sales, inventory, credit, and discount reports with Excel export).
* **Usability Refinements (Current IT 14/L Focus):** Updating UI labels on the restocking/inventory pages to match actual store terminology used by Liberty LPG staff.

### DELIBERATELY OUT OF SCOPE (Do NOT Build or Assume Unless Explicitly Instructed)
* **Multi-Branch Support (Future Phase Only):** Although Liberty LPG Center operates three physical branches (`Bangkal`, `Catalunan`, and `Victoria Plaza`), multi-branch support—including separate per-branch databases/ledgers, inter-branch cylinder transfer tracking, and a consolidated multi-branch owner view—is **NOT** implemented in the current system and represents a future roadmap item.
* **Customer Self-Service Ordering Portal:** Customers do not log in or place orders online themselves.
* **Automated Payment Gateway Integration:** The system records cash and house-credit (*utang*) transactions; third-party online payment gateways (e.g., GCash/Stripe checkout APIs) have not been established as part of the system.
* **Full General Ledger / Payroll / Double-Entry Accounting System:** Accounting integration is strictly limited to generating reports and exporting Excel files for the accounting team.
* **Custom Domain Email Hosting:** Namecheap's "Private Email - Scale Email" trial on `tionkolpg.com` is unused and unrelated to the application server.

---

## 5. Open Questions / Needs Confirmation

The following items have **not** been explicitly confirmed in our conversations or documentation. Any AI agent working on these areas **must ask the user for confirmation** before writing code or altering requirements:

1. **Exact Permission Matrix for `Owner` vs. `Manager` vs. `Cashier`:**
   * Slide 5 groups users into `Owner` and `Staff`, whereas Slides 6–7 specify three roles (`Owner/Admin`, `Manager`, `Cashier`), and the Dashboard UI shows `Admin Owner (Level 3)`. What are the exact database role identifiers (`Level 1`, `Level 2`, `Level 3`?) and which specific routes/actions (such as voiding a sale, suspending a credit account, or exporting to Excel) are permitted for `Manager` vs. `Cashier`?
2. **Sale Voiding Side Effects:**
   * When a sale is voided (and kept on record per Rule 6), does the system automatically reverse the deducted filled tanks, reverse the added empty shells, and deduct the amount from the customer's *utang* balance, or are those reversals handled manually?
3. **First-Time Cylinder Purchase vs. Refill Swap (Empty Shell Edge Case):**
   * Rule 1 states that completing a sale deducts filled tanks and adds returned empty shells simultaneously. How does (or should) the POS handle a transaction where a customer buys a brand-new cylinder without returning an empty shell, or returns a partial number of empty shells?
4. **Restocking Page Terminology Details:**
   * Slide 7 states that Usability is *"Partially Implemented"* with *"minor label updates applied on the restocking page to match store terms."* Which exact field labels on the restocking page were changed or still need to be renamed?
5. **Credit Suspension Mechanism:**
   * Is a customer's credit (*utang*) account marked as "Suspended" manually via a toggle by the Owner/Manager, or does the system automatically suspend accounts when a credit limit or due date is exceeded?
6. **Delivery Order Lifecycle:**
   * Does selecting "Delivery" at the POS complete the sale and deduct stock immediately, or does a delivery order go through intermediate statuses (e.g., `Pending` → `Out for Delivery` → `Completed`)?
7. **Database Backup & Recovery Configuration:**
   * How are automated backups currently configured between the DigitalOcean server / Laravel Forge and the Supabase PostgreSQL instance?