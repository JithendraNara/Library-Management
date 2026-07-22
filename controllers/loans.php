<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/Book.php';
require_once __DIR__ . '/../src/Loan.php';
require_once __DIR__ . '/helpers.php';

/** Issue a book to a member, flashing the model's success or error message. */
function borrowFromPost(int $loanDays): void
{
    $res = Loan::borrow(
        (int)($_POST['book_id'] ?? 0),
        (int)($_POST['member_id'] ?? 0),
        $loanDays
    );
    setFlash($res['ok'] ? 'success' : 'error', $res['message']);
}

/** Return a book by id, flashing the model's success or error message. */
function returnFromPost(): void
{
    $res = Loan::returnBook((int)($_POST['book_id'] ?? 0));
    setFlash($res['ok'] ? 'success' : 'error', $res['message']);
}
