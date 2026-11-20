# 00 · Start Here — Plain-English Guide

**Read this first if the other documents feel too technical.** This page explains the whole project in
everyday language. The other documents keep every technical detail, but each one now starts with a short
**"In plain words"** summary like this page, so you can get the gist fast and only dive deeper when you
actually need to.

---

## 1. What exactly is this website for?

Imagine a **locked filing cabinet for a school**, but on a web page:

- **Staff** log in and upload documents (PDFs, Word files, slides…) with a title, a category, and a short description.
- The **administrator** checks each new document and either **approves** it (everyone can see it) or **rejects** it (only the uploader and admins can see it, with a note why).
- **Viewers** (students / regular users) search the approved documents and download the ones they are allowed to see.
- Every important action — uploads, downloads, denied attempts, account changes — is written down in a **log** with who, what, when, and from where. Nothing happens "silently."

That's the whole product: **a safe, organized place to store and share school documents**, where only
the right people can reach each file and everything is recorded. That is also why this is the final
project for *Information Security* — the interesting part is the safety, not the file uploads.

## 2. Is it online, or only local?

**Local — it runs on your own laptop.** This is normal for a classroom project:

1. You install **XAMPP**, a free program that turns your computer into a mini web server (it gives you Apache + MySQL + PHP in one install).
2. You open the site in your browser at `http://localhost/SDMS/public/`.
3. At the final presentation, you demo it live from your laptop. No venue Wi-Fi needed.

Two clarifications people often mix up:

- Putting the **code** on GitHub does **not** put the **website** online. GitHub only stores the code.
- A real online deployment (rented server, domain, HTTPS) is possible later — it goes on the
  "Future work" slide, but the manual does not require it.

## 3. What tools do we use, and why?

| Tool | What it is, in plain words | Why we chose it |
|------|----------------------------|-----------------|
| **XAMPP** | One installer that gives you Apache (web server) + MySQL (database) + PHP at once | Free, standard in classrooms, matches our labs |
| **PHP** | The language that runs on the server: checks passwords, saves files, builds pages | We already practiced it in ITEC labs |
| **MySQL** | The database — a set of smart spreadsheets the site reads and writes | Same — lab practice |
| **Bootstrap 5** | A free CSS toolkit: ready-made styles for buttons, tables, forms, menus | Professional look without designing everything by hand |
| **Git + GitHub** | Saves every version of the code; lets 7 people work at the same time | Teamwork + visible contribution history |

## 4. Questions you might be asking

**"Can we use Supabase?"** — Supabase is a real, good service (an online database with login features
built in). For *this* course, we're not using it, for three reasons: (1) the whole point of the manual
is that **we build the security ourselves** — password hashing, prepared statements, session checks,
access control — while Supabase does much of that *for* you, so the professor couldn't see our own
security work; (2) it would force the whole site online and add new tools to learn mid-project;
(3) the demo would depend on venue internet. It stays on the "Future work" slide instead.

**"Can we use React?"** — Technically yes, but it would make this project harder, not better. React
needs Node.js build tools plus a separate PHP "API" behind it — double the work for the same features.
PHP pages + Bootstrap cover everything the manual asks for, with tools we already know from the labs.
If someone wants React practice, that's a personal side project or a future-work slide — not the core.

**"Are we really creating the database ourselves?"** — Yes, but the hard part is already written for
you. Two files exist in this repo: `database/schema.sql` (the blueprint — creates all tables) and
`database/seed.sql` (starter data — the 3 roles, 5 categories, and the first admin account). You just
open phpMyAdmin, create an empty database called `sdms`, click **Import**, and pick those two files.
After that, PHP talks to the database automatically.

**"What is Bootstrap 5?"** — A free style kit for web pages. Instead of writing all CSS yourself, you
add class names like `btn btn-primary` to your HTML and instantly get good-looking buttons, tables,
cards, and menus. It's just a CSS file (plus a small JS file) loaded from a CDN link. Version 5 is
simply the latest major release.

**"What are `.agents/` and `skills-lock.json` in the repo?"** — A guidebook for AI coding assistants
installed by the team lead. It does **not** run inside our app. Leave the files as they are and commit
them, so everyone's assistant gives the same modern advice.

## 5. Glossary — the words the other docs use

| Term | Plain meaning |
|------|---------------|
| XAMPP | All-in-one installer: Apache + MySQL + PHP on your PC |
| Apache | The "waiter" that takes browser requests and serves pages |
| PHP | The server-side language: builds pages, checks passwords, saves files |
| MySQL | The database program (the app's ledger) |
| phpMyAdmin | A web page for managing MySQL — we use it to import our SQL files |
| Table | One "smart spreadsheet" in the database (e.g., `users`, `documents`) |
| Schema | The blueprint of all tables |
| Seed data | The starting rows: 3 roles, 5 categories, the first admin |
| ERD | A drawing showing the tables and how they connect |
| DDL | The SQL script that creates tables (`schema.sql`) |
| Prepared statement | The safe way to run queries, so typed tricks like `' OR 1=1` do nothing |
| Password hash | An unreadable, one-way scramble of a password — the only thing we store |
| Session | The server's memory of "this browser is logged in as Kim" |
| RBAC | Role-Based Access Control: admin / staff / viewer see different things |
| CSRF token | A hidden ticket in every form proving the request came from our own site |
| Audit log | The permanent record of who did what, when, from where |
| Soft delete | "Deleted" items are only flagged, not erased — recoverable |
| CDN | A public server that hosts Bootstrap's files so we can just link to them |
| Localhost | "This computer" — the server running on your own machine |

## 6. Your first task (do this today)

1. Install [XAMPP](https://www.apachefriends.org/) — next, next, finish.
2. Open the **XAMPP Control Panel** and start **Apache** and **MySQL**.
3. Get the project folder into `C:\xampp\htdocs\SDMS` (clone from GitHub, or copy it).
4. Open `http://localhost/phpmyadmin` → create database `sdms` → **Import** `database/schema.sql` → **Import** `database/seed.sql`.
5. Open `http://localhost/SDMS/public/` — you should see the login page.

Stuck? Post a screenshot of the error in the group chat — that's normal; everyone gets stuck on setup.

## 7. What to read next, in order

| Order | Document | What it gives you |
|-------|----------|-------------------|
| 1 | This page | The big picture |
| 2 | [README](../README.md) | Project summary + install steps |
| 3 | [05 – Development Plan](05-DEVELOPMENT-PLAN.md) | The calendar: what to build, in what order, who starts with what |
| 4 | [06 – Team](06-TEAM.md) | Roles and work agreements |
| 5 | [01 – Requirements](01-REQUIREMENTS.md) | Everything the site must do (skim the plain intro; details are reference) |
| 6 | [02 – Architecture](02-ARCHITECTURE.md) | The floor plan of the code |
| 7 | [03 – Database Design](03-DATABASE-DESIGN.md) | The tables + the ready-made SQL |
| 8 | [04 – Security Plan](04-SECURITY-PLAN.md) | How each of the 10 security rules is implemented and tested |
| 9 | [07 – Presentation Plan](07-PRESENTATION-PLAN.md) | The demo-day script |

**Remember:** no requirement was removed to make things easier to read — every FR/SR/NFR is still in
docs 01–04, just with friendlier signposts.
