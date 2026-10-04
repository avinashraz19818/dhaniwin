<?php
require_once dirname(__DIR__) . '/_bootstrap.php';

$endpoint = 'ThirdGame/GetGameUrl';
$override = api_get_override($endpoint);
if ($override) {
    $decoded = api_json_decode_lenient((string) $override['content']);
    if ($decoded['ok']) {
        $payload = $decoded['data'];
        api_refresh_times($payload);
        api_emit($payload);
    }
}

$payload = [
        'data' => [
            'url' => 'https://aviator-next.spribesgaming.com/aviator?user=143883660&token=312D4C4B5134335757543355434E504E302D41304435423636454534393433343939&lang=en&currency=INR&operator=vip176wg1578&return_url=d3NzOi8vcXBhcGkudGJnYW1lbG9hZGVyLmNvbQ%3D%3D&res=ODA0L3YxNA%3D%3D&cs=SU5S',
            'returnType' => 1,
        ],
        'code' => 0,
        'msg' => 'Succeed',
        'msgCode' => 0,
        'serverTime' => 1780315891190,
    ];

api_refresh_times($payload);
api_emit($payload);
