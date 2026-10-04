<?php
require __DIR__ . '/_bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    api_headers();
    exit;
}

$path = trim((string) ($_GET['path'] ?? ''), "/ \t\n\r\0\x0B");
$gameCode = (string) ($_GET['gameCode'] ?? '');

if ($path !== '') {
    $path = str_replace('\\', '/', urldecode($path));
    $path = preg_replace('#\.\./#', '', $path);
    // 1. Draw Results History: pull from upstream live if available, or local
    if (preg_match('#^([^/]+)/([^/]+)/GetHistoryIssuePage\.json$#i', $path, $m)) {
        api_emit(api_lottery_history_payload($m[2], ['params' => ['lotteryCode' => $m[1], 'gameCode' => $m[2]]]));
    }
    // 2. Issue / Timer endpoint (e.g. WinGo/WinGo_1M.json, WinGo/WinGo_1M/GetGameIssue.json, or /webapi/kv/issue/WinGo_1M)
    if (preg_match('#^([^/]+)/([^/]+)(?:/(?:GetGameIssue|issue))?\.json$#i', $path, $m)) {
        api_emit(api_lottery_issue_payload($m[2]));
    }
    if (preg_match('#(?:kv/issue|issue)/([A-Za-z0-9_]+)#i', $path, $m)) {
        api_emit(api_lottery_issue_payload($m[1]));
    }
}

if ($gameCode === '') {
    if (preg_match('#(?:kv/issue|issue)/([A-Za-z0-9_]+)#i', $path, $gm)) {
        $gameCode = $gm[1];
    } else {
        $gameCode = 'WinGo_30S';
    }
}

// Issue & Countdown timer (millisecond accurate)
api_emit(api_lottery_issue_payload($gameCode));
