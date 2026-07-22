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

/** Pull a flash message set via session, if any. */
function flash(): ?array
{
    session_start();
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

function setFlash(string $type, string $message): void
{
    session_start();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

// --- routing ------------------------------------------------------------

$method = $_SERVER['REQUEST_METHOD'];
$path   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$path   = rtrim($path, '/') ?: '/';

try {
    // GET routes
    if ($method === 'GET') {
        switch ($path) {
            case '/':
                $stats  = Book::stats();
                $books  = Book::all();
                render('home', ['stats' => $stats, 'books' => $books], 'Catalog');
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
        exit;
    }

    // POST routes (all state-changing actions)
    if ($method === 'POST') {
        switch ($path) {
            case '/books/create':
                $title  = trim($_POST['title'] ?? '');
                $author = trim($_POST['author'] ?? '');
                $copies = max(1, (int)($_POST['copies'] ?? 1));
                if ($title === '') {
                    setFlash('error', 'Title is required.');
                } else {
                    Book::create($title, $author, $copies);
                    setFlash('success', "Added “{$title}” to the catalog.");
                }
                redirect('/books');

            case '/books/delete':
                $id = (int)($_POST['id'] ?? 0);
                Book::delete($id);
                setFlash('success', 'Book removed.');
                redirect('/books');

            case '/members/create':
                $name  = trim($_POST['name'] ?? '');
                $email = trim($_POST['email'] ?? '');
                if ($name === '') {
                    setFlash('error', 'Name is required.');
                } else {
                    Member::create($name, $email);
                    setFlash('success', "Member “{$name}” added.");
                }
                redirect('/members');

            case '/members/delete':
                $id = (int)($_POST['id'] ?? 0);
                Member::delete($id);
                setFlash('success', 'Member removed.');
                redirect('/members');

            case '/loans/borrow':
                $bookId   = (int)($_POST['book_id'] ?? 0);
                $memberId = (int)($_POST['member_id'] ?? 0);
                $res = Loan::borrow($bookId, $memberId, (int) $CONFIG['loan_days']);
                setFlash($res['ok'] ? 'success' : 'error', $res['message']);
                redirect('/loans');

            case '/loans/return':
                $bookId = (int)($_POST['book_id'] ?? 0);
                $res = Loan::returnBook($bookId);
                setFlash($res['ok'] ? 'success' : 'error', $res['message']);
                redirect('/loans');

            default:
                http_response_code(404);
                render('404', [], 'Not Found');
        }
        exit;
    }

    http_response_code(405);
    render('404', [], 'Method Not Allowed');
} catch (Throwable $e) {
    http_response_code(500);
    render('error', ['error' => $e->getMessage()], 'Error');
}
