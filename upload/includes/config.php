// SPDX-License-Identifier: GPL-3.0-or-later
// Copyright (C) 2026 Vaibhav Agarwal

<?php
// Site settings - edit these 3 lines only if needed.
declare(strict_types=1);
define('SITE_NAME', 'Neko Blog');
define('SITE_TAGLINE', 'Writing about anything I like');
define('CONTACT_EMAIL', 'nekomeow@duck.com');

// Admin seed (first run only). Password is hashed into data/users.json,
// it is NEVER shown on the visible site. After first login you can
// change it from Admin -> Change password, then delete these 2 lines.
define('ADMIN_USER', 'neko-admin-hi');
define('ADMIN_PASS', 'this-is-my-account-to-write-no-hacking@nekomeow.duck');

// Paths
define('BASE_DIR', dirname(__DIR__));
define('DATA_DIR', BASE_DIR . '/data');
define('REMEMBER_DAYS', 30);
date_default_timezone_set('UTC');
