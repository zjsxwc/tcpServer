<?php

include_once "parseData.php";

@mkdir(__DIR__ . "/log");

$server = new swoole_server("0.0.0.0", 9503);
$server->on('connect', function ($server, $fd) {
    echo "connection open: {$fd}\n";
});


$table = new Swoole\Table(1024);
$table->column('fd', swoole_table::TYPE_INT, 8);
$table->column('deviceId', swoole_table::TYPE_STRING, 1024);
$table->column('lastRequestTime', swoole_table::TYPE_INT, 8);
$table->create();

$server->on('receive', function ($server, $fd, $reactor_id, $data) use ($table) {
    /** @var swoole_server $server */

    $currentTime = time();

    $deviceId = null;
    $tableData = $table->get($fd);
    var_dump($tableData);
    if ($tableData) {
        $deviceId = $tableData["deviceId"];
    }
    $table->set($fd, ["fd" => $fd, "deviceId" => trim($data), "lastRequestTime" => $currentTime]);

    return;

    $isNewConnect = false;

    $isRegisterDeviceId =
        $data{0} == "{" &&
        $data{1} == "{" &&
        ord($data{2}) == 0x84;
    if ($isRegisterDeviceId) {
        $deviceId = substr($data, 3, 14);
        $table->set($fd, ["fd" => $fd, "deviceId" => $deviceId, "lastRequestTime" => $currentTime]);
        $isNewConnect = true;
    }

    if (!$deviceId) {
        $server->close($fd);
        return;
    }

    sleep(1);
    if ($isNewConnect) {
        //发送设备通讯报文漏电温度
        $message = "\x7b\x7b\x90\x01\x03\x10\x00\x00\x2a\xc0\xd5\xe6\xfd\x7d\x7d";
        $server->send($fd, $message);
        return;
    }




    $isValidLdwd = $data{0} == "{" &&
        $data{1} == "{" &&
        ord($data{2}) == 0x90 &&
        ord($data{3}) == 0x01 &&
        ord($data{4}) == 0x03 &&
        ord($data{5}) == 0x54;
    if ($isValidLdwd) {
        $response = parseLdwd($data);
        file_put_contents(__DIR__ . "/log/" . "deviceId_{$deviceId}_Ldwd" . "--" . time() . "--" . uniqid() . ".txt", serialize($data)."\n\n\n".serialize($response));

        //发送设备通讯报文 电压电流
        $message = "\x7b\x7b\x90\x01\x03\x12\x04\x00\x1a\x80\xb8\xe6\xfd\x7d\x7d";
        $server->send($fd, $message);
        return;
    }


    $isValidDldy = $data{0} == "{" &&
        $data{1} == "{" &&
        ord($data{2}) == 0x90 &&
        ord($data{3}) == 0x01 &&
        ord($data{4}) == 0x03 &&
        ord($data{5}) == 0x34;
    if (!$isValidDldy) {
        parseDldy($data);
        file_put_contents(__DIR__ . "/log/" . "deviceId_{$deviceId}_Dldy" . "--" . time() . "--" . uniqid() . ".txt", serialize($data)."\n\n\n".serialize($response));

        //发送设备通讯报文 电压电流
        $message = "\x7b\x7b\x90\x01\x03\x13\x00\x00\x02\xc0\x8f\xe6\xfd\x7d\x7d";
        $server->send($fd, $message);
        return;
    }


    $isValidDn = $data{0} == "{" &&
        $data{1} == "{" &&
        ord($data{2}) == 0x90 &&
        ord($data{3}) == 0x01 &&
        ord($data{4}) == 0x03 &&
        ord($data{5}) == 0x04;
    if (!$isValidDn) {
        parseDn($data);
        file_put_contents(__DIR__ . "/log/" . "deviceId_{$deviceId}_Dn" . "--" . time() . "--" . uniqid() . ".txt", serialize($data)."\n\n\n".serialize($response));

        return;
    }
});
$server->on('close', function ($server, $fd) {
    echo "connection close: {$fd}\n";
});
$server->start();

