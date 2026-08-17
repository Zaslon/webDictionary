<?php
//凡例のページ。中身は辞書データ（idyer.json）のlegendをそのまま読む
require_once __DIR__ . '/view.php';

header('Content-Type: text/html; charset=UTF-8');

$dictionaryFile = __DIR__ . '/idyer.json';

$json = loadDictionary($dictionaryFile);
$legend = isset($json['legend']) ? $json['legend'] : null;

$pageMenu = buildPageMenu('legend');
require __DIR__ . '/header.php';
?>
		<div class="dictVer">
			<p>プログラム更新日：<?php echo h(date("Y/m/d", programUpdatedAt())); ?></p>
			<p>辞書更新日：<?php echo h(date("Y/m/d", filemtime($dictionaryFile))); ?></p>
		</div>
	</header>

	<main id="main">
	<?php renderLegend($legend); ?>
	</main>
<?php require __DIR__ . '/footer.php'; ?>
