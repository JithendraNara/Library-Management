<?php
declare(strict_types=1);

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Book.php';
require_once __DIR__ . '/Member.php';

/**
 * Loan model — the borrow/return business logic.
 *
 * Borrow and return run inside a transaction with a row lock so that
 * available_copies can never drift out of sync with the loans table,
 * even under concurrent requests.
 */
final class Loan
{
    /**
     * Issue a book to a member.
     * @return array{ok:bool,message:string}
     */
    public static function borrow(int $bookId, int $memberId, int $loanDays): array
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            // Lock the book row for the duration of the transaction.
            $stmt = $pdo->prepare('SELECT * FROM books WHERE id = ? FOR UPDATE');
            $stmt->execute([$bookId]);
            $book = $stmt->fetch();

            if (!$book) {
                $pdo->rollBack();
                return ['ok' => false, 'message' => 'That book does not exist.'];
            }
            if ((int) $book['available_copies'] <= 0) {
                $pdo->rollBack();
                return ['ok' => false,
                        'message' => "Sorry, “{$book['title']}” is fully issued. Please wait until a copy is returned."];
            }

            $member = Member::find($memberId);
            if (!$member) {
                $pdo->rollBack();
                return ['ok' => false, 'message' => 'That member does not exist.'];
            }

            $due = (new DateTimeImmutable("+{$loanDays} days"))->format('Y-m-d');

            $pdo->prepare(
                'INSERT INTO loans (book_id, member_id, due_at) VALUES (?, ?, ?)'
            )->execute([$bookId, $memberId, $due]);

            $pdo->prepare(
                'UPDATE books SET available_copies = available_copies - 1 WHERE id = ?'
            )->execute([$bookId]);

            $pdo->commit();
            return ['ok' => true,
                    'message' => "“{$book['title']}” issued to {$member['name']}. Please return it within {$loanDays} days (due {$due})."];
        } catch (Throwable $e) {
            $pdo->rollBack();
            return ['ok' => false, 'message' => 'Could not issue book: ' . $e->getMessage()];
        }
    }

    /**
     * Return the oldest active loan for a book.
     * @return array{ok:bool,message:string}
     */
    public static function returnBook(int $bookId): array
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT * FROM books WHERE id = ? FOR UPDATE');
            $stmt->execute([$bookId]);
            $book = $stmt->fetch();
            if (!$book) {
                $pdo->rollBack();
                return ['ok' => false, 'message' => 'That book does not exist.'];
            }

            // Find the oldest unreturned loan for this book, locking the row so
            // two concurrent returns can't both claim the same loan and double-
            // increment available_copies.
            $stmt = $pdo->prepare(
                'SELECT * FROM loans
                 WHERE book_id = ? AND returned_at IS NULL
                 ORDER BY borrowed_at ASC LIMIT 1
                 FOR UPDATE'
            );
            $stmt->execute([$bookId]);
            $loan = $stmt->fetch();

            if (!$loan) {
                $pdo->rollBack();
                return ['ok' => false, 'message' => "No active loan found for “{$book['title']}”."];
            }

            // Guard: refuse if every copy is already available, so the counter
            // can never be inflated past total_copies even with stale loan rows.
            if ((int) $book['available_copies'] >= (int) $book['total_copies']) {
                $pdo->rollBack();
                return ['ok' => false,
                        'message' => "All copies of “{$book['title']}” are already available — nothing to return."];
            }

            $pdo->prepare('UPDATE loans SET returned_at = NOW() WHERE id = ?')
                ->execute([$loan['id']]);

            $pdo->prepare(
                'UPDATE books SET available_copies = available_copies + 1 WHERE id = ?'
            )->execute([$bookId]);

            $pdo->commit();
            return ['ok' => true,
                    'message' => "Thanks for returning “{$book['title']}”! Hope you enjoyed it."];
        } catch (Throwable $e) {
            $pdo->rollBack();
            return ['ok' => false, 'message' => 'Could not return book: ' . $e->getMessage()];
        }
    }

    /** Currently active (unreturned) loans, joined to book + member. */
    public static function active(): array
    {
        $stmt = Database::pdo()->query(
            'SELECT l.*, b.title, b.author, m.name AS member_name
             FROM loans l
             JOIN books   b ON b.id = l.book_id
             JOIN members m ON m.id = l.member_id
             WHERE l.returned_at IS NULL
             ORDER BY l.due_at ASC'
        );
        return $stmt->fetchAll();
    }

    /** Full loan history, newest first. */
    public static function history(int $limit = 50): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT l.*, b.title, b.author, m.name AS member_name
             FROM loans l
             JOIN books   b ON b.id = l.book_id
             JOIN members m ON m.id = l.member_id
             ORDER BY l.borrowed_at DESC
             LIMIT ?'
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
