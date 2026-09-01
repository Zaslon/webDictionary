<?php
//maskable用アイコンを返すファイル。中身の生成はpwa.phpのmaskableIconPng()が行う。
//URLの?vは版に過ぎず、実際に使うのは?iが指すconfig.phpのapp_icons側の絵柄と倍率
require_once __DIR__ . '/pwa.php';

$config = dictConfig();
$index = (int)getParam('i');

if (!isset($config['app_icons'][$index])){
	http_response_code(404);
	exit;
}

$png = maskableIconPng($config['app_icons'][$index]);
if ($png === null){
	http_response_code(404);
	exit;
}

header('Content-Type: image/png');
//URLに版(?v)が入っており絵柄か倍率を変えたときだけ変わるため、長く覚えさせてよい
header('Cache-Control: public, max-age=604800, immutable');
echo $png;
