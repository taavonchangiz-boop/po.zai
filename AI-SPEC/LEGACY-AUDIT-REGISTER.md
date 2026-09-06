# LEGACY AUDIT REGISTER

این رجیستر خلاصه یافته‌های ممیزی مرجع‌هاست. کدهای مرجع به‌هیچ‌وجه source-of-truth امنیتی نیستند.

## Asovin — High/Critical
- credentialهای حساس در config hard-coded هستند → rotate/revoke و انتقال به environment/secret store.
- bootstrap اولین superadmin در برابر race condition آسیب‌پذیر است → atomic claim/transaction/unique constraint.
- mutationهای حساس با GET دیده شده‌اند → فقط POST/PATCH/DELETE همراه authorization و CSRF.
- uploadهای فایل/تصویر نیازمند validation سخت‌گیرانه‌تر هستند → MIME sniffing، size/type validation، storage isolation.
- reset token/OTP در بخش‌هایی plaintext یا با کنترل ناکافی نگهداری/مصرف می‌شوند → hash/expiry/attempt limit.
- rate limiting در نقاطی غیراتمی است → shared/atomic limiter.
- SQLite و MySQL/MariaDB semantics در بخش‌هایی مخلوط شده‌اند → یک dialect و migration strategy مشخص.
- controller/viewهای بسیار بزرگ و monolithic هستند → modularization.

## Postyar — Structural/Completeness
- build artifactها از source موجود گسترده‌ترند؛ `.next` نباید جای source قرار گیرد.
- source موجود برای بازسازی کامل release کافی نیست؛ dependency/config/tests موردنیاز باید با evidence واقعی بررسی شوند.
- schema و مدل‌های پیشرفته ارزشمندند و باید به‌عنوان reference domain استفاده شوند، نه copy/paste.
- الگوهای ارزشمند: idempotency، queue lease/fencing، dedup event، hashed token/OTP، audit log، health check، AI job abstraction و ledger/money integer.

## Mandatory rule
هیچ finding ناامن یا unresolved از مرجع نباید بدون طراحی remediation وارد محصول جدید شود.
