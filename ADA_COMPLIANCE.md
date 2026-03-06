# ADA / WCAG Accessibility Compliance

This document summarizes the accessibility improvements made across the Share Fair backend so the application is more compliant with the Americans with Disabilities Act (ADA) and Web Content Accessibility Guidelines (WCAG) 2.1 Level AA where applicable.

## Summary of Changes

### 1. Keyboard & Navigation
- **Skip link**: “Skip to main content” link at the top of every page; visible on focus so keyboard users can bypass repeated navigation.
- **Focus visible**: Global `:focus-visible` outline so keyboard focus is clearly visible without affecting mouse users.
- **Logical tab order**: Forms and interactive elements use natural tab order; no `tabindex` values that would break flow except where needed (e.g. modal).

### 2. Landmarks & Structure
- **Main content**: Wrapped in `<main id="main-content" role="main">` so screen readers can jump to primary content.
- **Navigation**: Top nav has `aria-label="Primary navigation"`; sidebar has `role="navigation" aria-label="Main menu"`.
- **Footer**: Marked with `role="contentinfo"`.
- **Headings**: Single `<h1>` per page; sections use `<h2 class="h5">` where appropriate so heading order is logical.

### 3. Forms
- **Labels**: Every form control has an associated `<label for="id">` or `aria-label` so purpose is clear.
- **Required fields**: Indicated with `aria-required="true"` and a visible “*” with `aria-hidden="true"` so only one is announced.
- **Errors**: Validation messages use `role="alert"` and are tied to inputs via `aria-describedby` and `aria-invalid` where used (e.g. login).
- **Autocomplete**: Email/password and name fields use appropriate `autocomplete` and `inputmode` where helpful.

### 4. Tables
- **Captions**: Data tables have `<caption class="sr-only">` describing the table content for screen readers.
- **Headers**: All column headers use `<th scope="col">` so cells are associated with the correct column.
- **Table role**: Tables use `role="table"` and `aria-label` where it adds context.

### 5. Buttons & Links
- **Icon-only controls**: Buttons/links that only show an icon have `aria-label` (e.g. “Toggle sidebar”, “Close modal”, “Remove user from case”).
- **Action context**: Edit/Delete buttons include context in the label or with `<span class="sr-only">` (e.g. “Edit case X”, “Delete case X”).
- **Confirmations**: Delete confirmations use clear, descriptive messages.

### 6. Dynamic Content
- **Case users table**: The wrapper has `aria-live="polite"` so when the table is updated after “Update Assigned Users” or “Remove”, screen readers are notified.
- **Alerts**: Success/error messages use `role="alert"` so they are announced.

### 7. Modals
- **Activity modal**: Uses `role="dialog"`, `aria-labelledby="activityModalLabel"`, `aria-modal="true"`, and a close button with `aria-label="Close modal"`.
- **Form fields**: Modal inputs have matching `id` and `<label for="...">`.

### 8. Images & Decorative Content
- **Logo / user avatar**: Treated as decorative with `alt=""` where the adjacent text conveys the same information.
- **Dashboard banners**: Decorative images use `alt=""` and `role="presentation"` and are wrapped with `aria-hidden="true"` where appropriate.
- **Loader**: Loader element has `aria-hidden="true"`.

### 9. Other
- **Breadcrumbs**: Use `<nav aria-label="breadcrumb">` and `aria-current="page"` on the current item.
- **Dropdowns**: User menu uses `aria-haspopup="true"` and `role="menu"` / `role="menuitem"` for the list and items.
- **Multi-select**: “Assign users” select has a visible label and an `aria-describedby` hint (e.g. “Hold Ctrl to select multiple”) for screen readers.

## Files Modified

- `resources/views/backend/layout/inner-app.blade.php` – Skip link, landmarks, nav/sidebar/footer ARIA, focus styles.
- `resources/views/backend/layout/app.blade.php` – Skip link, `<main>` wrapper.
- `resources/views/backend/login.blade.php` – Form labels, ARIA for errors/required, autocomplete.
- `resources/views/backend/cases/index.blade.php` – Table caption/scope, search/filter labels, alert role, button labels.
- `resources/views/backend/cases/create.blade.php` – Form labels (for/id), aria-required, alert role.
- `resources/views/backend/cases/edit.blade.php` – Form labels, aria-required, remove-row/add-user button labels, table caption/scope.
- `resources/views/backend/cases/show.blade.php` – Table captions/scopes, case users live region, assign-users label, modal ARIA, Remove button labels.
- `resources/views/backend/users/index.blade.php` – “Employee” typo fix, table caption/scope, search label, alert role, action button labels.
- `resources/views/backend/users/create.blade.php` – Form labels, permissions table caption/scope and checkbox aria-labels.
- `resources/views/backend/users/edit.blade.php` – Same as create for form and permissions table.
- `resources/views/backend/default/dashboard.blade.php` – Decorative images marked with `alt=""` and `role="presentation"`.

## Testing Recommendations

1. **Keyboard**: Navigate the entire app with Tab/Shift+Tab and Enter/Space; ensure no traps and that skip link, modals, and dropdowns work.
2. **Screen reader**: Test with NVDA (Windows) or VoiceOver (macOS) to confirm labels, live regions, and table structure are announced correctly.
3. **Contrast**: Verify text and interactive elements meet WCAG AA contrast (4.5:1 for normal text, 3:1 for large text) in your theme; adjust in `custom.css` if needed.
4. **Zoom**: Confirm layout remains usable at 200% zoom and that nothing is clipped or overlapping critical controls.

## Optional Next Steps

- Add a visible “Skip to main content” style (e.g. background and padding) so it’s easier to see when focused.
- Run an automated checker (e.g. axe DevTools, WAVE) and fix any remaining issues.
- Document any theme-specific color or focus overrides in `public/backend-assets/css/custom.css` for future maintainers.
