# AI CODING RULES

1. Never claim a file was tested unless a real command produced the result.
2. Never claim a feature is implemented if only a stub/mock exists, unless the contract explicitly marks it as a stub.
3. Never silently drop requirements.
4. Never rewrite unrelated files merely to simplify work.
5. Before deleting a file, record why and verify references.
6. Prefer small, reviewable commits.
7. Keep generated/build output out of source unless explicitly required.
8. Do not commit `.env` or secrets.
9. Do not use `any` broadly to hide type errors.
10. Do not disable ESLint/TypeScript/security rules merely to pass CI.
11. Use explicit error handling at external boundaries.
12. Add tests for critical business rules and failure paths.
13. Keep provider integrations behind adapters.
14. Keep UI brand rules centralized.
15. Use Persian labels in user-facing UI and Vazirmatn as default Persian font.
16. Use Jalali presentation consistently without corrupting UTC persistence semantics.
17. For security-sensitive changes, add regression tests.
18. For database changes, include migration and rollback considerations.
19. For async jobs, include retry/idempotency/dedup behavior.
20. Never continue past a failed Phase Gate.
