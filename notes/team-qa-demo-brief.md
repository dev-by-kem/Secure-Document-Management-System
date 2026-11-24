# Team QA & Demo Brief — SDMS (ITEC 85)

**What this is:** one-page briefing for the 7-member team, condensing docs 01/04/06/07/08 and CONTRIBUTING.md for QA runs and the role-play demo. All IDs (FR-n, SR-n, ST-xx) kept verbatim.
**Note:** the requested `.zwork/notes/` path is reserved by the runtime and not writable; this file is the digest, placed one level up.
**Source snapshot:** docs as of Nov 2026. Priorities: **M** = must have · **S** = should have · **E** = enhancement.

---

## 1. FR Checklist (docs/01-REQUIREMENTS.md §3)

| ID | Requirement (one-line) | Pri |
|----|------------------------|:---:|
| FR-1 | Login securely with username + password | M |
| FR-2 | Logout securely, ending the server session | M |
| FR-3 | Protected page while logged out → redirect to login | M |
| FR-4 | View personal profile | M |
| FR-5 | Change own password (requires current password) | S |
| FR-6 | Staff upload documents (title, category, description) | M |
| FR-7 | System validates every upload (type, size, content) before storing | M |
| FR-8 | Staff view list of their uploaded documents | M |
| FR-9 | Staff search & filter (title, category, status, date) | M |
| FR-10 | Staff download authorized documents | M |
| FR-11 | Staff edit details of their own documents | M |
| FR-12 | Staff delete their own documents when permitted | M |
| FR-13 | Staff see status Pending / Approved / Rejected | M |
| FR-14 | Staff view their upload history | M |
| FR-15 | Staff view their own activity logs | M |
| FR-16 | Rejected documents carry admin's remarks | S |
| FR-17 | Viewers view & search approved documents | M |
| FR-18 | Viewers download authorized (approved) documents | M |
| FR-19 | Admin dashboard: user & document counts | M |
| FR-20 | Admin adds, edits, manages user accounts | M |
| FR-21 | Admin assigns roles & access permissions | M |
| FR-22 | Admin activates / deactivates accounts | M |
| FR-23 | Admin views all uploaded documents | M |
| FR-24 | Admin reviews & verifies submitted documents | M |
| FR-25 | Admin approves or rejects documents | M |
| FR-26 | Admin edits / removes documents when necessary | M |
| FR-27 | Admin manages categories (add, edit, delete) | M |
| FR-28 | Admin monitors document access & user activities | M |
| FR-29 | Admin generates & prints document reports | M |
| FR-30 | Approval requirement configurable per category (`requires_approval`) | S |
| FR-31 | Reports filterable by date range & category | S |
| FR-32 | Log login success, login failure, logout | M |
| FR-33 | Log upload / view / download / edit / delete / approve / reject | M |
| FR-34 | Log user & category administration actions | M |
| FR-35 | Each log entry: timestamp, user, action, target, IP, user agent | M |
| FR-36 | Admin views & filters logs; logs not editable/deletable via UI | M |

*No FR carries E priority in doc 01.*

## 2. Security Requirements (doc 01 §4)

| ID | One-liner |
|----|-----------|
| SR-1 | Password hashing — `password_hash()`/`password_verify()`, never plaintext/MD5/SHA1 |
| SR-2 | Authentication — no page without login; redirect to login |
| SR-3 | RBAC — deny-by-default role guards (admin/staff/viewer) |
| SR-4 | Data confidentiality — files outside web root; gated downloads |
| SR-5 | SQL injection prevention — PDO prepared statements, 100% of queries |
| SR-6 | Input validation — server-side whitelist, XSS escaping, CSRF tokens |
| SR-7 | Session management — regenerate id, HttpOnly/SameSite, 30-min idle timeout |
| SR-8 | Activity logs — append-only `activity_logs` + `document_access_logs` |
| SR-9 | Account management — activate/deactivate; deactivated blocked at login |
| SR-10 | Data integrity — FKs, transactions, SHA-256 checksums, soft delete |

## 3. Security Test Checklist (doc 04 §5)

> Executor: doc 04 §5 sets no per-test owner ("executed before the presentation"); doc 06 §1b assigns the whole ST-01…ST-15 set to **Denise Valencia** on staging.

| ID | Tests (≤10 words) |
|----|-------------------|
| ST-01 | SQL injection: `' OR 1=1 --` login fails safely |
| ST-02 | RBAC bypass: viewer opens admin pages → 403 + ACCESS_DENIED log |
| ST-03 | Direct file access: guessed file URL serves nothing |
| ST-04 | Unauthenticated access: protected pages redirect to login |
| ST-05 | Status policy: Pending/Rejected download blocked, granted=0 logged |
| ST-06 | Owner rules: staff can't edit/delete another's document |
| ST-07 | XSS: `<script>` title renders as inert text |
| ST-08 | CSRF: tokenless external POST → 403 |
| ST-09 | Deactivation: deactivated user blocked; reactivation restores |
| ST-10 | Sessions: old session invalid after login/logout; 30-min timeout |
| ST-11 | Path traversal: only DB IDs honored on download |
| ST-12 | Malicious upload: `.php` renamed `.pdf` / fake MIME rejected |
| ST-13 | Hash storage: `users.password_hash` shows only `$2y$…` |
| ST-14 | Audit completeness: each key action logged with user, IP, timestamp |
| ST-15 | Integrity: checksum mismatch detectable; soft-deleted row recoverable |

## 4. Team Workflow (doc 06)

| Member | Role | Contribution channel |
|--------|------|----------------------|
| Kim Jerald Sialana | Team lead · all PHP (`app/`, `public/`) · integration | Only coder; merges PRs; unblocks; escalations land here |
| Dyna Lunas | Database owner (SQL only) | Schema PRs update `schema.sql` + doc 03 together; staging DB imports (doc 09); query tuning |
| Denise Valencia | QA & security testing lead · demo Hacker | Executes ST-01…ST-15 on staging; reviews every PR vs SR-1…SR-10 + upload hardening checklist; files bug reports + test evidence |
| Edcel Santos | QA engineer (functional) · demo viewer | FR-1…FR-29 checklist on staging → GitHub Issues (steps-to-reproduce, expected vs actual); no direct code edits |
| Mycah Layaban | UI/UX feedback lead · demo staff | Usability walkthroughs + Bootstrap-consistency checks on staging → Issues with screenshots; no direct code edits |
| Sofia Jhaye Peñaflor | Documentation & presentation lead · demo admin | Doc polish, slide drafts, printable-report checks, rehearsal notes |
| Elijah Zyrus Tipo | Test-data & admin-flow tester · demo admin/viewer | Prepares demo accounts/files; tests admin pages on staging → Issues |

Ground rules: testing happens on the staging site; GitHub Issues/PRs for anything code-related, group chat for day-to-day; blockers >24 h escalate to Kim; every PR ≥1 review (module owner + Denise for security-relevant); everyone keeps evidence visible on GitHub for grading.

## 5. Demo Format (doc 07 §2 — role-play, ~8 min, seeded data)

| Actor | Plays | Shows |
|-------|-------|-------|
| Kim | Narrator / drives laptop | Keeps story moving; says what each step proves |
| Elijah | **Administrator** | Accounts, approvals, logs, reports (FR-19…FR-29) |
| Mycah | **Staff member** | Upload → Pending → Approved (FR-6, FR-13) |
| Edcel | **Viewer** | Least privilege: only approved docs visible (SR-4) |
| Denise | **The Hacker** | Attacks and loses; every attempt recorded (SR-8) |
| Dyna | Database witness | phpMyAdmin: hashes, audit rows (SR-1, SR-8) |
| Sofia | Timekeeper + slides | Feeds next slide; watches the clock |

Script: ① login → dashboard counts (FR-19) ② admin creates staff + viewer accounts (FR-20…FR-21) ③ staff uploads PDF → Pending (FR-6, FR-13) ④ phpMyAdmin shows `$2y$…` hashes (SR-1, ST-13) ⑤ Hacker segment ~2 min, prepared payloads only: SQLi `' OR 1=1 --` fails (ST-01), viewer→`admin/users.php` 403 (ST-02), guessed file URL dead (ST-03), `.php`→`.pdf` upload rejected (ST-12), `<script>alert(1)</script>` title inert (ST-07) ⑥ viewer can't see/download Pending doc (ST-05) ⑦ admin approves (FR-25) ⑧ viewer downloads → original filename (FR-18) ⑨ log viewer shows upload + every hacker attempt + download with user/IP/timestamp (SR-8, ST-14) ⑩ generate & print report (FR-29).

⚠️ Hacker payloads are rehearsed in advance, never improvised; fallback = screenshots (slides 8–11) or pre-recorded capture. Casting is a proposal — swap freely, then update doc 06.

## 6. UI/UX Pointers for the Usability Walkthrough (doc 08)

**Screens/conventions to exercise:**
- **Theme toggle** (navbar): dark mode follows OS; toggle pins opposite scheme; override stored in `localStorage` (`sdms-theme`); check both themes on every page.
- **Documents & My-uploads tables** (`.sdms-table-wrap` + `.sdms-card-table`): sticky headers + keyboard-scrollable region on desktop; under 640 px rows stack into cards with `data-label` column names.
- **Destructive actions**: shared native `<dialog id="confirm-dialog">` (`.js-confirm` + `data-confirm-message`), Esc closes, focus returns, safe Cancel button autofocused.
- **Forms**: every control has `<label for>`; correct `autocomplete` (`username`, `current-password`, `new-password`); mutations POST + CSRF, searches GET; show-password toggle on sign-in; upload form uses `<fieldset>`/`<legend>`.
- **Feedback**: flash messages in `.flash-region` `aria-live="polite"`; status badges via `status_badge()`; empty tables show a muted "nothing here" row.
- **Accessibility**: skip link on `Tab` → `#main-content`; one `<h1>` per page; landmarks `<nav>`/`<main>`/`<footer>`/`<search>`; `:focus-visible` outline; reduced motion respected; no inline `onclick`.

**Walkthrough self-check (doc 08 §6):** keyboard-only reach + visible focus + Esc on dialogs · phone-width tables stack readably · both themes look right · 200% zoom no clipping · `tests/backend-smoke.ps1` 13/13 green.
**Graded rails (doc 08 §4):** never remove `require_login()`/`require_role()`, `csrf_field()`, or `e()` escaping; keep `data-label` synced with columns; rerun the smoke script after POST-flow UI changes.

## 7. CONTRIBUTING — PR/Review Rules + Definition of Done (§5) in ≤6 bullets

- `main` always works; one feature branch per feature/fix (merge every 2–3 days) via Pull Request — team lead or security focal reviews before merge.
- Every PR gets ≥1 review; security-relevant PRs also reviewed by Denise against SR-1…SR-10.
- Commits: `type(scope): summary ~50 chars` (feat/fix/docs/style/refactor/test/chore); push small and often.
- Never commit: `config.php` credentials (only `config.sample.php`), `storage/uploads/`, `logs/`, phpinfo dumps, `.env`, `vendor/`.
- DoD: satisfies the FR IDs it claims · works on a clean XAMPP checkout (clone + import SQL + config) · SR-1…SR-10 respected with server-side validation, escaped output, prepared queries.
- DoD: important actions write activity logs · UI shows success/failure feedback (no silent or raw MySQL errors) · peer-reviewed by ≥1 teammate and merged via PR.
