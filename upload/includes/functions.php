// SPDX-License-Identifier: GPL-3.0-or-later
// Copyright (C) 2026 Vaibhav Agarwal

<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

session_start();

// ---------- tiny helpers (optimized: no dependencies) ----------
function e(?string $s): string {
    return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
}
function slugify(string $t): string {
    $t = strtolower(trim($t));
    $t = (string)preg_replace('/[^a-z0-9]+/', '-', $t);
    return trim($t, '-') ?: 'post';
}
function now_iso(): string { return gmdate('c'); }
function data_file(string $n): string { return DATA_DIR . '/' . $n . '.json'; }

function load_json(string $name, array $fallback = []): array {
    $f = data_file($name);
    if (!is_file($f)) return $fallback;
    $raw = @file_get_contents($f);
    if ($raw === false || $raw === '') return $fallback;
    $d = json_decode($raw, true);
    return is_array($d) ? $d : $fallback;
}
function save_json(string $name, array $data): bool {
    if (!is_dir(DATA_DIR)) @mkdir(DATA_DIR, 0755, true);
    $f = data_file($name);
    $tmp = $f . '.tmp';
    $ok = @file_put_contents($tmp, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    if ($ok === false) return false;
    return @rename($tmp, $f);
}
function uid(string $prefix = ''): string {
    return $prefix . bin2hex(random_bytes(8));
}

// ---------- CSRF ----------
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_check(): bool {
    return isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)$_POST['csrf']);
}

// ---------- users / auth ----------
function get_users(): array { return load_json('users', []); }
function save_users(array $u): bool { return save_json('users', $u); }
function find_user(string $login): ?array {
    $login = strtolower(trim($login));
    foreach (get_users() as $u) {
        if (strtolower($u['username']) === $login || strtolower($u['email']) === $login) return $u;
    }
    return null;
}
// Seed admin on first run (hashed, never displayed)
function ensure_admin(): void {
    $users = get_users();
    foreach ($users as $u) { if (($u['role'] ?? '') === 'admin') return; }
    $users[] = [
        'id' => uid('u_'),
        'username' => ADMIN_USER,
        'email' => CONTACT_EMAIL,
        'pass' => password_hash(ADMIN_PASS, PASSWORD_DEFAULT),
        'role' => 'admin',
        'created' => now_iso(),
        'remember' => null,
    ];
    save_users($users);
    if (!load_json('posts', [])) {
        save_json('posts', [[
            'id' => uid('p_'),
            'slug' => 'welcome-to-my-blog',
            'title' => 'Welcome to my blog!',
            'tags' => 'welcome, intro',
            'excerpt' => 'This is my first post. I write about anything I like here.',
            'content' => "Hello and welcome!\n\nThis is my personal blog where I write about anything I like.\n\nYou can create an account to comment, and use the Contact page to email me.\n\n— Neko",
            'date' => now_iso(),
            'updated' => now_iso(),
        ]]);
    }
    if (!is_file(data_file('comments'))) save_json('comments', []);
    if (!is_file(data_file('messages'))) save_json('messages', []);
}
ensure_admin();

function current_user(): ?array {
    if (!empty($_SESSION['uid'])) {
        foreach (get_users() as $u) if ($u['id'] === $_SESSION['uid']) return $u;
    }
    // remember-me (30 days)
    if (!empty($_COOKIE['remember'])) {
        $tok = (string)$_COOKIE['remember'];
        foreach (get_users() as $u) {
            if (!empty($u['remember']) && hash_equals((string)$u['remember'], $tok)) {
                $_SESSION['uid'] = $u['id'];
                session_regenerate_id(true);
                return $u;
            }
        }
    }
    return null;
}
function is_admin(?array $u = null): bool {
    $u = $u ?? current_user();
    return $u !== null && ($u['role'] ?? '') === 'admin';
}
function do_login(array $user, bool $remember): void {
    session_regenerate_id(true);
    $_SESSION['uid'] = $user['id'];
    if ($remember) {
        $tok = bin2hex(random_bytes(32));
        $users = get_users();
        foreach ($users as &$u) if ($u['id'] === $user['id']) $u['remember'] = $tok;
        save_users($users);
        setcookie('remember', $tok, time() + REMEMBER_DAYS * 86400, '/', '', false, true);
    }
}
function do_logout(): void {
    $u = current_user();
    if ($u) {
        $users = get_users();
        foreach ($users as &$x) if ($x['id'] === $u['id']) $x['remember'] = null;
        save_users($users);
    }
    $_SESSION = [];
    setcookie('remember', '', time() - 3600, '/');
    if (session_status() === PHP_SESSION_ACTIVE) session_destroy();
    session_start();
    session_regenerate_id(true);
}

// ---------- posts ----------
function get_posts(): array {
    $p = load_json('posts', []);
    usort($p, fn($a, $b) => strcmp($b['date'] ?? '', $a['date'] ?? ''));
    return $p;
}
function get_post(string $slug): ?array {
    foreach (get_posts() as $p) if ($p['slug'] === $slug) return $p;
    return null;
}

// ---------- comments (threaded via parent_id) ----------
function get_comments(string $post_id): array {
    $all = array_values(array_filter(load_json('comments', []), fn($c) => ($c['post_id'] ?? '') === $post_id));
    usort($all, fn($a, $b) => strcmp($a['date'] ?? '', $b['date'] ?? ''));
    return $all;
}
function comment_count(string $post_id): int { return count(get_comments($post_id)); }

// Safe text formatting: escape HTML, keep line breaks, auto-links
function format_text(string $t): string {
    $t = htmlspecialchars($t, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $t = preg_replace('#(https?://[^\s<]+)#', '<a href="$1" rel="nofollow noopener" target="_blank">$1</a>', $t);
    return nl2br($t, false);
}

// Simple rate limit (file based, 30s per IP for comments/messages)
function rate_ok(string $key, int $secs = 30): bool {
    $k = 'rl_' . $key . '_' . md5($_SERVER['REMOTE_ADDR'] ?? 'x');
    $t = $_SESSION[$k] ?? 0;
    if (time() - $t < $secs) return false;
    $_SESSION[$k] = time();
    return true;
}
