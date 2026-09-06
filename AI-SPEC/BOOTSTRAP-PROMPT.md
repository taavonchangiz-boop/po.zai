# BOOTSTRAP PROMPT — برای z.ai / arena.ai

این repository را Source of Truth اجرایی پروژه در نظر بگیر:

https://github.com/taavonchangiz-boop/po.zai

ابتدا تمام فایل‌های زیر را بخوان:

- `AI-SPEC/MASTER-PROMPT.md`
- `AI-SPEC/PROJECT-CONTRACT.md`
- `AI-SPEC/BRAND-RULES.md`
- `AI-SPEC/SECURITY-RULES.md`
- `AI-SPEC/ARCHITECTURE-RULES.md`
- `AI-SPEC/FEATURE-MATRIX.md`
- `AI-SPEC/REFERENCE-MAPPING.md`
- `AI-SPEC/AI-CODING-RULES.md`
- `AI-SPEC/PHASE-PLAN.md`
- `AI-SPEC/DEFINITION-OF-DONE.md`
- `AI-SPEC/DEPLOYMENT-CPANEL.md`
- `AI-SPEC/PHASE-STATUS.md`

سپس فقط Phase 0 را انجام بده.

Phase 0 فقط شامل inventory و audit است و نباید implementation محصول جدید را شروع کنی.

در Phase 0 باید:
1. `Asovin/` و `postyar/` را file-by-file بررسی کنی.
2. قابلیت‌ها، dependencyها، مدل داده، routeها و assetها را استخراج کنی.
3. assetهای بصری را visually audit کنی.
4. canonical logo مورد تأیید کارفرما را فقط بر اساس evidence واقعی شناسایی کنی.
5. Vazirmatn را پیدا و verify کنی.
6. ریسک‌های امنیتی را ثبت کنی.
7. معماری پیشنهادی پروژه مستقل را ارائه کنی.
8. missing files/dependencies را ثبت کنی.
9. سازگاری با Node.js 22.23.2 و cPanel بدون SSH را بررسی کنی.

تا زمانی که Phase 0 کامل و مستند نشده است، هیچ قابلیت application جدیدی نساز.

هر ادعای «انجام شد» باید evidence واقعی داشته باشد.

اگر فایل یا اطلاعات موردنیاز وجود ندارد، حدس نزن؛ آن را گزارش کن.

در پایان تنها این موارد را ارائه کن:
- Inventory
- Feature Matrix update
- Security Risk Register
- Technology Inventory
- Asset/Brand Audit
- Font Audit
- Missing File/Dependency Report
- Architecture Proposal
- Deployment Compatibility Report
- Phase Status

سپس متوقف شو و منتظر Gate بعدی بمان.
