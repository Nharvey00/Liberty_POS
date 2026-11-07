# ARCHITECTURE.md — LibertyPOS Technical Reference & Architectural Contract

> **CRITICAL INSTRUCTION FOR AI AGENTS:**
> This document defines the structural, infrastructural, and modular architecture of **LibertyPOS**. Do **not** rename modules, reorganize navigation hierarchies, introduce decoupled frontend frameworks (such as React/Vue SPAs), change database engines, or assume database table/column names that are not explicitly verified here. Whenever a task touches an item listed in Section 5 (*Undecided / Pending Architecture Decisions*), you **must** inspect the repository files first or ask the user for confirmation before writing code.

---

## 1. Structural Overview

LibertyPOS is a monolithic, server-rendered **Laravel (PHP 8.2)** web application backed by a cloud-hosted **Supabase PostgreSQL** database and deployed on a **DigitalOcean Ubuntu 24.04** server via **Laravel Forge**.

### A. Infrastructure & Deployment Components

| Component | Identifier / Value | Role in System |
| :--- | :--- | :--- |
| **Domain Registrar & DNS** | Namecheap (`tionkolpg.com`) | Routes public traffic using `Namecheap BasicDNS` (`dns1.registrar-servers.com`, `dns2.registrar-servers.com`). |
| **DNS Host Records** | `A` (`@`) → `159.89.204.80`<br>`A` (`www`) → `159.89.204.80` | Maps root and `www` traffic directly to the DigitalOcean server's public IPv4 address (`TTL: Automatic`). |
| **Cloud Compute** | DigitalOcean Droplet (`tionkolpg-server`) | App server hosted in the `Singapore` region (`Public IP: 159.89.204.80`, `Private IP: 10.104.0.3`, `OS: Ubuntu 24.04`). |
| **Server Provisioning** | Laravel Forge (`Server ID: 1263262`) | Manages Nginx, SSL certificates, PHP 8.2 runtime, deployment scripts, and scheduled cron jobs under organization `Tionko LPG`. |
| **Source Control** | GitHub (`Nharvey00/Liberty_POS`) | Primary code repository; Laravel Forge deploys directly from the `main` branch to the `tionkolpg.com` site directory. |
| **Database Engine** | Supabase (PostgreSQL) | Cloud-hosted relational database storing all users, customers, inventory counts, sales, and credit (*utang*) ledgers. |
| **Process / Cron Layer** | Forge Scheduled Jobs | Runs a weekly root cleanup job (`apt-get autoremove -y && apt-get autoclean`). No Supervisor background queue workers are currently provisioned. |

---

### B. Application Layer (Laravel MVC)

1. **Presentation Layer (Laravel Blade):**
   * Server-side rendered HTML templates styled with a persistent left-hand dark navigation sidebar (`#0f172a` / dark navy theme) and a light content workspace.
2. **Application & Routing Layer (Controllers & Middleware):**
   * Handles HTTP requests, enforces session authentication, checks role-based access control (`Owner/Admin`, `Manager`, `Cashier`), and executes business logic.
3. **Domain & Data Access Layer (Eloquent ORM):**
   * Maps PHP model classes to the Supabase PostgreSQL tables and executes transactional queries for POS checkouts, stock adjustments, and credit calculations.
4. **Export Module (Excel Sync):**
   * Generates downloadable Excel spreadsheets (`Sync to Excel`) from sales, inventory, credit, and discount records for external accounting import.

---

### C. Functional UI Modules (Sidebar Architecture)

The application UI is strictly organized into five top-level navigation groups in the left sidebar:

1. **`OVERVIEW`**
   * **`Dashboard`**: Displays 4 KPI summary cards (`Today's Sales`, `Low Stock Alerts`, `Total Customers`, `Active Credit Accounts`), 3 quick-action launch cards (`+ New Sale`, `Stock In`, `Stock Out`), a `Sync to Excel` header action, and a filterable `Sales` bar chart (`Weekly`, `Monthly`, `Quarterly`).
2. **`OPERATIONS`**
   * **`Point of Sale`**: Checkout interface supporting walk-in and delivery orders, cash and credit (*utang*) payment methods, and auto-populated, editable discounts (Percentage `%` for Senior/PWD; Fixed Peso `₱` for Regular, Tertiary, Industrial, Promo, Custom; capped at order subtotal).
   * **`Sales History`**: Audit log of completed and voided sales transactions with staff attribution.
   * **`Customers`**: CRUD registry for household, senior citizen, tertiary business, and industrial clients, including `Borrowed: X Tank(s)` badges, Monthly Purchase & Discount Summary Breakdown, and Itemized Purchase Audit Log.
3. **`INVENTORY`**
   * **`Inventory Hub`** *(collapsible submenu)*: Tracks LPG items across two physical states (**Filled Tanks** and **Empty Shells**), displays low-stock reorder flags, records **Stock In** (restocking and Huram returns) and **Stock Out** (manual deduction and Huram loans) movements, and provides **Level 3 Admin Owner** non-destructive voiding (`stock-ins.void`, `stock-outs.void`).
4. **`CREDIT & RECEIVABLES`**
   * **`Credit Accounts (Utang)`**: Manages running unpaid customer balances and account eligibility status (`Active` vs. `Suspended`).
   * **`Statements of Account`**: Generates itemized billing statements for credit customers.
   * **`Loaned Tanks`**: Tracks customer-borrowed LPG tanks and empty shells (*Huram*, `₱0.00` charge) via `TankLoanController` (`tank_loans` and `tank_loan_returns` tables), supporting dual entry points (`Stock Out` and `+ New Loaned Tank`) and partial/full returns as either `Empty Shell` or `Filled Tank`.
5. **`REPORTS & ANALYTICS`**
   * Management reporting module providing filtered views and Excel exports for sales, inventory, credit, and discount logs.

---

## 2. Relationships & Dependencies

### A. System Dependency Graph

```text
[Client Browser]
       │
       ▼ (HTTPS / Port 443 via Namecheap BasicDNS)
[DigitalOcean Droplet: 159.89.204.80 (Nginx)]
       │
       ▼ (FastCGI)
[PHP 8.2-FPM Runtime (Laravel App: Nharvey00/Liberty_POS:main)]
       ├──► [Blade View Engine] ──► Renders HTML/CSS/JS back to Client Browser
       ├──► [Excel Export Service] ──► Streams .xlsx/.csv file to Client Browser
       └──► [Eloquent ORM (PostgreSQL Driver)]
                   │
                   ▼ (Remote TCP / SSL Connection)
            [Supabase Cloud PostgreSQL Database]
```

* **Hard Dependencies:**
  * The **Laravel Application** depends on **Supabase PostgreSQL** being reachable and active for every authenticated request and dashboard query.
  * **Point of Sale Checkout** depends on **Inventory Hub** (to validate and update Filled Tanks and Empty Shells) and **Credit Accounts (Utang)** (to verify a customer is not `Suspended` before allowing a credit sale).
  * **Statements of Account** depends on **Credit Accounts (Utang)** and **Sales History** to aggregate unpaid credit transactions per customer.
  * **Dashboard** depends on real-time aggregations from **Sales History**, **Inventory Hub**, **Customers**, and **Credit Accounts (Utang)**.

---

### B. Domain Entity Relationships & Cardinality

```text
[User / Staff] (1) ──────< (0..N) [Sales Transaction] (0..N) >────── (0..1) [Customer]
      │                                  │                                      │
      │ (1)                              │ (1)                                  │ (1)
      ▼                                  ▼                                      ▼
   (0..N)                             (1..N)                                 (0..1)
[Stock Movement]                 [Sale Line Item]                     [Credit Account (Utang)]
  (In / Out)                             │                                      │
      │ (0..N)                           │ (0..N)                               │ (1)
      └───────────► (1) [Inventory Item (LPG)] (1) ◄────────────────────────────┘
                         (Filled Count + Empty Shell Count)                     │
                                                                                ▼ (0..N)
                                                                     [Statement of Account]
```

1. **`User / Staff` $\rightarrow$ `Sales Transaction` (`1 : N`, Mandatory Staff Attribution):**
   * One authenticated staff user (`Owner`, `Manager`, or `Cashier`) can create many `Sales Transactions`.
   * Every `Sales Transaction` **must** reference the `User` who recorded it.
2. **`Customer` $\rightarrow$ `Sales Transaction` (`1 : N`, Optional for Walk-In Cash Sales / Mandatory for Credit Sales):**
   * One `Customer` can be associated with many `Sales Transactions`.
   * Every credit (*utang*) sale **must** belong to a registered `Customer`.
3. **`Sales Transaction` $\leftrightarrow$ `Inventory Item` (`M : N` via Sale Items / Inventory Ledger):**
   * A `Sales Transaction` includes one or more `Inventory Items` and triggers a dual-counter update on the target item(s): decrementing **Filled Tanks** and incrementing **Empty Shells**.
4. **`Inventory Item` $\rightarrow$ `Stock Movement (Stock In / Stock Out)` (`1 : N`):**
   * One `Inventory Item` has many historical `Stock In` and `Stock Out` movement records.
5. **`User / Staff` $\rightarrow$ `Stock Movement` (`1 : N`):**
   * One `User` records many `Stock In` and `Stock Out` entries.
6. **`Customer` $\rightarrow$ `Credit Account (Utang)` (`1 : 1` or Embedded State):**
   * A credit-eligible `Customer` has one running *utang* ledger balance and an explicit credit status (`Active` or `Suspended`).
7. **`Credit Account (Utang)` $\rightarrow$ `Statement of Account` (`1 : N`):**
   * One customer credit account can generate multiple `Statements of Account` over time.

---

## 3. Established Conventions

All future code edits, UI modifications, and infrastructure maintenance **must** adhere to these established conventions:

### A. Architectural & Stack Conventions
* **Monolithic Blade Architecture:** All UI pages must be built and maintained using **Laravel Blade** templates rendered by Laravel controllers. Do not introduce separate frontend build pipelines or single-page application routers unless explicitly instructed.
* **ORM Convention:** All database reads and writes must go through **Laravel Eloquent ORM** (and database transactions where multiple tables update together) targeting the **Supabase PostgreSQL** connection.
* **Single-Branch Data Model:** All current queries and views operate on a single branch (Catalunan main branch). Do not add `branch_id` foreign keys or multi-tenant filters unless the user explicitly initiates the Phase 2 Multi-Branch expansion.

### B. UI, Formatting, and Terminology Conventions
* **Currency Formatting:** All monetary values in the UI must be prefixed with the Philippine Peso symbol (`₱`) and formatted to two decimal places with comma thousands separators (e.g., `₱70,610.00`, `₱0.00`).
* **Domain Terminology:**
  * Customer credit accounts must retain the localized label **`Credit Accounts (Utang)`** or **`Utang`** where established in the UI (e.g., dashboard badge `Utang ledger active`).
  * Restocking and inventory interfaces must use store-aligned terminology (`Stock In`, `Stock Out`, `Filled Tanks`, `Empty Shells`, and any store-specific restocking labels established during UAT).
* **Sidebar Navigation Hierarchy:** New views or features must fit into the existing 5-section sidebar (`OVERVIEW`, `OPERATIONS`, `INVENTORY`, `CREDIT & RECEIVABLES`, `REPORTS & ANALYTICS`) rather than creating top-bar navigation menus.
* **User Session Display:** The bottom-left sidebar footer displays the logged-in user's two-letter avatar badge, role title (e.g., `Admin Owner`), role tier (e.g., `Level 3`), and a `Log out` trigger.

### C. Data Integrity Conventions
* **Soft / Non-Destructive Voiding:** Voiding a sale must **never** execute a SQL `DELETE` on the sales record. Voided transactions must be marked via a status/flag and preserved in `Sales History`.
* **Atomic Sale Inventory Updates:** Completing a sale must update both the filled tank stock and empty shell count in a single logical operation.

---

## 4. End-to-End System Flows

### Flow 1: Point of Sale (POS) Checkout (Cash vs. Credit / *Utang*)

```text
[Store Staff / Cashier]
  │
  ├─► 1. Clicks "+ New Sale" (Dashboard) or "Point of Sale" (Sidebar)
  │
  ├─► 2. Selects Order Channel (Walk-in OR Delivery), LPG Item(s), Quantity,
  │      Returned Empty Shells, Customer, Manual Discount (if eligible),
  │      and Payment Method (Cash OR Credit/Utang)
  │
  ▼
[Laravel POS Controller]
  │
  ├─► 3. Validates authenticated session & role permissions
  │
  ├─► 4. IF Payment Method == Credit (Utang):
  │        Checks Customer's Credit Account status in Supabase PostgreSQL
  │        ├── IF Status == "Suspended" ──► ABORT & return error (Credit Blocked)
  │        └── IF Status == "Active"    ──► Proceed
  │
  ├─► 5. Executes Database Updates via Eloquent ORM (Supabase PostgreSQL):
  │        ├── a. Inserts Sales Transaction record (linked to Staff User ID & Customer ID,
  │        │      including manual discount & order type)
  │        ├── b. Decrements Filled Tank stock count for sold LPG item(s)
  │        ├── c. Increments Empty Shell stock count for returned cylinder shell(s)
  │        └── d. IF Credit Sale: Increments Customer's unpaid Utang balance
  │
  ▼
[Dashboard & Inventory Hub State Refresh]
  │
  └─► 6. Recomputes Today's Sales total, checks if Filled Tank count <= Reorder Threshold
         (activates Low Stock Alert flag if threshold reached), and returns updated Blade view.
```

---

### Flow 2: Daily Accounting Export (`Sync to Excel`)

```text
[Owner / Admin (or Authorized Role)]
  │
  ├─► 1. Clicks "Sync to Excel" on Dashboard (or exports from Reports & Analytics)
  │
  ▼
[Laravel Report / Export Controller]
  │
  ├─► 2. Verifies authenticated session and role authorization
  │
  ├─► 3. Queries Supabase PostgreSQL via Eloquent ORM for daily sales, discounts,
  │      inventory movements, and credit records
  │
  ├─► 4. Formats dataset into Excel (.xlsx / .csv) structure
  │
  ▼
[Client Browser Download]
  │
  └─► 5. Accounting staff imports exported Excel file directly into accounting records
         (eliminating manual re-typing from paper logsheets).
```

---

### Flow 3: Production Code Deployment Pipeline

```text
[Developer in VS Code]
  │
  ├─► 1. Commits & pushes code changes to GitHub (Nharvey00/Liberty_POS -> main branch)
  │
  ▼
[Laravel Forge (tionkolpg-server / ID: 1263262)]
  │
  ├─► 2. Pulls latest `main` branch onto DigitalOcean Droplet (159.89.204.80)
  ├─► 3. Runs Composer install, Blade/config cache refresh, and database migrations (if any)
  └─► 4. Reloads PHP 8.2-FPM & Nginx to serve live traffic on https://tionkolpg.com
```

---

## 5. Undecided / Pending Architecture Decisions

The following structural details have **not** yet been inspected from the repository source code or finalized in architectural discussions. Any AI agent **must** inspect the codebase or ask the user before assuming how these are structured:

1. **Exact Database Schema & Eloquent Model Names:**
   * The exact PostgreSQL table names, primary/foreign key column names, and Eloquent model filenames in `app/Models/` (e.g., whether empty shells and filled tanks are stored as two columns on a single `products`/`inventories` table or as separate item records) have not been provided in chat context and must be read from `database/migrations/` before writing queries.
2. **Supabase Connection Pooling Mode:**
   * Whether the production `.env` on Laravel Forge connects to Supabase via the Transaction Pooler (`port 6543`) or Session/Direct connection (`port 5432`) is unconfirmed.
3. **Exact Role & Permission Implementation Pattern:**
   * The UI displays `Admin Owner` with `Level 3`, while documentation references `Owner/Admin`, `Manager`, and `Cashier`. Whether role checks are implemented via an integer column (`level = 1, 2, 3`), a string/enum column (`role`), custom Laravel middleware, or a package like `spatie/laravel-permission` must be verified in `app/Models/User.php` and `routes/web.php`.
4. **Excel Export Package Implementation:**
   * Which specific library handles the `Sync to Excel` and report export feature (e.g., `maatwebsite/excel`, `openspout`, or native PHP CSV/spreadsheet streaming) must be verified in `composer.json` before modifying export controllers.
5. **Frontend CSS & Charting Libraries:**
   * Which CSS framework (e.g., Tailwind CSS, Bootstrap, or custom CSS) and JavaScript charting library (e.g., Chart.js, ApexCharts) render the Dashboard layout and `Sales` bar chart must be verified in `package.json` and `resources/views/` before editing UI templates.
6. **Multi-Branch Database Architecture (Future Roadmap Only):**
   * For the planned Phase 2 expansion across `Bangkal`, `Catalunan`, and `Victoria Plaza`, it has not yet been decided whether branch isolation will use a shared schema with a `branch_id` foreign key on all operational tables or separate schemas/databases per branch.