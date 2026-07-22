<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($title) ?> · Central Library</title>
  <link rel="stylesheet" href="/style.css">
</head>
<body>
  <header class="site-header">
    <div class="wrap">
      <a href="/" class="brand">📚 Central Library</a>
      <nav>
        <a href="/books">Books</a>
        <a href="/members">Members</a>
        <a href="/loans">Loans</a>
      </nav>
    </div>
  </header>

  <main class="wrap">
    <?php $flash = flash(); if ($flash): ?>
      <div class="flash flash-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>

    <?= $content ?>
  </main>

  <footer class="wrap site-footer">
    <p>Raw PHP + MySQL rebuild of the 2021 Tkinter CLI · no framework</p>
  </footer>
</body>
</html>
