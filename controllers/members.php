<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/Member.php';
require_once __DIR__ . '/helpers.php';

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

/** Refuse to delete when the member has active (unreturned) loans. */
function deleteGuardedMember(): void
{
    $id = (int)($_POST['id'] ?? 0);
    if (Member::activeLoanCount($id) > 0) {
        setFlash('error', 'Cannot delete: this member has books currently on loan. Return them first.');
        return;
    }
    Member::delete($id);
    setFlash('success', 'Member removed.');
}
