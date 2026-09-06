<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title><?php echo htmlspecialchars($title); ?> | پنل مدیریت ارشد پُست‌یار</title>

    <?php $baseUrl = rtrim(str_replace(['/assets', '/public/assets'], '', \WHCM\Core\Bootstrap::getAssetsUrl()), '/'); ?>

    <!-- PWA Meta Tags -->
    <link rel="manifest" href="<?php echo $baseUrl; ?>/manifest.json">
    <meta name="theme-color" content="#6366f1">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="application-name" content="پُست‌یار">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo $baseUrl; ?>/assets/icons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo $baseUrl; ?>/assets/icons/favicon-16x16.png">
    <!-- iOS PWA Support -->
    <link rel="apple-touch-icon" href="<?php echo $baseUrl; ?>/assets/icons/apple-touch-icon.png">
    <link rel="apple-touch-icon" sizes="152x152" href="<?php echo $baseUrl; ?>/assets/icons/icon-152x152.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="پُست‌یار">
    <meta name="format-detection" content="telephone=no">
    <link rel="stylesheet" href="<?php echo \WHCM\Core\Bootstrap::getAssetsUrl(); ?>/css/admin.css">
    <link rel="stylesheet" href="<?php echo \WHCM\Core\Bootstrap::getAssetsUrl(); ?>/css/components.css">
    <link rel="stylesheet" href="<?php echo \WHCM\Core\Bootstrap::getAssetsUrl(); ?>/css/jalalidatepicker.min.css">
</head>
<body>

    <!-- کشوی منوی مدیریت در موبایل (Drawer) -->
    <div class="drawer-overlay" id="drawer-overlay"></div>
    <div class="drawer-menu" id="drawer-menu">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; border-bottom:1px solid var(--border); padding-bottom:0.75rem;">
            <span style="font-weight:bold; color:var(--primary); font-size:1rem;"><?php echo $is_support ? 'پنل پشتیبانی' : 'منوی مدیریت پُست‌یار'; ?></span>
            <button class="close-btn" style="position:static;">✖</button>
        </div>
        <?php if (!$is_support): ?>
        <div class="menu-item active" data-target="dashboard" data-toggle-drawer="true" onclick="switchSection('dashboard')">📊 وضعیت کلی و آمارگیری حرفه‌ای</div>
        <div class="menu-item" data-target="users" data-toggle-drawer="true" onclick="switchSection('users')">👥 مدیریت کاربران و هدیه اشتراک</div>
        <div class="menu-item" data-target="payments" data-toggle-drawer="true" onclick="switchSection('payments')">💳 تایید فیش‌های واریزی</div>
        <div class="menu-item" data-target="subscriptions" data-toggle-drawer="true" onclick="switchSection('subscriptions')">🎫 لیست اشتراک‌های فعال</div>
        <div class="menu-item" data-target="plans" data-toggle-drawer="true" onclick="switchSection('plans')">💎 مدیریت پلن‌های اشتراکی</div>
        <div class="menu-item" data-target="admin-gold" data-toggle-drawer="true" onclick="switchSection('admin-gold')">🪙 تنظیمات ربات طلا و سکه</div>
        <div class="menu-item" data-target="admin-ai" data-toggle-drawer="true" onclick="switchSection('admin-ai')">🧠 تنظیمات سراسری هوش مصنوعی</div>
        <div class="menu-item" data-target="discounts" data-toggle-drawer="true" onclick="switchSection('discounts')">🎁 کدهای تخفیف</div>
        <div class="menu-item" data-target="admin-responder" data-toggle-drawer="true" onclick="switchSection('admin-responder')">🤖 تنظیمات پاسخگوی هوشمند</div>
        <div class="menu-item" data-target="admin-woo" data-toggle-drawer="true" onclick="switchSection('admin-woo')">🛍 تنظیمات اتصال ووکامرس</div>
        <div class="menu-item" data-target="broadcast" data-toggle-drawer="true" onclick="switchSection('broadcast')">📢 ارسال اعلان همگانی</div>
        <div class="menu-item" data-target="bank" data-toggle-drawer="true" onclick="switchSection('bank')">💳 تنظیمات کارت بانکی</div>
        <div class="menu-item" data-target="referral-settings" data-toggle-drawer="true" onclick="switchSection('referral-settings')">🎯 تنظیمات زیرمجموعه‌گیری</div>
        <div class="menu-item" data-target="sms-settings" data-toggle-drawer="true" onclick="switchSection('sms-settings')">📱 تنظیمات پیامک</div>
        <div class="menu-item" data-target="email-settings" data-toggle-drawer="true" onclick="switchSection('email-settings')">📧 تنظیمات ایمیل</div>
        <?php endif; ?>
        <div class="menu-item <?php echo $is_support ? 'active' : ''; ?>" data-target="tickets" data-toggle-drawer="true" onclick="switchSection('tickets')">🎫 تیکت‌های پشتیبانی</div>
        <a href="<?php echo \WHCM\Core\Bootstrap::getRouteUrl('/logout'); ?>" class="menu-item logout-btn" style="margin-top:0.5rem; padding-top:0;">🚪 خروج از حساب</a>
    </div>

    <!-- هدر بالای صفحه مدیریت ارشد پُست‌یار -->
    <header>
        <div class="logo-container">
            <img src="<?php echo \WHCM\Core\Bootstrap::getAssetsUrl(); ?>/images/logo-white-bg.webp" alt="پُست‌یار" class="logo-img">
            <span class="logo-text" style="background: linear-gradient(135deg, #ffffff 0%, #fbbf24 50%, #f59e0b 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">پُست‌یار ارشد</span>
        </div>
        <div style="display:flex; align-items:center; gap:1rem;">
            <?php 
                $pending_p_count = 0;
                foreach ($payments as $pay) { if($pay['status'] === 'pending') $pending_p_count++; }
                $open_t_count = 0;
                foreach ($tickets as $tick) { if($tick['status'] === 'open') $open_t_count++; }
                $total_notifs = $pending_p_count + $open_t_count;
            ?>
            <!-- دکمه زنگوله اعلان بالای صفحه — دقیقاً مشابه داشبورد کاربر -->
            <div style="position:relative;">
                <button type="button" onclick="var p=document.getElementById('admin-bell-popup'); p.style.display=(p.style.display==='flex'?'none':'flex');" style="background:rgba(15,23,42,0.85); border:1px solid rgba(99,102,241,0.4); border-radius:50%; width:40px; height:40px; display:flex; align-items:center; justify-content:center; color:white; font-size:1.15rem; cursor:pointer; box-shadow:0 4px 12px rgba(0,0,0,0.4);">
                    <span>🔔</span>
                    <?php if ($total_notifs > 0): ?>
                        <span style="position:absolute; top:2px; right:2px; width:10px; height:10px; background:#ef4444; border-radius:50%; border:2px solid #0f172a;"></span>
                    <?php endif; ?>
                </button>
                <div id="admin-bell-popup" style="display:none; position:absolute; left:0; top:60px; width:290px; background:#0f172a; border:1px solid #4f46e5; border-radius:16px; box-shadow:0 15px 35px rgba(0,0,0,0.85); z-index:9999; flex-direction:column; padding:1rem;">
                    <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px dashed #334155; padding-bottom:0.6rem; margin-bottom:0.75rem;">
                        <strong style="color:white; font-size:0.9rem;">🔔 اعلان‌های سیستمی مدیر</strong>
                        <span style="font-size:0.75rem; color:#818cf8;"><?php echo \WHCM\Domain\TextFormat::fa_digits($total_notifs); ?> مورد</span>
                    </div>
                    <?php if ($total_notifs === 0): ?>
                        <div style="color:#94a3b8; font-size:0.8rem; text-align:center; padding:0.5rem 0;">همه موارد بررسی شده است ✔</div>
                    <?php else: ?>
                        <?php if ($pending_p_count > 0): ?>
                            <div style="padding:0.5rem; background:#1e293b; border-radius:8px; margin-bottom:0.5rem; font-size:0.8rem; color:#cbd5e1; cursor:pointer;" onclick="switchSection('payments'); document.getElementById('admin-bell-popup').style.display='none';">
                                💳 <?php echo \WHCM\Domain\TextFormat::fa_digits($pending_p_count); ?> فیش واریزی در انتظار تأیید
                            </div>
                        <?php endif; ?>
                        <?php if ($open_t_count > 0): ?>
                            <div style="padding:0.5rem; background:#1e293b; border-radius:8px; font-size:0.8rem; color:#cbd5e1; cursor:pointer;" onclick="switchSection('tickets'); document.getElementById('admin-bell-popup').style.display='none';">
                                🎫 <?php echo \WHCM\Domain\TextFormat::fa_digits($open_t_count); ?> تیکت پشتیبانی باز
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="admin-badge"><?php echo $is_support ? 'پشتیبان پُست‌یار 🎧' : 'مدیر ارشد پلتفرم 👑'; ?></div>
            <!-- دکمه بازکردن همبرگری کشویی موبایل -->
            <button class="hamburger-btn">☰</button>
        </div>
    </header>

    <!-- کانتینر اصلی محتوا -->
    <div class="wrapper">
        
        <!-- سایدبار دسکتاپی -->
        <aside class="sidebar-desktop">
            <div class="menu-item active" data-target="dashboard">📊 وضعیت کلی و آمارگیری حرفه‌ای</div>
            <div class="menu-item" data-target="users">👥 مدیریت کاربران و هدیه اشتراک</div>
            <div class="menu-item" data-target="payments">💳 تایید فیش‌های واریزی</div>
            <div class="menu-item" data-target="subscriptions">🎫 لیست اشتراک‌های فعال</div>
            <div class="menu-item" data-target="plans">💎 مدیریت پلن‌های اشتراکی</div>
            <div class="menu-item" data-target="admin-gold">🪙 تنظیمات ربات طلا و سکه</div>
            <div class="menu-item" data-target="admin-ai">🧠 تنظیمات سراسری هوش مصنوعی</div>
            <div class="menu-item" data-target="discounts">🎁 کدهای تخفیف</div>
            <div class="menu-item" data-target="admin-responder">🤖 تنظیمات پاسخگوی هوشمند</div>
            <div class="menu-item" data-target="admin-woo">🛍 تنظیمات اتصال ووکامرس</div>
            <div class="menu-item" data-target="broadcast">📢 ارسال اعلان همگانی</div>
            <div class="menu-item" data-target="bank">💳 تنظیمات کارت بانکی</div>
            <div class="menu-item" data-target="tickets">🎫 تیکت‌های پشتیبانی</div>
            <div class="menu-item" data-target="referral-settings">🎯 تنظیمات زیرمجموعه‌گیری</div>
            <div class="menu-item" data-target="sms-settings">📱 تنظیمات پیامک</div>
            <div class="menu-item" data-target="email-settings">📧 تنظیمات ایمیل</div>
            <a href="<?php echo \WHCM\Core\Bootstrap::getRouteUrl('/dashboard'); ?>" class="menu-item" style="color:var(--primary); border-top:1px solid var(--border); padding-top:1rem; border-radius:0; margin-top:1.5rem;">🏠 رفتن به پیشخوان کاربری</a>
            <a href="<?php echo \WHCM\Core\Bootstrap::getRouteUrl('/logout'); ?>" class="menu-item logout-btn" style="margin-top:0.5rem; padding-top:0;">🚪 خروج از حساب</a>
        </aside>

        <!-- محتوای اصلی -->
        <main>
            
            <!-- نمایش سریع گزارش وضعیت سیستم (اعلان بالا صفحه) -->
            <?php 
                $pending_p_count = 0;
                foreach ($payments as $pay) { if($pay['status'] === 'pending') $pending_p_count++; }
                $open_t_count = 0;
                foreach ($tickets as $tick) { if($tick['status'] === 'open') $open_t_count++; }
            ?>
            <div class="admin-announcement-bar">
                <span>📢</span>
                <span>گزارش سریع وضعیت سیستم: تعداد <?php echo \WHCM\Domain\TextFormat::fa_digits($pending_p_count); ?> فیش پرداخت در انتظار تأیید و <?php echo \WHCM\Domain\TextFormat::fa_digits($open_t_count); ?> تیکت پشتیبانی پاسخ‌نداده فعال وجود دارد.</span>
            </div>

            <?php if (!empty($message)): ?>
                <div class="alert" id="system-alert-toast" style="position:relative; display:flex; justify-content:space-between; align-items:center;">
                    <span><?php echo htmlspecialchars($message); ?></span>
                    <button type="button" onclick="document.getElementById('system-alert-toast').style.display='none'" style="background:none; border:none; color:white; font-size:1.1rem; cursor:pointer; margin-right:1rem;">✖</button>
                </div>
                <script>autoDismissAlert('system-alert-toast', 5000);</script>
            <?php endif; ?>

            <!-- ========================================== -->
            <!-- ۱. بخش وضعیت کلی سیستم -->
            <!-- ========================================== -->
            <div id="section-dashboard" class="tab-content active">
                <div class="grid-stats">
                    <a href="javascript:switchSection('users')" style="text-decoration:none;">
                        <div class="card-stat" style="cursor:pointer;">
                            <div class="card-stat-icon" style="background:linear-gradient(135deg, rgba(99,102,241,0.2) 0%, rgba(99,102,241,0.05) 100%); color:#818cf8;">👥</div>
                            <div class="card-stat-info">
                                <span class="title">کل کاربران ثبت‌نام شده</span>
                                <span class="value"><?php echo \WHCM\Domain\TextFormat::fa_digits($total_users); ?> نفر</span>
                            </div>
                        </div>
                    </a>
                    <a href="javascript:switchSection('users')" style="text-decoration:none;">
                        <div class="card-stat" style="cursor:pointer;">
                            <div class="card-stat-icon" style="background:linear-gradient(135deg, rgba(16,185,129,0.2) 0%, rgba(16,185,129,0.05) 100%); color:#10b981;">✅</div>
                            <div class="card-stat-info">
                                <span class="title">کاربران فعال</span>
                                <span class="value"><?php echo \WHCM\Domain\TextFormat::fa_digits($active_users_count); ?> نفر</span>
                            </div>
                        </div>
                    </a>
                    <a href="javascript:switchSection('payments')" style="text-decoration:none;">
                        <div class="card-stat" style="cursor:pointer;">
                            <div class="card-stat-icon" style="background:linear-gradient(135deg, rgba(245,158,11,0.2) 0%, rgba(245,158,11,0.05) 100%); color:#f59e0b;">💳</div>
                            <div class="card-stat-info">
                                <span class="title">پرداخت‌های در انتظار تایید</span>
                                <span class="value"><?php echo \WHCM\Domain\TextFormat::fa_digits($pending_p_count); ?> فیش</span>
                            </div>
                        </div>
                    </a>
                    <a href="javascript:switchSection('tickets')" style="text-decoration:none;">
                        <div class="card-stat" style="cursor:pointer;">
                            <div class="card-stat-icon" style="background:linear-gradient(135deg, rgba(239,68,68,0.2) 0%, rgba(239,68,68,0.05) 100%); color:#ef4444;">🎟</div>
                            <div class="card-stat-info">
                                <span class="title">تیکت‌های باز منتظر پاسخ</span>
                                <span class="value"><?php echo \WHCM\Domain\TextFormat::fa_digits($open_t_count); ?> تیکت</span>
                            </div>
                        </div>
                    </a>
                    <a href="javascript:switchSection('plans')" style="text-decoration:none;">
                        <div class="card-stat" style="cursor:pointer;">
                            <div class="card-stat-icon" style="background:linear-gradient(135deg, rgba(168,85,247,0.2) 0%, rgba(168,85,247,0.05) 100%); color:#a855f7;">💎</div>
                            <div class="card-stat-info">
                                <span class="title">پلن‌های اشتراک</span>
                                <span class="value"><?php echo \WHCM\Domain\TextFormat::fa_digits(count($plans)); ?> پلن</span>
                            </div>
                        </div>
                    </a>
                    <a href="javascript:switchSection('subscriptions')" style="text-decoration:none;">
                        <div class="card-stat" style="cursor:pointer;">
                            <div class="card-stat-icon" style="background:linear-gradient(135deg, rgba(34,211,238,0.2) 0%, rgba(34,211,238,0.05) 100%); color:#22d3ee;">🎯</div>
                            <div class="card-stat-info">
                                <span class="title">اشتراک‌های فعال</span>
                                <span class="value"><?php echo \WHCM\Domain\TextFormat::fa_digits($active_subs_count); ?> اشتراک</span>
                            </div>
                        </div>
                    </a>
                    <a href="javascript:switchSection('users')" style="text-decoration:none;">
                        <div class="card-stat" style="cursor:pointer;">
                            <div class="card-stat-icon" style="background:linear-gradient(135deg, rgba(56,189,248,0.2) 0%, rgba(56,189,248,0.05) 100%); color:#38bdf8;">📱</div>
                            <div class="card-stat-info">
                                <span class="title">کل کانال‌های ثبت شده</span>
                                <span class="value"><?php echo \WHCM\Domain\TextFormat::fa_digits($total_channels); ?> کانال</span>
                            </div>
                        </div>
                    </a>
                    <a href="javascript:switchSection('payments')" style="text-decoration:none;">
                        <div class="card-stat" style="cursor:pointer;">
                            <div class="card-stat-icon" style="background:linear-gradient(135deg, rgba(251,191,36,0.2) 0%, rgba(251,191,36,0.05) 100%); color:#fbbf24;">💰</div>
                            <div class="card-stat-info">
                                <span class="title">کل درآمد تایید شده</span>
                                <span class="value"><?php echo \WHCM\Domain\TextFormat::fa_num($total_revenue); ?> تومان</span>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- ۲. بخش مدیریت کاربران -->
            <!-- ========================================== -->
            <div id="section-users" class="tab-content">
                <div class="card">
                    <h2>👥 لیست کامل کاربران فعال سیستم</h2>
                    <?php if (empty($users)): ?>
                        <p style="color: var(--text-muted); text-align: center; padding: 2rem 0;">کاربری یافت نشد.</p>
                    <?php else: ?>
                        <div style="margin-bottom:0.75rem; display:flex; gap:0.5rem;"><input type="text" id="admin-user-search" placeholder="جستجوی نام یا ایمیل یا کسب‌وکار..." oninput="filterAdminUsers(this.value)" style="flex:1; padding:0.6rem 0.85rem; border-radius:10px; border:1px solid #334155; background:#0f172a; color:white;"></div>
                        <div class="table-responsive" style="max-height:520px; overflow:auto; border:1px solid #1e293b; border-radius:12px;">
                            <table>
                                <thead>
                                    <tr>
                                        <th>نام کاربر</th>
                                        <th>مشخصات کسب و کار</th>
                                        <th>اشتراک فعلی و اعتبار</th>
                                        <th>تاریخ عضویت</th>
                                        <th>کانال‌ها</th>
                                        <th>وضعیت حساب</th>
                                        <th>اقدامات مدیریتی</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $u): ?>
                                        <tr>
                                            <td data-label="نام کاربر">
                                                <strong><?php echo htmlspecialchars($u['name']); ?></strong><br>
                                                <span style="font-size:0.75rem; color:var(--text-muted);"><?php echo htmlspecialchars($u['email']); ?></span>
                                            </td>
                                            <td data-label="مشخصات کسب و کار">
                                                <?php if (!empty($u['business_name'])): ?>
                                                    <strong><?php echo htmlspecialchars($u['business_name']); ?></strong><br>
                                                    <span style="font-size:0.75rem; color:var(--text-muted);"><?php echo htmlspecialchars($u['business_type']); ?></span>
                                                <?php else: ?>
                                                    <span style="font-size:0.8rem; color:var(--text-muted);">ثبت نشده</span>
                                                <?php endif; ?>
                                            </td>
                                            <td data-label="اشتراک فعلی و اعتبار">
                                                <?php if (!empty($u['plan_title'])): ?>
                                                    <span class="badge badge-success" style="font-size:0.75rem; margin-bottom:0.25rem; display:inline-block;">💎 <?php echo htmlspecialchars($u['plan_title']); ?></span><br>
                                                    <span style="font-size:0.7rem; color: #a5b4fc;">اعتبار تا: <?php echo \WHCM\Domain\TextFormat::mysql_to_jalali($u['end_date'], false); ?></span>
                                                <?php else: ?>
                                                    <span class="badge" style="background:rgba(255,255,255,0.08); color:#cbd5e1; font-size:0.75rem;">رایگان / بدون اشتراک</span>
                                                <?php endif; ?>
                                            </td>
                                            <td data-label="تاریخ عضویت">
                                                <span style="font-size:0.8rem; color:#cbd5e1;"><?php echo \WHCM\Domain\TextFormat::mysql_to_jalali($u['created_at']); ?></span>
                                            </td>
                                            <td data-label="کانال‌ها"><strong><?php echo \WHCM\Domain\TextFormat::fa_digits($u['channel_count']); ?> کانال</strong></td>
                                            <td data-label="وضعیت حساب">
                                                <span style="color: <?php echo $u['status'] === 'active' ? '#10b981' : '#ef4444'; ?>; font-weight: bold;">
                                                    <?php echo $u['status'] === 'active' ? 'فعال' : 'مسدود'; ?>
                                                </span>
                                            </td>
                                            <td data-label="اقدامات مدیریتی">
                                                <div style="display:flex; gap:0.25rem; flex-wrap:wrap;">
                                                    <button type="button" class="btn btn-sm" style="padding:0.35rem 0.6rem; background:linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); color:white; border:none; font-weight:bold;" onclick='openUserProfileModal(<?php echo json_encode($u, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP|JSON_UNESCAPED_UNICODE); ?>)'>👁 پروفایل ۳۶۰ درجه</button>
                                                    <button type="button" class="btn btn-success btn-sm" style="padding:0.35rem 0.6rem; background:#10b981; color:white; border:none;" onclick="openGiftModal(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars(addslashes($u['name'])); ?>')">🎁 هدیه اشتراک</button>
                                                    <?php if ($u['status'] === 'active'): ?>
                                                        <a href="<?php echo \WHCM\Core\Bootstrap::getRouteUrl('/hnnh/suspend-user'); ?>&id=<?php echo $u['id']; ?>" class="btn btn-danger btn-sm" style="background:#f59e0b; padding:0.35rem 0.6rem;">تعلیق 🚫</a>
                                                    <?php else: ?>
                                                        <a href="<?php echo \WHCM\Core\Bootstrap::getRouteUrl('/hnnh/activate-user'); ?>&id=<?php echo $u['id']; ?>" class="btn btn-success btn-sm" style="padding:0.35rem 0.6rem;">فعال ✔</a>
                                                    <?php endif; ?>
                                                    <a href="<?php echo \WHCM\Core\Bootstrap::getRouteUrl('/hnnh/delete-user'); ?>&id=<?php echo $u['id']; ?>" class="btn btn-danger btn-sm" style="padding:0.35rem 0.6rem;" onclick="return confirm('آیا از حذف کامل این مستأجر اطمینان دارید؟ تمامی داده‌های او پاک خواهد شد.');">حذف 🗑</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- بخش عملیات پیشرفته دستی کاربران -->
                <div class="grid-content" style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 2rem;">
                    
                    <!-- کارت افزودن دستی کاربر -->
                    <div class="card">
                        <h2>👤 افزودن کاربر جدید به صورت دستی</h2>
                        <form action="<?php echo \WHCM\Core\Bootstrap::getRouteUrl('/hnnh/add-user-manual'); ?>" method="POST">
                            <?php echo $csrf_field; ?>
                            <div class="form-group">
                                <label for="man-name">نام و نام خانوادگی:</label>
                                <input type="text" name="name" id="man-name" required placeholder="مثال: رضا محمدی">
                            </div>
                            <div class="form-group">
                                <label for="man-email">نشانی ایمیل:</label>
                                <input type="email" name="email" id="man-email" required placeholder="name@example.com">
                            </div>
                            <div class="form-group">
                                <label for="man-pass">رمز عبور اختصاصی:</label>
                                <input type="password" name="password" id="man-pass" required placeholder="حداقل ۸ کاراکتر">
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="man-biz">نام کسب و کار:</label>
                                    <input type="text" name="business_name" id="man-biz" placeholder="مثال: گالری آسوین">
                                </div>
                                <div class="form-group">
                                    <label for="man-role">نقش کاربر:</label>
                                    <select name="role" id="man-role" style="border-radius:12px;">
                                        <option value="user">کاربر عادی (مستأجر)</option>
                                        <option value="support_agent">پشتیبان (فقط تیکت‌ها)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="man-biz-type">نوع کسب و کار:</label>
                                <input type="text" name="business_type" id="man-biz-type" placeholder="مثال: طلا و جواهرات">
                            </div>
                            <button type="submit" class="btn" style="width:100%;">ثبت و ایجاد دستی کاربر 👤</button>
                        </form>
                    </div>

                    <!-- کارت اعطای دستی اشتراک -->
                    <div class="card">
                        <h2>🎫 اعطای مستقیم و دستی اشتراک به کاربر</h2>
                        <form action="<?php echo \WHCM\Core\Bootstrap::getRouteUrl('/hnnh/grant-subscription-manual'); ?>" method="POST">
                            <?php echo $csrf_field; ?>
                            <div class="form-group">
                                <label for="man-user">انتخاب مستأجر (کاربر):</label>
                                <select name="user_id" id="man-user" required style="border-radius:12px;">
                                    <option value="">-- یک کاربر را انتخاب کنید --</option>
                                    <?php foreach ($users as $u): ?>
                                        <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['name']); ?> (<?php echo htmlspecialchars($u['email']); ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="man-plan">انتخاب پلن اشتراک هدف:</label>
                                <select name="plan_id" id="man-plan" required style="border-radius:12px;">
                                    <option value="">-- پلن مورد نظر را انتخاب کنید --</option>
                                    <?php foreach ($plans as $p): ?>
                                        <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['title']); ?> (قیمت: <?php echo \WHCM\Domain\TextFormat::fa_num($p['price']); ?> تومان)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-success" style="width:100%; margin-top:2.5rem; background: linear-gradient(135deg, #10b981 0%, #059669 100%); border:none;">فعال‌سازی آنی اشتراک برای کاربر 🎫</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- ۳. بخش تایید فیش‌های واریزی -->
            <!-- ========================================== -->
            <div id="section-payments" class="tab-content">
                <div class="card">
                    <h2>درخواست‌های فعال تایید واریز کارت به کارت (بلو بانک) 💳</h2>
                    <?php if (empty($payments)): ?>
                        <p style="color: var(--text-muted); text-align: center; padding: 2rem 0;">هیچ فیش بانکی منتظر تاییدی یافت نشد.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>کاربر فرستنده</th>
                                        <th>پلن درخواستی</th>
                                        <th>مبلغ واریزی</th>
                                        <th>کد رهگیری تراکنش</th>
                                        <th>تصویر رسید</th>
                                        <th>وضعیت مالی</th>
                                        <th>تأیید</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payments as $p): ?>
                                        <tr>
                                            <td data-label="کاربر فرستنده">
                                                <strong><?php echo htmlspecialchars($p['user_name']); ?></strong><br>
                                                <span style="font-size: 0.8rem; color: var(--text-muted);"><?php echo htmlspecialchars($p['user_email']); ?></span>
                                            </td>
                                            <td data-label="پلن درخواستی"><strong><?php echo htmlspecialchars($p['plan_title']); ?></strong></td>
                                            <td data-label="مبلغ واریزی"><strong style="color:#a5b4fc;"><?php echo \WHCM\Domain\TextFormat::fa_num($p['amount']); ?> تومان</strong></td>
                                            <td data-label="کد رهگیری تراکنش"><code><?php echo \WHCM\Domain\TextFormat::fa_digits($p['reference_num']); ?></code></td>
                                            <td data-label="تصویر رسید">
                                                <?php if (!empty($p['receipt_photo'])): ?>
                                                    <a href="<?php echo htmlspecialchars($p['receipt_photo']); ?>" target="_blank" class="btn btn-sm" style="background:#8b5cf6; padding:0.35rem 0.6rem; font-size:0.75rem;">🔎 مشاهده فیش</a>
                                                <?php else: ?>
                                                    <span style="font-size:0.8rem; color:var(--text-muted);">فاقد تصویر</span>
                                                <?php endif; ?>
                                            </td>
                                            <td data-label="وضعیت مالی">
                                                <span class="badge badge-<?php echo $p['status']; ?>">
                                                    <?php echo $p['status'] === 'pending' ? 'در انتظار تایید ⏳' : 'تایید شده ✔'; ?>
                                                </span>
                                            </td>
                                            <td data-label="تأیید">
                                                <?php if ($p['status'] === 'pending'): ?>
                                                    <a href="<?php echo \WHCM\Core\Bootstrap::getRouteUrl('/hnnh/approve-payment'); ?>&id=<?php echo $p['id']; ?>" class="btn btn-success btn-sm">تایید و فعال‌سازی</a>
                                                <?php else: ?>
                                                    <span style="font-size: 0.85rem; color: var(--text-muted);">پردازش شده</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- ۴. بخش لیست کامل اشتراک‌های فعال کاربران -->
            <!-- ========================================== -->
            <div id="section-subscriptions" class="tab-content">
                <div class="card">
                    <h2>🎫 لیست اشتراک‌های تهیه شده توسط کاربران</h2>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>کاربر فرستنده</th>
                                    <th>کسب و کار</th>
                                    <th>پلن اشتراک</th>
                                    <th>شروع اشتراک</th>
                                    <th>اتمام اشتراک</th>
                                    <th>وضعیت نهایی</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                    // دریافت مستقیم اشتراک‌های فعال از دیتابیس
                                    $stmt = \WHCM\Core\Bootstrap::getDB()->query("
                                        SELECT s.*, u.name as user_name, u.email as user_email, u.business_name, p.title as plan_title 
                                        FROM subscriptions s 
                                        JOIN users u ON s.user_id = u.id 
                                        JOIN plans p ON s.plan_id = p.id 
                                        ORDER BY s.id DESC
                                    ");
                                    $subs = $stmt->fetchAll();
                                    if (empty($subs)):
                                ?>
                                    <tr><td colspan="6" style="text-align:center; color:var(--text-muted);">هیچ اشتراک فعالی ثبت نشده است.</td></tr>
                                <?php else: foreach ($subs as $s): ?>
                                    <tr>
                                        <td data-label="کاربر فرستنده">
                                            <strong><?php echo htmlspecialchars($s['user_name']); ?></strong><br>
                                            <span style="font-size:0.75rem; color:var(--text-muted);"><?php echo htmlspecialchars($s['user_email']); ?></span>
                                        </td>
                                        <td data-label="کسب و کار"><strong><?php echo htmlspecialchars($s['business_name'] ?: 'ثبت نشده'); ?></strong></td>
                                        <td data-label="پلن اشتراک"><span class="badge badge-approved"><?php echo htmlspecialchars($s['plan_title']); ?></span></td>
                                        <td data-label="شروع اشتراک"><span style="font-size:0.8rem;"><?php echo \WHCM\Domain\TextFormat::mysql_to_jalali($s['start_date'], false); ?></span></td>
                                        <td data-label="اتمام اشتراک"><span style="font-size:0.8rem; color:#fca5a5;"><?php echo \WHCM\Domain\TextFormat::mysql_to_jalali($s['end_date'], false); ?></span></td>
                                        <td data-label="وضعیت نهایی">
                                            <span style="color: <?php echo $s['status'] === 'active' ? '#10b981' : '#ef4444'; ?>; font-weight:bold;">
                                                <?php echo $s['status'] === 'active' ? 'فعال' : 'منقضی'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- ۵. بخش مدیریت پلن‌های اشتراکی مستقل -->
            <!-- ========================================== -->
            <div id="section-plans" class="tab-content">
                <div class="grid-content" style="grid-template-columns: 1fr;">
                    
                    <!-- فرم ایجاد/ویرایش پلن -->
                    <div class="card" style="border-color: <?php echo $edit_plan ? 'var(--warning)' : 'var(--border)'; ?>;">
                        <?php if ($edit_plan): ?>
                            <h2>⚙ ویرایش پلن اشتراک: «<?php echo htmlspecialchars($edit_plan['title']); ?>»</h2>
                            <form action="<?php echo \WHCM\Core\Bootstrap::getRouteUrl('/hnnh/edit-plan'); ?>" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="plan_id" value="<?php echo $edit_plan['id']; ?>">
                        <?php else: ?>
                            <h2>💎 ساخت پلن اشتراک جدید</h2>
                            <form action="<?php echo \WHCM\Core\Bootstrap::getRouteUrl('/hnnh/create-plan'); ?>" method="POST" enctype="multipart/form-data">
                        <?php endif; ?>

                            <?php echo $csrf_field; ?>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="title">نام پلن اشتراکی:</label>
                                    <input type="text" name="title" id="title" value="<?php echo $edit_plan ? htmlspecialchars($edit_plan['title']) : ''; ?>" required placeholder="مثلا: پلن طلایی ویژه">
                                </div>
                                <div class="form-group">
                                    <label for="price">قیمت پلن (به تومان):</label>
                                    <input type="number" name="price" id="price" value="<?php echo $edit_plan ? (int)$edit_plan['price'] : ''; ?>" required placeholder="قیمت دوره">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="duration_days">مدت اعتبار به روز:</label>
                                    <input type="number" name="duration_days" id="duration_days" value="<?php echo $edit_plan ? (int)$edit_plan['duration_days'] : '30'; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="max_channels">حداکثر سهمیه کانال:</label>
                                    <input type="number" name="max_channels" id="max_channels" value="<?php echo $edit_plan ? (int)$edit_plan['max_channels'] : '3'; ?>" required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="max_posts">حداکثر پست ماهانه (۰ = نامحدود):</label>
                                    <input type="number" name="max_posts" id="max_posts" value="<?php echo $edit_plan ? (int)$edit_plan['max_posts'] : '0'; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="early_renewal_discount">درصد تخفیف تمدید پیش از موعد (درصد):</label>
                                    <input type="number" name="early_renewal_discount" id="early_renewal_discount" value="<?php echo $edit_plan ? (int)$edit_plan['early_renewal_discount'] : '0'; ?>" min="0" max="100" required placeholder="مثلاً: ۱۰">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="general_discount">درصد تخفیف عمومی/مناسبتی کل پلن (درصد):</label>
                                    <input type="number" name="general_discount" id="general_discount" value="<?php echo $edit_plan ? (int)$edit_plan['general_discount'] : '0'; ?>" min="0" max="100" required placeholder="مثلاً: ۱۵">
                                </div>
                                <div class="form-group">
                                    <label for="discount_badge_text">متن برچسب تخفیف روی تصویر (مثال: آفر ویژه عید):</label>
                                    <input type="text" name="discount_badge_text" id="discount_badge_text" value="<?php echo $edit_plan ? htmlspecialchars($edit_plan['discount_badge_text'] ?? '') : ''; ?>" placeholder="خالی بگذارید تا نشان داده نشود">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="plan-desc">توضیحات اختصاصی پلن (مزیت‌های این اشتراک):</label>
                                <textarea name="description" id="plan-desc" rows="4" placeholder="توضیحات را بنویسید..."><?php echo $edit_plan ? htmlspecialchars($edit_plan['description'] ?? '') : ''; ?></textarea>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="plan-img">بارگذاری و آپلود تصویر پلن (فرمت وب‌پی خودکار):</label>
                                    <input type="file" name="plan_image" id="plan-img" accept="image/*" style="padding:0.5rem 1rem;">
                                </div>
                                <div class="form-group">
                                    <label for="payment_url">لینک پرداخت مستقیم اختصاصی (بلو لینک):</label>
                                    <input type="url" name="payment_url" id="payment_url" value="<?php echo $edit_plan ? htmlspecialchars($edit_plan['payment_url'] ?? '') : ''; ?>" placeholder="https://blubank.com/pay/...">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label style="margin-bottom: 0.75rem;">دسترسی‌های مجاز پلن:</label>
                                <?php 
                                    $feats = [];
                                    if ($edit_plan) {
                                        $feats = json_decode($edit_plan['features'] ?? '{}', true);
                                    }
                                    $feat_gold = !empty($feats['gold_ticker']);
                                    $feat_reply = !empty($feats['auto_responder']);
                                    $feat_woo = !empty($feats['woocommerce']);
                                    $feat_ai = !empty($feats['ai_caption']);
                                ?>
                                <div style="display:flex; gap:1.5rem; flex-wrap:wrap;">
                                    <div class="form-group-checkbox" style="margin:0;">
                                        <input type="checkbox" name="feat_gold" id="feat_gold" value="1" <?php echo $feat_gold ? 'checked' : ''; ?>>
                                        <label for="feat_gold" style="margin: 0; cursor: pointer;">📈 ربات قیمت خودکار طلا</label>
                                    </div>
                                    <div class="form-group-checkbox" style="margin:0;">
                                        <input type="checkbox" name="feat_reply" id="feat_reply" value="1" <?php echo $feat_reply ? 'checked' : ''; ?>>
                                        <label for="feat_reply" style="margin: 0; cursor: pointer;">🤖 پاسخگوی کلمات کلیدی</label>
                                    </div>
                                    <div class="form-group-checkbox" style="margin:0;">
                                        <input type="checkbox" name="feat_woo" id="feat_woo" value="1" <?php echo $feat_woo ? 'checked' : ''; ?>>
                                        <label for="feat_woo" style="margin: 0; cursor: pointer;">🛍 اتصال ووکامرس</label>
                                    </div>
                                    <div class="form-group-checkbox" style="margin:0;">
                                        <input type="checkbox" name="feat_ai" id="feat_ai" value="1" <?php echo $feat_ai ? 'checked' : ''; ?>>
                                        <label for="feat_ai" style="margin: 0; cursor: pointer;">🧠 کپشن‌ساز هوش مصنوعی</label>
                                    </div>
                                    <div class="form-group-checkbox" style="margin:0; border-color:var(--warning);">
                                        <input type="checkbox" name="is_featured" id="is_featured" value="1" <?php echo ($edit_plan && !empty($edit_plan['is_featured'])) ? 'checked' : ''; ?>>
                                        <label for="is_featured" style="margin: 0; cursor: pointer; color:var(--primary); font-weight:bold;">⭐ پلن پیشنهادی ویژه (محبوب‌ترین)</label>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if ($edit_plan): ?>
                                <div style="display:flex; gap:1rem;">
                                    <button type="submit" class="btn btn-success btn-block" style="flex:1;">بروزرسانی نهایی پلن ⚙</button>
                                    <a href="?" class="btn btn-danger" style="background:#64748b; color:white;">انصراف</a>
                                </div>
                            <?php else: ?>
                                <button type="submit" class="btn btn-block">ثبت و ایجاد پلن جدید 🚀</button>
                            <?php endif; ?>
                        </form>
                    </div>

                    <!-- لیست پلن‌های فعال -->
                    <div class="card">
                        <h2>📋 لیست کامل پلن‌های اشتراک تعریف شده در پُست‌یار</h2>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>نام پلن</th>
                                        <th>قیمت</th>
                                        <th>مدت</th>
                                        <th>سهمیه کانال</th>
                                        <th>سهمیه پست</th>
                                        <th>عملیات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($plans as $p): ?>
                                        <tr>
                                            <td data-label="نام پلن"><strong><?php echo htmlspecialchars($p['title']); ?></strong></td>
                                            <td data-label="قیمت"><strong style="color:#34d399;"><?php echo \WHCM\Domain\TextFormat::fa_num($p['price']); ?> تومان</strong></td>
                                            <td data-label="مدت"><?php echo \WHCM\Domain\TextFormat::fa_digits($p['duration_days']); ?> روز</td>
                                            <td data-label="سهمیه کانال"><?php echo \WHCM\Domain\TextFormat::fa_digits($p['max_channels']); ?> کانال</td>
                                            <td data-label="سهمیه پست"><?php echo $p['max_posts'] === 0 ? 'نامحدود' : \WHCM\Domain\TextFormat::fa_digits($p['max_posts']) . ' پست'; ?></td>
                                            <td data-label="عملیات">
                                                <div style="display:flex; gap:0.5rem;">
                                                    <a href="<?php echo \WHCM\Core\Bootstrap::getRouteUrl('/hnnh'); ?>&edit_plan=<?php echo $p['id']; ?>" class="btn btn-sm" style="background:#3b82f6;">⚙ ویرایش</a>
                                                    <a href="<?php echo \WHCM\Core\Bootstrap::getRouteUrl('/hnnh/delete-plan'); ?>&id=<?php echo $p['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('آیا از حذف این پلن اشتراک اطمینان دارید؟');">حذف</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>

            <!-- ========================================== -->
            <!-- تنظیمات ربات طلا و سکه (Gold Ticker Admin) -->
            <!-- ========================================== -->
            <div id="section-admin-gold" class="tab-content">
                <div class="card">
                    <h2>🪙 تنظیمات کلان ربات لحظه‌ای طلا، سکه و ارز</h2>
                    <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1.5rem;">پیکربندی سورس‌های رسمی دریافت نرخ و متون پیش‌فرض برای کاربران سامانه پُست‌یار</p>
                    
                    <form action="<?php echo \WHCM\Core\Bootstrap::getRouteUrl('/hnnh/save-gold-settings-admin'); ?>" method="POST">
                        <?php echo $csrf_field; ?>
                        <div class="form-row" style="margin-bottom: 1.5rem;">
                            <div class="form-group">
                                <label for="gold-source">سورس رسمی دریافت زنده نرخ‌ها (API):</label>
                                <select name="gold_api_source" id="gold-source" style="border-radius:10px;">
                                    <option value="tgju" <?php echo (($admin_settings['gold_api_source'] ?? 'tgju') === 'tgju') ? 'selected' : ''; ?>>سامانه شبکه اطلاع‌رسانی طلا، سکه و ارز (TGJU API)</option>
                                    <option value="tala_ir" <?php echo (($admin_settings['gold_api_source'] ?? '') === 'tala_ir') ? 'selected' : ''; ?>>اتحادیه طلا و جواهر ایران (Tala.ir)</option>
                                    <option value="cbi" <?php echo (($admin_settings['gold_api_source'] ?? '') === 'cbi') ? 'selected' : ''; ?>>بانک مرکزی جمهوری اسلامی ایران</option>
                                    <option value="custom" <?php echo (($admin_settings['gold_api_source'] ?? '') === 'custom') ? 'selected' : ''; ?>>سورس اختصاصی و وب‌هوک دستی</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="gold-interval">دوره تناوب استعلام نرخ زنده (Interval):</label>
                                <select name="gold_interval" id="gold-interval" style="border-radius:10px;">
                                    <option value="60" <?php echo (($admin_settings['gold_interval'] ?? '') === '60') ? 'selected' : ''; ?>>هر ۶۰ ثانیه یک‌بار (پرفشار)</option>
                                    <option value="180" <?php echo (($admin_settings['gold_interval'] ?? '180') === '180') ? 'selected' : ''; ?>>هر ۳ دقیقه یک‌بار (پیشنهادی)</option>
                                    <option value="300" <?php echo (($admin_settings['gold_interval'] ?? '') === '300') ? 'selected' : ''; ?>>هر ۵ دقیقه یک‌بار</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 1.5rem;">
                            <label for="gold-custom-url">🔑 آدرس API دستی / کلید اختصاصی (برای سورس custom یا جایگزینی):
                            <span style="display:block; color:var(--text-muted); font-size:0.78rem; margin-top:0.25rem;">این آدرس به کاربرانی که پلن طلا دارند به عنوان API پیش‌فرض تخصیص داده می‌شود. اگر خالی بگذارید، آدرس پیش‌فرض TGJU استفاده خواهد شد.</span></label>
                            <input type="url" name="gold_custom_api_url" id="gold-custom-url" value="<?php echo htmlspecialchars($admin_settings['gold_custom_api_url'] ?? ''); ?>" placeholder="https://api.example.com/gold/prices" class="dir-ltr" style="border-radius:10px; font-size:0.85rem;">
                        </div>

                        <div class="form-group">
                            <label for="gold-default-template">الگوی پیش‌فرض ارسال قیمت طلا برای کاربران جدید:</label>
                            <textarea name="gold_default_template" id="gold-default-template" rows="6" style="font-family: monospace, Vazirmatn; line-height: 1.8;"><?php echo htmlspecialchars($admin_settings['gold_default_template'] ?? '🔸 نرخ لحظه‌ای طلا و سکه در بازار:

🥇 طلا ۱۸ عیار: {gold_18k} تومان
🪙 سکه امامی: {coin_emami} تومان
🪙 سکه بهار آزادی: {coin_bahar} تومان
🌕 انس جهانی طلا: {gold_ounce} دلار

📌 به‌روزرسانی خودکار توسط پُست‌یار'); ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-success" style="width: 100%; padding: 0.9rem; font-size: 1rem;">
                            💾 ذخیره و اعمال تنظیمات کلان ربات طلا و سکه
                        </button>
                    </form>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- تنظیمات سراسری هوش مصنوعی (AI Admin) -->
            <!-- ========================================== -->
            <div id="section-admin-ai" class="tab-content">
                <div class="card">
                    <h2>🧠 تنظیمات سراسری هوش مصنوعی و مدل‌های مولد</h2>
                    <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1.5rem;">تعریف ارائه‌دهندگان هوش مصنوعی (OpenAI، OpenRouter، Groq و مدل‌های دلخواه دستی) برای کاربران پُست‌یار</p>
                    
                    <form action="<?php echo \WHCM\Core\Bootstrap::getRouteUrl('/hnnh/save-ai-settings-admin'); ?>" method="POST" data-saved-provider="<?php echo htmlspecialchars($admin_settings['ai_global_provider'] ?? 'openai'); ?>" data-saved-model="<?php echo htmlspecialchars($admin_settings['ai_global_model'] ?? ''); ?>" data-saved-url="<?php echo htmlspecialchars($admin_settings['ai_global_url'] ?? ''); ?>">
                        <?php echo $csrf_field; ?>
                        
                        <div class="form-row" style="margin-bottom: 1.5rem;">
                            <div class="form-group">
                                <label for="ai-g-provider">ارائه‌دهنده پیش‌فرض هوش مصنوعی در پلتفرم:</label>
                                <select name="ai_global_provider" id="ai-g-provider" style="border-radius:10px;">
                                    <option value="openai" <?php echo (($admin_settings['ai_global_provider'] ?? '') === 'openai') ? 'selected' : ''; ?>>OpenAI (GPT-4o / o3-mini / GPT-3.5-turbo)</option>
                                    <option value="deepseek" <?php echo (($admin_settings['ai_global_provider'] ?? '') === 'deepseek') ? 'selected' : ''; ?>>DeepSeek (V3 / R1 استدلالی)</option>
                                    <option value="anthropic" <?php echo (($admin_settings['ai_global_provider'] ?? '') === 'anthropic') ? 'selected' : ''; ?>>Anthropic Claude (Claude 4 Sonnet / 3.5)</option>
                                    <option value="openrouter" <?php echo (($admin_settings['ai_global_provider'] ?? '') === 'openrouter') ? 'selected' : ''; ?>>OpenRouter AI (تمام مدل‌ها با یک کلید)</option>
                                    <option value="groq" <?php echo (($admin_settings['ai_global_provider'] ?? '') === 'groq') ? 'selected' : ''; ?>>Groq (Llama-3.3-70b / Mixtral)</option>
                                    <option value="gemini" <?php echo (($admin_settings['ai_global_provider'] ?? '') === 'gemini') ? 'selected' : ''; ?>>Google Gemini (2.5 Pro / 2.0 Flash)</option>
                                    <option value="custom" <?php echo (($admin_settings['ai_global_provider'] ?? '') === 'custom') ? 'selected' : ''; ?>>مدل دلخواه دستی (Custom AI Model / Custom URL)</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="ai-g-model">نام مدل پیش‌فرض (Model Name):</label>
                                <select name="ai_global_model" id="ai-g-model" style="border-radius:10px;" class="dir-ltr"></select>
                                <input type="text" id="ai-g-model-custom" placeholder="نام مدل دلخواه را وارد کنید" style="display:none; margin-top:0.5rem; border-radius:10px;" class="dir-ltr" value="<?php echo htmlspecialchars($admin_settings['ai_global_model'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="form-row" style="margin-bottom: 1.5rem;">
                            <div class="form-group">
                                <label for="ai-g-key">کلید اصلی دسترسی به API (Global API Key):</label>
                                <input type="password" name="ai_global_key" id="ai-g-key" placeholder="sk-..." class="dir-ltr" value="<?php echo htmlspecialchars($admin_settings['ai_global_key'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="ai-g-url">آدرس اختصاصی endpoint (برای مدل دستی / Custom URL):</label>
                                <input type="url" name="ai_global_url" id="ai-g-url" placeholder="https://api.openai.com/v1/chat/completions" class="dir-ltr" value="<?php echo htmlspecialchars($admin_settings['ai_global_url'] ?? ''); ?>">
                            </div>
                        </div>

                        <div style="background: rgba(99,102,241,0.1); border: 1px solid rgba(99,102,241,0.3); padding: 1rem 1.25rem; border-radius: 12px; margin-bottom: 1.5rem;">
                            <label style="display:flex; align-items:center; gap:0.5rem; color:white; cursor:pointer; margin:0;">
                                <input type="checkbox" name="ai_active_by_default" value="1" <?php echo (empty($admin_settings['ai_active_by_default']) || $admin_settings['ai_active_by_default'] === '1') ? 'checked' : ''; ?> style="width:18px; height:18px;">
                                <span>فعال‌سازی دستیار نگارش هوشمند (AI Writer) برای کاربران دارای اشتراک مجاز</span>
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.9rem; font-size: 1rem; background-color: #a855f7;">
                            💾 ذخیره پیکربندی و مدل‌های سراسری هوش مصنوعی
                        </button>
                    </form>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- کدهای تخفیف (Discounts - تقویم شمسی) -->
            <!-- ========================================== -->
            <div id="section-discounts" class="tab-content">
                <div class="card">
                    <h2>🎁 مدیریت کدهای تخفیف و جشنواره‌ها</h2>
                    <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1.5rem;">کدهای تخفیفی که کاربران می‌توانند هنگام خرید اشتراک از آن‌ها استفاده کنند (با تقویم شمسی و اعداد فارسی).</p>
                    
                    <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>کد تخفیف</th>
                                    <th>درصد تخفیف</th>
                                    <th>حداکثر استفاده</th>
                                    <th>تعداد استفاده‌شده</th>
                                    <th>تاریخ انقضا (شمسی)</th>
                                    <th>وضعیت</th>
                                    <th>عملیات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($discounts)): ?>
                                    <?php foreach ($discounts as $d): ?>
                                        <tr>
                                            <td><strong style="color: var(--warning); font-family: monospace; font-size: 1.1rem;"><?php echo htmlspecialchars($d['code']); ?></strong></td>
                                            <td>٪<?php echo \WHCM\Domain\TextFormat::fa_digits($d['percentage']); ?></td>
                                            <td><?php echo $d['max_uses'] == 0 ? 'نامحدود' : \WHCM\Domain\TextFormat::fa_digits($d['max_uses']); ?></td>
                                            <td><?php echo \WHCM\Domain\TextFormat::fa_digits($d['used_count']); ?></td>
                                            <td><?php echo htmlspecialchars($d['expires_at'] ?? 'بدون انقضا'); ?></td>
                                            <td>
                                                <?php if ($d['status'] === 'active'): ?>
                                                    <span class="badge badge-success">فعال</span>
                                                <?php else: ?>
                                                    <span class="badge badge-danger">غیرفعال</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <form action="<?php echo \WHCM\Core\Bootstrap::getRouteUrl('/hnnh/delete-discount'); ?>" method="POST" style="display:inline-block;" onsubmit="return confirm('آیا از حذف این کد تخفیف اطمینان دارید؟');">
                                                    <?php echo $csrf_field; ?>
                                                    <input type="hidden" name="discount_id" value="<?php echo $d['id']; ?>">
                                                    <button type="submit" class="btn btn-danger" style="padding: 0.3rem 0.6rem; font-size: 0.75rem;">🗑 حذف</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" style="text-align: center; color: var(--text-muted);">هیچ کد تخفیفی تعریف نشده است.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <h3>➕ ایجاد کد تخفیف جدید</h3>
                    <form action="<?php echo \WHCM\Core\Bootstrap::getRouteUrl('/hnnh/add-discount'); ?>" method="POST">
                        <?php echo $csrf_field; ?>
                        <div class="form-row">
                            <div class="form-group">
                                <label>کد تخفیف (حروف انگلیسی یا عدد):</label>
                                <input type="text" name="code" required placeholder="مثلاً: NOWRUZ1405" style="text-transform: uppercase;">
                            </div>
                            <div class="form-group">
                                <label>درصد تخفیف (۱ تا ۱۰۰):</label>
                                <input type="number" name="percentage" required min="1" max="100" placeholder="مثلاً: 25">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>حداکثر تعداد دفعات مجاز استفاده (۰ = نامحدود):</label>
                                <input type="number" name="max_uses" value="0" required placeholder="مثلاً: 100">
                            </div>
                            <div class="form-group">
                                <label>تاریخ انقضا (انتخاب از تقویم شمسی - خالی برای بدون انقضا):</label>
                                <input type="text" name="expires_at" id="admin-discount-date" data-jdp placeholder="مثلاً: 1405/01/15 23:59" class="dir-ltr" readonly style="cursor: pointer; background: var(--bg-dark); color: #34d399; font-weight: bold; border: 2px solid #34d399;">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success" style="width: 100%;">ثبت و فعال‌سازی کد تخفیف 🎁</button>
                    </form>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- ۶. بخش ارسال اعلان همگانی -->
            <!-- ========================================== -->
            
            <!-- ========================================== -->
            <!-- تنظیمات پاسخگوی هوشمند (مدیریت) -->
            <!-- ========================================== -->
            <div id="section-admin-responder" class="tab-content">
                <div class="card">
                    <h2>🤖 تنظیمات کلان پاسخگوی هوشمند کلمات کلیدی</h2>
                    <p style="color:var(--text-muted); font-size:0.85rem; margin-bottom:1.25rem;">پیکربندی پیش‌فرض، محدودیت‌ها و پیام‌های سیستمی پاسخگوی خودکار برای تمام کاربران (کاربر فقط در صورت داشتن پلن مجاز به این بخش دسترسی دارد)</p>
                    <form action="<?php echo \WHCM\Core\Bootstrap::getRouteUrl('/hnnh/save-responder-settings-admin'); ?>" method="POST">
                        <?php echo $csrf_field; ?>
                        <div class="form-row">
                            <div class="form-group">
                                <label>حداکثر کلمات کلیدی مجاز برای هر کاربر</label>
                                <input type="number" name="responder_max_keywords" value="<?php echo htmlspecialchars($global_responder['responder_max_keywords'] ?? '20'); ?>" min="1" max="200">
                            </div>
                            <div class="form-group">
                                <label>تأخیر پاسخ (ثانیه)</label>
                                <select name="responder_delay">
                                    <option value="0">بدون تأخیر</option>
                                    <option value="2">۲ ثانیه</option>
                                    <option value="5">۵ ثانیه</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>پیام پیش‌فرض در صورت عدم تطابق کلمه</label>
                            <textarea name="responder_fallback" rows="3" placeholder="پیامی که اگر کلمه یافت نشد ارسال شود (خالی = بدون پاسخ)"><?php echo htmlspecialchars($global_responder['responder_fallback'] ?? ''); ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary" style="width:100%;">ذخیره تنظیمات پاسخگو 🤖</button>
                    </form>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- تنظیمات اتصال ووکامرس (مدیریت) -->
            <!-- ========================================== -->
            <div id="section-admin-woo" class="tab-content">
                <div class="card">
                    <h2>🛍 تنظیمات کلان اتصال ووکامرس</h2>
                    <p style="color:var(--text-muted); font-size:0.85rem; margin-bottom:1.25rem;">راهنما و محدودیت‌های اتصال فروشگاه ووکامرس — کاربران فقط در صورت داشتن پلن دارای ووکامرس به تنظیمات اتصال دسترسی دارند</p>
                    <form action="<?php echo \WHCM\Core\Bootstrap::getRouteUrl('/hnnh/save-woo-settings-admin'); ?>" method="POST">
                        <?php echo $csrf_field; ?>
                        <div class="form-group">
                            <label>متن راهنمای اتصال برای کاربران</label>
                            <textarea name="woo_help_text" rows="4"><?php echo htmlspecialchars($global_woo['woo_help_text'] ?? 'برای اتصال، از پیشخوان وردپرس → ووکامرس → تنظیمات → پیشرفته → REST API یک کلید با دسترسی خواندن/نوشتن بسازید.'); ?></textarea>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>حداکثر فروشگاه مجاز برای هر کاربر</label>
                                <input type="number" name="woo_max_stores" value="<?php echo htmlspecialchars($global_woo['woo_max_stores'] ?? '1'); ?>" min="1" max="5">
                            </div>
                            <div class="form-group">
                                <label style="display:flex; align-items:center; gap:0.5rem; margin-top:1.5rem;"><input type="checkbox" name="woo_require_ssl" value="1" checked> الزام اتصال امن (HTTPS)</label>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary" style="width:100%;">ذخیره تنظیمات ووکامرس 🛍</button>
                    </form>
                </div>
            </div>

            <div id="section-broadcast" class="tab-content">
                <div class="card" style="border: 1px solid rgba(99, 102, 241, 0.25); background: linear-gradient(135deg, rgba(99, 102, 241, 0.05) 0%, rgba(15, 23, 42, 0.6) 100%);">
                    <h2>📢 ارسال اعلان همگانی درون‌برنامه‌ای</h2>
                    <form action="<?php echo \WHCM\Core\Bootstrap::getRouteUrl('/hnnh/broadcast-announcement'); ?>" method="POST">
                        <?php echo $csrf_field; ?>
                        <div class="form-group">
                            <label for="ann-title">عنوان اعلان:</label>
                            <input type="text" name="title" id="ann-title" required placeholder="مثال: بروزرسانی جدید هسته پُست‌یار">
                        </div>
                        <div class="form-group">
                            <label>انتخاب جامعه هدف (پلن‌های اشتراکی):</label>
                            <div style="display:flex; gap:1rem; flex-wrap:wrap; background:rgba(15,23,42,0.6); padding:1rem; border-radius:12px; border:1px solid #334155;">
                                <label style="display:flex; align-items:center; gap:0.4rem; color:white; cursor:pointer;">
                                    <input type="radio" name="target_plans" value="all" checked style="width:16px; height:16px;">
                                    <span>همه کاربران پلتفرم</span>
                                </label>
                                <?php foreach ($plans as $p_opt): ?>
                                    <label style="display:flex; align-items:center; gap:0.4rem; color:#a5b4fc; cursor:pointer;">
                                        <input type="radio" name="target_plans" value="<?php echo $p_opt['id']; ?>" style="width:16px; height:16px;">
                                        <span>کاربران پلن «<?php echo htmlspecialchars($p_opt['title']); ?>»</span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="ann-msg">متن پیام اعلان:</label>
                            <textarea name="message" id="ann-msg" rows="5" required placeholder="متن پیام شما برای نمایش در بالای پیشخوان کاربران منتخب..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary" style="width:100%; background:linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);">ارسال اعلان به کاربران هدف 📢</button>
                    </form>
                </div>
                <div class="card" style="margin-top:2rem;">
                    <h2>📋 آمار و تاریخچه اعلان‌های ارسالی</h2>
                    <?php if (empty($announcements_list)): ?>
                        <p style="color:var(--text-muted); text-align:center; padding:1.5rem 0;">هیچ اعلانی تا کنون ارسال نشده است.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>عنوان اعلان</th>
                                        <th>جامعه هدف</th>
                                        <th>تاریخ ارسال</th>
                                        <th>متن پیام</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($announcements_list as $ann_item): ?>
                                        <tr>
                                            <td><strong style="color:white;"><?php echo htmlspecialchars($ann_item['title']); ?></strong></td>
                                            <td>
                                                <span class="badge badge-success">
                                                    <?php 
                                                        if ($ann_item['target_plans'] === 'all' || empty($ann_item['target_plans'])) {
                                                            echo 'همه کاربران';
                                                        } else {
                                                            echo 'پلن اختصاصی شناسه ' . \WHCM\Domain\TextFormat::fa_digits($ann_item['target_plans']);
                                                        }
                                                    ?>
                                                </span>
                                            </td>
                                            <td><span style="font-size:0.8rem; color:#a5b4fc;"><?php echo htmlspecialchars($ann_item['created_at']); ?></span></td>
                                            <td><span style="font-size:0.8rem; color:#cbd5e1;"><?php echo htmlspecialchars(mb_substr($ann_item['message'], 0, 60)) . '...'; ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- ۷. بخش تنظیمات کارت بانکی پُست‌یار (Bank Settings) -->
            <!-- ========================================== -->
            <div id="section-bank" class="tab-content">
                <div class="card">
                    <h2>💳 تنظیمات و شماره کارت بانکی عمومی سامانه</h2>
                    <p style="color:var(--text-muted); font-size:0.85rem; line-height:1.7; margin-bottom:1.5rem;">
                        شماره کارت و مشخصات حساب بانکی خود را از این بخش ویرایش کنید. این کارت گرافیکی فین‌تک در تب تمدید اشتراک تمام کاربران پلتفرم به صورت اتوماتیک تغییر خواهد کرد!
                    </p>

                    <?php 
                        // لود فیلدهای کارت بانکی و پشتیبانی ادمین
                        $stmt = \WHCM\Core\Bootstrap::getDB()->prepare("SELECT key_name, key_value FROM settings WHERE tenant_id = 0 AND key_name IN ('admin_card_number', 'admin_card_holder', 'admin_bank_name', 'support_telegram_url', 'support_bale_url', 'support_email', 'occasion_discount_text')");
                        $stmt->execute();
                        $global_bank_rows = $stmt->fetchAll();
                        $global_bank = [];
                        foreach ($global_bank_rows as $row) {
                            $global_bank[$row['key_name']] = $row['key_value'];
                        }
                        $saved_card = $global_bank['admin_card_number'] ?? '۶۲۱۹-۸۶۱۰-xxxx-xxxx';
                        $saved_holder = $global_bank['admin_card_holder'] ?? 'هومن نقشی';
                        $saved_bank = $global_bank['admin_bank_name'] ?? 'بانک سامان';
                        $saved_tele = $global_bank['support_telegram_url'] ?? 'https://t.me/asovin_support';
                        $saved_bale = $global_bank['support_bale_url'] ?? 'https://ble.ir/asovin_support';
                        $saved_email = $global_bank['support_email'] ?? 'support@asovin.ir';
                        $saved_occ = $global_bank['occasion_discount_text'] ?? 'تخفیف مناسبتی';
                    ?>

                    <form action="<?php echo \WHCM\Core\Bootstrap::getRouteUrl('/hnnh/save-bank-settings'); ?>" method="POST">
                        <?php echo $csrf_field; ?>
                        
                        <div class="form-group">
                            <label for="bk-card">شماره کارت ۱۶ رقمی (فرمت خط تیره خودکار اعمال می‌شود):</label>
                            <input type="text" name="card_number" id="bk-card" value="<?php echo htmlspecialchars($saved_card); ?>" required placeholder="62198610xxxxxxxx">
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="bk-holder">نام و نام خانوادگی صاحب حساب:</label>
                                <input type="text" name="card_holder" id="bk-holder" value="<?php echo htmlspecialchars($saved_holder); ?>" required placeholder="هومن نقشی">
                            </div>
                            <div class="form-group">
                                <label for="bk-name">نام بانک صادرکننده:</label>
                                <input type="text" name="bank_name" id="bk-name" value="<?php echo htmlspecialchars($saved_bank); ?>" required placeholder="بانک سامان">
                            </div>
                        </div>

                        <!-- تنظیمات پویای سایر روش‌های تماس با پشتیبانی -->
                        <h3 style="font-size: 0.95rem; margin-top: 1.5rem; margin-bottom: 0.75rem; border-bottom: 1px dashed var(--border); padding-bottom: 0.4rem; color:#a5b4fc;">📞 تنظیمات راه‌های ارتباطی فرعی پشتیبانی کاربران</h3>
                        <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:1rem; margin-bottom: 1.5rem;">
                            <div class="form-group">
                                <label for="sup-tele">آدرس پشتیبانی تلگرام:</label>
                                <input type="url" name="support_telegram_url" id="sup-tele" value="<?php echo htmlspecialchars($saved_tele); ?>" placeholder="https://t.me/...">
                            </div>
                            <div class="form-group">
                                <label for="sup-bale">آدرس پشتیبانی بله:</label>
                                <input type="url" name="support_bale_url" id="sup-bale" value="<?php echo htmlspecialchars($saved_bale); ?>" placeholder="https://ble.ir/...">
                            </div>
                            <div class="form-group">
                                <label for="sup-mail">نشانی ایمیل پشتیبانی:</label>
                                <input type="email" name="support_email" id="sup-mail" value="<?php echo htmlspecialchars($saved_email); ?>" placeholder="support@example.com">
                            </div>
                        </div>



                        <button type="submit" class="btn btn-success" style="width:100%;">ذخیره تنظیمات بانک، پشتیبانی و تخفیف‌های پُست‌یار 💳✔</button>
                    </form>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- ۹. بخش تنظیمات زیرمجموعه‌گیری -->
            <!-- ========================================== -->
            <div id="section-referral-settings" class="tab-content">
                <?php
                    $admin_ref_settings = \WHCM\Domain\Referral::getAdminSettings();
                    $settings = $admin_ref_settings;
                ?>
                <?php include __DIR__ . '/partials/admin-referral-settings.php'; ?>
            </div>

            <!-- ========================================== -->
            <!-- ۱۰. بخش تنظیمات پیامک -->
            <!-- ========================================== -->
            <div id="section-sms-settings" class="tab-content">
                <?php
                    $sms_settings = [];
                    $db = \WHCM\Core\Bootstrap::getDB();
                    $sms_keys = ['sms_enabled', 'sms_api_key', 'sms_line_number'];
                    foreach ($sms_keys as $sk) {
                        $sstmt = $db->prepare("SELECT key_value FROM settings WHERE tenant_id = 0 AND key_name = ? LIMIT 1");
                        $sstmt->execute([$sk]);
                        $srow = $sstmt->fetch();
                        $sms_settings[$sk] = $srow !== false ? $srow['key_value'] : '';
                    }
                    $templates = $db->query("SELECT * FROM sms_templates ORDER BY id ASC")->fetchAll();
                    $logs = $db->query("SELECT sl.*, st.template_name, st.event_key FROM sms_log sl LEFT JOIN sms_templates st ON sl.template_id = st.template_id ORDER BY sl.id DESC LIMIT 50")->fetchAll();
                    $active_users = $db->query("SELECT id, name, phone FROM users WHERE status = 'active' AND role != 'superadmin' ORDER BY id DESC")->fetchAll();
                    $filter_status = $_GET['filter_status'] ?? '';
                    $filter_phone = trim($_GET['filter_phone'] ?? '');
                ?>
                <?php include __DIR__ . '/partials/admin-sms-settings.php'; ?>
            </div>

            <!-- ========================================== -->
            <!-- ۱۱. بخش تنظیمات ایمیل -->
            <!-- ========================================== -->
            <div id="section-email-settings" class="tab-content">
                <?php
                    $email_settings = [];
                    $edb = \WHCM\Core\Bootstrap::getDB();
                    $smtp_keys = ['smtp_enabled', 'smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'smtp_encryption', 'smtp_from_address', 'smtp_from_name'];
                    foreach ($smtp_keys as $ek) {
                        $estmt = $edb->prepare("SELECT key_value FROM settings WHERE tenant_id = 0 AND key_name = ? LIMIT 1");
                        $estmt->execute([$ek]);
                        $erow = $estmt->fetch();
                        $email_settings[$ek] = $erow !== false ? $erow['key_value'] : '';
                    }
                    $email_templates = \WHCM\Core\EmailTemplate::getAllTemplates();
                    $email_logs = \WHCM\Core\EmailTemplate::getLog(50, 0, !empty($_GET['filter_status']) ? $_GET['filter_status'] : null);
                    $email_stats = \WHCM\Core\EmailTemplate::getAdminEmailStats();
                    $active_users = $edb->query("SELECT id, name, email FROM users WHERE status = 'active' AND role != 'superadmin' ORDER BY id DESC")->fetchAll();
                    $all_users = $edb->query("SELECT id, name, email FROM users WHERE role != 'superadmin' ORDER BY id DESC")->fetchAll();
                    $filter_status = $_GET['filter_status'] ?? '';
                ?>
                <?php include __DIR__ . '/partials/admin-email-settings.php'; ?>
            </div>

            <!-- ========================================== -->
            <!-- ۸. بخش تیکت‌های پشتیبانی — سیستم حرفه‌ای -->
            <!-- ========================================== -->
            <div id="section-tickets" class="tab-content">
                <!-- مدیریت دسته‌بندی‌ها و پشتیبان‌ها -->
                <?php if (!$is_support): ?>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; margin-bottom:1.5rem;">
                    <div class="card">
                        <h2 style="font-size:1rem; margin-bottom:1rem;">🏷️ مدیریت دسته‌بندی تیکت‌ها</h2>
                        <p style="color:var(--text-muted); font-size:0.8rem; margin-bottom:1rem;">دسته‌بندی‌ها در فرم ثبت تیکت کاربران نمایش داده می‌شوند. می‌توانید هر دسته را به یک پشتیبان اختصاص دهید.</p>
                        <div id="cat-editor-area"></div>
                        <div style="display:flex; gap:0.5rem; margin-top:0.75rem;">
                            <button type="button" class="btn btn-sm" style="background:rgba(99,102,241,0.2); color:#a5b4fc; border:1px solid rgba(99,102,241,0.3);" onclick="addCategoryRow()">➕ افزودن دسته‌بندی</button>
                            <button type="button" class="btn btn-sm" style="background:rgba(16,185,129,0.2); color:#34d399; border:1px solid rgba(16,185,129,0.3);" onclick="saveCategories()">💾 ذخیره تغییرات</button>
                        </div>
                    </div>
                    <div class="card">
                        <h2 style="font-size:1rem; margin-bottom:1rem;">🎧 لیست کاربران پشتیبان</h2>
                        <p style="color:var(--text-muted); font-size:0.8rem; margin-bottom:1rem;">کاربران با نقش «پشتیبان» فقط به بخش تیکت‌ها دسترسی دارند.</p>
                        <?php if (empty($support_agents)): ?>
                            <p style="color:#94a3b8; font-size:0.85rem; text-align:center; padding:1rem;">هیچ پشتیبانی ثبت نشده است. از بخش «مدیریت کاربران» با نقش «پشتیبان» بسازید.</p>
                        <?php else: ?>
                            <div style="display:flex; flex-direction:column; gap:0.5rem;">
                                <?php foreach ($support_agents as $agent): ?>
                                    <div style="display:flex; justify-content:space-between; align-items:center; background:#1e293b; border:1px solid #334155; border-radius:10px; padding:0.75rem 1rem;">
                                        <div>
                                            <strong style="color:white; font-size:0.9rem;"><?php echo htmlspecialchars($agent['name']); ?></strong>
                                            <div style="color:#94a3b8; font-size:0.75rem;"><?php echo htmlspecialchars($agent['email']); ?></div>
                                        </div>
                                        <span style="background:rgba(99,102,241,0.2); color:#a5b4fc; padding:0.25rem 0.75rem; border-radius:8px; font-size:0.75rem; font-weight:700;">🎧 پشتیبان</span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- کارت آمار تیکت‌ها -->
                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(160px, 1fr)); gap:1rem; margin-bottom:1.5rem;">
                    <div style="background:linear-gradient(135deg, rgba(99,102,241,0.15) 0%, rgba(15,23,42,0.8) 100%); border:1px solid rgba(99,102,241,0.3); border-radius:16px; padding:1.25rem; text-align:center;">
                        <div style="font-size:2rem;">🎟</div>
                        <div style="font-size:0.8rem; color:#94a3b8; margin:0.3rem 0;">کل تیکت‌ها</div>
                        <strong style="color:#a5b4fc; font-size:1.4rem;"><?php echo \WHCM\Domain\TextFormat::fa_digits(count($tickets)); ?></strong>
                    </div>
                    <div style="background:linear-gradient(135deg, rgba(245,158,11,0.15) 0%, rgba(15,23,42,0.8) 100%); border:1px solid rgba(245,158,11,0.3); border-radius:16px; padding:1.25rem; text-align:center;">
                        <div style="font-size:2rem;">⏳</div>
                        <div style="font-size:0.8rem; color:#94a3b8; margin:0.3rem 0;">در انتظار پاسخ</div>
                        <strong style="color:#fbbf24; font-size:1.4rem;"><?php echo \WHCM\Domain\TextFormat::fa_digits($open_t_count); ?></strong>
                    </div>
                    <div style="background:linear-gradient(135deg, rgba(16,185,129,0.15) 0%, rgba(15,23,42,0.8) 100%); border:1px solid rgba(16,185,129,0.3); border-radius:16px; padding:1.25rem; text-align:center;">
                        <div style="font-size:2rem;">✔</div>
                        <div style="font-size:0.8rem; color:#94a3b8; margin:0.3rem 0;">پاسخ داده شده</div>
                        <strong style="color:#34d399; font-size:1.4rem;"><?php
                            $replied_count = 0;
                            foreach ($tickets as $tc) { if($tc['status'] === 'replied') $replied_count++; }
                            echo \WHCM\Domain\TextFormat::fa_digits($replied_count);
                        ?></strong>
                    </div>
                    <div style="background:linear-gradient(135deg, rgba(239,68,68,0.12) 0%, rgba(15,23,42,0.8) 100%); border:1px solid rgba(239,68,68,0.2); border-radius:16px; padding:1.25rem; text-align:center;">
                        <div style="font-size:2rem;">🔒</div>
                        <div style="font-size:0.8rem; color:#94a3b8; margin:0.3rem 0;">بسته شده</div>
                        <strong style="color:#f87171; font-size:1.4rem;"><?php
                            $closed_count = 0;
                            foreach ($tickets as $tc) { if($tc['status'] === 'closed') $closed_count++; }
                            echo \WHCM\Domain\TextFormat::fa_digits($closed_count);
                        ?></strong>
                    </div>
                </div>

                <!-- دکمه ارسال پیام جدید + فیلتر -->
                <div class="card">
                    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; margin-bottom:1.5rem; border-bottom:1px solid var(--border); padding-bottom:1rem;">
                        <h2 style="margin:0; border:none; padding:0;">🎫 مرکز تیکت‌ها و پیام‌رسانی</h2>
                        <button type="button" class="btn" style="background:linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); font-size:0.85rem; padding:0.7rem 1.2rem;" onclick="document.getElementById('newTicketModal').style.display='flex'">✉️ ارسال پیام جدید به کاربر</button>
                    </div>

                    <!-- فیلتر وضعیت -->
                    <div style="display:flex; gap:0.5rem; flex-wrap:wrap; margin-bottom:1.25rem;">
                        <button type="button" class="ticket-filter-btn active" onclick="filterTickets('all', this)">همه</button>
                        <button type="button" class="ticket-filter-btn" onclick="filterTickets('open', this)">⏳ باز</button>
                        <button type="button" class="ticket-filter-btn" onclick="filterTickets('replied', this)">✔ پاسخ‌داده‌شده</button>
                        <button type="button" class="ticket-filter-btn" onclick="filterTickets('closed', this)">🔒 بسته‌شده</button>
                    </div>

                    <?php if (empty($tickets)): ?>
                        <div style="text-align:center; padding:3rem 1rem;">
                            <div style="font-size:4rem; margin-bottom:1rem;">📭</div>
                            <p style="color:var(--text-muted); font-size:1.05rem; margin-bottom:0.75rem; font-weight:bold;">هنوز هیچ تیکتی ثبت نشده است</p>
                            <p style="color:var(--text-muted); font-size:0.85rem; line-height:1.8; margin-bottom:1.5rem;">کاربران از بخش «پشتیبانی» در پیشخوان خود تیکت ثبت می‌کنند.<br>همچنین می‌توانید با دکمه بالا پیام مستقیم به هر کاربر ارسال کنید.</p>
                            <button type="button" class="btn" style="background:linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);" onclick="document.getElementById('newTicketModal').style.display='flex'">✉️ ارسال پیام به کاربر</button>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive" style="border:none; background:transparent; border-radius:0;">
                            <div id="tickets-list-container">
                                <?php foreach ($tickets as $t): ?>
                                <div class="ticket-card-row" data-status="<?php echo $t['status']; ?>">
                                    <div class="ticket-card-header">
                                        <div class="ticket-card-user">
                                            <div class="ticket-avatar"><?php echo mb_substr(htmlspecialchars($t['user_name'] ?? '؟'), 0, 1); ?></div>
                                            <div>
                                                <strong style="color:#fff; font-size:0.9rem;"><?php echo htmlspecialchars($t['user_name'] ?? 'نامشخص'); ?></strong><br>
                                                <span style="font-size:0.75rem; color:#94a3b8;"><?php echo htmlspecialchars($t['user_email'] ?? ''); ?></span>
                                            </div>
                                        </div>
                                        <div style="display:flex; align-items:center; gap:0.5rem;">
                                            <?php if (!empty($t['created_by_admin'])): ?>
                                                <span style="font-size:0.7rem; background:rgba(99,102,241,0.2); color:#a5b4fc; padding:0.2rem 0.6rem; border-radius:6px;">📤 ارسال توسط ادمین</span>
                                            <?php endif; ?>
                                            <span class="badge badge-<?php echo $t['status'] === 'open' ? 'pending' : ($t['status'] === 'replied' ? 'approved' : 'danger'); ?>">
                                                <?php echo $t['status'] === 'open' ? '⏳ باز' : ($t['status'] === 'replied' ? '✔ پاسخ‌داده‌شده' : '🔒 بسته‌شده'); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="ticket-card-subject"><?php echo htmlspecialchars($t['subject']); ?></div>
                                    <div class="ticket-card-preview"><?php echo mb_substr(strip_tags($t['message']), 0, 120); ?>...</div>
                                    <div class="ticket-card-actions">
                                        <button type="button" class="btn btn-sm" style="background:linear-gradient(135deg, #6366f1 0%, #4f46e5 100%) !important; color:#fff !important; font-weight:800; border:none;" onclick='openAdminTicketModal(<?php echo json_encode($t, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP|JSON_UNESCAPED_UNICODE); ?>)'>👁 مشاهده و پاسخ</button>
                                        <?php if ($t['status'] === 'closed'): ?>
                                            <form action="<?php echo \WHCM\Core\Bootstrap::getRouteUrl('/hnnh/reopen-ticket'); ?>" method="POST" style="display:inline;">
                                                <?php echo $csrf_field; ?>
                                                <input type="hidden" name="ticket_id" value="<?php echo $t['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline" title="باز کردن مجدد">🔄</button>
                                            </form>
                                        <?php endif; ?>
                                        <form action="<?php echo \WHCM\Core\Bootstrap::getRouteUrl('/hnnh/delete-ticket'); ?>" method="POST" style="display:inline;" onsubmit="return confirm('آیا از حذف این تیکت اطمینان دارید؟')">
                                            <?php echo $csrf_field; ?>
                                            <input type="hidden" name="ticket_id" value="<?php echo $t['id']; ?>">
                                            <button type="submit" class="btn btn-sm" style="background:rgba(239,68,68,0.15); color:#f87171; border:1px solid rgba(239,68,68,0.3);" title="حذف تیکت">🗑</button>
                                        </form>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </main>
    </div>

    <script src="<?php echo \WHCM\Core\Bootstrap::getAssetsUrl(); ?>/js/utils.js"></script>
    <script src="<?php echo \WHCM\Core\Bootstrap::getAssetsUrl(); ?>/js/admin.js"></script>
    <script src="<?php echo \WHCM\Core\Bootstrap::getAssetsUrl(); ?>/js/jalalidatepicker.min.js"></script>
    <!-- مدال هدیه دادن اشتراک به کاربر -->
    <div id="giftModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.75); z-index:1000; align-items:center; justify-content:center; padding:1rem;">
        <div class="card" style="width:100%; max-width:480px; margin:0; position:relative; background:#1e293b; border:1px solid #334155;">
            <button onclick="closeGiftModal()" style="position:absolute; top:15px; left:15px; background:none; border:none; color:#94a3b8; font-size:1.2rem; cursor:pointer;">✖</button>
            <h3 style="color:#10b981; margin-bottom:1.25rem;">🎁 هدیه دادن اشتراک به کاربر</h3>
            <p style="color:#94a3b8; font-size:0.85rem; margin-bottom:1.5rem;">با انتخاب پلن زیر، اشتراک کاربر <strong id="giftUserName" style="color:white;"></strong> به صورت رایگان و فوری فعال/تمدید خواهد شد.</p>
            
            <form action="<?php echo \WHCM\Core\Bootstrap::getRouteUrl('/hnnh/grant-subscription-manual'); ?>" method="POST">
                <?php echo $csrf_field; ?>
                <input type="hidden" name="user_id" id="giftUserId">
                <div class="form-group" style="margin-bottom:1.5rem;">
                    <label for="giftPlanSelect" style="display:block; color:#cbd5e1; margin-bottom:0.5rem;">انتخاب پلن اشتراک هدیه:</label>
                    <select name="plan_id" id="giftPlanSelect" required style="width:100%; padding:0.75rem; border-radius:10px; background:#0f172a; color:white; border:1px solid #334155;">
                        <?php foreach ($plans as $pl): ?>
                            <option value="<?php echo $pl['id']; ?>"><?php echo htmlspecialchars($pl['title']) . ' (' . \WHCM\Domain\TextFormat::fa_digits($pl['duration_days']) . ' روزه)'; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-success" style="width:100%; padding:0.85rem; background:#10b981; color:white; font-weight:bold; border-radius:12px;">🎁 فعال‌سازی فوری اشتراک هدیه برای کاربر</button>
            </form>
        </div>
    </div>

    <!-- مدال پروفایل ۳۶۰ درجه و سوابق فعالیت کاربر -->
    <div id="userProfileModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.85); z-index:1100; align-items:center; justify-content:center; padding:1rem; overflow-y:auto;">
        <div class="card" style="width:100%; max-width:640px; margin:auto; position:relative; background:#0f172a; border:1px solid #4f46e5; border-radius:16px; box-shadow:0 20px 50px rgba(0,0,0,0.7);">
            <button onclick="closeUserProfileModal()" style="position:absolute; top:15px; left:15px; background:none; border:none; color:#94a3b8; font-size:1.4rem; cursor:pointer;">✖</button>
            
            <!-- هدر کارت پروفایل -->
            <div style="display:flex; align-items:center; gap:1rem; border-bottom:1px dashed #334155; padding-bottom:1.25rem; margin-bottom:1.25rem;">
                <div style="width:60px; height:60px; border-radius:50%; background:linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); display:flex; align-items:center; justify-content:center; font-size:1.8rem; color:white; font-weight:900;">
                    👤
                </div>
                <div>
                    <h3 id="up-name" style="color:white; margin:0; font-size:1.25rem; font-weight:900;"></h3>
                    <span id="up-email" style="color:#94a3b8; font-size:0.85rem;"></span>
                </div>
            </div>

            <!-- وضعیت اشتراک فعلی و اعتبار -->
            <div style="background:rgba(99, 102, 241, 0.1); border:1px solid rgba(99, 102, 241, 0.3); border-radius:12px; padding:1rem; margin-bottom:1.25rem;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.5rem;">
                    <span style="color:#a5b4fc; font-weight:bold; font-size:0.9rem;">💎 وضعیت اشتراک فعال:</span>
                    <span id="up-plan" class="badge badge-success" style="font-size:0.85rem;"></span>
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center; font-size:0.8rem; color:#cbd5e1;">
                    <span>تاریخ عضویت در سایت: <strong id="up-created" style="color:white;"></strong></span>
                    <span>اعتبار اشتراک تا: <strong id="up-end" style="color:#34d399;"></strong></span>
                </div>
            </div>

            <!-- مشخصات کسب و کار -->
            <div style="background:rgba(15, 23, 42, 0.7); border:1px solid #1e293b; border-radius:12px; padding:1rem; margin-bottom:1.25rem; display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                <div>
                    <span style="font-size:0.75rem; color:#94a3b8;">نام کسب و کار:</span>
                    <div id="up-biz-name" style="color:white; font-weight:bold; font-size:0.9rem;"></div>
                </div>
                <div>
                    <span style="font-size:0.75rem; color:#94a3b8;">حوزه فعالیت / صنف:</span>
                    <div id="up-biz-type" style="color:white; font-weight:bold; font-size:0.9rem;"></div>
                </div>
            </div>

            <!-- ۴ کارت آمار ۳۶۰ درجه عملکرد کاربر -->
            <h4 style="color:#a5b4fc; font-size:0.9rem; margin-bottom:0.75rem;">📊 آمار جامع و تفکیکی عملکرد ۳۶۰ درجه</h4>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem; margin-bottom:1.5rem;">
                <div style="background:#1e293b; border-radius:10px; padding:0.85rem; text-align:center; border:1px solid #334155;">
                    <div style="font-size:0.75rem; color:#94a3b8; margin-bottom:0.25rem;">📻 کانال‌های متصل شده</div>
                    <strong id="up-channels" style="color:#60a5fa; font-size:1.2rem;">۰</strong>
                </div>
                <div style="background:#1e293b; border-radius:10px; padding:0.85rem; text-align:center; border:1px solid #334155;">
                    <div style="font-size:0.75rem; color:#94a3b8; margin-bottom:0.25rem;">📝 پست‌های ارسالی</div>
                    <strong id="up-posts" style="color:#34d399; font-size:1.2rem;">۰</strong>
                </div>
                <div style="background:#1e293b; border-radius:10px; padding:0.85rem; text-align:center; border:1px solid #334155;">
                    <div style="font-size:0.75rem; color:#94a3b8; margin-bottom:0.25rem;">🎫 تیکت‌های پشتیبانی</div>
                    <strong id="up-tickets" style="color:#f59e0b; font-size:1.2rem;">۰</strong>
                </div>
                <div style="background:#1e293b; border-radius:10px; padding:0.85rem; text-align:center; border:1px solid #334155;">
                    <div style="font-size:0.75rem; color:#94a3b8; margin-bottom:0.25rem;">💳 کل واریزی‌های تایید شده</div>
                    <strong id="up-payments" style="color:#a855f7; font-size:1.2rem;">۰ تومان</strong>
                </div>
            </div>

            <!-- اقدامات سریع مدیریتی روی کاربر -->
            <div style="display:flex; justify-content:space-between; gap:0.75rem; border-top:1px dashed #334155; padding-top:1rem;">
                <button type="button" class="btn btn-success" style="flex:1; background:#10b981; border:none;" onclick="triggerGiftFromProfile()">🎁 هدیه اشتراک</button>
                <button type="button" class="btn" style="flex:1; background:rgba(255,255,255,0.08); color:white; border:1px solid #334155;" onclick="closeUserProfileModal()">بستن پنجره</button>
            </div>
        </div>
    </div>
    <!-- مدال گفتگو و مدیریت حرفه‌ای تیکت توسط ادمین ارشد -->
    <div id="adminTicketModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.85); z-index:1200; align-items:center; justify-content:center; padding:1rem; overflow-y:auto;">
        <div class="card" style="width:100%; max-width:620px; margin:auto; position:relative; background:#0f172a; border:1px solid #6366f1; border-radius:16px; box-shadow:0 20px 50px rgba(0,0,0,0.8);">
            <button onclick="closeAdminTicketModal()" style="position:absolute; top:15px; left:15px; background:none; border:none; color:#94a3b8; font-size:1.4rem; cursor:pointer;">✖</button>
            
            <div style="border-bottom:1px dashed #334155; padding-bottom:1rem; margin-bottom:1.25rem;">
                <span id="at-modal-status" class="badge" style="float:left; margin-top:2px;"></span>
                <h3 id="at-modal-subject" style="color:white; margin:0; font-size:1.15rem; font-weight:900;"></h3>
                <span id="at-modal-user" style="font-size:0.8rem; color:#94a3b8; display:block; margin-top:0.3rem;"></span>
            </div>

            <div id="at-modal-body" style="display:flex; flex-direction:column; gap:1rem; max-height:380px; overflow-y:auto; padding-right:0.5rem; margin-bottom:1.5rem;">
                <!-- گفتگوها در اینجا رندر می‌شوند -->
            </div>

            <!-- فرم ارسال پاسخ به تیکت — با پیوست، ارجاع و بستن همزمان -->
            <form action="<?php echo \WHCM\Core\Bootstrap::getRouteUrl('/hnnh/reply-ticket'); ?>" method="POST" enctype="multipart/form-data" style="margin-bottom:1rem;">
                <?php echo $csrf_field; ?>
                <input type="hidden" name="ticket_id" id="at-reply-id">
                <div class="form-group" style="margin-bottom:0.75rem;">
                    <textarea name="reply" rows="3" required placeholder="پاسخ کارشناس پشتیبانی را بنویسید..." style="width:100%; border-radius:10px; background:#1e293b; color:white; border:1px solid #334155; padding:0.75rem;"></textarea>
                </div>
                <div class="form-group">
                    <label style="font-size:0.8rem; color:#94a3b8;">پیوست تصویر (اختیاری):</label>
                    <input type="file" name="attachment" accept="image/*,.pdf" style="padding:0.4rem; font-size:0.8rem;">
                </div>
                <div class="form-group">
                    <label style="font-size:0.8rem; color:#94a3b8;">ارجاع به پشتیبان دیگر (اختیاری):</label>
                    <select name="assigned_to" style="width:100%; padding:0.5rem; border-radius:8px; background:#1e293b; color:white; border:1px solid #334155;">
                        <option value="0">— بدون ارجاع —</option>
                        <?php foreach ($users as $au): if(($au['role'] ?? '')==='superadmin' || ($au['role'] ?? '')==='support_agent'): ?>
                        <option value="<?php echo $au['id']; ?>"><?php echo htmlspecialchars($au['name']); ?> (<?php echo htmlspecialchars($au['email']); ?>)<?php echo ($au['role'] ?? '')==='support_agent' ? ' 🎧' : ' 👑'; ?></option>
                        <?php endif; endforeach; ?>
                    </select>
                </div>
                <label style="display:flex; align-items:center; gap:0.5rem; font-size:0.85rem; color:#fbbf24; margin-bottom:0.75rem; cursor:pointer;"><input type="checkbox" name="close_after_reply" value="1"> ارسال و بستن همزمان تیکت</label>
                <button type="submit" class="btn btn-success" style="width:100%; padding:0.75rem;">ارسال پاسخ پشتیبانی به کاربر ✔</button>
            </form>

            <!-- دکمه بستن جداگانه بدون پاسخ -->
            <form action="<?php echo \WHCM\Core\Bootstrap::getRouteUrl('/hnnh/close-ticket'); ?>" method="POST" style="margin:0;">
                <?php echo $csrf_field; ?>
                <input type="hidden" name="ticket_id" id="at-close-id">
                <button type="submit" class="btn btn-danger" style="width:100%; padding:0.6rem; font-size:0.85rem; background:rgba(239, 68, 68, 0.2); border:1px solid #ef4444; color:#ef4444;">بستن این تیکت بدون پاسخ</button>
            </form>
        </div>
    </div>

    <!-- مدال ایجاد تیکت جدید (ارسال پیام ادمین به کاربر) -->
    <div id="newTicketModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.85); z-index:1300; align-items:center; justify-content:center; padding:1rem; overflow-y:auto;">
        <div class="card" style="width:100%; max-width:540px; margin:auto; position:relative; background:#0f172a; border:1px solid #6366f1; border-radius:16px; box-shadow:0 20px 50px rgba(0,0,0,0.8);">
            <button onclick="document.getElementById('newTicketModal').style.display='none'" style="position:absolute; top:15px; left:15px; background:none; border:none; color:#94a3b8; font-size:1.4rem; cursor:pointer;">✖</button>
            <h3 style="color:#a5b4fc; margin-bottom:1.5rem;">✉️ ارسال پیام جدید به کاربر</h3>
            <form action="<?php echo \WHCM\Core\Bootstrap::getRouteUrl('/hnnh/create-ticket'); ?>" method="POST" enctype="multipart/form-data">
                <?php echo $csrf_field; ?>
                <div class="form-group">
                    <label>انتخاب کاربر مقصد <span style="color:#ef4444;">*</span></label>
                    <select name="target_user_id" required style="width:100%; padding:0.75rem; border-radius:10px; background:#1e293b; color:white; border:1px solid #334155;">
                        <option value="">— کاربر را انتخاب کنید —</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['name']); ?> (<?php echo htmlspecialchars($u['email']); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>موضوع پیام <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="subject" required placeholder="موضوع پیام یا تیکت..." style="width:100%; padding:0.75rem; border-radius:10px; background:#1e293b; color:white; border:1px solid #334155;">
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                    <div class="form-group">
                        <label>دسته‌بندی</label>
                        <select name="category" style="width:100%; padding:0.75rem; border-radius:10px; background:#1e293b; color:white; border:1px solid #334155;">
                            <option value="general">🔍 عمومی</option>
                            <option value="technical">💻 فنی</option>
                            <option value="billing">💳 مالی</option>
                            <option value="feature">🚀 پیشنهاد ویژگی</option>
                            <option value="bug">🐛 گزارش باگ</option>
                            <option value="other">📋 سایر</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>اولویت</label>
                        <select name="priority" style="width:100%; padding:0.75rem; border-radius:10px; background:#1e293b; color:white; border:1px solid #334155;">
                            <option value="low">🟢 پایین</option>
                            <option value="normal" selected>🟡 عادی</option>
                            <option value="high">🟠 بالا</option>
                            <option value="critical">🔴 بحرانی</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>متن پیام <span style="color:#ef4444;">*</span></label>
                    <textarea name="message" rows="5" required placeholder="متن پیام خود را بنویسید..." style="width:100%; padding:0.75rem; border-radius:10px; background:#1e293b; color:white; border:1px solid #334155; line-height:1.8;"></textarea>
                </div>
                <div class="form-group">
                    <label style="font-size:0.8rem; color:#94a3b8;">پیوست فایل (اختیاری):</label>
                    <input type="file" name="attachment" accept="image/*,.pdf" style="padding:0.4rem; font-size:0.8rem;">
                </div>
                <button type="submit" class="btn btn-success" style="width:100%; padding:0.85rem;">📤 ارسال پیام به کاربر</button>
            </form>
        </div>
    </div>


    <!-- مدیریت دسته‌بندی تیکت‌ها -->
    <?php if (!$is_support): ?>
    <script>
    var __ticketCategories = <?php echo json_encode($ticket_categories ?? [], JSON_UNESCAPED_UNICODE); ?>;
    var __supportAgents = <?php echo json_encode($support_agents ?? [], JSON_UNESCAPED_UNICODE); ?>;
    var __adminCsrf = '<?php echo \WHCM\Core\Csrf::getToken(); ?>';

    function renderCategoryRows() {
        var area = document.getElementById('cat-editor-area');
        if (!area) return;
        area.innerHTML = '';
        var rows = area.querySelectorAll('.cat-edit-row');
        // If no rows exist yet, create from saved data
        if (rows.length === 0 && __ticketCategories.length > 0) {
            for (var i = 0; i < __ticketCategories.length; i++) {
                createCatRow(__ticketCategories[i].slug, __ticketCategories[i].title, __ticketCategories[i].icon, __ticketCategories[i].assigned_agent_id || '');
            }
        }
    }

    function createCatRow(slug, title, icon, agentId) {
        var area = document.getElementById('cat-editor-area');
        var row = document.createElement('div');
        row.className = 'cat-edit-row';
        row.style.cssText = 'display:grid; grid-template-columns:1fr 2fr auto auto 40px; gap:0.5rem; align-items:center; margin-bottom:0.5rem;';

        var agentOptions = '<option value="">بدون تخصیص</option>';
        for (var a = 0; a < __supportAgents.length; a++) {
            var sel = (__supportAgents[a].id == agentId) ? ' selected' : '';
            agentOptions += '<option value="' + __supportAgents[a].id + '"' + sel + '>' + __supportAgents[a].name + '</option>';
        }

        row.innerHTML =
            '<input type="text" placeholder="slug" value="' + (slug || '') + '" style="width:100%;background:#0f172a;color:#94a3b8;border:1px solid #334155;border-radius:8px;padding:0.5rem;font-size:0.8rem;direction:ltr;" class="cat-slug">' +
            '<input type="text" placeholder="عنوان" value="' + (title || '') + '" style="width:100%;background:#0f172a;color:white;border:1px solid #334155;border-radius:8px;padding:0.5rem;" class="cat-title">' +
            '<input type="text" placeholder="ایموجی" value="' + (icon || '🌐') + '" style="width:50px;text-align:center;background:#0f172a;color:white;border:1px solid #334155;border-radius:8px;padding:0.5rem;font-size:1.1rem;" class="cat-icon">' +
            '<select style="background:#0f172a;color:white;border:1px solid #334155;border-radius:8px;padding:0.5rem;font-size:0.8rem;" class="cat-agent">' + agentOptions + '</select>' +
            '<button type="button" onclick="this.closest(\'.cat-edit-row\').remove()" style="background:rgba(239,68,68,0.15);color:#f87171;border:1px solid rgba(239,68,68,0.3);border-radius:8px;padding:0.5rem 0.6rem;cursor:pointer;font-size:0.9rem;">✖</button>';
        area.appendChild(row);
    }

    function addCategoryRow() {
        createCatRow('', '', '🌐', '');
    }

    function saveCategories() {
        var rows = document.querySelectorAll('.cat-edit-row');
        var cats = [];
        for (var i = 0; i < rows.length; i++) {
            var slug = rows[i].querySelector('.cat-slug').value.trim();
            var title = rows[i].querySelector('.cat-title').value.trim();
            var icon = rows[i].querySelector('.cat-icon').value.trim() || '🌐';
            var agent = rows[i].querySelector('.cat-agent').value;
            if (slug && title) {
                cats.push({slug: slug, title: title, icon: icon, assigned_agent: agent});
            }
        }
        var formData = 'csrf_token=' + encodeURIComponent(__adminCsrf) + '&categories=' + encodeURIComponent(JSON.stringify(cats));
        var xhr = new XMLHttpRequest();
        var baseUrl = window.location.pathname;
        xhr.open('POST', baseUrl + '?route=' + encodeURIComponent('/hnnh/save-ticket-categories'), true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                try {
                    var res = JSON.parse(xhr.responseText);
                    if (res.success) {
                        alert(res.message);
                        __ticketCategories = cats;
                    } else {
                        alert(res.message || 'خطا در ذخیره‌سازی');
                    }
                } catch(e) {
                    alert('خطای سیستمی');
                }
            }
        };
        xhr.send(formData);
    }

    // مقداردهی اولیه
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', renderCategoryRows);
    } else {
        renderCategoryRows();
    }
    </script>
    <?php endif; ?>

    <!-- پشتیبان: فقط بخش تیکت -->
    <?php if ($is_support): ?>
    <script>
    (function(){
        var sections = document.querySelectorAll('.tab-content');
        for (var i = 0; i < sections.length; i++) {
            if (sections[i].id !== 'section-tickets') {
                sections[i].style.display = 'none';
            }
        }
        var items = document.querySelectorAll('.sidebar-desktop .menu-item');
        for (var j = 0; j < items.length; j++) {
            var target = items[j].getAttribute('data-target');
            if (target && target !== 'tickets') {
                items[j].style.display = 'none';
            }
        }
    }());
    </script>
    <?php endif; ?>

    <!-- PWA: ثبت سرویس ورکر و بنر نصب (فقط موبایل/تبلت) -->
    <script>
    (function(){
        var baseUrl = '<?php echo $baseUrl; ?>';
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register(baseUrl + '/service-worker.js', { scope: baseUrl + '/' })
                .catch(function() {});
        }
    }());
    </script>
    <script src="<?php echo \WHCM\Core\Bootstrap::getAssetsUrl(); ?>/js/pwa-install.js"></script>
</body>
</html>