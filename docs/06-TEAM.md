# 06 · Team

**Secure Document Management System — ITEC 85 (Information Security)**
Cavite State University – Don Severino delas Alas Campus · College of Engineering and Information Technology

## 1. Members

> **In plain words (updated Nov 2026):** writing the PHP is deliberately concentrated in one or two
> people so `main` stays clean. Most of the team works on **quality assurance, UI feedback,
> documentation, and the role-play demo** — real engineering roles whose work is visible on GitHub
> (issues, test evidence, doc commits) and counts toward grading, not "leftovers".

### 1a. Code & database team (small on purpose)

| Member | Role | Owns |
|---|---|---|
| **Kim Jerald Sialana** | Team lead · application code (auth, RBAC, documents, admin) · integration | all PHP under `app/` and `public/`, PR merges, repo |
| **Dyna Lunas** | Database owner (**SQL only — no PHP needed**) | `database/schema.sql`, `seed.sql`, `demo-data.sql`, ERD, staging DB imports (doc 09), query tuning |

*If another member wants to learn PHP, Kim pairs with them on small branches — otherwise nobody else
edits code. Everything below is non-code by design.*

### 1b. Non-code roles (most of the team)

| Member | Role | What it looks like on GitHub (grading evidence) |
|---|---|---|
| **Denise Valencia** | **QA & security testing lead** · plays the **Hacker** in the demo | executes ST-01…ST-15 (doc 04 §5) on the staging site; files bug reports + test evidence |
| **Edcel Santos** | **QA engineer** — functional testing · demo actor (viewer) | requirement-by-requirement checklist (FR-1…FR-29, doc 01) on staging; bug reports as Issues |
| **Mycah Layaban** | **UI/UX feedback lead** · demo actor (staff) | usability walkthroughs + design suggestions as Issues (screenshots welcome); checks Bootstrap consistency on staging |
| **Sofia Jhaye Peñaflor** | **Documentation & presentation lead** · demo actor (admin) | doc polish, slide drafts, printable-report checks; rehearsal notes |
| **Elijah Zyrus Tipo** | **Test-data & admin-flow tester** · demo actor (admin or viewer) | prepares demo accounts/files; tests admin pages on staging; findings as Issues |

> Casting in the demo is a proposal — swap roles freely to match strengths, then update this table.
> Kim coordinates; Denise owns "security compliance" reviews; everyone writes some docs.

## 2. Responsibility Areas

- **Team lead** — all application code, plan tracking, PR merges, unblocking, integration, final packaging.
- **Database** — schema changes go through Dyna; every schema PR updates `schema.sql` + doc 03 together; Dyna also handles staging DB imports (doc 09).
- **Security focal / QA lead** — reviews every PR against SR-1…SR-10 and the upload hardening checklist; owns the security test evidence (ST-01…ST-15); plays the Hacker in the demo (doc 07 §2).
- **QA** — testing happens on the staging site (doc 09): bug reports as GitHub Issues with steps-to-reproduce, expected vs. actual; **no direct code edits**.
- **UI/UX feedback** — suggestions as GitHub Issues with screenshots; the lead decides what gets implemented; **no direct code edits**.
- **Everyone** — doc updates alongside your findings, demo rehearsal attendance, test evidence for your area.

## 3. Work Agreements

1. **Sync:** short stand-up at least twice a week (day/time: *to be set by the group*).
2. **Channel:** group chat for day-to-day; GitHub Issues/PRs for anything about code.
3. **Response time:** acknowledge blockers within 24 h; blockers > 24 h escalate to Kim.
4. **Reviews:** every PR gets at least one review (module owner + Denise for security-relevant ones).
5. **Commits:** push small and often — daily pushes to `main`-bound branches; never commit credentials or uploaded files (see `CONTRIBUTING.md` §4).
6. **Contribution evidence:** commit history, PR reviews, Issues you filed (QA findings, UI suggestions), and executed test checklists all serve as each member's contribution record for grading — keep your work visible on GitHub.

## 4. Doc Ownership

| Doc | Maintainer |
|-----|------------|
| `README.md`, `docs/01-REQUIREMENTS.md` | Kim |
| `docs/02-ARCHITECTURE.md` | Kim + Dyna |
| `docs/03-DATABASE-DESIGN.md` | Dyna |
| `docs/04-SECURITY-PLAN.md` | Denise |
| `docs/05-DEVELOPMENT-PLAN.md` | Kim |
| `docs/07-PRESENTATION-PLAN.md` | Sofia + Mycah |
| `CONTRIBUTING.md` | Kim |
