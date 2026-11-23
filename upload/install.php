// SPDX-License-Identifier: GPL-3.0-or-later
// Copyright (C) 2026 Vaibhav Agarwal

<?php
// Run once after folder upload: yourdomain.com/install.php
// Creates the 2 .htaccess files (web upload blocks dotfiles), then DELETE this file.
declare(strict_types=1);
$ok = [];
$fail = [];
if (is_file(__DIR__ . '/htaccess-root.txt')) {
    if (@copy(__DIR__ . '/htaccess-root.txt', __DIR__ . '/.htaccess')) $ok[] = 'htdocs/.htaccess created';
    else $fail[] = 'could not create htdocs/.htaccess';
}
if (is_file(__DIR__ . '/data-htaccess.txt')) {
    if (@copy(__DIR__ . '/data-htaccess.txt', __DIR__ . '/data/.htaccess')) $ok[] = 'data/.htaccess created';
    else $fail[] = 'could not create data/.htaccess';
}
@chmod(__DIR__ . '/data', 0755);
foreach (['users','posts','comments','messages'] as $n) {
    $f = __DIR__ . '/data/' . $n . '.json';
    if (!is_file($f)) @file_put_contents($f, '[]');
}
?><!DOCTYPE html><html><body style="font-family:sans-serif;max-width:600px;margin:40px auto;padding:0 16px">
<h1>Install done</h1>
<?php foreach ($ok as $m): ?><p>OK: <?= htmlspecialchars($m) ?></p><?php endforeach; ?>
<?php foreach ($fail as $m): ?><p>FAIL: <?= htmlspecialchars($m) ?></p><?php endforeach; ?>
<p>1. Delete <b>install.php</b>, <b>htaccess-root.txt</b>, <b>data-htaccess.txt</b> from File Manager now.</p>
<p>2. Open <a href="index.php">Home</a> then <a href="admin.php">admin.php</a> and login as neko-admin-hi.</p>
</body></html>
