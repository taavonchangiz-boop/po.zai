# DEPLOYMENT — cPanel / Node.js 22.23.2

## Target
- Node.js 22.23.2
- cPanel
- Terminal available
- SSH unavailable

## Constraints
Deployment must not require SSH. Do not make Docker/Kubernetes/Redis mandatory dependencies.

## Required outputs
- exact Node.js application setup for cPanel
- exact environment variables list
- MySQL/MariaDB creation and migration procedure
- build command
- start command compatible with cPanel Node app
- static/public filesystem guidance
- writable directories and permissions
- cron commands for scheduler/worker
- log locations and rotation guidance
- rollback procedure
- health-check endpoint
- post-deploy smoke test

## Worker
A one-shot worker mode such as `worker --once` is required for cron compatibility where persistent workers are not guaranteed.

## Secrets
No secret is committed. Production values are configured in cPanel environment configuration or equivalent secure mechanism.

## Deployment gate
A release is not accepted until a clean production-like cPanel deployment path is documented and all smoke tests pass.
