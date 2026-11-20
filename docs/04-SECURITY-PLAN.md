# 04 · Security Plan

**Secure Document Management System — ITEC 85 (Information Security)**

This is the heart of the project: how the 10 security requirements of the manual (§E) are actually
implemented, what threats we considered, and how we prove the protections work. Requirement IDs SR-1…
SR-10 follow `docs/01-REQUIREMENTS.md`.

> **In plain words — what the 10 security rules actually mean:**
> 1. **Hash passwords** — never store real passwords, only unreadable scrambles of them.
> 2. **Authentication** — nobody sees any page without logging in first.
> 3. **RBAC** — admin, staff, and viewer each see different things; the wrong role gets blocked.
> 4. **Confidentiality** — uploaded files sit where no web address can reach them; every download goes
>    through a checkpoint that decides "yes" or "no".
> 5. **SQL injection prevention** — queries are built the safe way, so typed tricks like `' OR 1=1` do nothing.
> 6. **Input validation** — check everything the user sends; reject weird files and scripts.
> 7. **Session management** — the "logged in" memory is hardened against being stolen or faked.
> 8. **Activity logs** — every important action is written down permanently.
> 9. **Account management** — admins can switch an account off, and it stops working instantly.
> 10. **Data integrity** — records can't be quietly changed or truly lost; deletions are undoable.
>
> The rest of this page is *how* we do each rule in PHP, and §5 is the list of attacks we will try
> ourselves to prove the protections work (great material for the presentation).

---

## 1. Threat Model (STRIDE)

| Threat | Realistic attack on SDMS | Mitigation |
|--------|--------------------------|------------|
| **Spoofing** | Guessing passwords; reusing stolen session cookies | SR-1 hashing, SR-2 mandatory login, SR-7 session hardening, optional lockout |
| **Tampering** | SQL injection; malicious file upload; form submission from another site | SR-5 prepared statements, SR-6 validation + CSRF tokens, upload whitelist |
| **Repudiation** | "I never downloaded that file" | SR-8 append-only activity + access logs with IP and timestamp |
| **Information disclosure** | Opening a file by guessing its URL; directory listing; verbose DB errors; XSS stealing sessions | SR-4 gated downloads + files outside web root; generic error pages; output escaping |
| **Denial of service** | Uploading huge files to fill the disk | SR-6 size limits (php.ini + application check), file-type whitelist |
| **Elevation of privilege** | Viewer calling `admin/users.php` directly; staff approving their own document | SR-3 deny-by-default `require_role()` on every admin page; policy check on every action |

---

## 2. Requirement → Implementation Map

| ID | Requirement | Implemented in | Verified by |
|----|-------------|----------------|-------------|
| SR-1 | Password hashing | `lib/auth.php` (`password_hash`, `password_verify`) | ST-13 |
| SR-2 | Authentication | `app/bootstrap.php` + `require_login()` on every protected page | ST-04 |
| SR-3 | Role-based access control | `lib/rbac.php` + guards on every page | ST-02 |
| SR-4 | Data confidentiality | `storage/uploads/` outside web root + `documents/download.php` gate | ST-03, ST-05, ST-11 |
| SR-5 | SQL injection prevention | PDO prepared statements everywhere | ST-01 |
| SR-6 | Input validation | `lib/validate.php`, `e()` output escaping, `lib/csrf.php` | ST-07, ST-08, ST-12 |
| SR-7 | Session management | `bootstrap.php` cookie params, `session_regenerate_id`, 30-min timeout | ST-10 |
| SR-8 | Activity logs | `lib/logger.php` → `activity_logs`, `document_access_logs` | ST-14 |
| SR-9 | Account management | `admin/users.php` activate/deactivate | ST-09 |
| SR-10 | Data integrity | FKs, transactions, SHA-256, soft delete | ST-15 |

---

## 3. Implementation Playbook

### SR-1 · Password Hashing
```php
// create/update user (admin/users.php)
$hash = password_hash($password, PASSWORD_DEFAULT);

// login (lib/auth.php)
if (password_verify($inputPassword, $user['password_hash'])) { /* grant session */ }
```
- Never store, log, or transmit the plaintext password (except over the login POST).
- `PASSWORD_DEFAULT` follows PHP's current best (bcrypt; Argon2id when available).
- No MD5/SHA1 anywhere — those are for checksums of files, not passwords.

### SR-2 · Authentication
- `require_login()` is the first line of **every** protected page; it redirects to `login.php` otherwise.
- Login comparison happens against the DB row where `status = 'active'` — deactivated users fail here (SR-9).
- Generic failure message (*"Invalid username or password"*) — never reveal which part was wrong.

### SR-3 · Role-Based Access Control
```php
// lib/rbac.php — deny by default
function require_role(array $roles): void {
    if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], $roles, true)) {
        log_activity($_SESSION['user_id'] ?? null, 'ACCESS_DENIED', $_SERVER['SCRIPT_NAME']);
        http_response_code(403);
        exit('403 — Forbidden');
    }
}
// top of every admin page:
require_role(['admin']);
// upload page:
require_role(['admin', 'staff']);
```
- Permission matrix lives in `docs/01-REQUIREMENTS.md` §2; every page maps to exactly one row of it.
- Actions check both the page guard **and** the object policy: `can_access_document($user, $doc)`
  implements the Pending/Approved/Rejected access table.

### SR-4 · Data Confidentiality
- Files are saved to `storage/uploads/` — a sibling of `public/`, so Apache never serves them directly.
- The only path to file bytes is `documents/download.php`, which enforces: session → RBAC → document
  status policy → then streams the file and logs the access.
- `storage/uploads/.htaccess` as defense-in-depth (in case someone moves the folder):
  ```apacheconf
  php_flag engine off
  RemoveHandler .php .phtml .php3 .php4 .php5 .php7 .php8 .pht
  Options -Indexes
  ```
- Errors and logs never echo SQL, paths, or stack traces to users.

### SR-5 · SQL Injection Prevention
```php
// the ONLY acceptable way to query with user input
$stmt = $pdo->prepare("SELECT user_id, password_hash, role_id, status
                       FROM users WHERE username = ? AND status = 'active'");
$stmt->execute([$username]);
$user = $stmt->fetch();
```
- Rule: **if a value can vary, it is a placeholder.** No concatenation, no `"... WHERE id = $id"`.
- Search filters (LIKE) use bound parameters with the wildcard added in PHP, not in SQL strings.

### SR-6 · Input Validation & XSS & CSRF
```php
// server-side, whitelist style (lib/validate.php)
if (!in_array($ext, array_keys(ALLOWED_TYPES), true) || $size > MAX_UPLOAD) { reject(); }
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmpPath);   // real content, not the browser's claim

// output escaping — every dynamic value in HTML
function e(?string $v): string { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }

// CSRF — token in every form, verified on every POST
if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
    http_response_code(403); exit('Invalid CSRF token');
}
```
- Validation is server-side; client-side (HTML5) checks exist only for UX.
- Whitelists, not blacklists: allowed extensions/MIMEs are enumerated; everything else is rejected.

### SR-7 · Session Management
```php
// app/bootstrap.php
session_set_cookie_params([
    'lifetime' => 0, 'path' => '/',
    'httponly' => true, 'samesite' => 'Lax',   // 'secure' => true when on HTTPS
]);
session_start();

// idle timeout (30 minutes)
if (isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > 1800) {
    session_unset(); session_destroy();
    header('Location: /SDMS/public/login.php?timeout=1'); exit;
}
$_SESSION['last_activity'] = time();

// immediately after successful password verification:
session_regenerate_id(true);      // defeats session-fixation
```
- Logout: `session_unset()` + `session_destroy()` + new session cookie — back button cannot resurrect it.
- Only the session holds identity; never trust hidden fields or query strings for "who am I".

### SR-8 · Activity Logs
Logged events (who, what, when, IP, user-agent): login success/failure, logout, upload, view/download
(in `document_access_logs`, including **denied attempts**), edit, delete, approve, reject, user
create/update/deactivate, category changes, report generation.
- Logs are append-only: no UI or code path updates or deletes log rows.
- Admin log viewer supports filter by user, action, and date (FR-36).

### SR-9 · Account Management
- Admin can add, edit, deactivate, and reactivate users; deactivated accounts fail login instantly.
- Guards: an admin cannot deactivate their own account, and the system refuses to deactivate the last
  active admin (prevents lockout of the whole system).

### SR-10 · Data Integrity
- Foreign keys + InnoDB: orphaned rows are impossible; multi-step writes are wrapped in transactions.
- SHA-256 checksum per file at upload; (enhancement) re-verify on download and flag mismatches.
- Documents use soft delete; destructive UI actions require confirmation dialogs.
- Upload validation happens server-side **before** anything is stored.

---

## 4. Upload Hardening Checklist (dedicated)

- [ ] Size ≤ 10 MB (`upload_max_filesize`, `post_max_size`, and an app-level check)
- [ ] Extension whitelist **and** `finfo` MIME check (a renamed `.php` fails the MIME test)
- [ ] Random server-generated stored name; original name kept only as metadata
- [ ] No user input ever touches a filesystem path
- [ ] File lands in `storage/uploads/` (outside web root, execution disabled)
- [ ] Metadata row + activity log written in one transaction
- [ ] Failed/invalid uploads leave no partial files behind

---

## 5. Security Test Checklist (executed before the presentation)

| ID | Test | Steps | Expected |
|----|------|-------|----------|
| ST-01 | SQL injection | Login with `admin' OR 1=1 --` and variants | Normal failure message, no bypass, safe behavior |
| ST-02 | RBAC bypass | Log in as viewer; open `admin/users.php`, `admin/approvals.php` directly | 403 Forbidden + ACCESS_DENIED log entry |
| ST-03 | Direct file access | Request a stored file by any guessed URL | No route serves it from the browser |
| ST-04 | Unauthenticated access | While logged out, open `documents/index.php`, `download.php?id=1` | Redirect to login |
| ST-05 | Status policy | Viewer/staff attempts download of a Pending or Rejected document | 403 + denied access log (granted=0) |
| ST-06 | Owner rules | Staff edits/deletes another staff's document | Rejected by policy check |
| ST-07 | XSS | Save a document titled `<script>alert(1)</script>`; list documents | Title renders as inert text, no script runs |
| ST-08 | CSRF | POST the upload/approve form from an external page without the token | 403 Invalid CSRF token |
| ST-09 | Deactivation | Deactivate a test user; that user tries to log in | Login blocked; reactivating restores access |
| ST-10 | Sessions | After login/logout and after 30 idle minutes | Old session invalid; timeout redirects to login |
| ST-11 | Path traversal | `download.php?id=1` with crafted parameters | Only DB IDs are honored; no path manipulation possible |
| ST-12 | Malicious upload | Rename a `.php` to `.pdf`; upload a fake image MIME | Rejected by MIME/extension validation |
| ST-13 | Hash storage | Inspect `users.password_hash` in phpMyAdmin | bcrypt/argon2 hash only (`$2y$…`), no plaintext |
| ST-14 | Audit completeness | Perform one of each key action | Each appears in logs with user, IP, timestamp |
| ST-15 | Integrity | Corrupt a stored file; re-verify checksum; delete then restore | Mismatch detectable; soft-deleted row recoverable in DB |

Screenshots of the passing results go into the presentation (see `docs/07-PRESENTATION-PLAN.md`).

---

## 6. Configuration Hardening

- `php.ini` (or `.htaccess`): `display_errors=Off`, `log_errors=On`, `expose_php=Off`,
  `session.use_strict_mode=1`, `upload_max_filesize=10M`, `post_max_size=12M`.
- One MySQL account for the app (least privilege: `SELECT/INSERT/UPDATE` on `sdms`); root only for setup.
- phpMyAdmin is for development/demo only, never left reachable on a real deployment.

## 7. Stretch Goals (only if time allows — E priority)

- Login lockout after 5 failed attempts in 15 minutes (`login_attempts` table is ready).
- File encryption at rest (Sodium `sodium_crypto_secretbox`) for confidential categories.
- Self-signed HTTPS for the demo to show `secure` cookies end-to-end.
- Download watermarking / rate limiting per user.

## 8. Deployment Note

The demo runs on local XAMPP (HTTP on localhost is acceptable for the classroom). For any real
deployment: enable HTTPS first (secure cookies, no password sniffing), change all seeded passwords, and
restrict the MySQL user account.
