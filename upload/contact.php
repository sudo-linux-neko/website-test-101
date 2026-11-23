// SPDX-License-Identifier: GPL-3.0-or-later
// Copyright (C) 2026 Vaibhav Agarwal

<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';
$ok = ''; $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) $err = 'Security check failed.';
    elseif (!empty($_POST['website'])) $ok = 'Thanks! I will get back to you soon.';
    elseif (!rate_ok('m', 60)) $err = 'Please wait a minute before sending again.';
    else {
        $n = trim((string)($_POST['name'] ?? ''));
        $em = trim((string)($_POST['email'] ?? ''));
        $m = trim((string)($_POST['message'] ?? ''));
        if ($n === '' || $m === '' || !filter_var($em, FILTER_VALIDATE_EMAIL)) $err = 'Please fill name, valid email and message.';
        elseif (mb_strlen($m) > 5000) $err = 'Message too long.';
        else {
            $all = load_json('messages', []);
            $all[] = ['id'=>uid('m_'),'name'=>mb_substr($n,0,80),'email'=>mb_substr($em,0,120),'message'=>$m,'date'=>now_iso()];
            save_json('messages', $all);
            // NOTE: InfinityFree blocks PHP mail(). Message is saved for admin + mailto fallback below.
            $ok = 'Saved! You can also email me directly at ' . CONTACT_EMAIL;
        }
    }
}
$page_title = 'Contact'; $page_desc = 'Contact ' . SITE_NAME;
include __DIR__ . '/includes/header.php';
?>
<div class="card">
<h1>Contact me</h1>
<p>Fastest: <a href="mailto:<?= e(CONTACT_EMAIL) ?>?subject=<?= rawurlencode('Hello from your blog') ?>"><?= e(CONTACT_EMAIL) ?></a></p>
<p class="small">InfinityFree free plan blocks automatic PHP email sending, so this form saves your message to my private inbox on the site and I reply from my email. Or click the email link above — it opens your mail app.</p>
<?php if ($ok): ?><div class="alert ok"><?= e($ok) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert err"><?= e($err) ?></div><?php endif; ?>
<form method="post" action="contact.php">
  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
  <label for="name">Your name</label><input id="name" name="name" maxlength="80" required>
  <label for="email">Your email</label><input id="email" name="email" type="email" maxlength="120" required>
  <label for="message">Message</label><textarea id="message" name="message" rows="5" maxlength="5000" required></textarea>
  <input type="text" name="website" style="display:none" tabindex="-1" autocomplete="off">
  <p><button class="btn" type="submit">Send message</button></p>
</form>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
