<?php
declare(strict_types=1);

require_once __DIR__ . '/Database.php';

/**
 * Book model — catalog CRUD + availability.
 */
final class Book
{
    /** All books, newest first. */
    public static function all(): array
    {
        $stmt = Database::pdo()->query(
            'SELECT * FROM books ORDER BY title ASC'
        );
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM books WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Search by title/author (case-insensitive). */
    public static function search(string $q): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM books
             WHERE title LIKE ? OR author LIKE ?
             ORDER BY title ASC'
        );
        $like = '%' . $q . '%';
        $stmt->execute([$like, $like]);
        return $stmt->fetchAll();
    }

    public static function create(string $title, string $author, int $copies): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO books (title, author, total_copies, available_copies)
             VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([trim($title), trim($author) ?: 'Unknown', $copies, $copies]);
        return (int) Database::pdo()->lastInsertId();
    }

    public static function delete(int $id): void
    {
        $stmt = Database::pdo()->prepare('DELETE FROM books WHERE id = ?');
        $stmt->execute([$id]);
    }

    /** Books currently out on loan (available < total). */
    public static function stats(): array
    {
        $stmt = Database::pdo()->query(
            'SELECT
               COUNT(*)                         AS titles,
               COALESCE(SUM(total_copies),0)    AS total_copies,
               COALESCE(SUM(available_copies),0) AS available_copies
             FROM books'
        );
        return $stmt->fetch();
    }
}
