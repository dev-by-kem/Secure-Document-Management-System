# 08 · Frontend Guide — the sample base and how to redesign it

> **In plain words:** the backend engine is finished, so this is your playground. This page explains
> the frontend "sample base": which files to touch, which web concepts are already implemented (and
> where), which rules you must keep, and how to retheme the whole app in five minutes. You are free to
> redesign everything visual — just don't break the few safety rails listed at the end.

---

## 1. The file map — where frontend lives

| File | What it is | You may change? |
|------|------------|-----------------|
| `public/assets/css/tokens.css` | **Design tokens** — colors, radius, shadows, spacing as CSS variables | ✅ Yes — this rebrands everything |
| `public/assets/css/base.css` | Resets, visible keyboard focus, skip link, reduced-motion support | ✅ Carefully |
| `public/assets/css/components.css` | SDMS components: cards, confirm dialog, responsive tables, flash region | ✅ Yes — your main canvas |
| `public/assets/css/custom.css` | Import hub that loads the three files in order (1 line each) | Usually untouched |
| `public/assets/js/app.js` | Theme toggle, confirm dialog, password show/hide (progressive enhancement) | ✅ Add features; keep the rules |
| `app/includes/header.php` · `nav.php` · `footer.php` | The shared page shell: `<head>`, navbar, flash area, shared dialog, scripts | ✅ Yes |
| The `public/**/*.php` pages | Each page = PHP logic at the top + its HTML below | ✅ Redesign the HTML |

Bootstrap 5.3 (local files, no CDN) provides the component classes (`btn`, `card`, `table`, forms).
Our three CSS layers sit **on top of it** and never fight it.

---

## 2. The concepts already implemented (and where to see them)

### Concept 1 — Design tokens (rebrand in one file)
All custom colors/spacing/radii are CSS custom properties in `tokens.css` (`--sdms-brand`,
`--sdms-accent`, `--sdms-radius`, …). Components reference the tokens, never raw hex values.
**Retheme = edit one file.** Try changing `--sdms-brand` and refresh.

### Concept 2 — Dark mode that follows the system (+ optional override)
- Default: the site **follows your OS light/dark preference**.
- The navbar **Theme** button pins the opposite scheme; toggling back to the system scheme
  *removes* the stored override, so the site keeps following the OS when it changes (e.g., auto-dark at night).
- Stored in `localStorage` (`sdms-theme`), applied before first paint by the tiny script in `header.php`
  (that script exists only to prevent a flash of the wrong theme — an explicit exception to our
  "no inline JS" rule, and it is 4 lines).
- Dark values of our tokens live under `[data-bs-theme="dark"]`, which is also Bootstrap 5.3's
  own dark-mode switch — so Bootstrap components and our components change together.

### Concept 3 — Semantic shell & accessibility
- Landmarks: single `<h1>` per page, `<nav>`, `<main id="main-content">`, `<footer>`, `<search>` for filters.
- **Skip link**: invisible until you press `Tab`; jumps past the navbar to `#main-content`.
- Every page's flash messages render inside `<div class="flash-region" aria-live="polite">` so screen
  readers announce success/error feedback automatically.
- Visible keyboard focus everywhere (`:focus-visible` outline from `base.css`).

### Concept 4 — Feedback patterns
- Success/error flash messages (PRG pattern: action → redirect → message).
- Status badges (`pending` / `approved` / `rejected`) via the `status_badge()` helper.
- Empty states: tables always show a muted "nothing here" row instead of blank space.

### Concept 5 — Responsive tables (scroll on desktop, cards on mobile)
The Documents and My-uploads tables use `.sdms-table-wrap` + `.sdms-card-table`:
- On wide screens: sticky column headers while scrolling, keyboard-scrollable region
  (`role="region"` + `tabindex="0"` + `aria-label`).
- Under 640px (container query, not viewport media query): each row stacks into a card and every
  cell's column name is injected from its `data-label` attribute — so nothing loses meaning.
**To use on more tables:** add the two classes to the wrapper/table and `data-label="…"` to each `<td>`.

### Concept 6 — Native `<dialog>` confirmations (no custom modal code)
Delete buttons (forms marked `.js-confirm` + `data-confirm-message="…"`) open **one shared**
`<dialog id="confirm-dialog">` from `footer.php`. The browser provides focus containment, `Esc`
dismissal, backdrop and focus-return — we wrote zero modal plumbing. Rules already followed:
`aria-labelledby` heading, `autofocus` on the safe button (Cancel), `returnValue` reset after close,
scroll containment CSS. **To add a confirmation to any destructive form:** add class `js-confirm` and
the `data-confirm-message` attribute — that's it.

### Concept 7 — Forms done properly
- Every control has a `<label for>`. Mutations are `POST` + CSRF hidden field; searches are `GET`.
- Correct `autocomplete` values (`username`, `current-password`, `new-password`, `email`, `name`),
  `required`/`minlength`/`maxlength` native constraints, `type="email"`, `type="search"`, `type="file"`
  with an `accept` list, `enterkeyhint` on the login form.
- Show-password toggles (`data-password-toggle="#id"`) on sign-in.
- Grouped controls use `<fieldset>` + `<legend>` (see the upload form).
- **Server-side validation is the security; the HTML constraints are only comfort.**

### Concept 8 — Motion & behavior hygiene
- `prefers-reduced-motion` is respected (animations effectively off for users who opt out).
- No inline `onclick=` anywhere; all behavior lives in `app.js` and degrades gracefully:
  with JavaScript disabled, every page still works and forms still submit.

---

## 3. How to retheme in 5 minutes (try this as a team exercise)

1. Open `public/assets/css/tokens.css`.
2. Change `--sdms-brand` to your school color, `--sdms-radius` to `2px` (sharp) or `16px` (bubbly),
   and the two `--sdms-shadow` lines.
3. Refresh — buttons, focus rings, cards, dialog and skip link all follow.
4. Optionally add a font: add `body { font-family: "Segoe UI", system-ui, sans-serif; }` in `base.css`.

---

## 4. Rules to KEEP while redesigning (these are graded, not cosmetic)

1. **Never remove** `require_login()` / `require_role([...])` at the top of a page.
2. **Never remove** `csrf_field()` from a form or `csrf_check()` from its POST handling.
3. **Never print user data without** `e( ... )` — that is the XSS protection (SR-6).
4. Mutations stay `POST` (with CSRF); searches/filters stay `GET`.
5. Keep `data-label` attributes in sync when you add/remove table columns.
6. Keep labels attached to inputs (`for`/`id`) and visible focus — accessibility is part of the grade.
7. No inline `onclick=`/`onsubmit=`; put behavior in `app.js`.
8. After UI changes that touch POST flows, re-run `tests/backend-smoke.ps1` — all 13 must stay green.

---

## 5. Ideas backlog (nice upgrades for the "actual web-developing" phase)

- Row-action menus with the Popover API (`popover` attribute) instead of many small buttons.
- View Transitions API for smoother page-to-page navigation (Chrome/Edge; harmless elsewhere).
- Upload progress bar + nicer drag-and-drop dropzone on the upload form.
- Skeleton loaders or spinners while the document list filters.
- A proper icon set rendered from CSS (accessible by default).
- Container-query-driven dashboard cards that adapt to their column, not the viewport.
- Print-friendly invoices… sorry — *reports* (already started: the print CSS hides chrome).
- Sortable table headers (progressive enhancement on the documents list).

---

## 6. Quick self-check before merging UI work

- [ ] Keyboard only: can you reach every control, see focus, and operate dialogs with `Esc`?
- [ ] Phone width: do the Documents tables stack into readable cards?
- [ ] Dark theme: does every new component look right in **both** themes?
- [ ] Zoomed to 200%: does anything overlap or clip?
- [ ] `tests/backend-smoke.ps1`: 13/13 still passing?
