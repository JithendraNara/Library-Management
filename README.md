# Central Library — raw PHP + MySQL

A rebuild of my 2021 Tkinter CLI library app as a real web application.
**No framework** — a hand-rolled router, PDO models with prepared statements,
and server-rendered PHP templates. Every layer is touched: schema, connection,
models, routing, and views.

## What it does

- **Catalog** — list, search, add, and delete books (with copy counts)
- **Members** — manage borrowers
- **Loans** — issue a book to a member (decrements availability) and return it
  (increments it). Borrow/return run in a transaction with a row lock, so
  `available_copies` can never drift out of sync, even under concurrent requests.
- Overdue detection on active loans.

The original CLI seeded `["Algorithms", "Django", "Clrs", "Python Notes"]` —
those four are seeded here too.

## Structure

```
config.example.php      copy to config.php — DB credentials + loan period
database/schema.sql     books / members / loans tables
database/seed.sql       the original 4 books + a demo member
src/Database.php        singleton PDO factory (real prepared statements)
src/Book.php            catalog model
src/Member.php          member model
src/Loan.php            borrow/return business logic (transactional)
public/index.php        front controller + router
public/style.css        dark UI
public/.htaccess        Apache rewrite to index.php
views/                  layout + page templates
router.php              router for PHP's built-in server
```

## Setup

```bash
# 1. Database
mysql -u root < database/schema.sql
mysql -u root < database/seed.sql

# 2. Config
cp config.example.php config.php
# edit config.php if your DB user/pass differ

# 3. Run (dev server — no Apache needed)
php -S 127.0.0.1:8000 router.php
```

Open <http://127.0.0.1:8000>.

For Apache/Nginx, point the document root at `public/` (`.htaccess` handles
routing on Apache).

## Notes

- All queries use PDO prepared statements — no string-interpolated SQL.
- `available_copies` is guarded by a `CHECK` constraint and a transactional
  `FOR UPDATE` lock on borrow/return.
- Output is escaped with `htmlspecialchars` everywhere (`e()` helper).
