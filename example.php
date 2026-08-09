<?php
//例文の一覧ページ
require_once __DIR__ . '/view.php';

header('Content-Type: text/html; charset=UTF-8');

$dictionaryFile = __DIR__ . '/idyer.json';

$json = loadDictionary($dictionaryFile);
$exampleIndex = makeExampleIndex($json);
$examples = $exampleIndex['examples'];

//////リクエストパラメータの取り出し//////
$exampleAmount = count($examples);
$page = getParam("page");
$page = ($page !== null && preg_match("/^[0-9]+$/", $page)) ? max(1, (int)$page) : 1;
$page = min($page, max(1, (int)ceil($exampleAmount / EXAMPLES_PER_PAGE)));//存在しないページを指定された場合は最終ページに寄せる
$firstIndex = EXAMPLES_PER_PAGE * ($page - 1);

$pageMenu = buildPageMenu('example');
$pageScripts = array('dict.js');//表示前にフォントを確定させるため、head内で読み込む
require __DIR__ . '/header.php';
?>
		<div class="dictVer">
			<p>辞書更新日：<?php echo h(date("Y/m/d", filemtime($dictionaryFile))); ?><br />
			例文数：<?php echo h($exampleAmount); ?></p>
		</div>

		<!-- 検索ページと状態を共有するため、送信はせずdict.jsに任せる -->
		<div class="fontSwitch">
			<div class="buttonAndLabel"><input type="checkbox" id="c5"<?php echo checkedAttr(isIdfRequested()); ?>><label for="c5">イジェール文字表示</label></div>
		</div>
	</header>

	<main id="main">
	<?php
	if ($exampleAmount === 0){
		echo '<p>例文はまだ登録されていません。</p>';
	}else{
		echo '<p class="result">';
		echo_h('例文：' . $exampleAmount . '件(' . ($firstIndex + 1) . 'から' . min($firstIndex + EXAMPLES_PER_PAGE, $exampleAmount) . '件目)');
		echo '</p>';

		//例文からの単語リンクは、検索ページの既定の条件で引かせる
		foreach (array_slice($examples, $firstIndex, EXAMPLES_PER_PAGE) as $singleExample){
			echo '<div class="exampleEntry">';
			renderExample($singleExample, $exampleIndex, 'both', 'prt');
			echo '</div>';
		}
	}

	renderExampleNavigation($exampleAmount, $page);
	?>
	</main>
<?php require __DIR__ . '/footer.php'; ?>
