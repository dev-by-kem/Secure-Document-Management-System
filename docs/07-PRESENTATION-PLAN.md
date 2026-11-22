# 07 · Presentation Plan

**Secure Document Management System — ITEC 85 (Information Security) Final Presentation**

Target: ~15–20 minutes of slides + demo, then Q&A. The through-line for the professor: **every feature
exists to satisfy the manual's 10 security requirements — and we can prove it live.**

> **In plain words:** this is our script for demo day — what goes on the slides, the exact order of the
> live demonstration, and answers to the questions the professor is most likely to ask. Read it before
> the rehearsal; bring it printed.

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

## 2. Live Demo — Role-Play Format (~8 minutes, seeded data)

> **In plain words:** instead of one person clicking through everything, each member **plays their
> system role live** — and one member plays an attacker. The audience sees the product work *and* see
> it survive an attack. Casting is a proposal (doc 06 §1b) — swap freely, then rehearse once with all
> actors before demo day.

**The cast**

| Actor | Plays | Shows the audience |
|---|---|---|
| Kim | Narrator / drives the laptop | Keeps the story moving; says what each step proves |
| Elijah | **Administrator** | Control: accounts, approvals, logs, reports (FR-19…FR-29) |
| Mycah | **Staff member** | The daily workflow: upload → pending → approved (FR-6, FR-13) |
| Edcel | **Viewer** | Least privilege: only approved documents are visible (SR-4) |
| Denise | **The Hacker** | Attacks the system and loses — every attempt gets recorded (SR-8) |
| Dyna | Database witness | phpMyAdmin: hashed passwords, audit rows (SR-1, SR-8) |
| Sofia | Timekeeper + slides | Feeds the next slide; watches the clock |

**The script**

1. **(Kim narrates · Elijah as admin)** Login → dashboard shows user and document counts (FR-19).
2. **(Elijah)** Admin creates a staff account and a viewer account (FR-20…FR-21).
3. **(Mycah as staff)** Uploads a PDF with title/category/description; category requires approval →
   status **Pending** (FR-6, FR-13).
4. **(Dyna) Security internals:** open phpMyAdmin → `users.password_hash` shows only `$2y$…`
   hashes (SR-1, ST-13).
5. **(Denise — Hacker segment, ~2 min, prepared payloads only):**
   - login attempt `' OR 1=1 --` → normal failure, no bypass (SR-5, ST-01);
   - as viewer, open `admin/users.php` by URL → 403 (SR-3, ST-02);
   - request a stored file by a guessed URL → nothing serves it (SR-4, ST-03);
   - upload a `.php` renamed to `.pdf` → rejected by validation (ST-12);
   - document title `<script>alert(1)</script>` → renders as inert text (ST-07).
   Denise *loses every time* — and says so. That is the point.
6. **(Edcel as viewer)** The Pending document is **not** visible or downloadable (SR-4, ST-05).
7. **(Elijah)** Admin approves the document (FR-25).
8. **(Edcel)** Viewer now downloads it → the file arrives with its original name (FR-18).
9. **(Elijah) The punchline:** admin opens the log viewer → the upload, **every hacker attempt**, and
   the download are all recorded with user, IP, and timestamp (SR-8, ST-14).
10. **(Dyna)** Reports → generate and print a document report (FR-29).

> ⚠️ The Hacker uses **pre-prepared payloads rehearsed in advance** — nothing improvised live.
> If live probing is skipped for time, slides 8–11 carry the same evidence as screenshots.

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

- **Speaker split (suggested):** intro + architecture (Kim), roles + demo navigation (Kim/Mycah), security deep-dive (Denise), database + reports (Dyna), conclusion (Elijah, Edcel, Sofia) — and everyone acts their demo role (§2).
- **Checklist before the session:**
  - [ ] Fresh seed data, demo accounts for all 3 roles
  - [ ] Role-play rehearsed once with all actors; hacker payloads prepared and tested (§2)
  - [ ] XAMPP Apache + MySQL running; browser tabs pre-opened (app, phpMyAdmin, logs)
  - [ ] Slides exported to PDF on laptop **and** USB/email/cloud backup
  - [ ] Backup laptop with the same setup; recorded demo as final fallback
  - [ ] Security-test screenshots ready in slide 11 in case live probing is skipped
- **Timing target:** slides ≤ 10 min, demo ≤ 8 min, leaving ~5+ min for Q&A.
