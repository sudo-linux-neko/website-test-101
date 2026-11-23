// SPDX-License-Identifier: GPL-3.0-or-later
// Copyright (C) 2026 Vaibhav Agarwal

<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';
header('Content-Type: application/xml; charset=utf-8');
$host = (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'example.com');
$base = 'https://' . $host;
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<url><loc><?= e($base) ?>/index.php</loc></url>
<url><loc><?= e($base) ?>/contact.php</loc></url>
<?php foreach (get_posts() as $p): ?>
<url><loc><?= e($base) ?>/post.php?slug=<?= e($p['slug']) ?></loc><lastmod><?= e(substr($p['updated'] ?? $p['date'] ?? '', 0, 10)) ?></lastmod></url>
<?php endforeach; ?>
</urlset>
