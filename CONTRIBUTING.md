# Contributing to SDMS

Working agreements for the ITEC 85 group so everyone can build in parallel without stepping on each other.

## 1. Branching model

- `main` always works — never push broken code directly to it.
- Do your work on a feature branch named after the module:

  ```
  feature/auth-login          feature/docs-upload
  feature/admin-users         feature/security-csrf
  fix/login-redirect-loop
  ```

- One branch = one feature or fix. Keep branches small (merge at least every 2–3 days).
- Merge through a Pull Request into `main`; the team lead or security focal reviews before merging.

## 2. Commit messages

Use short, imperative messages in this format:

```
type(scope): summary in ~50 chars
```

| Type | Use for |
|------|---------|
| `feat` | new feature |
| `fix` | bug fix |
| `docs` | documentation only |
| `style` | formatting, no logic change |
| `refactor` | code change that neither fixes nor adds |
| `test` | test cases / QA scripts |
| `chore` | config, .gitignore, setup |

Examples:
- `feat(auth): add login with password_verify and session guard`
- `fix(download): block viewer access to pending documents`
- `docs(db): add activity_logs table to ERD`

## 3. Code conventions

**PHP**
- Follow PSR-12 basics: 4-space indent, opening brace on the same line for control structures.
- File naming: lowercase with underscores for page scripts (`upload_document.php`), camelCase for functions, `$snake_case` for variables.
- **Every SQL query uses PDO prepared statements — no exceptions.**
- All state-changing forms carry a CSRF token; all output goes through `e()` (htmlspecialchars).
- Top of every protected page: `require_login()` / `require_role([...])`.

**SQL**
- Table and column names: lowercase `snake_case`, plural table names (`users`, `documents`).
- Every change to the schema updates `database/schema.sql` (and `docs/03-DATABASE-DESIGN.md`) in the same PR.

**HTML/CSS/JS**
- Bootstrap 5 classes first; custom CSS goes in `public/assets/css/custom.css`.
- No inline JavaScript; put scripts in `public/assets/js/`.

## 4. What never gets committed

- `app/config/config.php` (real credentials) — commit only `config.sample.php`.
- Uploaded files (`storage/uploads/`) and runtime logs (`logs/`) — ignored via `.gitignore`.
- `phpinfo()` dumps, test scripts with real passwords, `.env`, `vendor/`.

## 5. Definition of Done

A feature is done when:

- [ ] It satisfies the requirement IDs it claims (see `docs/01-REQUIREMENTS.md`).
- [ ] It works on a clean XAMPP checkout (fresh clone + import SQL + config).
- [ ] Relevant security requirements (SR-1…SR-10) are respected and checked.
- [ ] Inputs are validated server-side; outputs escaped; queries prepared.
- [ ] Important actions write activity log entries.
- [ ] UI shows feedback for success and failure (no silent errors, no raw MySQL errors).
- [ ] Peer-reviewed by at least one teammate and merged via PR.

## 6. Team rhythm

- Short sync meeting at least twice a week (see `docs/06-TEAM.md`).
- Update the group chat when a PR is open or a blocker appears — blockers older than 24 h go to the team lead.
- Keep `docs/05-DEVELOPMENT-PLAN.md` checkboxes current so progress is visible to everyone.
