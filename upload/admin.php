// SPDX-License-Identifier: GPL-3.0-or-later
// Copyright (C) 2026 Vaibhav Agarwal

<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';
$user = current_user();
if (!is_admin($user)) { header('Location: login.php'); exit; }
$msg = ''; $err = '';
$posts = get_posts();

// --- new / edit post ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
    $act = (string)($_POST['act'] ?? '');
    if ($act === 'save') {
        $id = trim((string)($_POST['id'] ?? ''));
        $title = trim((string)($_POST['title'] ?? ''));
        $tags = trim((string)($_POST['tags'] ?? ''));
        $content = trim((string)($_POST['content'] ?? ''));
        if ($title === '' || $content === '') $err = 'Title and content required.';
        else {
            $all = load_json('posts', []);
            $slug = slugify($title);
            // keep slug unique
            $base = $slug; $i = 2;
            $slugs = array_column($all, 'slug');
            if ($id !== '') { $slugs = array_filter($slugs, fn($s) => get_post($s)['id'] !== $id); }
            while (in_array($slug, $slugs, true)) $slug = $base . '-' . ($i++);
            $excerpt = mb_substr(trim(preg_replace('/\s+/', ' ', strip_tags($content))), 0, 160);
            if ($id === '') {
                $all[] = ['id'=>uid('p_'),'slug'=>$slug,'title'=>mb_substr($title,0,150),'tags'=>mb_substr($tags,0,150),
                    'excerpt'=>$excerpt,'content'=>$content,'date'=>now_iso(),'updated'=>now_iso()];
                $msg = 'Post published!';
            } else {
                foreach ($all as &$p) if ($p['id'] === $id) {
                    $p['title']=mb_substr($title,0,150); $p['slug']=$slug; $p['tags']=mb_substr($tags,0,150);
                    $p['content']=$content; $p['excerpt']=$excerpt; $p['updated']=now_iso();
                }
                $msg = 'Post updated!';
            }
            save_json('posts', $all); $posts = get_posts();
        }
    } elseif ($act === 'del_post') {
        $id = (string)$_POST['id'];
        save_json('posts', array_values(array_filter(load_json('posts', []), fn($p) => $p['id'] !== $id)));
        save_json('comments', array_values(array_filter(load_json('comments', []), fn($c) => $c['post_id'] !== $id)));
        $msg = 'Post deleted.'; $posts = get_posts();
    } elseif ($act === 'del_comment') {
        $id = (string)$_POST['id'];
        save_json('comments', array_values(array_filter(load_json('comments', []), fn($c) => $c['id'] !== $id && ($c['parent_id'] ?? null) !== $id)));
        $msg = 'Comment deleted.';
    } elseif ($act === 'del_msg') {
        $id = (string)$_POST['id'];
        save_json('messages', array_values(array_filter(load_json('messages', []), fn($m) => $m['id'] !== $id)));
        $msg = 'Message deleted.';
    } elseif ($act === 'pw') {
        $np = (string)($_POST['newpass'] ?? '');
        if (strlen($np) < 10) $err = 'New password min 10 chars.';
        else {
            $us = get_users();
            foreach ($us as &$u) if ($u['id'] === $user['id']) $u['pass'] = password_hash($np, PASSWORD_DEFAULT);
            save_users($us); $msg = 'Password changed. Remember to update your records.';
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') { $err = 'Security check failed.'; }

$edit = null;
if (isset($_GET['edit'])) { foreach ($posts as $p) if ($p['id'] === $_GET['edit']) $edit = $p; }
$messages = array_reverse(load_json('messages', []));
$allComments = array_reverse(load_json('comments', []));
$page_title = 'Write / Admin';
include __DIR__ . '/includes/header.php';
?>
<h1>Write & manage</h1>
<p class="meta">Logged in as <strong><?= e($user['username']) ?></strong> · stays logged in 30 days on this browser · <a href="logout.php">Logout</a></p>
<?php if ($msg): ?><div class="alert ok"><?= e($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert err"><?= e($err) ?></div><?php endif; ?>

<div class="card">
<h2><?= $edit ? 'Edit post' : 'New post (write here, no code needed)' ?></h2>
<form method="post" action="admin.php">
  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
  <input type="hidden" name="act" value="save">
  <input type="hidden" name="id" value="<?= e($edit['id'] ?? '') ?>">
  <label for="t">Title</label><input id="t" name="title" maxlength="150" required value="<?= e($edit['title'] ?? '') ?>">
  <label for="tg">Tags (comma separated, optional)</label><input id="tg" name="tags" maxlength="150" value="<?= e($edit['tags'] ?? '') ?>">
  <label for="c">Content (line breaks are kept, links auto-clickable)</label>
  <textarea id="c" name="content" rows="12" required><?= e($edit['content'] ?? '') ?></textarea>
  <p><button class="btn" type="submit"><?= $edit ? 'Save changes' : 'Publish post' ?></button>
  <?php if ($edit): ?> <a class="btn2" href="admin.php">Cancel</a><?php endif; ?></p>
</form>
</div>

<div class="card">
<h2>Posts (<?= count($posts) ?>)</h2>
<?php foreach ($posts as $p): ?>
<p><strong><a href="post.php?slug=<?= e($p['slug']) ?>"><?= e($p['title']) ?></a></strong><br>
<span class="small"><?= e(substr($p['date']??'',0,10)) ?> · <?= (int)comment_count($p['id']) ?> comments</span><br>
<a class="btn2" href="admin.php?edit=<?= e($p['id']) ?>">Edit</a>
<form method="post" action="admin.php" style="display:inline" onsubmit="return confirm('Delete this post?')">
<input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="act" value="del_post">
<input type="hidden" name="id" value="<?= e($p['id']) ?>"><button class="btn2" type="submit">Delete</button></form></p>
<?php endforeach; ?>
</div>

<div class="card">
<h2>Latest comments (<?= count($allComments) ?>)</h2>
<?php foreach (array_slice($allComments, 0, 30) as $c): ?>
<div class="comment"><p><strong><?= e($c['author']) ?></strong> <span class="small"><?= e($c['date']??'') ?></span></p>
<p><?= e(mb_substr($c['text'],0,300)) ?></p>
<form method="post" action="admin.php" onsubmit="return confirm('Delete?')">
<input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="act" value="del_comment">
<input type="hidden" name="id" value="<?= e($c['id']) ?>"><button class="btn2" type="submit">Delete</button></form></div>
<?php endforeach; ?>
<?php if (!$allComments): ?><p class="small">No comments yet.</p><?php endif; ?>
</div>

<div class="card">
<h2>Contact inbox (<?= count($messages) ?>)</h2>
<p class="small">Saved from contact form (InfinityFree blocks auto-email, so reply from <?= e(CONTACT_EMAIL) ?>).</p>
<?php foreach (array_slice($messages, 0, 30) as $m): ?>
<div class="comment"><p><strong><?= e($m['name']) ?></strong> &lt;<?= e($m['email']) ?>&gt; <span class="small"><?= e($m['date']??'') ?></span></p>
<p><?= format_text($m['message']) ?></p>
<p><a class="btn2" href="mailto:<?= e($m['email']) ?>?subject=<?= rawurlencode('Re: your message to '.SITE_NAME) ?>">Reply by email</a>
<form method="post" action="admin.php" style="display:inline"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
<input type="hidden" name="act" value="del_msg"><input type="hidden" name="id" value="<?= e($m['id']) ?>">
<button class="btn2" type="submit">Delete</button></form></p></div>
<?php endforeach; ?>
<?php if (!$messages): ?><p class="small">No messages yet.</p><?php endif; ?>
</div>

<div class="card">
<h2>Change password</h2>
<form method="post" action="admin.php">
<input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="act" value="pw">
<label for="np">New password (min 10)</label><input id="np" name="newpass" type="password" required>
<p><button class="btn2" type="submit">Change password</button></p>
</form>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
