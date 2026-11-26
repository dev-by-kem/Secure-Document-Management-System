# AGENTS.md — SDMS Project Instructions

Instructions for all AI coding agents working in the Secure Document Management System (SDMS) repository.

## 1. Commit and Push After Every Prompt

At the end of every prompt that changes files in the repository:

1. Verify your changes first (`php -l` on modified PHP files and `powershell -NoProfile -ExecutionPolicy Bypass -File tests\backend-smoke.ps1` when XAMPP is available).
2. Stage, commit, and push your changes to the current remote branch (`git add -A`, `git commit`, `git push`).
3. **Commit message rule:** Always use a **short, summarized message** explaining what happened or what changed, following [CONTRIBUTING.md](CONTRIBUTING.md):
   - Format: `type(scope): concise summary` (imperative, ~50–72 characters).
   - Types: `feat`, `fix`, `docs`, `style`, `refactor`, `test`, `chore`.
   - Examples:
     - `feat(ui): add mobile drawer, pagination, and inline upload errors`
     - `fix(auth): preserve session guard on document download`
     - `docs(plan): update UI improvement checklist`
4. Never commit secrets or runtime artifacts (`app/config/config.php`, `.env`, `storage/uploads/*`, `logs/*`).
5. Never run `git push --force` unless explicitly requested by the user.

## 2. Project Guardrails

Before editing code, follow the safety and architecture rules in:
- [docs/01-REQUIREMENTS.md](docs/01-REQUIREMENTS.md) — Functional and security requirements (`FR-*`, `SR-*`).
- [docs/08-FRONTEND-GUIDE.md](docs/08-FRONTEND-GUIDE.md) — Frontend layers, accessibility, dark mode, and safety rules.
- [CONTRIBUTING.md](CONTRIBUTING.md) — Code conventions and commit standards.

Never remove or bypass `require_login()`, `require_role([...])`, `csrf_field()`, `csrf_check()`, `e(...)` HTML escaping, PDO prepared statements, download authorization gates, upload validation, or audit logging.
