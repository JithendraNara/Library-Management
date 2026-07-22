<h1>Books</h1>

<form class="inline-form" method="get" action="/books">
  <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search title or author…">
  <button type="submit">Search</button>
  <?php if ($q !== ''): ?><a href="/books" class="btn-link">Clear</a><?php endif; ?>
</form>

<table class="grid">
  <thead>
    <tr><th>Title</th><th>Author</th><th>Available</th><th></th></tr>
  </thead>
  <tbody>
    <?php foreach ($books as $b): ?>
      <tr>
        <td><?= e($b['title']) ?></td>
        <td><?= e($b['author']) ?></td>
        <td>
          <?php $avail = (int) $b['available_copies']; ?>
          <span class="badge <?= $avail > 0 ? 'badge-ok' : 'badge-out' ?>">
            <?= $avail ?> / <?= (int) $b['total_copies'] ?>
          </span>
        </td>
        <td class="row-actions">
          <form method="post" action="/books/delete" onsubmit="return confirm('Delete this book?')">
            <?= csrfField() ?>
            <input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
            <button type="submit" class="btn-danger">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$books): ?>
      <tr><td colspan="4" class="empty">No books found.</td></tr>
    <?php endif; ?>
  </tbody>
</table>

<h2>Add a book</h2>
<form class="card-form" method="post" action="/books/create">
  <?= csrfField() ?>
  <label>Title <input type="text" name="title" required></label>
  <label>Author <input type="text" name="author" placeholder="Unknown"></label>
  <label>Copies <input type="number" name="copies" value="1" min="1"></label>
  <button type="submit">Add book</button>
</form>
