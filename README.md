# Central Library — raw PHP + MySQL

A rebuild of my 2021 Tkinter CLI library app as a real web application.
**No framework** — hand-rolled router, PDO models with prepared statements,
and server-rendered PHP templates. Every layer is touched: schema, connection,
models, routing, controllers, and views.

## What it does

- **Catalog** — list, search, add, and delete books (with copy counts)
- **Members** — manage borrowers
- **Loans** — issue a book to a member (decrements availability) and return it
  (increments it). Borrow/return run in a transaction with a row lock, so
  `available_copies` can never drift out of sync, even under concurrent requests.
- Overdue detection on active loans.
- Search treats `%` and `_` as literal characters, not LIKE wildcards.

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
controllers/
  helpers.php           csrfToken/Field/IsValid/requireCsrf, render, flash, e
  books.php             createBookFromPost, deleteGuardedBook, guardedDelete
  members.php           createMemberFromPost, deleteGuardedMember
  loans.php             borrowFromPost, returnFromPost
public/index.php        front controller — session config, requires, dispatch
public/style.css        dark UI
public/.htaccess        Apache rewrite to index.php
views/                  layout + page templates (home, books, members, loans, 404, error)
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

## Routes

| Method | Path              | Purpose                         |
| ------ | ----------------- | ------------------------------- |
| GET    | `/`               | Catalog home + stats            |
| GET    | `/books`          | List books (search via `?q=`)   |
| POST   | `/books/create`   | Add a book (CSRF required)      |
| POST   | `/books/delete`   | Delete a book (refused if on loan) |
| GET    | `/members`        | List members                    |
| POST   | `/members/create` | Add a member (CSRF required)    |
| POST   | `/members/delete` | Delete a member (refused if on loan) |
| GET    | `/loans`          | Active loans + history          |
| POST   | `/loans/borrow`   | Issue a book (CSRF required)    |
| POST   | `/loans/return`   | Return a book (CSRF required)   |

All POST routes require a valid CSRF token; missing/invalid tokens get `419`.

## Security notes

- All queries use PDO prepared statements — no string-interpolated SQL.
- `available_copies` is guarded by a `CHECK` constraint and a transactional
  `SELECT ... FOR UPDATE` lock on borrow and return.
- Output is escaped with `htmlspecialchars` everywhere (`e()` helper).
- CSRF tokens are per-session, 64-char hex from `random_bytes(32)`, validated
  with `hash_equals`. Every form includes `csrfField()`; `requireCsrf()` is
  called at the top of every POST handler.
- Session cookies are `HttpOnly` + `SameSite=Lax`, and `Secure` whenever the
  request came over HTTPS.
- LIKE wildcards in book search are escaped (`%\\_`) and matched with an
  explicit `ESCAPE '\\'` clause.
- Books and members cannot be deleted while they have active (unreturned)
  loans — the handler flashes an error and redirects without writing.

## Notes

- The original Python CLI (`main.py`) is kept in the tree for reference.
- The PHP app is the supported path going forward; new features go in the
  controllers/ layer, not in `main.py`.
