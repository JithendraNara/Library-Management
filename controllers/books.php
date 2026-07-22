<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/Book.php';
require_once __DIR__ . '/helpers.php';

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

/**
 * Generic guarded delete: refuse when the item still has active (unreturned)
 * loans, so the loan history is never silently cascade-deleted.
 *
 * $activeCount and $doDelete are zero-arg closures bound to the specific
 * model, which keeps the call sites short and avoids the "string-heavy
 * function arguments" pattern.
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

/** Thin wrappers — keep the dispatcher flat and call sites readable. */
function deleteGuardedBook(): void
{
    guardedDelete('book', fn(int $id) => Book::activeLoanCount($id), fn(int $id) => Book::delete($id));
}
