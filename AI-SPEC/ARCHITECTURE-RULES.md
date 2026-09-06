# ARCHITECTURE RULES — پٌست‌یار

## Target
Node.js 22.23.2 + TypeScript + Next.js + Prisma + MySQL/MariaDB.

## Style
Modular Monolith with explicit domain/application/infrastructure boundaries. API/UI may share types only through deliberate contracts; business logic must not live inside React components or route handlers.

## Required boundaries
- Identity/Auth
- Users/Roles/Permissions
- Workspace/Tenant
- Channels/Connectors
- Posts/Media/Captions/Buttons
- Scheduler/Queue/Workers
- Bots/Workflow/Inbound/Outbound
- AI Jobs/Providers
- Billing/Plans/Subscriptions/Wallet/Payments
- Notifications/SMS/Email
- Analytics
- Gold Pricing/Bots
- Integrations/WooCommerce/WordPress
- Admin/Settings/Audit/Health

## Rules
- Shared kernel must stay small.
- Avoid god classes/controllers.
- Dependency direction must be enforced.
- External providers behind adapters.
- Database access behind repositories/services where appropriate.
- Transactions for multi-write business invariants.
- Every critical async operation has idempotency/deduplication strategy.
- Long-running work must be worker-safe and cPanel-Cron compatible.
- Config validated at startup.
- No production secret defaults.
- Database migrations versioned and reversible where practical.

## Data
Money stored as integers/minor units. Dates stored in UTC where technically necessary; presentation is Jalali. IDs should avoid predictable public enumeration where relevant.

## Deployment
Must build and run on cPanel Node.js 22.23.2 without SSH. Avoid architecture that requires Docker, Kubernetes or Redis as a mandatory runtime dependency.
