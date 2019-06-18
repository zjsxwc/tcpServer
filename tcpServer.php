<?php

@mkdir(__DIR__."/log");

$server = new swoole_server("127.0.0.1", 9503);
$server->on('connect', function ($server, $fd) {
    echo "connection open: {$fd}\n";
});
$server->on('receive', function ($server, $fd, $reactor_id, $data) {
    /** @var swoole_server $server */

    $clientInfo = $server->getClientInfo($fd);
    $clientIp = $clientInfo["remote_ip"];
    file_put_contents(__DIR__ . "/log/" . $clientIp."--".time()."--".uniqid().".txt", serialize($data));
    $server->send($fd, "Swoole: {$data}");
    if (trim($data) === "bye") {
        $server->close($fd);
    }
});
$server->on('close', function ($server, $fd) {
    echo "connection close: {$fd}\n";
});
$server->start();

