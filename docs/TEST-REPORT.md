# Test Report

Generated: 2026-11-23

## Scope

Comprehensive Laravel feature-test pass covering authentication, roles, POS checkout, inventory, categories, members, credits, transactions, activity logs, analytics boundaries, report exports, account security, and route smoke coverage.

No application code was changed during this verification pass. Test files, factories, and this documentation were added. The working tree already contained application changes from earlier work.

## Environment

- Tests use `APP_ENV=testing`, `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:` from `phpunit.xml`.
- No `.env.testing` file is used; PHPUnit overrides the database settings.
- Dompdf is installed and the PDF export test passes.
- Disk space is nearly full; no packages were installed during this pass.

## Commands

| Command | Result |
|---|---|
| `php artisan test tests/Feature/FullCoverage` | 48 passed, 8 failed, 1 incomplete (307 assertions) |
| `composer test` | 225 passed, 18 failed, 1 incomplete, 1 skipped (1322 assertions) |
| `php artisan route:list` | 61 routes |
| `vendor/bin/pint --test database/factories tests/Support tests/Feature/FullCoverage tests/TestCase.php` | Pass |

## Status Legend

- **PASS** — assertion succeeded.
- **FAIL** — application behavior does not match the test/spec.
- **NOT IMPLEMENTED** — functionality or contract is absent; test documents current absence.
- **MANUAL** — requires browser verification; feature tests cannot cover it reliably.

## Overall Status

| Area | Pass | Fail | Incomplete | Notes |
|---|---:|---:|---:|---|
| Auth and roles | 5 | 0 | 0 | Guest redirects, role guards, inactive sessions |
| Account security | 4 | 0 | 1 | CSRF needs browser verification |
| POS checkout | 7 | 0 | 0 | Stock, totals, members, credits, payment validation |
| Inventory | 7 | 0 | 0 | CRUD, validation, cashier read-only, low stock |
| Categories | 3 | 0 | 0 | Create/delete rules and shared select |
| Members | 6 | 1 | 0 | Lookup fails on SQLite `CONCAT` |
| Credits | 4 | 0 | 0 | Repayment schema/routes absent; documented |
| Transactions and activity logs | 6 | 0 | 0 | Filters, export, cashier denial |
| Analytics boundaries | 3 | 4 | 0 | SQLite MySQL functions and period boundaries |
| Report access/exports | 3 | 1 | 0 | Cashier 403 spec mismatch |
| Route smoke | 0 | 2 | 0 | Admin 500s, cashier member lookup 500 |
| **New grouped suite total** | **48** | **8** | **1** | 57 tests |
| **Full suite total** | **225** | **18** | **1** | Plus 1 skipped |

## Route/Role Matrix

| Route group | Guest | Cashier | Admin | Notes |
|---|---|---|---|---|
| `/`, `/login` | Allowed | Allowed | Allowed | `/` redirects to `/login` |
| `POST /login`, `POST /logout` | Login allowed; logout auth-protected | Allowed | Allowed | `POST /logout` requires auth |
| `/account/status` | Redirect login | Allowed | Allowed | `auth` |
| `/pos`, `POST /checkout`, `/items/lookup`, `/members/lookup` | Redirect login | Allowed | Allowed | `role.cashier`; admin allowed by `RoleMiddleware` |
| `/credits`, `/credits/{credit}` | Redirect login | Allowed | Allowed | Scoped by controller |
| `/my-transactions` | Redirect login | Allowed | Allowed | Scoped to session user |
| `/inventory`, `/inventory/low-stock`, barcode download | Redirect login | Read-only | Full access | `auth` |
| `/inventory/add`, `/inventory/edit/{id}` | Redirect login | HTML GET redirects to `/pos`; non-safe/JSON 403 | Allowed | `role.admin` |
| `POST/PUT/DELETE /inventory/*`, `/inventory/categories/*` | Redirect login | 403 with permission-denied audit | Allowed | `inventory.write` |
| `/members/*` management/cards/analytics index | Redirect login | HTML GET redirects to `/pos`; non-safe/JSON 403 | Allowed | `role.admin` |
| `/accounts/*` | Redirect login | HTML GET redirects to `/pos`; non-safe/JSON 403 | Allowed | `role.admin` |
| `/analytics`, `/api/analytics/*` | Analytics API public; page redirect login | Allowed | Allowed | Page uses `auth`; API public |
| `/reports/*` | Redirect login | Currently allowed with scoped data; spec test expects 403 | Allowed | `report.access` allows admin and cashier |
| `/transactions/*`, `/transactions/activity`, `/transactions/export` | Redirect login | HTML GET redirects to `/pos`; non-safe/JSON 403 | Allowed | `role.admin` |
| `/api/test`, `/api/create-sample-data` | Allowed | Allowed | Allowed | Public API |
| `/api/user` | 500 | 500 | 500 | `auth:sanctum` guard is undefined |

Route note: `/members/analytics/{id}` is registered twice in `routes/web.php` (lines 88 and 90) under the same `members.analytics` name. The route list resolves the name to `MemberController@memberAnalytics`.

## Ordered Failures

Severity order: critical, high, medium, low.

1. **CRITICAL — `RouteSmokeTest::test_every_admin_get_route_responds_without_server_error`**
   - `GET /members/lookup?q=...` returned 500: SQLite `no such function: CONCAT` from `MemberController@lookup` (`app/Http/Controllers/MemberController.php:345`).
   - `GET /members/analytics` returned 500: `Undefined variable $member` in `resources/views/members/analytics.blade.php:5`.
   - `GET /api/user` returned 500: `Auth guard [sanctum] is not defined` from `routes/api.php:19`.

2. **HIGH — `MemberManagementTest::test_member_lookup_accepts_partial_id_and_blocks_inactive_members`**
   - Expected 200, got 500.
   - Same root cause: raw `CONCAT(first_name, ' ', last_name)` in `MemberController@lookup` is not portable to SQLite.

3. **HIGH — `RouteSmokeTest::test_every_cashier_allowed_get_route_responds_without_server_error`**
   - `GET /members/lookup?q=...` returned 500 from the same `CONCAT` query.
   - This affects POS member lookup for cashiers under the test database.

4. **MEDIUM — `ReportAccessTest::test_cashier_report_access_is_denied`**
   - Expected 403, got 200.
   - `EnsureReportAccess` currently allows both `admin` and `cashier`; cashier data is scoped by `ReportService`.
   - Needs product decision: enforce report denial for cashiers or update/remove the spec test.

5. **MEDIUM — `AnalyticsBoundaryTest::test_monthly_sales_api_works_on_sqlite_test_database`**
   - 500: `no such function: DATE_FORMAT` from `AnalyticsController@getSalesData` (`app/Http/Controllers/AnalyticsController.php:140`).

6. **MEDIUM — `AnalyticsBoundaryTest::test_yearly_sales_api_works_on_sqlite_test_database`**
   - 500: `no such function: YEAR` from `AnalyticsController@getSalesData` (`app/Http/Controllers/AnalyticsController.php:161`).

7. **LOW — `AnalyticsBoundaryTest::test_weekly_sales_respect_first_week_and_future_periods`**
   - Future month `2026-04` returned 5 buckets instead of 0; future periods are clamped to the current month.

8. **LOW — `AnalyticsBoundaryTest::test_year_dropdown_only_lists_years_with_sales`**
   - Analytics index contains `value="2026"` even though only 2025 has sales.
   - `AnalyticsController@index` pushes the current year into `$quarterYears`.

### Pre-existing Full-Suite Failures

These failures existed before this pass and remain unchanged:

| Test | Root cause |
|---|---|
| `BarcodeScanningTest` (4 tests) | Checkout payloads lack `amount_received`; app now returns `Amount received is required for cash payments.` |
| `CreditSaleTest::credit sale rejected for inactive member` | Expected 400, app returns 403 |
| `CreditSaleTest::cash sale marks purchases as cash` | Checkout fails before `purchases` insert because `amount_received` is missing |
| `NonMemberSaleTest` (2 tests) | Checkout payloads lack `amount_received` |
| `TransactionTrackingTest` (2 tests) | Checkout payloads lack `amount_received` |

## Not Implemented / Manual

| Item | Status | Evidence |
|---|---|---|
| Credit repayment/payment history | NOT IMPLEMENTED | `credit_payments` table dropped; no `credits.amount_paid`/`status`; no repayment routes. `CreditManagementTest` asserts absence. |
| CSRF rejection in feature tests | MANUAL | `AccountSecurityTest::test_csrf_protection_requires_browser_verification` is marked incomplete because Laravel disables CSRF middleware in feature tests. |
| Cashier report denial | FAIL / spec mismatch | Current app intentionally scopes reports for cashiers; test expects 403. |

## Added Files

### Factories

- `database/factories/AuditLogFactory.php`
- `database/factories/CategoryFactory.php`
- `database/factories/CreditFactory.php`
- `database/factories/ItemFactory.php`
- `database/factories/MemberFactory.php`
- `database/factories/ProductFactory.php`
- `database/factories/PurchaseFactory.php`
- `database/factories/SaleFactory.php`
- `database/factories/SaleItemFactory.php`

### Test Support

- `tests/Support/CreatesScenarioData.php`
- `tests/TestCase.php` updated to use the helper trait.

### Feature Tests

- `tests/Feature/FullCoverage/AuthAndRolesTest.php`
- `tests/Feature/FullCoverage/AccountSecurityTest.php`
- `tests/Feature/FullCoverage/AnalyticsBoundaryTest.php`
- `tests/Feature/FullCoverage/CategoryManagementTest.php`
- `tests/Feature/FullCoverage/CreditManagementTest.php`
- `tests/Feature/FullCoverage/InventoryManagementTest.php`
- `tests/Feature/FullCoverage/MemberManagementTest.php`
- `tests/Feature/FullCoverage/ReportAccessTest.php`
- `tests/Feature/FullCoverage/PosCheckoutTest.php`
- `tests/Feature/FullCoverage/RouteSmokeTest.php`
- `tests/Feature/FullCoverage/TransactionAndActivityLogTest.php`

### Documentation

- `docs/TEST-REPORT.md`
- `docs/MANUAL-TEST-CHECKLIST.md`
