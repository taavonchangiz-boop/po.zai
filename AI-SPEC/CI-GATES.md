# CI GATES — پٌست‌یار

## Required gates
1. Install/dependency integrity
2. Typecheck
3. Lint
4. Unit tests
5. Integration tests
6. Database migration validation
7. Security/dependency audit
8. Secret scan
9. Brand/asset audit
10. Forbidden-string audit
11. Build
12. Production-like smoke test

## Brand gate
Fail when unauthorized logo/mascot assets, forbidden third-party branding strings, or unauthorized brand imports are detected.

## Security gate
Fail on Critical/High findings unless an explicit documented risk acceptance exists.

## Evidence
CI output must be retained as release evidence. Local claims are not a substitute for CI results.

## No bypass
Do not add `continue-on-error`, disable checks, weaken rules, or suppress findings merely to obtain green CI.
