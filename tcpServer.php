<?php

include_once "parseData.php";

@mkdir(__DIR__."/log");

$server = new swoole_server("0.0.0.0", 9503);
$server->on('connect', function ($server, $fd) {
    echo "connection open: {$fd}\n";
});
$server->on('receive', function ($server, $fd, $reactor_id, $data) {
    /** @var swoole_server $server */


    $isValidLdwd = $data{0} == "{" &&
        $data{1} == "{" &&
        ord($data{2}) == 0x90 &&
        ord($data{3}) == 0x01 &&
        ord($data{4}) == 0x03 &&
        ord($data{5}) == 0x54;
    if ($isValidLdwd) {
        parseLdwd($data);
        file_put_contents(__DIR__ . "/log/" . "Ldwd"."--".time()."--".uniqid().".txt", serialize($data));
    }


    $clientInfo = $server->getClientInfo($fd);
    $clientIp = $clientInfo["remote_ip"];
    file_put_contents(__DIR__ . "/log/" . $clientIp."--".time()."--".uniqid().".txt", serialize($data));
    // $server->send($fd, "Swoole: {$data}");
    if (trim($data) === "bye") {
        $server->close($fd);
    }

    //发送设备通讯报文漏电温度
    $message = "\x7b\x7b\x90\x01\x03\x10\x00\x00\x2a\xc0\xd5\xe6\xfd\x7d\x7d";
    $server->send($fd, $message);
});
$server->on('close', function ($server, $fd) {
    echo "connection close: {$fd}\n";
});
$server->start();

