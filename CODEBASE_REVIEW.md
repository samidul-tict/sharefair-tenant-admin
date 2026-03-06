# Law Tenant – Codebase Review

## Structure Overview

```
law-tenant/
├── app/
│   ├── Helpers/PermissionHelper.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── backend/     (Auth, Case, Default, User)
│   │   │   ├── frontend/    (HomeController)
│   │   │   └── Controller.php
│   │   └── Middleware/MenuPermissionMiddleware.php
│   ├── Mail/EnquiryMail.php
│   ├── Models/              (CaseActivity, CaseUserMapping, CourtCase, Item, User, etc.)
│   └── Providers/AppServiceProvider.php
├── config/                  (app, auth, database, etc.)
├── database/
│   ├── migrations/          (users, cache, jobs, enquiries, stories, tenant/cases)
│   └── seeders/
├── resources/
│   ├── views/
│   │   ├── backend/         (cases, users, default/dashboard, layout, login)
│   │   ├── frontend/         (site pages, layout)
│   │   └── emails/
│   ├── css/, js/
├── routes/web.php           (only web routes; no api.php)
└── public/
```

## Routes Summary

- **Public:** `home.*` (index, about, contact, enquiry, privacy, terms, notice-of-practices), `admin.login` / `admin.login.post`
- **Auth middleware:** All admin routes under `Route::middleware('auth')`
- **Admin:** `admin.dashboard`, `admin.users.*` (index, create, store, show, edit, update, destroy), `admin.cases.*` (index, create, store, show, edit, update, destroy), `admin.case.activity.store`, `admin.case.activity.list`, `admin.cases.updateAssign`, `admin.cases.removeUser`, `admin.logout`

All case routes use the **`admin.cases.*`** names (not `backend.cases.*`). Views live under **`backend.cases.*`** (e.g. `backend.cases.index`).

---

## Fixes Applied

### 1. Case delete redirect (CaseController)
- **Issue:** After deleting a case, redirect used `route('backend.cases.index')`, which does not exist.
- **Change:** Updated to `route('admin.cases.index')` in `CaseController::destroy()`.

### 2. Remove-user URL on case show (already fixed earlier)
- **Issue:** `route('admin.cases.removeUser', $case->id)` threw `RouteNotFoundException` when route cache was stale.
- **Change:** Replaced with `url('/admin/cases/' . $case->id . '/remove-user')` in `resources/views/backend/cases/show.blade.php`.

---

## Recommendations

### Route / view cache
If you still see “Route [admin.cases.removeUser] not defined” or other route/view oddities:

```bash
php artisan route:clear
php artisan view:clear
```

If you use route caching in production, run `php artisan route:cache` only after all route changes are in place.

### Frontend enquiry form – missing route and handler
- **Views:** `resources/views/frontend/site/contact.blade.php` and `enquiry.blade.php` use `route('home.enquiry.store')` as the form action.
- **Gap:** There is no `home.enquiry.store` route or POST handler in `routes/web.php` or `HomeController`. Submitting the form will 404.
- **Existing pieces:** `EnquiryMail` mailable, `emails.enquiry` view, `enquiries` table migration. `DefaultController` references `App\Models\Enquiry` but there is no `Enquiry` model in `app/Models`.
- **Suggested next steps:** Add an `Enquiry` model, a POST route (e.g. `Route::post('/enquiry', ...)->name('home.enquiry.store')`), and a controller method that validates input, saves the enquiry (and/or sends `EnquiryMail`), then redirects or returns JSON as needed.

### Case index sort parameter (fixed)
- **Views:** Case index sort link for “Case Type” was using `sort=case_type`; controller only allows `case_type_value`.
- **Change:** Index view updated to use `case_type_value` for the Case Type column sort so it matches the controller.

### Consistency
- **Case links:** Case list uses `route('admin.cases.show', ['id' => $case->id])`; edit/destroy use `$case->id` directly. Both are valid; keeping `['id' => $case->id]` for show is fine and explicit.
- **Eager loading:** Case index now uses `->with('createdBy')` for cases so the list renders without N+1 and links have correct data.

---

## Quick Reference – Important route names

| Purpose           | Route name                 |
|-------------------|----------------------------|
| Case list         | `admin.cases.index`        |
| Case show         | `admin.cases.show`         |
| Case create       | `admin.cases.create`       |
| Case store        | `admin.cases.store`        |
| Case edit         | `admin.cases.edit`         |
| Case update       | `admin.cases.update`       |
| Case delete       | `admin.cases.destroy`     |
| Assign users      | `admin.cases.updateAssign` |
| Remove LEGAL_RE   | `admin.cases.removeUser`   |
| Case activity list| `admin.case.activity.list` |
| Case activity store | `admin.case.activity.store` |

Use these names in `route()` and redirects; use `backend.cases.*` only for **view** names (e.g. `view('backend.cases.index')`).
