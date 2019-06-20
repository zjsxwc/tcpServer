<?php


$http = new Swoole\Http\Server("0.0.0.0", 9503);
$http->on('request', function ($request, $response) {
    $jsonList = "[";
    $ph = popen('tail -n 10 ' . __DIR__ . '/data.txt', 'r');
    while ($r = fgets($ph)) {
        $jsonList .= $r . ",";
    }
    $jsonList .= "]";
    pclose($ph);


    $response->end(
        "<!DOCTYPE html>
<html lang=\"zh-CN\">
<head>
    <meta charset=\"UTF-8\">
    <title>电表数据获取测试</title>
</head>
<body><pre id='textPre'></pre><script>var jsonList = " . $jsonList . ";

function in_array(search,array){
    for(var i in array){
        if(array[i]==search){
            return true;
        }
    }
    return false;
}

//console.log(jsonList);
var text = \"\";
for (var json of jsonList) {
    var line = '';
    for (var prop in json) {
        if (in_array(prop,['type','date', 'deviceId'])) {
            continue;
        }
        line += prop + \": \" + json[prop] + \", \"; 
    }
    line += \"\\n\\n\";
    var typeMap = {'Dn': '电能', 'Ldwd': '漏电温度', 'Dldy': '电流电压'};
    line =typeMap[json['type']] + ' '+ json['date'] + ' '+ json['deviceId'] + ' ' + line;
    text += line;
}
textPre.textContent = text;
</script></body>
</html>"
    );
});
$http->start();


