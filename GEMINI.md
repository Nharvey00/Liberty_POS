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
