<?php

include_once "parseData.php";

@mkdir(__DIR__ . "/log");

$server = new swoole_server("0.0.0.0", 9504);
$server->on('connect', function ($server, $fd) {
    echo "connection open: {$fd}\n";
});


$table = new Swoole\Table(10240);
$table->column('fd', swoole_table::TYPE_INT, 8);
$table->column('deviceId', swoole_table::TYPE_STRING, 1024);
$table->column('lastRequestTime', swoole_table::TYPE_INT, 8);
$table->create();


$server->on('Receive', function ($server, $fd, $reactor_id, $data) use ($table) {
    /** @var swoole_server $server */

    if (trim($data) == "bye") {
        $server->close($fd);
        return;
    }

    $currentTime = time();

    $deviceId = null;
    $row = $table->get($fd);
    if ($row) {
        $deviceId = $row["deviceId"];
    }

    $isRegisterDeviceId =
        $data{0} == "{" &&
        $data{1} == "{" &&
        ord($data{2}) == 0x84;
    if (trim($data) == "123451234512345") {
        $isRegisterDeviceId = true;
    }
    if ($isRegisterDeviceId) {
        $deviceId = substr($data, 3, 14);
        if ($row) {
            if ($row["deviceId"] != $deviceId) {
                $server->close($fd);
            }
            return;
        }
        $table->set($fd, ["fd" => $fd, "deviceId" => $deviceId, "lastRequestTime" => $currentTime]);
        //发送设备同意注册报文
        $message = "\x7b\x7b\x84\xbf\x23\x7d\x7d";
        $server->send($fd, $message);

        $server->tick(20000, function ($timerId) use ($server, $fd, $table) {
            echo sprintf("tick fd%d timerid %d\n", $fd, $timerId);
            $row = $table->get($fd);
            if (!$row) {
                $server->clearTimer($timerId);
                return;
            }

            //发送指令获取设备通讯报文 漏电温度
            $message = "\x7b\x7b\x90\x01\x03\x10\x00\x00\x2a\xc0\xd5\xe6\xfd\x7d\x7d";
            $server->send($fd, $message);
            sleep(1);

            //发送指令获取设备通讯报文 电压电流
            $message = "\x7b\x7b\x90\x01\x03\x12\x04\x00\x1a\x80\xb8\xe6\xfd\x7d\x7d";
            $server->send($fd, $message);
            sleep(1);

            //发送指令获取设备通讯报文 电压电流
            $message = "\x7b\x7b\x90\x01\x03\x13\x00\x00\x02\xc0\x8f\xe6\xfd\x7d\x7d";
            $server->send($fd, $message);
        });

        return;
    }

    if (!$deviceId) {
        $server->close($fd);
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
        file_put_contents(__DIR__ . "/log/" . "deviceId_{$deviceId}_Ldwd" . "--" . time() . "--" . uniqid() . ".txt", serialize($data) . "\n\n\n" . serialize($response));

        return;
    }

    $isValidDldy = $data{0} == "{" &&
        $data{1} == "{" &&
        ord($data{2}) == 0x90 &&
        ord($data{3}) == 0x01 &&
        ord($data{4}) == 0x03 &&
        ord($data{5}) == 0x34;
    if ($isValidDldy) {
        $response = parseDldy($data);
        file_put_contents(__DIR__ . "/log/" . "deviceId_{$deviceId}_Dldy" . "--" . time() . "--" . uniqid() . ".txt", serialize($data) . "\n\n\n" . serialize($response));

        return;
    }

    $isValidDn = $data{0} == "{" &&
        $data{1} == "{" &&
        ord($data{2}) == 0x90 &&
        ord($data{3}) == 0x01 &&
        ord($data{4}) == 0x03 &&
        ord($data{5}) == 0x04;
    if ($isValidDn) {
        $response = parseDn($data);
        file_put_contents(__DIR__ . "/log/" . "deviceId_{$deviceId}_Dn" . "--" . time() . "--" . uniqid() . ".txt", serialize($data) . "\n\n\n" . serialize($response));

        return;
    }
});

$server->on('Close', function ($server, $fd) use ($table) {
    $table->del($fd);
    echo "connection close: {$fd}\n";
});
$server->start();

