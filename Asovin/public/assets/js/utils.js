/* ==========================================================================
   فایل توابع مشترک و قابل استفاده مجدد (Shared Utility Functions)
   سامانه مدیریت کانال‌ها و انتشار خودکار پُست‌یار
   ========================================================================== */

/* کنترلر ذخیره‌سازی ایمن جهت ممانعت از کرش در مرورگرهای قدیمی و پرایوت */
var SafeStorage = {
    getItem: function(key, defaultValue) {
        try {
            return sessionStorage.getItem(key) || defaultValue;
        } catch (e) {
            return defaultValue;
        }
    },
    setItem: function(key, value) {
        try {
            sessionStorage.setItem(key, value);
        } catch (e) {
            // نادیده گرفتن خطای پرایوت مرورگر
        }
    }
};

/* تبدیل اعداد لاتین به فارسی */
function toFaDigits(num) {
    var str = num.toString();
    var fa = ['\u06F0','\u06F1','\u06F2','\u06F3','\u06F4','\u06F5','\u06F6','\u06F7','\u06F8','\u06F9'];
    return str.replace(/[0-9]/g, function(w){ return fa[+w]; });
}

/* استخراج بخش تاریخ از رشته کامل datetime */
function toPersianDateStr(dtStr) {
    if (!dtStr || dtStr.indexOf('2099') !== -1) return "\u0628\u062F\u0648\u0646 \u0627\u0646\u0642\u0636\u0627 / \u062F\u0627\u0626\u0645\u06CC";
    return dtStr.split(' ')[0];
}

/* حذف خودکار اعلان (Alert Toast Auto-Dismiss) */
function autoDismissAlert(elementId, timeoutMs) {
    timeoutMs = timeoutMs || 5000;
    setTimeout(function() {
        var toast = document.getElementById(elementId);
        if (toast) {
            toast.style.opacity = '0';
            toast.style.transition = 'opacity 0.6s ease';
            setTimeout(function() { toast.style.display = 'none'; }, 600);
        }
    }, timeoutMs);
}

/* تبدیل خودکار تمام اعداد لاتین نمایشی به فارسی */
function autoConvertToPersianDigits() {
    var walk = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT, null, false);
    var node;
    var skips = ['SCRIPT', 'STYLE', 'INPUT', 'TEXTAREA', 'CODE', 'PRE'];
    while (node = walk.nextNode()) {
        if (skips.indexOf(node.parentElement.tagName) !== -1) continue;
        if (skips.indexOf(node.parentNode.tagName) !== -1) continue;
        var original = node.nodeValue;
        if (/[0-9]/.test(original)) {
            node.nodeValue = toFaDigits(original);
        }
    }
}
