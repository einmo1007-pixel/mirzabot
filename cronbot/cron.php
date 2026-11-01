<?php
ignore_user_abort(true);
set_time_limit(120);
@unlink(__DIR__ . '/cron.lock');

$functionBootstrap = __DIR__ . '/function.php';
if (!is_readable($functionBootstrap)) {
    $functionBootstrap = __DIR__ . '/../function.php';
}
if (is_readable($functionBootstrap)) {
    try {
        require_once $functionBootstrap;
    } catch (Throwable $e) {}
}

$host = null;
if (isset($domainhosts) && is_string($domainhosts) && trim($domainhosts) !== '') {
    $host = $domainhosts;
}
if ($host === null || trim((string) $host) === '') {
    $host = $_SERVER['HTTP_HOST'] ?? null;
}
if ($host === null || trim((string) $host) === '') {
    $host = 'localhost';
}

$hostConfig = $host;
if (!preg_match('~^https?://~i', $hostConfig)) {
    $hostConfig = 'https://' . ltrim($hostConfig);
}

$parts    = parse_url($hostConfig);
$scheme   = $parts['scheme'] ?? 'https';
$hostOnly = $parts['host']   ?? 'localhost';
$basePath = rtrim($parts['path'] ?? '', '/');

$buildCronUrl = static function (string $script) use ($scheme, $hostOnly, $basePath): string {
    $script = ltrim($script, '/');
    $prefix = 'cronbot';
    $path = $basePath === '' ? '' : $basePath . '/';
    return $scheme . '://' . $hostOnly . $path . $prefix . '/' . $script;
};

function callEndpoint(string $url): void
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_FORBID_REUSE   => true,
    ]);
    curl_exec($ch);
    curl_close($ch);
    
    sleep(1);
}

$now       = time();
$minute    = (int) date('i', $now);

if (!defined('APP_ROOT_PATH')) {
    define('APP_ROOT_PATH', dirname(__DIR__));
}

$everyMinute = [
    'croncard.php', 'NoticationsService.php', 'sendmessage.php',
    'activeconfig.php', 'disableconfig.php', 'iranpay1.php',
    'index.php', 'lottery.php',
];
foreach ($everyMinute as $script) {
    callEndpoint($buildCronUrl($script));
}

if ($minute % 2 === 0) {
    foreach (['gift.php', 'configtest.php'] as $script) {
        callEndpoint($buildCronUrl($script));
    }
}

if ($minute % 3 === 0) {
    callEndpoint($buildCronUrl('plisio.php'));
}

if ($minute % 5 === 0) {
    callEndpoint($buildCronUrl('payment_expire.php'));
}

if ($minute % 15 === 0) {
    foreach (['statusday.php', 'on_hold.php', 'uptime_node.php', 'uptime_panel.php'] as $script) {
        callEndpoint($buildCronUrl($script));
    }
}

if ($minute % 30 === 0) {
    callEndpoint($buildCronUrl('expireagent.php'));
}

if ($minute === 30) {
    callEndpoint($buildCronUrl('backupbot.php'));
}

echo "OK\n";