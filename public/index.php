<?php
declare(strict_types=1);

/**
 * Central Library — front controller.
 *
 * Single entry point. All requests come through here (see .htaccess /
 * the built-in server router). No framework — just a hand-rolled router,
 * PDO models, and server-rendered templates.
 *
 * Business logic lives in controllers/; helpers and shared view helpers
 * live in controllers/helpers.php.
 */

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

require_once __DIR__ . '/../controllers/helpers.php';
require_once __DIR__ . '/../src/Book.php';
require_once __DIR__ . '/../src/Member.php';
require_once __DIR__ . '/../src/Loan.php';

require_once __DIR__ . '/../controllers/books.php';
require_once __DIR__ . '/../controllers/members.php';
require_once __DIR__ . '/../controllers/loans.php';

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
            borrowFromPost((int) $config['loan_days']);
            redirect('/loans');

        case '/loans/return':
            returnFromPost();
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
