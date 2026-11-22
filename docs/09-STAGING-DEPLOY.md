# 09 — Shared Staging Server
**Secure Document Management System — ITEC 85 (Information Security)**

> **In plain words:** this doc sets up one **online copy of our website** that all seven of us can open
> from anywhere, at the same address. Whenever code is merged into `main` on GitHub, GitHub copies the
> changed files to that site **by itself** — about 1–3 minutes later, everyone who refreshes their
> browser sees the newest version. We call that copy the **staging server** ("staging" = the dress
> rehearsal before the show). Anything anyone *does* on the site (uploads, approvals, downloads) is
> instant for everyone, because it is one shared database.

## Why we want this

- **One shared database, live** — everyone tests against the same data, from anywhere.
- **No laptop babysitting** — nobody's PC has to stay on to keep the site reachable.
- **Reviewed code only** — staging always shows what was merged into `main`, never unreviewed work.
- **Industry-shaped story** — professional teams work exactly this way: *dev → staging → production*.

| Environment | Where it runs | Who uses it | In our project |
|---|---|---|---|
| Development | Each member's laptop (`localhost`) | One person writing code | Local XAMPP + Git |
| **Staging** | Free hosting (this doc) | All 7 of us, seeing integrated work | InfinityFree + auto-deploy |
| Production | The institution's own server | End users | Concept only — demo stays local |

> 🎬 **Demo day stays local.** The final presentation runs from the presenter's laptop on `localhost`
> (see [00-START-HERE §2](00-START-HERE.md)). If the professor asks why the site is on free hosting,
> the prepared answer is:
> *"Development happens on localhost, merged work deploys automatically to a staging server so all seven
> of us test one shared instance, and the production design targets the institution's own intranet
> server — mirroring the dev → staging → production lifecycle used in industry."*

---

## 1. What gets deployed — and what never does

| Deployed to staging (automatic) | **Never** deployed (and why) |
|---|---|
| `public/` — all browser-facing pages | `app/config/config.php` — **holds passwords**; the server has its own copy (Step 4) |
| `app/` — libraries and bootstrap | `database/` — SQL files must never be downloadable; schema changes are imported by hand (§3) |
| `.htaccess` — the web-access guards | `storage/uploads/` — uploaded files are runtime data, never shipped |
| `public/assets/` — CSS/JS | `logs/` — runtime logs stay on the server |
| Root `.htaccess`, `storage/uploads/.htaccess` | `docs/`, `tests/`, `scripts/`, `.git*`, `.github/` |

The never-list lives in `.github/workflows/deploy-staging.yml` under `exclude:` — don't remove entries
from it without a good reason, especially `app/config/config.php`.

---

## 2. One-time setup (about an hour — do it together)

**You need:** one GitHub admin (Kim), one person to create the hosting account, and Dyna for the SQL
import. Nothing here costs money.

### Step 1 — Create the hosting account

1. Go to [infinityfree.com](https://www.infinityfree.com/) → **Register** (free, no credit card).
2. Create a hosting account and pick a **free subdomain**, for example `sdms-team.infinityfreeapp.com`.
3. In the Client Area, note the **FTP details** it shows for your account.

Write yours down here:

```
Staging URL     https://__________________.infinityfreeapp.com/public/
FTP host        ftpupload.net
FTP username    epiz____________
FTP password    (kept ONLY in GitHub Secrets — never in the chat, never in code)
```

### Step 2 — Create the MySQL database

Client Area → **MySQL Databases** → create a database. The panel shows four values — fill these in:

| Value | What it looks like | Yours |
|---|---|---|
| SQL host | `sqlXXX.infinityfree.com` | |
| Database name | `epiz_…_sdms` (add the suffix `_sdms` if it lets you) | |
| Database user | `epiz_…` | |
| Password | the one you set for this database | |

> ⚠️ The SQL host is **not** the same as the FTP host (`ftpupload.net`). Copy each from the panel.

### Step 3 — Import the database (Dyna)

Client Area → **phpMyAdmin** → select your database → **Import**, in this exact order:

1. `database/schema.sql` — creates all tables
2. `database/seed.sql` — roles, categories, the first accounts
3. `database/demo-data.sql` — realistic sample documents

> Why this order: the schema draws the empty tables, the seed fills in the basics, the demo data
> fills in the content. Out of order, the imports fail with "table does not exist" errors.
>
> **Select first, then import:** click your `epiz_…_sdms` database in phpMyAdmin's left sidebar
> *before* pressing Import — each file pours into whatever database is selected. The SQL files
> contain no `CREATE DATABASE` / `USE` lines on purpose: free hosting blocks those commands, so
> the same files work on localhost and staging alike.

### Step 4 — Upload the staging config (ONE file, by hand)

The automatic deploy **never touches** `app/config/config.php`, so the server needs its own copy once:

1. Client Area → **File Manager** → open `htdocs/app/config/` (create the folders if missing).
2. Create `config.php` there, filling in the four values from Step 2:

```php
<?php
/**
 * STAGING configuration — lives ONLY on the hosting server.
 * Never commit this file: it holds real credentials (CONTRIBUTING.md §4).
 */
return [
    'db' => [
        'host' => 'sqlXXX.infinityfree.com',   // SQL host from Step 2
        'port' => '3306',
        'name' => 'epiz_XXXXXX_sdms',          // database name from Step 2
        'user' => 'epiz_XXXXXX',               // database user from Step 2
        'pass' => 'YOUR-STRONG-PASSWORD',
    ],
    'base_url'              => '/public',      // staging web root is htdocs/, app is in public/
    'upload_max_mb'         => 10,
    'session_timeout'       => 1800,
    'session_secure_cookie' => false,          // set to true AFTER Step 6 confirms HTTPS works
];
```

### Step 5 — Give GitHub the keys (Secrets)

GitHub repo → **Settings → Secrets and variables → Actions → New repository secret** — add three:

| Secret name | Value |
|---|---|
| `FTP_SERVER` | `ftpupload.net` |
| `FTP_USERNAME` | your `epiz_…` username |
| `FTP_PASSWORD` | your FTP password |

> **In plain words:** a **secret** is a locked drawer GitHub keeps for the workflow. The workflow can
> *use* the values but nobody — not even teammates with repo access — can read them back. That is why
> passwords must go here and not into any file.

### Step 6 — Turn on HTTPS

Good news: free subdomains come with **SSL enabled automatically** — there is nothing to request
or install (InfinityFree made this the default in May 2025). The **SSL/TLS** menu inside the
control panel is only for custom domains, so ignore it.

1. Open `https://your-subdomain.infinityfreeapp.com` — the **non-www** address, because the `www.`
   version is NOT covered by the automatic certificate — and confirm it opens without a
   certificate warning. HTTPS is available but not forced, so always share the `https://` link
   with the team.
2. If the panel offers a **PHP version** selector, choose **8.1 or newer** to match local XAMPP.
3. **Only after HTTPS is confirmed** — edit the server's `htdocs/app/config/config.php` and set
   `'session_secure_cookie' => true`. If you flip it too early, logins will loop.

### Step 7 — First deploy

1. Make sure `.github/workflows/deploy-staging.yml` is on `main` (it is in the repo).
2. Either push anything to `main`, or go to the **Actions** tab → **Deploy staging** → **Run workflow**.
3. Watch the Actions tab: a green check after ~1–3 minutes means the files are live.
4. Open `https://your-site/public/`. If you still see the host's "coming soon" placeholder page,
   delete its placeholder file(s) (an `index*.html` in `htdocs/`) via File Manager and refresh.
5. Log in with `admin / Admin@123`, then **immediately change the admin password** — staging is
   reachable from the public internet, so seeded passwords must not survive (see §4).

---

## 3. How the team works after setup

```
code on your laptop → push your branch → PR reviewed (CONTRIBUTING.md)
      → merge into main → GitHub deploys automatically (1–3 min)
      → all seven of us refresh the staging URL and see the change
```

- **Staging shows reviewed code only.** If a merge breaks something, staging shows it first —
  that is its job. Test locally while coding; use staging to confirm the *integrated* result.
- **Database changes are not automatic.** A deploy copies *code*, never *SQL*. After Dyna merges a
  `schema.sql` change, Dyna imports the changed file on the staging phpMyAdmin (Step 3 order rules).
- **Who deploys?** Nobody, manually — merges do it. To force a redeploy without new code:
  Actions tab → Deploy staging → **Run workflow**.

---

## 4. Security rules for staging (SR-aligned)

1. **Credentials live in exactly two places:** GitHub Secrets and the server's own `config.php`.
   Never in code, chat, or screenshots (CONTRIBUTING.md §4).
2. **Demo/seed data only.** No real personal or academic documents on the public site — ever.
3. **Change the seeded passwords** (`admin`, `staff`, `viewer`) right after the first staging login.
4. **Keep HTTPS on** — `session_secure_cookie => true` in the server config after Step 6.
5. **Treat the hosting account password like a database password.** End of semester: back up what you
   need, then suspend/delete the hosting account in the Client Area so nothing stays online.

---

## 5. Troubleshooting

| Symptom | Likely cause / fix |
|---|---|
| Page says *"Setup needed: copy config.sample.php …"* | `htdocs/app/config/config.php` missing → redo Step 4 |
| *"Access denied for user …"* | Wrong DB values in the server config → re-copy from the panel; remember the SQL host ≠ FTP host |
| *""DROP DATABASE" statements are disabled"* or *"No database selected"* during import | The SQL files never create or select a database — click your `epiz_…_sdms` database in phpMyAdmin's left sidebar first, then Import (Step 3) |
| Deploy fails in the Actions tab (red ❌) | Open the log; usually a typo in a secret, or a temporary FTP rate limit → fix and re-run |
| Merged change not appearing | Deploy only runs on pushes to `main`; check the Actions tab ran; then hard-refresh (Ctrl+F5) |
| First visit shows a "checking your browser…" page | That is the host's bot check, not our app — pass it once per browser |
| Can't find SSL settings — only an "SSL/TLS" menu in the control panel | Normal: that menu is only for custom domains. Free subdomains already have SSL automatically — nothing to request or install (Step 6) |
| Logins loop after Step 6 | `session_secure_cookie` was set `true` before HTTPS worked → set it back to `false`, confirm HTTPS, then flip |
| Uploads fail on staging | Host PHP limits differ from XAMPP; stay within the 10 MB cap (NFR-4) or raise `upload_max_mb` |

---

## 6. Glossary

| Term | Plain meaning |
|---|---|
| Staging | A shared dress-rehearsal copy of the site — the bridge between "my laptop" and "real production" |
| Deploy | Copying the latest code to a server so it runs there |
| Workflow (GitHub Actions) | A recipe file that tells GitHub to do a job automatically (ours: deploy on merge) |
| Secret | A value GitHub stores locked away; usable by workflows, invisible to people |
| FTP | The classic way of copying files to a web server |
| phpMyAdmin | A web page for managing a MySQL database (we use it to import SQL) |
| Subdomain | A free web address under someone else's domain, e.g. `sdms-team.infinityfreeapp.com` |
| SSL / HTTPS | The padlock encryption for web traffic; staging must use it |
| `htdocs` | The folder a web server serves to browsers (InfinityFree's web root) |
| `.htaccess` | An Apache config file; ours blocks direct browser access to internal folders (SR-4) |
