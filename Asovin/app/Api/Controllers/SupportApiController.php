<?php
namespace WHCM\Api\Controllers;

use WHCM\Api\MobileApiResponse;
use WHCM\Core\Bootstrap;

/**
 * کنترلر API پشتیبانی و تیکت‌ها
 *
 * شامل: لیست تیکت‌ها، ایجاد تیکت، مشاهده تیکت، پاسخ به تیکت
 *
 * @package WHCM\Api\Controllers
 */
class SupportApiController extends \WHCM\Api\MobileApiController {

    /**
     * آپلود فایل پیوست تیکت
     */
    private function handleAttachment(string $field = 'attachment'): ?string {
        if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $file = $_FILES[$field];
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            MobileApiResponse::error('فرمت فایل پیوست مجاز نیست. فقط JPG, PNG, GIF, WebP, PDF مجاز است.', 400);
        }

        $maxSize = 5 * 1024 * 1024; // 5MB
        if ($file['size'] > $maxSize) {
            MobileApiResponse::error('حجم فایل پیوست بیش از ۵ مگابایت است.', 400);
        }

        $dir = __DIR__ . '/../../../public/assets/tickets/';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = uniqid('ticket_') . '.' . $ext;
        $filepath = $dir . '/' . $filename;

        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return '/assets/tickets/' . $filename;
        }

        return null;
    }

    /**
     * دریافت لیست تیکت‌های کاربر
     * GET /api/v1/tickets (auth)
     */
    public function index(): void {
        $userId = $this->userId();
        $db     = $this->db();

        $stmt = $db->prepare("SELECT * FROM tickets WHERE user_id = ? ORDER BY id DESC");
        $stmt->execute([$userId]);
        $tickets = $stmt->fetchAll();

        // تعداد پاسخ‌ها برای هر تیکت
        foreach ($tickets as &$ticket) {
            $ticketId = (int)$ticket['id'];
            $stmt = $db->prepare("SELECT COUNT(*) as reply_count FROM ticket_replies WHERE ticket_id = ?");
            $stmt->execute([$ticketId]);
            $ticket['replies_count'] = (int)$stmt->fetch()['reply_count'];
        }
        unset($ticket);

        MobileApiResponse::success($tickets);
    }

    /**
     * ایجاد تیکت جدید
     * POST /api/v1/tickets (auth)
     *
     * Input: subject (required), category (required), message (required), attachment (file, optional)
     */
    public function store(): void {
        $userId = $this->userId();
        $db     = $this->db();
        $input  = $this->input();

        $errors = $this->validate([
            'subject'  => 'required',
            'category' => 'required',
            'message'  => 'required',
        ], $input);

        if (!empty($errors)) {
            MobileApiResponse::validationError($errors);
        }

        $subject    = trim($input['subject']);
        $category   = trim($input['category']);
        $message    = trim($input['message']);

        // آپلود فایل پیوست در صورت وجود
        $attachment = $this->handleAttachment('attachment');

        $stmt = $db->prepare("
            INSERT INTO tickets (user_id, subject, category, message, status, attachment)
            VALUES (?, ?, ?, ?, 'open', ?)
        ");
        $stmt->execute([$userId, $subject, $category, $message, $attachment]);

        $ticketId = (int)$db->lastInsertId();

        // دریافت تیکت ایجاد شده
        $stmt = $db->prepare("SELECT * FROM tickets WHERE id = ?");
        $stmt->execute([$ticketId]);
        $ticket = $stmt->fetch();
        $ticket['replies_count'] = 0;

        // ارسال نوتیفیکیشن پوش به ادمین‌ها
        try {
            \WHCM\Controllers\MainController::sendPushBroadcast(
                '🎫 تیکت جدید',
                'تیکت از «' . ($this->user()['name'] ?? 'کاربر') . '»: ' . mb_substr($subject, 0, 60),
                '/hnnh'
            );
        } catch (\Throwable $e) {}

        MobileApiResponse::success($ticket, 'تیکت با موفقیت ایجاد شد.');
    }

    /**
     * مشاهده جزئیات یک تیکت
     * GET /api/v1/tickets/{id} (auth)
     *
     * @param string $id شناسه تیکت از مسیر
     */
    public function show(string $id): void {
        $userId  = $this->userId();
        $db      = $this->db();
        $ticketId = (int)$id;

        // دریافت تیکت و بررسی مالکیت
        $stmt = $db->prepare("SELECT * FROM tickets WHERE id = ? AND user_id = ? LIMIT 1");
        $stmt->execute([$ticketId, $userId]);
        $ticket = $stmt->fetch();

        if (!$ticket) {
            MobileApiResponse::notFound('تیکت مورد نظر یافت نشد یا دسترسی ندارید.');
        }

        // دریافت پاسخ‌ها
        $stmt = $db->prepare("
            SELECT tr.*, u.name as sender_name
            FROM ticket_replies tr
            LEFT JOIN users u ON tr.user_id = u.id
            WHERE tr.ticket_id = ?
            ORDER BY tr.id ASC
        ");
        $stmt->execute([$ticketId]);
        $replies = $stmt->fetchAll();

        MobileApiResponse::success([
            'ticket'  => $ticket,
            'replies' => $replies,
        ]);
    }

    /**
     * پاسخ به تیکت
     * POST /api/v1/tickets/{id}/reply (auth)
     *
     * Input: message (required), close_after_reply (optional bool), attachment (optional file)
     *
     * @param string $id شناسه تیکت از مسیر
     */
    public function reply(string $id): void {
        $userId   = $this->userId();
        $db       = $this->db();
        $ticketId = (int)$id;
        $input    = $this->input();

        $errors = $this->validate([
            'message' => 'required',
        ], $input);

        if (!empty($errors)) {
            MobileApiResponse::validationError($errors);
        }

        $message          = trim($input['message']);
        $closeAfterReply  = !empty($input['close_after_reply']);

        // بررسی مالکیت تیکت
        $stmt = $db->prepare("SELECT * FROM tickets WHERE id = ? AND user_id = ? LIMIT 1");
        $stmt->execute([$ticketId, $userId]);
        $ticket = $stmt->fetch();

        if (!$ticket) {
            MobileApiResponse::notFound('تیکت مورد نظر یافت نشد یا دسترسی ندارید.');
        }

        // آپلود فایل پیوست در صورت وجود
        $attachment = $this->handleAttachment('attachment');
        if ($attachment) {
            $message .= "\n\n[پیوست: " . $attachment . "]";
        }

        // ذخیره پاسخ
        $stmt = $db->prepare("
            INSERT INTO ticket_replies (ticket_id, user_id, message)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$ticketId, $userId, $message]);

        // بروزرسانی وضعیت تیکت
        if ($closeAfterReply) {
            $newStatus = 'closed';
        } else {
            $newStatus = ($ticket['status'] === 'closed' || $ticket['status'] === 'replied') ? 'open' : 'open';
        }

        $stmt = $db->prepare("UPDATE tickets SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $ticketId]);

        // ارسال نوتیفیکیشن پوش به ادمین‌ها
        try {
            \WHCM\Controllers\MainController::sendPushBroadcast(
                '👤 پاسخ کاربر به تیکت',
                mb_substr($message, 0, 60),
                '/hnnh'
            );
        } catch (\Throwable $e) {}

        MobileApiResponse::success([
            'ticket_id' => $ticketId,
            'status'    => $newStatus,
        ], $closeAfterReply ? 'پاسخ ثبت و تیکت بسته شد.' : 'پاسخ با موفقیت ثبت شد.');
    }
}
