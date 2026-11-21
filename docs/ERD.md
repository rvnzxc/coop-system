# CoopPOS Entity-Relationship Diagram

Schema reflects the live `coopos` database after credit repayment was removed and the payment-history schema was dropped.

## Legend

| Marking | Meaning |
|---|---|
| Active | Still created or updated by the current application |
| Legacy | Older table still present for backward compatibility or migration history |

`credit_payments`, `credits.amount_paid`, and `credits.status` are no longer part of the schema. Existing credit and payment data was intentionally cleared when the credit ledger was converted to purchase-only records.

## ERD

```mermaid
erDiagram
    USERS ||--o{ SALES : "cashier_id"
    USERS ||--o{ SALES : "voided_by"
    USERS ||--o{ AUDIT_LOGS : "user_id"

    MEMBERS ||--o{ SALES : "member_id"
    MEMBERS ||--o{ CREDITS : "member_id"
    MEMBERS ||--o{ PURCHASES : "legacy member_id"

    SALES ||--o{ SALE_ITEMS : "sale_id"
    ITEMS ||--o{ SALE_ITEMS : "item_id"

    USERS {
        bigint id PK
        varchar name
        varchar username UK "nullable"
        varchar email UK "nullable"
        timestamp email_verified_at "nullable"
        varchar password
        varchar remember_token "nullable"
        enum role "admin|cashier"
        boolean is_active
        timestamp created_at
        timestamp updated_at
    }

    MEMBERS {
        bigint id PK
        varchar member_number UK
        varchar first_name
        varchar last_name
        varchar email UK "nullable"
        varchar phone "nullable"
        decimal total_purchases
        int purchase_count
        datetime last_purchase_date "nullable"
        boolean is_active
        timestamp created_at
        timestamp updated_at
    }

    ITEMS {
        bigint id PK
        varchar item_name
        varchar barcode UK "nullable"
        varchar manufacturer_barcode UK "nullable"
        int quantity
        decimal price
        varchar category "nullable"
        timestamp created_at
        timestamp updated_at
    }

    SALES {
        bigint id PK
        bigint cashier_id FK "nullable"
        bigint member_id FK "nullable"
        varchar member_number "nullable"
        varchar customer_type "member|non_member"
        varchar payment_method "cash|credit"
        decimal total
        decimal amount_received "cash tender; nullable"
        decimal change_amount "nullable"
        varchar status "completed|voided"
        bigint voided_by FK "nullable"
        text void_reason "nullable"
        timestamp voided_at "nullable"
        timestamp created_at
        timestamp updated_at
    }

    SALE_ITEMS {
        bigint id PK
        bigint sale_id FK
        bigint item_id FK "nullable"
        varchar product_name
        int quantity
        decimal unit_price
        decimal subtotal
        timestamp created_at
        timestamp updated_at
    }

    CREDITS {
        bigint id PK
        bigint member_id FK
        decimal amount
        varchar sale_reference "loose reference to sale; no FK"
        text notes "nullable"
        longtext items_snapshot "JSON encoded; nullable"
        timestamp created_at
        timestamp updated_at
    }

    PURCHASES {
        bigint id PK
        bigint member_id FK "nullable; legacy"
        varchar member_number "nullable; legacy"
        varchar customer_type "legacy"
        varchar payment_method "legacy"
        decimal amount "legacy"
        int quantity "nullable; legacy"
        varchar product_name "nullable; legacy"
        date purchase_date "legacy"
        timestamp created_at
        timestamp updated_at
    }

    AUDIT_LOGS {
        bigint id PK
        bigint user_id FK "nullable"
        varchar action
        varchar target_type "nullable"
        bigint target_id "nullable"
        longtext details "JSON encoded; nullable"
        timestamp created_at
        timestamp updated_at
    }
```

## Entity notes

| Entity / field | Status | Notes |
|---|---|---|
| `CREDITS` | Active | Purchase-only credit ledger. Each row records a credit sale for a member. |
| `CREDITS.amount` | Active | Amount charged on credit. |
| `CREDITS.sale_reference` | Active | Loose text reference to the related sale; there is no enforced foreign key. |
| `CREDITS.items_snapshot` | Active | JSON snapshot of the items purchased on credit. |
| `PURCHASES` | Legacy | Older purchase table retained for compatibility and migration history. |
| `SALES.payment_method` | Active | Remains `cash` or `credit`; credit sales are still recorded in `sales` and `sale_items`. |
| `CREDIT_PAYMENTS` | Removed | Repayment history table was dropped. |
| `CREDITS.amount_paid` | Removed | Payment tracking column was dropped. |
| `CREDITS.status` | Removed | `unpaid`, `partial`, and `paid` tracking was dropped. |

## Data reset note

All rows in the old `credits` table and the removed `credit_payments` table were discarded. No archive table was created. Users, members, items, sales, sale items, legacy purchases, and audit logs were not cleared.
