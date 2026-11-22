# Ecommerce feature inventory

Status definitions: Planned, In progress, Implemented, Tested, Released. No production features are claimed released.

## Platform
- [In progress] Standalone modular PHP, root index.php and single page.php renderer. SQLite CLI installer and versioned migrations implemented (CI pending); web installer with first administrator setup, login and general settings implemented (CI pending); MySQL support, production hardening and rewrite-free routing remain planned.
- [In progress] English source strings, initial Turkish PO/POT catalogs, MO compiler and translator (CI pending). Site language selector and Bootstrap general settings screen implemented (CI pending); complete string extraction, installer localization and dynamic theme CSS remain planned.
- [In progress] Signed GitHub webhook and direct deploy script (implemented, not server-tested); live activation, health checks and rollback (planned).

## Users
- [Planned] User list/add/edit/delete, My Profile, email/password changes, roles and capabilities.
- [Planned] AJAX login drawer and role-aware navigation.

## Commerce
- [Planned] Simple/variable/digital products, categories, attributes, pricing, cart, checkout, orders, payments, coupons, tax, returns.
- [Planned] Multi-warehouse stock per product/variation, reservations, atomic paid-order stock deduction, idempotency and stock history.
- [Planned] Marketplace integration engine, ZIP module upload, adapter contract, sync queues, category mapping and logs.

## CMS and visual builder
- [Implemented; testing pending] Database-backed published page lookup, selected homepage via settings, single shared page.php, basic JSON heading/paragraph rendering. Admin page CRUD, visual editor, blog, revisions, SEO remain planned.
- [Planned] Shared visual drag-and-drop editor, fluid/contained/full-width rows, columns, widget picker, responsive controls and reusable blocks.
- [Implemented; testing pending] Menu schema, assigned primary menu lookup and page links. [Planned] Menu CRUD, ordering/nesting UI, menu locations, unlimited headers, heroes and footers with page-level assignments, shared widgets and theme settings.

Each feature will be broken into verifiable subfeatures and updated in the same PR as its implementation.

## Release acceptance rules
- Every schema change updates the clean installer path, versioned migrations and installation/upgrade tests in the same PR.
- Every new user-facing English string updates POT, Turkish PO and compiled MO output; CI must validate translations.
- Settings > General > Site Language must become functional before the first public release. Default en_US, Turkish tr_TR.
- No production release without admin setup, protected storage, real database configuration and functional smoke tests.

## PR #4 scope and limitations
- [Implemented; CI pending] Web installer with initial administrator creation, password hashing and one-time setup guard; SQLite database stored outside public_html by default.
- [Implemented; CI pending] Session login/logout, CSRF validation, protected general settings and en_US/tr_TR selector.
- [Planned] MySQL database connection wizard, installation locks/race protection, brute-force throttling, email verification and production deployment validation.
