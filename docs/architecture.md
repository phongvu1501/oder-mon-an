# OderMonAn - Architecture and Database Design

## Audit baseline

The project started as the Laravel 12 skeleton on PHP 8.2 with the three default
migrations already applied to MySQL. It had no authentication UI/API, domain
models, authorization, business routes, or business tests. The existing user
migration is retained; new columns are introduced by a forward-only migration.

## Business decisions

- A restaurant table has many service sessions but at most one active session.
- A session accepts many orders and owns one aggregate bill. An order is not a bill.
- Customer access starts from a random 64-character table QR token. Laravel's
  encrypted session binds the browser to the current table session, so queries
  never use a client-supplied table id.
- An idempotency key is unique within a table session. Network retries return the
  existing order instead of creating another one.
- Product name and unit price are copied to each order item. Historical totals do
  not depend on the current product record.
- Inventory is deducted once when an order first enters `preparing`. Cancelling a
  prepared order does not automatically return ingredients because they may have
  been consumed; a manager records an explicit return/waste adjustment.
- Payments may be split. Only staff confirmation changes a payment to `paid`.
  Row locks prevent overpayment and concurrent session closure.
- Users may have multiple roles. Permissions are evaluated centrally, never from
  hard-coded role names in controllers.
- Purchase orders and automated bank webhooks are deferred. Inventory transactions
  and supplier references cover the version-one receiving workflow without an
  unused purchasing subsystem.

## ERD

```text
users --< role_user >-- roles --< permission_role >-- permissions
  |             |                                      |
  |             +---------------- authorization -------+
  |
  +--< activity_logs
  +--< orders (created_by / confirmed_by)
  +--< payments (confirmed_by)
  +--< inventory_transactions (created_by)

categories --< products --1 recipes --< recipe_items >-- inventory_items
                    |                                      |
                    +--< order_items                       +--< inventory_transaction_items
                                                               |
suppliers ------------------------------------------------ inventory_transactions

restaurant_tables --< table_sessions --< orders --< order_items
                           |
                           +--1 bills --< payments
```

## Schema summary

All primary and foreign keys are unsigned BIGINT. Monetary values use
`DECIMAL(15,2)` and stock quantities use `DECIMAL(15,3)`.

| Table | Important columns and constraints | Delete policy |
|---|---|---|
| users | email unique; status indexed; token_version; deleted_at | soft delete |
| roles | name and slug unique; status | protected by pivot |
| permissions | name unique; module indexed | protected by pivot |
| role_user | unique user_id + role_id | cascade pivot only |
| permission_role | unique permission_id + role_id | cascade pivot only |
| categories | slug unique; status; sort_order; deleted_at | soft delete, products restrict |
| products | category_id; sku/slug unique; price/cost; status; is_available | soft delete, history retains snapshots |
| restaurant_tables | code and qr_token unique; status; deleted_at | soft delete, sessions restrict |
| table_sessions | session_code unique; table_id; status; opened/closed timestamps | historical, no soft delete |
| orders | order_code unique; session_id + idempotency_key unique; totals; status | immutable history |
| order_items | nullable product_id; name/price snapshots; quantity; status | cascades only with order rollback |
| bills | table_session_id unique; bill_code unique; totals; payment status | immutable history |
| payments | payment_code/request_id unique; bill/session; amount; method/status | immutable history |
| suppliers | contact fields; status; deleted_at | soft delete, transaction FK set null |
| inventory_items | sku unique; current/minimum stock; status; deleted_at | soft delete, history restrict |
| recipes | product_id unique; yield quantity | cascade with non-historical definition |
| recipe_items | recipe + inventory item unique; quantity | cascade recipe, inventory restrict |
| inventory_transactions | code unique; type; reference; actor/supplier | immutable history |
| inventory_transaction_items | before/change/after quantity snapshots | cascades only with failed parent creation |
| activity_logs | actor, action, module, subject, old/new JSON, request metadata | immutable history |

Database checks enforce non-negative money, positive order quantities, valid
statuses, unique business identifiers, and one active session through transactional
row locking (a portable partial unique index is not available in MySQL).

