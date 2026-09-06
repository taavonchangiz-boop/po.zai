<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title><?php echo htmlspecialchars($title); ?> | پُست‌یار</title>

    <?php $baseUrl = rtrim(str_replace(['/assets', '/public/assets'], '', \WHCM\Core\Bootstrap::getAssetsUrl()), '/'); ?>

    <!-- PWA Meta Tags -->
    <link rel="manifest" href="<?php echo $baseUrl; ?>/manifest.php">
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

    <link rel="stylesheet" href="<?php echo \WHCM\Core\Bootstrap::getAssetsUrl(); ?>/css/dashboard.css?v=13">
    <link rel="stylesheet" href="<?php echo \WHCM\Core\Bootstrap::getAssetsUrl(); ?>/css/components.css?v=13">
    <link rel="stylesheet" href="<?php echo \WHCM\Core\Bootstrap::getAssetsUrl(); ?>/css/jalalidatepicker.min.css?v=13">
</head>
<body>

    <!-- توستر کپی کارت بانکی -->
    <div id="copy-toast" class="toast">شماره کارت با موفقیت کپی شد! 📋</div>

    <!-- هدر بالای صفحه با لوگوی اختصاصی پُست‌یار -->
    <header>
        <div class="logo-container">
            <div class="logo-frame">
                <div class="logo-frame-inner">
                    <img src="<?php echo \WHCM\Core\Bootstrap::getAssetsUrl(); ?>/images/logo-white-bg.webp" alt="پُست‌یار" class="logo-img">
                </div>
            </div>
            <span class="logo-text">پُست‌یار</span>
        </div>
        <div style="display:flex; align-items:center; gap:0.75rem;">
            <!-- دکمه زنگوله اعلان‌های کاربر -->
            <div style="position:relative;" id="bell-wrapper">
                <button type="button" id="bell-btn" onclick="toggleBellPopup()" style="background:rgba(15,23,42,0.85); border:1px solid rgba(99,102,241,0.4); border-radius:50%; width:2.5rem; height:2.5rem; display:flex; align-items:center; justify-content:center; color:white; font-size:1.15rem; cursor:pointer; box-shadow:0 4px 12px rgba(0,0,0,0.4);">
                    <span>🔔</span>
                    <span id="bell-badge" style="position:absolute; top:0.125rem; right:0.125rem; min-width:1.1rem; height:1.1rem; background:#ef4444; border-radius:9999px; border:2px solid #0f172a; color:#fff; font-size:0.6rem; font-weight:900; align-items:center; justify-content:center; padding:0 0.2rem; <?php echo ($unread_count > 0) ? 'display:flex;' : 'display:none;'; ?>"><?php echo $unread_count > 0 ? \WHCM\Domain\TextFormat::fa_digits($unread_count) : ''; ?></span>
                </button>
                <div id="user-bell-popup" style="display:none; position:absolute; left:0; top:3.75rem; width:22rem; max-height:28rem; overflow:hidden; background:#0f172a; border:1px solid #4f46e5; border-radius:1rem; box-shadow:0 15px 35px rgba(0,0,0,0.85); z-index:9999; flex-direction:column;">
                    <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px dashed #334155; padding:0.75rem 1rem;">
                        <strong style="color:white; font-size:0.9rem;">🔔 اعلان‌ها</strong>
                        <?php if ($unread_count > 0): ?>
                        <button type="button" onclick="markAllNotificationsRead(this)" style="background:none; border:1px solid #4f46e5; color:#818cf8; font-size:0.7rem; padding:0.2rem 0.6rem; border-radius:0.5rem; cursor:pointer;">خواندن همه ✔</button>
                        <?php endif; ?>
                    </div>
                    <div id="bell-notifications-list" style="overflow-y:auto; max-height:22rem; padding:0.5rem;">
                        <?php if (!empty($user_notifications)): ?>
                            <?php foreach ($user_notifications as $notif): ?>
                                <div id="notif-item-<?php echo $notif['id']; ?>" class="notif-list-item" data-notif-id="<?php echo $notif['id']; ?>" data-target="<?php echo htmlspecialchars($notif['target_section'] ?? ''); ?>" style="padding:0.65rem 0.75rem; margin-bottom:0.35rem; background:<?php echo ($notif['is_read'] == 0) ? 'rgba(99, 102, 241, 0.12)' : 'transparent'; ?>; border-radius:0.625rem; border-right:3px solid <?php echo ($notif['is_read'] == 0) ? '#6366f1' : 'transparent'; ?>; cursor:pointer; transition:background 0.2s;" onclick="openNotification(<?php echo $notif['id']; ?>, '<?php echo htmlspecialchars(addslashes($notif['target_section'] ?? '')); ?>')">
                                    <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:0.5rem;">
                                        <div style="font-weight:<?php echo ($notif['is_read'] == 0) ? '800' : '400'; ?>; color:<?php echo ($notif['is_read'] == 0) ? '#e2e8f0' : '#94a3b8'; ?>; font-size:0.8rem; line-height:1.5;"><?php echo htmlspecialchars($notif['title']); ?></div>
                                        <?php if ($notif['is_read'] == 0): ?>
                                        <span style="width:0.45rem; height:0.45rem; background:#6366f1; border-radius:50%; flex-shrink:0; margin-top:0.35rem;"></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($notif['message'])): ?>
                                    <div style="font-size:0.72rem; color:#64748b; line-height:1.5; margin-top:0.2rem; overflow:hidden; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical;"><?php echo htmlspecialchars(mb_substr($notif['message'], 0, 100)); ?></div>
                                    <?php endif; ?>
                                    <div style="font-size:0.65rem; color:#475569; margin-top:0.3rem;"><?php echo \WHCM\Domain\TextFormat::timeAgo($notif['created_at']); ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="color:#94a3b8; font-size:0.8rem; text-align:center; padding:1.5rem 0;">اعلان جدیدی وجود ندارد ✔</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- نشان جذاب و چندرنگ کاربر و نوع اشتراک -->
            <div class="user-badge" style="display:flex; align-items:center; gap:0.6rem; background:rgba(15, 23, 42, 0.9); border:1px solid rgba(99, 102, 241, 0.45); padding:0.35rem 1rem; border-radius:9999px; box-shadow:0 4px 15px rgba(0,0,0,0.5);">
                <span style="display:flex; align-items:center; justify-content:center; width:1.75rem; height:1.75rem; border-radius:50%; background:linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); color:#ffffff; font-size:0.9rem; font-weight:bold; flex-shrink:0;">
                    👤
                </span>
                <span class="header-username" style="color:#f8fafc; font-weight:850; font-size:0.92rem; white-space:nowrap;">
                    <?php echo htmlspecialchars($user['name']); ?>
                </span>
                <span class="header-plan-badge" style="background:linear-gradient(135deg, #10b981 0%, #059669 100%); color:#ffffff; font-size:0.78rem; font-weight:900; padding:0.2rem 0.75rem; border-radius:0.75rem; box-shadow:0 2px 8px rgba(16, 185, 129, 0.35); white-space:nowrap;">
                    💎 <?php echo \WHCM\Domain\TextFormat::fa_digits($quota['plan_title']); ?>
                </span>
            </div>
        </div>
    </header>

    <!-- کانتینر اصلی محتوا -->
    <div class="wrapper">
        
        <!-- سایدبار دسکتاپ -->
        <aside>
            <div class="menu-item active" data-target="dashboard" onclick="switchSection('dashboard')">🏠 وضعیت کلی</div>
            <div class="menu-item" data-target="publish" onclick="switchSection('publish')">✉ ارسال پست جدید</div>
            <div class="menu-item" data-target="channels" onclick="switchSection('channels')">📻 مدیریت کانال‌ها</div>
            <?php if (!empty($quota['features']['gold_ticker'])): ?>
                <div class="menu-item" data-target="ticker" onclick="switchSection('ticker')">🪙 ربات طلا و سکه</div>
            <?php endif; ?>
            <?php if (!empty($quota['features']['auto_responder'])): ?>
                <div class="menu-item" data-target="responder" onclick="switchSection('responder')">🤖 پاسخگوی خودکار</div>
            <?php endif; ?>
            <div class="menu-item" data-target="tickets" onclick="switchSection('tickets')">🎫 پشتیبانی و تیکت‌ها</div>
            <div class="menu-item" data-target="inbox" onclick="switchSection('inbox')">📩 صندوق پیام</div>
            <div class="menu-item" data-target="settings" onclick="switchSection('settings')">👤 تنظیمات حساب</div>
            <div class="menu-item" data-target="advanced-settings" onclick="switchSection('advanced-settings')">⚙ تنظیمات پیشرفته</div>
            <div class="menu-item" data-target="upgrade" onclick="switchSection('upgrade')">💎 خرید اشتراک</div>
            <div class="menu-item" data-target="referral" onclick="switchSection('referral')">🎯 زیرمجموعه‌گیری</div>
            <div class="menu-item" data-target="wallet" onclick="switchSection('wallet')">💰 کیف پول</div>
            <a href="<?php echo \WHCM\Core\Bootstrap::getRouteUrl('/help'); ?>" class="menu-item">📖 آموزش استفاده</a>
            <?php if (\WHCM\Core\Auth::isSuperAdmin()): ?>
                <a href="<?php echo \WHCM\Core\Bootstrap::getRouteUrl('/hnnh'); ?>" class="menu-item" style="color: var(--warning); border: 1px dashed var(--warning); margin-top: 1rem;">👑 پنل مدیریت کل</a>
            <?php endif; ?>
            <a href="<?php echo \WHCM\Core\Bootstrap::getRouteUrl('/logout'); ?>" class="menu-item logout-btn">🚪 خروج از حساب</a>
        </aside>

        <!-- منوی تب موبایل (Native-like) -->
        <div class="mobile-nav">
            <div class="mobile-nav-item active" data-target="dashboard">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                <span>داشبورد</span>
            </div>
            <div class="mobile-nav-item" data-target="publish">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                <span>پست جدید</span>
            </div>
            <div class="mobile-nav-item" data-target="channels">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 100-6 3 3 0 000 6z"></path></svg>
                <span>کانال‌ها</span>
            </div>
            <div class="mobile-nav-item" data-target="tickets">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path></svg>
                <span>تیکت‌ها</span>
            </div>
            <div class="mobile-nav-item" onclick="toggleMobileMoreMenu()">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                <span>بیشتر</span>
            </div>
        </div>

        <!-- دراور منوی بیشتر (موبایل) -->
        <div class="mobile-more-overlay" id="mobileMoreOverlay" onclick="toggleMobileMoreMenu()"></div>
        <div class="mobile-more-drawer" id="mobileMoreDrawer">
            <div class="mobile-more-handle"></div>
            <div class="mobile-more-title">منوی کامل</div>
            <div class="mobile-more-grid">
                <?php if (!empty($quota['features']['gold_ticker'])): ?>
                <div class="mobile-more-item" data-target="ticker" onclick="switchSection('ticker'); toggleMobileMoreMenu();">
                    <span class="mobile-more-icon">🪙</span>
                    <span>ربات طلا و سکه</span>
                </div>
                <?php endif; ?>
                <?php if (!empty($quota['features']['auto_responder'])): ?>
                <div class="mobile-more-item" data-target="responder" onclick="switchSection('responder'); toggleMobileMoreMenu();">
                    <span class="mobile-more-icon">🤖</span>
                    <span>پاسخگوی خودکار</span>
                </div>
                <?php endif; ?>
                <div class="mobile-more-item" data-target="inbox" onclick="switchSection('inbox'); toggleMobileMoreMenu();">
                    <span class="mobile-more-icon">📩</span>
                    <span>صندوق پیام</span>
                </div>
                <div class="mobile-more-item" data-target="settings" onclick="switchSection('settings'); toggleMobileMoreMenu();">
                    <span class="mobile-more-icon">👤</span>
                    <span>تنظیمات حساب</span>
                </div>
                <div class="mobile-more-item" data-target="advanced-settings" onclick="switchSection('advanced-settings'); toggleMobileMoreMenu();">
                    <span class="mobile-more-icon">⚙</span>
                    <span>تنظیمات پیشرفته</span>
                </div>
                <div class="mobile-more-item" data-target="upgrade" onclick="switchSection('upgrade'); toggleMobileMoreMenu();">
                    <span class="mobile-more-icon">💎</span>
                    <span>خرید اشتراک</span>
                </div>
                <div class="mobile-more-item" data-target="referral" onclick="switchSection('referral'); toggleMobileMoreMenu();">
                    <span class="mobile-more-icon">🎯</span>
                    <span>زیرمجموعه‌گیری</span>
                </div>
                <div class="mobile-more-item" data-target="wallet" onclick="switchSection('wallet'); toggleMobileMoreMenu();">
                    <span class="mobile-more-icon">💰</span>
                    <span>کیف پول</span>
                </div>
                <a href="<?php echo \WHCM\Core\Bootstrap::getRouteUrl('/help'); ?>" class="mobile-more-item">
                    <span class="mobile-more-icon">📖</span>
                    <span>آموزش استفاده</span>
                </a>
                <?php if (\WHCM\Core\Auth::isSuperAdmin()): ?>
                <a href="<?php echo \WHCM\Core\Bootstrap::getRouteUrl('/hnnh'); ?>" class="mobile-more-item mobile-more-admin">
                    <span class="mobile-more-icon">👑</span>
                    <span>پنل مدیریت کل</span>
                </a>
                <?php endif; ?>
                <a href="<?php echo \WHCM\Core\Bootstrap::getRouteUrl('/logout'); ?>" class="mobile-more-item mobile-more-logout">
                    <span class="mobile-more-icon">🚪</span>
                    <span>خروج از حساب</span>
                </a>
            </div>
        </div>

        <!-- محتوای صفحات -->
        <main>
            <?php if (!empty($message)): ?>
                <div class="alert" id="system-alert-toast" style="position:relative; display:flex; justify-content:space-between; align-items:center;">
                    <span><?php echo htmlspecialchars($message); ?></span>
                    <button type="button" onclick="document.getElementById('system-alert-toast').style.display='none'" style="background:none; border:none; color:white; font-size:1.1rem; cursor:pointer; margin-right:1rem;">✖</button>
                </div>
                <script>autoDismissAlert('system-alert-toast', 5000);</script>
            <?php endif; ?>

            <!-- نمایش اعلان همگانی مدیر کل پلتفرم — فقط اگر کاربر هنوز نخوانده -->
            <?php if (!empty($announcement) && !empty($announcement_unread)): ?>
                <div class="broadcast-alert" id="broadcast-alert-banner">
                    <span class="broadcast-alert-close" onclick="closeBroadcastBanner()">✖</span>
                    <h4 style="font-weight:900; margin-bottom:0.4rem; color:#ffffff;">📢 پیام همگانی مدیریت: <?php echo htmlspecialchars($announcement['title']); ?></h4>
                    <p style="font-size:0.85rem; line-height:1.7; color:#cbd5e1;"><?php echo nl2br(htmlspecialchars($announcement['message'])); ?></p>
                    <span style="font-size:0.75rem; color:#818cf8; display:inline-block; margin-top:0.5rem; font-weight:bold;">ثبت شده در تاریخ: <?php echo $announcement['date']; ?></span>
                </div>
            <?php endif; ?>

            <!-- ========================================== -->
            <!-- ۱. بخش وضعیت کلی و گراف آماری لوکس -->
            <!-- ========================================== -->
            <div id="section-dashboard" class="tab-content active">
                
                <!-- سه باکس آماری درخشان و هدفمند -->
                <div class="grid-stats" style="margin-bottom:0.5rem;">
                    <div class="card-stat" style="border-color: rgba(99,102,241,0.25);">
                        <div class="card-stat-icon" style="color:#6366f1;">📈</div>
                        <div class="card-stat-info">
                            <span class="title">کل بازدیدهای ورودی (کلیک کل)</span>
                            <?php 
                                $total_clicks = 0;
                                foreach ($posts as $pst) { $total_clicks += (int)$pst['clicks']; }
                            ?>
                            <span class="value"><?php echo \WHCM\Domain\TextFormat::fa_digits($total_clicks); ?> کلیک</span>
                        </div>
                    </div>
                    <div class="card-stat" style="border-color: rgba(16,185,129,0.25);">
                        <div class="card-stat-icon" style="color:#10b981;">👥</div>
                        <div class="card-stat-info">
                            <span class="title">بازدیدهای یکتای حقیقی (Unique)</span>
                            <?php 
                                $unique_clicks = 0;
                                foreach ($posts as $pst) { $unique_clicks += (int)$pst['unique_clicks']; }
                            ?>
                            <span class="value"><?php echo \WHCM\Domain\TextFormat::fa_digits($unique_clicks); ?> کاربر</span>
                        </div>
                    </div>
                    <div class="card-stat" style="border-color: rgba(245,158,11,0.25);">
                        <div class="card-stat-icon" style="color:#f59e0b;">⚡</div>
                        <div class="card-stat-info">
                            <span class="title">نرخ تعامل کانال‌های شما</span>
                            <?php 
                                $ratio = $total_clicks > 0 ? round(($unique_clicks / $total_clicks) * 100) : 0;
                            ?>
                            <span class="value">%<?php echo \WHCM\Domain\TextFormat::fa_digits($ratio); ?> تعامل</span>
                        </div>
                    </div>
                </div>

                <div class="grid-stats">
                    <div class="card-stat">
                        <div class="card-stat-icon">📻</div>
                        <div class="card-stat-info">
                            <span class="title">کانال‌های متصل شده</span>
                            <span class="value"><?php echo \WHCM\Domain\TextFormat::fa_digits($quota['used_channels']); ?> / <?php echo \WHCM\Domain\TextFormat::fa_digits($quota['max_channels']); ?></span>
                        </div>
                    </div>
                    <div class="card-stat">
                        <div class="card-stat-icon">📝</div>
                        <div class="card-stat-info">
                            <span class="title">پست‌های ارسالی این دوره</span>
                            <span class="value"><?php echo \WHCM\Domain\TextFormat::fa_digits($quota['used_posts']); ?> / <?php echo $quota['max_posts'] === 0 ? 'نامحدود' : \WHCM\Domain\TextFormat::fa_digits($quota['max_posts']); ?></span>
                        </div>
                    </div>
                    <div class="card-stat">
                        <div class="card-stat-icon">⏰</div>
                        <div class="card-stat-info">
                            <span class="title">تاریخ اتمام اشتراک</span>
                            <span class="value" style="font-size: 1.05rem; font-weight: bold;">
                                <?php echo $quota['end_date'] ? \WHCM\Domain\TextFormat::mysql_to_jalali($quota['end_date'], false) : 'بدون انقضا'; ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- گراف آماری جامع و درخشان SVG بومی -->
                <div class="card">
                    <h2>📊 نمودار تحلیل مقایسه‌ای پیشرفته کانال‌ها</h2>
                    <div class="analytics-graph">
                        <svg viewBox="0 0 500 200" style="width: 100%; height: 100%;">
                            <!-- گرید لاین‌ها -->
                            <line x1="50" y1="30" x2="480" y2="30" stroke="rgba(255,255,255,0.05)" />
                            <line x1="50" y1="80" x2="480" y2="80" stroke="rgba(255,255,255,0.05)" />
                            <line x1="50" y1="130" x2="480" y2="130" stroke="rgba(255,255,255,0.05)" />
                            <line x1="50" y1="170" x2="480" y2="170" stroke="rgba(255,255,255,0.1)" stroke-width="2" />

                            <!-- راهنما -->
                            <text x="55" y="20" fill="rgba(99, 102, 241, 0.8)" font-size="9" font-family="Vazirmatn">● میزان کلیک‌ها (تک کلیک)</text>
                            <text x="180" y="20" fill="rgba(16, 185, 129, 0.8)" font-size="9" font-family="Vazirmatn">● میزان کلیک‌های یکتا (Unique)</text>

                            <!-- نمودار خطی کلیک کل (آبی نئون) -->
                            <path d="M 50 160 Q 120 120 190 70 T 330 110 T 470 40" fill="none" stroke="#6366f1" stroke-width="3" stroke-linecap="round" />
                            <circle cx="470" cy="40" r="5" fill="#6366f1" />

                            <!-- نمودار خطی کلیک یکتا (سبز نئون) -->
                            <path d="M 50 170 Q 120 140 190 90 T 330 130 T 470 60" fill="none" stroke="#10b981" stroke-width="3" stroke-linecap="round" />
                            <circle cx="470" cy="60" r="5" fill="#10b981" />

                            <!-- محور افقی روزهای شمسی دوره -->
                            <text x="50" y="190" fill="var(--text-muted)" font-size="8" font-family="Vazirmatn">امروز شمسی</text>
                            <text x="250" y="190" fill="var(--text-muted)" font-size="8" font-family="Vazirmatn">میانه دوره</text>
                            <text x="440" y="190" fill="var(--text-muted)" font-size="8" font-family="Vazirmatn">شروع دوره</text>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- ۲. بخش ارسال پست جدید (Publish) -->
            <!-- ========================================== -->
            <div id="section-publish" class="tab-content">
                <div class="card">
                    <h2>✉ ایجاد و انتشار پست جدید در کانال‌ها</h2>
                    <form action="<?php echo \WHCM\Core\Bootstrap::getRouteUrl('/dashboard/add-post'); ?>" method="POST" enctype="multipart/form-data">
                        <?php echo $csrf_field; ?>
                        
                        <div class="form-group">
                            <label for="p-title">عنوان پست:</label>
                            <input type="text" name="title" id="p-title" required placeholder="مثلاً: رونمایی از کلکسیون طلای جدید آسوین">
                        </div>

                        <div class="form-group" style="position:relative;">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.5rem;">
                                <label for="p-content" style="margin:0;">محتوای پست (متن پیام):</label>
                                <!-- پک استیکر و اموجی تلگرامی پاپ‌آپ بومی پُست‌یار -->
                                <div class="emoji-picker-container">
                                    <button type="button" class="emoji-picker-btn" onclick="toggleEmojiPicker()">😀 افزودن استیکر و اموجی پُست‌یار</button>
                                    <div class="emoji-popup" id="emoji-popup">
                                        <div class="emoji-tabs">
                                            <span class="emoji-tab active" onclick="switchEmojiTab('face')">😀</span>
                                            <span class="emoji-tab" onclick="switchEmojiTab('objects')">💰</span>
                                            <span class="emoji-tab" onclick="switchEmojiTab('arrows')">🔺</span>
                                        </div>
                                        <div class="emoji-grid" id="emoji-grid-face">
                                            <?php 
                                                $smileys = ['😀','😃','😄','😁','😆','😅','😂','🤣','👍','🔥','❤️','🎉','✨','✅','❌','⏳'];
                                                foreach ($smileys as $em) {
                                                    echo "<span class='emoji-item' onclick='insertEmoji(\"{$em}\")'>{$em}</span>";
                                                }
                                            ?>
                                        </div>
                                        <div class="emoji-grid hidden" id="emoji-grid-objects">
                                            <?php 
                                                $objects = ['🌟','🪙','💰','📈','📉','⏰','💎','📻','✉','⚙','🚀','💬','👑','💳','🛒','🛍','📦'];
                                                foreach ($objects as $em) {
                                                    echo "<span class='emoji-item' onclick='insertEmoji(\"{$em}\")'>{$em}</span>";
                                                }
                                            ?>
                                        </div>
                                        <div class="emoji-grid hidden" id="emoji-grid-arrows">
                                            <?php 
                                                $arrows = ['🔺','🔻','🔸','🔹','◽','◾','🔗','⚡','✈','🔽','🔼'];
                                                foreach ($arrows as $em) {
                                                    echo "<span class='emoji-item' onclick='insertEmoji(\"{$em}\")'>{$em}</span>";
                                                }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <textarea name="content" id="p-content" rows="6" required placeholder="متن پیام خود را بنویسید..."></textarea>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="p-media">بارگذاری و آپلود تصویر شاخص (فرمت بهینه وب‌پی خودکار):</label>
                                <input type="file" name="media_file" id="p-media" accept="image/*" style="padding:0.5rem 1rem;">
                            </div>
                            <div class="form-group">
                                <label for="p-send-type">نوع انتشار:</label>
                                <select name="send_type" id="p-send-type" onchange="toggleScheduleInput(this.value)">
                                    <option value="instant">ارسال آنی و سریع ⚡</option>
                                    <option value="scheduled">زمان‌بندی ارسال خودکار ⏰</option>
                                </select>
                            </div>
                        </div>

                        <!-- زمان‌بندی ارسال شمسی با تقویم تصویری فوق جذاب و مدرن -->
                        <div class="form-group hidden" id="schedule-datetime-group">
                            <label style="color:#a5b4fc; font-weight:bold; display:block; margin-bottom:0.75rem;">📅 انتخاب تاریخ و ساعت دقیق ارسال:</label>
                            <div style="display:grid; grid-template-columns: 2fr 1fr 1fr; gap:1rem;">
                                <div>
                                    <label style="font-size:0.75rem; color:var(--text-muted);">انتخاب روز از تقویم:</label>
                                    <input type="text" name="sched_date" id="sched_date_input" data-jdp placeholder="کلیک کنید تا تقویم باز شود..." style="background-color: rgba(15,23,42,0.6); color: #34d399; font-weight: bold; border: 2px solid #34d399; border-radius:0.75rem; padding:0.85rem 1rem; cursor: pointer;" readonly>
                                </div>
                                <div>
                                    <label style="font-size:0.75rem; color:var(--text-muted);">ساعت:</label>
                                    <select name="sched_hour" id="sched_hour" style="border-radius:0.75rem;">
                                        <?php for($h=0; $h<=23; $h++): ?>
                                            <option value="<?php echo str_pad($h,2,'0',STR_PAD_LEFT); ?>"><?php echo \WHCM\Domain\TextFormat::fa_digits(str_pad($h,2,'0',STR_PAD_LEFT)); ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div>
                                    <label style="font-size:0.75rem; color:var(--text-muted);">دقیقه:</label>
                                    <select name="sched_minute" id="sched_minute" style="border-radius:0.75rem;">
                                        <?php for($i=0; $i<=59; $i++): ?>
                                            <option value="<?php echo str_pad($i,2,'0',STR_PAD_LEFT); ?>"><?php echo \WHCM\Domain\TextFormat::fa_digits(str_pad($i,2,'0',STR_PAD_LEFT)); ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- چک‌باکس کانال‌های مقصد -->
                        <div class="form-group">
                            <label style="margin-bottom:0.75rem;">انتخاب کانال‌های هدف جهت انتشار پست:</label>
                            <div style="display: flex; flex-wrap: wrap; gap: 1.5rem; background: rgba(15,23,42,0.4); padding: 1rem; border-radius: 12px; border:1px solid var(--border);">
                                <?php if (empty($channels)): ?>
                                    <span style="color:var(--text-muted); font-size:0.85rem;">هنوز کانالی متصل نکرده‌اید. ابتدا از تب کانال‌ها یک کانال ثبت کنید.</span>
                                <?php else: ?>
                                    <?php foreach ($channels as $ch): ?>
                                        <label class="toggle-container" style="background: none; border: none; padding: 0;">
                                            <input type="checkbox" name="post_channels[]" value="<?php echo $ch['id']; ?>" class="toggle-input" checked>
                                            <span><?php echo htmlspecialchars($ch['name']); ?> (<?php echo $ch['platform'] === 'telegram' ? 'تلگرام' : 'بله'; ?>)</span>
                                        </label>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <button type="submit" class="btn">انتشار و زمان‌بندی پست در پُست‌یار 🚀</button>
                    </form>
                </div>

                <!-- صف انتظار و پست‌های زمان‌بندی‌شده -->
                <?php 
                $queued_posts = array_filter($posts, function($p) { return $p['status'] === 'scheduled' || $p['status'] === 'queued' || $p['status'] === 'draft'; });
                ?>
                <?php if (!empty($queued_posts)): ?>
                <div class="card" style="border-color: #f59e0b; background: linear-gradient(135deg, rgba(245, 158, 11, 0.08) 0%, rgba(15, 23, 42, 0.9) 100%);">
                    <h2>⏳ صف انتظار و پست‌های زمان‌بندی‌شده <span style="font-size:0.8rem; color:var(--text-muted);">(<?php echo \WHCM\Domain\TextFormat::fa_digits(count($queued_posts)); ?> پست)</span></h2>
                    <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 1rem;">پست‌هایی که هنوز ارسال نشده‌اند. می‌توانید آنها را لغو کنید:</p>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>عنوان پست</th>
                                    <th>وضعیت</th>
                                    <th>زمان ارسال / زمان‌بندی</th>
                                    <th>عملیات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($queued_posts as $qp): ?>
                                <tr id="queue-row-<?php echo $qp['id']; ?>">
                                    <td data-label="عنوان پست"><strong><?php echo htmlspecialchars($qp['title']); ?></strong></td>
                                    <td data-label="وضعیت">
                                        <?php if ($qp['status'] === 'queued'): ?>
                                            <span class="badge badge-pending">در صف ارسال ⏳</span>
                                        <?php elseif ($qp['status'] === 'scheduled'): ?>
                                            <span class="badge badge-scheduled">زمان‌بندی‌شده 📅</span>
                                        <?php else: ?>
                                            <span class="badge" style="background:rgba(255,255,255,0.05);">پیش‌نویس 📝</span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="زمان">
                                        <?php if ($qp['scheduled_at']): ?>
                                            <span style="font-size:0.8rem;"><?php echo \WHCM\Domain\TextFormat::mysql_to_jalali($qp['scheduled_at'], false); ?></span>
                                        <?php else: ?>
                                            <span style="font-size:0.8rem; color:var(--text-muted);">آنی — در صف انتظار</span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="عملیات">
                                        <button type="button" class="btn btn-danger" style="padding:0.4rem 0.8rem; font-size:0.78rem; background:rgba(239, 68, 68, 0.15); border:1px solid #ef4444; color:#ef4444;" onclick="cancelPost(<?php echo $qp['id']; ?>, this)">🗑 لغو و حذف</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- تاریخچه پست‌های ارسالی -->
                <div class="card">
                    <h2>📋 تاریخچه پست‌های ارسالی و زمان‌بندی شده شما</h2>
                    <?php if (empty($posts)): ?>
                        <p style="color: var(--text-muted); text-align: center; padding: 2rem 0;">هنوز پستی ارسال یا ثبت نکرده‌اید.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>عنوان پست</th>
                                        <th>نوع ارسال</th>
                                        <th>وضعیت</th>
                                        <th>زمان ثبت / ارسال</th>
                                        <th>بازدید کل (کلیک)</th>
                                        <th>کلیک‌های یکتا</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($posts as $p): ?>
                                        <tr>
                                            <td data-label="عنوان پست"><strong><?php echo htmlspecialchars($p['title']); ?></strong></td>
                                            <td data-label="نوع ارسال">
                                                <span class="badge" style="background:rgba(255,255,255,0.05);">
                                                    <?php echo $p['scheduled_at'] ? 'زمان‌بندی شده ⏰' : 'آنی ⚡'; ?>
                                                </span>
                                            </td>
                                            <td data-label="وضعیت">
                                                <?php if ($p['status'] === 'sent'): ?>
                                                    <span class="badge badge-success">ارسال شده ✔</span>
                                                <?php elseif ($p['status'] === 'scheduled'): ?>
                                                    <span class="badge badge-scheduled">در انتظار ارسال ⏳</span>
                                                <?php else: ?>
                                                    <span class="badge badge-failed">خطا در ارسال ❌</span>
                                                <?php endif; ?>
                                            </td>
                                            <td data-label="زمان ثبت / ارسال"><span style="font-size:0.8rem;"><?php echo $p['scheduled_at'] ? \WHCM\Domain\TextFormat::mysql_to_jalali($p['scheduled_at'], false) : \WHCM\Domain\TextFormat::mysql_to_jalali($p['created_at']); ?></span></td>
                                            <td data-label="بازدید کل (کلیک)"><strong style="color:var(--primary);"><?php echo \WHCM\Domain\TextFormat::fa_digits($p['clicks']); ?> بازدید</strong></td>
                                            <td data-label="کلیک‌های یکتا"><strong><?php echo \WHCM\Domain\TextFormat::fa_digits($p['unique_clicks']); ?> کلیک</strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- ۳. بخش مدیریت کانال‌ها به صورت دو باکس مجزا متقارن -->
            <!-- ========================================== -->
            <div id="section-channels" class="tab-content">
                
                <!-- حالت ویرایش تنظیمات کانال -->
                <?php if ($edit_channel): ?>
                    <div class="card" style="border-color: var(--primary);">
                        <h2>⚙ ویرایش تنظیمات کانال: «<?php echo htmlspecialchars($edit_channel['name']); ?>»</h2>
                        <form action="<?php echo \WHCM\Core\Bootstrap::getRouteUrl('/dashboard/edit-channel'); ?>" method="POST">
                            <?php echo $csrf_field; ?>
                            <input type="hidden" name="channel_id" value="<?php echo $edit_channel['id']; ?>">
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="edit-name">نام نمایشی کانال:</label>
                                    <input type="text" name="name" id="edit-name" value="<?php echo htmlspecialchars($edit_channel['name']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="edit-platform">پلتفرم:</label>
                                    <select name="platform" id="edit-platform" required>
                                        <option value="telegram" <?php echo $edit_channel['platform'] === 'telegram' ? 'selected' : ''; ?>>تلگرام</option>
                                        <option value="bale" <?php echo $edit_channel['platform'] === 'bale' ? 'selected' : ''; ?>>بله (Bale)</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="edit-channel_id_val">آیدی کانال (مانند @MyGoldShop):</label>
                                    <input type="text" name="channel_id_val" id="edit-channel_id_val" value="<?php echo htmlspecialchars($edit_channel['channel_id']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="edit-token">توکن ربات:</label>
                                    <input type="password" name="token" id="edit-token" value="<?php echo htmlspecialchars($edit_channel['token']); ?>" required>
                                </div>
                            </div>

                            <!-- تنظیمات ۳ لینک اختصاصی زیر هر پست -->
                            <h3 style="font-size: 0.95rem; margin-top: 1rem; margin-bottom: 0.75rem; border-bottom: 1px dashed var(--border); padding-bottom: 0.4rem; color:#a5b4fc;">🔗 تنظیمات ۳ دکمه شیشه‌ای کپشن (لینک وب‌سایت)</h3>
                            <?php 
                                $links = json_decode($edit_channel['link_config'] ?? '[]', true); 
                                if (count($links) < 3) {
                                    $links = [['name'=>'', 'url'=>''], ['name'=>'', 'url'=>''], ['name'=>'', 'url'=>'']];
                                }
                            ?>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>دکمه ۱ - عنوان و آدرس لینک:</label>
                                    <input type="text" name="link_name_1" value="<?php echo htmlspecialchars($links[0]['name'] ?? ''); ?>" placeholder="عنوان دکمه اول" style="margin-bottom:0.5rem;">
                                    <input type="text" name="link_url_1" value="<?php echo htmlspecialchars($links[0]['url'] ?? ''); ?>" placeholder="https://example.com/shop">
                                </div>
                                <div class="form-group">
                                    <label>دکمه ۲ - عنوان و آدرس لینک:</label>
                                    <input type="text" name="link_name_2" value="<?php echo htmlspecialchars($links[1]['name'] ?? ''); ?>" placeholder="عنوان دکمه دوم" style="margin-bottom:0.5rem;">
                                    <input type="text" name="link_url_2" value="<?php echo htmlspecialchars($links[1]['url'] ?? ''); ?>" placeholder="https://example.com/t.me">
                                </div>
                            </div>
                            <div class="form-group" style="max-width: 50%;">
                                <label>دکمه ۳ - عنوان (آدرس آن خودکار به ردیاب کلیک تبدیل می‌شود):</label>
                                <input type="text" name="link_name_3" value="<?php echo htmlspecialchars($links[2]['name'] ?? ''); ?>" placeholder="مثلاً: ورود به فروشگاه">
                                <input type="hidden" name="link_url_3" value="">
                            </div>

                            <!-- تنظیمات دکمه‌های شیشه‌ای تعاملی زیرین -->
                            <h3 style="font-size: 0.95rem; margin-top: 1rem; margin-bottom: 0.75rem; border-bottom: 1px dashed var(--border); padding-bottom: 0.4rem; color:#a5b4fc;">💬 دکمه‌های شیشه‌ای تعاملی زیر پست (Interactive Buttons)</h3>
                            <?php 
                                $btn_cfg = json_decode($edit_channel['button_config'] ?? '[]', true); 
                                $btns_active = !empty($btn_cfg['active']);
                                $btns = $btn_cfg['buttons'] ?? [['text'=>'', 'url'=>''], ['text'=>'', 'url'=>'']];
                            ?>
                            <div class="form-group">
                                <label class="toggle-container">
                                    <input type="checkbox" name="buttons_active" value="1" class="toggle-input" <?php echo $btns_active ? 'checked' : ''; ?>>
                                    <span>فعال‌سازی دکمه‌های تعاملی شیشه‌ای زیر پیام در زمان ارسال</span>
                                </label>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>دکمه تعاملی ۱ - متن و آدرس دکمه:</label>
                                    <input type="text" name="btn_text_1" value="<?php echo htmlspecialchars($btns[0]['text'] ?? ''); ?>" placeholder="ارتباط با ادمین" style="margin-bottom:0.5rem;">
                                    <input type="text" name="btn_url_1" value="<?php echo htmlspecialchars($btns[0]['url'] ?? ''); ?>" placeholder="https://t.me/your_admin">
                                </div>
                                <div class="form-group">
                                    <label>دکمه تعاملی ۲ - متن و آدرس دکمه:</label>
                                    <input type="text" name="btn_text_2" value="<?php echo htmlspecialchars($btns[1]['text'] ?? ''); ?>" placeholder="سفارش فوری" style="margin-bottom:0.5rem;">
                                    <input type="text" name="btn_url_2" value="<?php echo htmlspecialchars($btns[1]['url'] ?? ''); ?>" placeholder="https://asovin.ir">
                                </div>
                            </div>

                            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                                <button type="submit" class="btn btn-success">ذخیره تنظیمات کانال ✔</button>
                                <a href="<?php echo \WHCM\Core\Bootstrap::getRouteUrl('/dashboard'); ?>" class="btn btn-danger" style="background: rgba(255,255,255,0.08); color: white;">انصراف</a>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

                <!-- نمایش دو باکسی متقارن و در کنار هم کانال‌ها (طراحی روز دنیا) -->
                <div class="card">
                    <h2>📻 لیست تفکیکی کانال‌های متصل شده شما</h2>
                    <div class="grid-channels">
                        
                        <!-- باکس کانال‌های تلگرام -->
                        <div class="channel-box">
                            <div class="channel-box-title">🔵 کانال‌های فعال تلگرام</div>
                            <?php 
                                $tg_channels = array_filter($channels, function($c) { return $c['platform'] === 'telegram'; });
                                if (empty($tg_channels)):
                            ?>
                                <p style="color:var(--text-muted); text-align:center; font-size:0.8rem; padding: 1rem 0;">هنوز هیچ کانال تلگرامی متصل نکرده‌اید.</p>
                            <?php else: foreach ($tg_channels as $ch): ?>
                                <div class="channel-item">
                                    <div>
                                        <strong><?php echo htmlspecialchars($ch['name']); ?></strong><br>
                                        <code style="font-size:0.75rem; color:var(--text-muted);"><?php echo htmlspecialchars($ch['channel_id']); ?></code>
                                    </div>
                                    <div style="display:flex; gap:0.25rem;">
                                        <a href="?edit_channel=<?php echo $ch['id']; ?>" class="btn btn-sm" style="background:#3b82f6; padding:0.35rem 0.65rem;">⚙</a>
                                        <a href="<?php echo \WHCM\Core\Bootstrap::getRouteUrl('/dashboard/delete-channel'); ?>&id=<?php echo $ch['id']; ?>" class="btn btn-danger btn-sm" style="padding:0.35rem 0.65rem;" onclick="return confirm('آیا از حذف این کانال اطمینان دارید؟');">🗑</a>
                                    </div>
                                </div>
                            <?php endforeach; endif; ?>
                        </div>

                        <!-- باکس کانال‌های بله -->
                        <div class="channel-box">
                            <div class="channel-box-title">🟢 کانال‌های فعال بله</div>
                            <?php 
                                $bale_channels = array_filter($channels, function($c) { return $c['platform'] === 'bale'; });
                                if (empty($bale_channels)):
                            ?>
                                <p style="color:var(--text-muted); text-align:center; font-size:0.8rem; padding: 1rem 0;">هنوز هیچ کانال بله متصل نکرده‌اید.</p>
                            <?php else: foreach ($bale_channels as $ch): ?>
                                <div class="channel-item">
                                    <div>
                                        <strong><?php echo htmlspecialchars($ch['name']); ?></strong><br>
                                        <code style="font-size:0.75rem; color:var(--text-muted);"><?php echo htmlspecialchars($ch['channel_id']); ?></code>
                                    </div>
                                    <div style="display:flex; gap:0.25rem;">
                                        <a href="?edit_channel=<?php echo $ch['id']; ?>" class="btn btn-sm" style="background:#3b82f6; padding:0.35rem 0.65rem;">⚙</a>
                                        <a href="<?php echo \WHCM\Core\Bootstrap::getRouteUrl('/dashboard/delete-channel'); ?>&id=<?php echo $ch['id']; ?>" class="btn btn-danger btn-sm" style="padding:0.35rem 0.65rem;" onclick="return confirm('آیا از حذف این کانال اطمینان دارید؟');">🗑</a>
                                    </div>
                                </div>
                            <?php endforeach; endif; ?>
                        </div>

                    </div>
                </div>

                <div class="card">
                    <h2>➕ اتصال کانال جدید</h2>
                    <form action="<?php echo \WHCM\Core\Bootstrap::getRouteUrl('/dashboard/add-channel'); ?>" method="POST">
                        <?php echo $csrf_field; ?>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="c-name">نام نمایشی کانال:</label>
                                <input type="text" name="name" id="c-name" required placeholder="مثلا: فروشگاه طلا و سکه آسوین">
                            </div>
                            <div class="form-group">
                                <label for="c-platform">پلتفرم پیام‌رسان:</label>
                                <select name="platform" id="c-platform" required>
                                    <option value="telegram">تلگرام (Telegram)</option>
                                    <option value="bale">بله (Bale)</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="c-channel_id">آیدی کانال (شروع با @ یا آیدی عددی):</label>
                                <input type="text" name="channel_id" id="c-channel_id" required placeholder="@MyGoldShop">
                            </div>
                            <div class="form-group">
                                <label for="c-token">توکن ربات (Bot Token):</label>
                                <input type="password" name="token" id="c-token" required placeholder="توکن دریافتی از BotFather">
                            </div>
                        </div>
                        <button type="submit" class="btn">اتصال ربات و بررسی ارتباط 📡</button>
                    </form>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- ۴. بخش ربات طلا و سکه -->
            <!-- ========================================== -->
            <div id="section-ticker" class="tab-content">
                <div class="card">
                    <h2>🪙 ربات خودکار و هوشمند انتشار نرخ لحظه‌ای طلا</h2>
                    <form id="gold-settings-form" action="javascript:void(0);" onsubmit="saveGoldSettingsAjax(this)" enctype="multipart/form-data">
                        <?php echo $csrf_field; ?>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="g-schedule">زمان‌بندی بررسی و انتشار خودکار:</label>
                                <?php $sel_sched = $settings['gold_schedule'] ?? 'manual'; ?>
                                <select name="gold_schedule" id="g-schedule">
                                    <option value="manual" <?php echo $sel_sched === 'manual' ? 'selected' : ''; ?>>غیرفعال (فقط دستی)</option>
                                    <option value="every_5_minutes" <?php echo $sel_sched === 'every_5_minutes' ? 'selected' : ''; ?>>هر ۵ دقیقه</option>
                                    <option value="every_15_minutes" <?php echo $sel_sched === 'every_15_minutes' ? 'selected' : ''; ?>>هر ۱۵ دقیقه</option>
                                    <option value="every_30_minutes" <?php echo $sel_sched === 'every_30_minutes' ? 'selected' : ''; ?>>هر ۳۰ دقیقه</option>
                                    <option value="every_1_hour" <?php echo $sel_sched === 'every_1_hour' ? 'selected' : ''; ?>>هر ۱ ساعت</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="g-currency">واحد پول ورودی API:</label>
                                <?php $sel_curr = $settings['gold_currency'] ?? 'toman'; ?>
                                <select name="gold_currency" id="g-currency">
                                    <option value="toman" <?php echo $sel_curr === 'toman' ? 'selected' : ''; ?>>تومان</option>
                                    <option value="rial" <?php echo $sel_curr === 'rial' ? 'selected' : ''; ?>>ریال (تبدیل خودکار به تومان)</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="g-url">آدرس اختصاصی API طلا (اختیاری):</label>
                                <input type="text" name="gold_api_url" id="g-url" value="<?php echo htmlspecialchars($settings['gold_api_url'] ?? ''); ?>" placeholder="https://api.tgju.org/v1/...">
                            </div>
                            <div class="form-group">
                                <label for="g-image">بارگذاری و آپلود تصویر شاخص نرخ طلا:</label>
                                <input type="file" name="gold_image" id="g-image" accept="image/*" style="padding:0.5rem 1rem;">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>انتخاب کانال‌های هدف جهت ارسال خودکار:</label>
                            <?php $saved_channels = json_decode($settings['gold_auto_channels'] ?? '[]', true); ?>
                            <div style="display: flex; flex-wrap: wrap; gap: 1.5rem; background: rgba(15,23,42,0.4); padding: 1rem; border-radius: 12px; border:1px solid var(--border);">
                                <?php if (empty($channels)): ?>
                                    <span style="color:var(--text-muted); font-size:0.85rem;">هنوز کانالی متصل نکرده‌اید.</span>
                                <?php else: ?>
                                    <?php foreach ($channels as $ch): ?>
                                        <label class="toggle-container" style="background: none; border: none; padding: 0;">
                                            <input type="checkbox" name="gold_channels[]" value="<?php echo $ch['id']; ?>" class="toggle-input" <?php echo in_array($ch['id'], $saved_channels) ? 'checked' : ''; ?>>
                                            <span><?php echo htmlspecialchars($ch['name']); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="g-template">قالب پیام نرخ طلا:</label>
                            <?php $tpl = $settings['gold_template'] ?? "🌟 اعلام نرخ لحظه‌ای بازار طلا و سکه\n\nهر گرم طلا ۱۸ عیار: {g18k}\nسکه تمام بهار آزادی: {coin}\nانس جهانی: {oz}\n\n⏰ به‌روزشده در: {time}"; ?>
                            <textarea name="gold_template" id="g-template" rows="6" required><?php echo htmlspecialchars($tpl); ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-success" id="gold-save-btn">ذخیره تنظیمات ربات طلا 🪙</button>
                    </form>
                </div>

                <div class="card" style="border: 1px solid rgba(16,185,129,0.2); background: linear-gradient(135deg, rgba(16,185,129,0.05) 0%, rgba(15,23,42,0.6) 100%);">
                    <h2>⚡ انتشار آنی، زنده و تستی نرخ طلا</h2>
                    <form action="<?php echo \WHCM\Core\Bootstrap::getRouteUrl('/dashboard/trigger-gold-publish'); ?>" method="POST">
                        <?php echo $csrf_field; ?>
                        <button type="submit" class="btn btn-success" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">انتشار زنده و آنی به کانال‌ها 🚀</button>
                    </form>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- ۵. بخش پاسخگوی هوشمند (کلمات کلیدی) -->
            <!-- ========================================== -->
            <div id="section-responder" class="tab-content">
                <!-- توضیح عملکرد -->
                <div class="card" style="background: linear-gradient(135deg, rgba(99,102,241,0.1) 0%, rgba(15,23,42,0.9) 100%); border: 1px solid rgba(99,102,241,0.3); margin-bottom: 1.5rem;">
                    <h2>🤖 دریافت پیام‌های مشترکین و ارسال خودکار</h2>
                    <p style="color: var(--text-muted); font-size: 0.85rem; line-height: 1.8; margin-bottom: 0.75rem;">
                        این سیستم با بررسی پیام‌های ورودی به ربات کانال‌های تلگرام و بله، در صورت وجود کلمه کلیدی تعریف‌شده، پاسخ از پیش تنظیم‌شده را به صورت خودکار ارسال می‌کند.
                    </p>
                    <p style="color: #94a3b8; font-size: 0.8rem; line-height: 1.7;">
                        ⚡ نحوه کار: ابتدا حالت پاسخگویی را برای کانال مورد نظر فعال کنید، سپس کلمات کلیدی و پاسخ‌های آماده را تعریف نمایید.
                        هرگاه کاربری پیامی حاوی آن کلمه کلیدی ارسال کند، ربات بلافاصله پاسخ متناظر را ارسال خواهد کرد.
                    </p>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <!-- پنل چپ: مدیریت کلمات کلیدی -->
                    <div class="card">
                        <h2 style="font-size: 1rem; margin-bottom: 1rem;">📝 محتوای قالب‌های پاسخگویی خودکار</h2>
                        
                        <form id="ar-add-form" action="javascript:void(0);" onsubmit="addAutoReplyAjax()">
                            <div class="form-group" style="margin-bottom: 0.75rem;">
                                <label for="ar-channel">انتخاب ربات کانال:</label>
                                <select name="channel_id" id="ar-channel" required style="border-radius:10px;">
                                    <option value="">-- کانال هدف را انتخاب کنید --</option>
                                    <?php foreach ($channels as $ch): ?>
                                        <option value="<?php echo $ch['id']; ?>"><?php echo htmlspecialchars($ch['name']); ?> (<?php echo $ch['platform'] === 'telegram' ? '✈️ تلگرام' : '💬 بله'; ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-row" style="margin-bottom: 0.75rem;">
                                <div class="form-group">
                                    <label for="ar-keyword">کلمه کلیدی (متن اضافه):</label>
                                    <input type="text" name="keyword" id="ar-keyword" required placeholder="مثلاً: سلام، آدرس، قیمت" style="border-radius:10px;">
                                </div>
                                <div class="form-group" style="display:flex; align-items:flex-end;">
                                    <button type="submit" class="btn btn-primary" style="background:rgba(99,102,241,0.2); color:#a5b4fc; border:1px solid rgba(99,102,241,0.3); border-radius:10px; padding:0.6rem 1.2rem; white-space:nowrap;">+ افزودن</button>
                                </div>
                            </div>
                            <div class="form-group" style="margin-bottom: 0.75rem;">
                                <label for="ar-reply">متن پاسخ خودکار:</label>
                                <textarea name="reply_text" id="ar-reply" rows="3" required placeholder="پاسخ خودکار را بنویسید..." style="border-radius:10px;"></textarea>
                            </div>
                        </form>

                        <!-- جدول قالب‌های تعریف‌شده -->
                        <div style="margin-top: 1rem; max-height: 350px; overflow-y: auto;">
                            <div id="ar-list-container">
                            <?php if (empty($auto_replies)): ?>
                                <p style="color:var(--text-muted); text-align:center; padding:1.5rem 0; font-size:0.85rem;">هیچ قالب پاسخگویی تعریف نشده است. از فرم بالا افزودن کنید.</p>
                            <?php else: ?>
                                <table style="width:100%; font-size:0.85rem;">
                                    <thead>
                                        <tr>
                                            <th style="text-align:right; padding:0.5rem;">کلمه کلید</th>
                                            <th style="text-align:center; padding:0.5rem;">وضعیت</th>
                                            <th style="text-align:center; padding:0.5rem;">عملیات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($auto_replies as $rule): ?>
                                        <tr id="ar-row-<?php echo $rule['id']; ?>">
                                            <td style="padding:0.5rem;">
                                                <div style="font-weight:bold; color:white;"><?php echo htmlspecialchars($rule['keyword']); ?></div>
                                                <div style="color:#64748b; font-size:0.75rem; margin-top:0.25rem;"><?php echo htmlspecialchars(mb_substr($rule['reply_text'], 0, 60)) . (mb_strlen($rule['reply_text']) > 60 ? '...' : ''); ?></div>
                                                <div style="color:#475569; font-size:0.7rem; margin-top:0.15rem;">کانال: <?php echo htmlspecialchars($rule['channel_name']); ?> <?php echo ($rule['channel_platform'] ?? '') === 'telegram' ? '✈️' : '💬'; ?></div>
                                            </td>
                                            <td style="text-align:center; padding:0.5rem;">
                                                <span class="badge badge-success" style="font-size:0.7rem;">سرویس</span>
                                            </td>
                                            <td style="text-align:center; padding:0.5rem;">
                                                <button type="button" onclick="deleteAutoReplyAjax(<?php echo $rule['id']; ?>)" class="btn btn-danger btn-sm" style="font-size:0.75rem; background:rgba(239,68,68,0.15); border:1px solid rgba(239,68,68,0.3); color:#f87171; border-radius:8px;">حذف</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- پنل راست: وضعیت کانال‌ها + گزارش آخرین ارتباط‌ها -->
                    <div>
                        <!-- کارت وضعیت پاسخگویی هر کانال -->
                        <div class="card" style="margin-bottom: 1.5rem;">
                            <h2 style="font-size: 1rem; margin-bottom: 1rem;">⚙️ حالت پاسخگویی خودکار</h2>
                            <p style="color:var(--text-muted); font-size:0.78rem; margin-bottom:1rem;">برای هر کانال می‌توانید به‌صورت جداگانه پاسخگوی خودکار را فعال یا غیرفعال کنید:</p>
                            <?php if (empty($channels)): ?>
                                <p style="color:#94a3b8; text-align:center; padding:1rem; font-size:0.85rem;">هیچ کانالی ثبت نشده است. ابتدا از بخش «کانال‌ها» یک کانال اضافه کنید.</p>
                            <?php else: ?>
                                <div style="display:flex; flex-direction:column; gap:0.75rem;">
                                    <?php foreach ($channels as $ch): ?>
                                        <?php 
                                            $is_enabled = !empty($responder_settings['responder_enabled_' . $ch['id']]) ? (int)$responder_settings['responder_enabled_' . $ch['id']] : 0;
                                            $platform_icon = $ch['platform'] === 'telegram' ? '✈️' : '💬';
                                            $platform_label = $ch['platform'] === 'telegram' ? 'تلگرام' : 'بله';
                                        ?>
                                        <div style="display:flex; justify-content:space-between; align-items:center; background:#0f172a; border:1px solid <?php echo $is_enabled ? '#10b981' : '#334155'; ?>; border-radius:12px; padding:0.75rem 1rem; transition: all 0.3s;">
                                            <div>
                                                <strong style="color:white; font-size:0.9rem;"><?php echo htmlspecialchars($ch['name']); ?></strong>
                                                <div style="color:#64748b; font-size:0.72rem;"><?php echo $platform_icon . ' ' . $platform_label; ?></div>
                                            </div>
                                            <label class="responder-toggle" style="position:relative; display:inline-block; width:48px; height:26px; cursor:pointer;" id="toggle-label-<?php echo $ch['id']; ?>">
                                                <input type="checkbox" <?php echo $is_enabled ? 'checked' : ''; ?> onchange="toggleResponder(<?php echo $ch['id']; ?>, this.checked)" style="opacity:0; width:0; height:0; position:absolute;">
                                                <span class="toggle-track" style="position:absolute; cursor:pointer; inset:0; background:<?php echo $is_enabled ? '#10b981' : '#475569'; ?>; border-radius:26px; transition:0.3s;"></span>
                                                <span class="toggle-thumb" style="position:absolute; height:20px; width:20px; left:<?php echo $is_enabled ? '25px' : '3px'; ?>; bottom:3px; background:white; border-radius:50%; transition:0.3s;"></span>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- کارت آخرین ارتباط‌ها -->
                        <div class="card">
                            <h2 style="font-size: 1rem; margin-bottom: 1rem;">📊 آخرین ارتباط‌های مشترکین</h2>
                            <p style="color:var(--text-muted); font-size:0.78rem; margin-bottom:1rem;">نمایش آخرین پیام‌های دریافتی و وضعیت پاسخگویی خودکار:</p>
                            <div id="responder-log-area" style="max-height: 250px; overflow-y: auto;">
                                <?php
                                    // دریافت آخرین لاگ‌های پاسخگوی خودکار
                                    $log_db = \WHCM\Core\Bootstrap::getDB();
                                    try {
                                        $log_stmt = $log_db->prepare("SELECT * FROM responder_logs WHERE tenant_id = ? ORDER BY id DESC LIMIT 20");
                                        $log_stmt->execute([$user['id']]);
                                        $responder_logs = $log_stmt->fetchAll();
                                    } catch (\Throwable $e) { $responder_logs = []; }
                                ?>
                                <?php if (empty($responder_logs)): ?>
                                    <p style="color:#94a3b8; text-align:center; padding:1.5rem 0; font-size:0.82rem;">هنوز پیامی دریافت نشده یا لاگی موجود نیست.</p>
                                <?php else: ?>
                                    <table style="width:100%; font-size:0.8rem;">
                                        <thead>
                                            <tr>
                                                <th style="text-align:right; padding:0.4rem;">پیام</th>
                                                <th style="text-align:center; padding:0.4rem; width:60px;">وضعیت</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($responder_logs as $log): ?>
                                                <tr>
                                                    <td style="padding:0.4rem; color:#e2e8f0;">
                                                        <div style="color:#94a3b8; font-size:0.7rem;"><?php echo htmlspecialchars($log['sender_name'] ?? 'کاربر'); ?></div>
                                                        <div style="max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?php echo htmlspecialchars($log['message_text'] ?? ''); ?></div>
                                                    </td>
                                                    <td style="text-align:center; padding:0.4rem;">
                                                        <?php if (!empty($log['replied'])): ?>
                                                            <span style="color:#10b981; font-size:1rem;">✅</span>
                                                        <?php else: ?>
                                                            <span style="color:#475569; font-size:1rem;">➖</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- ۵.۵. بخش سیستم پشتیبانی و تیکت‌ها -->
            <!-- ========================================== -->
            <div id="section-tickets" class="tab-content">
                <!-- کارت راه‌های ارتباط سریع با پشتیبانی -->
                <div class="card" style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.15) 0%, rgba(15, 23, 42, 0.8) 100%); border: 1px solid var(--primary); margin-bottom: 2rem;">
                    <h3 style="color: #818cf8; margin-bottom: 0.75rem;">📞 راه‌های ارتباط سریع با تیم پشتیبانی پُست‌یار</h3>
                    <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 1.25rem;">شما می‌توانید علاوه بر ارسال تیکت در سامانه، از طریق کانال‌ها و پیام‌رسان‌های زیر به‌صورت مستقیم با مدیریت و کارشناسان پشتیبانی در ارتباط باشید:</p>
                    <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                        <a href="<?php echo htmlspecialchars($global_bank['support_telegram_url'] ?? 'https://t.me/asovin_support'); ?>" target="_blank" class="btn btn-outline" style="border-color: #38bdf8; color: #38bdf8;">✈ تلگرام پشتیبانی</a>
                        <a href="<?php echo htmlspecialchars($global_bank['support_bale_url'] ?? 'https://ble.ir/asovin_support'); ?>" target="_blank" class="btn btn-outline" style="border-color: #34d399; color: #34d399;">💬 بله پشتیبانی</a>
                        <a href="mailto:<?php echo htmlspecialchars($global_bank['support_email'] ?? 'support@asovin.ir'); ?>" class="btn btn-outline" style="border-color: #a855f7; color: #a855f7;">✉ ایمیل پشتیبانی</a>
                    </div>
                </div>

                <div class="card">
                    <h2>🎫 ارسال تیکت پشتیبانی جدید</h2>
                    <form action="<?php echo \WHCM\Core\Bootstrap::getRouteUrl('/dashboard/add-ticket'); ?>" method="POST" enctype="multipart/form-data">
                        <?php echo $csrf_field; ?>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="t-subject">موضوع تیکت:</label>
                                <input type="text" name="subject" id="t-subject" required placeholder="مثلاً: سوال در مورد فعال‌سازی سهمیه">
                            </div>
                            <div class="form-group">
                                <label for="t-cat">دسته‌بندی تیکت:</label>
                                <select name="category" id="t-cat" required>
                                    <?php if (!empty($ticket_categories)): ?>
                                        <?php foreach ($ticket_categories as $cat): ?>
                                            <option value="<?php echo htmlspecialchars($cat['slug']); ?>"><?php echo htmlspecialchars($cat['title']); ?></option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <option value="technical">فنی و ربات‌ها 🤖</option>
                                        <option value="billing">مالی و فیش واریزی 💳</option>
                                        <option value="general">سوال عمومی 🌐</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="t-msg">متن پیام شما برای پشتیبانی:</label>
                            <textarea name="message" id="t-msg" rows="5" required placeholder="توضیحات کامل مشکل یا سوال خود را اینجا بنویسید..."></textarea>
                        </div>
                        <div class="form-group">
                            <label for="t-file">پیوست تصویر (اختیاری):</label>
                            <input type="file" name="attachment" id="t-file" accept="image/*,.pdf" style="padding:0.5rem 1rem;">
                        </div>
                        <button type="submit" class="btn">ارسال تیکت به تیم پشتیبانی 🎫</button>
                    </form>
                </div>

                <div class="card">
                    <h2>📋 وضعیت تیکت‌های پشتیبانی قبلی شما</h2>
                    <?php if (empty($tickets)): ?>
                        <p style="color: var(--text-muted); text-align: center; padding: 2.5rem 0;">شما هنوز تیکتی ارسال نکرده‌اید.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>شناسه</th>
                                        <th>موضوع تیکت</th>
                                        <th>دسته‌بندی</th>
                                        <th>وضعیت پاسخ</th>
                                        <th>تاریخ ارسال</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tickets as $t): ?>
                                        <tr>
                                            <td data-label="شناسه"><code>#<?php echo \WHCM\Domain\TextFormat::fa_digits($t['id']); ?></code></td>
                                            <td data-label="موضوع تیکت">
                                                <strong style="color:#ffffff;"><?php echo htmlspecialchars($t['subject']); ?></strong><br>
                                                <button type="button" class="btn btn-outline btn-sm" style="margin-top:0.5rem; background:linear-gradient(135deg, #6366f1 0%, #4f46e5 100%) !important; color:#ffffff !important; font-weight:800; border:none; font-size:0.78rem; padding:0.4rem 0.8rem; box-shadow:0 4px 10px rgba(99,102,241,0.3);" onclick='openTicketModal(<?php echo json_encode($t, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP|JSON_UNESCAPED_UNICODE); ?>)'>👁 مشاهده گفتگو و پاسخ پشتیبانی</button>
                                            </td>
                                            <td data-label="دسته‌بندی">
                                                <span class="badge" style="background:rgba(255,255,255,0.05);">
                                                    <?php echo isset($category_map[$t['category']]) ? $category_map[$t['category']] : htmlspecialchars($t['category']); ?>
                                                </span>
                                            </td>
                                            <td data-label="وضعیت پاسخ">
                                                <?php if ($t['status'] === 'open'): ?>
                                                    <span class="badge badge-pending">در انتظار پاسخ ⏳</span>
                                                <?php elseif ($t['status'] === 'replied'): ?>
                                                    <span class="badge badge-success">پاسخ داده شده ✔</span>
                                                <?php else: ?>
                                                    <span class="badge badge-telegram">بسته شده</span>
                                                <?php endif; ?>
                                            </td>
                                            <td data-label="تاریخ ارسال"><span style="font-size:0.8rem;"><?php echo \WHCM\Domain\TextFormat::mysql_to_jalali($t['created_at']); ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- راه‌های ارتباطی فرعی — حذف شد (تکراریِ بالای صفحه) -->
            </div>

            <!-- ========================================== -->
            <!-- ۶. بخش صندوق پیام -->
            <!-- ========================================== -->
            <div id="section-inbox" class="tab-content">
                <div class="card">
                    <h2>📩 صندوق پیام‌های دریافتی</h2>
                    <p style="color: var(--text-muted); font-size: 0.82rem; margin-bottom: 1rem; line-height: 1.7;">
                        این بخش پیام‌هایی را نشان می‌دهد که کاربران از طریق ربات تلگرام یا بله به کانال‌های شما ارسال کرده‌اند.
                        همچنین پاسخ‌های تیکت‌های پشتیبانی که توسط مدیریت پُست‌یار داده شده نیز اینجا نمایش داده می‌شود.
                    </p>
                    <?php if (empty($inbox)): ?>
                        <div style="text-align: center; padding: 2rem 0;">
                            <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">📭</div>
                            <p style="color: var(--text-muted);">هنوز پیامی دریافت نشده است.</p>
                            <p style="color: #64748b; font-size: 0.78rem; margin-top: 0.5rem;">پیام‌ها زمانی نمایش داده می‌شوند که کاربرانی در کانال‌های متصل شما به ربات پیام ارسال کنند.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>کانال</th>
                                        <th>فرستنده</th>
                                        <th>متن پیام</th>
                                        <th>تاریخ دریافت</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($inbox as $msg): ?>
                                        <tr>
                                            <td data-label="کانال"><span class="badge badge-telegram"><?php echo htmlspecialchars($msg['channel_name']); ?></span></td>
                                            <td data-label="فرستنده"><strong><?php echo htmlspecialchars($msg['sender_name']); ?></strong></td>
                                            <td data-label="پیام"><span style="font-size: 0.85rem;"><?php echo nl2br(htmlspecialchars($msg['message_text'])); ?></span></td>
                                            <td data-label="زمان دریافت"><span style="font-size: 0.8rem; color: var(--text-muted);"><?php echo \WHCM\Domain\TextFormat::mysql_to_jalali($msg['received_at']); ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- ۷. بخش تنظیمات حساب کاربری -->
            <!-- ========================================== -->
            <div id="section-settings" class="tab-content">
                <div class="card">
                    <h2>👤 ویرایش پروفایل کاربری</h2>
                    <form action="<?php echo \WHCM\Core\Bootstrap::getRouteUrl('/dashboard/update-profile'); ?>" method="POST">
                        <?php echo $csrf_field; ?>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="u-name">نام و نام خانوادگی:</label>
                                <input type="text" name="name" id="u-name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="u-email">نشانی ایمیل:</label>
                                <input type="email" name="email" id="u-email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="profile_birthday">تاریخ تولد (شمسی):</label>
                            <input type="text" name="birthday" id="profile_birthday" data-jdp placeholder="مثلاً: ۱۳۷۰/۰۶/۱۵" value="<?php echo htmlspecialchars($user['birthday'] ?? ''); ?>" style="background-color:rgba(15,23,42,0.6); color:#34d399; font-weight:bold; border:2px solid #34d399; border-radius:0.75rem; padding:0.85rem 1rem; cursor:pointer;" readonly>
                            <small style="color:var(--text-muted); font-size:0.78rem; margin-top:0.25rem; display:block;">تاریخ تولد به صورت شمسی (مثلاً: ۱۳۷۰/۰۶/۱۵)</small>
                        </div>
                        <button type="submit" class="btn btn-success">بروزرسانی مشخصات کاربری ✔</button>
                    </form>
                </div>

                <div class="card">
                    <h2>🔑 تغییر کلمه عبور</h2>
                    <form action="<?php echo \WHCM\Core\Bootstrap::getRouteUrl('/dashboard/change-password'); ?>" method="POST">
                        <?php echo $csrf_field; ?>
                        <div class="form-group">
                            <label for="p-curr">کلمه عبور فعلی:</label>
                            <input type="password" name="current_password" id="p-curr" required>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="p-new">کلمه عبور جدید:</label>
                                <input type="password" name="new_password" id="p-new" required placeholder="حداقل ۸ کاراکتر">
                            </div>
                            <div class="form-group">
                                <label for="p-conf">تکرار کلمه عبور جدید:</label>
                                <input type="password" name="confirm_password" id="p-conf" required placeholder="تکرار مجدد">
                            </div>
                        </div>
                        <button type="submit" class="btn">بروزرسانی کلمه عبور 🔒</button>
                    </form>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- ۷.۵. بخش تنظیمات پیشرفته (جداگانه در لیست منو) -->
            <!-- ========================================== -->
            <div id="section-advanced-settings" class="tab-content">
                <?php
                    // لود گروهی تمام تنظیمات پیشرفته مستأجر
                    $stmt = \WHCM\Core\Bootstrap::getDB()->prepare("SELECT key_name, key_value FROM settings WHERE tenant_id = ?");
                    $stmt->execute([$user['id']]);
                    $settings_rows = $stmt->fetchAll();
                    $adv_settings = [];
                    foreach ($settings_rows as $row) {
                        $adv_settings[$row['key_name']] = $row['key_value'];
                    }

                    $woo_active = ($adv_settings['auto_publish_woo'] ?? 'yes') === 'yes';
                    $watermark_active = ($adv_settings['watermark_active'] ?? 'yes') === 'yes';
                    $caption_format = $adv_settings['caption_format'] ?? 'plain';
                    $inbound_method = $adv_settings['inbound_method'] ?? 'polling';
                    $poll_interval = $adv_settings['poll_interval'] ?? 'every_1_minute';
                    
                    $ai_provider = $adv_settings['ai_provider'] ?? 'openai';
                    $ai_key = $adv_settings['ai_api_key'] ?? '';
                    $ai_model = $adv_settings['ai_model'] ?? 'gpt-4o';
                    $ai_url = $adv_settings['ai_api_url'] ?? 'https://api.openai.com/v1/chat/completions';
                    
                    $link_1_n = $adv_settings['link_1_name'] ?? '📢 کانال تلگرام';
                    $link_1_u = $adv_settings['link_1_url'] ?? '';
                    $link_2_n = $adv_settings['link_2_name'] ?? '💬 کانال بله';
                    $link_2_u = $adv_settings['link_2_url'] ?? '';
                    $link_3_n = $adv_settings['link_3_name'] ?? '🌐 خرید آنلاین از سایت';
                    $link_3_u = $adv_settings['link_3_url'] ?? '';
                    
                    $btn_1_t = $adv_settings['btn_1_text'] ?? '🛒 خرید آنلاین از سایت';
                    $btn_2_t = $adv_settings['btn_2_text'] ?? '💎 پشتیبانی VIP';
                    $btn_2_u = $adv_settings['btn_2_url'] ?? '';
                    $btn_3_t = $adv_settings['btn_3_text'] ?? '📢 هومن وب';
                    $btn_3_u = $adv_settings['btn_3_url'] ?? '';
                ?>
                <div class="card" style="border: 1px solid rgba(139, 92, 246, 0.25); background: linear-gradient(135deg, rgba(139, 92, 246, 0.05) 0%, rgba(15, 23, 42, 0.6) 100%);">
                    <h2>⚙ تنظیمات پیشرفته و اتوماسیون پُست‌یار</h2>
                    <form id="adv-settings-form" action="javascript:void(0);" onsubmit="saveAdvancedSettingsAjax(this)">
                        <?php echo $csrf_field; ?>
                        
                        <!-- ۳ لینک سراسری -->
                        <h3 style="font-size: 0.95rem; margin-top: 1rem; margin-bottom: 0.75rem; border-bottom: 1px dashed var(--border); padding-bottom: 0.4rem; color:#a5b4fc;">🔗 پیش‌فرض سراسری ۳ لینک پایین محتوا</h3>
                        <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:1rem; margin-bottom: 1.5rem;">
                            <div class="form-group" style="background: rgba(15,23,42,0.4); padding: 1rem; border-radius: 12px; border: 1px solid var(--border);">
                                <label style="color:#38bdf8;">🔗 لینک ۱ (پیش‌فرض):</label>
                                <input type="text" name="link_1_name" value="<?php echo htmlspecialchars($link_1_n); ?>" style="margin-bottom: 0.5rem;">
                                <input type="url" name="link_1_url" value="<?php echo htmlspecialchars($link_1_u); ?>" placeholder="https://t.me/MyChannel">
                            </div>
                            <div class="form-group" style="background: rgba(15,23,42,0.4); padding: 1rem; border-radius: 12px; border: 1px solid var(--border);">
                                <label style="color:#34d399;">🔗 لینک ۲ (پیش‌فرض):</label>
                                <input type="text" name="link_2_name" value="<?php echo htmlspecialchars($link_2_n); ?>" style="margin-bottom: 0.5rem;">
                                <input type="url" name="link_2_url" value="<?php echo htmlspecialchars($link_2_u); ?>" placeholder="https://ble.ir/MyChannel">
                            </div>
                            <div class="form-group" style="background: rgba(15,23,42,0.4); padding: 1rem; border-radius: 12px; border: 1px solid var(--border);">
                                <label style="color:#a855f7;">🔗 لینک ۳ (پیش‌فرض سایت):</label>
                                <input type="text" name="link_3_name" value="<?php echo htmlspecialchars($link_3_n); ?>" style="margin-bottom: 0.5rem;">
                                <input type="url" name="link_3_url" value="<?php echo htmlspecialchars($link_3_u); ?>" placeholder="https://example.com">
                            </div>
                        </div>

                        <!-- ۳ دکمه تعاملی سراسری -->
                        <h3 style="font-size: 0.95rem; margin-top: 1rem; margin-bottom: 0.75rem; border-bottom: 1px dashed var(--border); padding-bottom: 0.4rem; color:#a5b4fc;">🎛️ پیش‌فرض سراسری دکمه‌های شیشه‌ای تعاملی</h3>
                        <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:1rem; margin-bottom: 1.5rem;">
                            <div class="form-group" style="background: rgba(15,23,42,0.4); padding: 1rem; border-radius: 12px; border: 1px solid var(--border);">
                                <label style="color:#38bdf8;">🎛️ دکمه ۱ (خرید):</label>
                                <input type="text" name="btn_1_text" value="<?php echo htmlspecialchars($btn_1_t); ?>">
                            </div>
                            <div class="form-group" style="background: rgba(15,23,42,0.4); padding: 1rem; border-radius: 12px; border: 1px solid var(--border);">
                                <label style="color:#34d399;">🎛️ دکمه ۲ (پشتیبانی):</label>
                                <input type="text" name="btn_2_text" value="<?php echo htmlspecialchars($btn_2_t); ?>" style="margin-bottom: 0.5rem;">
                                <input type="url" name="btn_2_url" value="<?php echo htmlspecialchars($btn_2_u); ?>" placeholder="https://t.me/MySupport">
                            </div>
                            <div class="form-group" style="background: rgba(15,23,42,0.4); padding: 1rem; border-radius: 12px; border: 1px solid var(--border);">
                                <label style="color:#a855f7;">🎛️ دکمه ۳ (برند):</label>
                                <input type="text" name="btn_3_text" value="<?php echo htmlspecialchars($btn_3_t); ?>" style="margin-bottom: 0.5rem;">
                                <input type="url" name="btn_3_url" value="<?php echo htmlspecialchars($btn_3_u); ?>" placeholder="https://hoomanweb.ir">
                            </div>
                        </div>

                        <!-- تنظیمات و اتوماسیون ووکامرس (گیت شده بر اساس ویژگی پلن) -->
                        <?php if (!empty($quota['features']['woocommerce'])): ?>
                            <h3 style="font-size: 0.95rem; margin-top: 1rem; margin-bottom: 0.75rem; border-bottom: 1px dashed var(--border); padding-bottom: 0.4rem; color:#a5b4fc;">🛍️ اتوماسیون هوشمند فروشگاهی ووکامرس</h3>
                            <div class="form-row" style="margin-bottom: 1rem;">
                                <label class="toggle-container" style="background: rgba(15,23,42,0.4); border: 1px solid var(--border);">
                                    <input type="checkbox" name="auto_publish_woo" value="yes" class="toggle-input" <?php echo $woo_active ? 'checked' : ''; ?>>
                                    <span>انتشار خودکار محصول جدید ووکامرس به همه‌ی کانال‌های فعال</span>
                                </label>
                                <label class="toggle-container" style="background: rgba(15,23,42,0.4); border: 1px solid var(--border);">
                                    <input type="checkbox" name="watermark_active" value="yes" class="toggle-input" <?php echo $watermark_active ? 'checked' : ''; ?>>
                                    <span>درج خودکار واترمرک روی تصاویر محصولات</span>
                                </label>
                            </div>
                        <?php endif; ?>

                        <!-- تنظیمات ارسال و دریافت -->
                        <h3 style="font-size: 0.95rem; margin-top: 1.5rem; margin-bottom: 0.75rem; border-bottom: 1px dashed var(--border); padding-bottom: 0.4rem; color:#a5b4fc;">✉️ تنظیمات ارسال و دریافت پیام</h3>
                        <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:1rem; margin-bottom: 1.5rem;">
                            <div class="form-group">
                                <label>قالب متن ارسالی به کانال‌ها:</label>
                                <select name="caption_format" style="border-radius:0.75rem;">
                                    <option value="plain" <?php echo $caption_format === 'plain' ? 'selected' : ''; ?>>متن ساده + دکمه‌های شیشه‌ای</option>
                                    <option value="html" <?php echo $caption_format === 'html' ? 'selected' : ''; ?>>متن HTML (لینک روی متن)</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>روش دریافت پیام‌ها (پاسخ خودکار):</label>
                                <select name="inbound_method" style="border-radius:0.75rem;">
                                    <option value="polling" <?php echo $inbound_method === 'polling' ? 'selected' : ''; ?>>Polling خودکار (getUpdates)</option>
                                    <option value="webhook" <?php echo $inbound_method === 'webhook' ? 'selected' : ''; ?>>وبهوک (Webhook)</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>سرعت بررسی پیام‌ها (Polling):</label>
                                <select name="poll_interval" style="border-radius:0.75rem;">
                                    <option value="every_30_seconds" <?php echo $poll_interval === 'every_30_seconds' ? 'selected' : ''; ?>>هر ۳۰ ثانیه (تقریباً بلادرنگ)</option>
                                    <option value="every_1_minute" <?php echo $poll_interval === 'every_1_minute' ? 'selected' : ''; ?>>هر ۱ دقیقه (پیشنهادی)</option>
                                    <option value="every_2_minutes" <?php echo $poll_interval === 'every_2_minutes' ? 'selected' : ''; ?>>هر ۲ دقیقه</option>
                                    <option value="every_5_minutes" <?php echo $poll_interval === 'every_5_minutes' ? 'selected' : ''; ?>>هر ۵ دقیقه</option>
                                </select>
                            </div>
                        </div>

                        <!-- تنظیمات هوش مصنوعی (گیت شده بر اساس ویژگی پلن) -->
                        <?php if (!empty($quota['features']['ai_caption'])): ?>
                            <h3 style="font-size: 0.95rem; margin-top: 1.5rem; margin-bottom: 0.75rem; border-bottom: 1px dashed var(--border); padding-bottom: 0.4rem; color:#a5b4fc;">🤖 تنظیمات هوش مصنوعی مولد کپشن</h3>
                            <div class="form-row" style="margin-bottom: 1rem;">
                                <div class="form-group">
                                    <label>سرویس هوش مصنوعی:</label>
                                    <select name="ai_provider" id="whcm-ai-provider" onchange="onAiProviderChange(this.value)" style="border-radius:0.75rem;">
                                        <option value="" <?php echo empty($ai_provider) ? 'selected' : ''; ?>>-- انتخاب سرویس --</option>
                                        <option value="openai" <?php echo $ai_provider === 'openai' ? 'selected' : ''; ?>>OpenAI (GPT)</option>
                                        <option value="gemini" <?php echo $ai_provider === 'gemini' ? 'selected' : ''; ?>>Google Gemini</option>
                                        <option value="groq" <?php echo $ai_provider === 'groq' ? 'selected' : ''; ?>>Groq (سریع و رایگان)</option>
                                        <option value="deepseek" <?php echo $ai_provider === 'deepseek' ? 'selected' : ''; ?>>DeepSeek</option>
                                        <option value="anthropic" <?php echo $ai_provider === 'anthropic' ? 'selected' : ''; ?>>Anthropic (Claude)</option>
                                        <option value="openrouter" <?php echo $ai_provider === 'openrouter' ? 'selected' : ''; ?>>OpenRouter</option>
                                        <option value="mistral" <?php echo $ai_provider === 'mistral' ? 'selected' : ''; ?>>Mistral</option>
                                        <option value="together" <?php echo $ai_provider === 'together' ? 'selected' : ''; ?>>Together AI</option>
                                        <option value="ollama" <?php echo $ai_provider === 'ollama' ? 'selected' : ''; ?>>Ollama (محلی)</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>مدل هوش مصنوعی:</label>
                                    <select id="ai-model-select" onchange="onAiModelChange(this.value)" style="border-radius:0.75rem;">
                                        <option value="<?php echo htmlspecialchars($ai_model); ?>"><?php echo htmlspecialchars($ai_model); ?></option>
                                    </select>
                                    <input type="hidden" name="ai_model" id="ai-model-hidden" value="<?php echo htmlspecialchars($ai_model); ?>">
                                </div>
                            </div>
                            <div class="form-row" style="margin-bottom: 1rem;">
                                <div class="form-group">
                                    <label>کلید API هوش مصنوعی:</label>
                                    <input type="text" name="ai_api_key" value="<?php echo htmlspecialchars($ai_key); ?>" placeholder="sk-..." class="dir-ltr">
                                </div>
                                <div class="form-group id-custom-group hidden" id="ai-custom-model-group">
                                    <label>نام مدل دلخواه:</label>
                                    <input type="text" id="ai-model-custom-input" value="<?php echo htmlspecialchars($ai_model); ?>" oninput="onAiModelChange('custom')" placeholder="مثال: custom-model-name" class="dir-ltr">
                                </div>
                            </div>
                            <div class="form-group" style="margin-bottom: 2rem;">
                                <label>آدرس اختصاصی API (Completions URL):</label>
                                <input type="url" name="ai_api_url" id="ai-url-input" value="<?php echo htmlspecialchars($ai_url); ?>" placeholder="https://api.openai.com/v1/chat/completions" class="dir-ltr">
                            </div>
                        <?php endif; ?>

                        <!-- اعلان‌های مرورگر -->
                        <div style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 0.75rem; padding: 1.25rem; margin-bottom: 1.5rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem;">
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <span style="font-size: 1.5rem;">🔔</span>
                                <div>
                                    <div style="font-weight: 700; color: var(--text-primary); font-size: 0.95rem;">اعلان‌های مرورگر</div>
                                    <div id="push-toggle-label" style="color: var(--text-muted); font-size: 0.8rem;">برای دریافت اعلان روی موبایل و دسکتاپ فعال کنید</div>
                                </div>
                            </div>
                            <button type="button" id="push-toggle-btn" style="display: flex; align-items: center; gap: 0.5rem; padding: 0.6rem 1.25rem; border-radius: 2rem; border: 1px solid var(--border-color); background: var(--bg-secondary); color: var(--text-primary); cursor: pointer; font-family: inherit; font-size: 0.85rem; font-weight: 600; white-space: nowrap; transition: all 0.2s;">
                                <span id="push-toggle-icon">🔕</span>
                                <span id="push-toggle-text">فعال‌سازی</span>
                            </button>
                        </div>

                        <button type="submit" class="btn btn-success" id="adv-save-btn" style="width:100%; padding:1rem; background: linear-gradient(135deg, var(--primary) 0%, #4f46e5 100%); border:none;">ذخیره تنظیمات پیشرفته و اتوماسیون پُست‌یار 💾✔</button>
                    </form>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- ۸. بخش خرید اشتراک -->
            <!-- ========================================== -->
            <div id="section-referral" class="tab-content">
                <?php
                    $ref_settings = \WHCM\Domain\Referral::getAdminSettings();
                    $enabled = ($ref_settings['enabled'] ?? '0') === '1';
                    $referralCode = \WHCM\Domain\Referral::getUserReferralCode($user['id']);
                    $referralLink = \WHCM\Domain\Referral::getReferralLink($user['id']);
                    $stats = \WHCM\Domain\Referral::getReferralStats($user['id']);
                    $history = \WHCM\Domain\Referral::getReferralHistory($user['id']);
                    $db = \WHCM\Core\Bootstrap::getDB();
                    $stmt_p = $db->prepare("SELECT referral_points FROM users WHERE id = ? LIMIT 1");
                    $stmt_p->execute([$user['id']]);
                    $points = (float)($stmt_p->fetch()['referral_points'] ?? 0);
                ?>
                <?php include __DIR__ . '/partials/referral-section.php'; ?>
            </div>

            <div id="section-wallet" class="tab-content">
                <?php
                    $db = \WHCM\Core\Bootstrap::getDB();
                    $stmt_p = $db->prepare("SELECT referral_points FROM users WHERE id = ? LIMIT 1");
                    $stmt_p->execute([$user['id']]);
                    $points = (float)($stmt_p->fetch()['referral_points'] ?? 0);
                    $balance = \WHCM\Domain\Wallet::getBalance($user['id']);
                    $transactions = \WHCM\Domain\Wallet::getTransactions($user['id'], 50, 0);
                ?>
                <?php include __DIR__ . '/partials/wallet-section.php'; ?>
            </div>

            <div id="section-upgrade" class="tab-content">
                <div class="card">
                    <h2>💎 ارتقا و تمدید اشتراک پنل کاربری</h2>
                    <?php if ($quota['has_active_sub']): ?>
                    <div style="background:linear-gradient(135deg, rgba(99,102,241,0.15) 0%, rgba(16,185,129,0.1) 100%); border:1px solid rgba(99,102,241,0.3); border-radius:12px; padding:1rem 1.25rem; margin-bottom:1.5rem; display:flex; align-items:center; gap:1rem; flex-wrap:wrap;">
                        <div style="width:2.5rem; height:2.5rem; background:linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:1.2rem; flex-shrink:0;">💎</div>
                        <div style="flex:1; min-width:200px;">
                            <div style="font-weight:900; color:#e2e8f0; font-size:0.95rem;">اشتراک فعلی شما: <?php echo htmlspecialchars($quota['plan_title']); ?></div>
                            <?php if ($quota['end_date']): ?>
                            <div style="font-size:0.8rem; color:#94a3b8; margin-top:0.2rem;">اعتبار تا: <?php echo \WHCM\Domain\TextFormat::mysql_to_jalali($quota['end_date'], false); ?></div>
                            <?php endif; ?>
                        </div>
                        <div style="font-size:0.75rem; color:#10b981; font-weight:800; background:rgba(16,185,129,0.15); padding:0.35rem 0.75rem; border-radius:8px; white-space:nowrap;">✅ فعال</div>
                    </div>
                    <?php endif; ?>
                    <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1.5rem;"><?php echo $quota['has_active_sub'] ? 'برای تمدید یا ارتقای اشتراک فعلی، پلن مورد نظر را انتخاب کنید:' : 'یکی از پلن‌های اشتراکی زیر را انتخاب کنید و رسید تراکنش واریزی خود را ثبت نمایید.'; ?></p>
                    
                    <?php
                        $stmt_occ = \WHCM\Core\Bootstrap::getDB()->prepare("SELECT key_value FROM settings WHERE tenant_id = 0 AND key_name = 'occasion_discount_text'");
                        $stmt_occ->execute();
                        $occ_row = $stmt_occ->fetch();
                        $occasion_discount_text = !empty($occ_row['key_value']) ? $occ_row['key_value'] : 'تخفیف مناسبتی';
                    ?>
                    <div class="plans-container">
                        <?php foreach ($plans as $p): ?>
                            <?php 
                                $is_featured = !empty($p['is_featured']);
                                $gen_discount = (int)($p['general_discount'] ?? 0);
                                $early_discount = (int)($p['early_renewal_discount'] ?? 0);
                                $is_current_plan = !empty($quota['plan_id']) && (int)$quota['plan_id'] === (int)$p['id'];
                                
                                // محاسبه قیمت بر اساس تخفیف‌های عمومی و تمدید
                                $base_price = $p['price'];
                                if ($gen_discount > 0) {
                                    $base_price = $p['price'] * (1 - ($gen_discount / 100));
                                }
                                
                                $eligible_early = false;
                                $final_price = $base_price;
                                
                                // بررسی نزدیکی به انقضا (برای دکمه تمدید)
                                $is_near_expiry = false;
                                $days_until_expiry = 999;
                                $subStmt = \WHCM\Core\Bootstrap::getDB()->prepare("SELECT end_date FROM subscriptions WHERE user_id = ? AND status = 'active' ORDER BY id DESC LIMIT 1");
                                $subStmt->execute([$user['id']]);
                                $activeSub = $subStmt->fetch();
                                if ($activeSub && strtotime($activeSub['end_date']) > time()) {
                                    $days_until_expiry = (int)round((strtotime($activeSub['end_date']) - time()) / 86400);
                                    $is_near_expiry = $days_until_expiry <= 7;
                                    if ($early_discount > 0) {
                                        $eligible_early = true;
                                        $final_price = $base_price * (1 - ($early_discount / 100));
                                    }
                                }
                            ?>
                            <div class="plan-card <?php echo $is_current_plan ? 'current-plan-locked' : ($is_featured ? 'featured-plan' : ($p['price'] > 500000 ? 'recommended' : '')); ?>" id="plan-card-<?php echo $p['id']; ?>" <?php echo ($is_current_plan && !$is_near_expiry) ? 'data-current-plan="1"' : ''; ?>>
                                <div>
                                    <div class="plan-card-img-wrapper">
                                        <img src="<?php echo \WHCM\Core\Bootstrap::getPlanImageUrl($p['image_url']); ?>" alt="<?php echo htmlspecialchars($p['title']); ?>" class="plan-card-img">
                                        
                                        <!-- برچسب‌ها و متن‌های تخفیف نئونی با افکت نوری روی تصویر -->
                                        <div class="neon-badges-overlay">
                                            <?php if (!empty($p['discount_badge_text'])): ?>
                                                <div class="neon-glow-badge neon-badge-pink">
                                                    ✨ <?php echo htmlspecialchars($p['discount_badge_text']); ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($gen_discount > 0): ?>
                                                <div class="neon-glow-badge neon-badge-emerald">
                                                    🔥 %<?php echo \WHCM\Domain\TextFormat::fa_digits($gen_discount); ?> <?php echo !empty($p['discount_badge_text']) ? htmlspecialchars($p['discount_badge_text']) : 'تخفیف ویژه'; ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($eligible_early): ?>
                                                <div class="neon-glow-badge neon-badge-cyan">
                                                    ⚡ %<?php echo \WHCM\Domain\TextFormat::fa_digits($early_discount); ?> تخفیف تمدید پیش از موعد!
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <h3><?php echo htmlspecialchars($p['title']); ?></h3>
                                    <?php if (!empty($p['description'])): ?>
                                        <p style="font-size:0.78rem; color:var(--text-muted); margin-bottom:0.75rem; line-height:1.5; text-align:right;">
                                            <?php echo nl2br(htmlspecialchars($p['description'])); ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <div class="price" style="margin-bottom: 1rem; text-align: center;">
                                        <?php if ($gen_discount > 0 || $eligible_early): ?>
                                            <span style="text-decoration: line-through; color: var(--text-muted); font-size: 0.95rem; margin-left: 0.35rem; font-weight: normal;"><?php echo \WHCM\Domain\TextFormat::fa_num($p['price']); ?></span>
                                            <span style="color: #10b981; font-size: 1.2rem; font-weight: 900;"><?php echo \WHCM\Domain\TextFormat::fa_num($final_price); ?> <span style="font-size: 0.8rem; font-weight: normal; color: var(--text-muted);">تومان</span></span>
                                        <?php else: ?>
                                            <span style="font-size: 1.25rem; font-weight: 900; color: #ffffff;"><?php echo \WHCM\Domain\TextFormat::fa_num($p['price']); ?> <span style="font-size: 0.8rem; font-weight: normal; color: var(--text-muted);">تومان</span></span>
                                        <?php endif; ?>
                                    </div>
                                    <ul>
                                        <li>⌛ مدت زمان: <strong><?php echo \WHCM\Domain\TextFormat::fa_digits($p['duration_days']); ?> روز</strong></li>
                                        <li>📻 سهمیه کانال: <strong><?php echo \WHCM\Domain\TextFormat::fa_digits($p['max_channels']); ?> کانال</strong></li>
                                        <li>📝 سهمیه پست: <strong><?php echo $p['max_posts'] === 0 ? 'نامحدود' : \WHCM\Domain\TextFormat::fa_digits($p['max_posts']) . ' پست'; ?></strong></li>
                                        <?php $feats = json_decode($p['features'] ?? '{}', true); ?>
                                        <li>📈 تحلیل آمار تفکیکی: <?php echo !empty($feats['stats']) ? '✅' : '❌'; ?></li>
                                        <li>🪙 ربات خودکار نرخ طلا: <?php echo !empty($feats['gold_ticker']) ? '✅' : '❌'; ?></li>
                                        <li>🤖 پاسخگوی کلمات کلیدی: <?php echo !empty($feats['auto_responder']) ? '✅' : '❌'; ?></li>
                                        <li>🛍 قابلیت اتصال به ووکامرس: <?php echo !empty($feats['woocommerce']) ? '✅' : '❌'; ?></li>
                                        <?php if (!empty($feats['ai_caption'])): ?>
                                            <li>🧠 کپشن‌ساز هوش مصنوعی: ✅</li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                                <?php if ($is_current_plan && !$is_near_expiry): ?>
                                <div style="width: 100%; border-radius: 12px; padding: 0.65rem; font-size: 0.85rem; font-weight: 850; background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); border: none; text-align:center; color:white; cursor:default; display:flex; align-items:center; justify-content:center; gap:0.5rem;">
                                    🔒 اشتراک فعلی شما
                                </div>
                                <?php elseif ($is_current_plan && $is_near_expiry): ?>
                                <button class="btn btn-success plan-select-btn" id="plan-btn-<?php echo $p['id']; ?>" onclick="selectPlan(<?php echo $p['id']; ?>, '<?php echo htmlspecialchars(addslashes($p['title'])); ?>', <?php echo $final_price; ?>, '<?php echo htmlspecialchars(addslashes($p['payment_url'] ?? '')); ?>')" style="width: 100%; border-radius: 12px; padding: 0.65rem; font-size: 0.85rem; font-weight: 850; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); border: none; box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);">🔄 تمدید این اشتراک (<?php echo \WHCM\Domain\TextFormat::fa_digits($days_until_expiry); ?> روز مانده)</button>
                                <?php else: ?>
                                <button class="btn btn-success plan-select-btn" id="plan-btn-<?php echo $p['id']; ?>" onclick="selectPlan(<?php echo $p['id']; ?>, '<?php echo htmlspecialchars(addslashes($p['title'])); ?>', <?php echo $final_price; ?>, '<?php echo htmlspecialchars(addslashes($p['payment_url'] ?? '')); ?>')" style="width: 100%; border-radius: 12px; padding: 0.65rem; font-size: 0.85rem; font-weight: 850; background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);">انتخاب این پلن</button>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- بخش پرداخت کارت به کارت فین‌تک هومن نقشی -->
                    <?php
                        $stmt = \WHCM\Core\Bootstrap::getDB()->prepare("SELECT key_name, key_value FROM settings WHERE tenant_id = 0 AND key_name IN ('admin_card_number', 'admin_card_holder', 'admin_bank_name')");
                        $stmt->execute();
                        $global_bank_rows = $stmt->fetchAll();
                        $global_bank = [];
                        foreach ($global_bank_rows as $row) {
                            $global_bank[$row['key_name']] = $row['key_value'];
                        }
                        $saved_card = $global_bank['admin_card_number'] ?? '۶۲۱۹-۸۶۱۰-xxxx-xxxx';
                        $saved_holder = $global_bank['admin_card_holder'] ?? 'هومن نقشی';
                        $saved_bank = $global_bank['admin_bank_name'] ?? 'بانک سامان';
                    ?>
                    <div id="payment-box" class="payment-box hidden">
                        <h4 style="margin-bottom: 0.5rem; color: #ffffff;">💳 جزئیات پرداخت پلن انتخابی</h4>
                        <h3 id="sel-title" style="color:#a5b4fc; margin-bottom:0.25rem;">...</h3>
                        <p style="font-size:1.1rem; color:#10b981; font-weight:900; margin-bottom:1rem;">مبلغ: <span id="sel-price">۰</span> <span style="font-size:0.8rem; font-weight:normal; color:var(--text-muted);">تومان</span></p>
                        
                        <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 2rem; align-items: center;">
                            <div>
                                <p style="font-size:0.85rem; color: var(--text-muted); margin-bottom:0.75rem;">برای کپی سریع شماره کارت، روی کارت بانکی زیر ضربه بزنید:</p>
                                <div class="credit-card" onclick="copyCardNumber()">
                                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                                        <span style="font-size:0.85rem; font-weight:bold; letter-spacing:1px; color:#cbd5e1;"><?php echo htmlspecialchars($saved_bank); ?></span>
                                        <span style="font-size:1.1rem;">💳</span>
                                    </div>
                                    <div class="credit-card-chip"></div>
                                    <div class="credit-card-number" id="card-num-text"><?php echo htmlspecialchars($saved_card); ?></div>
                                    <div class="credit-card-holder">
                                        <span>صاحب حساب: <?php echo htmlspecialchars($saved_holder); ?></span>
                                        <span><?php echo htmlspecialchars($saved_bank); ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- نمایش دکمه پرداخت مستقیم بلو لینک اختصاصی هر پلن -->
                            <div id="online-pay-div" class="hidden" style="text-align: center; border-right: 1px dashed var(--border); padding-right: 1.5rem;">
                                <p style="font-size:0.85rem; color: var(--text-muted); margin-bottom:1rem;">یا می‌توانید مستقیماً به صورت آنلاین از طریق بلو لینک زیر پرداخت را انجام دهید:</p>
                                <a href="#" id="online-pay-link" target="_blank" class="btn btn-success" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); border: none; padding: 1rem; width: 100%; font-size:0.95rem;">
                                    💳 پرداخت آنلاین با بلو لینک ⚡
                                </a>
                            </div>
                        </div>

                        <form action="<?php echo \WHCM\Core\Bootstrap::getRouteUrl('/dashboard/submit-payment'); ?>" method="POST" enctype="multipart/form-data" style="max-width: 480px; margin-top: 1.5rem;">
                            <?php echo $csrf_field; ?>
                            <input type="hidden" name="plan_id" id="form-plan-id">
                            <input type="hidden" name="amount" id="form-amount">
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="ref_num">کد رهگیری / شماره ارجاع تراکنش:</label>
                                    <input type="text" name="reference_num" id="ref_num" required placeholder="مثلاً: ۷۴۵۸۹۶۲۱۰">
                                </div>
                                <div class="form-group">
                                    <label for="rec-photo">بارگذاری تصویر رسید پرداخت (وب‌پی خودکار):</label>
                                    <input type="file" name="receipt_photo" id="rec-photo" accept="image/*" required style="padding: 0.5rem 1rem;">
                                </div>
                            </div>

                            <!-- دکمه اتصال مستقیم به برنامه همراه بانک بلو جهت کارت به کارت فوری -->
                            <div style="margin-bottom: 1.5rem; text-align: center;">
                                <button type="button" onclick="openBluBank()" class="btn btn-outline btn-bluebank" style="width: 100%; border-radius: 12px; font-weight: bold; font-size: 0.85rem;">
                                    🚀 کارت به کارت فوری در اپلیکیشن بلو بانک (مخصوص گوشی)
                                </button>
                            </div>

                            <button type="submit" class="btn btn-block" style="width:100%;">ثبت نهایی رسید و واریز 💳</button>
                        </form>
                    </div>
                </div>

        

                    <!-- سوابق اشتراک‌ها و پرداخت‌ها -->
                    <?php if (!empty($subscription_history) || !empty($payment_history)): ?>
                    <div class="card" style="margin-top:1.5rem;">
                        <h2 style="margin-bottom:1rem;">📋 سوابق اشتراک‌ها و پرداخت‌ها</h2>
                        <?php if (!empty($payment_history)): ?>
                        <h3 style="font-size:0.9rem; color:#94a3b8; margin-bottom:0.75rem; border-bottom:1px dashed var(--border); padding-bottom:0.4rem;">💳 تراکش‌های پرداخت</h3>
                        <div style="overflow-x:auto; max-height:300px; overflow-y:auto;">
                        <table style="width:100%; font-size:0.8rem;"><thead><tr style="border-bottom:1px solid var(--border);"><th style="padding:0.5rem; text-align:right;">پلن</th><th style="padding:0.5rem; text-align:right;">مبلغ</th><th style="padding:0.5rem; text-align:right;">وضعت</th><th style="padding:0.5rem; text-align:right;">تاریخ</th></tr></thead><tbody>
                                <?php foreach ($payment_history as $ph): ?>
                                <tr style="border-bottom:1px solid rgba(51,65,85,0.3);"><td style="padding:0.5rem;"><?php echo htmlspecialchars($ph['plan_title'] ?? '-'); ?></td><td style="padding:0.5rem;"><?php echo \WHCM\Domain\TextFormat::fa_num($ph['amount']); ?> تومان</td><td style="padding:0.5rem;"><?php if ($ph['status']==='approved') echo '<span class="badge badge-success" style="font-size:0.7rem;">تاید شده ✔</span>'; elseif ($ph['status']==='rejected') echo '<span class="badge badge-failed" style="font-size:0.7rem;">رد شده ✖</span>'; else echo '<span class="badge badge-pending" style="font-size:0.7rem;">در انتظار تاید ⏳</span>'; ?></td><td style="padding:0.5rem; color:var(--text-muted); font-size:0.75rem;"><?php echo \WHCM\Domain\TextFormat::mysql_to_jalali($ph['created_at']); ?></td></tr>
                                <?php endforeach; ?>
                            </tbody></table></div>
                        <?php endif; ?>
                        <?php if (!empty($subscription_history)): ?>
                        <h3 style="font-size:0.9rem; color:#94a3b8; margin:1.25rem 0 0.75rem; border-bottom:1px dashed var(--border); padding-bottom:0.4rem;">💎 سابق اشتراک‌ها</h3>
                        <div style="overflow-x:auto; max-height:300px; overflow-y:auto;">
                        <table style="width:100%; font-size:0.8rem;"><thead><tr style="border-bottom:1px solid var(--border);"><th style="padding:0.5rem; text-align:right;">پلن</th><th style="padding:0.5rem; text-align:right;">شروع</th><th style="padding:0.5rem; text-align:right;">پاین</th><th style="padding:0.5rem; text-align:right;">وضعت</th></tr></thead><tbody>
                                <?php foreach ($subscription_history as $sh): ?>
                                <tr style="border-bottom:1px solid rgba(51,65,85,0.3);"><td style="padding:0.5rem;"><?php echo htmlspecialchars($sh['plan_title'] ?? '-'); ?></td><td style="padding:0.5rem; font-size:0.75rem;"><?php echo \WHCM\Domain\TextFormat::mysql_to_jalali($sh['start_date'], false); ?></td><td style="padding:0.5rem; font-size:0.75rem;"><?php echo \WHCM\Domain\TextFormat::mysql_to_jalali($sh['end_date'], false); ?></td><td style="padding:0.5rem;"><?php if ($sh['status']==='active') echo '<span class="badge badge-success" style="font-size:0.7rem;">فعال ✔</span>'; else echo '<span class="badge badge-failed" style="font-size:0.7rem;">منقضی ✖</span>'; ?></td></tr>
                                <?php endforeach; ?>
                            </tbody></table></div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
</main>
    </div>

    <script>
        window.postyarBaseUrl = '<?php echo $baseUrl; ?>';
        window.__csrfToken = '<?php echo \WHCM\Core\Csrf::getToken(); ?>';
    </script>
    <script src="<?php echo \WHCM\Core\Bootstrap::getAssetsUrl(); ?>/js/utils.js?v=13"></script>
    <script src="<?php echo \WHCM\Core\Bootstrap::getAssetsUrl(); ?>/js/dashboard.js?v=13"></script>
    <script src="<?php echo \WHCM\Core\Bootstrap::getAssetsUrl(); ?>/js/jalalidatepicker.min.js?v=13"></script>
    <script>
        window.__dashboardSavedCard = "<?php echo htmlspecialchars($saved_card); ?>";
        // مقداردهی اولیه دراپ‌داون هوش مصنوعی
        (function() {
            var provSelect = document.getElementById('whcm-ai-provider');
            if (provSelect && provSelect.value && typeof onAiProviderChange === 'function') {
                onAiProviderChange(provSelect.value);
            }
        })();
        if (typeof jalaliDatepicker !== 'undefined') {
            try {
                jalaliDatepicker.startWatch({
                    minDate: "today",
                    showTodayBtn: true,
                    showEmptyBtn: false
                });
            } catch (e) {}
        }
    </script>

    <!-- مدال گفتگو و مدیریت حرفه‌ای تیکت توسط مستأجر -->
    <div id="ticketModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.85); z-index:1200; align-items:center; justify-content:center; padding:1rem; overflow-y:auto;">
        <div class="card" style="width:100%; max-width:580px; margin:auto; position:relative; background:#0f172a; border:1px solid #4f46e5; border-radius:16px; box-shadow:0 20px 50px rgba(0,0,0,0.8);">
            <button onclick="closeTicketModal()" style="position:absolute; top:15px; left:15px; background:none; border:none; color:#94a3b8; font-size:1.4rem; cursor:pointer;">✖</button>
            
            <div style="border-bottom:1px dashed #334155; padding-bottom:1rem; margin-bottom:1.25rem;">
                <span id="t-modal-status" class="badge" style="float:left; margin-top:2px;"></span>
                <h3 id="t-modal-subject" style="color:white; margin:0; font-size:1.15rem; font-weight:900;"></h3>
            </div>

            <div id="t-modal-body" style="display:flex; flex-direction:column; gap:1rem; max-height:350px; overflow-y:auto; padding-right:0.5rem; margin-bottom:1.5rem;">
                <!-- گفتگوهای تیکت در اینجا به صورت چت درج می‌شوند -->
            </div>

            <!-- فرم ارسال پاسخ به تیکت توسط کاربر -->
            <form action="<?php echo \WHCM\Core\Bootstrap::getRouteUrl('/dashboard/reply-ticket'); ?>" method="POST" enctype="multipart/form-data" style="margin-bottom:1rem;">
                <?php echo $csrf_field; ?>
                <input type="hidden" name="ticket_id" id="t-reply-id">
                <div class="form-group" style="margin-bottom:0.75rem;">
                    <textarea name="reply" rows="3" required placeholder="پاسخ یا توضیحات تکمیلی خود را بنویسید..." style="width:100%; border-radius:10px; background:#1e293b; color:white; border:1px solid #334155; padding:0.75rem;"></textarea>
                </div>
                <div class="form-group">
                    <label style="font-size:0.8rem; color:#94a3b8;">پیوست تصویر (اختیاری):</label>
                    <input type="file" name="attachment" accept="image/*,.pdf" style="padding:0.4rem; font-size:0.8rem;">
                </div>
                <label style="display:flex; align-items:center; gap:0.5rem; font-size:0.85rem; color:#fbbf24; margin-bottom:0.75rem; cursor:pointer;"><input type="checkbox" name="close_after_reply" value="1"> ارسال و بستن همزمان تیکت</label>
                <button type="submit" class="btn btn-success" style="width:100%; padding:0.75rem;">ارسال پاسخ جدید به پشتیبانی ✔</button>
            </form>

            <!-- دکمه بستن تیکت توسط کاربر -->
            <form action="<?php echo \WHCM\Core\Bootstrap::getRouteUrl('/dashboard/close-ticket'); ?>" method="POST" style="margin:0;">
                <?php echo $csrf_field; ?>
                <input type="hidden" name="ticket_id" id="t-close-id">
                <button type="submit" class="btn btn-danger" style="width:100%; padding:0.6rem; font-size:0.85rem; background:rgba(239, 68, 68, 0.2); border:1px solid #ef4444; color:#ef4444;">بستن این تیکت (مختومه کردن)</button>
            </form>
        </div>
    </div>

    <!-- PWA: ثبت سرویس ورکر و بنر نصب (فقط موبایل/تبلت) -->
    <script>
    (function(){
        var baseUrl = '<?php echo $baseUrl; ?>';
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register(baseUrl + '/service-worker.js', { scope: baseUrl + '/' })
                .then(function(reg) {
                    reg.addEventListener('updatefound', function() {
                        var newWorker = reg.installing;
                        newWorker.addEventListener('statechange', function() {
                            if (newWorker.state === 'activated') {
                                console.log('[PWA] نسخه جدید سرویس ورکر فعال شد');
                            }
                        });
                    });
                })
                .catch(function(err) {
                    console.warn('[PWA] خطا در ثبت سرویس ورکر:', err.message);
                });
        }
    })();
    </script>
    <script defer src="<?php echo \WHCM\Core\Bootstrap::getAssetsUrl(); ?>/js/push.js?v=13"></script>
    <script defer src="<?php echo \WHCM\Core\Bootstrap::getAssetsUrl(); ?>/js/pwa-install.js?v=13"></script>
    <script>
    /* اجرای قلب تپنده پس از بارگذاری */
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        setTimeout(postyarHeartbeat, 2000);
    } else {
        window.addEventListener('DOMContentLoaded', function() { setTimeout(postyarHeartbeat, 2000); });
    }
    setInterval(postyarHeartbeat, 60000);
    </script>
</body>
</html>