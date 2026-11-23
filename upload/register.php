// SPDX-License-Identifier: GPL-3.0-or-later
// Copyright (C) 2026 Vaibhav Agarwal

<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';
if (current_user()) { header('Location: index.php'); exit; }
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) $err = 'Security check failed.';
    elseif (!empty($_POST['website'])) $err = 'Spam blocked.';
    else {
        $un = trim((string)($_POST['username'] ?? ''));
        $em = trim((string)($_POST['email'] ?? ''));
        $p1 = (string)($_POST['password'] ?? '');
        if (!preg_match('/^[a-zA-Z0-9_.-]{3,30}$/', $un)) $err = 'Username: 3-30 chars, letters/numbers/._-';
        elseif (!filter_var($em, FILTER_VALIDATE_EMAIL)) $err = 'Invalid email.';
        elseif (strlen($p1) < 8) $err = 'Password must be at least 8 characters.';
        elseif (find_user($un) || find_user($em)) $err = 'Username or email already taken.';
        else {
            $users = get_users();
            $nu = ['id'=>uid('u_'),'username'=>$un,'email'=>$em,'pass'=>password_hash($p1, PASSWORD_DEFAULT),'role'=>'user','created'=>now_iso(),'remember'=>null];
            $users[] = $nu; save_users($users);
            do_login($nu, true);
            header('Location: index.php'); exit;
        }
    }
}
$page_title = 'Register';
include __DIR__ . '/includes/header.php';
?>
<div class="card">
<h1>Register</h1>
<p class="small">Register with your email to comment faster. Only I (admin) can write posts.</p>
<?php if ($err): ?><div class="alert err"><?= e($err) ?></div><?php endif; ?>
<form method="post" action="register.php">
  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
  <label for="u">Username</label><input id="u" name="username" maxlength="30" required autocomplete="username">
  <label for="em">Email</label><input id="em" name="email" type="email" maxlength="120" required autocomplete="email">
  <label for="p">Password (min 8)</label><input id="p" name="password" type="password" required autocomplete="new-password">
  <input type="text" name="website" style="display:none" tabindex="-1" autocomplete="off">
  <p><button class="btn" type="submit">Create account</button></p>
</form>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
