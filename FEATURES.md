# Ecommerce feature inventory

Status definitions: Planned, In progress, Implemented, Tested, Released. No production features are claimed released.

## Platform
- [In progress] Standalone modular PHP, root index.php and single page.php renderer; installer, MySQL support, administrator creation and rewrite-free routing remain planned.
- [Planned] English source strings, PO/MO translations, Bootstrap UI, dynamic theme CSS.
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
