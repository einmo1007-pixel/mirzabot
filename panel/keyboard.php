<?php
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/icons.php';
require_auth();
setcookie('XSRF-TOKEN', csrf_token(), [
    'path' => '/',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'samesite' => 'Strict',
]);

$keyboard = json_decode(file_get_contents("php://input"), true);
$method = $_SERVER['REQUEST_METHOD'];
if ($method == "POST" && !csrf_check_value($_SERVER['HTTP_X_XSRF_TOKEN'] ?? '')) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false]);
    exit;
}
if ($method == "POST" && is_array($keyboard)) {
    $validKeyboard = count($keyboard) > 0;
    foreach ($keyboard as $row) {
        if (!is_array($row) || count($row) === 0) {
            $validKeyboard = false;
            break;
        }
        foreach ($row as $button) {
            if (!is_array($button) || !isset($button['text']) || !is_string($button['text']) || trim($button['text']) === '') {
                $validKeyboard = false;
                break 2;
            }
        }
    }
    header('Content-Type: application/json; charset=utf-8');
    if (!$validKeyboard) {
        http_response_code(422);
        echo json_encode(['ok' => false]);
        exit;
    }
    $keyboardmain = ['keyboard' => $keyboard];
    update("setting", "keyboardmain", json_encode($keyboardmain), null, null);
    echo json_encode(['ok' => true]);
    exit;
}

$action = filter_input(INPUT_GET, 'action');
if ($action === "reaset") {
    csrf_check_get();
    $default_kb = '{"keyboard":[[{"text":"text_sell"},{"text":"text_extend"}],[{"text":"text_usertest"},{"text":"text_wheel_luck"}],[{"text":"text_Purchased_services"},{"text":"accountwallet"}],[{"text":"text_affiliates"},{"text":"text_Tariff_list"}],[{"text":"text_support"},{"text":"text_help"}],[{"text":"text_agentpanel"},{"text":"text_requestagent"}]]}';
    update("setting", "keyboardmain", $default_kb, null, null);
    header('Location: keyboard.php');
    exit;
}

if ($method == "GET" && (isset($_GET['api']) || isset($_GET['ajax']) || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false))) {
    header('Content-Type: application/json; charset=utf-8');
    $keyboardSetting = select("setting", "keyboardmain", null, null, "select");
    $keyboardmain = json_decode($keyboardSetting['keyboardmain'] ?? '', true);
    if (!is_array($keyboardmain) || !isset($keyboardmain['keyboard']) || !is_array($keyboardmain['keyboard'])) {
        $keyboardmain = ['keyboard' => []];
    }

    $list_keyboard = [
        'text_sell',
        'text_extend',
        'text_usertest',
        'text_wheel_luck',
        'text_Purchased_services',
        'accountwallet',
        'text_affiliates',
        'text_Tariff_list',
        'text_support',
        'text_help',
        'text_agentpanel',
        'text_requestagent',
    ];
    $labels = [
        'text_sell' => $textbotlang['textbot']['sell'] ?? 'خرید سرویس',
        'text_extend' => $textbotlang['textbot']['extend'] ?? 'تمدید سرویس',
        'text_usertest' => $textbotlang['textbot']['userTest'] ?? 'تست رایگان',
        'text_wheel_luck' => $textbotlang['textbot']['wheelLuck'] ?? 'گردونه شانس',
        'text_Purchased_services' => $textbotlang['textbot']['purchasedServices'] ?? 'سرویس‌های من',
        'accountwallet' => $textbotlang['textbot']['accountWallet'] ?? 'کیف پول',
        'text_affiliates' => $textbotlang['textbot']['affiliates'] ?? 'زیرمجموعه‌گیری',
        'text_Tariff_list' => $textbotlang['textbot']['tariffList'] ?? 'تعرفه‌ها',
        'text_support' => $textbotlang['textbot']['support'] ?? 'پشتیبانی',
        'text_help' => $textbotlang['textbot']['help'] ?? 'راهنما',
        'text_agentpanel' => $textbotlang['textbot']['agentPanel'] ?? 'پنل نمایندگی',
        'text_requestagent' => $textbotlang['textbot']['requestAgent'] ?? 'درخواست نمایندگی',
    ];

    foreach ($keyboardmain['keyboard'] as $row_kb) {
        if (!is_array($row_kb)) {
            continue;
        }
        foreach ($row_kb as $arrkey) {
            if (!is_array($arrkey) || !isset($arrkey['text'])) {
                continue;
            }
            $index_number = array_search($arrkey['text'], $list_keyboard, true);
            if ($index_number !== false) {
                unset($list_keyboard[$index_number]);
            }
        }
    }
    $list_keyboard = array_values($list_keyboard);

    $available_keys = [];
    foreach ($list_keyboard as $key) {
        $available_keys[] = [['text' => $key]];
    }

    $formatted_labels = [];
    foreach ($labels as $k => $v) {
        $formatted_labels[$k] = function_exists('customEmojiLabelText') ? customEmojiLabelText($v) : $v;
    }

    echo json_encode([
        'keylist' => $available_keys,
        'userlist' => $keyboardmain['keyboard'],
        'text' => $formatted_labels
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
?>

<!doctype html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= $textbotlang['panel']['keyboardManageTitle'] ?></title>

    <script>
        window.__MIRZA_API_ORIGIN = new URL('..', window.location.href).href.replace(/\/$/, '');
        window.__MIRZA_KEYBOARD_API = window.location.href.split('?')[0].split('#')[0] + '?api=1';
        (function() {
            var origOpen = XMLHttpRequest.prototype.open;
            XMLHttpRequest.prototype.open = function(method, url) {
                if (typeof url === 'string' && url.indexOf('/api/keyboard') !== -1) {
                    url = window.__MIRZA_KEYBOARD_API;
                }
                return origOpen.apply(this, arguments);
            };
        })();
    </script>

    <script type="module" crossorigin src="js/sort_keyboard.js?v=<?= file_exists(__DIR__ . '/js/sort_keyboard.js') ? filemtime(__DIR__ . '/js/sort_keyboard.js') : '1' ?>"></script>
    <link rel="stylesheet" crossorigin href="css/sort_keyboard.css?v=<?= file_exists(__DIR__ . '/css/sort_keyboard.css') ? filemtime(__DIR__ . '/css/sort_keyboard.css') : '1' ?>">
    <style>
        @import url(https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap);

        * {
            font-family: 'Vazirmatn' !important;
        }

        button {
            font-family: yekan;
        }

        .btnback {
            position: fixed;
            top: 10px;
            left: 10px;
            padding: 7px;
            background-color: #3d3d3d;
            color: #fff;
            border-radius: 6px;
            font-family: yekan;
            font-size: 13px;
            font-weight: bold;
            text-decoration: none;
        }

        .btndefult {
            position: fixed;
            top: 10px;
            left: 150px;
            padding: 7px;
            background-color: #fff;
            border: 2px solid #3d3d3d;
            color: #3d3d3d;
            border-radius: 6px;
            font-family: yekan;
            font-size: 13px;
            font-weight: bold;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <a class="btnback" href="index.php"><?= $textbotlang['panel']['keyboardSortHint'] ?></a>
    <a class="btndefult" href="keyboard.php?action=reaset&_csrf=<?= urlencode(csrf_token()) ?>"><?= $textbotlang['panel']['keyboardSaveBtn'] ?></a>
    <div id="root"></div>
</body>

</html>