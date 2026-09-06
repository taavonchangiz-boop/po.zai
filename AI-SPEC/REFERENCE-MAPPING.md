# REFERENCE MAPPING

## Rules
منابع مرجع:
- `Asovin/` — قابلیت‌ها، UX، مسیرهای محصول، assetهای بصری و فونت.
- `postyar/` — قابلیت‌های پیشرفته، schema/model و الگوهای معماری.

هیچ کد legacy نباید بدون security review منتقل شود.

## Mapping method
برای هر feature نهایی ثبت شود:
- source file/path
- observed behavior
- business rule
- security implications
- target module
- target tests
- migration/data impact

## Important legacy caveat
بخش source پروژه postyar در archive کامل و قابل‌بازتولید نیست؛ build artifact به‌تنهایی source-of-truth نیست. Agent باید هر ادعای قابلیت را با evidence واقعی repository بررسی کند و چیزی را از روی نام فایل یا build output فرض نکند.

## Asset rule
فقط asset بصری Asovin که کارفرما تأیید کرده قابل انتقال به public asset layer است. هر asset مبهم BLOCKED است تا visually verified شود.
