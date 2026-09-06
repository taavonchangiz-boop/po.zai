<?php
namespace WHCM\Api\Controllers;

use WHCM\Api\MobileApiResponse;
use WHCM\Core\Bootstrap;

/**
 * کنترلر API صورتحساب و پرداخت
 *
 * شامل: لیست پلن‌ها، ثبت پرداخت، لیست پرداخت‌ها، اعتبارسنجی کد تخفیف
 *
 * @package WHCM\Api\Controllers
 */
class BillingApiController extends \WHCM\Api\MobileApiController {


    /**
     * دریافت لیست پلن‌ها
     * GET /api/v1/plans
     */
    public function getPlans(): void {

        $db = $this->db();

        $stmt = $db->query(
            "SELECT * FROM plans ORDER BY price ASC"
        );

        $plans = $stmt->fetchAll();


        foreach ($plans as &$plan) {

            $plan['features'] =
                json_decode(
                    $plan['features'] ?? '[]',
                    true
                ) ?: [];
        }

        unset($plan);


        MobileApiResponse::success($plans);
    }



    /**
     * ثبت پرداخت جدید
     * POST /api/v1/payments
     */
    public function submitPayment(): void {


        $userId = $this->userId();

        $db = $this->db();

        $input = $this->input();



        $errors = $this->validate(
            [
                'plan_id'       => 'required',
                'amount'        => 'required',
                'reference_num' => 'required',
            ],
            $input
        );


        if (!empty($errors)) {

            MobileApiResponse::validationError($errors);
        }



        $planId =
            (int)$input['plan_id'];

        $amount =
            (float)$input['amount'];

        $referenceNum =
            trim($input['reference_num']);



        if ($planId <= 0) {

            MobileApiResponse::validationError([
                'plan_id' => 'شناسه پلن نامعتبر است.'
            ]);
        }



        if ($amount <= 0) {

            MobileApiResponse::validationError([
                'amount' => 'مبلغ باید بزرگتر از صفر باشد.'
            ]);
        }



        $stmt = $db->prepare(
            "SELECT * FROM plans WHERE id = ? LIMIT 1"
        );

        $stmt->execute([$planId]);

        $plan = $stmt->fetch();



        if (!$plan) {

            MobileApiResponse::notFound(
                'پلن مورد نظر یافت نشد.'
            );
        }



        $receiptPhoto =
            $this->uploadImage(
                'receipt_photo',
                'receipts'
            );



        $stmt = $db->prepare(
            "
            INSERT INTO payments
            (
                user_id,
                plan_id,
                amount,
                payment_method,
                receipt_photo,
                reference_num,
                status
            )
            VALUES
            (
                ?,
                ?,
                ?,
                'card_to_card',
                ?,
                ?,
                'pending'
            )
            "
        );


        $stmt->execute([
            $userId,
            $planId,
            $amount,
            $receiptPhoto,
            $referenceNum
        ]);



        $paymentId =
            (int)$db->lastInsertId();



        $stmt = $db->prepare(
            "
            SELECT pay.*, p.title AS plan_title
            FROM payments pay
            LEFT JOIN plans p
            ON pay.plan_id = p.id
            WHERE pay.id = ?
            "
        );


        $stmt->execute([$paymentId]);


        $payment = $stmt->fetch();



        MobileApiResponse::success(
            $payment,
            'پرداخت با موفقیت ثبت شد و پس از بررسی نتیجه اعلام خواهد شد.'
        );
    }




    /**
     * دریافت لیست پرداخت‌ها
     */
    public function getPayments(): void {


        $userId = $this->userId();

        $db = $this->db();



        $stmt = $db->prepare(
            "
            SELECT pay.*, p.title AS plan_title
            FROM payments pay
            LEFT JOIN plans p
            ON pay.plan_id = p.id
            WHERE pay.user_id = ?
            ORDER BY pay.id DESC
            "
        );


        $stmt->execute([$userId]);


        $payments = $stmt->fetchAll();



        MobileApiResponse::success($payments);
    }





    /**
     * اعتبارسنجی کد تخفیف
     * POST /api/v1/coupons/validate
     */
    public function validateCoupon(): void {


        $userId = $this->userId();

        $db = $this->db();

        $input = $this->input();



        $errors = $this->validate(
            [
                'code' => 'required',
                'plan_id' => 'required',
            ],
            $input
        );



        if (!empty($errors)) {

            MobileApiResponse::validationError($errors);
        }



        $code =
            trim($input['code']);

        $planId =
            (int)$input['plan_id'];



        /*
         * MySQL/MariaDB compatible timestamp
         * جایگزین datetime('now')
         */
        $now = date('Y-m-d H:i:s');



        $stmt = $db->prepare(
            "
            SELECT *
            FROM discount_codes
            WHERE code = ?
              AND active = 1
              AND (
                    expires_at IS NULL
                    OR expires_at > ?
                  )
              AND (
                    max_uses = 0
                    OR used < max_uses
                  )
            LIMIT 1
            "
        );



        $stmt->execute([
            $code,
            $now
        ]);



        $coupon = $stmt->fetch();



        if (!$coupon) {

            MobileApiResponse::error(
                'کد تخفیف نامعتبر، منقضی شده یا استفاده شده است.',
                404
            );
        }



        MobileApiResponse::success(
            [
                'id' =>
                    (int)$coupon['id'],

                'code' =>
                    $coupon['code'],

                'type' =>
                    $coupon['type'],

                'amount' =>
                    (float)$coupon['amount'],

                'max_uses' =>
                    (int)$coupon['max_uses'],

                'used' =>
                    (int)$coupon['used'],

                'expires_at' =>
                    $coupon['expires_at'],
            ],
            'کد تخفیف معتبر است.'
        );
    }
}