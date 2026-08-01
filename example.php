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
$page = ($page !== null && preg_match("/^[0-9]+$/", $page)) ? max(1, (int)$page) : 1;//ページIDに数字以外を入力された場合、強制的に1とする。
$page = min($page, max(1, (int)ceil($exampleAmount / EXAMPLES_PER_PAGE)));//存在しないページを指定された場合は最終ページに寄せる
$firstIndex = EXAMPLES_PER_PAGE * ($page - 1);

$pageMenu = array(
	'検索仕様'         => 'https://zaslon.info/idyerin/%e6%a4%9c%e7%b4%a2%e4%bb%95%e6%a7%98/',
	'凡例'             => 'https://zaslon.info/idyerin/%e8%be%9e%e6%9b%b8%e5%87%a1%e4%be%8b/',
	'検索ページへ戻る' => 'https://zaslon.info/dict/dict.php',
	'単語数推移'       => 'https://zaslon.info/dict/chart.php',
	'ホームへ戻る'     => 'https://zaslon.info/idyer',
);
require __DIR__ . '/header.php';
?>
		<div class="dictVer">
			<p>辞書更新日：<?php echo h(date("Y/m/d", filemtime($dictionaryFile))); ?><br />
			例文数：<?php echo h($exampleAmount); ?></p>
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
