<?php
/**
 * ثبت تمام مسیرهای API موبایل پُست‌یار
 *
 * این فایل فقط وقتی لود می‌شود که درخواست با /api/v1/ شروع شود.
 * هیچ تاثیری روی مسیرهای فعلی سایت ندارد.
 */

use WHCM\Api\MobileApiRouter;

// ═══════════════════════════════════════════════════════════
// احراز هویت (بدون نیاز به توکن)
// ═══════════════════════════════════════════════════════════
MobileApiRouter::post('/auth/login', 'AuthApiController@login');
MobileApiRouter::post('/auth/register', 'AuthApiController@register');
MobileApiRouter::post('/auth/reset-password', 'AuthApiController@requestResetEmail');
MobileApiRouter::post('/auth/reset-password/confirm', 'AuthApiController@confirmResetEmail');
MobileApiRouter::post('/auth/reset-password-sms', 'AuthApiController@requestResetSms');
MobileApiRouter::post('/auth/verify-sms-code', 'AuthApiController@verifySmsCode');

// ═══════════════════════════════════════════════════════════
// احراز هویت (نیاز به توکن)
// ═══════════════════════════════════════════════════════════
MobileApiRouter::post('/auth/logout', 'AuthApiController@logout', ['auth']);
MobileApiRouter::get('/auth/me', 'AuthApiController@me', ['auth']);
MobileApiRouter::put('/auth/profile', 'AuthApiController@updateProfile', ['auth']);
MobileApiRouter::post('/auth/change-password', 'AuthApiController@changePassword', ['auth']);

// ═══════════════════════════════════════════════════════════
// پلن‌ها (عمومی - بدون نیاز به توکن)
// ═══════════════════════════════════════════════════════════
MobileApiRouter::get('/plans', 'BillingApiController@getPlans');

// ═══════════════════════════════════════════════════════════
// داشبورد و همگام‌سازی (توکن لازم)
// ═══════════════════════════════════════════════════════════
MobileApiRouter::get('/bootstrap', 'DashboardApiController@bootstrap', ['auth']);
MobileApiRouter::get('/sync', 'DashboardApiController@sync', ['auth']);

// ═══════════════════════════════════════════════════════════
// کانال‌ها
// ═══════════════════════════════════════════════════════════
MobileApiRouter::get('/channels', 'ChannelApiController@index', ['auth']);
MobileApiRouter::post('/channels', 'ChannelApiController@store', ['auth']);
MobileApiRouter::get('/channels/{id}', 'ChannelApiController@show', ['auth']);
MobileApiRouter::put('/channels/{id}', 'ChannelApiController@update', ['auth']);
MobileApiRouter::delete('/channels/{id}', 'ChannelApiController@delete', ['auth']);

// ═══════════════════════════════════════════════════════════
// پست‌ها
// ═══════════════════════════════════════════════════════════
MobileApiRouter::get('/posts', 'PostApiController@index', ['auth']);
MobileApiRouter::post('/posts', 'PostApiController@store', ['auth']);
MobileApiRouter::get('/posts/{id}', 'PostApiController@show', ['auth']);
MobileApiRouter::post('/posts/{id}/cancel', 'PostApiController@cancel', ['auth']);
MobileApiRouter::post('/posts/{id}/retry', 'PostApiController@retry', ['auth']);

// ═══════════════════════════════════════════════════════════
// اعلان‌ها
// ═══════════════════════════════════════════════════════════
MobileApiRouter::get('/notifications', 'NotificationApiController@index', ['auth']);
MobileApiRouter::post('/notifications/{id}/read', 'NotificationApiController@markRead', ['auth']);
MobileApiRouter::post('/notifications/read-all', 'NotificationApiController@markAllRead', ['auth']);

// ═══════════════════════════════════════════════════════════
// پرداخت و صورتحساب
// ═══════════════════════════════════════════════════════════
MobileApiRouter::post('/payments', 'BillingApiController@submitPayment', ['auth']);
MobileApiRouter::get('/payments', 'BillingApiController@getPayments', ['auth']);
MobileApiRouter::post('/coupons/validate', 'BillingApiController@validateCoupon', ['auth']);

// ═══════════════════════════════════════════════════════════
// تیکت‌های پشتیبانی
// ═══════════════════════════════════════════════════════════
MobileApiRouter::get('/tickets', 'SupportApiController@index', ['auth']);
MobileApiRouter::post('/tickets', 'SupportApiController@store', ['auth']);
MobileApiRouter::get('/tickets/{id}', 'SupportApiController@show', ['auth']);
MobileApiRouter::post('/tickets/{id}/reply', 'SupportApiController@reply', ['auth']);

// ═══════════════════════════════════════════════════════════
// تنظیمات
// ═══════════════════════════════════════════════════════════
MobileApiRouter::get('/settings', 'SettingsApiController@getSettings', ['auth']);
MobileApiRouter::post('/settings/gold', 'SettingsApiController@saveGoldSettings', ['auth']);
MobileApiRouter::post('/settings/gold/trigger', 'SettingsApiController@triggerGoldPublish', ['auth']);
MobileApiRouter::put('/settings/advanced', 'SettingsApiController@saveAdvancedSettings', ['auth']);

// ═══════════════════════════════════════════════════════════
// پاسخگوی خودکار
// ═══════════════════════════════════════════════════════════
MobileApiRouter::get('/auto-responder', 'SettingsApiController@getAutoReplies', ['auth']);
MobileApiRouter::post('/auto-responder', 'SettingsApiController@addAutoReply', ['auth']);
MobileApiRouter::delete('/auto-responder/{id}', 'SettingsApiController@deleteAutoReply', ['auth']);
MobileApiRouter::post('/auto-responder/toggle', 'SettingsApiController@toggleResponder', ['auth']);

// ═══════════════════════════════════════════════════════════
// کیف پول و زیرمجموعه‌گیری
// ═══════════════════════════════════════════════════════════
MobileApiRouter::get('/wallet', 'WalletReferralApiController@getWallet', ['auth']);
MobileApiRouter::post('/wallet/convert-points', 'WalletReferralApiController@convertPoints', ['auth']);
MobileApiRouter::get('/referral', 'WalletReferralApiController@getReferral', ['auth']);

// ═══════════════════════════════════════════════════════════
// تحلیل آماری
// ═══════════════════════════════════════════════════════════
MobileApiRouter::get('/analytics/links', 'AnalyticsApiController@linkStats', ['auth']);
MobileApiRouter::get('/analytics/links/{id}', 'AnalyticsApiController@linkStatsDetail', ['auth']);

// ═══════════════════════════════════════════════════════════
// پنل مدیریت (فقط سوپر ادمین)
// ═══════════════════════════════════════════════════════════
MobileApiRouter::get('/admin/dashboard', 'AdminApiController@dashboard', ['superadmin']);
MobileApiRouter::get('/admin/users', 'AdminApiController@users', ['superadmin']);
MobileApiRouter::post('/admin/users/{id}/suspend', 'AdminApiController@suspendUser', ['superadmin']);
MobileApiRouter::post('/admin/users/{id}/activate', 'AdminApiController@activateUser', ['superadmin']);
MobileApiRouter::get('/admin/payments', 'AdminApiController@payments', ['superadmin']);
MobileApiRouter::post('/admin/payments/{id}/approve', 'AdminApiController@approvePayment', ['superadmin']);
MobileApiRouter::get('/admin/tickets', 'AdminApiController@tickets', ['superadmin']);
MobileApiRouter::post('/admin/tickets/{id}/reply', 'AdminApiController@replyTicket', ['superadmin']);
MobileApiRouter::get('/admin/plans', 'AdminApiController@plans', ['superadmin']);
MobileApiRouter::post('/admin/plans', 'AdminApiController@createPlan', ['superadmin']);
MobileApiRouter::put('/admin/plans/{id}', 'AdminApiController@updatePlan', ['superadmin']);
MobileApiRouter::delete('/admin/plans/{id}', 'AdminApiController@deletePlan', ['superadmin']);
MobileApiRouter::post('/admin/broadcast', 'AdminApiController@broadcast', ['superadmin']);
MobileApiRouter::post('/admin/discounts', 'AdminApiController@addDiscount', ['superadmin']);
MobileApiRouter::delete('/admin/discounts/{id}', 'AdminApiController@deleteDiscount', ['superadmin']);
