<?php
/**
 * Lottery Upstream Result Bridge (Draw Results ONLY)
 *
 * This bridge ONLY fetches official Live Draw Results and History from the
 * external Lottery Engine (wingoapi / draw provider).
 *
 * Balance, User Accounts, Wallet, Recharge, Withdraw, Betting, and Payouts
 * REMAIN 100% LOCAL on Dhaniwin.
 */

declare(strict_types=1);

function lottery_upstream_url(): string
{
    return rtrim(trim((string) api_setting('lottery_upstream_url', '')), '/');
}

function lottery_upstream_key(): string
{
    return trim((string) api_setting('lottery_upstream_key', ''));
}

function lottery_upstream_enabled(): bool
{
    return lottery_upstream_url() !== '';
}

/**
 * Fetch draw data from upstream (Result History or Countdown Issue)
 */
function lottery_upstream_call(string $action, array $input = [], string $method = 'POST'): ?array
{
    $base = lottery_upstream_url();
    if ($base === '') {
        return null;
    }

    $url = $base . '?action=' . rawurlencode($action);
    $headers = [
        'Accept: application/json',
        'Content-Type: application/json',
        'X-Api-Key: ' . lottery_upstream_key(),
        'Origin: https://' . ($_SERVER['HTTP_HOST'] ?? ''),
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 6,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_HTTPHEADER     => $headers,
    ]);
    if ($method !== 'GET') {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($input, JSON_UNESCAPED_SLASHES));
    }

    $raw = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || empty($raw)) {
        return null;
    }

    $decoded = json_decode((string) $raw, true);
    return is_array($decoded) ? $decoded : null;
}

/**
 * Fetch a single issue result from upstream engine
 */
function lottery_upstream_fetch_result(string $gameCode, string $issueNumber): ?array
{
    if (!lottery_upstream_enabled() || empty($issueNumber)) {
        return null;
    }

    $answer = lottery_upstream_call('GetWinTheLotteryResult', [
        'gameCode' => $gameCode,
        'issueNumber' => $issueNumber
    ]);

    if ($answer && isset($answer['data'])) {
        $row = is_array($answer['data']) && isset($answer['data'][0]) ? $answer['data'][0] : $answer['data'];
        if (isset($row['number']) || isset($row['premium'])) {
            $premium = (string)($row['premium'] ?? $row['number']);
            return [
                'premium' => $premium,
                'number'  => (string)($row['number'] ?? $premium),
                'color'   => (string)($row['color'] ?? $row['colour'] ?? '')
            ];
        }
    }

    return null;
}

/**
 * Sync latest draw history from upstream into local lottery_results table
 */
function lottery_upstream_sync_history(string $gameCode, int $limit = 20): int
{
    if (!lottery_upstream_enabled()) {
        return 0;
    }

    $answer = lottery_upstream_call('GetHistoryIssuePage', [
        'gameCode' => $gameCode,
        'pageSize' => $limit
    ]);

    if (!$answer || empty($answer['data']['list'])) {
        return 0;
    }

    $pdo = api_pdo();
    if (!$pdo) {
        return 0;
    }

    $saved = 0;
    $lotteryCode = api_lottery_code_from_game($gameCode);

    foreach ($answer['data']['list'] as $item) {
        $issueNumber = (string)($item['issueNumber'] ?? $item['issue_number'] ?? '');
        $premium = (string)($item['premium'] ?? $item['number'] ?? $item['result'] ?? '');
        if ($issueNumber === '' || $premium === '') {
            continue;
        }

        $resultRow = api_lottery_result_from_premium($gameCode, $issueNumber, $premium);
        try {
            $stmt = $pdo->prepare("INSERT IGNORE INTO lottery_results (game_code, lottery_code, issue_number, premium, number_value, color, sum_value, source, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'upstream_live', CURRENT_TIMESTAMP)");
            $stmt->execute([
                $gameCode,
                $lotteryCode,
                $issueNumber,
                $resultRow['premium'],
                $resultRow['number_value'],
                $resultRow['color'],
                $resultRow['sum_value']
            ]);
            if ($stmt->rowCount() > 0) {
                $saved++;
            }
        } catch (Throwable $e) {}
    }

    return $saved;
}

/**
 * Draw routes bridge (/webapi/kv/issue/X and /WinGo/WinGo_1M/*.json)
 */
function lottery_upstream_draw(string $gameCode, string $action = 'GetGameIssue', array $input = []): ?array
{
    if (!lottery_upstream_enabled() || $gameCode === '') {
        return null;
    }

    $answer = lottery_upstream_call($action, array_merge($input, ['gameCode' => $gameCode]));
    return $answer;
}
