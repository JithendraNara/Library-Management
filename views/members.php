<h1>Members</h1>

<table class="grid">
  <thead>
    <tr><th>Name</th><th>Email</th><th>Joined</th><th></th></tr>
  </thead>
  <tbody>
    <?php foreach ($members as $m): ?>
      <tr>
        <td><?= e($m['name']) ?></td>
        <td><?= e($m['email'] ?? '—') ?></td>
        <td><?= e(substr((string) $m['created_at'], 0, 10)) ?></td>
        <td class="row-actions">
          <form method="post" action="/members/delete" onsubmit="return confirm('Remove this member?')">
            <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
            <button type="submit" class="btn-danger">Remove</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$members): ?>
      <tr><td colspan="4" class="empty">No members yet.</td></tr>
    <?php endif; ?>
  </tbody>
</table>

<h2>Add a member</h2>
<form class="card-form" method="post" action="/members/create">
  <label>Name <input type="text" name="name" required></label>
  <label>Email <input type="email" name="email" placeholder="optional"></label>
  <button type="submit">Add member</button>
</form>
