# PHASE STATUS

این فایل کنترل وضعیت اجرایی AI است.

## Current
PHASE: 0
STATUS: NOT_STARTED
LAST_COMMIT:
OWNER: AI CODING AGENT

## Gate Rules
- هر اجرا فقط یک Phase.
- `PASSED` فقط با evidence واقعی.
- `FAILED` یعنی اصلاح همان Phase، بدون عبور.
- `BLOCKED` یعنی نیاز به رفع مانع واقعی؛ حدس و bypass ممنوع.

## Phase Table
| Phase | Status | Evidence | Commit |
|---|---|---|---|
| 0 Inventory | NOT_STARTED | | |
| 1 Requirements | NOT_STARTED | | |
| 2 Security | NOT_STARTED | | |
| 3 Architecture | NOT_STARTED | | |
| 4 Data | NOT_STARTED | | |
| 5 Infrastructure | NOT_STARTED | | |
| 6 Identity | NOT_STARTED | | |
| 7 Core Product | NOT_STARTED | | |
| 8 Scheduler/Queue | NOT_STARTED | | |
| 9 Messaging | NOT_STARTED | | |
| 10 AI | NOT_STARTED | | |
| 11 Billing | NOT_STARTED | | |
| 12 Integrations | NOT_STARTED | | |
| 13 Admin/User | NOT_STARTED | | |
| 14 UI/UX | NOT_STARTED | | |
| 15 Hardening | NOT_STARTED | | |
| 16 Testing | NOT_STARTED | | |
| 17 Performance | NOT_STARTED | | |
| 18 cPanel | NOT_STARTED | | |
| 19 Final Audit | NOT_STARTED | | |
| 20 Release | NOT_STARTED | | |

## Update Protocol
Agent باید پس از هر execution همین فایل را با status، evidence و commit واقعی به‌روز کند؛ وضعیت جعلی یا PASS صوری ممنوع.
