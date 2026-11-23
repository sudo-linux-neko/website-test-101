// SPDX-License-Identifier: GPL-3.0-or-later
// Copyright (C) 2026 Vaibhav Agarwal

<?php
// Put THIS file as Home/htdocs/index.php (1 file only).
// It forwards neko.site.je/ -> neko.site.je/upload/ where your blog is now.
// Later when you move files to root, DELETE this file.
declare(strict_types=1);
header('Location: /upload/index.php', true, 302);
exit;
