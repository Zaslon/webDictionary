<?php
//単語数の推移を表示するページ
require_once __DIR__ . '/func.php';

header('Content-Type: text/html; charset=UTF-8');

$pageMenu = buildPageMenu('chart');
$pageScripts = array('https://www.gstatic.com/charts/loader.js');
$pageBodyScripts = array('wordchart.js');
$pageHeading = '単語数推移';
$pageWide = true;
require __DIR__ . '/header.php';
?>
		<div id="wordchart"></div>
<?php require __DIR__ . '/footer.php'; ?>
