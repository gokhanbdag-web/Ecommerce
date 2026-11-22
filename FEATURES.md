# Ecommerce feature inventory

Status definitions: Planned, In progress, Implemented, Tested, Released. No production features are claimed released.

## Platform
- [Planned] Standalone modular PHP, root index.php, MySQL installer, administrator creation, no rewrite dependency.
- [Planned] English source strings, PO/MO translations, Bootstrap UI, dynamic theme CSS.
- [In progress] Signed GitHub webhook, staging worker, verified live activation, health checks, rollback.

## Users
- [Planned] User list/add/edit/delete, My Profile, email/password changes, roles and capabilities.
- [Planned] AJAX login drawer and role-aware navigation.

## Commerce
- [Planned] Simple/variable/digital products, categories, attributes, pricing, cart, checkout, orders, payments, coupons, tax, returns.
- [Planned] Multi-warehouse stock per product/variation, reservations, atomic paid-order stock deduction, idempotency and stock history.
- [Planned] Marketplace integration engine, ZIP module upload, adapter contract, sync queues, category mapping and logs.

## CMS and visual builder
- [Planned] Pages, blog posts, categories, tags, comments, revisions, SEO fields and on-page SEO scoring.
- [Planned] Shared visual editor, fluid/contained/full-width rows, columns, widget picker, responsive controls and reusable blocks.
- [Planned] Unlimited headers, heroes and footers with page-level assignments, shared widgets and theme settings.

Each feature will be broken into verifiable subfeatures and updated in the same PR as its implementation.
