NEKO BLOG - InfinityFree upload guide (KEEP THIS FILE ON YOUR PC, do NOT upload it)
================================================================================
You asked me not to put instructions on the visible site - so this file stays
OFF the website. Only files inside /htdocs go to InfinityFree.

WHAT YOU GOT
- 17 files, ~35KB total. Uses 0.06% of InfinityFree 30,000 inode limit
  (WordPress uses ~50%). 3 requests per page (vs ~30 for WP) so you get
  ~10x more visitors before the 50,000 hits/day cap.
- No MySQL needed. Posts/users/comments saved as JSON in /data.
  Works on PHP 8.4 (InfinityFree current), no composer, no external libs.
- PHP mail() is BLOCKED by InfinityFree (anti-spam). So contact form saves
  to Admin inbox + gives mailto:nekomeow@duck.com link. That is the only
  reliable free-plan way without paid SMTP.

FIX FOR "Upload failed: JSON.parse..." (File Manager web uploader bug)
1. That error = File Manager rejected a dotfile (.htaccess), not your code.
   Web Upload files CANNOT upload .htaccess - it returns HTML not JSON.
2. USE THIS ORDER:
   a) Drag/upload everything EXCEPT the 2 .htaccess files first.
      Easiest: extract neko-blog-WEB-UPLOAD-no-htaccess.zip on PC, drag those.
      Or drag htdocs/ contents but SKIP .htaccess + data/.htaccess.
      Upload 5-6 files at a time, not all 19 at once.
   b) Then create .htaccess manually (30 sec):
      File Manager > htdocs/ > New File > ".htaccess" > paste SECTION A from htaccess-manual.txt
      File Manager > htdocs/data/ > New File > ".htaccess" > paste "Require all denied"
   c) BEST (no errors ever): use FTP FileZilla with host/user/pass from
      Control Panel > FTP Details, port 21, then drag full htdocs/ incl dotfiles.
3. I reset data/*.json to [] (clean). Server will auto-create admin + welcome
   post on first visit. Your old local test user + remember token is gone
   (it would have leaked a live session if uploaded).
4. If still fails: re-login to panel (session expired), disable adblock,
   try incognito, or try different browser.

UPLOAD (account + domain already done - 2 min)

LOGIN (admin - write in browser, no code)
- URL: /admin.php or /login.php
- Username: neko-admin-hi
- Password: this-is-my-account-to-write-no-hacking@nekomeow.duck
- Tick "Stay logged in" -> stays 30 days on that browser (cookie + session).
- After login: Admin -> New post -> Publish. Edit/Delete there too.
- IMPORTANT: Admin -> Change password after first login if you want,
  then you can delete ADMIN_PASS line in includes/config.php (site keeps
  working, password stays hashed in data/users.json).

READERS
- Register: /register.php (username + email + password 8+)
- Login: /login.php, comment + reply on any post (/post.php?slug=...)
- Guests can comment too (just name). Replies are threaded.
- Contact: /contact.php -> saves to your Admin inbox + mailto link.
- Dark/light: moon button top-right, auto follows system, saved per browser.

DISCLAIMER (already in footer of every page, as you asked)
"I am not a student. Everything here is personal opinion / experience for
information only - not professional advice. I take no liability..."

VERIFY ON YOUR PC (you have PHP 8.4.26)
  cd website-test-101/htdocs
  php -l index.php && php -l post.php && php -l admin.php && php -l includes/functions.php
  php -S localhost:8000
  open http://localhost:8000

OPTIMIZATIONS INCLUDED (for InfinityFree free plan)
- All PHP files <8KB (limit 1MB), .htaccess 787B (limit 10KB)
- Gzip + 1-year CSS/JS cache in .htaccess (saves hits)
- System fonts only, no Google Fonts/CDN (each external file = 1 hit)
- No MySQL queries (avoids 50MB DB cap + CPU suspensions)
- Atomic JSON writes + 30s comment rate limit + CSRF + honeypot + password_hash
- data/.htaccess denies web access to JSON

DO NOT UPLOAD to htdocs/: README-UPLOAD.txt, *.zip, htaccess-manual.txt
