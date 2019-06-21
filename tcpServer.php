<?php

date_default_timezone_set('Asia/Shanghai');

define("DEBUG_PRINTF", true);

include_once "parseData.php";


$table = new Swoole\Table(10240);
$table->column('fd', swoole_table::TYPE_INT, 8);
$table->column('deviceId', swoole_table::TYPE_STRING, 32);
$table->column('lastRequestMessage', swoole_table::TYPE_INT, 4);
$table->create();

$server = new swoole_server("0.0.0.0", 9504);
$server->on('connect', function ($server, $fd) {
    debugPrintf("connection open: {$fd}\n");
});

$server->on('Receive', function ($server, $fd, $reactor_id, $data) use ($table) {
    /** @var swoole_server $server */

    if (trim($data) == "bye") {
        $server->close($fd);
        return;
    }

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
        $table->set($fd, ["fd" => $fd, "deviceId" => $deviceId, "lastRequestMessage" => 0]);
        //发送设备同意注册报文
        $message = "\x7b\x7b\x84\xbf\x23\x7d\x7d";
        $server->send($fd, $message);

        $server->tick(20000, function ($timerId) use ($server, $fd, $table) {
            debugPrintf("tick fd%d timerid %d\n", $fd, $timerId);
            $row = $table->get($fd);
            if (!$row) {
                $server->clearTimer($timerId);
                return;
            }
            $lastRequestMessage = intval($row["lastRequestMessage"]) % 3;
            $requestMessage = 0;
            if ($lastRequestMessage == 0) {
                $requestMessage = 1;
                //发送指令获取设备通讯报文 漏电温度
                $message = "\x7b\x7b\x90\x01\x03\x10\x00\x00\x2a\xc0\xd5\xe6\xfd\x7d\x7d";
            }
            if ($lastRequestMessage == 1) {
                $requestMessage = 2;
                //发送指令获取设备通讯报文 电压电流
                $message = "\x7b\x7b\x90\x01\x03\x12\x04\x00\x1a\x80\xb8\xe6\xfd\x7d\x7d";
            }
            if ($lastRequestMessage == 2) {
                $requestMessage = 3;
                //发送指令获取设备通讯报文 电能
                $message = "\x7b\x7b\x90\x01\x03\x13\x00\x00\x02\xc0\x8f\xe6\xfd\x7d\x7d";
            }
            $row["lastRequestMessage"] = $requestMessage;
            $table->set($fd, $row);
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

        $response["type"] = "Ldwd";
        $response["deviceId"] = $deviceId;
        $response["time"] = time();
        $response["date"] = date("Y-m-d H:i:s", time());

        file_put_contents(__DIR__ . "/data.txt", json_encode($response)."\n", FILE_APPEND);

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

        $response["type"] = "Dldy";
        $response["deviceId"] = $deviceId;
        $response["time"] = time();
        $response["date"] = date("Y-m-d H:i:s", time());

        file_put_contents(__DIR__ . "/data.txt", json_encode($response)."\n", FILE_APPEND);

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

        $response["type"] = "Dn";
        $response["deviceId"] = $deviceId;
        $response["time"] = time();
        $response["date"] = date("Y-m-d H:i:s", time());

        file_put_contents(__DIR__ . "/data.txt", json_encode($response)."\n", FILE_APPEND);

        return;
    }
});

$server->on('Close', function ($server, $fd) use ($table) {
    $table->del($fd);
    debugPrintf("connection close: {$fd}\n");
});
$server->start();

