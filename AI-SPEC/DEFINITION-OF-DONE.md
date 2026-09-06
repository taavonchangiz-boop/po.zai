# DEFINITION OF DONE

یک Phase یا Feature فقط با تمام شرایط زیر Done/PASSED است:

## Code
- code review منطقی انجام شده
- module boundaries رعایت شده
- typecheck موفق
- lint موفق
- dead code مشخص و حذف/مستندسازی شده

## Security
- authentication/authorization بررسی شده
- ورودی‌ها validate شده‌اند
- secret leak بررسی شده
- relevant OWASP controls بررسی شده
- Critical/High unresolved ندارد

## Data
- migration اجرا و verify شده
- constraints/indexes بررسی شده
- transaction/idempotency برای invariantهای لازم وجود دارد

## Tests
- tests مربوط به Feature اجرا شده
- regression tests اجرا شده
- failure pathها تست شده‌اند

## UX
- loading/error/empty/success state وجود دارد
- RTL/Jalali/Persian digits رعایت شده
- keyboard/focus/accessibility بررسی شده

## Performance
- queryهای حساس بررسی شده
- N+1 نداریم
- pagination/caching در صورت نیاز اعمال شده

## Documentation
- تغییرات و روش استفاده/استقرار ثبت شده‌اند

## Evidence
Agent باید commandهای واقعی و نتیجه واقعی را گزارش کند. ادعای تست بدون اجرای آن تخلف از قرارداد است.
