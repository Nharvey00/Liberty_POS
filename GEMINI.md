# Safe Refactoring and String Replacement

When performing codebase refactoring, renaming variables, or updating strings across files:
1. **Never use broad, unconstrained regex or string replacements** (e.g., simple `sed` or PowerShell `-replace` without boundaries).
2. **Use word boundaries:** Always use regex word boundaries (`\b`) if you must do a raw text replacement (e.g., `-replace '\bcustom\b', 'promo'`).
3. **Prefer targeted replacements:** Use the `replace_file_content` tool with explicit `TargetContent` whenever possible, rather than blind terminal replacements.
4. **Always verify:** Immediately verify the diff or the surrounding code after applying a bulk change to ensure substrings of unrelated words were not accidentally corrupted.

# Strict Default Profiles & Locked UI

When building forms or interfaces for transactions (like Point of Sale discounts):
1. **Lock-in pre-configured defaults:** If a customer or entity has a predefined profile setting (e.g. a default Senior Citizen discount type and percentage), the UI must completely hide manual override inputs and instead display a read-only badge (e.g. "Applied Discount").
2. Manual inputs should only be visible for entities without pre-configured default settings.

# Customer Model Database Queries

1. **Virtual `name` Attribute:** The `name` property on the `Customer` model is a computed accessor. **Never** use `where('name', ...)` or `orWhere('name', ...)` in Eloquent database queries.
2. **Correct Search Fields:** When searching or filtering customers by name, always group queries (`where(function($q) {...})`) targeting the actual database columns: `first_name`, `last_name`, and `business_name`.

# Boolean Columns Stored as Strings

1. **Never combine a `'boolean'` cast with a string mutator:** If a model stores a boolean as `'true'`/`'false'` strings via a `set...Attribute()` mutator (for PostgreSQL compatibility), do **not** also list that column in `$casts` as `'boolean'`. Eloquent's dirty check casts both values to `true`, so the `UPDATE` is silently skipped.
2. **Use the accessor/mutator pair only:** Read through a `get...Attribute(): bool` accessor using `filter_var($value, FILTER_VALIDATE_BOOLEAN)`, and write through the mutator.
3. **Verify persistence in tests:** For any toggle (active/paid/approved), assert the value after `->refresh()` or `->fresh()`, never only on the in-memory model.
4. **Known instances:** `CreditAccount.is_active`, `StatementOfAccount.is_paid`. Check any new boolean column against this rule.
5. **Never filter these columns with integers or mixed-type arrays:** Do not use `whereIn('is_paid', [false, 0, 'false', '0'])` or similar. PostgreSQL throws `boolean = integer`. Use the driver-aware pattern (`DB::raw('true')` / `DB::raw('false')` when the driver is `pgsql`), as in `StatementController` and `PaymentController`.
6. **Remember tests run on SQLite:** a passing test does not prove a boolean query is PostgreSQL-safe. Review boolean filters manually.

# Business-Focused UI for Non-IT Users

1. **No technical metadata in user-facing views:** Never expose internal system identifiers (`event_uuid`, `correlation_id`, raw database entity class names/IDs, internal dot-coded action keys like `product.price_changed`, or IP addresses) in business views—even inside collapsed `<details>` ("Technical reference") blocks.
2. **Plain-language presentation:** Always present human-readable activity labels, formatted currency/quantities, and business-level references (e.g., Invoice/Receipt/SO numbers) so non-IT staff and owners can read every screen effortlessly.

# PostgreSQL Strict Type Queries & Eloquent `whereHas` Grouping

1. **String-typed ID columns (`audit_logs.entity_id`):** Always cast integer model IDs to `(string)` when querying `varchar` identifier columns (e.g., `AuditLog::where('entity_id', (string) $model->id)`). PostgreSQL rejects `character varying = integer`.
2. **UUID columns (`event_uuid`, `movement_uuid`):** Never apply `LIKE` or `ILIKE` directly to PostgreSQL `uuid` columns. Always cast to text first (e.g., `whereRaw("CAST(event_uuid AS TEXT) {$like} ?", ["%{$search}%"])`).
3. **Always group `orWhere` inside `whereHas` / `orWhereHas`:** Never chain top-level `->orWhere(...)` directly inside a `whereHas` closure, as SQL `AND` precedence decouples the foreign-key correlation. Always use `Customer::scopeSearch()` or wrap `orWhere` conditions inside a nested `$q->where(function ($sub) { ... })`.
