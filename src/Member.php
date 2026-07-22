<?php
declare(strict_types=1);

require_once __DIR__ . '/Database.php';

/**
 * Member model — borrowers.
 */
final class Member
{
    public static function all(): array
    {
        $stmt = Database::pdo()->query('SELECT * FROM members ORDER BY name ASC');
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM members WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(string $name, string $email): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO members (name, email) VALUES (?, ?)'
        );
        $stmt->execute([trim($name), trim($email) ?: null]);
        return (int) Database::pdo()->lastInsertId();
    }

    public static function delete(int $id): void
    {
        $stmt = Database::pdo()->prepare('DELETE FROM members WHERE id = ?');
        $stmt->execute([$id]);
    }

    /** Count unreturned loans for a member (used to block destructive deletes). */
    public static function activeLoanCount(int $id): int
    {
        $stmt = Database::pdo()->prepare(
            'SELECT COUNT(*) FROM loans WHERE member_id = ? AND returned_at IS NULL'
        );
        $stmt->execute([$id]);
        return (int) $stmt->fetchColumn();
    }
}
