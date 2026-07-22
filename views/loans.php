<?php
// Books and members are needed for the borrow dropdowns.
$allBooks   = Book::all();
$allMembers = Member::all();
?>
<h1>Loans</h1>

<div class="loan-actions">
  <div class="card">
    <h2>Borrow a book</h2>
    <form method="post" action="/loans/borrow">
      <label>Book
        <select name="book_id" required>
          <option value="">— choose —</option>
          <?php foreach ($allBooks as $b): ?>
            <option value="<?= (int) $b['id'] ?>" <?= (int) $b['available_copies'] <= 0 ? 'disabled' : '' ?>>
              <?= e($b['title']) ?> (<?= (int) $b['available_copies'] ?> avail)
            </option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Member
        <select name="member_id" required>
          <option value="">— choose —</option>
          <?php foreach ($allMembers as $m): ?>
            <option value="<?= (int) $m['id'] ?>"><?= e($m['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <button type="submit">Issue book</button>
    </form>
  </div>

  <div class="card">
    <h2>Return a book</h2>
    <form method="post" action="/loans/return">
      <label>Active loan
        <select name="book_id" required>
          <option value="">— choose —</option>
          <?php foreach ($active as $l): ?>
            <option value="<?= (int) $l['book_id'] ?>">
              <?= e($l['title']) ?> — <?= e($l['member_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>
      <button type="submit">Return book</button>
    </form>
  </div>
</div>

<h2>Currently out</h2>
<table class="grid">
  <thead>
    <tr><th>Book</th><th>Borrower</th><th>Borrowed</th><th>Due</th></tr>
  </thead>
  <tbody>
    <?php foreach ($active as $l): ?>
      <?php $overdue = strtotime((string) $l['due_at']) < strtotime('today'); ?>
      <tr>
        <td><?= e($l['title']) ?></td>
        <td><?= e($l['member_name']) ?></td>
        <td><?= e(substr((string) $l['borrowed_at'], 0, 10)) ?></td>
        <td>
          <span class="badge <?= $overdue ? 'badge-out' : 'badge-ok' ?>">
            <?= e((string) $l['due_at']) ?><?= $overdue ? ' · overdue' : '' ?>
          </span>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$active): ?>
      <tr><td colspan="4" class="empty">Nothing checked out right now.</td></tr>
    <?php endif; ?>
  </tbody>
</table>

<h2>History</h2>
<table class="grid">
  <thead>
    <tr><th>Book</th><th>Borrower</th><th>Borrowed</th><th>Returned</th></tr>
  </thead>
  <tbody>
    <?php foreach ($history as $l): ?>
      <tr>
        <td><?= e($l['title']) ?></td>
        <td><?= e($l['member_name']) ?></td>
        <td><?= e(substr((string) $l['borrowed_at'], 0, 10)) ?></td>
        <td><?= $l['returned_at'] ? e(substr((string) $l['returned_at'], 0, 10)) : '<em>out</em>' ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$history): ?>
      <tr><td colspan="4" class="empty">No loans yet.</td></tr>
    <?php endif; ?>
  </tbody>
</table>
