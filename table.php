<?php

require_once __DIR__ . '/db/bootstrap.php';

foreach (['Marzban.php', 'marzneshin.php', 'hiddify.php', 'ibsng.php', 'mikrotik.php', 'mirza_agent.php', 'nexora.php', 'Rebecca.php', 'WGDashboard.php', 'wg_mate.php', 'x-ui_single.php', 'alireza_single.php', 's_ui.php'] as $oldPanelFile) {
    if (is_file(__DIR__ . '/' . $oldPanelFile)) {
        @unlink(__DIR__ . '/' . $oldPanelFile);
    }
}

global $domainhosts;

$setting = select("setting", "*");
if (intval($setting['Channel_Report'] ?? 0) != 0) {
    syncReportTopics($setting['Channel_Report'], languagechange());
}

$webhookSecret = ensureWebhookSecret();
$baseUrl = function_exists('mirzaGetBaseUrl') ? mirzaGetBaseUrl() : "https://$domainhosts";
$webhookUrl = "$baseUrl/index.php?secret={$webhookSecret['secret']}";

$setWebhookResult = telegram('setWebhook', [
    'url' => $webhookUrl,
]);

$webhookInfo = telegram('getWebhookInfo');

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'status' => 'success',
    'webhook_url' => $webhookUrl,
    'set_webhook_result' => $setWebhookResult,
    'webhook_info' => $webhookInfo,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
