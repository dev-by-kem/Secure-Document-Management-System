# SDMS Semantic Color and UI Cleanup Implementation Brief

**Purpose:** Give a coding AI an implementation-ready plan for making SDMS cleaner, easier to scan, and more consistent while preserving the existing Cavite State University (CvSU) green-and-gold identity.

**Audience:** A coding AI or developer who will inspect, implement, test, and review the changes.

**Status:** Approved implementation brief. The coding AI must inspect the current source before editing and must not assume that line numbers or markup are unchanged.

**Primary visual reference:** The supplied clean file-browser reference. Use it as inspiration for spacing, hierarchy, quiet borders, compact metadata, and restrained surfaces. Do not copy its branding, colors, wording, or layout literally.

---

## 1. Scope and design intent

Improve the authenticated SDMS interface with a calm, clean document-management visual language:

- Keep CvSU emerald green and warm gold as the institutional brand anchors.
- Add a restrained semantic palette for success, pending, warning, danger, information, and neutral states.
- Make activity and audit actions recognizable at a glance through text labels, icons, and semantic badge colors.
- Make documents and people easier to scan by improving hierarchy and reducing visual noise.
- Show real names as the primary identity and usernames/emails as secondary metadata.
- Keep initials-only avatars. Do **not** implement profile-picture uploads in this task.
- Use title case for visible roles and human-readable labels for ordinary users.
- Preserve dark mode, keyboard navigation, responsive behavior, and no-JavaScript fallbacks.

This is a UI/UX improvement. It must not weaken authentication, authorization, CSRF protection, upload validation, download authorization, file integrity checks, or audit logging.

---

## 2. Project context

SDMS is a PHP 8 + MySQL/MariaDB application using Bootstrap 5, vanilla JavaScript, shared PHP includes, and a token-based CSS system.

Important files:

- Shared shell: `app/includes/header.php`, `app/includes/nav.php`, `app/includes/footer.php`
- Shared helpers: `app/lib/helpers.php`
- Authentication: `app/lib/auth.php`
- Design tokens: `public/assets/css/tokens.css`
- Base styles: `public/assets/css/base.css`
- Components: `public/assets/css/components.css`
- CSS import hub: `public/assets/css/custom.css`
- Shared behavior: `public/assets/js/app.js`
- Main dashboard: `public/dashboard.php`
- Document directory: `public/documents/index.php`
- Document view: `public/documents/view.php`
- Approval workflow: `public/admin/approvals.php`
- User management: `public/admin/users.php`
- Admin dashboard: `public/admin/dashboard.php`
- Audit logs: `public/admin/logs.php`
- Seed users: `database/seed.sql`
- Demo data: `database/demo-data.sql`
- Existing safety plan: `docs/10-UI-UX-IMPROVEMENT-PLAN.md`
- Frontend rules: `docs/08-FRONTEND-GUIDE.md`
- Backend smoke tests: `tests/backend-smoke.ps1`

The system already has a `users.full_name` field and an initials-based `avatar()` helper. Reuse those existing structures.

---

## 3. Non-negotiable safety rules

Before editing any file, read the current file and preserve surrounding behavior.

Never remove or bypass:

1. `require_login()` or `require_role([...])` guards.
2. `csrf_field()` from state-changing forms.
3. `csrf_check()` from POST handlers.
4. `e(...)` escaping around user-controlled output.
5. POST for mutations and GET for search/filter forms.
6. Download authorization checks.
7. `can_access_document()` policy checks.
8. Upload extension, MIME, size, and integrity validation.
9. Server-generated storage filenames.
10. Activity and access logging.
11. Session timeout, regeneration, and logout behavior.
12. The no-JavaScript form and navigation fallbacks.
13. Existing reduced-motion behavior.
14. Existing dark-mode support.

Do not implement profile-image uploads, avatar file storage, or a new profile-image database column as part of this task.

Do not change role authorization because a role badge or label changes visually. Server-side authorization remains authoritative.

Do not expose raw password hashes or change password-storage behavior.

---

## 4. Design tokens and semantic color system

Add or refine semantic tokens in `public/assets/css/tokens.css`. Preserve the current CvSU brand tokens and dark-mode equivalents.

Recommended semantic roles:

| Meaning | Light-mode direction | Typical use |
|---|---|---|
| Brand / primary | Existing CvSU emerald | Primary buttons, active navigation, institutional emphasis |
| Accent / attention | Existing CvSU warm gold or amber | Pending review, important attention, academic accent |
| Success | Existing accessible green | Approved, granted, successful, active |
| Danger | Accessible red | Rejected, denied, failed, destructive actions |
| Warning | Amber/orange distinct from success | Rate-limited, needs review, incomplete, caution |
| Info | Accessible blue | Informational activity, upload/download context |
| Neutral | Slate/gray | Inactive, draft, archived, unknown, technical metadata |

Use soft tinted backgrounds with readable foreground text rather than highly saturated solid badges. Example structure:

```css
--sdms-success: ...;
--sdms-success-soft: ...;
--sdms-danger: ...;
--sdms-danger-soft: ...;
--sdms-warning: ...;
--sdms-warning-soft: ...;
--sdms-info: ...;
--sdms-info-soft: ...;
--sdms-neutral: ...;
--sdms-neutral-soft: ...;
```

Requirements:

- Preserve sufficient contrast for text and icons.
- Do not use color as the only meaning signal.
- Every semantic state must include readable text and, where useful, an icon.
- Provide dark-mode token overrides rather than hard-coding light-mode colors into shared components.
- Do not introduce a rainbow effect or assign arbitrary colors to unrelated content.

---

## 5. Action badge system

The current audit action codes should become human-readable, semantic badges. Do not remove the raw action values from the database or logging system.

Create a single shared mapping, preferably in `app/lib/helpers.php`, so all pages use the same label, icon, and CSS class. The helper should safely escape dynamic values and return consistent markup.

A suitable shape is:

```php
function action_badge(string $action): string
{
    // Return a span with:
    // - semantic class
    // - Bootstrap icon
    // - readable label
    // - optional title/data-action-code for admin detail
}
```

Recommended mapping:

| Raw action | Visible label | Semantic class | Icon direction |
|---|---|---|---|
| `LOGIN_SUCCESS` | Login successful | success | `bi-box-arrow-in-right` |
| `LOGIN_FAILED` | Login failed | danger | `bi-shield-x` |
| `LOGIN_DENIED_INACTIVE` | Login blocked | danger or warning | `bi-person-slash` |
| `LOGIN_THROTTLED` | Login temporarily limited | warning | `bi-hourglass-split` |
| `LOGOUT` | Signed out | neutral | `bi-box-arrow-right` |
| `UPLOAD_DOCUMENT` | Document uploaded | info | `bi-cloud-arrow-up` |
| `DOWNLOAD_DOCUMENT` | Document downloaded | info | `bi-download` |
| `VIEW_DOCUMENT` | Document viewed | neutral or info | `bi-eye` |
| `APPROVE_DOCUMENT` | Document approved | success | `bi-check-circle` |
| `REJECT_DOCUMENT` | Document rejected | danger | `bi-x-circle` |
| `DELETE_DOCUMENT` | Document deleted | danger | `bi-trash3` |
| `CREATE_USER` | User created | success or info | `bi-person-plus` |
| `UPDATE_USER` | User updated | info | `bi-person-gear` |
| `ACTIVATE_USER` | User activated | success | `bi-person-check` |
| `DEACTIVATE_USER` | User deactivated | warning | `bi-person-dash` |
| `RESET_PASSWORD` | Password reset | warning | `bi-key` |
| `CHECKSUM_MISMATCH` | File integrity check failed | danger | `bi-shield-exclamation` |
| Unknown action | System activity | neutral | `bi-activity` |

Implementation requirements:

- The visible badge must contain text plus an icon.
- Keep the raw code available through a tooltip, `data-action-code`, or an admin-only detail area if useful.
- Do not display raw codes as the primary label to ordinary users.
- Preserve raw codes in audit storage, filtering, exports, and logs.
- Make the helper safe for unknown future actions.
- Replace duplicated action-badge markup in `public/admin/dashboard.php` and `public/admin/logs.php` with the shared helper where appropriate.
- Keep technical action codes in the existing Geist Mono/monospace treatment only when shown as secondary technical metadata.

Example visible result:

```text
[check icon] Login successful
```

not:

```text
[gray icon] LOGIN_SUCCESS
```

---

## 6. Document status badges

Review `status_badge()` and related CSS. Ensure document states are immediately recognizable:

```text
Approved       soft green
Pending review soft amber
Rejected       soft red
Draft          neutral slate
Deleted        neutral or danger-muted
```

Requirements:

- Use human-readable labels such as `Pending review`, not only `pending`.
- Include an icon where it improves scanning.
- Use the same status vocabulary across document lists, drawers, document views, and approvals.
- Keep deleted records visually subdued but still understandable.
- Do not rely on color alone.

---

## 7. Identity hierarchy and avatars

Do not implement profile pictures. Keep the existing initials avatar helper.

The standard identity pattern should be:

```text
[AR] Andrea Reyes
     @admin
     Administrator
```

or, in a compact document row:

```text
[MS] Maria Santos
     @staff
```

Rules:

1. `full_name` is the primary visible identity.
2. `username` is secondary metadata, displayed with `@` where appropriate.
3. Email may appear as secondary metadata in administration screens.
4. Role is a separate title-cased badge or metadata line.
5. Do not use the role as the user’s name.
6. Do not use the username as the primary person label when `full_name` exists.
7. Preserve useful fallbacks for missing names, such as `Unknown user` or `Anonymous/Guest`.
8. Keep initials derived from the real display name.
9. Do not add avatar uploads or public image URLs.

Review and update these areas:

- `app/includes/nav.php`
- `public/documents/index.php`
- `public/documents/view.php`
- `public/admin/approvals.php`
- `public/admin/dashboard.php`
- `public/admin/logs.php`
- `public/admin/users.php`
- Any other page showing `avatar(...)` beside a raw username.

Use a consistent stacked structure such as:

```html
<span class="user-identity">
  <span class="name">Maria Santos</span>
  <span class="sub">@staff</span>
</span>
```

Keep output escaped with `e(...)`.

---

## 8. Demo and seed data

Use realistic fictional names in demo data while preserving the login usernames and passwords.

Recommended examples:

| Username | Display name | Role |
|---|---|---|
| `admin` | Andrea Reyes | Administrator |
| `staff` | Maria Santos | Staff |
| `viewer` | Juan Dela Cruz | Viewer |

Update both:

- `database/seed.sql` for fresh installations.
- `database/demo-data.sql` for existing demo databases.

Do not change demo passwords as part of this task unless explicitly requested.

Do not treat `admin`, `staff`, or `viewer` as display names. They remain login identifiers only.

Use `ucfirst()` or a shared role-label helper for visible role text. Prefer a central role-label mapping if role names may later become more descriptive.

---

## 9. File-browser-inspired layout refinements

Use the supplied reference as a visual guide, not a literal template.

Apply the following carefully to the existing component system:

- Keep white or dark surfaces calm and uncluttered.
- Prefer thin borders and soft shadows over heavy elevation.
- Reduce unnecessary nested card appearance.
- Give page headings and primary content more breathing room.
- Make document titles the strongest text in document rows.
- Keep file extension, size, date, username, and hash as quieter metadata.
- Keep expanded details drawers for hashes and technical integrity information.
- Keep action controls compact and clearly grouped.
- Use neutral outlined controls for secondary actions.
- Use semantic treatment for approve, reject, delete, activate, and deactivate actions.
- Avoid introducing new brand colors that compete with emerald and gold.

Table headings should be readable and restrained. Avoid excessive all-caps styling for ordinary labels. Technical values may remain monospace.

Recommended priority in a file row:

1. Document title.
2. Document type/category and status.
3. Person who uploaded it.
4. Date and size.
5. Secondary technical metadata.
6. Actions.

---

## 10. Action-button semantics

Review action controls throughout documents and admin pages.

Recommended treatment:

- Primary action: CvSU green filled button.
- View/details: neutral outlined button.
- Approve/activate: soft green or green outline.
- Review/reset: amber or gold outline.
- Reject/delete/deactivate: red outline or soft red treatment.
- Download: neutral or informational treatment.

Requirements:

- Keep visible text for important actions.
- Icon-only buttons must have accessible `aria-label` and `title` values.
- Destructive actions must continue using the existing confirmation behavior.
- Do not make a dangerous action look like the primary submit action.
- Do not change server-side authorization or POST behavior.

---

## 11. Dashboard and audit-log improvements

The dashboard should answer “What needs my attention?” quickly.

If the existing structure supports it without a broad rewrite, prioritize:

- Pending approvals.
- Recent failed or rate-limited logins.
- Rejected documents.
- Inactive accounts.
- Recent activity.

Use small semantic counts or alert panels, not large saturated blocks.

For audit logs:

- Use the shared action badge helper.
- Show the person’s full name first and username second.
- Keep IP addresses, entity IDs, raw action codes, and other technical values in monospace.
- Use readable column labels:
  - `User` instead of `User Account` where clear.
  - `Activity` instead of `Action Code`.
  - `Affected item` instead of `Target Entity`.
  - `Access result` instead of `Authorization Result`.
  - `Source IP address` instead of `Origin IP` if space permits.
- Preserve raw technical values for admin traceability.

Do not hide security-relevant information from administrators; improve its hierarchy instead.

---

## 12. Empty states and feedback

Improve empty states where they are encountered during implementation.

Use specific, actionable copy:

```text
No documents match your filters.
Try clearing a filter or searching by title, category, or uploader.
```

For approval queues:

```text
All caught up.
There are no documents waiting for approval.
```

Requirements:

- Do not claim that a system action succeeded unless the server confirmed it.
- Preserve flash-message behavior and escaping.
- Keep errors clear without exposing sensitive implementation details.
- Do not expose password hashing, database, filesystem, or internal security implementation details to ordinary users.

---

## 13. Accessibility requirements

All changes must preserve or improve accessibility:

- Maintain visible `:focus-visible` states.
- Maintain keyboard access to filters, buttons, menus, drawers, and tables.
- Do not communicate meaning by color alone.
- Maintain readable contrast in light and dark modes.
- Keep table regions keyboard-scrollable where already present.
- Use semantic buttons for actions and links for navigation.
- Keep `aria-label` values accurate after visible-label changes.
- Keep initials avatars decorative unless they convey required identity information; the adjacent name must remain available as text.
- Ensure badges remain understandable when color vision is limited.
- Preserve reduced-motion behavior.
- Test at 200% zoom and narrow mobile widths.

---

## 14. Performance and compatibility requirements

- Reuse existing CSS and helper systems instead of adding a new UI framework.
- Avoid new external assets unless necessary.
- Do not add profile image downloads or upload processing.
- Keep semantic badges as lightweight HTML/CSS.
- Avoid expensive JavaScript for static visual changes.
- Preserve the no-JavaScript fallback.
- Keep the existing Inter and Geist Mono fallback strategy.
- Bump the shared CSS cache key after CSS changes.
- If JavaScript changes, bump the JavaScript cache key.

---

## 15. Suggested implementation sequence

### Phase A — Shared foundations

1. Inspect current tokens, helpers, and all affected views.
2. Add/refine semantic color tokens and dark-mode equivalents.
3. Add a shared role-label helper if duplicated title-casing is widespread.
4. Add the shared action-badge mapping/helper.
5. Add or refine semantic badge CSS.

### Phase B — Identity cleanup

1. Update seeded administrator name to a realistic fictional name.
2. Ensure demo-data updates existing demo accounts.
3. Make all common identity cells name-first.
4. Add username/email as secondary metadata.
5. Keep initials-only avatars.

### Phase C — Activity and status clarity

1. Replace raw audit action labels with shared human-readable badges.
2. Improve document status labels and colors.
3. Refine action buttons by risk and meaning.
4. Improve audit-log column labels and technical metadata hierarchy.

### Phase D — Visual cleanup

1. Refine table headers, row spacing, and metadata contrast.
2. Reduce unnecessary shadows or heavy borders where safe.
3. Improve empty states encountered during the work.
4. Preserve existing responsive card-table behavior.

### Phase E — Verification

1. Run syntax checks.
2. Run backend smoke tests when XAMPP/MySQL is available.
3. Test light and dark modes.
4. Test keyboard navigation and focus states.
5. Test desktop, tablet, and mobile widths.
6. Test admin, staff, and viewer permissions.
7. Verify no raw lowercase role labels or username-first identity blocks remain in common user-facing areas.

---

## 16. Regression checklist

### Authentication and security

- Login POST still works.
- CSRF field and verification remain intact.
- Failed-login throttling remains intact.
- Session regeneration remains intact.
- Logout remains intact.
- Login success uses the display name without changing authentication semantics.
- No password hash or security implementation details are shown to ordinary users.

### Documents

- Document list still loads.
- Search and filters still work.
- Document detail drawers still expand and collapse.
- View/download authorization is unchanged.
- Upload and delete authorization is unchanged.
- File hashes remain available in appropriate technical detail areas.

### Admin

- Approval actions still use POST and CSRF.
- Reject actions still require remarks where required.
- Delete/deactivate/reset actions retain confirmation behavior.
- User management still validates names, emails, roles, and passwords.
- Audit logs retain raw action codes, timestamps, IP addresses, entities, and details.
- Colored badges do not alter stored audit values.

### Visual and accessibility

- Light mode remains readable.
- Dark mode remains readable.
- Semantic badges have text and icons.
- Names are primary; usernames are secondary.
- Roles are title-cased.
- Initials avatars remain stable.
- No profile-photo upload UI appears.
- Tables remain usable at narrow widths.
- Focus indicators remain visible.
- 200% zoom does not hide important actions.
- No unnecessary horizontal overflow is introduced.

---

## 17. Validation notes to report

The coding AI must report:

- Files changed.
- Whether profile-picture functionality was intentionally not added.
- Which semantic action mappings were implemented.
- Whether existing databases require the demo-data SQL update to be run.
- Syntax-check results.
- Runtime test results.
- Any unavailable environment dependency, such as missing PHP CLI, unavailable XAMPP/MySQL, or unavailable browser verification.
- Any remaining raw technical labels that were intentionally retained for administrators.

Do not claim visual or runtime verification that was not actually performed.

---

## 18. Definition of done

This brief is complete when:

- CvSU green and gold remain the primary institutional colors.
- Semantic colors are used consistently for meaningful states.
- Audit actions are recognizable through readable colored badges and icons.
- Document statuses are readable and semantically colored.
- Full names are shown before usernames in common identity surfaces.
- Roles are title-cased and visually separate from names.
- Initials-only avatars remain in use.
- Profile-picture upload functionality is not added.
- The seeded administrator has a realistic fictional display name.
- The clean file-browser-inspired hierarchy is visible without copying the reference literally.
- Authentication, authorization, CSRF, uploads, downloads, integrity checks, and audit storage are unchanged.
- Light mode, dark mode, keyboard navigation, responsive layouts, and reduced-motion behavior remain functional.
- Validation results and environment limitations are documented.
