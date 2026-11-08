# Deploying Quietroom on InfinityFree

Quietroom is a plain PHP 8 + MySQL application. There is no Composer step, no build
tool and no Node runtime. You upload the files, point the installer at your database,
and you are done.

Total upload is about 500 KB.

---

## 1. Before you start

You need three things:

| What | Where to get it |
| --- | --- |
| An InfinityFree account | infinityfree.com. The free tier is enough |
| A MySQL database | InfinityFree control panel → **MySQL Databases** |
| A Gemini API key (optional) | aistudio.google.com/app/apikey |

> **The AI companion is optional.** Without a key, check-ins, the journal, the CBT
> toolkit, insights, the safety plan and all crisis support still work. Only the
> AI chat and AI reflections are switched off.

### Rotate the development key first

The key in `app/config.local.php` was used while building and testing this app and
must be treated as compromised. Before you deploy:

1. Open <https://aistudio.google.com/app/apikey> and create a **new** key.
2. Delete the old one.
3. Do **not** upload `app/config.local.php` (see step 3). The installer writes a
   fresh one on the server.

---

## 2. Create the database

In the InfinityFree control panel:

1. **MySQL Databases → Create a new database**.
2. Note down the four values it shows you:

   ```
   Host:     sql123.infinityfree.com   (the number varies)
   Database: if0_12345678
   Username: if0_12345678
   Password: (the one you set)
   ```

3. Open **phpMyAdmin** with those credentials and confirm you can connect.
   (The installer creates the tables itself. You do not need to import anything.)

> **The table prefix.** InfinityFree only lets your account touch tables whose
> names start with a fixed prefix, normally your database name and an
> underscore, so `users` becomes `if0_12345678_users`. The app writes plain
> table names and the database layer adds the prefix on the way to MySQL, so
> nothing has to be renamed by hand. The installer works the prefix out for
> itself (it creates a throw-away table to see which name your account is
> allowed to use) and stores it in `app/config.local.php`. You can also type
> it in yourself on the installer's **Table prefix** box.

---

## 3. Upload the files

InfinityFree gives you FTP access and a web-based file manager. Either works.

**Upload the contents of the project folder** into `htdocs` so that you end up with:

```
htdocs/
  index.php
  .htaccess
  manifest.webmanifest
  robots.txt
  app/        (with its own .htaccess)
  assets/
```

> **Do not upload `app/config.local.php`.** It holds your local machine's database
> password and the development API key. The installer creates the correct file on
> the server. It is already listed in `.gitignore` for this reason.
> If your FTP client uploaded it anyway, delete it from `htdocs/app/` now.

### FTP settings

| Field | Value |
| --- | --- |
| Host | `ftps.infinitescript.com` (or `ftp001.unix`) |
| Username | your InfinityFree account name |
| Password | your InfinityFree account password |
| Port | 21 (FTP) or 22 (SFTP) |
| Mode | Passive |

Make sure hidden files are uploaded. **`.htaccess` is required**. It provides the
clean URLs and blocks direct access to `/app`. Both `.htaccess` files must sit at
`htdocs/.htaccess` and `htdocs/app/.htaccess`.

---

## 4. Run the installer

1. In the InfinityFree control panel, set the **PHP version to 8.1 or newer**
   (Account Settings → PHP version). InfinityFree offers 8.2 and 8.3, both of
   which work.
2. Visit `https://your-domain.rf/install`
3. The installer checks the environment and then walks you through three steps:
   - **Database**: paste the host, port, name, user and password from step 2.
     Leave **Table prefix** empty and it will be worked out for you.
   - **Admin**: create your administrator account.
   - **AI key**: paste a Gemini key, or press *Skip, and set it up later in admin*.
4. When it says *Quietroom is installed and ready*, the installer has written
   `app/config.local.php` on the server with your database credentials and created
   all 11 tables.

If the environment check fails, it tells you exactly which extension is missing.
InfinityFree provides PHP 8, PDO MySQL, cURL, JSON and mbstring on all free accounts.

---

## 5. Lock it down

Do all four of these before you share the link:

1. **Delete `app/pages/install.php`** from the server. The installer refuses to run
   again once it is installed, but there is no reason to leave it exposed.
2. **Change the admin password** if you used the one from this project's
   development data.
3. **Set a security question** on your account (Settings → Security). It is the only
   way to recover a password, because InfinityFree blocks PHP's `mail()` function and
   this app never sends email.
4. **Check the Gemini key** in **Admin**. It is the key you want the live site to use,
   and it is stored in the database, not in a file.

---

## 6. Verify the live site

| Check | How |
| --- | --- |
| Clean URLs | `https://your-domain.rf/dashboard` loads (not `index.php?r=…`) |
| Sign in | Register a throwaway account, then sign in and out again |
| Assets load | No 404s in the browser network tab; the logo appears in the header |
| Check-in | Submit a check-in; the dashboard heat map gains a square |
| Journal | Write an entry, open it, use *Reflect with AI* |
| Toolkit | Open **Panic Mode** and press **Start**. The circle animates |
| Insights | The mood/anxiety/energy chart renders |
| AI chat | Ask *"I feel low"*. A reply streams in |
| Crisis page | `https://your-domain.rf/crisis` lists helplines for your country |
| Mobile | Open on a phone. A tab bar appears at the bottom |

---

## InfinityFree notes and limits

These are hosting constraints, not app bugs. Plan around them.

- **Daily hit limit.** The free plan allows roughly 50,000 requests per day, shared
  across every site on the account. Each page view is one hit; the app makes no
  third-party requests except the Gemini API call, which is made from the server and
  does not consume your hits.
- **`mail()` is disabled.** Quietroom does not send email at all. Password recovery
  uses a security question instead, so nothing breaks.
- **Forced HTTPS.** On a brand-new domain InfinityFree shows a one-time
  "Force HTTPS" notice. Click *Continue* once and the warning is stored in your
  browser. The app sets the `secure` flag on session cookies automatically when it
  sees HTTPS.
- **No cron jobs on the free tier.** The daily check-in reminder is shown in the app
  itself rather than by email, so it works without a scheduler.
- **No shell.** Everything is done through the web installer, phpMyAdmin and the
  file manager. The app never needs shell access.
- **PHP 8.1+ is required.** The code uses `never` return types, which arrived in
  PHP 8.1. On PHP 8.0 or 7.x you will get a fatal error. InfinityFree's PHP 8.2
  and 8.3 are both fine.
- **Table names carry a prefix.** See step 2. The installer detects it, stores it
  in `app/config.local.php` as `DB_PREFIX` and the database layer applies it to
  every statement.
- **Requests are cut off after about 30 seconds.** The AI calls are sized to fit
  inside that window, so a slow reply ends with a clear message rather than a
  half-written page. The companion's reply may be cut short. It says so.
- **An ad banner is injected into every page.** That is InfinityFree's free-tier
  rule, not something the app does. The layout keeps clear of it.
- **MySQL is MariaDB.** The schema uses only InnoDB, `utf8mb4` and standard DDL, all
  of which MariaDB supports. No JSON columns and no full-text indexes are used.
- **`RewriteBase`** in `.htaccess` is set to `/`. If you install the app in a
  subfolder rather than the domain root, change it to that folder's path.

---

## Moving to another host

The app is portable. On any Apache or LiteSpeed host with PHP 8.1+ and MySQL:

1. Upload the same files, **excluding** `app/config.local.php`.
2. Make `htdocs` (or your document root) writable for the duration of the install.
3. Visit `/install` and enter the new database details.
4. Delete `app/pages/install.php` when it finishes.

For Nginx, replace the `.htaccess` rewrite with:

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
location ~ ^/app/ { deny all; }
```

---

## Configuration reference

Everything is configured in `app/config.local.php` on the server (written by the
installer) or in the **Admin** screen, which stores values in the database and takes
precedence.

| Key | Purpose | Default |
| --- | --- | --- |
| `DB_HOST` / `DB_PORT` | MySQL host and port | `localhost` / `3306` |
| `DB_NAME` / `DB_USER` / `DB_PASS` | MySQL credentials | (none) |
| `DB_PREFIX` | Prefix every table name carries, e.g. `if0_12345678_` | empty (no prefix) |
| `GEMINI_API_KEY` | Enables the AI companion | empty (AI off) |
| `GEMINI_MODEL` | Primary model | `gemini-3.8-flash` |
| `GEMINI_FALLBACKS` | Tried in order when the primary fails | 3 more models |
| `GEMINI_TIMEOUT` | Per-request timeout in seconds | `25` |
| `SITE_URL` | Canonical URL for meta tags, e.g. `https://classifiedy.rf` | empty |
| `SUPPORT_EMAIL` / `ADMIN_EMAIL` | Shown in the footer and the admin area | placeholders |
| `MAINTENANCE` | `1` puts the site into maintenance mode | `0` |

### Maintenance mode

Set `'MAINTENANCE' => '1'` in `app/config.local.php` on the server and every page
returns a short "we are doing a little tidying up" message with HTTP 503. The
crisis page, the sign-in page and the installer stay reachable on purpose, so
anyone in distress can still get helplines while you work on the site.

### Where the API key lives

The key can sit in three places. The app uses the first one it finds:

1. The `GEMINI_API_KEY` environment variable (if your host lets you set one).
2. `app/config.local.php` on the server, written by the installer.
3. The **Admin** screen, which stores it in the `settings` table and takes
   precedence over the file.

Because option 3 wins, you can change or revoke the key at any time from Admin
without touching a file on the server.

---

## Running the tests

The suite lives in `tests/` and is CLI-only. It refuses to run over HTTP and
`tests/.htaccess` blocks the folder outright.

```bash
# from the project root, with a server running:
php -S 127.0.0.1:8088 -t . &

php tests/crisis_test.php     # 35 cases: crisis detection, both directions
php tests/smoke_test.php      # 35 checks: every route, auth, CSRF, export
php tests/recover_test.php    # 8 checks: password recovery end to end
php tests/prefix_test.php     # 27 checks: table prefix, shared-host support
php tests/rate_limit_test.php # 6 checks: the sign-up and sign-in limiter
```

`crisis_test.php` is the one to run after any change to `app/crisis.php`. It
asserts that everyday phrases ("I want to kill time", "I am going to kill it at
work") do **not** trigger a crisis banner, and that real distress language does.
It needs no database.

`prefix_test.php` covers the shared-host table prefix. It proves the prefix is
applied to the statements but not to string literals or to columns that merely
start with a table name, and, against the live database, that the rows land in
the prefixed table and not in an unprefixed one.

`rate_limit_test.php` covers the limiter that counts sign-up and sign-in
attempts. It needs no database either.

`smoke_test.php` and `recover_test.php` create real accounts, so point them at a
throwaway database, not a live one. Set `CLASSY_TEST_URL` to change the host.
They clear the rate limiter's counters first, so the suite stays repeatable.

---

## If something goes wrong on the server

| Symptom | Cause | Fix |
| --- | --- | --- |
| Every page shows *500 Internal Server Error* and the host log mentions `Options` | The host does not allow the `Options` directive in `.htaccess` | Delete the two `Options` lines from `htdocs/.htaccess`. Nothing else depends on them. |
| *500* and the log mentions `AddOutputFilterByType` | `mod_filter` is missing | The rule is already guarded, but delete the whole "Compress text responses" block. |
| *500* and the log mentions `php_flag` | PHP runs as CGI/FPM, not `mod_php` | Delete the "Do not advertise the PHP version" block. |
| *Database is not configured yet* | `app/config.local.php` is missing or unreadable | Re-run `/install`, or create the file by hand with the values in the error message. |
| *Table 'users' doesn't exist* | The table prefix was not detected | Open the installer again and fill in **Table prefix** with your database name and an underscore, e.g. `if0_12345678_`. |
| Clean URLs do not work, `?r=` links do | `mod_rewrite` is missing or `AllowOverride` excludes it | Ask the host to enable `mod_rewrite`. The app works with `?r=` links meanwhile. |
| The AI reply is cut off | The host stops requests after about 30 seconds | Nothing to do. The app sizes its calls to the limit and says when a reply was cut short. |
| No styling, no icons | `assets/` did not upload | Upload the whole `assets/` folder, including the sub-folders. |

---

## Security notes

- Every POST is CSRF-checked, every query uses bound parameters, and every output
  is escaped through the `e()` helper.
- Sessions are regenerated on sign-in and sign-out; the session cookie is
  `HttpOnly`, `SameSite=Lax`, and `Secure` over HTTPS.
- Passwords are hashed with `password_hash()` (bcrypt).
- Sign-in is rate limited per account and address, sign-up and password recovery
  per address. The limiter slides rather than locking, so nobody can lock a real
  person out of their own account.
- Data export omits `password_hash`, `recovery_hash`, `recovery_question`,
  `is_admin`, remember tokens and the last login IP.
- `/app` is denied at the web-server level, and the `.htaccess` blocks execution of
  any `.php` file under `app/` or `assets/`.
- Crisis detection is a safety net, not a diagnosis. The app never gives medical
  advice and always points people to human helplines.
- `app/config.php` and `app/config.local.php` refuse to run when requested
  directly over HTTP, so a host that ignores `.htaccess` still cannot serve the
  database password or the API key.
