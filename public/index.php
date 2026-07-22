<?php
declare(strict_types=1);

/**
 * Central Library — front controller / router.
 *
 * Single entry point. All requests come through here (see .htaccess /
 * the built-in server router). No framework — just a hand-rolled router,
 * PDO models, and server-rendered templates.
 */

require_once __DIR__ . '/../src/Book.php';
require_once __DIR__ . '/../src/Member.php';
require_once __DIR__ . '/../src/Loan.php';

$CONFIG = require __DIR__ . '/../config.php';

// Harden the session cookie: not readable by JS, only sent over the same
// site for top-level navigations (SameSite=Lax blocks cross-site POSTs),
// and marked Secure when the request came over HTTPS.
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => !empty($_SERVER['HTTPS']),
    'httponly'  => true,
    'samesite' => 'Lax',
]);

// --- tiny helpers -------------------------------------------------------

function e(?string $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

function redirect(string $to): never
{
    header('Location: ' . $to);
    exit;
}

/** Render a template with extracted vars, wrapped in the layout. */
function render(string $view, array $data = [], string $title = 'Central Library'): void
{
    extract($data, EXTR_SKIP);
    ob_start();
    require __DIR__ . "/../views/{$view}.php";
    $content = ob_get_clean();
    require __DIR__ . '/../views/layout.php';
}

/** Start the session exactly once per request (avoids duplicate-start notices). */
function startSession(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

/** Pull a flash message set via session, if any. */
function flash(): ?array
{
    startSession();
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

function setFlash(string $type, string $message): void
{
    startSession();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/** Get (or lazily create) the per-session CSRF token. */
function csrfToken(): string
{
    startSession();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

/** Render a hidden CSRF input for inclusion in every state-changing form. */
function csrfField(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrfToken()) . '">';
}

/** True when the submitted token matches the session token. */
function csrfIsValid(?string $sent): bool
{
    return is_string($sent) && $sent !== '' && hash_equals(csrfToken(), $sent);
}

/**
 * Validate the CSRF token on a POST request.
 * Aborts with 419 if the token is missing or does not match.
 */
function requireCsrf(): void
{
    if (csrfIsValid($_POST['csrf'] ?? null)) {
        return;
    }
    http_response_code(419);
    render('error', ['error' => 'Invalid or missing CSRF token. Please go back and retry.'], 'Security check failed');
    exit;
}

// --- routing ------------------------------------------------------------

$method = $_SERVER['REQUEST_METHOD'];
$path   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$path   = rtrim($path, '/') ?: '/';

/** GET routes — read-only pages. */
function handleGet(string $path): void
{
    switch ($path) {
        case '/':
            render('home', ['stats' => Book::stats(), 'books' => Book::all()], 'Catalog');
            break;

        case '/books':
            $q     = trim($_GET['q'] ?? '');
            $books = $q !== '' ? Book::search($q) : Book::all();
            render('books', ['books' => $books, 'q' => $q], 'Books');
            break;

        case '/members':
            render('members', ['members' => Member::all()], 'Members');
            break;

        case '/loans':
            render('loans', [
                'active'  => Loan::active(),
                'history' => Loan::history(),
            ], 'Loans');
            break;

        default:
            http_response_code(404);
            render('404', [], 'Not Found');
    }
}

/** Create a book from POST input, flashing success or a validation error. */
function createBookFromPost(): void
{
    $title  = trim($_POST['title'] ?? '');
    if ($title === '') {
        setFlash('error', 'Title is required.');
        return;
    }
    Book::create($title, trim($_POST['author'] ?? ''), max(1, (int)($_POST['copies'] ?? 1)));
    setFlash('success', "Added “{$title}” to the catalog.");
}

/** Create a member from POST input, flashing success or a validation error. */
function createMemberFromPost(): void
{
    $name = trim($_POST['name'] ?? '');
    if ($name === '') {
        setFlash('error', 'Name is required.');
        return;
    }
    Member::create($name, trim($_POST['email'] ?? ''));
    setFlash('success', "Member “{$name}” added.");
}

/**
 * Generic guarded delete: refuse when the item still has active (unreturned)
 * loans, so the loan history is never silently cascade-deleted.
 *
 * $activeCount and $doDelete are zero-arg closures bound to the specific
 * model, which keeps the call sites short and avoids the "string-heavy
 * function arguments" pattern CodeScene flagged on callables.
 */
function guardedDelete(string $label, \Closure $activeCount, \Closure $doDelete): void
{
    $id = (int)($_POST['id'] ?? 0);
    if ($activeCount($id) > 0) {
        setFlash('error', "Cannot delete: this {$label} has items currently on loan. Return them first.");
        return;
    }
    $doDelete($id);
    setFlash('success', ucfirst($label) . ' removed.');
}

/** Thin wrappers — keep handlePost() flat and the call sites readable. */
function deleteGuardedBook(): void
{
    guardedDelete('book', fn(int $id) => Book::activeLoanCount($id), fn(int $id) => Book::delete($id));
}
function deleteGuardedMember(): void
{
    guardedDelete('member', fn(int $id) => Member::activeLoanCount($id), fn(int $id) => Member::delete($id));
}

/** POST routes — all state-changing actions (CSRF-checked by the caller). */
function handlePost(string $path, array $config): void
{
    switch ($path) {
        case '/books/create':
            createBookFromPost();
            redirect('/books');

        case '/books/delete':
            deleteGuardedBook();
            redirect('/books');

        case '/members/create':
            createMemberFromPost();
            redirect('/members');

        case '/members/delete':
            deleteGuardedMember();
            redirect('/members');

        case '/loans/borrow':
            $res = Loan::borrow(
                (int)($_POST['book_id'] ?? 0),
                (int)($_POST['member_id'] ?? 0),
                (int) $config['loan_days']
            );
            setFlash($res['ok'] ? 'success' : 'error', $res['message']);
            redirect('/loans');

        case '/loans/return':
            $res = Loan::returnBook((int)($_POST['book_id'] ?? 0));
            setFlash($res['ok'] ? 'success' : 'error', $res['message']);
            redirect('/loans');

        default:
            http_response_code(404);
            render('404', [], 'Not Found');
    }
}

try {
    if ($method === 'GET') {
        handleGet($path);
    } elseif ($method === 'POST') {
        requireCsrf();
        handlePost($path, $CONFIG);
    } else {
        http_response_code(405);
        render('404', [], 'Method Not Allowed');
    }
} catch (Throwable $e) {
    http_response_code(500);
    render('error', ['error' => $e->getMessage()], 'Error');
}
