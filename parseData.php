<?php


/**
 * 01字符串 转换为 整数
 * @param string $str
 * @param int $bitsCount
 * @return int
 */
function unsignedBinStrToInt($str, $bitsCount = 8) {
    if (strlen($str) != $bitsCount) {
        die("bitsCount error");
    }
    $int = null;
    eval("\$int = 0b". $str.";");
    return $int;
}
/**
 * 01字符串 转换为 有符号整数
 * @param string $str
 * @param int $bitsCount
 * @return int
 */
function signedBinStrToInt($str, $bitsCount = 8) {
    if (strlen($str) != $bitsCount) {
        die("bitsCount error");
    }

    if (intval($str{0}) == 0) {
        return unsignedBinStrToInt($str, $bitsCount);
    }
    // 无符号数 就是  有符号负数的 补码， 那么根据  有符号负数的绝对值  加上 它自己的补码，  就等于 溢出一位 ，也就是2的8次 256， 于是  有符号负数 等于  无符号数 减去 256
    return intval(unsignedBinStrToInt($str, $bitsCount) - pow(2, $bitsCount));
}



/**
 * @param string $data
 * @return mixed
 */
function parseLdwd($data) {
    $isValid = $data{0} == "{" &&
        $data{1} == "{" &&
        ord($data{2}) == 0x90 &&
        ord($data{3}) == 0x01 &&
        ord($data{4}) == 0x03 &&
        ord($data{5}) == 0x54;
    if (!$isValid) {
        echo "不是有效的Ldwd数据\n";
        return false;
    }

    $response = [];

    $dataStartPos = 6;
//    $byteOffset = 0;
//    $maxByteOffset = 84 - 1; //总共84字节，因为从0开始所以 最大偏移是 83

    //通道类别
    echo "\n通道类别:\n";
    $offset = 0x00;
    $byteOffset = $offset * 2;
    $h = ord($data{$dataStartPos + $byteOffset});
    printf("%08b ", $h); $response["通道类别_h"] = sprintf("%08b", $h);
    $l = ord($data{$dataStartPos + $byteOffset + 1});
    printf("%08b ", $l); $response["通道类别_l"] = sprintf("%08b", $l);
    // $h 与 $l 总共16个bit， 每个bit值0表示电流检测回路 值1表示温度检测回路 ， $l 最 右边的bit表示回路1  $h 最左边的 bit 表示回路16


    //断线
    echo "\n断线:\n";
    $offset = 0x01;
    $byteOffset = $offset * 2;
    $h = ord($data{$dataStartPos + $byteOffset});
    printf("%08b ", $h); $response["断线_h"] = sprintf("%08b", $h);
    $l = ord($data{$dataStartPos + $byteOffset + 1});
    printf("%08b ", $l); $response["断线_l"] = sprintf("%08b", $l);
    // $h 与 $l 总共16个bit， 每个bit值0表示回路正常 值1表示回路断线 ， $l 最 右边的bit表示回路1  $h 最左边的 bit 表示回路16


    //短路
    echo "\n短路:\n";
    $offset = 0x02;
    $byteOffset = $offset * 2;
    $h = ord($data{$dataStartPos + $byteOffset});
    printf("%08b ", $h); $response["短路_h"] = sprintf("%08b", $h);
    $l = ord($data{$dataStartPos + $byteOffset + 1});
    printf("%08b ", $l); $response["短路_l"] = sprintf("%08b", $l);
    // $h 与 $l 总共16个bit， 每个bit值0表示回路正常 值1表示回路短路 ， $l 最 右边的bit表示回路1  $h 最左边的 bit 表示回路16


    //报警状态
    echo "\n报警状态:\n";
    $offset = 0x03;
    $byteOffset = $offset * 2;
    $h = ord($data{$dataStartPos + $byteOffset});
    printf("%08b ", $h); $response["报警状态_h"] = sprintf("%08b", $h);
    $l = ord($data{$dataStartPos + $byteOffset + 1});
    printf("%08b ", $l); $response["报警状态_l"] = sprintf("%08b", $l);
    // $h 与 $l 总共16个bit， 每个bit值0表示回路正常 值1表示回路报警 ， $l 最 右边的bit表示回路1  $h 最左边的 bit 表示回路16


    //漏电测量值
    echo "\n漏电测量值:\n";
    $offset = 0x05;
    $byteOffset = $offset * 2;
    $h = ord($data{$dataStartPos + $byteOffset});
    $l = ord($data{$dataStartPos + $byteOffset + 1});
    $value = unsignedBinStrToInt(sprintf("%08b", $h) .  sprintf("%08b", $l), 16); //数值换算翻转高低位
    printf("%d (0b%08b 0b%08b)", $value, $h, $l); $response["漏电测量值"] = sprintf("%d", $value);


    echo "\n第一路温度测量值:\n";
    $offset = 0x06;
    $byteOffset = $offset * 2;
    $h = ord($data{$dataStartPos + $byteOffset});
    $l = ord($data{$dataStartPos + $byteOffset + 1});
    $value = signedBinStrToInt(sprintf("%08b", $h) .  sprintf("%08b", $l), 16); //数值换算翻转高低位
    printf("%.1f (0b%08b 0b%08b)", $value/10.0, $h, $l); $response["第一路温度测量值"] = sprintf("%.1f", $value/10.0);

    echo "\n第二路温度测量值:\n";
    $offset = 0x07;
    $byteOffset = $offset * 2;
    $h = ord($data{$dataStartPos + $byteOffset});
    $l = ord($data{$dataStartPos + $byteOffset + 1});
    $value = signedBinStrToInt(sprintf("%08b", $h) .  sprintf("%08b", $l), 16); //数值换算翻转高低位
    printf("%.1f (0b%08b 0b%08b)", $value/10.0, $h, $l); $response["第二路温度测量值"] = sprintf("%.1f", $value/10.0);

    echo "\n第三路温度测量值:\n";
    $offset = 0x08;
    $byteOffset = $offset * 2;
    $h = ord($data{$dataStartPos + $byteOffset});
    $l = ord($data{$dataStartPos + $byteOffset + 1});
    $value = signedBinStrToInt(sprintf("%08b", $h) .  sprintf("%08b", $l), 16); //数值换算翻转高低位
    printf("%.1f (0b%08b 0b%08b)", $value/10.0, $h, $l); $response["第三路温度测量值"] = sprintf("%.1f", $value/10.0);


    echo "\n第四路温度测量值:\n";
    $offset = 0x09;
    $byteOffset = $offset * 2;
    $h = ord($data{$dataStartPos + $byteOffset});
    $l = ord($data{$dataStartPos + $byteOffset + 1});
    $value = signedBinStrToInt(sprintf("%08b", $h) .  sprintf("%08b", $l), 16); //数值换算翻转高低位
    printf("%.1f (0b%08b 0b%08b)", $value/10.0, $h, $l); $response["第四路温度测量值"] = sprintf("%.1f", $value/10.0);


    echo "\n漏电报警测量值:\n";
    $offset = 0x15;
    $byteOffset = $offset * 2;
    $h = ord($data{$dataStartPos + $byteOffset});
    $l = ord($data{$dataStartPos + $byteOffset + 1});
    $value = unsignedBinStrToInt(sprintf("%08b", $h) .  sprintf("%08b", $l), 16); //数值换算翻转高低位
    printf("%d (0b%08b 0b%08b)", $value, $h, $l); $response["漏电报警测量值"] = sprintf("%d", $value);


    echo "\n第一路温度报警值:\n";
    $offset = 0x16;
    $byteOffset = $offset * 2;
    $h = ord($data{$dataStartPos + $byteOffset});
    $l = ord($data{$dataStartPos + $byteOffset + 1});
    $value = signedBinStrToInt(sprintf("%08b", $h) .  sprintf("%08b", $l), 16); //数值换算翻转高低位
    printf("%.1f (0b%08b 0b%08b)", $value/10.0, $h, $l); $response["第一路温度报警值"] = sprintf("%.1f", $value/10.0);


    echo "\n第二路温度报警值:\n";
    $offset = 0x17;
    $byteOffset = $offset * 2;
    $h = ord($data{$dataStartPos + $byteOffset});
    $l = ord($data{$dataStartPos + $byteOffset + 1});
    $value = signedBinStrToInt(sprintf("%08b", $h) .  sprintf("%08b", $l), 16); //数值换算翻转高低位
    printf("%.1f (0b%08b 0b%08b)", $value/10.0, $h, $l); $response["第二路温度报警值"] = sprintf("%.1f", $value/10.0);

    echo "\n第三路温度报警值:\n";
    $offset = 0x18;
    $byteOffset = $offset * 2;
    $h = ord($data{$dataStartPos + $byteOffset});
    $l = ord($data{$dataStartPos + $byteOffset + 1});
    $value = signedBinStrToInt(sprintf("%08b", $h) .  sprintf("%08b", $l), 16); //数值换算翻转高低位
    printf("%.1f (0b%08b 0b%08b)", $value/10.0, $h, $l); $response["第三路温度报警值"] = sprintf("%.1f", $value/10.0);

    echo "\n第四路温度报警值:\n";
    $offset = 0x19;
    $byteOffset = $offset * 2;
    $h = ord($data{$dataStartPos + $byteOffset});
    $l = ord($data{$dataStartPos + $byteOffset + 1});
    $value = signedBinStrToInt(sprintf("%08b", $h) .  sprintf("%08b", $l), 16); //数值换算翻转高低位
    printf("%.1f (0b%08b 0b%08b)", $value/10.0, $h, $l); $response["第四路温度报警值"] = sprintf("%.1f", $value/10.0);



    //开入DI
    echo "\n开入DI:\n";
    $offset = 0x28;
    $byteOffset = $offset * 2;
    $h = ord($data{$dataStartPos + $byteOffset});
    printf("%08b ", $h); $response["开入DI_h"] = sprintf("%08b", $h);
    $l = ord($data{$dataStartPos + $byteOffset + 1});
    printf("%08b ", $l); $response["开入DI_l"] = sprintf("%08b", $l);
    // $h 与 $l 总共16个bit， 每个bit值0表示DI打开 值1表示DI闭合 ， $l 最 右边的bit表示DI1  $h 最左边的 bit 表示DI16


    //开出DO
    echo "\n开出DO:\n";
    $offset = 0x29;
    $byteOffset = $offset * 2;
    $h = ord($data{$dataStartPos + $byteOffset});
    printf("%08b ", $h); $response["开出DO_h"] = sprintf("%08b", $h);
    $l = ord($data{$dataStartPos + $byteOffset + 1});
    printf("%08b ", $l); $response["开出DO_l"] = sprintf("%08b", $l);
    // $h 与 $l 总共16个bit， 每个bit值0表示DO闭合 值1表示DO打开 ， $l 最 右边的bit表示DO1  $h 最左边的 bit 表示DO16


    echo "\n";
    return $response;
}


/**
 * @param string $data
 * @return mixed
 */
function parseDldy($data) {
    $isValid = $data{0} == "{" &&
        $data{1} == "{" &&
        ord($data{2}) == 0x90 &&
        ord($data{3}) == 0x01 &&
        ord($data{4}) == 0x03 &&
        ord($data{5}) == 0x34;
    if (!$isValid) {
        echo "不是有效的Dldy数据\n";
        return false;
    }

    $response = [];
    $dataStartPos = 6;
//    $byteOffset = 0;
//    $maxByteOffset = 64 - 1; //总共64字节，因为从0开始所以 最大偏移是 63

    echo "\nA相相电压（V）:\n";
    $offset = 0;
    $byteOffset = $offset * 2;
    $h = ord($data{$dataStartPos + $byteOffset});
    $l = ord($data{$dataStartPos + $byteOffset + 1});
    $value = unsignedBinStrToInt(sprintf("%08b", $h) .  sprintf("%08b", $l), 16); //数值换算翻转高低位
    printf("%.1f (0b%08b 0b%08b)", $value/10.0, $h, $l); $response["A相相电压（V）"] = sprintf("%.1f", $value/10.0);

    echo "\nB相相电压（V）:\n";
    $offset = 1;
    $byteOffset = $offset * 2;
    $h = ord($data{$dataStartPos + $byteOffset});
    $l = ord($data{$dataStartPos + $byteOffset + 1});
    $value = unsignedBinStrToInt(sprintf("%08b", $h) .  sprintf("%08b", $l), 16); //数值换算翻转高低位
    printf("%.1f (0b%08b 0b%08b)", $value/10.0, $h, $l); $response["B相相电压（V）"] = sprintf("%.1f", $value/10.0);


    echo "\nC相相电压（V）:\n";
    $offset = 2;
    $byteOffset = $offset * 2;
    $h = ord($data{$dataStartPos + $byteOffset});
    $l = ord($data{$dataStartPos + $byteOffset + 1});
    $value = unsignedBinStrToInt(sprintf("%08b", $h) .  sprintf("%08b", $l), 16); //数值换算翻转高低位
    printf("%.1f (0b%08b 0b%08b)", $value/10.0, $h, $l); $response["C相相电压（V）"] = sprintf("%.1f", $value/10.0);


    echo "\nUAB线电压（V）:\n";
    $offset = 3;
    $byteOffset = $offset * 2;
    $h = ord($data{$dataStartPos + $byteOffset});
    $l = ord($data{$dataStartPos + $byteOffset + 1});
    $value = unsignedBinStrToInt(sprintf("%08b", $h) .  sprintf("%08b", $l), 16); //数值换算翻转高低位
    printf("%.1f (0b%08b 0b%08b)", $value/10.0, $h, $l); $response["UAB线电压（V）"] = sprintf("%.1f", $value/10.0);


    echo "\nUBC线电压（V）:\n";
    $offset = 4;
    $byteOffset = $offset * 2;
    $h = ord($data{$dataStartPos + $byteOffset});
    $l = ord($data{$dataStartPos + $byteOffset + 1});
    $value = unsignedBinStrToInt(sprintf("%08b", $h) .  sprintf("%08b", $l), 16); //数值换算翻转高低位
    printf("%.1f (0b%08b 0b%08b)", $value/10.0, $h, $l); $response["UBC线电压（V）"] = sprintf("%.1f", $value/10.0);



    echo "\nUCA线电压（V）:\n";
    $offset = 5;
    $byteOffset = $offset * 2;
    $h = ord($data{$dataStartPos + $byteOffset});
    $l = ord($data{$dataStartPos + $byteOffset + 1});
    $value = unsignedBinStrToInt(sprintf("%08b", $h) .  sprintf("%08b", $l), 16); //数值换算翻转高低位
    printf("%.1f (0b%08b 0b%08b)", $value/10.0, $h, $l); $response["UCA线电压（V）"] = sprintf("%.1f", $value/10.0);



    echo "\n电压状态位:\n";
    $offset = 9;
    $byteOffset = $offset * 2;
    $h = ord($data{$dataStartPos + $byteOffset});
    $l = ord($data{$dataStartPos + $byteOffset + 1});
    printf("高字节 0x%02x 低字节 0x%02x\n", $h, $l); $response["电压状态位"] = sprintf("高字节 0x%02x 低字节 0x%02x", $h, $l);


    echo "\nA相过压值（V）:\n";
    $offset = 10;
    $byteOffset = $offset * 2;
    $h = ord($data{$dataStartPos + $byteOffset});
    $l = ord($data{$dataStartPos + $byteOffset + 1});
    $value = unsignedBinStrToInt(sprintf("%08b", $h) .  sprintf("%08b", $l), 16); //数值换算翻转高低位
    printf("%.1f (0b%08b 0b%08b)", $value/10.0, $h, $l); $response["A相过压值（V）"] = sprintf("%.1f", $value/10.0);

    echo "\nB相过压值（V）:\n";
    $offset = 11;
    $byteOffset = $offset * 2;
    $h = ord($data{$dataStartPos + $byteOffset});
    $l = ord($data{$dataStartPos + $byteOffset + 1});
    $value = unsignedBinStrToInt(sprintf("%08b", $h) .  sprintf("%08b", $l), 16); //数值换算翻转高低位
    printf("%.1f (0b%08b 0b%08b)", $value/10.0, $h, $l); $response["B相过压值（V）"] = sprintf("%.1f", $value/10.0);

    echo "\nC相过压值（V）:\n";
    $offset = 12;
    $byteOffset = $offset * 2;
    $h = ord($data{$dataStartPos + $byteOffset});
    $l = ord($data{$dataStartPos + $byteOffset + 1});
    $value = unsignedBinStrToInt(sprintf("%08b", $h) .  sprintf("%08b", $l), 16); //数值换算翻转高低位
    printf("%.1f (0b%08b 0b%08b)", $value/10.0, $h, $l); $response["C相过压值（V）"] = sprintf("%.1f", $value/10.0);

    echo "\nA相欠压值（V）:\n";
    $offset = 13;
    $byteOffset = $offset * 2;
    $h = ord($data{$dataStartPos + $byteOffset});
    $l = ord($data{$dataStartPos + $byteOffset + 1});
    $value = unsignedBinStrToInt(sprintf("%08b", $h) .  sprintf("%08b", $l), 16); //数值换算翻转高低位
    printf("%.1f (0b%08b 0b%08b)", $value/10.0, $h, $l); $response["A相欠压值（V）"] = sprintf("%.1f", $value/10.0);

    echo "\nB相欠压值（V）:\n";
    $offset = 14;
    $byteOffset = $offset * 2;
    $h = ord($data{$dataStartPos + $byteOffset});
    $l = ord($data{$dataStartPos + $byteOffset + 1});
    $value = unsignedBinStrToInt(sprintf("%08b", $h) .  sprintf("%08b", $l), 16); //数值换算翻转高低位
    printf("%.1f (0b%08b 0b%08b)", $value/10.0, $h, $l); $response["B相欠压值（V）"] = sprintf("%.1f", $value/10.0);

    echo "\nC相欠压值（V）:\n";
    $offset = 15;
    $byteOffset = $offset * 2;
    $h = ord($data{$dataStartPos + $byteOffset});
    $l = ord($data{$dataStartPos + $byteOffset + 1});
    $value = unsignedBinStrToInt(sprintf("%08b", $h) .  sprintf("%08b", $l), 16); //数值换算翻转高低位
    printf("%.1f (0b%08b 0b%08b)", $value/10.0, $h, $l); $response["C相欠压值（V）"] = sprintf("%.1f", $value/10.0);


    echo "\nA相电流（A）:\n";
    $offset = 16;
    $byteOffset = $offset * 2;
    $h = ord($data{$dataStartPos + $byteOffset});
    $l = ord($data{$dataStartPos + $byteOffset + 1});
    $value = unsignedBinStrToInt(sprintf("%08b", $h) .  sprintf("%08b", $l), 16); //数值换算翻转高低位
    printf("%.3f (0b%08b 0b%08b)", $value/1000.0, $h, $l); $response["A相电流（A）"] = sprintf("%.3f", $value/1000.0);


    echo "\nB相电流（A）:\n";
    $offset = 17;
    $byteOffset = $offset * 2;
    $h = ord($data{$dataStartPos + $byteOffset});
    $l = ord($data{$dataStartPos + $byteOffset + 1});
    $value = unsignedBinStrToInt(sprintf("%08b", $h) .  sprintf("%08b", $l), 16); //数值换算翻转高低位
    printf("%.3f (0b%08b 0b%08b)", $value/1000, $h, $l); $response["B相电流（A）"] = sprintf("%.3f", $value/1000.0);

    echo "\nC相电流（A）:\n";
    $offset = 18;
    $byteOffset = $offset * 2;
    $h = ord($data{$dataStartPos + $byteOffset});
    $l = ord($data{$dataStartPos + $byteOffset + 1});
    $value = unsignedBinStrToInt(sprintf("%08b", $h) .  sprintf("%08b", $l), 16); //数值换算翻转高低位
    printf("%.3f (0b%08b 0b%08b)", $value/1000, $h, $l); $response["C相电流（A）"] = sprintf("%.3f", $value/1000.0);



    echo "\n电流状态:\n";
    $offset = 22;
    $byteOffset = $offset * 2;
    $h = ord($data{$dataStartPos + $byteOffset});
    $l = ord($data{$dataStartPos + $byteOffset + 1});
    printf("0x%02x 0x%02x", $h, $l); $response["电流状态"] = sprintf("0x%02x 0x%02x", $h, $l);


    echo "\nA相过流值（A）:\n";
    $offset = 23;
    $byteOffset = $offset * 2;
    $h = ord($data{$dataStartPos + $byteOffset});
    $l = ord($data{$dataStartPos + $byteOffset + 1});
    $value = unsignedBinStrToInt(sprintf("%08b", $h) .  sprintf("%08b", $l), 16); //数值换算翻转高低位
    printf("%.3f (0b%08b 0b%08b)", $value/1000, $h, $l); $response["A相过流值（A）"] = sprintf("%.3f", $value/1000.0);

    echo "\nB相过流值（A）:\n";
    $offset = 24;
    $byteOffset = $offset * 2;
    $h = ord($data{$dataStartPos + $byteOffset});
    $l = ord($data{$dataStartPos + $byteOffset + 1});
    $value = unsignedBinStrToInt(sprintf("%08b", $h) .  sprintf("%08b", $l), 16); //数值换算翻转高低位
    printf("%.3f (0b%08b 0b%08b)", $value/1000, $h, $l); $response["B相过流值（A）"] = sprintf("%.3f", $value/1000.0);

    echo "\nC相过流值（A）:\n";
    $offset = 25;
    $byteOffset = $offset * 2;
    $h = ord($data{$dataStartPos + $byteOffset});
    $l = ord($data{$dataStartPos + $byteOffset + 1});
    $value = unsignedBinStrToInt(sprintf("%08b", $h) .  sprintf("%08b", $l), 16); //数值换算翻转高低位
    printf("%.3f (0b%08b 0b%08b)", $value/1000, $h, $l); $response["C相过流值（A）"] = sprintf("%.3f", $value/1000.0);

    echo "\n";

    return $response;
}



/**
 * @param string $data
 * @return mixed
 */
function parseDn($data) {
    $isValid = $data{0} == "{" &&
        $data{1} == "{" &&
        ord($data{2}) == 0x90 &&
        ord($data{3}) == 0x01 &&
        ord($data{4}) == 0x03 &&
        ord($data{5}) == 0x04;
    if (!$isValid) {
        echo "不是有效的Dn数据\n";
        return false;
    }

    $response = [];
    $dataStartPos = 6;

    echo "\n吸收有功功率（kWh）:\n";
    $offset = 0;
    $byteOffset = $offset * 2;
    $h1 = ord($data{$dataStartPos + $byteOffset});
    $l1 = ord($data{$dataStartPos + $byteOffset + 1});
    $offset = 1;
    $byteOffset = $offset * 2;
    $h2 = ord($data{$dataStartPos + $byteOffset});
    $l2 = ord($data{$dataStartPos + $byteOffset + 1});
//    printf("0x%02x 0x%02x 0x%02x 0x%02x \n", $h1, $l1, $h2, $l2);
    $value = unsignedBinStrToInt(sprintf("%08b", $h1) . sprintf("%08b", $l1) . sprintf("%08b", $h2) . sprintf("%08b", $l2), 32);
    printf("%.3f \n", $value / 1000.0); $response["吸收有功功率（kWh）"] = sprintf("%.3f", $value/1000.0);

    echo "\n";

    return $response;
}

