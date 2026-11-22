# Secure Document Management System (SDMS)

**ITEC 85 – Information Security · Final Group Project**
Cavite State University – Don Severino delas Alas Campus, Indang, Cavite
College of Engineering and Information Technology

> A web-based document management platform that lets users upload, store, organize, retrieve, and manage
> digital documents in a **secure environment** — where only **authorized users** can access, download,
> modify, or delete specific files.

> 🧭 **New here? Everything feels too technical?** Read the plain-English guide first:
> [docs/00-START-HERE.md](docs/00-START-HERE.md) — what the site is for, where it runs, a glossary,
> and FAQ (Supabase? React? Do we build the database ourselves?).

## 📌 Project Summary

- **Problem:** Ordinary file storage exposes academic files (assignments, research papers, project
  documents, reports, and other school materials) to unauthorized access, accidental deletion, and
  exposure of confidential content.
- **Solution:** A centralized PHP + MySQL web application with strong authentication, role-based access
  control, an approval workflow, and a full audit trail.
- **Focus:** Security first. This is the capstone output of an Information Security course, so every
  feature is built around the 10 security requirements in the project manual.

## ✨ Main Features

### Employee (Staff) Module
- Secure login and logout, personal profile
- Upload digital documents with title, category, and description
- View, search, and filter documents
- Download authorized documents
- Edit document details; delete own documents when permitted
- Track document status — Pending / Approved / Rejected
- View upload history and activity logs

### Administrator Module
- Dashboard showing the number of users and uploaded documents
- Add, edit, and manage user accounts
- Assign user roles and access permissions
- View all uploaded documents; review and verify submissions
- Approve or reject documents when approval is required
- Edit or remove documents when necessary
- Manage document categories
- Monitor document access and user activities
- Generate and print document reports

### Viewer Role
- Can only view and download documents they are authorized to access

## 🔐 Security Highlights

| # | Requirement (manual §E) | How SDMS implements it |
|---|--------------------------|------------------------|
| 1 | Password Hashing | `password_hash()` (bcrypt/Argon2) — no plaintext, no MD5/SHA1 |
| 2 | Authentication | Mandatory login before any page; server-side session guards |
| 3 | Role-Based Access Control | Admin / Staff / Viewer with deny-by-default checks |
| 4 | Data Confidentiality | Files stored outside the web root; gated download endpoint |
| 5 | SQL Injection Prevention | 100% PDO prepared statements |
| 6 | Input Validation | Server-side whitelist validation + output escaping (XSS) + CSRF tokens |
| 7 | Session Management | Session regeneration, HttpOnly/SameSite cookies, idle timeout |
| 8 | Activity Logs | Append-only audit trail of all important actions |
| 9 | Account Management | Admin account CRUD with activate/deactivate |
| 10 | Data Integrity | Foreign keys, transactions, SHA-256 file checksums, soft delete |

Full implementation details: [Security Plan](docs/04-SECURITY-PLAN.md).

## 🛠 Technology Stack

| Layer | Choice | Why |
|-------|--------|-----|
| Frontend | HTML5, CSS3, Bootstrap 5, vanilla JS | Familiar, fast to build, responsive |
| Backend | PHP 8.x | Continuation of our ITEC lab work |
| Database | MySQL / MariaDB | Continuation of our ITEC lab work |
| Server | Apache via XAMPP | Standard local development environment |
| Version control | Git + GitHub | Team collaboration and contribution history |

## 🚀 Getting Started

1. Install [XAMPP](https://www.apachefriends.org/) (PHP 8.x + MySQL).
2. Clone this repository into your XAMPP web root:
   ```
   C:\xampp\htdocs\SDMS
   ```
3. Start **Apache** and **MySQL** from the XAMPP Control Panel.
4. Create the database:
   - Open `http://localhost/phpmyadmin`
   - Import `database/schema.sql`, then `database/seed.sql`, then `database/demo-data.sql` (realistic sample documents)
5. Copy `app/config/config.sample.php` to `app/config/config.php` and set your DB credentials.
6. Open `http://localhost/SDMS/public/` and log in with the seeded administrator account.

## 📁 Repository Layout

```
SDMS/
├── public/            # browser-facing pages (the only web-visible folder)
├── app/               # config + shared libraries (auth, rbac, db, logger, ...)
├── .github/workflows/ # auto-deploy: every push to main lands on the staging site (docs/09)
├── database/          # schema.sql + seed.sql
├── storage/uploads/   # uploaded files — NOT directly reachable from the browser
├── logs/              # optional runtime logs (audit data lives in MySQL)
├── docs/              # planning & design documentation
└── README.md
```

## 📚 Documentation

| Doc | Contents |
|-----|----------|
| [00 – Start Here](docs/00-START-HERE.md) | Plain-English guide: purpose, local vs online, glossary, FAQ |
| [01 – Requirements](docs/01-REQUIREMENTS.md) | Functional + security requirements, traced to the project manual |
| [02 – Architecture](docs/02-ARCHITECTURE.md) | Stack rationale, modules, folder structure, request & approval flows |
| [03 – Database Design](docs/03-DATABASE-DESIGN.md) | ERD, table specifications, DDL, seed data |
| [04 – Security Plan](docs/04-SECURITY-PLAN.md) | Threat model, SR-1…SR-10 implementation playbook, security test checklist |
| [05 – Development Plan](docs/05-DEVELOPMENT-PLAN.md) | Milestones, task assignments, timeline, QA & risks |
| [06 – Team](docs/06-TEAM.md) | Members, suggested roles, work agreements |
| [07 – Presentation Plan](docs/07-PRESENTATION-PLAN.md) | Slide outline, live-demo script, Q&A preparation |
| [08 – Frontend Guide](docs/08-FRONTEND-GUIDE.md) | Frontend sample base: tokens, dark mode, dialogs, responsive tables, redesign rules |
| [09 – Staging Server](docs/09-STAGING-DEPLOY.md) | Shared online test site: one-time setup, auto-deploy workflow, team workflow, security rules |
| [Contributing](CONTRIBUTING.md) | Branching, commit style, code conventions, review rules |
| Project manual (source) | `docs/Secure-Document-Management-System.pdf` |

## 👥 Team — ITEC 85 Group

| Member | Suggested role |
|--------|----------------|
| Kim Jerald Sialana | Team lead · backend (auth & RBAC) · integration |
| Dyna Lunas | Database design & schema owner |
| Edcel Santos | Backend · document module |
| Denise Valencia | Security focal & QA |
| Elijah Zyrus Tipo | Backend · admin module & reports |
| Mycah Layaban | Frontend · staff/viewer experience |
| Sofia Jhaye Peñaflor | Frontend · admin dashboard & reports |

*Role assignments are proposals — see [06 – Team](docs/06-TEAM.md) and adjust to each member's strengths.*

## 📄 Status

🚧 **Planning phase** — requirements, architecture, database design, security plan, and task breakdown
are being finalized before implementation starts. See the [Development Plan](docs/05-DEVELOPMENT-PLAN.md).
