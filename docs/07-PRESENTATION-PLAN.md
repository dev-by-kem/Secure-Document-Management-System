# 07 · Presentation Plan

**Secure Document Management System — ITEC 85 (Information Security) Final Presentation**

Target: ~15–20 minutes of slides + demo, then Q&A. The through-line for the professor: **every feature
exists to satisfy the manual's 10 security requirements — and we can prove it live.**

---

## 1. Slide Outline

| # | Slide | Key content |
|---|-------|-------------|
| 1 | Title | Project name, course ITEC 85, CvSU-CEIT, all 7 members |
| 2 | Problem & Objectives | Manual §A–§B: ordinary storage exposes files → centralized, secure platform |
| 3 | Scope & Limitations | In scope (web app, 3 roles, approval, audit) / out of scope (email, cloud, mobile) |
| 4 | System Architecture | High-level diagram (doc 02 §2); emphasize files outside web root + gated downloads |
| 5 | Database Design | ERD (doc 03 §1); call out append-only audit tables |
| 6 | User Roles & Access Policy | Permission matrix + the Pending/Approved/Rejected access table |
| 7 | Security Requirements Mapping | SR-1…SR-10 table (README §Security Highlights) — the core slide |
| 8 | Security Deep-Dive 1 | Password hashing + prepared statements (show 5-line code snippets) |
| 9 | Security Deep-Dive 2 | RBAC deny-by-default + session hardening + CSRF/validation |
| 10 | Live Demo | Script below |
| 11 | Test Evidence | ST checklist results table; 2–3 screenshots (SQLi blocked, hash in DB, denied log) |
| 12 | Challenges & Learnings | Honest short list (e.g., MIME validation, session fixation, teamwork) |
| 13 | Conclusion & Future Work | Objectives met; future: email notifications, lockout, encryption at rest, HTTPS |
| 14 | Thank You / Q&A | Repo link + docs |

---

## 2. Live Demo Script (~6–8 minutes, seeded data)

1. **Login as admin** → dashboard shows user and document counts (FR-19).
2. **Admin creates a staff account and a viewer account** (FR-20…FR-21).
3. **Log in as staff** → upload a PDF with title/category/description; category requires approval →
   status **Pending** (FR-6, FR-13).
4. **Show security internals while logged in as staff:**
   - open phpMyAdmin → `users.password_hash` shows only `$2y$…` hashes (SR-1);
   - attempt login `' OR 1=1 --` → generic failure (SR-5);
   - attempt to open `admin/users.php` by URL → 403 (SR-3).
5. **Log in as viewer** → the Pending document is **not** visible/downloadable (SR-4).
6. **Log in as admin** → approve the document with one click (FR-25).
7. **Viewer now downloads it** → file arrives with its original name (FR-18).
8. **Admin opens the log viewer** → the upload, the denied attempt, and the download are all there with
   IP and timestamp (SR-8).
9. **Reports** → generate and print a document report (FR-29).

Fallback: pre-recorded screen capture of the same steps, in case the demo laptop misbehaves.

---

## 3. Q&A Preparation (likely professor questions)

| Likely question | Our answer |
|-----------------|------------|
| Why bcrypt/`password_hash()` instead of MD5/SHA1? | MD5/SHA1 are fast → brute-forceable; `PASSWORD_DEFAULT` uses bcrypt/argon2 with per-password salt and tunable cost |
| How exactly is SQL injection prevented? | 100% PDO prepared statements — user input is always a bound parameter, never concatenated; demo ST-01 |
| What if someone steals a session cookie? | HttpOnly blocks JS theft; `session_regenerate_id(true)` on login defeats fixation; idle timeout limits the window; SameSite=Lax limits cross-site replay |
| How is confidentiality enforced for files? | Files live outside the web root; the only access path is `download.php`, which checks session → role → document status policy, then streams and logs |
| How do you ensure data integrity? | FK constraints, transactions, SHA-256 checksums per file, soft delete, confirmation dialogs, append-only audit logs |
| How do you know who did what? | `activity_logs` + `document_access_logs` (including denied attempts) with user, action, IP, timestamp — demo step 8 |
| What happens when an account is deactivated? | Login check filters `status='active'`, so access ends instantly; admin can reactivate |
| Can a staff member approve their own document? | No — approval pages are `require_role(['admin'])` and the policy check is centralized in `lib/rbac.php` |

---

## 4. Presentation Logistics

- **Speaker split (suggested):** intro + architecture (Kim), roles + demo navigation (Mycah), security deep-dive (Denise), database + reports (Dyna), admin flows + conclusion (Elijah/Edcel/Sofia).
- **Checklist before the session:**
  - [ ] Fresh seed data, demo accounts for all 3 roles
  - [ ] XAMPP Apache + MySQL running; browser tabs pre-opened (app, phpMyAdmin, logs)
  - [ ] Slides exported to PDF on laptop **and** USB/email/cloud backup
  - [ ] Backup laptop with the same setup; recorded demo as final fallback
  - [ ] Security-test screenshots ready in slide 11 in case live probing is skipped
- **Timing target:** slides ≤ 10 min, demo ≤ 8 min, leaving ~5+ min for Q&A.
