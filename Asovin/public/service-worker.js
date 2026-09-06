const CACHE_NAME = 'postyar-pwa-v5';

// مسیر پایه پویا — از لوکیشن خود سرویس ورکر استخراج می‌شود
// چون SW در کنار index.php قرار دارد، مسیر آن برابر scope است
const SW_PATH = new URL('./', self.location).pathname.replace(/\/$/, '');

// ساخت آدرس کامل دارایی‌ها
function assetUrl(path) {
    return SW_PATH + '/assets/' + path;
}

// فایل‌های استاتیک برای پیش‌کش (نسبی)
const STATIC_ASSETS = [
    'css/admin.css',
    'css/dashboard.css',
    'css/home.css',
    'css/components.css',
    'js/admin.js',
    'js/dashboard.js',
    'js/home.js',
    'js/utils.js',
    'js/pwa-install.js',
    'js/push.js',
    'images/logo.webp',
    'images/logo-full.webp',
    'images/logo-white-bg.webp',
    'icons/icon-192x192.png',
    'icons/icon-512x512.png',
    'icons/apple-touch-icon.png',
    'icons/favicon-32x32.png',
    'fonts/Vazirmatn-Regular.woff2',
    'fonts/Vazirmatn-Bold.woff2',
    'fonts/Vazirmatn-Medium.woff2'
];

// نصب سرویس ورکر و کش فایل‌های استاتیک
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => cache.addAll(STATIC_ASSETS.map(p => assetUrl(p))))
            .then(() => self.skipWaiting())
            .catch(err => {
                console.log('[SW] Pre-cache partial:', err.message);
                return self.skipWaiting();
            })
    );
});

// فعال‌سازی و پاکسازی کش‌های قدیمی
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames
                    .filter(cache => cache !== CACHE_NAME)
                    .map(cache => caches.delete(cache))
            );
        }).then(() => self.clients.claim())
    );
});

// آیا این درخواست فایل استاتیک است؟
function isStaticRequest(url) {
    const exts = ['.css', '.js', '.webp', '.png', '.jpg', '.jpeg', '.svg', '.woff2', '.woff', '.ttf', '.eot'];
    return exts.some(ext => url.pathname.endsWith(ext));
}

// استراتژی کش: Cache First برای استاتیک، Network Only برای دینامیک
self.addEventListener('fetch', event => {
    const url = new URL(event.request.url);

    // فقط GET
    if (event.request.method !== 'GET') return;

    // فایل‌های استاتیک — Cache First با به‌روزرسانی پس‌زمینه‌ای
    if (isStaticRequest(url)) {
        event.respondWith(
            caches.match(event.request).then(cached => {
                // در صورت وجود کش، همزمان به‌روزرسانی پس‌زمینه‌ای
                const fetchPromise = fetch(event.request).then(response => {
                    if (response.ok) {
                        const clone = response.clone();
                        caches.open(CACHE_NAME).then(cache => cache.put(event.request, clone));
                    }
                    return response;
                }).catch(() => cached);

                return cached || fetchPromise;
            })
        );
        return;
    }

    // صفحات HTML و API — همیشه Network
    event.respondWith(
        fetch(event.request).catch(() => {
            if (event.request.mode === 'navigate') {
                return caches.match(SW_PATH + '/') || caches.match(SW_PATH + '/index.php');
            }
            return new Response('آفلاین هستید', { status: 503, statusText: 'Service Unavailable' });
        })
    );
});

// ===== Web Push — دریافت و نمایش اعلان =====
self.addEventListener('push', event => {
    let data = {
        title: 'پُست‌یار',
        body: 'شما یک اعلان جدید دارید.',
        url: SW_PATH + '/'
    };

    if (event.data) {
        try {
            const parsed = event.data.json();
            data = { ...data, ...parsed };
        } catch (e) {
            data.body = event.data.text();
        }
    }

    const options = {
        body: data.body,
        icon: assetUrl('icons/icon-192x192.png'),
        badge: assetUrl('icons/icon-72x72.png'),
        vibrate: [100, 50, 100],
        dir: 'rtl',
        lang: 'fa',
        data: { url: data.url || SW_PATH + '/' }
    };

    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});

// باز کردن لینک مربوطه هنگام کلیک روی اعلان
self.addEventListener('notificationclick', event => {
    event.notification.close();
    const targetUrl = event.notification.data?.url || SW_PATH + '/';
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(windowClients => {
            for (const client of windowClients) {
                if (client.url.includes(targetUrl) && 'focus' in client) {
                    return client.focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }
        })
    );
});
