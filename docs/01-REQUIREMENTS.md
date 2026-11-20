# 01 · Requirements Specification

**Secure Document Management System — ITEC 85 (Information Security)**

This document distills the professor's project manual ([source PDF](Secure-Document-Management-System.pdf))
into numbered, testable requirements. The IDs (FR-n, SR-n, NFR-n) are referenced by every other doc,
task, and test case, so we can always trace a feature back to the manual.

> **In plain words:** this page is the *master checklist* of everything the website must do. Every item
> has an ID (FR-7, SR-5, …) so we can point at it in code, tests, and the presentation. **FR = Functional
> Requirement** — something the site must *do*. **SR = Security Requirement** — a rule that keeps it
> *safe* (all ten come straight from the professor's manual). **NFR** = how well it should behave
> (speed, usability, file limits). You don't need to memorize the numbers: find your module, build what
> it says, and check it off.

Priority legend: **M** = must have (explicitly in the manual) · **S** = should have (recommended) ·
**E** = enhancement (if time allows).

---

## 1. Purpose & Scope

**Purpose** (manual §A–§B). Provide a centralized platform for storing and managing digital documents
where:

- uploading and retrieving files is organized and easy;
- file access is secured through user authentication;
- unauthorized users cannot view or download private documents;
- the confidentiality and integrity of uploaded files are maintained.

The system stores academic files: assignments, research papers, project documents, reports, and other
school-related materials.

**In scope**

- Web application on XAMPP (Apache + PHP 8 + MySQL)
- Three roles: Administrator, Staff (Employee), Viewer
- Document upload with metadata (title, category, description), search, view, download, edit, delete
- Optional approval workflow (Pending → Approved / Rejected)
- Document category management
- Admin dashboard, document reports (generate + print)
- Activity logging and access monitoring
- The 10 security requirements of manual §E

**Out of scope** (documented as future work)

- Email notifications and email-based password reset
- Cloud deployment / public hosting
- Mobile application
- Real-time co-editing of documents
- Antivirus scanning service integration

---

## 2. User Roles & Access Policy

Manual §D defines three roles (the manual's RBAC requirement mentions *students, staff, and
administrators* — the Viewer role covers students / general users):

| Role ID | UI label | Summary (manual §D) |
|---------|-----------|---------------------|
| `admin` | Administrator | Manages user accounts, assigns permissions, controls all updates, manages documents |
| `staff` | Employee (Staff) | Uploads, organizes, and updates documents |
| `viewer` | Viewer | Can only view and download documents |

**Permission matrix** (derived from manual §C feature lists):

| Action | Admin | Staff | Viewer |
|--------|:-----:|:-----:|:------:|
| Login / logout / view own profile | ✅ | ✅ | ✅ |
| Upload documents | ✅ (manages all) | ✅ | ❌ |
| Search & filter documents | ✅ | ✅ | ✅ |
| View / download authorized documents | ✅ (all) | ✅ | ✅ |
| Edit document details | ✅ (any) | ✅ (own) | ❌ |
| Delete documents | ✅ (any) | ✅ (own, when permitted) | ❌ |
| Approve / reject documents | ✅ | ❌ | ❌ |
| Manage document categories | ✅ | ❌ | ❌ |
| Manage users, roles & account status | ✅ | ❌ | ❌ |
| View activity logs | ✅ (all) | ✅ (own) | ❌ |
| Dashboard statistics | ✅ | ❌ | ❌ |
| Generate & print reports | ✅ | ❌ | ❌ |

**Access policy — what "authorized documents" means** (defined so implementation and tests agree):

| Document status | Uploader (owner) | Admin | Other staff & viewers |
|-----------------|:---:|:---:|:---:|
| Pending | view + download | view + download + review | ❌ blocked |
| Approved | view + download | view + download | view + download |
| Rejected | view (with remarks) | view + download | ❌ blocked |

---

## 3. Functional Requirements

### 3.1 Authentication & profile (manual §C — Employee/Admin modules)

| ID | Requirement | Pri |
|----|-------------|:---:|
| FR-1 | Users log in securely with username and password | M |
| FR-2 | Users log out securely, ending the server session | M |
| FR-3 | Any attempt to open a protected page while logged out redirects to the login page | M |
| FR-4 | Users view their personal profile | M |
| FR-5 | Users change their own password (requires current password) | S |

### 3.2 Document handling — Staff/Employee (manual §C)

| ID | Requirement | Pri |
|----|-------------|:---:|
| FR-6 | Staff upload digital documents, providing title, category, and description | M |
| FR-7 | The system validates every upload (type, size, content) before storing | M |
| FR-8 | Staff view the list of their uploaded documents | M |
| FR-9 | Staff search and filter documents (by title, category, status, date) | M |
| FR-10 | Staff download authorized documents | M |
| FR-11 | Staff edit the details of their own documents | M |
| FR-12 | Staff delete their own documents when permitted | M |
| FR-13 | Staff see document status — Pending / Approved / Rejected — when approval is required | M |
| FR-14 | Staff view their document upload history | M |
| FR-15 | Staff view their own activity logs | M |
| FR-16 | Rejected documents carry the administrator's remarks explaining the rejection | S |

### 3.3 Document handling — Viewer (manual §D)

| ID | Requirement | Pri |
|----|-------------|:---:|
| FR-17 | Viewers view and search approved documents | M |
| FR-18 | Viewers download authorized (approved) documents | M |

### 3.4 Administration (manual §C — Administrator module)

| ID | Requirement | Pri |
|----|-------------|:---:|
| FR-19 | Admin dashboard showing the number of users and uploaded documents | M |
| FR-20 | Admin adds, edits, and manages user accounts | M |
| FR-21 | Admin assigns user roles and access permissions | M |
| FR-22 | Admin activates and deactivates accounts | M |
| FR-23 | Admin views all uploaded documents | M |
| FR-24 | Admin reviews and verifies submitted documents | M |
| FR-25 | Admin approves or rejects documents when approval is required | M |
| FR-26 | Admin edits or removes documents when necessary | M |
| FR-27 | Admin manages document categories (add, edit, delete) | M |
| FR-28 | Admin monitors document access and user activities | M |
| FR-29 | Admin generates and prints document reports | M |
| FR-30 | Approval requirement is configurable per category (`requires_approval` flag) | S |
| FR-31 | Reports can be filtered by date range and category | S |

### 3.5 System-generated logging (manual §E-8, §C)

| ID | Requirement | Pri |
|----|-------------|:---:|
| FR-32 | Record login success, login failure, and logout events | M |
| FR-33 | Record upload, view, download, edit, delete, approve, and reject actions on documents | M |
| FR-34 | Record user and category administration actions | M |
| FR-35 | Every log entry stores timestamp, user, action, target, IP address, and user agent | M |
| FR-36 | Admin views and filters logs; logs cannot be edited or deleted through the UI | M |

---

## 4. Security Requirements (manual §E, verbatim → target → acceptance)

| ID | Manual requirement | Implementation target | Acceptance check |
|----|--------------------|------------------------|------------------|
| SR-1 | **Password Hashing** — securely store user passwords | `password_hash()` / `password_verify()` (bcrypt default); never plaintext or MD5/SHA1 | Database shows only hashes; login works; grep finds no plaintext password |
| SR-2 | **Authentication** — require login before accessing the system | Session guard on every protected page; redirect to login | Direct URL to a protected page while logged out → redirected to login |
| SR-3 | **Role-Based Access Control** — different permissions for students, staff, administrators | `roles` table + deny-by-default `require_role()` guard | Viewer cannot open admin pages or upload; staff cannot approve |
| SR-4 | **Data Confidentiality** — protect personal and academic information | Files stored outside the web root; download gate checks session, role, and status policy | Direct file URL fails; unauthorized users cannot view or download private documents |
| SR-5 | **SQL Injection Prevention** — use prepared statements | PDO prepared statements for 100% of queries | `' OR 1=1 --` login attempt fails safely; no query concatenates user input |
| SR-6 | **Input Validation** — validate user inputs | Server-side whitelist validation, MIME check on files, output escaping (XSS), CSRF tokens | Bad input rejected with feedback; `<script>` probe rendered inert; POST without CSRF token rejected |
| SR-7 | **Session Management** — secure user sessions | `session_regenerate_id(true)` on login, HttpOnly + SameSite cookie, 30-min idle timeout, secure logout | Old session invalid after login/logout; idle timeout returns to login |
| SR-8 | **Activity Logs** — record important system activities | Append-only `activity_logs` + `document_access_logs` (FR-32…FR-36) | Key actions appear with user, IP, timestamp |
| SR-9 | **Account Management** — manage and deactivate accounts | Admin CRUD + activate/deactivate; deactivated users blocked at login | Deactivated user cannot log in; reactivation restores access |
| SR-10 | **Data Integrity** — prevent unauthorized changes or deletion | Foreign keys, transactions, SHA-256 file checksums, soft delete, confirmation dialogs | Deleted documents recoverable in DB; checksum mismatch detectable; unauthorized edits rejected |

---

## 5. Non-Functional Requirements

| ID | Requirement |
|----|-------------|
| NFR-1 | **Usability** — clear forms, success/error feedback, confirmation dialogs for destructive actions |
| NFR-2 | **Portability** — runs on a stock XAMPP install; setup = clone + import SQL + edit one config file |
| NFR-3 | **Performance** — common operations respond within ~2 seconds on lab hardware |
| NFR-4 | **File limits** — max upload size 10 MB (configurable); allowed types: pdf, doc/docx, xls/xlsx, ppt/pptx, txt, jpg/png |
| NFR-5 | **Maintainability** — shared libraries (`app/lib/`), consistent naming, documented schema |
| NFR-6 | **Auditability** — any important action can be reconstructed from the logs alone |
| NFR-7 | **Backup** — documented procedure: MySQL dump + copy of `storage/uploads/` |
| NFR-8 | **Encoding** — `utf8mb4` throughout so Filipino names (e.g., *Peñaflor*) display correctly |

---

## 6. Approval Workflow (manual §C–§G)

- Each category has an `requires_approval` flag (default behavior matches the manual: approval *when required*).
- On upload: if the category requires approval → status **Pending**; otherwise → **Approved** directly.
- Admin reviews pending documents: **Approve** (visible/downloadable to everyone authorized) or
  **Reject** (with remarks, visible to owner and admin only).
- Manual §G workflow end-to-end: admin creates accounts and assigns roles → staff log in → staff upload
  documents with title, category, description → system validates and securely stores them → authorized
  users search, view, download → admin manages access and monitors activities through activity logs.

---

## 7. Constraints & Assumptions

1. Demo deployment is local (XAMPP). HTTPS is recommended for any real deployment (see Security Plan §8).
2. There is **no self-registration** — the administrator creates accounts, matching manual §G.
3. Single server, class-scale user base (tens of users, thousands of files max).
4. One MySQL database `sdms`; uploaded file binaries live on disk, metadata in the database.
5. The team collaborates through Git/GitHub (see `CONTRIBUTING.md`).

---

## 8. Traceability to the Manual

| Manual section | Covered by |
|----------------|-----------|
| §A Project Description | §1 Purpose & Scope |
| §B Objectives | §1 Purpose & Scope; SR-2…SR-4 |
| §C Main System Features (Employee / Administrator modules) | FR-1…FR-29 |
| §D User Roles | §2 User Roles & Access Policy |
| §E Security Requirements (1–10) | SR-1…SR-10 |
| §G Example System Workflow | §6 Approval Workflow; FR-6…FR-18 |
