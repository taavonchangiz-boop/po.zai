<?php
namespace WHCM\Domain;

/**
 * مدیریت قالب‌بندی متون، تبدیل ارقام فارسی و تقویم جلالی (شمسی)
 *
 * @package WHCM\Domain
 */
class TextFormat {

    /**
     * تبدیل عدد به ارقام فارسی + جداکننده هزارگان (فقط برای قیمت).
     */
    public static function fa_num($num): string {
        $num = number_format((float) $num, 0, '.', ',');
        return self::fa_digits((string) $num);
    }

    /**
     * تبدیل ارقام لاتین به فارسی (بدون جداکننده) — برای تاریخ/ساعت/کدها.
     */
    public static function fa_digits(string $str): string {
        $fa = array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹');
        $en = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
        return str_replace($en, $fa, $str);
    }

    /**
     * تبدیل ارقام فارسی/عربی به لاتین و حذف جداکننده و واحد.
     * هر چیزی به‌جز رقم و ممیز اعشاری حذف می‌شود.
     */
    public static function en_num($val): string {
        $map = array(
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        );
        $val = strtr((string) $val, $map);
        $val = str_replace(array(',', '٬', '،', ' '), '', $val);
        // حذف هر چیز غیر از رقم و ممیز (مثل «تومان»، «دلار»)
        $val = preg_replace('/[^0-9.]/', '', $val);
        return $val;
    }

    /**
     * تبدیل تاریخ میلادی به شمسی (الگوریتم jalaali کاملاً بهینه و تست‌شده).
     * خروجی: [سال، ماه، روز]
     */
    public static function g2j(int $gy, int $gm, int $gd): array {
        $g_d_m = array(0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334);
        $jy    = ($gy <= 1600) ? 0 : 979;
        $gy   -= ($gy <= 1600) ? 621 : 1600;
        $gy2   = ($gm > 2) ? ($gy + 1) : $gy;
        $days  = (365 * $gy) + (int)(($gy2 + 3) / 4) - (int)(($gy2 + 99) / 100) + (int)(($gy2 + 399) / 400) - 80 + $gd + $g_d_m[$gm - 1];
        $jy   += 33 * (int)($days / 12053);
        $days %= 12053;
        $jy   += 4 * (int)($days / 1461);
        $days %= 1461;
        if ($days > 365) {
            $jy  += (int)(($days - 1) / 365);
            $days = ($days - 1) % 365;
        }
        if ($days < 186) {
            $jm = 1 + (int)($days / 31);
            $jd = 1 + ($days % 31);
        } else {
            $jm = 7 + (int)(($days - 186) / 30);
            $jd = 1 + (($days - 186) % 30);
        }
        return array($jy, $jm, $jd);
    }

    /**
     * تاریخ و ساعت شمسی فارسی برای نمایش زنده (بدون جداکننده، ساعت دقیق ۲۴ساعته).
     */
    public static function now_jalali(): string {
        $gy = (int)date('Y');
        $gm = (int)date('n');
        $gd = (int)date('j');
        $j  = self::g2j($gy, $gm, $gd);
        $h  = date('H');
        $i  = date('i');
        return self::fa_digits($j[0] . '/' . str_pad($j[1], 2, '0', STR_PAD_LEFT) . '/' . str_pad($j[2], 2, '0', STR_PAD_LEFT) . ' - ' . $h . ':' . $i);
    }

    /**
     * لیست ماه‌های شمسی (برای نمایش).
     */
    public static function jalali_month_name(int $m): string {
        $names = array('فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند');
        return isset($names[$m - 1]) ? $names[$m - 1] : '';
    }

    /**
     * قالب‌بندی قیمت برای نمایش در کانال.
     */
    public static function format_price($value, string $type, array $settings): string {
        $value = self::en_num($value);
        if (!is_numeric($value)) {
            return '';
        }
        $num = (float) $value;

        // واحد پول قیمت‌های دریافتی از API
        $currency = !empty($settings['gold_currency']) ? $settings['gold_currency'] : 'toman';

        if ('oz' === $type) {
            // انس جهانی با واحد دلار
            return self::fa_num($num) . ' دلار';
        }

        // طلا و سکه: اگر ریال است به تومان تبدیل کن (تقسیم بر ۱۰)
        if ('rial' === $currency) {
            $num = $num / 10;
        }
        return self::fa_num($num) . ' تومان';
    }

    /**
     * تبدیل رشته تاریخ میلادی MySQL به رشته تاریخ شمسی فارسی (سازگار و زیبا)
     *
     * @param string $mysql_date تاریخ ذخیره‌شده در دیتابیس
     * @param bool $from_utc اگر true باشد، ورودی به عنوان UTC تفسیر و به Asia/Tehran تبدیل می‌شود
     *                       (برای فیلدهایی مثل created_at که توسط CURRENT_TIMESTAMP که UTC هستند)
     *                       اگر false باشد، ورودی مستقیماً بدون تبدیل منطقه زمانی استفاده می‌شود
     *                       (برای فیلدهایی مثل scheduled_at, end_date که توسط PHP با منطقه Asia/Tehran ذخیره شده‌اند)
     */
    public static function mysql_to_jalali(string $mysql_date, bool $from_utc = true): string {
        if (empty($mysql_date)) {
            return '';
        }

        if ($from_utc) {
            // فیلدهای تولیدشده توسط CURRENT_TIMESTAMP در SQLite همیشه UTC هستند.
            // ابتدا به عنوان UTC پارس کرده، سپس به Asia/Tehran تبدیل می‌کنیم.
            try {
                $dt = new \DateTime($mysql_date, new \DateTimeZone('UTC'));
                $dt->setTimezone(new \DateTimeZone('Asia/Tehran'));
                $timestamp = $dt->getTimestamp();
            } catch (\Throwable $e) {
                $timestamp = strtotime($mysql_date);
            }
        } else {
            // فیلدهای تنظیم‌شده توسط PHP (date()) که از قبل در Asia/Tehran هستند
            $timestamp = strtotime($mysql_date);
        }

        if (!$timestamp) {
            return $mysql_date;
        }
        $gy = (int)date('Y', $timestamp);
        $gm = (int)date('m', $timestamp);
        $gd = (int)date('d', $timestamp);
        $j = self::g2j($gy, $gm, $gd);
        $h = date('H', $timestamp);
        $i = date('i', $timestamp);
        return self::fa_digits($j[0] . '/' . str_pad($j[1], 2, '0', STR_PAD_LEFT) . '/' . str_pad($j[2], 2, '0', STR_PAD_LEFT) . ' - ' . $h . ':' . $i);
    }

    /**
     * نمایش زمان نسبی (مثلا «۲ دقیقه پیش»، «۱ ساعت پیش»)
     */
    public static function timeAgo(string $datetime): string {
        if (empty($datetime)) return '';
        try {
            $dt = new \DateTime($datetime, new \DateTimeZone('Asia/Tehran'));
            $now = new \DateTime('now', new \DateTimeZone('Asia/Tehran'));
            $diff = $now->getTimestamp() - $dt->getTimestamp();
            if ($diff < 0) $diff = 0;

            if ($diff < 60) return 'همین الان';
            if ($diff < 3600) {
                $m = (int)($diff / 60);
                return self::fa_digits($m) . ' دقیقه پیش';
            }
            if ($diff < 86400) {
                $h = (int)($diff / 3600);
                return self::fa_digits($h) . ' ساعت پیش';
            }
            if ($diff < 604800) {
                $d = (int)($diff / 86400);
                return self::fa_digits($d) . ' روز پیش';
            }
            // برای بازه‌های طولانی‌تر، تاریخ شمسی نمایش داده شود
            return self::mysql_to_jalali($datetime, false);
        } catch (\Throwable $e) {
            return '';
        }
    }
}
