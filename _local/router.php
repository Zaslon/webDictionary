<?php
//php -S（辞書チェック.bat）用のルータ。辞書は本番でサイト直下の /dict/ に置く前提で、
//メニューのリンク（config.php の pages）やService Workerの範囲も /dict/ を基準にしている。
//手元でも同じURLで開けるよう、/dict/ 以下をこのリポジトリのファイルに対応させる。
//
//ドキュメントルート（辞書チェック.bat の -t）は、隣に zaslon-site があればそこにする。
//サイト直下のアイコン（/favicon.ico 等）を本番と同じく本体から出すためで、無ければこのフォルダ（何も無い）になる。
//本体のフォルダの中に辞書は無いので、凡例は本体のレンダラではなく辞書側の簡易な変換（markdown.php）で出る。
$dictDir = dirname(__DIR__);
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

if ($path === '/' || $path === '/dict'){
	header('Location: /dict/dict.php');
	return true;
}

if (strpos($path, '/dict/') !== 0){
	return false;//アイコンなど、ドキュメントルートの実ファイルは組み込みサーバに任せる
}

$relative = substr($path, strlen('/dict/'));
if ($relative === ''){
	$relative = 'dict.php';
}
$file = realpath($dictDir . '/' . $relative);
//.. で辞書フォルダの外を読ませない。_ 始まり（このルータ・CSSの生成元）と tests は本番に送らないので出さない
if ($file === false || !is_file($file) || strpos($file, $dictDir . DIRECTORY_SEPARATOR) !== 0
	|| preg_match('#^(_|tests[/\\\\]|\.)#', $relative) === 1){
	http_response_code(404);
	echo 'Not Found';
	return true;
}

if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'php'){
	//pwa.php の appBasePath() は SCRIPT_NAME から辞書の置き場所（/dict/）を求める
	$_SERVER['SCRIPT_NAME'] = $_SERVER['PHP_SELF'] = '/dict/' . str_replace('\\', '/', $relative);
	$_SERVER['SCRIPT_FILENAME'] = $file;
	chdir(dirname($file));
	require $file;
	return true;
}

$types = array(
	'css' => 'text/css; charset=UTF-8', 'js' => 'text/javascript; charset=UTF-8',
	'json' => 'application/json; charset=UTF-8', 'csv' => 'text/csv; charset=UTF-8',
	'txt' => 'text/plain; charset=UTF-8', 'svg' => 'image/svg+xml', 'png' => 'image/png',
	'ico' => 'image/x-icon', 'woff' => 'font/woff', 'woff2' => 'font/woff2', 'ttf' => 'font/ttf',
);
$extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
header('Content-Type: ' . (isset($types[$extension]) ? $types[$extension] : 'application/octet-stream'));
header('Content-Length: ' . filesize($file));
readfile($file);
return true;
