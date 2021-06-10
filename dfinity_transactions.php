<?php
header('Content-Type:text/plain;charset=utf-8');
require 'thinkphp_5/vendor/autoload.php';
use QL\QueryList;
use Medoo\medoo;
require_once 'thinkphp_5/Medoo-1.7.10/src/Medoo.php';
ini_set('date.timezone','Asia/Shanghai');


//连接数据库
global $database;
$database =new medoo([
    'database_type' => 'mysql',
    'database_name' => 'dfinity',
    'server' => '127.0.0.1',
    'port' => '3306',
    'username' => 'root',
    'password' => '*',
    'charset' => 'utf8'
]);



function firstrequest($url){
$curl = curl_init();

curl_setopt_array($curl, array(
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS =>'{"network_identifier":{"blockchain":"Internet Computer","network":"00000000000000020101"}}',
    CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json'
    ),
));

$response = curl_exec($curl);
curl_close($curl);
return  $response;
}



function blockrequest($url2,$firstrequestnumber){

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $url2,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS =>'{"network_identifier":{"blockchain":"Internet Computer","network":"00000000000000020101"},"block_identifier":{"index":'.$firstrequestnumber.'}}',
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json'
        ),
    ));
    $response = curl_exec($curl);
    curl_close($curl);
    if(!$response){
     return false;
    }

    preg_match('/"index":'.$firstrequestnumber.',"hash":"(.*?)"}/', $response, $out3);//hash
    preg_match_all('/"value":"(.*?)",/', $response, $out4);//amount
    preg_match_all('/"address":"(.*?)"}/', $response, $out5);// from

    preg_match_all('/"address":"(.*?)"}/', $response, $out6);// to
    preg_match_all('/"timestamp":(.*?)}/', $response, $out7);//time
    //var_dump($response);
    $hashnumber=$out3[1];
    //var_dump($out3);
    if(empty($out4[1][1]) && empty($out6[1][1])){
        echo"为空";
        $value = str_replace("-", "", $out4[1][0]);
        $toaddress=$out6[1][0];

    }

    else{
        $value=$out4[1][1];
        $toaddress=$out6[1][1];
    }


    $count=$value/100000000;
    $fromaddress=$out5[1][0];
    $jasontime=$out7[1][0];
    $time=date('Y-m-d H:i:s',substr($jasontime,0,10));
    //整合数据


    $dbdata['Hash']=$hashnumber;
    $dbdata['From']=$fromaddress;
    $dbdata['To']=$toaddress;
    $dbdata['Amount']=$count;
    $dbdata['Time']=$time;
    //写入数据库
    //写入数据库
    echo "检查是否交易额大于100..\n";
    if($count>=100) {
        $GLOBALS['database']->insert('dfinity_block_info', $dbdata);
        echo "'$firstrequestnumber'写入成功\n";

    } else{

        echo "'$firstrequestnumber'的交易额为：'$count',小于100..\n";

    #var_dump($time);exit();

    }
}




$firstresult=firstrequest('https://rosetta-api.internetcomputer.org/network/status');
if(!$firstresult){
    exit('response error');
}
preg_match('/"current_index":(\d*?)}/', $firstresult, $out2);
//var_dump($out2);
$firstresult = $out2[1];

if(!file_get_contents("log.txt")){
    file_put_contents("log.txt",$firstresult);
}
else {
    $lognumber = file_get_contents("log.txt");
    while ($lognumber < $firstresult) {
        $lognumber++;
        blockrequest('https://rosetta-api.internetcomputer.org/block', $lognumber);
        file_put_contents("log.txt", $lognumber);


    }

}



