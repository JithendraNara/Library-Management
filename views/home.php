<h1>Welcome to Central Library</h1>
<p class="lede">Browse the catalog, issue books to members, and track returns — all backed by a plain PHP + MySQL stack.</p>

<section class="stats">
  <div class="stat">
    <span class="stat-num"><?= (int) $stats['titles'] ?></span>
    <span class="stat-label">Titles</span>
  </div>
  <div class="stat">
    <span class="stat-num"><?= (int) $stats['total_copies'] ?></span>
    <span class="stat-label">Total copies</span>
  </div>
  <div class="stat">
    <span class="stat-num"><?= (int) $stats['available_copies'] ?></span>
    <span class="stat-label">Available now</span>
  </div>
</section>

<h2>Recently added</h2>
<table class="grid">
  <thead>
    <tr><th>Title</th><th>Author</th><th>Available</th></tr>
  </thead>
  <tbody>
    <?php foreach (array_slice($books, 0, 8) as $b): ?>
      <tr>
        <td><?= e($b['title']) ?></td>
        <td><?= e($b['author']) ?></td>
        <td><?= (int) $b['available_copies'] ?> / <?= (int) $b['total_copies'] ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$books): ?>
      <tr><td colspan="3" class="empty">No books yet. <a href="/books">Add one →</a></td></tr>
    <?php endif; ?>
  </tbody>
</table>
