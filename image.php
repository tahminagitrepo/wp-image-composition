<?php

header("Content-Type: image/jpeg");

if(isset($_GET['path']) && isImage($_GET['path'])) {
    $url = $_GET['path'];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.1 Safari/537.11');
    $res = curl_exec($ch);
    $rescode = curl_getinfo($ch, CURLINFO_HTTP_CODE); 
    curl_close($ch) ;
    echo $res;
    die();
}


function isImage($l) {
    $arr = explode("?", $l);
    return preg_match("#\.(jpg|jpeg|png)$# i", $arr[0]);
}

function allowedDomain() {

}