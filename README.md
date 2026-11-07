## Portfol.io

A modern platform for photographers and videographers to showcase, manage, and monetize their work.

### Table of Contents
- Overview
- Core Feature Set
- Payment & Billing
- Admin Capabilities
- Portfolio & Collection Customization
- Technical Stack
- Development & Setup (Laravel Sail)
- Testing Strategy
- Security & Compliance
- Roadmap (Planned Enhancements)
- Contribution

---
### Overview
Portfol.io enables creatives to build public or private portfolios, manage client deliverables, sell photos/videos, and handle subscriptions with Stripe billing & Connect payouts. The platform provides granular control over presentation, access, and monetization while remaining easy to manage from an integrated dashboard.

---
### Core Feature Set
- User Accounts (UUID primary keys, profile & social fields)
- Portfolio Display Modes:
  - Masonry Photo Grid (infinite scroll, dynamic column count)
  - Collections View (event/date oriented galleries with cover photos)
- Collections
  - Private/public toggle with password protection
  - Cover photo selection + focal position (object-position) control
  - Watermark & visibility flags (hide from public portfolio)
  - Archive generation & secure timed download URLs
- Sets inside collections for structured grouping
- Photo Management
  - Upload (limits based on plan / override)
  - Bulk delete (initial) & movement between sets
  - Privacy flags + portfolio visibility filtering
- Feature Override System (grant all features temporarily or indefinitely)
- Watermark customization (user-level)
- Dark mode support across all new and legacy screens
- Infinite scroll with smooth, animated masonry hydration
- Client email sharing for private collections

---
### Payment & Billing
- Subscription Plans (Free / Photographer / Videographer + annual pricing & discounts)
- Dynamic annual + monthly pricing (with Stripe Price IDs stored per plan)
- Stripe Billing Integration (Laravel Cashier)
  - Subscribe / Swap / Cancel / Resume
  - Upcoming billing period information
  - Invoice download
  - SCA payment confirmation route
- Stripe Connect Integration
  - Creators can connect accounts for future payout flows (image sales, product sales)
- Admin configurable currency (default GBP, can switch symbol & ISO code)

---
### Admin Capabilities
- Dashboard metrics: users, new users, subscribers, orders, GMV (initial placeholders where applicable)
- Users management:
  - Toggle admin
  - Toggle feature override & set expiry
  - Impersonation (secure banner, exit flow)
- Subscription Plans CRUD:
  - Create/update/delete plans
  - Set Stripe Product / Price IDs
  - Annual pricing & discount percent
  - Feature & limit flags (private collections, video upload, watermarking, selling, templates, etc.)
  - Recommended plan highlighting
- Settings:
  - Currency configuration (default currency + symbol)
  - Legacy Stripe quick edit removed (handled in Plan CRUD now)

---
### Portfolio & Collection Customization
- User styling preferences:
  - Font family selection (system, Nunito, Inter, Playfair, Roboto, Open Sans)
  - Accent color (CSS variable applied to interactive elements)
  - Theme preference (auto/light/dark)
- Collection cover image focal point for better hero composition

---
### Technical Stack
- Backend: Laravel 12 (PHP 8.2+), Laravel Cashier 15 (Stripe), Sanctum 4
- Frontend: Blade, Livewire 3 (bundles Alpine.js), Tailwind CSS 4, Vite 8
- Icons: Heroicons via `blade-ui-kit/blade-heroicons` (`<x-ui.icon name="photo" />`)
- Storage: AWS S3 (images, thumbnails, watermarked copies, archives)
- Queue: database (configurable)

### Design system
- Tokens live in `resources/css/app.css` (`@theme`): `brand-*` colour scale, `rounded-control`, `rounded-card`, font. Use `brand-*` rather than raw `indigo-*`.
- Components live in `resources/views/components/ui` (button, input, select, textarea, checkbox, field, card, page-header, empty-state, badge, alert, icon). In local development, `/dev/ui` shows them all.
- Dark mode follows the visitor's system setting; adding `dark` or `light` to `<html>` overrides it (the sidebar theme switch does this).
- `can-hover:` variant: hide controls until hover only on devices with a mouse, so touch users still see them.
- Confirmations: `window.confirmAction({...})`, or `<form data-confirm="…" data-confirm-title="…" data-confirm-danger>`. Don't use `window.confirm()`.
- Flash messages: redirect `->with('success'|'error'|'warning'|'info', '…')`; the layout shows them.
- Public pages use the photographer's theme (`partials/portfolio-styles`). Wrap app-style surfaces (forms, order summaries) in `.portfolio-neutral` so theme colours don't apply to them.

### Configuration worth knowing
- `SUPPORT_EMAIL` – address shown on public and legal pages.
- `PLATFORM_FEE_PERCENT` – share of print sales kept by the platform (default 10). Shown to photographers on Payouts and Print products.
- Currency – set in Admin › Settings; used for all prices, Stripe checkout and payouts (`App\Support\Currency`).

### Development & Setup
Prerequisites: Docker & Docker Compose.

```bash
# Install dependencies
composer install
npm install

# Start environment
./vendor/bin/sail up -d

# Run migrations & seed plans
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan db:seed --class=SubscriptionPlanSeeder

# Build assets (production)
npm run build
# or for dev
npm run dev
```

Environment variables to confirm:
- STRIPE_KEY / STRIPE_SECRET
- STRIPE_WEBHOOK_SECRET (for webhook route)
- Filesystem (S3 keys) for photo storage

---
### Testing
- PHP: `php artisan test` (needs MySQL; see `phpunit.xml`, database `testing`).
- Accessibility: axe-core checks on the main customer and client pages at desktop and phone sizes. Seed and run the app, then:
  ```bash
  php artisan migrate:fresh && php artisan db:seed --class=UxDemoSeeder
  php artisan serve &
  npx playwright install chromium   # first time only
  APP_URL=http://127.0.0.1:8000 npm run test:a11y
  ```
- CI (`.github/workflows/ci.yml`) runs both on pushes to `develop`/`main` and on pull requests.

### Security & Compliance
- Role separation (admin middleware)
- Impersonation secured (cannot impersonate admins / self; clear session revert)
- Password-protected collections: hashed storage of collection password
- Stripe webhook route isolated
- CSRF protection via standard Laravel middleware
- Access control for private collections & hidden portfolio assets
- Planned: rate limiting for download requests & archive generation

---
### Roadmap (Planned Enhancements)
Short Term:
- Drag & drop ordering for collections, sets, and photos
- Bulk operations: move, tag, watermark toggle, privacy toggle
- Enhanced photo search & tagging filters
- Real-time progress UI for archive generation

Medium Term:
- Portfolio template marketplace (theme presets, layout variants)
- Advanced theming (custom CSS sandbox, per-collection accent overrides)
- Client proofing workflow (approve / reject selections, feedback comments)
- Analytics dashboard (views, downloads, sales conversions)
- Video transcoding pipeline (multi-resolution streaming)

Long Term / Future:
- Printing API integration (auto-fulfill physical product orders)
- Licensing & digital contract management
- AI-assisted tagging & smart album generation
- Team / multi-user studio accounts
- Public API & OAuth developer access

---
### Contribution
Internal project currently. As features stabilize, introduce:
- Conventional Commits
- CI pipeline (lint, type checks, tests)
- Security audit checklist (dependencies & permission hardening)

Coding Guidelines:
- Keep controllers lean—delegate heavy logic to services/jobs
- Prefer Livewire for interactive admin screens; keep blade components reusable
- Avoid duplication: unify plan/feature flags via `SubscriptionPlan` model capabilities

---
### Cleanup Notes
Legacy Stripe quick-edit section removed from settings in favor of full Subscription Plan CRUD. Any references to `settings.plans.update` are deprecated. Feature override logic centralized on `User` model. Continue auditing for hard-coded plan slugs outside pricing logic.

---
### Status
The application is in active feature expansion phase with a stable foundation for billing, portfolio presentation, and admin management.
