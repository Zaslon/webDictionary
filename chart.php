<?php
//単語数の推移を表示するページ
require_once __DIR__ . '/func.php';

header('Content-Type: text/html; charset=UTF-8');

$pageMenu = array(
	'検索仕様'         => 'https://zaslon.info/idyerin/%e6%a4%9c%e7%b4%a2%e4%bb%95%e6%a7%98/',
	'凡例'             => 'https://zaslon.info/idyerin/%e8%be%9e%e6%9b%b8%e5%87%a1%e4%be%8b/',
	'検索ページへ戻る' => 'https://zaslon.info/dict/dict.php',
	'例文一覧'         => 'https://zaslon.info/dict/example.php',
	'ホームへ戻る'     => 'https://zaslon.info/idyer',
);
$pageScripts = array('https://www.gstatic.com/charts/loader.js');
$pageBodyScripts = array('wordchart.js');
require __DIR__ . '/header.php';
?>
	</header>

	<main id="main">
		<div id="wordchart"></div>
	</main>
<?php require __DIR__ . '/footer.php'; ?>
