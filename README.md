# Quietroom

A secure, accessible, responsive, dependency-free PHP/MySQL mental wellness support application designed for shared hosting (InfinityFree, Hostinger, OVH, etc.). No Composer, no build step, no Node runtime — just plain PHP 8.1+ and MySQL.

## What it is

Quietroom is a self-help tool, not a medical service. It offers:

- **Daily check-ins** with mood, how you feel, energy, and sleep tracking
- **Private journal** with optional AI reflections
- **CBT-style thought records** and a safety-plan builder
- **AI companion chat** (optional, requires a Gemini API key; falls back to an offline helper without one)
- **Crisis helplines** for countries worldwide, always reachable
- **Settings**: appearance theme, daily check-in reminder, security-question password recovery, data export, account deletion
- **Admin panel**: AI configuration, user management, server info, AI usage stats, housekeeping
- **Profile picture uploads** (avatar) with server-side validation and resizing

## Tech stack

- PHP 8.1+ (no framework, no Composer)
- MySQL / MariaDB with InnoDB and utf8mb4
- PDO with bound parameters (every query)
- Native PHP sessions (CSRF protection, rate limiting, remember-me tokens)
- Vanilla JavaScript and inline SVG icons (no dependencies)
- GD for avatar resizing; `fileinfo` for MIME validation

## Project structure

```
index.php          # Only web-reachable PHP entry point (front controller)
.htaccess          # Clean URLs, blocks /app, blocks PHP execution under assets/
manifest.webmanifest
robots.txt
DEPLOY.md          # InfinityFree-specific deployment instructions
app/
  bootstrap.php    # App startup, config, migrations
  config.php       # Configuration (env > config.local.php > defaults)
  config.local.php # Written by the installer on the server (never upload this)
  db.php           # PDO layer with shared-host table prefix support
  auth.php         # Authentication, sessions, remember-me
  ai.php           # AI companion with stall guard and fallbacks
  crisis.php       # Crisis helpline data
  content.php      # Shared content (AI styles, etc.)
  helpers.php      # Template helpers, avatar upload, rate limiting
  icons.php        # Inline SVG sprite
  migrations.php   # Repeatable schema migrations
  pages/           # One PHP file per route (dashboard, settings, install, etc.)
  api/             # JSON endpoints (chat, mood, journal, export, etc.)
  sql/schema.sql   # Database schema
  views/header.php # Shared header
  views/footer.php # Shared footer
assets/
  css/app.css      # All styles
  img/logo.svg     # App logo
  js/app.js        # Core interactions
  js/*.js          # Page-specific scripts
  uploads/avatars/ # User-uploaded profile pictures (created on first upload)
tests/             # CLI test suite (blocked from the web)
```

## Getting started

### Local development

1. Make sure you have PHP 8.1+ with PDO MySQL, GD, fileinfo, and exif.
2. Create a MySQL database.
3. Start a local server from the project root:
   ```bash
   php -S 127.0.0.1:8088 -t .
   ```
4. Visit `http://127.0.0.1:8088/install` and follow the installer.
5. After install, delete `app/pages/install.php`.

### InfinityFree (shared hosting)

Full InfinityFree-specific instructions are in **DEPLOY.md**. Short version:

1. Set PHP version to 8.1+ in the InfinityFree control panel.
2. Create a MySQL database and note the host, name, user, and password.
3. Upload all files to `htdocs/` (including `.htaccess` files). **Do not upload `app/config.local.php`.**
4. Visit `https://your-domain.rf/install` and complete the setup.
5. Delete `app/pages/install.php` from the server.
6. Change the default admin password and set a security question.

## Security

- Every POST is CSRF-checked; every query uses bound parameters; every output is escaped.
- Sessions are `HttpOnly`, `SameSite=Lax`, and `Secure` over HTTPS.
- Passwords are hashed with `password_hash()` (bcrypt).
- Password recovery uses a security question (InfinityFree blocks `mail()`).
- `/app` is denied at the web-server level; PHP execution is blocked under `assets/` and `uploads/`.
- `config.php` and `config.local.php` refuse to run if requested directly.

## Testing

The CLI test suite lives in `tests/` and is blocked from the web:

```bash
php tests/smoke_test.php      # Routes, auth, CSRF, export
php tests/crisis_test.php     # Crisis detection across 32 countries
php tests/recover_test.php    # Password recovery end to end
php tests/rate_limit_test.php # Sign-up / sign-in rate limiting
php tests/prefix_test.php     # Shared-host table prefix
```

## Deployment notes

- Total upload is about 500 KB.
- The app makes no third-party requests except the optional Gemini API call (made from the server).
- InfinityFree's free tier allows roughly 50,000 hits/day and injects an ad banner into every page; neither affects app function.
- Requests are cut off after about 30 seconds; AI calls are sized to fit that window.

## License

This project is provided as-is for self-help use. It is not a substitute for professional medical or crisis care.
