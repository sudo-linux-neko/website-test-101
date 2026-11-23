// SPDX-License-Identifier: GPL-3.0-or-later
// Copyright (C) 2026 Vaibhav Agarwal

<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';
$q = strtolower(trim((string)($_GET['q'] ?? '')));
$tag = strtolower(trim((string)($_GET['tag'] ?? '')));
$posts = get_posts();
if ($q !== '') {
    $posts = array_values(array_filter($posts, fn($p) =>
        str_contains(strtolower($p['title'] . ' ' . $p['content'] . ' ' . ($p['tags'] ?? '')), $q)));
}
if ($tag !== '') {
    $posts = array_values(array_filter($posts, fn($p) => str_contains(strtolower($p['tags'] ?? ''), $tag)));
}
$page_title = $q !== '' ? 'Search: ' . $q : 'Home';
$page_desc = SITE_TAGLINE;
include __DIR__ . '/includes/header.php';
?>
<h1><?= e(SITE_NAME) ?></h1>
<p class="meta"><?= e(SITE_TAGLINE) ?></p>

<form class="search" method="get" action="index.php" role="search">
  <input name="q" value="<?= e($q) ?>" placeholder="Search posts…" aria-label="Search posts">
  <button class="btn" type="submit">Search</button>
</form>

<?php if (!$posts): ?>
  <div class="card"><p>No posts found.</p></div>
<?php endif; ?>
<?php foreach ($posts as $p): ?>
  <article class="card">
    <h2><a href="post.php?slug=<?= e($p['slug']) ?>"><?= e($p['title']) ?></a></h2>
    <p class="meta"><?= e(substr($p['date'] ?? '', 0, 10)) ?> · <?= (int)comment_count($p['id']) ?> comments<?php if (!empty($p['tags'])): ?> · <?= e($p['tags']) ?><?php endif; ?></p>
    <p><?= e($p['excerpt'] ?: mb_substr(strip_tags($p['content']), 0, 160)) ?></p>
    <a class="btn2" href="post.php?slug=<?= e($p['slug']) ?>">Read + comment</a>
  </article>
<?php endforeach; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
