// SPDX-License-Identifier: GPL-3.0-or-later
// Copyright (C) 2026 Vaibhav Agarwal

<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';
$slug = (string)($_GET['slug'] ?? '');
$post = get_post($slug);
if (!$post) { http_response_code(404); $page_title='Not found'; include __DIR__.'/includes/header.php'; echo '<div class="card"><h1>Post not found</h1><p><a href="index.php">← Home</a></p></div>'; include __DIR__.'/includes/footer.php'; exit; }

$user = current_user();
$msg = '';
// handle new comment / reply
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    if (!csrf_check()) $msg = 'Security check failed. Try again.';
    elseif (!empty($_POST['website'])) $msg = 'Spam blocked.';
    elseif (!rate_ok('c')) $msg = 'Please wait 30 seconds between comments.';
    else {
        $name = trim((string)($_POST['name'] ?? ($user['username'] ?? '')));
        $text = trim((string)($_POST['text'] ?? ''));
        $parent = trim((string)($_POST['parent_id'] ?? ''));
        if ($name === '' || $text === '') $msg = 'Name and comment are required.';
        elseif (mb_strlen($text) > 3000) $msg = 'Comment too long (max 3000).';
        else {
            $all = load_json('comments', []);
            $all[] = ['id'=>uid('c_'),'post_id'=>$post['id'],'parent_id'=>$parent ?: null,
                'author'=>mb_substr($name,0,60),'user_id'=>$user['id'] ?? null,
                'text'=>$text,'date'=>now_iso()];
            save_json('comments', $all);
            header('Location: post.php?slug='.urlencode($slug).'#comments'); exit;
        }
    }
}
$comments = get_comments($post['id']);
// index replies by parent
$byParent = [];
foreach ($comments as $c) $byParent[$c['parent_id'] ?? 'root'][] = $c;

$page_title = $post['title'];
$page_desc = $post['excerpt'] ?: mb_substr($post['content'], 0, 150);
include __DIR__ . '/includes/header.php';
?>
<article class="card">
  <h1><?= e($post['title']) ?></h1>
  <p class="meta"><?= e(substr($post['date'] ?? '', 0, 10)) ?><?php if (!empty($post['tags'])): ?> · tag: <?= e($post['tags']) ?><?php endif; ?></p>
  <div><?= format_text($post['content']) ?></div>
</article>
<p><a href="index.php">← All posts</a></p>

<h2 id="comments">Comments (<?= count($comments) ?>)</h2>
<?php if ($msg): ?><div class="alert err"><?= e($msg) ?></div><?php endif; ?>

<?php
function render_comments(array $list, array $byParent, string $slug, ?array $user): void {
    foreach ($list as $c) {
        echo '<div class="comment"><p><strong>'.e($c['author']).'</strong> <span class="small">'.e(substr($c['date']??'',0,16)).'</span></p>';
        echo '<p>'.format_text($c['text']).'</p>';
        echo '<form method="post" action="post.php?slug='.e($slug).'#comments">'
            .'<input type="hidden" name="csrf" value="'.e(csrf_token()).'">'
            .'<input type="hidden" name="parent_id" value="'.e($c['id']).'">';
        if (!$user) echo '<input name="name" placeholder="Your name" maxlength="60" required style="max-width:240px;margin-bottom:6px">';
        echo '<textarea name="text" rows="2" maxlength="3000" placeholder="Reply to '.e($c['author']).'…" required></textarea>'
            .'<p><button class="btn2" name="comment" type="submit">Reply</button></p></form>';
        $kids = $byParent[$c['id']] ?? [];
        if ($kids) { echo '<div class="reply">'; render_comments($kids, $byParent, $slug, $user); echo '</div>'; }
        echo '</div>';
    }
}
render_comments($byParent['root'] ?? [], $byParent, $slug, $user);
?>

<h3>Ask / leave a comment</h3>
<?php if (!$user): ?><p class="small">Tip: <a href="register.php">Register</a> or <a href="login.php">login</a> to auto-fill your name. Guests can comment too.</p><?php endif; ?>
<form method="post" action="post.php?slug=<?= e($slug) ?>#comments">
  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
  <input type="hidden" name="parent_id" value="">
  <?php if (!$user): ?>
    <label for="name">Your name</label>
    <input id="name" name="name" maxlength="60" required>
  <?php else: ?>
    <p>Posting as <strong><?= e($user['username']) ?></strong></p>
    <input type="hidden" name="name" value="<?= e($user['username']) ?>">
  <?php endif; ?>
  <label for="text">Question / comment</label>
  <textarea id="text" name="text" rows="4" maxlength="3000" required></textarea>
  <!-- honeypot -->
  <input type="text" name="website" style="display:none" tabindex="-1" autocomplete="off">
  <p><button class="btn" name="comment" type="submit">Post comment</button></p>
</form>
<?php include __DIR__ . '/includes/footer.php'; ?>
