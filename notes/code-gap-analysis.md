# Code Gap Analysis — SDMS (read-only audit)

Date: 2026-11-22 · Scope: `docs/01-REQUIREMENTS.md` (FR-1…36, SR-1…10, NFR-1…8) vs. current code; cross-check of `docs/04-SECURITY-PLAN.md` §5 (ST-01…ST-15).
Method: full read of app/lib/*, app/includes/*, bootstrap, all 18 public pages, schema.sql, seed.sql, both .htaccess files, config; greps for `query()`, md5/sha1, SQL concatenation, log-table writes, transactions, print handlers.
Note: the requested location `.zwork/notes/code-gap-analysis.md` is blocked by the runtime (.zwork is reserved for state); this file is the single report deliverable instead.
Evidence tiers: ✅ = verified in code (highest tier); ⚠ = inferred/needs runtime check; every claim names a file. No project file was modified.

## Verdict summary

| Group | Implemented | Partial | Missing | Unclear |
|---|---|---|---|---|
| FR (36) | 32 | 3 (FR-9, FR-29, FR-35) | 1 (FR-15) | 0 |
| SR (10) | 9 | 1 (SR-10) | 0 | 0 |
| NFR (8) | 5 | 2 (NFR-6, NFR-8) | 0 | 1 (NFR-3) |

Headline: the security core is solid (bcrypt, throttling present, CSRF everywhere, prepared statements everywhere, gated downloads, hardening files all present). The gaps are mostly workflow/finish quality: no staff activity-log view, dead Print button, no date filter, access-log rows lack user agent, no transactions/checksum re-verification, and a download-policy deviation (owner can download a rejected document).

---

## 1. Functional Requirements (FR-1…FR-36)

| ID | Requirement (short) | Status | Evidence (file → behavior) |
|----|---|---|---|
| FR-1 | Secure login with username+password | ✅ Implemented | `app/lib/auth.php` `attempt_login()`: `password_verify`, generic "Invalid username or password.", throttling, `session_regenerate_id(true)`. |
| FR-2 | Secure logout ends server session | ✅ Implemented | `app/lib/auth.php` `do_logout()`: unset + cookie-expire + `session_destroy()`; `public/logout.php` accepts POST+CSRF only. |
| FR-3 | Logged-out access → redirect to login | ✅ Implemented | `require_login()` in `app/lib/auth.php` redirects; present on dashboard, profile, all documents/* pages. |
| FR-4 | View own profile | ✅ Implemented | `public/profile.php` renders name/email/role/last login. |
| FR-5 | Change own password (requires current) | ✅ Implemented | `public/profile.php` POST: `password_verify(current)` gate, min-8 rule, rehash with `PASSWORD_DEFAULT`, logs `CHANGE_PASSWORD`. |
| FR-6 | Upload with title, category, description | ✅ Implemented | `public/documents/upload.php`: title/desc/category fields + validation; role guard `require_role(['admin','staff'])`. |
| FR-7 | System validates every upload (type/size/content) | ✅ Implemented | `app/lib/validate.php` `validate_upload()`: extension whitelist, `finfo` MIME check, `MAX_UPLOAD_BYTES` cap; called before storage in upload.php. |
| FR-8 | Staff view list of own documents | ✅ Implemented | `public/documents/history.php` (own uploads), dashboard "My recent uploads", index "Only mine" filter. |
| FR-9 | Search & filter by title, category, status, **date** | ⚠ Partial | `public/documents/index.php`: q/category/status/mine filters exist; **no date filter anywhere**; also `$mine` is used for checkbox state but never assigned (undefined-variable bug; checkbox does not retain state). |
| FR-10 | Download authorized documents | ✅ Implemented | `public/documents/download.php`: session → policy → stream; logs access + activity. |
| FR-11 | Edit own document details | ✅ Implemented | `public/documents/edit.php`: owner-or-admin gate + ACCESS_DENIED log; updates title/description/category. |
| FR-12 | Delete own documents when permitted | ✅ Implemented | `public/documents/delete.php`: owner-or-admin, POST+CSRF, soft delete (`is_deleted=1, deleted_at`). |
| FR-13 | Staff see Pending/Approved/Rejected status | ✅ Implemented | Status badges on dashboard.php, history.php, index.php, view.php. |
| FR-14 | View upload history | ✅ Implemented | `public/documents/history.php` lists own uploads newest-first. |
| FR-15 | Staff view **their own activity logs** | ❌ Missing | No staff-facing log page exists. `public/admin/logs.php` is `require_role(['admin'])`; staff nav (app/includes/nav.php) has Dashboard/Documents/Upload/My uploads/Profile only. |
| FR-16 | Rejected docs carry admin remarks | ✅ Implemented | `admin/approvals.php` requires remarks on reject; `documents/view.php` shows remarks to owner+admin only. |
| FR-17 | Viewers view & search approved documents | ✅ Implemented | `documents/index.php` non-admin WHERE: `(uploaded_by = ? OR status='approved')`; search applies. |
| FR-18 | Viewers download approved documents | ✅ Implemented | `can_access_document()` (`app/lib/rbac.php`): non-owner gets approved-only; download gate enforces it. |
| FR-19 | Admin dashboard: user & document counts | ✅ Implemented | `public/admin/dashboard.php`: users total/active, docs total/pending/approved/rejected, categories, recent activity. |
| FR-20 | Admin adds/edits user accounts | ✅ Implemented | `admin/users.php` actions create/update/reset_password with validation, duplicate check, logging. |
| FR-21 | Admin assigns roles | ✅ Implemented | role_id select in create/update; self-role change blocked server-side (`users.php` update branch). |
| FR-22 | Admin activates/deactivates accounts | ✅ Implemented | `toggle_status` with self-deactivation block and last-active-admin guard (`active_admin_count()`). |
| FR-23 | Admin views all uploaded documents | ✅ Implemented | `documents/index.php` admin branch: no owner restriction; `?deleted=1` includes soft-deleted rows. |
| FR-24 | Admin reviews submitted documents | ✅ Implemented | `admin/approvals.php` pending queue + recently-reviewed list. |
| FR-25 | Admin approves/rejects when required | ✅ Implemented | approve/reject actions; transition guarded to `status='pending'` only; logs both. |
| FR-26 | Admin edits/removes documents | ✅ Implemented | edit.php (admin allowed), delete.php (admin allowed). |
| FR-27 | Admin manages categories (add/edit/delete) | ✅ Implemented | `admin/categories.php` create/update/delete; delete blocked while documents exist. |
| FR-28 | Admin monitors access & activities | ✅ Implemented | `admin/logs.php` two tabs: activity_logs + document_access_logs (incl. denied). |
| FR-29 | Admin generates **and prints** reports | ⚠ Partial | `admin/reports.php` generates filtered table with print styling (`d-print-none` footer), **but the `#print-report` button has no handler: grep finds no `window.print()` / `onclick` in any JS or PHP. Button is dead; only browser Ctrl+P works.** |
| FR-30 | Per-category `requires_approval` flag | ✅ Implemented | schema.sql `document_categories.requires_approval`; categories.php checkbox; upload.php sets status pending/approved from the flag. |
| FR-31 | Reports filterable by date range & category | ✅ Implemented | reports.php: category_id, status, from, to (validated before interpolation). |
| FR-32 | Log login success/failure/logout | ✅ Implemented | auth.php: LOGIN_SUCCESS, LOGIN_FAILED, LOGIN_THROTTLED, LOGIN_DENIED_INACTIVE, LOGOUT; bootstrap.php logs SESSION_TIMEOUT. |
| FR-33 | Log upload/view/download/edit/delete/approve/reject | ✅ Implemented | UPLOAD/EDIT/DELETE/DOWNLOAD_DOCUMENT, APPROVE/REJECT_DOCUMENT via `log_activity`; view/download via `log_access` (granted & denied). |
| FR-34 | Log user & category administration | ✅ Implemented | CREATE/UPDATE/RESET/ACTIVATE/DEACTIVATE_USER; CREATE/UPDATE/DELETE_CATEGORY. |
| FR-35 | Every log entry stores timestamp, user, action, target, IP, **user agent** | ⚠ Partial | `activity_logs` has all six (schema.sql + logger.php). **`document_access_logs` has no `user_agent` column** — access rows store user/action/target/IP/timestamp but never user agent. |
| FR-36 | Admin views/filters logs; logs not editable/deletable via UI | ✅ Implemented | logs.php filters (user/action/date); grep confirms **no UPDATE/DELETE statement targets `activity_logs`, `document_access_logs`, or `login_attempts` anywhere**. |

## 2. Security Requirements (SR-1…SR-10)

| ID | Requirement | Status | Evidence |
|----|---|---|---|
| SR-1 | Password hashing | ✅ Implemented | `password_hash(PASSWORD_DEFAULT)` / `password_verify` (auth.php, profile.php, users.php); grep finds **no md5/sha1** in any PHP; seed.sql stores `$2y$10$…` bcrypt only. |
| SR-2 | Mandatory authentication | ✅ Implemented | `require_login()` on every protected page; `current_user()` re-checks `status='active'` each request, so mid-session deactivation kills the session (auth.php). |
| SR-3 | RBAC deny-by-default | ✅ Implemented | `require_role()` in `app/lib/rbac.php` logs ACCESS_DENIED + 403; **every page that needs a guard has one** (see §4 checklist) — no page lacking a guard was found. |
| SR-4 | Data confidentiality | ✅ Implemented (1 policy deviation) | Files in `storage/uploads/` (sibling of public/); root `.htaccess` `RedirectMatch 404 ^/(app|database|docs|logs|scripts|storage|tests…)`; `storage/uploads/.htaccess` `Require all denied`; sole path is download.php gate. **Deviation: `can_access_document()` lets the owner download a REJECTED document; the spec access table (01-REQUIREMENTS §2) grants owner view-only for rejected.** |
| SR-5 | Prepared statements | ✅ Implemented | All user-input queries use PDO placeholders (`ATTR_EMULATE_PREPARES => false`, db.php); grep for `(SELECT|INSERT|UPDATE|DELETE|WHERE)… . $` finds **zero concatenated SQL**; all `->query()` calls are constant SQL; dynamic WHERE clauses are assembled from whitelisted fragments with `?` placeholders (logs.php, index.php, reports.php). |
| SR-6 | Input validation / XSS / CSRF | ✅ Implemented | Server-side whitelists (validate.php), `e()` = `htmlspecialchars(ENT_QUOTES,'UTF-8')` on all dynamic output, CSRF on **every** state-changing form (none missing — see §4); `csrf_check()` uses `hash_equals`, logs CSRF_REJECTED, exits 403. |
| SR-7 | Session management | ✅ Implemented | bootstrap.php: HttpOnly + SameSite=Lax + config-driven `secure` cookie; `session_regenerate_id(true)` after successful verify; 30-min idle timeout (config `session_timeout=1800`) enforced with destroy + redirect + flash; logout destroys session. |
| SR-8 | Activity logs | ✅ Implemented | `app/lib/logger.php` append-only inserts to `activity_logs` (+ `document_access_logs`); failures fall back to error_log. Logged vs not-logged lists: see §5. |
| SR-9 | Account management | ✅ Implemented | users.php CRUD + toggle; deactivated login blocked in `attempt_login()` (status check after hash verify) with LOGIN_DENIED_INACTIVE log; self-deactivation and last-admin lockout prevented. |
| SR-10 | Data integrity | ⚠ Partial | FKs + InnoDB (schema.sql), SHA-256 at upload (`hash_file('sha256')`, upload.php), soft delete recoverable in DB, confirm dialogs (`.js-confirm` + native `<dialog>` in footer.php/app.js). **Missing: zero `beginTransaction`/`commit` in the codebase; download does not re-verify checksum (plan itself marks re-verify "(enhancement)"); no restore UI for soft-deleted docs; upload.php moves the file before INSERT without transaction → failed INSERT leaves an orphan file.** |

## 3. Non-Functional Requirements (NFR-1…NFR-8)

| ID | Requirement | Status | Evidence |
|----|---|---|---|
| NFR-1 | Usability: feedback, confirmations | ✅ Implemented | flash system (flash.php + header.php flash region), success/error on every action; delete forms use `.js-confirm` native dialog (footer.php + app.js:49-61). (Minor: index.php `$mine` checkbox bug degrades filter UX.) |
| NFR-2 | Portability: clone + import + one config | ✅ Implemented | config.sample.php → single config.php (gitignored); schema/seed import documented in-file; no CREATE DATABASE needed for free hosts. |
| NFR-3 | ~2s response on lab hardware | ❓ Unclear | Not statically verifiable. Supporting code evidence: indexes on status/category/uploader/title (schema.sql), LIMIT 200/500 caps on lists. Needs runtime measurement. |
| NFR-4 | 10 MB cap; pdf/doc(x)/xls(x)/ppt(x)/txt/jpg/png | ✅ Implemented | `ALLOWED_UPLOADS` covers exactly pdf, doc, docx, xls, xlsx, ppt, pptx, txt, jpg, jpeg, png; `upload_max_mb=10` in config; app-level size check in validate.php. |
| NFR-5 | Maintainability: shared libs, documented schema | ✅ Implemented | app/lib/* shared by all pages; schema.sql cross-references docs/03-DATABASE-DESIGN.md. |
| NFR-6 | Auditability: any important action reconstructable from logs | ⚠ Partial | Strong base (see §5). Gaps: failed upload attempts not logged; password-change failures not logged; document views lack user agent; no integrity-check events (corruption undetectable by app). |
| NFR-7 | Documented backup procedure | ✅ Implemented | `docs/03-DATABASE-DESIGN.md:338`: "mysqldump -u root sdms > backup.sql + copy of storage/uploads/". |
| NFR-8 | utf8mb4 throughout | ⚠ Partial | PDO DSN `charset=utf8mb4` (db.php), HTML `charset=utf-8`; but schema.sql `CREATE TABLE`s declare **no explicit CHARACTER SET** — correctness relies on the DB being created as utf8mb4_unicode_ci per the import comment (schema.sql header). Works if setup followed; not enforced by schema. |

## 4. Spot-check verdicts (the "specifically verify" list)

- **Password hashing**: bcrypt via `PASSWORD_DEFAULT`/`password_verify`; no md5/sha1 anywhere (grep). ✅
- **Session timeout**: enforced in `app/bootstrap.php` for any logged-in request; destroys session, logs SESSION_TIMEOUT, redirects with flash. ✅
- **RBAC guard on every page**: admin/* all `require_role(['admin'])` (6/6); documents/* all `require_login()` (7/7) with object policy in view/download/edit/delete; upload additionally `['admin','staff']`; dashboard/profile `require_login()`. `index.php` (redirect only), `login.php` (public by design), `logout.php` (POST+CSRF) need no guard. **No page lacking a guard.** ✅
- **CSRF on every state-changing form**: token present + verified on login, logout, profile password, users (create/update/reset/toggle ×3 forms), categories (add/edit/delete ×3 forms), approvals (approve/reject), upload, edit, delete. **None missing.** GET search/filter forms correctly omit tokens. ✅
- **Prepared statements**: zero string-concatenated SQL found (grep); PDO native prepares; LIKE wildcards bound from PHP. ✅
- **Upload validation**: ext whitelist + `finfo` MIME + size cap + `bin2hex(random_bytes(16))` stored name + original kept as metadata + SHA-256. Nuance: docx/xlsx/pptx whitelists accept `application/zip`, so an arbitrary zip renamed `.docx` passes MIME (acceptable; OOXML is zips) — a `.php` renamed `.pdf` is still rejected. ✅ (minor weakening noted)
- **Files outside web root, gated serving**: storage/ blocked twice (root RedirectMatch 404 + uploads `.htaccess` `Require all denied`); only download.php streams bytes, from `stored_filename` (server-generated, DB-only). Plan §SR-4's `php_flag engine off` snippet is not used, but `Require all denied` is strictly stronger. ✅
- **Download policy**: implemented as coded — deleted → admin only; admin → all; owner → own any state; others → approved only. Deviation vs. spec table: owner may download a **rejected** doc (spec says view-only for owner). Pending of others blocked for viewers/staff. ⚠ 1 deviation
- **Approval workflow transitions**: only `pending → approved/rejected` (approvals.php rejects actions on non-pending docs with a warning); approve records reviewer+time and clears remarks; reject requires remarks. **Gap: no path back to pending** — a rejected doc can never re-enter review through the UI (edit keeps status `rejected`). ⚠
- **Category requires_approval behavior**: upload → `pending` iff flag=1 else `approved`; flag editable per category; upload form marks "(needs approval)" categories. ✅
- **Document history/versioning**: upload **history** list exists (history.php); **versioning** (versions table, revision on edit) does not exist anywhere — but no FR requires it; edits overwrite metadata in place, file never replaced. ✅ as required / no versioning
- **Search & filter**: title/description LIKE, category, status, mine. Missing: date filter (FR-9); `$mine` undefined-variable bug breaks checkbox state persistence. ⚠
- **Audit logging**: see §5 for exact lists. ✅ with noted holes
- **Admin log viewer**: logs.php with user/action/date filters, two tabs, newest 200, read-only. ✅
- **Dashboard stats**: admin dashboard 7 counters + recent activity; staff/user dashboard personal counts + recent uploads. ✅
- **Printable reports**: filtered generation works; **Print button dead (no JS handler)**; print CSS partially present (`d-print-none`). ⚠
- **Login throttling/lockout**: **present, contrary to the task's expectation** — `attempt_login()` blocks after 5 failed attempts per username in 15 min (`login_attempts` table), logs LOGIN_THROTTLED; also tracks `users.failed_attempts` (display-only; not itself a lockout). ✅

## 5. Audit logging — exact coverage

**Logged (activity_logs)**: LOGIN_SUCCESS, LOGIN_FAILED, LOGIN_THROTTLED, LOGIN_DENIED_INACTIVE, LOGOUT, SESSION_TIMEOUT, CSRF_REJECTED, ACCESS_DENIED (page guard + view/edit/delete/download attempts), CHANGE_PASSWORD, CREATE_USER, UPDATE_USER, RESET_PASSWORD, ACTIVATE_USER, DEACTIVATE_USER, CREATE_CATEGORY, UPDATE_CATEGORY, DELETE_CATEGORY, UPLOAD_DOCUMENT, EDIT_DOCUMENT, DELETE_DOCUMENT, APPROVE_DOCUMENT, REJECT_DOCUMENT, DOWNLOAD_DOCUMENT, REPORT_GENERATED.
**Logged (document_access_logs)**: view/download, granted=1/0, with user, IP, timestamp.

**NOT logged (key holes)**:
1. **Failed/invalid upload attempts** (validate_upload rejections) — no audit trail of upload abuse.
2. **Failed password-change attempts** (profile.php wrong current password → flash only).
3. **Failed edit/delete attempts on missing docs** and failed login-adjacent errors beyond the above (only non-owner denials logged).
4. **Document list browsing / dashboard / log-viewer / report-page opens** (report *generation* is logged; viewing logs is not).
5. **User agent is never captured for document_access_logs rows** (column absent from schema) — weakens FR-35/NFR-6 for view/download forensics.

## 6. ST-01…ST-15 cross-check (static prediction)

| ID | Tests | Prediction | Basis |
|----|---|---|---|
| ST-01 | SQLi at login | **PASS** | prepared statement + `EMULATE_PREPARES=false`; no concatenated SQL anywhere. |
| ST-02 | Viewer opens admin pages | **PASS** | `require_role(['admin'])` on all 6 admin pages → 403 + ACCESS_DENIED log. |
| ST-03 | Direct file URL | **PASS** | root .htaccess 404s /storage; uploads .htaccess `Require all denied`. |
| ST-04 | Logged-out access to documents/download | **PASS** | `require_login()` redirect on both. |
| ST-05 | Download pending/rejected of others | **PASS** | `can_access_document` denies non-owner/non-admin → 403 + access log granted=0. |
| ST-06 | Staff edits/deletes another's doc | **PASS** | owner-or-admin gate + ACCESS_DENIED log in edit.php/delete.php. |
| ST-07 | XSS via document title | **PASS** | `e()` on every echo of title/description/etc. across all pages. |
| ST-08 | CSRF-less external POST | **PASS** | `csrf_check()` first line of every POST branch; hash_equals → 403 + CSRF_REJECTED log. |
| ST-09 | Deactivated login | **PASS** | status check after hash verify; message "account has been deactivated"; reactivation restores (toggle). |
| ST-10 | Session fixation/timeout | **PASS** | `session_regenerate_id(true)` on login; destroy on logout; 30-min idle timeout enforced in bootstrap. |
| ST-11 | Path traversal on download | **PASS** | only `?id` int → DB row → `stored_filename` (server-generated); no user input in path. |
| ST-12 | .php renamed .pdf / fake MIME | **PASS** | ext whitelist + finfo mismatch rejection. (Caveat: zip-MIME accepted for docx/xlsx/pptx.) |
| ST-13 | Hash storage inspection | **PASS** | bcrypt `$2y$` in seed.sql; `password_hash` at every write site. |
| ST-14 | Audit completeness | **PASS with caveats** | All enumerated key actions logged with user/IP/timestamp; but access rows lack user agent and failed uploads aren't logged (see §5). |
| ST-15 | Corrupt file → detect; delete → restore | **FAIL (likely)** | No in-app checksum re-verification (download streams without hashing; view.php only displays the stored hash); no restore action exists (soft-deleted rows recoverable only via SQL). The plan itself defers re-verify to "(enhancement)". |

## 7. Minor findings (not requirement failures)

1. `public/documents/index.php` — `$mine` used but never assigned: PHP 8 undefined-variable warning; "Only mine" checkbox never re-checked after search.
2. `admin/reports.php` — `#print-report` button has no click handler anywhere (no `window.print()` in any JS/PHP).
3. `documents/upload.php` — file moved to disk before metadata INSERT, no transaction: a DB failure leaves an orphan file in storage/uploads/ (plan §4 checklist expects metadata+log "in one transaction"; also `log_activity` is a separate write by design).
4. No resubmission path: editing a rejected document never returns it to `pending`; only an admin could via SQL. view.php even instructs "ask an administrator to review it again" — but no admin action exists for it.
5. Owner can download their own rejected document (stricter spec says view-only) — `rbac.php::can_access_document`.
6. `document_access_logs` lacks `user_agent` column (schema.sql) though logger records it for activity_logs.
7. Logs viewer caps at newest 200 rows (no pagination) — fine for class scale, noted for completeness.
8. `storage/uploads/.htaccess` uses `Require all denied` instead of the plan's `php_flag engine off` + RemoveHandler combo — strictly stronger; update the plan text or keep as-is.
9. NFR-8 relies on the database default charset; adding `DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci` to schema.sql would make it self-enforcing.

## 8. Suggested next steps (if investing further)

1. Add a staff-facing "My activity" page (reuse logs.php query filtered to own user_id) — closes FR-15.
2. Add `onclick`/app.js binding calling `window.print()` for `#print-report` — closes FR-29.
3. Add a date-range filter to documents/index.php; fix `$mine` (assign `$mine = query_param('mine') === '1';`).
4. Add `user_agent` column to `document_access_logs` + logger write — closes FR-35 fully.
5. Wrap upload in a transaction (or delete the moved file on INSERT failure); optionally re-verify SHA-256 in download.php and log CHECKSUM_MISMATCH — closes most of SR-10 / ST-15.
6. Add an admin "re-review" action (rejected → pending) to complete §6 workflow.
7. Run `tests/backend-smoke.ps1` against XAMPP to convert the static ST predictions in §6 into runtime evidence (ST-15 especially), and measure NFR-3 response times on lab hardware.
8. Confirm staging config.php sets `session_secure_cookie = true` (docs/09) — not verifiable from this repo since config.php is machine-local (the local copy sets false, which is correct for HTTP localhost).
