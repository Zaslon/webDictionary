<?php
//単語数の推移を表示するページ
require_once __DIR__ . '/func.php';

header('Content-Type: text/html; charset=UTF-8');

$pageMenu = buildPageMenu('chart');
$pageScripts = array('https://www.gstatic.com/charts/loader.js');
$pageBodyScripts = array('wordchart.js');
require __DIR__ . '/header.php';
?>
	</header>

	<main id="main">
		<div id="wordchart"></div>
	</main>
<?php require __DIR__ . '/footer.php'; ?>
