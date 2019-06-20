<?php


$http = new Swoole\Http\Server("0.0.0.0", 9503);
$http->on('request', function ($request, $response) {
    $lastLines = "";
    $ph = popen('tail -n 10 ' . __DIR__ . '/data.txt', 'r');
    while ($r = fgets($ph)) {
        $lastLines .= $r;
    }
    pclose($ph);

    $response->end(
        "<!DOCTYPE html>
<html lang=\"zh-CN\">
<head>
    <meta charset=\"UTF-8\">
    <title>电表数据获取测试</title>
</head>
<body><pre>" . $lastLines . "</pre></body>
</html>"
    );
});
$http->start();


