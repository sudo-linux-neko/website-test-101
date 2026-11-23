// SPDX-License-Identifier: GPL-3.0-or-later
// Copyright (C) 2026 Vaibhav Agarwal

<?php
declare(strict_types=1);
require_once __DIR__ . '/functions.php';
$user = current_user();
$admin = is_admin($user);
$page_title = ($page_title ?? SITE_NAME) . ' — ' . SITE_NAME;
?><!DOCTYPE html>
<html lang="en" data-theme="auto">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($page_title) ?></title>
<meta name="description" content="<?= e($page_desc ?? SITE_TAGLINE) ?>">
<link rel="stylesheet" href="assets/style.css">
<script src="assets/app.js" defer></script>
</head>
<body>
<a class="skip" href="#main">Skip to content</a>
<header class="top">
  <div class="wrap top-in">
    <a class="brand" href="index.php"><?= e(SITE_NAME) ?></a>
    <nav class="nav" aria-label="Main">
      <a href="index.php">Home</a>
      <a href="contact.php">Contact</a>
      <?php if ($user): ?>
        <?php if ($admin): ?><a href="admin.php">Write</a><?php endif; ?>
        <a href="logout.php">Logout (<?= e($user['username']) ?>)</a>
      <?php else: ?>
        <a href="login.php">Login</a>
        <a href="register.php">Register</a>
      <?php endif; ?>
      <button id="themeBtn" class="theme" type="button" aria-label="Toggle dark / light mode">◐</button>
    </nav>
  </div>
</header>
<main id="main" class="wrap">
