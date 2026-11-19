# 06 · Team

**Secure Document Management System — ITEC 85 (Information Security)**
Cavite State University – Don Severino delas Alas Campus · College of Engineering and Information Technology

## 1. Members

| # | Member | Suggested project role | Primary modules |
|---|--------|------------------------|-----------------|
| 1 | **Kim Jerald Sialana** | Team lead · Backend (auth, RBAC, sessions) · Integration | `lib/auth.php`, `lib/rbac.php`, repo & PR review |
| 2 | **Dyna Lunas** | Database designer & schema owner | `database/`, ERD, query support |
| 3 | **Edcel Santos** | Backend developer — document module | `documents/*`, upload validation, download gate |
| 4 | **Denise Valencia** | Security focal & QA | `lib/logger.php`, `admin/logs.php`, security tests ST-01…ST-15 |
| 5 | **Elijah Zyrus Tipo** | Backend developer — admin module | users, categories, approvals |
| 6 | **Mycah Layaban** | Frontend developer — staff/viewer experience | login, profile, document pages, `assets/` |
| 7 | **Sofia Jhaye Peñaflor** | Frontend developer — admin experience | dashboard, approvals UI, log viewer, printable reports |

> Roles are proposals so no two people own the same files — swap freely to match strengths, then update
> this table. Kim coordinates; Denise owns "security compliance" reviews; everyone writes some docs.

## 2. Responsibility Areas

- **Team lead** — plan tracking, PR merges, unblocking, integration, final packaging.
- **Database** — schema changes go through Dyna; every schema PR updates `schema.sql` + doc 03 together.
- **Security focal** — reviews every PR against SR-1…SR-10 and the upload hardening checklist; owns the security test evidence for the presentation.
- **Frontend** — consistent Bootstrap look, feedback messages, confirmation dialogs; no logic decisions without the module's backend owner.
- **Everyone** — activity logs for your module's actions, code comments, doc updates in the same PR.

## 3. Work Agreements

1. **Sync:** short stand-up at least twice a week (day/time: *to be set by the group*).
2. **Channel:** group chat for day-to-day; GitHub Issues/PRs for anything about code.
3. **Response time:** acknowledge blockers within 24 h; blockers > 24 h escalate to Kim.
4. **Reviews:** every PR gets at least one review (module owner + Denise for security-relevant ones).
5. **Commits:** push small and often — daily pushes to `main`-bound branches; never commit credentials or uploaded files (see `CONTRIBUTING.md` §4).
6. **Contribution evidence:** commit history + PR reviews serve as each member's contribution record for grading — keep your work visible on GitHub.

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
