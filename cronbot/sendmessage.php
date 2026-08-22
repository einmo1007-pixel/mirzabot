<?php
// جلوگیری از نمایش ارورها در خروجی که باعث خرابی جیسون میشود
error_reporting(0);
ini_set('display_errors', 0);

date_default_timezone_set('Asia/Tehran');

// اطمینان از صحت مسیر فایل‌های کانفیگ
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../botapi.php';
require_once __DIR__ . '/../function.php';

// بررسی وجود فایل‌های ضروری
if (!file_exists(__DIR__ . '/info') || !file_exists(__DIR__ . '/users.json')) {
    return;
}

// خواندن اطلاعات
$info_content = file_get_contents(__DIR__ . '/info');
$users_content = file_get_contents(__DIR__ . '/users.json');

$info = json_decode($info_content, true);
$userid = json_decode($users_content); // به صورت آبجکت دیکود میشود

// اگر جیسون خراب بود یا فایل خالی بود
if (!is_array($userid) && !is_object($userid)) {
    // تلاش برای دیکود مجدد به صورت آرایه
    $userid = json_decode($users_content, true);
    if (!is_array($userid)) {
        $userid = [];
    }
}

// پایان عملیات اگر لیستی وجود ندارد
if (empty($userid)) {
    if (isset($info['id_admin'])) {
        deletemessage($info['id_admin'], $info['id_message']);
        sendmessage($info['id_admin'], "✅ عملیات ارسال پیام با موفقیت به پایان رسید.", null, 'HTML');
    }
    // پاک کردن فایل‌های صف
    @unlink(__DIR__ . '/info');
    @unlink(__DIR__ . '/users.json');
    return;
}

// آپدیت وضعیت برای ادمین (هر 50 نفر یکبار برای جلوگیری از اسپم)
$total_count = count($userid);
// فقط اگر تعداد رند بود ادیت بزن تا لاگ تلگرام پر نشود
if ($total_count % 10 == 0) {
    $textprocces = "✏️ عملیات ارسال پیام درحال انجام می باشد...\n\nتعداد نفرات باقی مانده : $total_count";
    $cancelmessage = json_encode(['inline_keyboard' => [[['text' => "لغو عملیات", 'callback_data' => 'cancel_sendmessage']]]]);
    Editmessagetext($info['id_admin'], $info['id_message'], $textprocces, $cancelmessage);
}

// دریافت متن دکمه‌ها از دیتابیس
$datatextbotget = select("textbot", "*", null, null, "fetchAll");
$datatextbot = [];
foreach ($datatextbotget as $row) {
    $datatextbot[$row['id_text']] = $row['text'];
}

// آماده‌سازی کیبوردها
$keyboards = [
    'buy' => json_encode(['inline_keyboard' => [[['text' => $datatextbot['text_sell'] ?? 'خرید', 'callback_data' => 'buy']]]]),
    'start' => json_encode(['inline_keyboard' => [[['text' => "شروع", 'callback_data' => 'start']]]]),
    'usertestbtn' => json_encode(['inline_keyboard' => [[['text' => $datatextbot['text_usertest'] ?? 'تست', 'callback_data' => 'usertestbtn']]]]),
    'helpbtn' => json_encode(['inline_keyboard' => [[['text' => $datatextbot['text_help'] ?? 'آموزش', 'callback_data' => 'helpbtn']]]]),
    'affiliatesbtn' => json_encode(['inline_keyboard' => [[['text' => $datatextbot['text_affiliates'] ?? 'زیرمجموعه', 'callback_data' => 'affiliatesbtn']]]]),
    'addbalance' => json_encode(['inline_keyboard' => [[['text' => $datatextbot['text_Add_Balance'] ?? 'شارژ', 'callback_data' => 'Add_Balance']]]]),
];

// حلقه پردازش (حداکثر 30 عدد در هر اجرا)
$limit = 30;
$processed = 0;

while ($processed < $limit && count($userid) > 0) {
    // برداشتن اولین آیتم و حذف از لیست اصلی
    $user_obj = array_shift($userid);
    $processed++;

    // استخراج آیدی کاربر (پشتیبانی از فرمت‌های مختلف)
    $id_user = null;
    if (is_object($user_obj) && isset($user_obj->id)) {
        $id_user = $user_obj->id;
    } elseif (is_array($user_obj) && isset($user_obj['id'])) {
        $id_user = $user_obj['id'];
    } elseif (is_numeric($user_obj)) {
        $id_user = $user_obj;
    }

    if (!$id_user) continue; // اگر آیدی پیدا نشد برو بعدی

    try {
        if ($info['type'] == "unpinmessage") {
            unpinmessage($id_user);
        } elseif ($info['type'] == "sendmessage" || $info['type'] == "xdaynotmessage") {
            
            $keyboard = null;
            $btnType = $info['btnmessage'] ?? 'none';

            if (isset($keyboards[$btnType])) {
                $keyboard = $keyboards[$btnType];
            } elseif ($btnType == "uniqueproduct") {
                // --- بخش لینک یکتا ---
                $url_btn = $info['btnurl'] ?? '';
                $name_btn = $info['btnname'] ?? '💥خرید محصول ویژه 💥';
                
                // اعتبارسنجی لینک
                if (!filter_var($url_btn, FILTER_VALIDATE_URL)) {
                    // اگر لینک خراب بود، لینک ربات را بگذار تا ارور ندهد
                    $url_btn = "https://t.me/" . ($usernamebot ?? 'telegram'); 
                }

                $keyboard = json_encode([
                    'inline_keyboard' => [
                        [
                            ['text' => $name_btn, 'url' => $url_btn],
                        ],
                    ]
                ]);
                // ---------------------
            }

            $msg_response = sendmessage($id_user, $info['message'], $keyboard, 'HTML');

            // بررسی کاربر بلاک شده (بدون حذف از دیتابیس برای جلوگیری از کرش)
            if (isset($msg_response['ok']) && $msg_response['ok'] == false) {
                 // اینجا فقط لاگ می کنیم و حذف نمی کنیم تا باعث هنگ نشود
                 // اگر خواستید حذف کنید باید مطمئن شوید توابع پنل درست کار میکنند
            }

            // پین کردن
            if (($info['pingmessage'] ?? 'no') == "yes" && ($msg_response['ok'] ?? false)) {
                pinmessage($id_user, $msg_response['result']['message_id']);
            }

        } elseif ($info['type'] == "forwardmessage") {
            $msg_response = forwardMessage($info['id_admin'], $info['message'], $id_user);
            if (($info['pingmessage'] ?? 'no') == "yes" && ($msg_response['ok'] ?? false)) {
                pinmessage($id_user, $msg_response['result']['message_id']);
            }
        }
    } catch (Exception $e) {
        // اگر خطایی رخ داد نادیده بگیر و برو سراغ بعدی
        continue;
    }
}

// ذخیره لیست باقی‌مانده در فایل
file_put_contents(__DIR__ . '/users.json', json_encode($userid));
?>