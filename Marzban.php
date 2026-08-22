<?php
include('config.php');
require_once 'request.php';
date_default_timezone_set('Asia/Tehran');
#-----------------------------#
function token_panel($code_panel, $verify = true){
    $panel = select("marzban_panel","*","code_panel",$code_panel,"select");
    
    // Check if panel data exists
    if ($panel === null) {
        return null;
    }
    
    // جلوگیری از ایجاد دو اسلش در آدرس
    $url_get_token = rtrim($panel['url_panel'], '/') . '/api/admin/token';
    $username_panel = $panel['username_panel'];
    $password_panel = $panel['password_panel'];
    if($panel['datelogin'] != null && $verify){
        $date = json_decode($panel['datelogin'],true);
        if(isset($date['time'])){
            $timecurrent = time();
            $start_date = time() - strtotime($date['time']);
            if($start_date <= 3600){
                return $date;
            }
        }
    }
    $data_token = array(
        'username' => $username_panel,
        'password' => $password_panel
    );
    $headers = array(
            'Content-Type: application/x-www-form-urlencoded',
            'accept: application/json'
    );
    $req = new CurlRequest($url_get_token);
    $req->setHeaders($headers);
    $response = $req->post($data_token);
    if(!empty($response['error'])){
        return array("error" => $response['error']);
    }
    $body = json_decode($response['body'], true);
    if(isset($body['access_token'])){
        $time = date('Y/m/d H:i:s');
        $data = json_encode(array(
            'time' => $time,
            'access_token' => $body['access_token']
            ));
        update("marzban_panel","datelogin",$data,'name_panel',$panel['name_panel']);
    }
    return $body;
}
#-----------------------------#

function getuser($username_account, $location)
{
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $location,"select");
    $Check_token = token_panel($marzban_list_get['code_panel']);
    if(!empty($Check_token['error'])){
        return $Check_token;
    }
    $url = rtrim($marzban_list_get['url_panel'], '/') . '/api/user/' . $username_account;
    $headers = array(
            'accept: application/json'
    );
    $req = new CurlRequest($url);
    $req->setHeaders($headers);
    $req->setBearerToken($Check_token['access_token']);
    $response = $req->get();
    return $response;
}
#-----------------------------#

function Get_Nodes($location)
{
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $location,"select");
    $Check_token = token_panel($marzban_list_get['code_panel']);
    if(!empty($Check_token['error'])){
        return $Check_token;
    }
    $url = rtrim($marzban_list_get['url_panel'], '/') . '/api/nodes';
    $headers = array(
            'accept: application/json'
    );
    $req = new CurlRequest($url);
    $req->setHeaders($headers);
    $req->setBearerToken($Check_token['access_token']);
    $response = $req->get();
    return $response;
}

function Get_usage_Nodes($location)
{
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $location,"select");
    $Check_token = token_panel($marzban_list_get['code_panel']);
    if(!empty($Check_token['error'])){
        return $Check_token;
    }
    $url = rtrim($marzban_list_get['url_panel'], '/') . '/api/nodes/usage';
    $headers = array(
            'accept: application/json'
    );
    $req = new CurlRequest($url);
    $req->setHeaders($headers);
    $req->setBearerToken($Check_token['access_token']);
    $response = $req->get();
    return $response;
}

function Get_Node($location, $Nodeid)
{
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $location,"select");
    $Check_token = token_panel($marzban_list_get['code_panel']);
    if(!empty($Check_token['error'])){
        return $Check_token;
    }
    $url = rtrim($marzban_list_get['url_panel'], '/') . '/api/node/' . $Nodeid;
    $headers = array(
            'accept: application/json'
    );
    $req = new CurlRequest($url);
    $req->setHeaders($headers);
    $req->setBearerToken($Check_token['access_token']);
    $response = $req->get();
    return $response;
}

function getusers($location, $status)
{
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $location,"select");
    $Check_token = token_panel($marzban_list_get['code_panel']);
    if(!empty($Check_token['error'])){
        return $Check_token;
    }
    $url = rtrim($marzban_list_get['url_panel'], '/') . '/api/users?status=' . $status;
    if(!isset($Check_token['access_token'])) return;
    
    $header_value = 'Bearer ';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPGET, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Accept: application/json',
        'Authorization: ' . $header_value .  $Check_token['access_token']
    ));

    $output = curl_exec($ch);
    curl_close($ch);
    $data_useer = json_decode($output, true);
    return $data_useer;
}
#-----------------------------#
function getinbounds($location)
{
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $location,"select");
    $Check_token = token_panel($marzban_list_get['code_panel']);
    $url = rtrim($marzban_list_get['url_panel'], '/') . '/api/inbounds';
    $header_value = 'Bearer ';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPGET, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Accept: application/json',
        'Authorization: ' . $header_value .  $Check_token['access_token']
    ));

    $output = curl_exec($ch);
    curl_close($ch);
    $inbounds = json_decode($output, true);
    return $inbounds;
}
#-----------------------------#
function ResetUserDataUsage($username_account, $location)
{
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $location,"select");
    $Check_token = token_panel($marzban_list_get['code_panel']);
    if(!empty($Check_token['error'])){
        return $Check_token;
    }
    $url = rtrim($marzban_list_get['url_panel'], '/') . '/api/user/' . $username_account . '/reset';

    $headers = array(
            'accept: application/json'
    );
    $req = new CurlRequest($url);
    $req->setHeaders($headers);
    $req->setBearerToken($Check_token['access_token']);
    $response = $req->post(array());
    return $response;
}

function revoke_sub($username_account, $location)
{
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $location,"select");
    $Check_token = token_panel($marzban_list_get['code_panel']);
    if(!empty($Check_token['error'])){
        return $Check_token;
    }
    $url = rtrim($marzban_list_get['url_panel'], '/') . '/api/user/' . $username_account . '/revoke_sub';
    $headers = array(
            'accept: application/json'
    );
    $req = new CurlRequest($url);
    $req->setHeaders($headers);
    $req->setBearerToken($Check_token['access_token']);
    $response = $req->post(array());
    return $response;
}
#-----------------------------#

// ادد یوزر طبق خواسته شما بدون تغییرات در منطق (فقط اصلاح آدرس rtrim)
function adduser($location, $data_limit, $username_ac, $timestamp, $note = '', $data_limit_reset = 'no_reset', $name_product = false)
{
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $location, "select");
    $Check_token = token_panel($marzban_list_get['code_panel']);
    if (!empty($Check_token['error'])) {
        return $Check_token;
    }
    $url = rtrim($marzban_list_get['url_panel'], '/') . "/api/user";

    $data = [];
    $product = null;
    $service_id_to_use = null;

    if ($name_product != false && $name_product != "usertest") {
        $product = select("product", "*", "name_product", $name_product, "select");
    }

    // --- شروع منطق ترکیبی و هوشمند ---
    // اولویت ۱: بررسی شناسه سرویس در سطح محصول
    if ($product && isset($product['marzban_service_id']) && !empty($product['marzban_service_id'])) {
        $service_id_to_use = (int)$product['marzban_service_id'];
    } 
    // اولویت ۲ (Fallback): بررسی شناسه سرویس پیش‌فرض در سطح پنل
    elseif (isset($marzban_list_get['default_service_id']) && !empty($marzban_list_get['default_service_id'])) {
        $service_id_to_use = (int)$marzban_list_get['default_service_id'];
    }

    if ($service_id_to_use) {
        // *** اگر Service ID پیدا شد (از هر کدام از دو اولویت بالا)، از روش جدید استفاده کن ***
        $data['service_id'] = $service_id_to_use;
    } else {
        // *** اولویت ۳ (روش قدیمی): اگر هیچ Service ID پیدا نشد، جزئیات را دستی ارسال کن ***
        $inbounds = null;
        // مقدار پیش‌فرض proxies از تنظیمات پنل خوانده می‌شود
        $proxies = json_decode($marzban_list_get['proxies']); 

        if ($product) {
            // اگر محصول در دیتابیس پیدا شد و inbounds داشت، مقادیر پیش‌فرض را با مقادیر محصول جایگزین کن
            if (isset($product['inbounds']) && !empty($product['inbounds'])) {
                $inbounds = json_decode($product['inbounds'], true);
                $proxies = json_decode($product['proxies']);
            }
        }
        
        // اگر بعد از تمام بررسی‌ها، inbounds هنوز خالی بود، از مقدار پیش‌فرض پنل استفاده کن
        if (is_null($inbounds) && !empty($marzban_list_get['inbounds']) && $marzban_list_get['inbounds'] != "null") {
            $inbounds = json_decode($marzban_list_get['inbounds'], true);
        }

        // اضافه کردن مقادیر به آرایه دیتا بر اساس نسخه پنل
        if ($marzban_list_get['version_panel'] == "1") {
            $data['proxy_settings'] = $proxies;
            if (isset($inbounds)) {
                $data['group_ids'] = $inbounds;
            }
        } else {
            $data['proxies'] = $proxies;
            if (isset($inbounds)) {
                $data['inbounds'] = $inbounds;
            }
        }
    }
    // --- پایان منطق ترکیبی ---

    // 1. اضافه کردن نام کاربری
    $data['username'] = $username_ac;

    // 2. اضافه کردن حجم ترافیک (که از ورودی تابع گرفته شده و همیشه باید ارسال شود)
    $data['data_limit'] = $data_limit;
    $data['data_limit_reset_strategy'] = $data_limit_reset;

    // 3. اضافه کردن یادداشت (Note) با نام محصول
    $final_note = "محصول: " . ($name_product ?: 'N/A');
    if (!empty($note)) {
        $final_note .= " | " . $note;
    }
    $data['note'] = $final_note;

    // 4. مدیریت تاریخ انقضا و وضعیت on_hold
    $is_test_user = ($name_product == "usertest");
    $on_hold_enabled = $is_test_user ? ($marzban_list_get['on_hold_test'] != "0") : ($marzban_list_get['conecton'] != "offconecton");
    
    if ($on_hold_enabled) {
        if ($timestamp != 0) {
            $data["expire"] = 0;
            $data["status"] = "on_hold";
            // مطمئن می‌شویم که مقدار منفی ارسال نشود
            $duration = $timestamp - time();
            $data["on_hold_expire_duration"] = ($duration > 0) ? $duration : 0;
        } else {
            $data["expire"] = 0;
        }
    } else {
        if ($timestamp != 0) {
            if ($marzban_list_get['version_panel'] == "1") {
                $data["expire"] = date('c', $timestamp);
            } else {
                $data["expire"] = $timestamp;
            }
        } else {
            $data["expire"] = 0;
        }
    }

    // --- ارسال درخواست به پنل مرزبان ---
    $headers = array(
        'accept: application/json',
        'Content-Type: application/json'
    );
    $req = new CurlRequest($url);
    $req->setHeaders($headers);
    $req->setBearerToken($Check_token['access_token']);
    $response = $req->post(json_encode($data));
    return $response;
}

//----------------------------------
function Get_System_Stats($location){
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $location,"select");
    $Check_token = token_panel($marzban_list_get['code_panel']);
    $url = rtrim($marzban_list_get['url_panel'], '/') . '/api/system';
    $header_value = 'Bearer ';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPGET, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Accept: application/json',
        'Authorization: ' . $header_value .  $Check_token['access_token']
    ));

    $output = curl_exec($ch);
    curl_close($ch);
    $Get_System_Stats = json_decode($output, true);
    return $Get_System_Stats;
}

//----------------------------------
// جایگزینی CurlRequest با cURL نیتیو در توابع حساس (DELETE)
function removeuser($location, $username)
{
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $location,"select");
    $Check_token = token_panel($marzban_list_get['code_panel']);
    if(!empty($Check_token['error'])){
        return $Check_token;
    }
    $url = rtrim($marzban_list_get['url_panel'], '/') . '/api/user/' . $username;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Accept: application/json',
        'Authorization: Bearer ' . $Check_token['access_token']
    ));
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

function removenode($location, $nodeid){
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $location,"select");
    $Check_token = token_panel($marzban_list_get['code_panel']);
    if(!empty($Check_token['error'])){
        return $Check_token;
    }
    $url = rtrim($marzban_list_get['url_panel'], '/') . '/api/node/' . $nodeid;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Accept: application/json',
        'Authorization: Bearer ' . $Check_token['access_token']
    ));
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

//----------------------------------
// حل مشکل اساسی تمدید کاربر (ارسال دقیق درخواست PUT با cURL نیتیو)
function Modifyuser($location, $username, array $data)
{
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $location,"select");
    $Check_token = token_panel($marzban_list_get['code_panel']);
    if(!empty($Check_token['error'])){
        return $Check_token;
    }
    $url = rtrim($marzban_list_get['url_panel'], '/') . '/api/user/' . $username;
    $payload = json_encode($data);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Accept: application/json',
        'Authorization: Bearer ' . $Check_token['access_token'],
        'Content-Type: application/json',
        'Content-Length: ' . strlen($payload)
    ));

    $response = curl_exec($ch);
    curl_close($ch);
    
    // خروجی استاندارد آرایه مانند کلاس کاستوم
    return json_decode($response, true) ?: ['body' => $response];
}

//----------------------------------
function Modifyuser_node($location, $id_node, array $data)
{
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $location,"select");
    $Check_token = token_panel($marzban_list_get['code_panel']);
    $url = rtrim($marzban_list_get['url_panel'], '/') . '/api/node/' . $id_node;
    $payload = json_encode($data);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    $headers = array();
    $headers[] = 'Accept: application/json';
    $headers[] = 'Authorization: Bearer ' . $Check_token['access_token'];
    $headers[] = 'Content-Type: application/json';
    $headers[] = 'Content-Length: ' . strlen($payload); // افزودن طول محتوا برای جلوگیری از خطای سرور
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $result = curl_exec($ch);
    curl_close($ch);
    $data_useer = json_decode($result, true);
    return $data_useer;
}

function hosts($location)
{
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $location,"select");
    $Check_token = token_panel($marzban_list_get['code_panel']);
    $url = rtrim($marzban_list_get['url_panel'], '/') . '/api/hosts';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    $headers = array();
    $headers[] = 'Accept: application/json';
    $headers[] = 'Authorization: Bearer ' . $Check_token['access_token'];
    $headers[] = 'Content-Type: application/json';
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $result = curl_exec($ch);
    curl_close($ch);
    $data_hosts = $result; // این تابع ظاهرا در ربات شما به شکل استرینگ (String) استفاده می‌شود
    return $data_hosts;
}

//----------------------------------
function reconnect_node($location, $id_node)
{
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $location,"select");
    $Check_token = token_panel($marzban_list_get['code_panel']);
    if(!empty($Check_token['error'])){
        return $Check_token;
    }
    $url = rtrim($marzban_list_get['url_panel'], '/') . '/api/node/' . $id_node . '/reconnect';
    $headers = array(
            'accept: application/json'
    );
    $req = new CurlRequest($url);
    $req->setHeaders($headers);
    $req->setBearerToken($Check_token['access_token']);
    $response = $req->post(array());
    return $response;
}

function get_list_update($location, $username)
{
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $location,"select");
    $Check_token = token_panel($marzban_list_get['code_panel']);
    if(!empty($Check_token['error'])){
        return $Check_token;
    }
    $url = rtrim($marzban_list_get['url_panel'], '/') . "/api/user/$username/sub_update?offset=0&limit=1";
    $headers = array(
            'accept: application/json'
    );
    $req = new CurlRequest($url);
    $req->setHeaders($headers);
    $req->setBearerToken($Check_token['access_token']);
    $response = $req->get();
    return $response;
}
?>