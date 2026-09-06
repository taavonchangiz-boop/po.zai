<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="index,follow">
    <link rel="canonical" href="https://asovin.ir/privacy">
    <meta name="description" content="سیاست حفظ حریم خصوصی کاربران پُست‌یار و نحوه جمع‌آوری، استفاده، نگهداری و حذف اطلاعات.">
    <meta name="theme-color" content="#0a0a0a">
    <title><?php echo htmlspecialchars($title ?? 'حریم خصوصی کاربران پُست‌یار'); ?></title>
    <?php
        $assetsUrl = \WHCM\Core\Bootstrap::getAssetsUrl();
        $siteUrl = rtrim(\WHCM\Core\Bootstrap::getConfig('app.url', 'https://asovin.ir'), '/');
    ?>
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo $assetsUrl; ?>/icons/favicon-32x32.png">
    <style>
        @font-face{font-family:Vazirmatn;src:url('<?php echo $assetsUrl; ?>/fonts/Vazirmatn-Regular.woff2') format('woff2');font-weight:400;font-display:swap}
        @font-face{font-family:Vazirmatn;src:url('<?php echo $assetsUrl; ?>/fonts/Vazirmatn-Bold.woff2') format('woff2');font-weight:700;font-display:swap}
        @font-face{font-family:Vazirmatn;src:url('<?php echo $assetsUrl; ?>/fonts/Vazirmatn-Black.woff2') format('woff2');font-weight:900;font-display:swap}
        :root{color-scheme:dark;--bg:#08080b;--card:#111118;--text:#f5f5f7;--muted:#b8b8c7;--line:#292938;--indigo:#818cf8;--pink:#f472b6;--green:#34d399}
        *{box-sizing:border-box}
        html{scroll-behavior:smooth}
        body{margin:0;background:radial-gradient(circle at 90% 0,rgba(99,102,241,.16),transparent 30rem),radial-gradient(circle at 10% 25%,rgba(236,72,153,.09),transparent 26rem),var(--bg);color:var(--text);font-family:Vazirmatn,Tahoma,sans-serif;line-height:2}
        a{color:var(--indigo);text-decoration:none}
        a:hover{text-decoration:underline}
        .container{width:min(1120px,calc(100% - 32px));margin-inline:auto}
        .site-header{position:sticky;top:0;z-index:50;background:rgba(8,8,11,.88);backdrop-filter:blur(16px);border-bottom:1px solid rgba(255,255,255,.07)}
        .top-nav{min-height:72px;display:flex;align-items:center;justify-content:space-between;gap:20px}
        .brand{display:flex;align-items:center;gap:12px;color:#fff;font-size:1.15rem;font-weight:900}
        .brand:hover{text-decoration:none}
        .brand img{width:42px;height:42px;object-fit:contain;border-radius:12px;background:#fff}
        .back{border:1px solid var(--line);color:#ddddea;padding:7px 14px;border-radius:11px;font-size:.9rem}
        .back:hover{background:#ffffff0a;text-decoration:none}
        main{padding:64px 0 80px}
        .hero{text-align:center;max-width:850px;margin:0 auto 40px}
        .badge{display:inline-flex;color:#c7d2fe;border:1px solid #6366f166;background:#6366f11a;padding:5px 13px;border-radius:999px;font-size:.82rem;font-weight:700}
        h1{font-size:clamp(2rem,5vw,3.35rem);line-height:1.5;margin:18px 0 12px;font-weight:900;background:linear-gradient(100deg,#fff,#c7d2fe 48%,#fbcfe8);-webkit-background-clip:text;color:transparent}
        .lead{color:#c7c7d3;font-size:1.04rem;margin:0}
        .meta{margin-top:16px;color:#a5a5b7;font-size:.9rem;font-weight:700}
        .summary{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:28px}
        .summary div{border:1px solid var(--line);background:linear-gradient(145deg,#151520,#0e0e15);padding:18px;border-radius:16px;text-align:center}
        .summary strong{display:block;color:#fff;margin-bottom:4px}
        .summary span{font-size:.86rem;color:var(--muted)}
        .page-layout{display:grid;grid-template-columns:255px minmax(0,1fr);gap:24px;align-items:start}
        .toc{position:sticky;top:94px;align-self:start;max-height:calc(100vh - 116px);overflow:auto;border:1px solid var(--line);background:#111118;padding:18px;border-radius:18px;z-index:1}
        .toc-title{display:block;margin-bottom:10px;color:#fff}
        .toc-list{display:block;margin:0;padding:0;list-style:none}
        .toc-list li{display:block;margin:0}
        .toc-list a{display:block;color:#aaaabd;padding:5px 8px;border-radius:8px;font-size:.82rem;line-height:1.8}
        .toc-list a:hover{background:#ffffff09;color:#fff;text-decoration:none}
        article{display:block;min-width:0;position:relative;z-index:2}
        section{display:block;scroll-margin-top:96px;background:rgba(17,17,24,.92);border:1px solid var(--line);border-radius:18px;padding:27px;margin:0 0 18px;overflow:hidden}
        h2{font-size:1.3rem;margin:0 0 13px;color:#fff}
        h3{font-size:1.03rem;color:#eeeef5;margin:23px 0 7px}
        p{margin:8px 0;color:#c5c5d1}
        ul,ol{margin:9px 0;padding-right:24px;color:#c5c5d1}
        li{margin:5px 0}
        .notice{border-right:3px solid var(--indigo);background:#6366f112;padding:13px 15px;border-radius:10px;color:#d9d9e5;margin-top:14px}
        .notice.success{border-right-color:var(--green);background:#10b98110}
        .subsection{padding-top:5px}
        .contact-card{background:linear-gradient(120deg,#6366f11c,#ec48991a)}
        .signature{text-align:center;padding:30px}
        .signature img{display:block;max-width:190px;max-height:80px;object-fit:contain;margin:0 auto 12px}
        .signature strong{font-size:1.25rem}
        footer{border-top:1px solid var(--line);padding:28px 0;color:#8b8b9b;text-align:center;font-size:.85rem}
        @media(max-width:850px){.page-layout{grid-template-columns:1fr}.toc{position:relative;top:auto;max-height:none}.toc-list{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:2px 10px}.summary{grid-template-columns:1fr}.hero{margin-bottom:28px}main{padding-top:42px}}
        @media(max-width:540px){.container{width:min(100% - 22px,1120px)}section{padding:21px 17px}.brand span{display:none}.toc-list{grid-template-columns:1fr}h1{font-size:2rem}.back{font-size:.8rem;padding:6px 10px}}
    </style>
</head>
<body>
<header class="site-header">
    <nav class="container top-nav" aria-label="ناوبری اصلی">
        <a class="brand" href="<?php echo htmlspecialchars($siteUrl); ?>/">
            <img src="<?php echo $assetsUrl; ?>/images/logo-white-bg.webp" alt="نشان پُست‌یار">
            <span>پُست‌یار</span>
        </a>
        <a class="back" href="<?php echo htmlspecialchars($siteUrl); ?>/">بازگشت به صفحه اصلی ←</a>
    </nav>
</header>

<main class="container">
    <div class="hero">
        <span class="badge">سیاست حفظ حریم خصوصی</span>
        <h1>حریم خصوصی کاربران پُست‌یار</h1>
        <p class="lead">پُست‌یار به حریم خصوصی کاربران خود احترام می‌گذارد و تلاش می‌کند اطلاعات کاربران را تنها در حد لازم برای ارائه، حفظ امنیت و بهبود خدمات پردازش کند.</p>
        <p class="meta">نسخه ۱.۴ — تاریخ اجرا و آخرین به‌روزرسانی: ۲۴ مرداد ۱۴۰۵</p>
    </div>

    <div class="summary" aria-label="خلاصه سیاست حریم خصوصی">
        <div><strong>فروش اطلاعات ممنوع</strong><span>اطلاعات شخصی کاربران را نمی‌فروشیم.</span></div>
        <div><strong>دسترسی به اندازه نیاز</strong><span>اطلاعات تنها برای اهداف مشخص و مرتبط با ارائه خدمات پردازش می‌شوند.</span></div>
        <div><strong>حق کنترل و حذف</strong><span>کاربران می‌توانند در حدود امکانات سامانه و الزامات قانونی، اصلاح یا حذف اطلاعات حساب خود را درخواست کنند.</span></div>
    </div>

    <div class="page-layout">
        <aside class="toc" aria-label="فهرست مطالب">
            <strong class="toc-title">فهرست مطالب</strong>
            <ol class="toc-list">
                <li><a href="#scope">۱. دامنه و مسئول پردازش داده</a></li>
                <li><a href="#data">۲. اطلاعات دریافتی</a></li>
                <li><a href="#purposes">۳. اهداف استفاده</a></li>
                <li><a href="#cookies">۴. کوکی‌ها</a></li>
                <li><a href="#sharing">۵. اشتراک‌گذاری</a></li>
                <li><a href="#publishing">۶. انتشار محتوا</a></li>
                <li><a href="#transfer">۷. انتقال خارج از ایران</a></li>
                <li><a href="#ai">۸. هوش مصنوعی</a></li>
                <li><a href="#retention">۹. نگهداری داده</a></li>
                <li><a href="#deletion">۱۰. حذف حساب</a></li>
                <li><a href="#security">۱۱. امنیت</a></li>
                <li><a href="#rights">۱۲. حقوق کاربران</a></li>
                <li><a href="#third-parties">۱۳. اطلاعات اشخاص ثالث</a></li>
                <li><a href="#children">۱۴. حریم خصوصی کودکان</a></li>
                <li><a href="#changes">۱۵. تغییرات سیاست</a></li>
                <li><a href="#contact">۱۶. ارتباط با ما</a></li>
                <li><a href="#acceptance">۱۷. پذیرش سیاست</a></li>
            </ol>
        </aside>

        <article>
            <section id="scope">
                <h2>۱. دامنه و مسئول پردازش داده</h2>
                <p>این سیاست حفظ حریم خصوصی برای وب‌سایت <a href="https://asovin.ir/">asovin.ir</a>، پنل تحت وب و وب‌اپلیکیشن پُست‌یار و APIهای مرتبط با آن‌ها اعمال می‌شود.</p>
                <p>در این سند، عبارت‌های «پُست‌یار»، «ما» و «سامانه» به سرویس پُست‌یار و ارائه‌دهنده آن اشاره دارند.</p>
                <p>پُست‌یار در حال حاضر به‌صورت <strong>وب‌سایت و وب‌اپلیکیشن</strong> ارائه می‌شود و این سیاست، در وضعیت فعلی، مربوط به خدمات تحت وب پُست‌یار است.</p>
                <p>با ایجاد حساب یا استفاده از خدمات پُست‌یار، شما این سیاست را مطالعه کرده و از شیوه‌های پردازش اطلاعات توضیح‌داده‌شده در آن آگاه می‌شوید.</p>
                <p>اگر برای یک کسب‌وکار، مجموعه یا کانال فعالیت می‌کنید، مسئولیت داشتن مجوز لازم برای قراردادن، پردازش یا انتشار محتوای آن مجموعه در پُست‌یار بر عهده شماست.</p>
            </section>

            <section id="data">
                <h2>۲. چه اطلاعاتی دریافت می‌کنیم؟</h2>
                <p>بسته به نحوه استفاده شما از پُست‌یار، ممکن است برخی از اطلاعات زیر دریافت و پردازش شوند.</p>

                <div class="subsection"><h3>۲.۱. اطلاعات حساب و پروفایل</h3>
                    <p>ممکن است اطلاعات زیر را از کاربر دریافت کنیم:</p>
                    <ul><li>نام و نام خانوادگی</li><li>نشانی ایمیل</li><li>شماره همراه، در صورت ثبت</li><li>نام کسب‌وکار</li><li>نوع فعالیت یا زمینه کاری</li><li>تاریخ تولد، در صورت تکمیل</li><li>کد معرف، در صورت استفاده</li><li>اطلاعات مربوط به اشتراک و وضعیت حساب</li></ul>
                    <p>این اطلاعات برای ثبت‌نام، ورود، بازیابی حساب، پشتیبانی، ارتباطات خدماتی و مدیریت حساب استفاده می‌شوند.</p>
                </div>

                <div class="subsection"><h3>۲.۲. اطلاعات احراز هویت</h3>
                    <p>برای امنیت حساب و ارائه خدمات ممکن است اطلاعات فنی مربوط به احراز هویت مانند موارد زیر پردازش شود:</p>
                    <ul><li>رمز عبور به‌صورت هش‌شده</li><li>توکن نشست</li><li>توکن‌های API</li><li>اطلاعات فنی مورد نیاز برای احراز هویت</li><li>کدهای یک‌بارمصرف، در صورت استفاده از این قابلیت</li></ul>
                    <p>پُست‌یار رمز عبور کاربران را به‌صورت متن ساده برای استفاده عادی سامانه ذخیره نمی‌کند.</p>
                </div>

                <div class="subsection"><h3>۲.۳. اطلاعات کانال‌ها و اتصال‌ها</h3>
                    <p>در صورت اتصال کانال یا سرویس خارجی به پُست‌یار، ممکن است اطلاعات مورد نیاز برای برقراری و مدیریت اتصال پردازش شود، از جمله:</p>
                    <ul><li>نام و شناسه کانال تلگرام یا بله</li><li>اطلاعات فنی مربوط به ربات یا اتصال</li><li>توکن ربات یا اطلاعات دسترسی لازم</li><li>تنظیمات Webhook</li><li>تنظیمات اتصال ووکامرس</li><li>اطلاعات لازم برای انتشار محتوا</li><li>وضعیت اتصال و تنظیمات مربوط به آن</li></ul>
                    <p>این اطلاعات برای انجام قابلیت‌هایی استفاده می‌شوند که کاربر شخصاً در سامانه فعال کرده است.</p>
                    <p>کاربر مسئول اطمینان از داشتن مجوز لازم برای اتصال و مدیریت کانال‌ها و سرویس‌های مربوطه است.</p>
                </div>

                <div class="subsection"><h3>۲.۴. محتوا و ارتباطات</h3>
                    <p>ممکن است محتوایی که کاربر در سامانه ایجاد، ذخیره یا بارگذاری می‌کند پردازش شود، از جمله:</p>
                    <ul><li>متن و عنوان پست</li><li>تصاویر و ویدئوها</li><li>فایل‌های مرتبط</li><li>زمان انتشار</li><li>پست‌های زمان‌بندی‌شده</li><li>کانال‌های مقصد</li><li>پاسخ‌های خودکار</li><li>پیام‌های دریافتی از طریق قابلیت‌های مرتبط</li><li>نام و شناسه فرستنده، در صورت ارائه توسط سرویس مربوطه</li><li>تیکت‌های پشتیبانی</li><li>فایل‌های پیوست تیکت</li></ul>
                    <p>این اطلاعات برای زمان‌بندی، انتشار، مدیریت محتوا، پاسخگویی خودکار، صندوق پیام و پشتیبانی استفاده می‌شوند.</p>
                </div>

                <div class="subsection"><h3>۲.۵. اطلاعات اشتراک و پرداخت</h3>
                    <p>برای مدیریت اشتراک و سوابق مالی ممکن است اطلاعات زیر پردازش یا ذخیره شود:</p>
                    <ul><li>نوع پلن</li><li>مبلغ پرداخت</li><li>روش و وضعیت پرداخت</li><li>تاریخ و زمان پرداخت</li><li>شماره یا شناسه پیگیری تراکنش</li><li>کد تخفیف</li><li>اطلاعات مربوط به کیف پول یا اعتبار</li><li>تصویر رسید پرداخت، در صورت بارگذاری توسط کاربر</li></ul>
                    <p>اطلاعات محرمانه کارت بانکی مانند رمز کارت، CVV2 و رمز دوم نباید در اختیار پُست‌یار قرار گیرد.</p>
                    <p>در صورت استفاده از درگاه پرداخت، اطلاعات حساس پرداخت در محیط درگاه مربوطه وارد می‌شود و پُست‌یار اطلاعات لازم برای ثبت و بررسی وضعیت تراکنش را دریافت می‌کند.</p>
                </div>

                <div class="subsection"><h3>۲.۶. داده‌های فنی و امنیتی</h3>
                    <p>ممکن است هنگام استفاده از سامانه اطلاعات فنی زیر ثبت یا پردازش شود:</p>
                    <ul><li>نشانی IP</li><li>نوع مرورگر</li><li>User-Agent</li><li>تاریخ و زمان درخواست</li><li>اطلاعات نشست</li><li>خطاهای فنی</li><li>تلاش‌های ورود</li><li>رخدادهای امنیتی</li><li>اطلاعات فنی مرتبط با عملکرد سامانه</li></ul>
                    <p>این اطلاعات برای امنیت، رفع خطا، جلوگیری از سوءاستفاده، شناسایی فعالیت غیرمجاز و حفظ پایداری سرویس استفاده می‌شوند.</p>
                </div>

                <div class="subsection"><h3>۲.۷. آمار لینک و کانال</h3>
                    <p>برای ارائه گزارش‌های عملکردی ممکن است اطلاعاتی مانند موارد زیر پردازش شوند:</p>
                    <ul><li>تعداد بازدید</li><li>تعداد کلیک</li><li>زمان کلیک</li><li>IP</li><li>User-Agent</li><li>نشانی ارجاع‌دهنده</li></ul>
                    <p>این اطلاعات برای ارائه آمار و گزارش عملکرد مربوط به قابلیت‌های سامانه استفاده می‌شوند.</p>
                    <p class="notice success"><strong>پُست‌یار در حال حاضر از سرویس‌های تحلیل شخص ثالث مانند Google Analytics برای تحلیل رفتار کاربران استفاده نمی‌کند و تحلیل‌های مربوط به عملکرد سامانه و محتوا در زیرساخت داخلی پُست‌یار انجام می‌شود.</strong></p>
                </div>
            </section>

            <section id="purposes">
                <h2>۳. اطلاعات برای چه اهدافی استفاده می‌شود؟</h2>
                <p>اطلاعات جمع‌آوری‌شده می‌تواند برای اهداف زیر مورد استفاده قرار گیرد:</p>
                <ul><li>ایجاد و مدیریت حساب کاربری</li><li>احراز هویت و بازیابی حساب</li><li>اتصال کانال‌های تلگرام و بله</li><li>انتشار و زمان‌بندی محتوا</li><li>مدیریت چند کانال</li><li>اجرای پاسخگوی کلمات کلیدی</li><li>مدیریت صندوق پیام</li><li>ارائه ربات نرخ طلا و سکه</li><li>اتصال و همگام‌سازی ووکامرس</li><li>تولید یا پیشنهاد کپشن و محتوای متنی با استفاده از قابلیت‌های هوش مصنوعی</li><li>ارائه گزارش و تحلیل عملکرد کانال‌ها و محتوا</li><li>مدیریت اشتراک، پرداخت، تخفیف و اعتبار</li><li>مدیریت سیستم معرفی کاربران</li><li>ارائه خدمات پشتیبانی</li><li>ارسال پیام‌های ضروری و خدماتی</li><li>رفع خطا و بهبود عملکرد سامانه</li><li>پیشگیری از تقلب، نفوذ و استفاده غیرمجاز</li><li>حفظ امنیت سامانه</li><li>رعایت الزامات قانونی و پاسخگویی به درخواست‌های معتبر مراجع ذی‌صلاح</li></ul>
                <p>پُست‌یار اطلاعات شخصی کاربران را برای فروش یا اجاره به اشخاص ثالث واگذار نمی‌کند.</p>
                <p>همچنین از اطلاعات شخصی کاربران برای ایجاد پروفایل تبلیغاتی یا تبلیغات رفتاری استفاده نمی‌شود.</p>
            </section>

            <section id="cookies">
                <h2>۴. کوکی‌ها و فناوری‌های مشابه</h2>
                <p>وب‌سایت و وب‌اپلیکیشن پُست‌یار ممکن است برای ارائه صحیح خدمات از کوکی‌ها و فناوری‌های مشابه استفاده کنند.</p>
                <p>این فناوری‌ها ممکن است برای موارد زیر استفاده شوند:</p>
                <ul><li>حفظ نشست کاربر</li><li>احراز هویت</li><li>حفظ تنظیمات</li><li>افزایش امنیت</li><li>عملکرد صحیح بخش‌های مختلف سامانه</li></ul>
                <p>برخی کوکی‌ها برای عملکرد صحیح سامانه ضروری هستند و غیرفعال کردن آن‌ها ممکن است باعث اختلال در ورود یا استفاده از برخی قابلیت‌ها شود.</p>
                <p>پُست‌یار در حال حاضر از سرویس‌های تحلیل شخص ثالث مانند Google Analytics برای تحلیل رفتار کاربران استفاده نمی‌کند.</p>
            </section>

            <section id="sharing">
                <h2>۵. انتقال یا اشتراک‌گذاری اطلاعات</h2>
                <p>پُست‌یار اطلاعات کاربران را فقط در حد لازم برای ارائه خدمات، انجام درخواست کاربر، حفظ امنیت یا رعایت الزامات قانونی با سرویس‌های مرتبط تبادل می‌کند.</p>
                <h3>تلگرام و بله</h3><p>در صورت اتصال کانال، اطلاعات و محتوای لازم برای انتشار، دریافت پیام یا اجرای پاسخ‌های خودکار، مطابق تنظیمات کاربر، با سرویس مربوطه تبادل می‌شود.</p>
                <h3>ووکامرس</h3><p>در صورت فعال‌سازی اتصال ووکامرس، اطلاعات فنی و محتوای مورد نیاز برای همگام‌سازی محصولات، قیمت‌ها، تخفیف‌ها و انتشار آن‌ها در کانال‌ها پردازش می‌شود.</p>
                <h3>ارائه‌دهندگان زیرساخت</h3><p>ممکن است برخی اطلاعات برای میزبانی، ذخیره‌سازی، پشتیبان‌گیری، امنیت شبکه و ارائه زیرساخت فنی توسط ارائه‌دهندگان خدمات زیرساختی پردازش شوند.</p>
                <h3>سرویس‌های پیام‌رسانی</h3><p>در صورت فعال بودن قابلیت مربوطه، ممکن است برای ارسال ایمیل، پیامک یا اعلان‌های خدماتی از ارائه‌دهندگان تخصصی این خدمات استفاده شود.</p>
                <h3>سرویس‌های تکمیلی</h3><p>برخی قابلیت‌ها مانند دریافت اطلاعات نرخ طلا یا امکانات هوش مصنوعی ممکن است برای پردازش درخواست کاربر با سرویس‌های مربوطه ارتباط برقرار کنند.</p><p>در صورت استفاده از قابلیت هوش مصنوعی، متن یا محتوایی که کاربر برای پردازش ارسال می‌کند ممکن است برای تولید خروجی مورد درخواست به ارائه‌دهنده فنی مربوطه منتقل شود.</p>
                <h3>مراجع قانونی</h3><p>در صورت وجود الزام قانونی یا دستور معتبر مرجع صالح، ممکن است اطلاعات لازم مطابق قانون در اختیار مرجع مربوطه قرار گیرد.</p>
            </section>

            <section id="publishing">
                <h2>۶. انتشار محتوا در سرویس‌های خارجی</h2>
                <p>هنگامی که کاربر محتوایی را از طریق پُست‌یار در یک کانال تلگرام، بله یا سایر سرویس‌های متصل منتشر می‌کند، آن محتوا مطابق تنظیمات و سیاست‌های همان سرویس در دسترس مخاطبان آن قرار خواهد گرفت.</p>
                <p>حذف اطلاعات از پُست‌یار لزوماً موجب حذف نسخه‌ای از محتوا که قبلاً در تلگرام، بله، ووکامرس یا دستگاه اشخاص دیگر منتشر یا ذخیره شده است نمی‌شود.</p>
                <p>کاربر مسئول محتوایی است که از طریق حساب خود منتشر می‌کند.</p>
            </section>

            <section id="transfer">
                <h2>۷. انتقال داده به خارج از ایران</h2>
                <p>برخی سرویس‌های شخص ثالث مورد استفاده در پُست‌یار ممکن است زیرساخت یا سرورهایی خارج از ایران داشته باشند.</p>
                <p>بنابراین، فعال‌سازی اتصال به برخی سرویس‌های خارجی ممکن است موجب پردازش بخشی از اطلاعات در خارج از ایران یا در حوزه قضایی دیگری شود.</p>
                <p>کاربر با فعال‌سازی سرویس مربوطه، از این موضوع آگاه می‌شود و باید سیاست حریم خصوصی سرویس شخص ثالث مربوطه را نیز بررسی کند.</p>
            </section>

            <section id="ai">
                <h2>۸. استفاده از هوش مصنوعی</h2>
                <p>برخی قابلیت‌های پُست‌یار از فناوری‌های هوش مصنوعی برای تولید یا بهبود محتوای متنی استفاده می‌کنند.</p>
                <p>هنگام استفاده از این قابلیت‌ها، محتوای واردشده توسط کاربر ممکن است برای پردازش درخواست و تولید خروجی به سرویس فنی مربوطه ارسال شود.</p>
                <p>کاربران نباید اطلاعات غیرضروری و محرمانه، از جمله موارد زیر را در ورودی ابزارهای هوش مصنوعی وارد کنند:</p>
                <ul><li>رمز عبور</li><li>اطلاعات کارت بانکی</li><li>توکن‌های دسترسی</li><li>کلیدهای API</li><li>اطلاعات محرمانه اشخاص دیگر</li></ul>
            </section>

            <section id="retention">
                <h2>۹. مدت نگهداری و حذف داده</h2>
                <p>اطلاعات حساب و محتوای مرتبط تا زمانی که حساب فعال است یا برای ارائه خدمات لازم باشد، ممکن است نگهداری شوند.</p>
                <p>پس از پایان نیاز یا تأیید درخواست حذف، داده‌هایی که نگهداری آن‌ها ضرورت قانونی یا عملیاتی ندارد، حذف یا در صورت امکان ناشناس‌سازی خواهند شد.</p>
                <p>با این حال، ممکن است برخی اطلاعات برای موارد زیر برای مدت لازم نگهداری شوند:</p>
                <ul><li>سوابق مالی</li><li>حل اختلاف</li><li>امنیت سامانه</li><li>جلوگیری از تقلب</li><li>پشتیبان‌گیری دوره‌ای</li><li>الزامات قانونی</li></ul>
                <p>لاگ‌های فنی و داده‌های موقت نیز متناسب با هدف امنیتی یا عملیاتی خود نگهداری خواهند شد.</p>
            </section>

            <section id="deletion">
                <h2>۱۰. درخواست حذف حساب و اطلاعات</h2>
                <p>کاربر می‌تواند درخواست حذف حساب و اطلاعات قابل حذف مرتبط با آن را از طریق ایمیل <a href="mailto:contact@asovin.ir"><strong>contact@asovin.ir</strong></a> ارسال کند.</p>
                <p>همچنین در صورت فراهم بودن قابلیت مربوطه، می‌توان درخواست را از طریق پنل کاربری یا تیکت پشتیبانی ثبت کرد.</p>
                <p>برای جلوگیری از حذف غیرمجاز اطلاعات، ممکن است احراز مالکیت حساب پیش از اجرای درخواست ضروری باشد.</p>
                <p>پس از بررسی درخواست، حساب و اطلاعات قابل حذف مرتبط با آن مطابق این سیاست حذف یا ناشناس‌سازی خواهند شد.</p>
                <p>حذف حساب می‌تواند موجب موارد زیر شود:</p>
                <ul><li>قطع دسترسی به حساب</li><li>لغو دسترسی‌های فعال</li><li>حذف محتوای ذخیره‌شده</li><li>حذف یا غیرفعال شدن اتصال‌های مرتبط</li><li>از دست رفتن گزارش‌ها و سوابق قابل حذف</li></ul>
                <p>اطلاعات موجود در نسخه‌های پشتیبان نیز مطابق چرخه معمول پشتیبان‌گیری و جایگزینی نسخه‌های قدیمی، از دسترس عملیاتی خارج خواهند شد.</p>
            </section>

            <section id="security">
                <h2>۱۱. امنیت اطلاعات</h2>
                <p>پُست‌یار برای کاهش خطر دسترسی غیرمجاز، تغییر، افشا یا از بین رفتن اطلاعات، از کنترل‌های فنی و مدیریتی متناسب با ماهیت خدمات استفاده می‌کند.</p>
                <p>از جمله این اقدامات می‌توان به موارد زیر اشاره کرد:</p>
                <ul><li>استفاده از ارتباط HTTPS</li><li>هش‌کردن رمزهای عبور</li><li>استفاده از توکن‌های دسترسی</li><li>کنترل سطح دسترسی</li><li>محدودسازی تلاش‌های غیرعادی ورود</li><li>اعتبارسنجی فایل‌های بارگذاری‌شده</li><li>ثبت رخدادهای مرتبط با امنیت</li><li>کنترل دسترسی به اطلاعات</li></ul>
                <p>با وجود این اقدامات، هیچ روش انتقال یا ذخیره‌سازی اطلاعات در اینترنت نمی‌تواند امنیت مطلق را تضمین کند.</p>
                <p>کاربر نیز باید رمز عبور خود را محرمانه نگه دارد، از رمز عبور منحصربه‌فرد استفاده کند و در صورت مشاهده فعالیت مشکوک، موضوع را فوراً به پُست‌یار اطلاع دهد.</p>
            </section>

            <section id="rights">
                <h2>۱۲. حقوق و انتخاب‌های کاربران</h2>
                <p>با توجه به امکانات سامانه و الزامات قانونی، کاربران می‌توانند:</p>
                <ul><li>اطلاعات پروفایل خود را مشاهده و اصلاح کنند؛</li><li>درباره اطلاعات مرتبط با حساب خود سؤال کنند؛</li><li>در صورت امکان، نسخه‌ای از اطلاعات مرتبط با حساب خود درخواست کنند؛</li><li>حذف حساب یا اطلاعات قابل حذف را درخواست کنند؛</li><li>اتصال کانال‌ها و سرویس‌های اختیاری را قطع کنند؛</li><li>اعلان‌های اختیاری را غیرفعال کنند؛</li><li>درباره نحوه پردازش اطلاعات خود درخواست توضیح کنند.</li></ul>
                <p>برای رسیدگی به درخواست‌های مربوط به اطلاعات شخصی، ممکن است اطلاعات لازم برای شناسایی حساب و احراز هویت دریافت شود.</p>
                <p>درخواست‌ها در محدوده امکانات فنی سامانه و الزامات قانونی قابل اعمال بررسی و اجرا خواهند شد.</p>
            </section>

            <section id="third-parties">
                <h2>۱۳. اطلاعات اشخاص ثالث</h2>
                <p>ممکن است کاربر در هنگام استفاده از پُست‌یار، اطلاعات اشخاص دیگری مانند مشتریان، مخاطبان یا کاربران کانال خود را در سامانه وارد یا از طریق سرویس‌های متصل پردازش کند.</p>
                <p>در چنین مواردی، مسئولیت قانونی جمع‌آوری، استفاده و پردازش این اطلاعات بر عهده کاربر است.</p>
                <p>کاربر باید اطمینان حاصل کند که برای جمع‌آوری، ذخیره، پردازش یا انتشار اطلاعات اشخاص دیگر، مجوز و مبنای قانونی لازم را در اختیار دارد.</p>
            </section>

            <section id="children">
                <h2>۱۴. حریم خصوصی کودکان</h2>
                <p>پُست‌یار یک ابزار مدیریت کانال و کسب‌وکار است و به‌طور خاص برای کودکان طراحی نشده است.</p>
                <p>اشخاصی که طبق قوانین محل اقامت خود اهلیت لازم برای پذیرش شرایط استفاده و مدیریت حساب یا کسب‌وکار را ندارند، باید با نظارت و رضایت ولی یا سرپرست قانونی از خدمات استفاده کنند.</p>
                <p>در صورت اطلاع از ثبت اطلاعات شخصی یک کودک بدون مجوز لازم، می‌توانید موضوع را از طریق ایمیل <a href="mailto:contact@asovin.ir"><strong>contact@asovin.ir</strong></a> به ما اطلاع دهید.</p>
            </section>

            <section id="changes">
                <h2>۱۵. تغییرات سیاست حریم خصوصی</h2>
                <p>ممکن است این سیاست در نتیجه تغییر قابلیت‌های پُست‌یار، اضافه شدن سرویس‌های جدید، تغییر روش‌های پردازش اطلاعات یا تغییر الزامات قانونی به‌روزرسانی شود.</p>
                <p>نسخه جاری سیاست حفظ حریم خصوصی همواره در همین صفحه منتشر خواهد شد و تاریخ آخرین به‌روزرسانی در ابتدای صفحه درج می‌شود.</p>
                <p>در صورت ایجاد تغییرات مهم، در صورت امکان از طریق وب‌سایت، پنل کاربری یا اطلاعات تماس ثبت‌شده، اطلاع‌رسانی مناسب انجام خواهد شد.</p>
            </section>

            <section id="contact" class="contact-card">
                <h2>۱۶. پرسش یا درخواست درباره حریم خصوصی</h2>
                <p>اگر درباره این سیاست، اطلاعات حساب، نحوه پردازش داده‌ها، امنیت اطلاعات یا حذف حساب سؤال یا درخواستی دارید، می‌توانید با ما در ارتباط باشید.</p>
                <p><strong>ایمیل پشتیبانی و حریم خصوصی:</strong><br><a href="mailto:contact@asovin.ir"><strong>contact@asovin.ir</strong></a></p>
                <p>لطفاً برای حفظ امنیت حساب، رمز عبور، توکن ربات، کلید API یا سایر اطلاعات محرمانه را در ایمیل ارسال نکنید.</p>
                <p><strong>وب‌سایت:</strong><br><a href="https://asovin.ir/">https://asovin.ir/</a></p>
            </section>

            <section id="acceptance">
                <h2>۱۷. پذیرش سیاست حریم خصوصی</h2>
                <p>با ثبت‌نام، ورود یا استفاده از خدمات پُست‌یار، کاربر اعلام می‌کند که این سیاست حفظ حریم خصوصی را مطالعه کرده و از نحوه جمع‌آوری، استفاده و پردازش اطلاعات مطابق مفاد آن آگاه است.</p>
                <p>در صورتی که با مفاد این سیاست موافق نیستید، لطفاً از ایجاد حساب یا استفاده از خدمات پُست‌یار خودداری کنید.</p>
                <p>پُست‌یار متعهد است اطلاعات کاربران را در چارچوب قوانین و مقررات قابل اعمال و با رعایت اصول محرمانگی و امنیت پردازش کند.</p>
            </section>

            <section class="signature">
                <img src="<?php echo $assetsUrl; ?>/images/logo-full.webp" alt="پُست‌یار">
                <strong>پُست‌یار</strong>
                <p>سامانه هوشمند مدیریت و انتشار کانال‌ها</p>
                <p><strong>وب‌سایت:</strong> <a href="https://asovin.ir/">asovin.ir</a><br><strong>ایمیل:</strong> <a href="mailto:contact@asovin.ir">contact@asovin.ir</a></p>
                <p>تمامی حقوق مادی و معنوی محفوظ است.</p>
            </section>
        </article>
    </div>
</main>

<footer><div class="container">تمامی حقوق برای پُست‌یار محفوظ است — <a href="<?php echo htmlspecialchars($siteUrl); ?>/">صفحه اصلی</a></div></footer>
</body>
</html>
