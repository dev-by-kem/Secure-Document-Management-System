# SDMS UI/UX Improvement Plan

**Purpose:** Provide an implementation-ready, safety-conscious plan for improving the Secure Document Management System (SDMS) user experience without weakening its security model or breaking existing workflows.

**Audience:** Another AI coding agent or developer who will inspect, implement, and test these changes.

**Scope:** UI, accessibility, responsive behavior, workflow quality of life, feedback, navigation, list usability, and related documentation. This plan does not authorize changing the authentication, authorization, CSRF, upload-validation, file-storage, or audit-logging model except where a change is explicitly listed and verified.

**Status:** Planning only. No implementation is implied by this document.

---

## 1. Project context

SDMS is a plain PHP 8 + MySQL/MariaDB application using Bootstrap 5, vanilla JavaScript, shared PHP layout partials, and a token-based CSS layer.

Important areas:

- Shared shell: `app/includes/header.php`, `app/includes/nav.php`, `app/includes/footer.php`
- Shared behavior: `public/assets/js/app.js`
- Design system: `public/assets/css/tokens.css`, `base.css`, `components.css`, `custom.css`
- User workflows: `public/dashboard.php`, `public/profile.php`, `public/documents/*`
- Admin workflows: `public/admin/*`
- Security libraries: `app/lib/auth.php`, `rbac.php`, `csrf.php`, `validate.php`, `logger.php`
- Runtime tests: `tests/backend-smoke.ps1`
- Requirements: `docs/01-REQUIREMENTS.md`
- Frontend safety rules: `docs/08-FRONTEND-GUIDE.md`

The application currently has a strong baseline: authentication, RBAC, CSRF protection, prepared statements, gated downloads, file validation, audit logging, dark mode, visible focus styles, responsive table behavior, empty states, and native confirmation dialogs.

---

## 2. Non-negotiable safety rules

Before editing any file, inspect its current contents and preserve surrounding behavior.

Never remove or bypass:

1. `require_login()` or `require_role([...])` guards.
2. `csrf_field()` from state-changing forms.
3. `csrf_check()` from POST handlers.
4. `e(...)` escaping around user-controlled output.
5. POST for mutations and GET for search/filter forms.
6. The download authorization gate in `documents/download.php`.
7. `can_access_document()` policy checks.
8. Upload extension, MIME, size, and file-integrity validation.
9. Server-generated storage filenames.
10. Activity and access logging.
11. Existing session timeout, regeneration, and logout behavior.
12. The working no-JavaScript fallback for forms and navigation.
13. The existing reduced-motion behavior.

Do not expose files directly from `storage/uploads/`.

Do not make the UI imply that a user can perform an action when the server will reject it. UI hiding is not authorization; server-side authorization remains authoritative.

After changes involving POST flows, run `tests/backend-smoke.ps1` against a configured XAMPP/MySQL environment. If the test environment is unavailable, report that clearly rather than claiming runtime verification.

---

## 3. Corrected audit notes

Some earlier notes require correction before implementation:

- `public/documents/activity.php` already exists and is linked in the staff navigation. Do not create a duplicate staff activity page. Improve the existing page only if needed.
- `public/assets/js/app.js:165–171` already contains a `window.print()` handler for `#print-report`. Do not add a duplicate print handler. Verify that the current handler works in the target runtime.
- `database/schema.sql:123–135` already includes `document_access_logs.user_agent`, and `app/lib/logger.php:26–32` writes it. Do not add the column again. Verify the deployed database matches the schema.
- The current upload flow writes metadata and moves the file inside a transaction in `public/documents/upload.php:46–81`. Preserve and test this behavior. Do not replace it with a less-safe sequence.
- The project currently contains inline styles in some views. They are a maintainability concern, not automatically a functional defect. Move them only when doing so does not change layout or print behavior.
- The current document page supports both `$from` and `$to` query parameters in the SQL logic, but the visible form only renders a From Date field. Verify this against the current file before editing.

---

## 4. Priority model

### P0 — Fix before broad redesign

Changes that fix broken, misleading, inaccessible, or high-friction behavior with low regression risk.

### P1 — Implement after P0

Changes that improve recurring workflows or responsive behavior and require moderate testing.

### P2 — Optional polish

Changes that improve maintainability, visual refinement, or convenience without being required for core usability.

Do not combine unrelated P0/P1 changes into one large edit. Work in small, reviewable batches.

---

# 5. P0 implementation tasks

## P0-01 — Fix document filter state and date-range controls

**Files:**

- `public/documents/index.php`

**Problem:**

The document directory supports search, category, status, ownership, and date conditions in the server-side query. The visible form should expose all supported date controls and preserve filter state after navigation. Confirm that `$mine` is initialized before it is used for checkbox rendering.

**Implementation:**

1. Initialize `$mine` once from `query_param('mine') === '1'` before the query and before rendering.
2. Render a visible `To Date` input if it is not already present.
3. Preserve `q`, `category_id`, `status`, `from`, `to`, and `mine` in the form.
4. Preserve filters when linking to relevant views where practical.
5. Keep searches and filters as GET requests.
6. Keep server-side validation for dates.
7. Add an accessible reset action with visible text on narrow screens.
8. If adding active-filter chips, make them supplementary; the form must remain usable without JavaScript.

**Do not:**

- Change access-control SQL without reviewing the requirements table in `docs/01-REQUIREMENTS.md`.
- Allow client-side filters to replace server-side filtering.
- Encode sensitive information in the URL.

**Acceptance criteria:**

- `Only mine` remains checked after submission.
- From and To dates are visible and survive a filtered request.
- Reset returns to an unfiltered document directory.
- Viewer, staff, and admin visibility rules remain unchanged.
- Existing document search still works with title and description.

**Risk:** Low, provided the WHERE clause is not altered beyond the existing validated parameters.

---

## P0-02 — Make upload validation errors actionable

**Files:**

- `public/documents/upload.php`
- Shared flash/error styles only if necessary.

**Problem:**

Validation currently redirects with a combined flash message. This is safe but makes it difficult to identify which field needs correction and may discard entered metadata.

**Implementation:**

1. Preserve title, description, and category values after validation failure.
2. Keep a summary flash message for compatibility with the existing PRG flow.
3. Add field-level error output for title, category, description, and file errors where practical.
4. Associate errors with controls using `aria-describedby`.
5. Add `aria-invalid="true"` only to invalid controls.
6. Focus the first invalid control after the response if JavaScript is available; do not make the form depend on JavaScript.
7. Keep server-side validation as the authority.
8. Do not display raw MIME errors or filesystem paths if they reveal unnecessary implementation details.

**Do not:**

- Trust `accept` as validation.
- Store an uploaded file before the existing server-side validation completes.
- Echo unsanitized filenames or descriptions.
- Preserve passwords or security tokens in form values.

**Acceptance criteria:**

- Invalid uploads explain what must be fixed.
- Valid title/description/category values are retained after failure.
- File validation still blocks disallowed extensions, oversized files, empty files, and MIME mismatches.
- Successful upload behavior and status assignment remain unchanged.

**Risk:** Medium. The main risk is changing the redirect/flash flow or accidentally retaining unsafe data. Implement incrementally.

---

## P0-03 — Make the upload dropzone keyboard accessible

**Files:**

- `public/documents/upload.php`
- `public/assets/js/app.js`
- `public/assets/css/components.css`

**Problem:**

The dropzone is a clickable `<div>` with drag-and-drop behavior but no keyboard activation path.

**Implementation:**

1. Add `tabindex="0"` and `role="button"` to the dropzone, or replace it with a semantically appropriate button-like control while keeping the real file input.
2. Add an accessible label that explains the action.
3. Support `Enter` and `Space` to open the file picker.
4. Add a visible `:focus-visible` style.
5. Keep the native file input available as a fallback.
6. Ensure drag-and-drop remains optional, not required.
7. Add a submit/loading state only if it does not prevent the browser from completing the file upload.
8. Keep filename display escaped; do not interpolate raw file names into HTML.

**Acceptance criteria:**

- Keyboard users can focus the dropzone and open the picker.
- Screen readers receive a useful label.
- Clicking the dropzone and using the native file input both work.
- Drag-and-drop still works where supported.
- Reduced-motion users are not forced to see animated feedback.

**Risk:** Low to medium. Test with JavaScript disabled and with a keyboard-only flow.

---

## P0-04 — Add confirmation to high-impact admin actions

**Files:**

- `public/admin/users.php`
- `public/admin/categories.php`
- `public/admin/approvals.php`
- `public/documents/delete.php` only if the existing shared pattern needs adjustment.

**Problem:**

Document deletion already uses the shared native confirmation dialog. User deactivation, password reset, category deletion, approval, and rejection should use consistent confirmation where the action is consequential.

**Implementation:**

1. Reuse the existing `.js-confirm` and `data-confirm-message` pattern.
2. Use explicit, action-specific messages containing the target name where safe.
3. Confirm only after a rejection remark has been entered.
4. Do not use confirmation to replace server-side validation.
5. Keep the action as a POST with CSRF protection.
6. Add `aria-label` to any icon-only action buttons.

**Recommended messages:**

- Deactivate: “Deactivate this account? The user will be unable to sign in.”
- Reset password: “Reset this user’s password? Their current password will stop working.”
- Delete category: “Delete this category? This cannot proceed while documents still use it.”
- Approve: “Approve and publish this document to authorized users?”
- Reject: “Reject this submission? The uploader will see the rejection remarks.”

**Do not:**

- Add confirmation to ordinary navigation.
- Depend only on a browser `confirm()` call if the shared dialog is available.
- Submit a form twice due to custom dialog handling.

**Acceptance criteria:**

- Cancel leaves the record unchanged.
- Confirm submits exactly once.
- Escape closes the dialog safely.
- Keyboard focus returns to the initiating control.
- Direct POST requests still require CSRF and server authorization.

**Risk:** Low if the existing dialog is reused. Test every form with JavaScript enabled and disabled.

---

## P0-05 — Separate document navigation from expandable details

**Files:**

- `public/documents/index.php`
- `public/assets/js/app.js`

**Problem:**

The document title is an anchor that also triggers the details drawer and may prevent normal link behavior. This can break Ctrl/Cmd-click, middle-click, and user expectations.

**Implementation:**

1. Keep the title as a normal link to `documents/view.php?id=...`.
2. Use a separate `<button>` for expanding quick details.
3. Give the button an accessible label containing the document title.
4. Maintain `aria-expanded` and the relationship to the drawer row.
5. Do not use `role="button"` on a navigation link.
6. Keep the non-JavaScript title link functional.

**Acceptance criteria:**

- Clicking the title navigates to the detail page.
- Ctrl/Cmd-click and middle-click work normally.
- Clicking the separate details button expands and collapses the drawer.
- Keyboard users can operate the details button.
- The table remains readable on mobile.

**Risk:** Low to medium. Verify the drawer JavaScript does not rely on title clicks.

---

# 6. P1 implementation tasks

## P1-01 — Redesign mobile navigation as an off-canvas drawer

**Files:**

- `app/includes/nav.php`
- `app/includes/header.php` if a toggle must be placed outside the sidebar
- `public/assets/js/app.js`
- `public/assets/css/components.css`
- `public/assets/css/tokens.css`

**Problem:**

The current mobile approach changes the sidebar width to 72px but does not provide a complete mobile drawer interaction. Labels, nested navigation, user information, and account actions may not fit comfortably.

**Implementation:**

1. Keep the desktop sidebar behavior intact.
2. Add a mobile menu button with an accessible label.
3. Add a drawer state controlled by a class or attribute.
4. Add a backdrop and close button.
5. Support Escape to close.
6. Return focus to the menu button after closing.
7. Prevent background interaction while the drawer is open without breaking page scrolling.
8. Close the drawer after navigation on mobile.
9. Keep the current active navigation state.
10. Respect reduced motion.

**Do not:**

- Remove navigation items just to fit the 72px rail.
- Hide labels without providing accessible names.
- Use a click-only `<div>`.
- Make the drawer the only way to access content when JavaScript is disabled.

**Acceptance criteria:**

- Desktop navigation is unchanged.
- At narrow widths, the menu is usable with touch, keyboard, and screen readers.
- The drawer does not cover focused controls incorrectly.
- Main content has no unusable horizontal overflow.
- Dark mode and print mode remain correct.

**Risk:** Medium to high. Implement as an isolated navigation change and test at 320px, 375px, 768px, and desktop widths.

---

## P1-02 — Add pagination and sorting to large lists

**Files:**

- `public/documents/index.php`
- `public/documents/history.php`
- `public/documents/activity.php`
- `public/admin/approvals.php`
- `public/admin/logs.php`
- `public/admin/reports.php`
- Shared pagination styles if needed.

**Problem:**

Several pages use fixed result limits without pagination or sorting.

**Implementation:**

1. Start with the document directory and admin logs.
2. Use validated numeric page and page-size parameters.
3. Keep all active filters in pagination links.
4. Use deterministic ordering with a tie-breaker such as `document_id`.
5. Add visible result counts such as “Showing 1–25 of 143.”
6. Add sorting only through a whitelist of server-side SQL fragments.
7. Keep caps as a safety limit even after pagination.
8. Add indexes only after measuring query performance.

**Do not:**

- Concatenate arbitrary query-string values into SQL.
- Remove the existing limits without replacement.
- Put sensitive filter data into links.

**Acceptance criteria:**

- Page navigation preserves filters.
- Invalid page values fail safely.
- Sorting cannot inject SQL.
- Empty pages fall back to the last valid page or show a clear empty state.
- Mobile pagination remains usable.

**Risk:** Medium. Query construction and count queries require careful review.

---

## P1-03 — Improve the approval queue for rapid review

**Files:**

- `public/admin/approvals.php`
- Possibly `public/assets/js/app.js` and component CSS.

**Implementation:**

1. Show title, uploader, category, file metadata, submission age, and status in a consistent hierarchy.
2. Replace the single-line rejection input with a larger textarea if the existing database field limit permits it.
3. Disable or validate Reject until remarks are present, while keeping server-side validation.
4. Add a clear View/Download action.
5. Add “Approve and next” or automatic navigation only after the basic workflow is stable.
6. Consider a visible “X of Y pending” indicator.
7. Keep all approval actions POST + CSRF + server-side status transition checks.

**Do not:**

- Approve or reject by GET.
- Assume a document remains pending between rendering and POST.
- Remove the existing server-side check that only pending items may be decided.

**Acceptance criteria:**

- Reject without remarks is blocked with a clear message.
- A second administrator cannot accidentally overwrite a completed decision without receiving the existing warning.
- Approval and rejection remain audited.
- The queue remains usable at narrow widths.

**Risk:** Medium. Avoid changing status semantics while improving layout.

---

## P1-04 — Simplify admin user and category management

**Files:**

- `public/admin/users.php`
- `public/admin/categories.php`

**Problem:**

The user table combines identity editing, role changes, password reset, and account state changes in one dense row. Categories similarly combine display and editing controls.

**Preferred implementation:**

1. Keep summary tables focused on identity, role/status, counts, and last activity.
2. Move editing into a dedicated page or accessible side panel.
3. Add search and status filters for users.
4. Add row actions with clear text labels or properly labelled icon buttons.
5. Keep self-role, self-deactivation, and last-admin protections unchanged.
6. Confirm password resets and account deactivation.
7. Keep category delete blocking when documents still reference the category.

**Do not:**

- Remove server-side protections because an action is hidden in the UI.
- Allow duplicate email or username values.
- Disable a form control in a way that prevents required values from being submitted without compensating hidden fields.

**Acceptance criteria:**

- The page is readable at mobile width.
- Every row action identifies its target account/category.
- Editing and password reset remain separate actions.
- Existing restrictions on self-management and foreign-key usage remain intact.

**Risk:** Medium to high. This is a structural UI change; implement one admin screen at a time.

---

## P1-05 — Improve dashboard prioritization

**Files:**

- `public/dashboard.php`
- `public/admin/dashboard.php`

**Implementation:**

For staff:

1. Add a “Needs attention” section for pending and rejected documents.
2. Link rejected documents directly to remarks and editing.
3. Keep Upload and Browse as primary actions.
4. Move the security workflow explanation into a collapsible or secondary section if it dominates the page.

For viewers:

1. Make Browse approved documents the primary action.
2. Keep the role explanation concise.

For admins:

1. Keep pending approvals as the dominant alert.
2. Add review age or oldest pending submission if it can be queried efficiently.
3. Keep current dashboard links and counts.

**Do not:**

- Add expensive dashboard queries without measuring them.
- Show users documents they are not authorized to access.
- Treat dashboard counts as authorization checks.

**Acceptance criteria:**

- The most urgent action is visible without scrolling.
- All links respect role and document access policy.
- Empty states remain useful.

**Risk:** Low to medium if queries are reused or kept bounded.

---

## P1-06 — Complete rejected-document resubmission workflow

**Files:**

- `public/documents/view.php`
- `public/documents/edit.php`
- Potentially `public/admin/approvals.php`
- `app/lib/rbac.php` only if access semantics must change.

**Problem:**

The detail page tells owners to update and resubmit rejected documents, but the exact transition back to review must be explicit.

**Implementation options:**

- Option A: Editing a rejected document automatically returns it to pending when its category requires approval.
- Option B: Add an explicit “Revise and resubmit” POST action.

Prefer Option B if the project needs to distinguish ordinary metadata edits from a formal resubmission.

**Requirements:**

1. Preserve rejection remarks in a history or audit record if the schema supports it; do not silently erase useful review context.
2. Re-check category approval policy on resubmission.
3. Audit the resubmission.
4. Tell the owner exactly what visibility/status will result.
5. Do not allow another user to resubmit someone else’s document unless the existing admin policy explicitly permits it.
6. Keep the download policy aligned with `docs/01-REQUIREMENTS.md`.

**Risk:** High. This changes workflow semantics and should not be implemented as a visual-only change. Add tests before or alongside implementation.

---

## P1-07 — Standardize date/time display

**Files:** Shared helpers and all pages that display timestamps.

**Implementation:**

1. Add a shared PHP formatting helper for exact local timestamps.
2. Use a consistent readable format in visible UI.
3. Optionally add relative labels for recent events with exact values still available.
4. Keep reports and audit logs precise and unambiguous.
5. Do not change stored timestamps or database timezone behavior without a separate data review.

**Acceptance criteria:**

- The same timestamp has the same visible format across the app.
- Audit reports retain exact timestamps.
- Filipino/local timezone expectations remain correct.

**Risk:** Low, but date display changes can affect reports and tests.

---

# 7. P2 maintainability and visual polish

## P2-01 — Remove duplicate CSS token declarations

**File:** `public/assets/css/tokens.css`

The root token file contains duplicated brand, accent, surface, and border declarations. Remove only the duplicate declarations after confirming the final computed values remain unchanged.

**Risk:** Low. Compare light and dark themes before and after.

---

## P2-02 — Replace `transition: all`

**File:** `public/assets/css/components.css`

Replace broad transitions with explicit properties such as color, background-color, border-color, box-shadow, opacity, and transform.

Preserve reduced-motion behavior and do not introduce layout animation.

**Risk:** Low.

---

## P2-03 — Move repeated inline styles into classes

Candidate areas:

- Approval document icon sizing
- Approval remarks width
- Document detail icon sizing
- Activity user-agent truncation
- Report print header styles
- Dropdown minimum width

Before changing, check print mode, dark mode, and responsive behavior.

**Risk:** Low to medium.

---

## P2-04 — Improve dark-mode consistency

Prefer SDMS token-based surfaces and text colors over scattered Bootstrap utilities when modifying components. Do not globally override Bootstrap utilities without checking all existing screens.

Test:

- Dashboard
- Documents
- Upload
- Approval queue
- Users
- Categories
- Logs
- Reports
- Profile
- Login

**Risk:** Medium if global CSS selectors are changed.

---

## P2-05 — Add quality-of-life features only after core workflows are stable

Candidate enhancements:

- Upload progress indicator
- Character counters
- Copy document link
- Document preview for safe file types
- Bookmarks/favorites
- Recently viewed documents
- CSV export for reports/logs
- Restore soft-deleted documents
- Saved filters
- Bulk admin actions

Each feature requires a separate threat, authorization, and regression review. Do not implement them as part of a broad visual refactor.

---

# 8. Suggested implementation sequence

## Batch 1: Safe, isolated fixes

1. P0-01 filter state and date controls.
2. P0-03 keyboard-accessible upload dropzone.
3. P0-04 admin confirmations.
4. P0-05 separate document links from drawer controls.
5. P2-01 remove duplicate tokens.
6. P2-02 replace `transition: all`.

Run syntax checks and inspect affected screens in both themes.

## Batch 2: Form and list quality

1. P0-02 inline upload validation.
2. P1-02 pagination and sorting, starting with documents.
3. P1-07 date/time formatting.

Run the backend smoke test after each mutation-related change.

## Batch 3: Larger interaction changes

1. P1-01 mobile navigation drawer.
2. P1-03 approval queue improvements.
3. P1-04 admin management simplification.
4. P1-05 dashboard prioritization.

Use small commits or checkpoints so regressions can be isolated.

## Batch 4: Workflow semantics

1. P1-06 rejected-document resubmission.
2. Restore/soft-delete enhancements.
3. Additional audit or integrity improvements.

Do not begin this batch until the current status policy, test data, and requirements are confirmed.

---

# 9. Required verification checklist

## Functional

- [ ] Guest is redirected to login.
- [ ] Admin, staff, and viewer navigation remains role-appropriate.
- [ ] Viewer cannot upload or access admin pages.
- [ ] Staff cannot approve/reject documents.
- [ ] Document search and filters preserve state.
- [ ] Pending/rejected visibility remains correct.
- [ ] Download authorization remains server-enforced.
- [ ] Upload validation remains server-enforced.
- [ ] Destructive actions can be cancelled.
- [ ] Approval and rejection still require POST + CSRF.
- [ ] Rejected-document behavior matches the requirements specification.
- [ ] Activity/access logs remain written.

## Accessibility

- [ ] Keyboard-only navigation works.
- [ ] Focus is visible.
- [ ] Icon-only buttons have accessible names.
- [ ] Every form control has a label or accessible name.
- [ ] Errors are associated with the relevant controls.
- [ ] Dialogs support Escape and focus return.
- [ ] Dropzone has a keyboard alternative.
- [ ] No navigation link is disguised as a button.
- [ ] Screen-reader live regions still announce flash messages.
- [ ] Zoom at 200% does not hide controls or cause unusable overflow.

## Responsive

- [ ] 320px width.
- [ ] 375px width.
- [ ] 768px width.
- [ ] Desktop width.
- [ ] Mobile navigation opens and closes correctly.
- [ ] Tables become readable cards or scroll regions.
- [ ] Long document titles and usernames do not break the layout.
- [ ] Approval and admin forms remain operable on small screens.

## Theme and presentation

- [ ] Light mode.
- [ ] Dark mode.
- [ ] Reduced motion.
- [ ] Print reports.
- [ ] Empty states.
- [ ] Flash messages.
- [ ] Validation errors.
- [ ] No unexpected hard-coded light backgrounds remain in dark mode.

## Security regression

- [ ] `tests/backend-smoke.ps1` passes.
- [ ] No CSRF field was removed.
- [ ] No guard was removed.
- [ ] No user output bypasses `e(...)`.
- [ ] No arbitrary SQL fragments are accepted from query parameters.
- [ ] No file path is derived directly from user input.
- [ ] No new sensitive data is exposed in URLs.
- [ ] No client-side control is treated as authorization.

---

# 10. Guidance for the implementing AI

Use the project’s existing institutional CvSU emerald-and-gold visual direction. Do not replace it with a generic dashboard style.

Treat modern web guidance as a guardrail for accessibility, semantics, responsive behavior, performance, and safe interaction—not as a mandate for a particular visual style.

Before each change:

1. Read the target file and its shared dependencies.
2. Identify the current behavior and the relevant requirement IDs.
3. Classify the change as P0, P1, or P2.
4. Check whether it changes data, authorization, status transitions, or only presentation.
5. Prefer progressive enhancement.
6. Make the smallest coherent change.
7. Test both success and failure paths.
8. Check light mode, dark mode, keyboard use, mobile width, and no-JavaScript fallback where applicable.
9. Report any unverified runtime assumption instead of silently changing behavior.

The goal is not to make every screen more elaborate. The goal is to make common tasks clearer, safer, faster, and more predictable while preserving SDMS’s security and institutional identity.
