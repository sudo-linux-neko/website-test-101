// SPDX-License-Identifier: GPL-3.0-or-later
// Copyright (C) 2026 Vaibhav Agarwal

<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';
if (current_user()) { header('Location: index.php'); exit; }
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) $err = 'Security check failed.';
    else {
        $login = trim((string)($_POST['login'] ?? ''));
        $pass = (string)($_POST['password'] ?? '');
        $remember = isset($_POST['remember']);
        $u = find_user($login);
        if (!$u || !password_verify($pass, $u['pass'])) $err = 'Wrong login or password.';
        else { do_login($u, $remember || ($u['role'] === 'admin')); header('Location: ' . (is_admin($u) ? 'admin.php' : 'index.php')); exit; }
    }
}
$page_title = 'Login';
include __DIR__ . '/includes/header.php';
?>
<div class="card">
<h1>Login</h1>
<?php if ($err): ?><div class="alert err"><?= e($err) ?></div><?php endif; ?>
<form method="post" action="login.php">
  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
  <label for="login">Username or email</label><input id="login" name="login" required autocomplete="username">
  <label for="pw">Password</label><input id="pw" name="password" type="password" required autocomplete="current-password">
  <p><label style="font-weight:normal"><input type="checkbox" name="remember" checked style="width:auto"> Stay logged in on this browser (30 days)</label></p>
  <p><button class="btn" type="submit">Login</button> <a class="btn2" href="register.php">Register</a></p>
</form>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
