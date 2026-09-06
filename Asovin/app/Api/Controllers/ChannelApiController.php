<?php
namespace WHCM\Api\Controllers;

use WHCM\Api\MobileApiResponse;
use WHCM\Domain\ChannelManager;
use WHCM\Core\Bootstrap;

/**
 * کنترلر API مدیریت کانال‌ها
 *
 * عملیات CRUD کانال‌ها برای اپلیکیشن موبایل
 *
 * @package WHCM\Api\Controllers
 */
class ChannelApiController extends \WHCM\Api\MobileApiController {

    /**
     * دریافت لیست کانال‌های کاربر
     * GET /api/v1/channels (auth)
     */
    public function index(): void {
        $tenant_id = $this->userId();
        $channels = ChannelManager::getTenantChannels($tenant_id);
        MobileApiResponse::success($channels);
    }

    /**
     * افزودن کانال جدید
     * POST /api/v1/channels (auth)
     *
     * Input: name (required), platform (required), channel_id (required), token (required)
     */
    public function store(): void {
        $tenant_id = $this->userId();
        $input = $this->input();

        // اعتبارسنجی فیلدهای الزامی
        $errors = $this->validate([
            'name'       => 'required',
            'platform'   => 'required',
            'channel_id' => 'required',
            'token'      => 'required',
        ], $input);

        if (!empty($errors)) {
            MobileApiResponse::validationError($errors);
        }

        $name       = trim($input['name']);
        $platform   = trim($input['platform']);
        $channel_id = trim($input['channel_id']);
        $token      = trim($input['token']);

        $result = ChannelManager::addChannel($name, $platform, $channel_id, $token);

        if (!$result['success']) {
            MobileApiResponse::error($result['message']);
        }

        // بازگرداندن کانال جدید ایجاد شده
        $channels = ChannelManager::getTenantChannels($tenant_id);
        $newChannel = null;
        foreach ($channels as $ch) {
            if ($ch['channel_id'] === $channel_id && $ch['platform'] === $platform) {
                $newChannel = $ch;
                break;
            }
        }

        MobileApiResponse::success($newChannel, $result['message']);
    }

    /**
     * دریافت اطلاعات یک کانال
     * GET /api/v1/channels/{id} (auth)
     *
     * @param string $id شناسه کانال از مسیر
     */
    public function show(string $id): void {
        $tenant_id = $this->userId();
        $channel = ChannelManager::getChannel((int)$id, $tenant_id);

        if (!$channel) {
            MobileApiResponse::notFound('کانال مورد نظر یافت نشد.');
        }

        MobileApiResponse::success($channel);
    }

    /**
     * بروزرسانی کانال
     * PUT /api/v1/channels/{id} (auth)
     *
     * @param string $id شناسه کانال از مسیر
     */
    public function update(string $id): void {
        $tenant_id = $this->userId();
        $channelId = (int)$id;

        // دریافت کانال فعلی
        $channel = ChannelManager::getChannel($channelId, $tenant_id);
        if (!$channel) {
            MobileApiResponse::notFound('کانال مورد نظر یافت نشد.');
        }

        $input = $this->input();

        // اعتبارسنجی فیلدهای الزامی
        $errors = $this->validate([
            'name'          => 'required',
            'platform'      => 'required',
            'channel_id_val'=> 'required',
            'token'         => 'required',
        ], $input);

        if (!empty($errors)) {
            MobileApiResponse::validationError($errors);
        }

        $name       = trim($input['name']);
        $platform   = trim($input['platform']);
        $channel_id = trim($input['channel_id_val']);
        $token      = trim($input['token']);

        // پردازش ساختار لینک‌های سه‌گانه
        $links = [
            ['name' => trim($input['link_name_1'] ?? ''), 'url' => trim($input['link_url_1'] ?? '')],
            ['name' => trim($input['link_name_2'] ?? ''), 'url' => trim($input['link_url_2'] ?? '')],
            ['name' => trim($input['link_name_3'] ?? ''), 'url' => trim($input['link_url_3'] ?? '')],
        ];

        // پردازش دکمه‌های شیشه‌ای تعاملی
        $buttons_active = !empty($input['buttons_active']);
        $buttons = [
            ['text' => trim($input['btn_text_1'] ?? ''), 'url' => trim($input['btn_url_1'] ?? '')],
            ['text' => trim($input['btn_text_2'] ?? ''), 'url' => trim($input['btn_url_2'] ?? '')],
        ];

        $button_config = [
            'active'  => $buttons_active,
            'buttons' => $buttons,
        ];

        $db = Bootstrap::getDB();

        // اگر آیدی کانال یا پلتفرم عوض شده باشد، بررسی ضد تقلب در channel_registry
        if ($channel['channel_id'] !== $channel_id || $channel['platform'] !== $platform) {
            $stmt = $db->prepare("SELECT owner_user_id FROM channel_registry WHERE platform = ? AND channel_id = ? LIMIT 1");
            $stmt->execute([$platform, $channel_id]);
            $reg = $stmt->fetch();

            if ($reg && (int)$reg['owner_user_id'] !== $tenant_id) {
                MobileApiResponse::error('این شناسه کانال قبلاً توسط کاربر دیگری ثبت شده و قفل است.');
            }

            if (!$reg) {
                // ثبت در رجیستری جهانی
                $stmt = $db->prepare("INSERT INTO channel_registry (platform, channel_id, owner_user_id) VALUES (?, ?, ?)");
                $stmt->execute([$platform, $channel_id, $tenant_id]);
            }
        }

        // بروزرسانی کانال
        $stmt = $db->prepare("
            UPDATE channels
            SET name = ?, platform = ?, channel_id = ?, token = ?, link_config = ?, button_config = ?
            WHERE id = ? AND tenant_id = ?
        ");
        $stmt->execute([
            $name,
            $platform,
            $channel_id,
            $token,
            json_encode($links, JSON_UNESCAPED_UNICODE),
            json_encode($button_config, JSON_UNESCAPED_UNICODE),
            $channelId,
            $tenant_id,
        ]);

        // بازگرداندن کانال بروزرسانی شده
        $updated = ChannelManager::getChannel($channelId, $tenant_id);
        MobileApiResponse::success($updated, 'تنظیمات کانال با موفقیت بروزرسانی شد.');
    }

    /**
     * حذف کانال
     * DELETE /api/v1/channels/{id} (auth)
     *
     * @param string $id شناسه کانال از مسیر
     */
    public function delete(string $id): void {
        $tenant_id = $this->userId();
        $channelId = (int)$id;

        // ابتدا بررسی مالکیت
        $channel = ChannelManager::getChannel($channelId, $tenant_id);
        if (!$channel) {
            MobileApiResponse::notFound('کانال مورد نظر یافت نشد.');
        }

        $deleted = ChannelManager::deleteChannel($channelId);

        if (!$deleted) {
            MobileApiResponse::error('خطا در حذف کانال.');
        }

        MobileApiResponse::success(null, 'کانال با موفقیت حذف گردید.');
    }
}
