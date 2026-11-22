# Manual Test Checklist

Use this checklist in a browser against a non-production database with seeded data. Do not run destructive database commands against the development database.

## Preconditions

- [ ] Confirm `.env` points to the intended local database.
- [ ] Confirm `phpunit.xml` still uses `DB_CONNECTION=sqlite` and `DB_DATABASE=:memory:`.
- [ ] Start the app with `php artisan serve`.
- [ ] Have at least one active admin, one active cashier, one active member, one inactive member, one item with stock, and one active credit.

## Authentication and Roles

- [ ] Guest visiting `/pos`, `/members`, `/accounts`, `/transactions`, `/analytics`, or `/credits` is redirected to `/login`.
- [ ] Active cashier can log in and lands on `/pos`.
- [ ] Active admin can log in and open admin pages.
- [ ] Inactive user cannot log in.
- [ ] Active session that is deactivated by an admin receives the account-disabled response on the next request.
- [ ] Cashier opening an admin HTML page directly is redirected to `/pos` with an error message.
- [ ] Cashier sending a JSON request or non-safe request to an admin route receives 403.
- [ ] Cashier write attempts on inventory/categories create a permission-denied audit log.

## POS Checkout

- [ ] Cash sale deducts item stock.
- [ ] Sale and sale items are saved with correct quantity, unit price, subtotal, discount, and total.
- [ ] Cash payment below total is rejected.
- [ ] `amount_received` is supplied for every cash checkout; missing value is rejected.
- [ ] Checkout with quantity greater than available stock is rejected.
- [ ] Checkout requires either member or non-member selection.
- [ ] Member checkout updates total purchases and purchase count.
- [ ] Credit checkout records a credit and is rejected for non-members.
- [ ] Inactive and archived members are blocked from checkout.
- [ ] Duplicate manufacturer barcodes are rejected.

## Inventory and Categories

- [ ] Admin can create, edit, and delete inventory items.
- [ ] Invalid item values are rejected with validation messages.
- [ ] Duplicate manufacturer barcode is rejected.
- [ ] Low-stock notification appears when quantity is 10 or less.
- [ ] Inventory search, sort, and summary cards use current data.
- [ ] Cashier can view inventory but cannot add, edit, delete, or manage categories.
- [ ] Category with items cannot be deleted; empty category can be deleted.
- [ ] Duplicate category name returns the existing category.
- [ ] POS tabs come from the categories table.

## Members

- [ ] New member gets the next sequential member number.
- [ ] Invalid names, phone numbers, and emails are rejected.
- [ ] Member photo upload appears in forms and public storage.
- [ ] Photo removal clears the stored path.
- [ ] Member with an outstanding credit cannot be archived until the balance is cleared.
- [ ] Member can be archived and restored.
- [ ] Member lookup finds a member by partial ID and full member number.
- [ ] Inactive member lookup is blocked and audited.
- [ ] Member card and card download render with expected headers.
- [ ] Known defect: `/members/lookup?q=...` may return 500 on SQLite due to raw `CONCAT`.

## Credits

- [ ] Credit index groups balances by member.
- [ ] Credit show page displays member and credit snapshot.
- [ ] Credit analytics accepts reversed date ranges.
- [ ] No repayment/payment-history UI exists.
- [ ] No `credit_payments` table, `amount_paid`, `status`, or repayment route exists.
- [ ] Credit quick-download report opens from the credits page.

## Transactions and Activity Logs

- [ ] Admin transaction index lists sales and summaries.
- [ ] Transaction filters work for date, cashier, customer type, and payment method.
- [ ] Invalid transaction date range is rejected.
- [ ] Transaction export downloads an XLSX file.
- [ ] Activity log hides void actions and records real actions.
- [ ] Cashier cannot open admin transaction or activity pages.

## Analytics

- [ ] Daily chart includes the last hour of a month boundary.
- [ ] Weekly chart starts with `Mar 1-7` and respects the first-week boundary.
- [ ] Known defect: future weekly periods such as `2026-04` currently show buckets instead of no data.
- [ ] Quarterly chart hides future quarters and respects year boundaries.
- [ ] Known defect: monthly analytics returns 500 on SQLite due to `DATE_FORMAT`.
- [ ] Known defect: yearly analytics returns 500 on SQLite due to `YEAR`.
- [ ] Daily customer-type breakdown uses only completed sales.
- [ ] Known defect: year dropdown includes the current year even when that year has no sales.

## Reports

- [ ] Report exports are reachable from the analytics dialog and credits quick-download.
- [ ] Admin can export credits, sales, inventory, member-purchases, and transactions as CSV.
- [ ] Admin can export PDF with `application/pdf`, `%PDF-` content, and a filename containing the report name.
- [ ] CSV exports include a comma-separated body and expected filename.
- [ ] Cashier exports are scoped to their own credits, sales, transactions, and member purchases.
- [ ] Inventory report is shared/read-only.
- [ ] Confirm product requirement for cashier report access:
  - Current app: cashier allowed with scoped data.
  - Test spec: cashier receives 403.
- [ ] Denied report access creates a permission-denied audit log.

## Account Security

- [ ] Admin can create a cashier with a hashed password.
- [ ] Submitted role is forced to `cashier`.
- [ ] Duplicate username and email are rejected.
- [ ] Short password is rejected.
- [ ] Validation errors do not expose stack traces or framework namespaces.
- [ ] Admin can deactivate a cashier; action is logged.
- [ ] CSRF: submit a browser form POST without a valid token and confirm 419 rejection.

## Route Smoke Checks

- [ ] Admin can open every admin GET route without a 500.
- [ ] Known defects causing admin 500s:
  - `/members/lookup?q=...` — SQLite `CONCAT`.
  - `/members/analytics` — `Undefined variable $member`.
  - `/api/user` — `Auth guard [sanctum] is not defined`.
- [ ] Cashier can open every allowed GET route without a 500.
- [ ] Known defect causing cashier 500: `/members/lookup?q=...`.

## Verification Commands

- `php artisan test tests/Feature/FullCoverage`
- `composer test`
- `php artisan route:list`
