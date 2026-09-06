<?php
/**
 * Web App Manifest — داینامیک با URL‌های صحیح
 * این فایل به جای manifest.json استاتیک استفاده می‌شود
 */

// بارگذاری حداقلی بوت‌استرپ برای دسترسی به getAssetsUrl()
try {
    require_once __DIR__ . '/../app/Core/Bootstrap.php';
    \WHCM\Core\Bootstrap::run();
} catch (\Throwable $e) {
    // فال‌بک: آدرس‌های نسبی
    $assetsUrl = '/assets';
    $baseUrl = '';
}

if (class_exists('\\WHCM\\Core\\Bootstrap')) {
    $assetsUrl = \WHCM\Core\Bootstrap::getAssetsUrl();
    $baseUrl = rtrim(str_replace(['/assets', '/public/assets'], '', $assetsUrl), '/');
}

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] === 443 ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$fullBase = $protocol . '://' . $host . $baseUrl;

$manifest = [
    'name'             => 'پُست‌یار | سامانه هوشمند مدیریت کانال‌ها',
    'short_name'       => 'پُست‌یار',
    'description'      => 'پُست‌یار - ابزار هوشمند مدیریت، زمان‌بندی شمسی، انتشار چندکاناله در تلگرام و بله، ربات خودکار نرخ طلا و سکه، پاسخگوی کلمات کلیدی و اتصال به ووکامرس.',
    'start_url'        => $fullBase . '/',
    'scope'            => $fullBase . '/',
    'display'          => 'standalone',
    'orientation'      => 'any',
    'background_color' => '#0a0a0a',
    'theme_color'      => '#6366f1',
    'dir'              => 'rtl',
    'lang'             => 'fa-IR',
    'categories'       => ['business', 'productivity'],
    'prefer_related_applications' => false,
    'icons' => [
        [
            'src'     => $assetsUrl . '/icons/icon-72x72.png',
            'sizes'   => '72x72',
            'type'    => 'image/png',
            'purpose' => 'any'
        ],
        [
            'src'     => $assetsUrl . '/icons/icon-96x96.png',
            'sizes'   => '96x96',
            'type'    => 'image/png',
            'purpose' => 'any'
        ],
        [
            'src'     => $assetsUrl . '/icons/icon-128x128.png',
            'sizes'   => '128x128',
            'type'    => 'image/png',
            'purpose' => 'any'
        ],
        [
            'src'     => $assetsUrl . '/icons/icon-144x144.png',
            'sizes'   => '144x144',
            'type'    => 'image/png',
            'purpose' => 'any'
        ],
        [
            'src'     => $assetsUrl . '/icons/icon-152x152.png',
            'sizes'   => '152x152',
            'type'    => 'image/png',
            'purpose' => 'any'
        ],
        [
            'src'     => $assetsUrl . '/icons/icon-192x192.png',
            'sizes'   => '192x192',
            'type'    => 'image/png',
            'purpose' => 'any'
        ],
        [
            'src'     => $assetsUrl . '/icons/icon-384x384.png',
            'sizes'   => '384x384',
            'type'    => 'image/png',
            'purpose' => 'any'
        ],
        [
            'src'     => $assetsUrl . '/icons/icon-512x512.png',
            'sizes'   => '512x512',
            'type'    => 'image/png',
            'purpose' => 'any'
        ],
        // آیکون‌های maskable — با پدینگ ایمن ۸۰٪
        [
            'src'     => $assetsUrl . '/icons/icon-192x192-maskable.png',
            'sizes'   => '192x192',
            'type'    => 'image/png',
            'purpose' => 'maskable'
        ],
        [
            'src'     => $assetsUrl . '/icons/icon-512x512-maskable.png',
            'sizes'   => '512x512',
            'type'    => 'image/png',
            'purpose' => 'maskable'
        ]
    ],
    'screenshots' => [],
    'shortcuts' => [
        [
            'name'        => 'داشبورد',
            'url'         => $fullBase . '/index.php?route=/dashboard',
            'description' => 'ورود به داشبورد مدیریت'
        ]
    ]
];

header('Content-Type: application/manifest+json');
header('Cache-Control: public, max-age=3600');
echo json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
