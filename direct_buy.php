<?php

if (!isset($user) || empty($user['id'])) {
    $user = select("user", "*", "id", $from_id, "select");
    
    if (!$user) {
        $date = time();
        $randomString = bin2hex(random_bytes(6));
        $valueverify = ($setting['verifystart'] != "onverify") ? 1 : 0;
        
        $stmt = $pdo->prepare("INSERT IGNORE INTO user (id, step, limit_usertest, User_Status, number, Balance, pagenumber, username, agent, message_count, last_message_time, affiliates, affiliatescount, cardpayment, number_username, namecustom, register, verify, codeInvitation, pricediscount, maxbuyagent, joinchannel, score, status_cron) VALUES (:from_id, 'none', :limit_usertest_all, 'Active', 'none', '0', '1', :username, 'f', '0', '0', '0', '0', :showcard, '100', 'none', :date, :verifycode, :codeInvitation, '0', '0', '0', '0', '1')");
        
        $stmt->execute([
            ':from_id' => $from_id,
            ':limit_usertest_all' => $setting['limit_usertest_all'],
            ':username' => $username,
            ':showcard' => $setting['showcard'],
            ':date' => $date,
            ':verifycode' => $valueverify,
            ':codeInvitation' => $randomString
        ]);

        $user = select("user", "*", "id", $from_id, "select");
    }
}

global $ManagePanel;
if (!isset($ManagePanel) || !($ManagePanel instanceof ManagePanel)) {
    if (class_exists('ManagePanel')) {
        $ManagePanel = new ManagePanel();
    }
}
if (empty($user['agent'])) { $user['agent'] = 'f'; }
if (!isset($admin_ids) || !is_array($admin_ids)) {
    $admin_ids = select("admin", "id_admin", null, null, "FETCH_COLUMN");
    if (!is_array($admin_ids)) $admin_ids = [];
}

if (!isset($reporttest)) {
    $topic_row = select("topicid", "idreport", "report", "reporttest", "select");
    $reporttest = ($topic_row) ? $topic_row['idreport'] : 0;
}

if (isset($text) && strpos($text, "/start usertest") === 0) {
    
    if (!check_active_btn($setting['keyboardmain'], "text_usertest")) {
        sendmessage($from_id, "📌 سرویس تست در حال حاضر در دسترس نیست .", null, 'HTML');
        return;
    }

    if ($user['limit_usertest'] <= 0 && !in_array($from_id, $admin_ids)) {
        sendmessage($from_id, $textbotlang['users']['usertest']['limitwarning'], null, 'html');
        return;
    }

    if ($setting['get_number'] == "onAuthenticationphone" && $user['number'] == "none") {
        sendmessage($from_id, $textbotlang['users']['number']['Confirming'], $request_contact, 'HTML');
        step('get_number', $from_id);
        return; 
    }

    $panel = select("marzban_panel", "*", "TestAccount", "ONTestAccount", "select");
    $locationproduct = select("marzban_panel", "*", "TestAccount", "ONTestAccount", "count");

    if ($locationproduct == 0) {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['nullpanel'], null, 'HTML');
        return;
    }

    $marzban_list_get = $panel;
    
    $limit_usertest = $user['limit_usertest'] - 1;
    update("user", "limit_usertest", $limit_usertest, "id", $from_id);

    $username_base = (!empty($username) && $username != 'none') ? $username : $from_id;
    $username_ac = generateUsername($from_id, $marzban_list_get['MethodUsername'], $username_base, "", "test", $marzban_list_get['namecustom'], $user['namecustom']);
    $username_ac = strtolower($username_ac);

    $DataUserOut = $ManagePanel->DataUser($marzban_list_get['name_panel'], $username_ac);
    
    if (!isset($usernameinvoice) || !is_array($usernameinvoice)) {
        $usernameinvoice = select("invoice", "username", null, null, "FETCH_COLUMN");
    }

    if (isset($DataUserOut['username']) || in_array($username_ac, $usernameinvoice)) {
        $username_ac = $username_ac . rand(11, 99);
    }

    $datac = array(
        'expire' => strtotime(date("Y-m-d H:i:s", strtotime("+" . $marzban_list_get['time_usertest'] . "hours"))),
        'data_limit' => $marzban_list_get['val_usertest'] * 1048576,
        'from_id' => $from_id,
        'username' => $username,
        'type' => 'usertest'
    );

    $date = time();
    $invoice_rand_id = bin2hex(random_bytes(4));
    $notifctions = json_encode(['volume' => false, 'time' => false]);
    $stmt = $pdo->prepare("INSERT IGNORE INTO invoice (id_user, id_invoice, username, time_sell, Service_location, name_product, price_product, Volume, Service_time, Status, notifctions) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $Status = "active";
    $p_name = "سرویس تست";
    $p_price = "0";
    
    $stmt->execute([$from_id, $invoice_rand_id, $username_ac, $date, $marzban_list_get['name_panel'], $p_name, $p_price, $marzban_list_get['val_usertest'], $marzban_list_get['time_usertest'], $Status, $notifctions]);

    $dataoutput = $ManagePanel->createUser($marzban_list_get['name_panel'], "usertest", $username_ac, $datac);

    if ($dataoutput['username'] == null) {
        sendmessage($from_id, $textbotlang['users']['usertest']['errorcreat'], null, 'html');
        return;
    }

    $output_config_link = "";
    $config = "";
    $output_config_link = $marzban_list_get['sublink'] == "onsublink" ? $dataoutput['subscription_url'] : "";
    if ($marzban_list_get['config'] == "onconfig" && is_array($dataoutput['configs'])) {
        foreach ($dataoutput['configs'] as $conf) {
            $config .= "\n" . $conf;
        }
    }

    $template_text = ($marzban_list_get['type'] == "ibsng" || $marzban_list_get['type'] == "mikrotik")
        ? $textbotlang['textbot']['afterPayIbsng']
        : $textbotlang['textbot']['afterText'];

    $textcreatuser = str_replace(['{username}', '{name_service}', '{location}', '{day}', '{volume}', '{config}', '{links}', '{links2}'], 
                                [$dataoutput['username'], $textbotlang['common']['labels']['test'], $marzban_list_get['name_panel'], $marzban_list_get['time_usertest'], $marzban_list_get['val_usertest'], "<code>{$output_config_link}</code>", $config, $output_config_link], 
                                $template_text);

    $usertestinfo = json_encode([
        'inline_keyboard' => [[['text' => $textbotlang['users']['help']['btninlinebuy'], 'callback_data' => "helpbtn"]]]
    ]);
    
    $sub_link_direct = (!empty($dataoutput['subscription_url']) && filter_var($dataoutput['subscription_url'], FILTER_VALIDATE_URL)) ? $dataoutput['subscription_url'] : $output_config_link;
    sendMessageService($marzban_list_get, $dataoutput['configs'], $sub_link_direct, $dataoutput['username'], $usertestinfo, $textcreatuser, $invoice_rand_id);
    sendmessage($from_id, "✅ سرویس تست شما با موفقیت فعال شد.", $keyboard, 'HTML');
    step('home', $from_id);
    
    if (strlen($setting['Channel_Report']) > 0 && !empty($reporttest)) {
        $timejalali = jdate('Y/m/d H:i:s');
        $text_report = "📣 جزئیات ساخت اکانت تست
▫️آیدی عددی: <code>$from_id</code>
▫️نام کاربری: @$username
▫️کانفیگ: $username_ac
▫️موقعیت: {$marzban_list_get['name_panel']}
▫️زمان: {$marzban_list_get['time_usertest']} ساعت
▫️حجم: {$marzban_list_get['val_usertest']} MB
▫️کد پیگیری: $invoice_rand_id
▫️زمان ثبت: $timejalali";

        telegram('sendmessage', [
            'chat_id' => $setting['Channel_Report'],
            'message_thread_id' => $reporttest,
            'text' => $text_report,
            'parse_mode' => "HTML",
            'reply_markup' => json_encode(['inline_keyboard' => [[['text' => 'مدیریت کاربر', 'callback_data' => 'manageuser_' . $from_id]]]])
        ]);
    }
    return; 
}

if (isset($text) && strpos($text, "/start buy_") === 0) {
    $full_command = explode(" ", $text)[1]; 
    $parts = explode("_", $full_command);

    if (count($parts) >= 3) {
        $req_product_code = $parts[1];
        $req_panel_code = $parts[2];

        $marzban_panel = select("marzban_panel", "*", "code_panel", $req_panel_code, "select");
        
        if (!$marzban_panel) {
            sendmessage($from_id, "❌ خطا: پنل با کد $req_panel_code پیدا نشد.", null, 'HTML');
            return;
        }

        $stmt = $pdo->prepare("SELECT * FROM product WHERE code_product = :code LIMIT 1");
        $stmt->execute([':code' => $req_product_code]);
        $info_product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$info_product) {
            sendmessage($from_id, "❌ خطا: محصول با کد $req_product_code پیدا نشد.", null, 'HTML');
            return;
        }

        $priceproduct = $info_product['price_product'];
        $user_discount = isset($user['pricediscount']) ? intval($user['pricediscount']) : 0;
        if ($user_discount != 0) {
            $result = ($priceproduct * $user_discount) / 100;
            $priceproduct = $priceproduct - $result;
        }

        $randomString = bin2hex(random_bytes(2));
        $username_ac = generateUsername($from_id, $marzban_panel['MethodUsername'], $username, $randomString, "quick", $marzban_panel['namecustom'], "none");
        $username_ac = strtolower($username_ac);
        
        $DataUserOut = $ManagePanel->DataUser($marzban_panel['name_panel'], $username_ac);

        if (!isset($usernameinvoice) || !is_array($usernameinvoice)) {
            $usernameinvoice = select("invoice", "username", null, null, "FETCH_COLUMN");
            if (!is_array($usernameinvoice)) $usernameinvoice = [];
        }

        if (isset($DataUserOut['username']) || in_array($username_ac, $usernameinvoice)) {
            $username_ac = rand(1000, 9999) . "_" . $username_ac;
        }

        $json_data = json_encode(
            ['name_panel' => $marzban_panel['name_panel'], 'nameconfig' => 'DirectBuy'], 
            JSON_UNESCAPED_UNICODE
        );
        
        try {
            $sql = "UPDATE user SET step = ?, Processing_value = ?, Processing_value_one = ?, Processing_value_tow = ?, Processing_value_four = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['payment', $json_data, $req_product_code, $username_ac, "1", $from_id]);
        } catch (Exception $e) {
            sendmessage($from_id, "❌ خطای سیستم: " . $e->getMessage(), null, 'HTML');
            return;
        }

        if ($info_product['Volume_constraint'] == 0) $info_product['Volume_constraint'] = "نامحدود";
        if ($info_product['Service_time'] == 0) $info_product['Service_time'] = "نامحدود";
        
        $price_show = number_format($priceproduct);
        
        $textin = "
📇 <b>پیش فاکتور خرید سریع:</b>

👤 نام کاربری سرویس: <code>$username_ac</code>
🔐 نام محصول: {$info_product['name_product']}
📆 مدت اعتبار: {$info_product['Service_time']} روز
👥 حجم: {$info_product['Volume_constraint']} گیگ
💶 قیمت نهایی: $price_show تومان

💳 <b>جهت دریافت شماره کارت و پرداخت، دکمه زیر را بزنید:</b>";

        $payment_keyboard = json_encode([
            'inline_keyboard' => [
                [['text' => "💳 پرداخت و دریافت سرویس", 'callback_data' => "direct_pay_start"]],
            ]
        ]);

        sendmessage($from_id, $textin, $payment_keyboard, 'HTML');
        return; 
    }
}

if (isset($datain) && $datain == "direct_pay_start") {
    
    $stmtRefresh = $pdo->prepare("SELECT Processing_value, Processing_value_one, Processing_value_tow, Balance, agent, pricediscount, affiliates FROM user WHERE id = :id LIMIT 1");
    $stmtRefresh->execute([':id' => $from_id]);
    $freshUser = $stmtRefresh->fetch(PDO::FETCH_ASSOC);

    $saved_product_code = isset($freshUser['Processing_value_one']) ? $freshUser['Processing_value_one'] : '';
    if (empty($saved_product_code)) {
        sendmessage($from_id, "❌ خطا: اطلاعات محصول یافت نشد. لطفاً مجدداً از لینک استفاده کنید.", null, 'HTML');
        return;
    }

    $panel_name = '';
    $json_decoded = json_decode($freshUser['Processing_value'], true);
    if (isset($json_decoded['name_panel'])) {
        $panel_name = $json_decoded['name_panel'];
    } else {
        $stmtP = $pdo->prepare("SELECT Location FROM product WHERE code_product = :code LIMIT 1");
        $stmtP->execute([':code' => $saved_product_code]);
        $tempProd = $stmtP->fetch(PDO::FETCH_ASSOC);
        $panel_name = $tempProd['Location'];
    }

    if (empty($freshUser['agent'])) { $freshUser['agent'] = 'f'; }

    $stmt = $pdo->prepare("SELECT * FROM product WHERE code_product = :code LIMIT 1");
    $stmt->execute([':code' => $saved_product_code]);
    $info_product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$info_product) {
        sendmessage($from_id, "❌ خطا: محصول یافت نشد.", null, 'HTML');
        return;
    }

    $priceproduct = $info_product['price_product'];
    $user_discount = isset($freshUser['pricediscount']) ? intval($freshUser['pricediscount']) : 0;
    
    if ($user_discount != 0) {
        $result = ($priceproduct * $user_discount) / 100;
        $priceproduct = $priceproduct - $result;
    }
    
    $payable_amount = $priceproduct - $freshUser['Balance'];
    if ($payable_amount < 0) $payable_amount = 0;

    $sql = "UPDATE user SET Processing_value = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$payable_amount, $from_id]);

    $stmtCard = $pdo->query("SELECT * FROM card_number ORDER BY RAND() LIMIT 1");
    $card_info = $stmtCard ? $stmtCard->fetch(PDO::FETCH_ASSOC) : null;
    
    if ($card_info) {
        $card_number = $card_info['cardnumber'];
        $PaySettingname = $card_info['namecard'];
        $valueprice = number_format($payable_amount);
        $username_ac = $freshUser['Processing_value_tow'];

        $date_now = time();
        $randomStringInvoice = bin2hex(random_bytes(4)); 
        $notifctions = json_encode(['volume' => false, 'time' => false]);
        
        $stmtInv = $pdo->prepare("INSERT IGNORE INTO invoice (id_user, id_invoice, username, time_sell, Service_location, name_product, price_product, Volume, Service_time, Status, note, refral, notifctions) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $status_inv = "unpaid";
        $note_inv = "DirectBuy";
        $refral_inv = $freshUser['affiliates'];
        
        $stmtInv->execute([
            $from_id, 
            $randomStringInvoice, 
            $username_ac, 
            $date_now, 
            $panel_name, 
            $info_product['name_product'], 
            $priceproduct, 
            $info_product['Volume_constraint'], 
            $info_product['Service_time'], 
            $status_inv, 
            $note_inv, 
            $refral_inv, 
            $notifctions
        ]);

        $invoice_ref = "getconfigafterpay|$username_ac";
        $dateacc = date('Y/m/d H:i:s');
        $randomStringOrder = bin2hex(random_bytes(5));
        
        $stmtPay = $pdo->prepare("INSERT INTO Payment_report (id_user,id_order,time,price,payment_Status,Payment_Method,id_invoice) VALUES (?,?,?,?,?,?,?)");
        $payment_Status = "Unpaid";
        $Payment_Method = "cart to cart";
        $stmtPay->execute([$from_id, $randomStringOrder, $dateacc, $payable_amount, $payment_Status, $Payment_Method, $invoice_ref]);

        $textcart = "💳 <b>اطلاعات جهت واریز:</b>\n\n🔹 شماره کارت:\n<code>$card_number</code>\n🔹 به نام: <b>$PaySettingname</b>\n\n💰 مبلغ قابل پرداخت: <b>$valueprice تومان</b>\n\n📸 لطفاً پس از واریز، دکمه زیر را زده و <b>تصویر رسید</b> خود را ارسال کنید.";
        
        $sendresidcart = json_encode([
            'inline_keyboard' => [
                [
                    ['text' => "کپی کارت", 'copy_text' => ["text" => $card_number]],
                    ['text' => "کپی مبلغ", 'copy_text' => ["text" => $payable_amount]]
                ],
                [
                    ['text' => "✅ پرداخت کردم | ارسال رسید", 'callback_data' => "sendresidcart-" . $randomStringOrder, 'style' => 'success']
                ]
            ]
        ]);
        
        $sql = "UPDATE user SET step = 'cart_to_cart_user' WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$from_id]);
        
        deletemessage($from_id, $message_id);
        sendmessage($from_id, $textcart, $sendresidcart, 'HTML');
        return; 
    } else {
        sendmessage($from_id, "❌ خطایی در دریافت اطلاعات بانکی رخ داد.", null, 'HTML');
    }
}
?>