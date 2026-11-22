# 05 · Development Plan

**Secure Document Management System — ITEC 85 (Information Security)**

Six milestones from empty repo to final presentation. Each milestone ends with a working, mergeable
state of `main` — never a half-broken branch. Timeline is relative (Week 1…Week 6); anchor real dates
as soon as the professor announces the presentation schedule.

> **In plain words:** this is the project calendar and chore list. A **milestone** is a checkpoint where
> something actually works end-to-end (login works, uploads work, …). **W1–W6** are our weeks, and each
> module has one **owner** — the person who drives it while a teammate reviews the code. Wondering what
> to do next? Find the current milestone, pick an unstarted requirement ID from
> `docs/01-REQUIREMENTS.md`, and build it.

---

## 1. Milestones

| # | Milestone | Scope (requirement IDs) | Exit criteria |
|---|-----------|--------------------------|---------------|
| M0 | **Setup & skeleton** | Repo on GitHub, XAMPP running, folder skeleton per `docs/02-ARCHITECTURE.md`, `schema.sql` + `seed.sql` imported, `bootstrap.php` + config | Fresh clone runs `public/index.php` without errors; roles/categories seeded |
| M1 | **Auth & users** | FR-1…FR-5, FR-20…FR-22, SR-1, SR-2, SR-3, SR-7, SR-9 | Login/logout/session timeout work; admin can create staff & viewer accounts; RBAC guards block wrong roles |
| M2 | **Core documents** | FR-6…FR-12, FR-17, FR-18, SR-4, SR-5, SR-6 | Upload with validation → stored outside web root; gated download with status policy; search/filter works |
| M3 | **Approval & categories** | FR-13…FR-16, FR-23…FR-27, FR-30 | Pending → Approved/Rejected flow with remarks; category CRUD incl. `requires_approval` |
| M4 | **Audit & hardening** | FR-28, FR-32…FR-36, SR-8, SR-10 + CSRF/escaping sweep | All key actions logged; admin log viewer; full ST-01…ST-12 checklist passes |
| M5 | **Dashboard & reports** | FR-19, FR-29, FR-31 | Admin dashboard stats; printable document reports |
| M6 | **QA, docs & rehearsal** | NFR-1…NFR-8, ST-13…ST-15 | Security checklist 100% green, demo data seeded, README/install verified on a clean machine, presentation rehearsed |

---

## 2. Suggested Task Assignments

PHP is written by the code team only (doc 06 §1a); every merged feature is still reviewed (`CONTRIBUTING.md`).
QA, UI feedback and documentation work flows through GitHub Issues and the staging site (docs 06 §1b, 09).

| Member | Owns | Also supports |
|--------|------|---------------|
| **Kim Jerald Sialana** (lead) | ALL application code: `lib/*`, `bootstrap.php`, `documents/*`, `admin/*`, UI pages, `assets/` integration, repo hygiene | M1 pairing, code review on everything |
| **Dyna Lunas** | `database/schema.sql`, `seed.sql`, `demo-data.sql`, ERD, staging DB imports (doc 09), query tuning | Search/filter queries (M2), reports queries (M5) |
| **Edcel Santos** | Functional QA: FR-1…FR-29 checklist on staging; bug reports as Issues | Download-gate tests (M2); demo actor (viewer) |
| **Denise Valencia** | Security QA: ST-01…ST-15 execution + evidence; reviews every PR against SR-1…SR-10 | Upload hardening checks (M2); plays the Hacker in the demo |
| **Elijah Zyrus Tipo** | Test-data prep (demo accounts/files); admin-flow testing on staging | Reports checks (M5); demo actor |
| **Mycah Layaban** | UI/UX feedback: usability walkthroughs + suggestions as Issues (staff & viewer flows) | Upload form UX, confirmation dialogs; demo actor (staff) |
| **Sofia Jhaye Peñaflor** | Documentation polish + presentation materials | Printable reports checks; demo actor (admin) |

Pairing suggestion: backend developer + frontend developer per module (e.g., Edcel + Mycah for
documents; Elijah + Sofia for admin; Kim + Denise for auth/security).

---

## 3. Timeline (relative)

| Week | Focus | Milestone |
|------|-------|-----------|
| W1 | Repo + XAMPP + schema v1 + skeleton; Git mini-workshop | M0 done |
| W2 | Login/session/RBAC + admin user management | M1 done |
| W3 | Upload, download gate, list/search/view | M2 done |
| W4 | Approvals, categories, document admin actions | M3 done |
| W5 | Activity logs, hardening sweep, security tests, dashboard + reports | M4 + M5 done |
| W6 | Full test pass, install guide verification, demo data, slides, rehearsal | M6 done |

Progress checkboxes live here and in the group chat; update weekly. If a week slips, cut **S** items
first, never the SR-1…SR-10 core.

---

## 4. Definition of Done (per feature)

See `CONTRIBUTING.md` §5 — requirement satisfied, works on a clean XAMPP clone, security requirements
respected, validation/escaping/prepared statements in place, activity logged, peer-reviewed via PR.

---

## 5. QA Plan

1. **Functional matrix** — walk every FR-n in `docs/01-REQUIREMENTS.md` for each role (admin, staff, viewer) and record pass/fail.
2. **Security checklist** — execute all 15 ST cases in `docs/04-SECURITY-PLAN.md` §5; screenshot the evidence.
3. **Cross-role regression** — after each milestone, rerun the previous milestone's exit criteria.
4. **Clean-machine test (M6)** — one member clones the repo on a fresh XAMPP and follows README only; every snag found is fixed in the install guide.
5. **Demo data** — `seed.sql` plus 3–4 sample PDFs per category uploaded through the UI before the presentation.

## 6. Risks & Mitigations

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Uneven Git/PHP experience in the team | High | PHP concentrated in the code team (doc 06 §1a); everyone else contributes via QA, UI feedback and docs on GitHub |
| Security features treated as "later" | Medium | Each SR is attached to a milestone; Denise reviews every PR against SR-1…SR-10 |
| Upload edge cases break the demo | Medium | Strict whitelist + size cap early (M2); rehearsal with varied files |
| Merge conflicts from parallel work | Medium | Module ownership boundaries; pull `main` daily; small branches |
| Schedule slippage | Medium | Weekly checkpoint; drop E/S-priority items first |
| Laptop failure / lost work | Low | Push to GitHub daily; demo laptop + one backup laptop |

## 7. Tools

XAMPP (Apache + MySQL + PHP), VS Code, Git + GitHub, phpMyAdmin, Bootstrap 5 (CDN),
[draw.io](https://draw.io) or Mermaid for diagrams, Google Docs for slide drafting.
