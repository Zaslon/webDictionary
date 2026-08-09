<?php
//イジェール語オンライン辞書の検索ページ
require_once __DIR__ . '/search.php';
require_once __DIR__ . '/view.php';

header('Content-Type: text/html; charset=UTF-8');

$dictionaryFile = __DIR__ . '/idyer.json';
$affixTableFile = __DIR__ . '/affixTable.csv';

$json = loadDictionary($dictionaryFile);
$affixTable = loadAffixTable($affixTableFile);
$words = $json["words"];
$exampleIndex = makeExampleIndex($json);

//////リクエストパラメータの取り出し//////
$type = normalizeType(getParam("type"));
$mode = normalizeMode(getParam("mode"));
$includeVoicing = isVoicingRequested();
$keyBox = getParam("keyBox");

$id = getParam("id");
$id = ($id !== null && preg_match("/^[0-9]+$/", $id)) ? (int)$id : null;

$page = getParam("page");
$page = ($page !== null && preg_match("/^[0-9]+$/", $page)) ? max(1, (int)$page) : 1;

//訳語検索と前方一致はフォームから外しているため、次の検索では既定のラジオを選択させる
$checkedType = ($type === "trans") ? "both" : $type;
$checkedMode = ($mode === "fwd") ? "prt" : $mode;

//////検索//////
$keyWords = parseKeywords($keyBox, $type, $mode);
$suggestions = array();
$hitKeys = array();
if ($keyWords){
	if ($id !== null){
		//id指定は関連語や例文からの1語リンクなので、検索条件より優先する
		$entryKey = findEntryKeyById($words, $id);
		$hitKeys = ($entryKey === null) ? array() : array($entryKey);
	}else{
		$suggestions = findDerivationSuggestions($words, $affixTable, $keyWords[0]);
		$hitKeys = searchEntries($words, $keyWords, $type, $mode, $includeVoicing, $exampleIndex);
	}
}
$hitAmount = count($hitKeys);
$page = min($page, max(1, (int)ceil($hitAmount / WORDS_PER_PAGE)));//存在しないページを指定された場合は最終ページに寄せる
$firstIndex = WORDS_PER_PAGE * ($page - 1);

$pageMenu = buildPageMenu('dict');
$pageScripts = array('dict.js');//表示前にフォントを確定させるため、head内で読み込む
require __DIR__ . '/header.php';
?>
		<div class="dictVer">
			<p>プログラム更新日：<?php echo h(date("Y/m/d", programUpdatedAt())); ?></p>
			<p>辞書更新日：<?php echo h(date("Y/m/d", filemtime($dictionaryFile))); ?><br />
			単語数：<?php echo h(count($words)); ?></p>
		</div>

		<form action="" method="GET">
			<div class="textAndSubmit"><input type="text" name="keyBox" id="keyBox" aria-label="検索語" value="<?php echo h($keyBox); ?>"><input type="submit" name="submit" id="btn" value="検索"></div>
<!--		<div class='buttonAndLabel'><input type="radio" name="type" id="c1" value="word"<?php echo checkedAttr($checkedType === "word"); ?>><label for="c1">見出し語検索</label></div> -->
<!--		<div class='buttonAndLabel'><input type="radio" name="type" id="c2" value="trans"<?php echo checkedAttr($checkedType === "trans"); ?>><label for="c2">訳語検索</label></div> -->
			<div class="buttonAndLabel"><input type="radio" name="type" id="c3" value="both"<?php echo checkedAttr($checkedType === "both"); ?>><label for="c3">見出し語・訳語検索</label></div>
			<div class="buttonAndLabel"><input type="radio" name="type" id="c4" value="all"<?php echo checkedAttr($checkedType === "all"); ?>><label for="c4">全文検索</label></div>
			<div class="buttonAndLabel"><input type="checkbox" name="Idf" id="c5" value="true"<?php echo checkedAttr(isIdfRequested()); ?>><label for="c5">イジェール文字表示</label></div>
			<div class="buttonAndLabel"><input type="radio" name="mode" id="c6" value="prt"<?php echo checkedAttr($checkedMode === "prt"); ?>><label for="c6">部分一致</label></div>
<!--		<div class='buttonAndLabel'><input type="radio" name="mode" id="c7" value="fwd"<?php echo checkedAttr($checkedMode === "fwd"); ?>><label for="c7">前方一致</label></div> -->
			<div class="buttonAndLabel"><input type="radio" name="mode" id="c8" value="perf"<?php echo checkedAttr($checkedMode === "perf"); ?>><label for="c8">完全一致</label></div>
			<div class="buttonAndLabel"><input type="checkbox" name="voicing" id="c9" value="true"<?php echo checkedAttr($includeVoicing); ?>><label for="c9">検索対象に連濁派生語を含む</label></div>
			<input type="hidden" name="page" value="1">
		</form>
	</header>

	<main id="main">
	<?php
	if (!$keyWords){
		echo '<p>検索ワードを入力してください。</p>';
	}else{
		renderSuggestions($suggestions, $type, $mode);

		echo '<p class="result">';
		if ($hitAmount === 0){
			echo_h($keyBox . ' での検索結果：0件');
		}else{
			echo_h($keyBox . ' での検索結果：' . $hitAmount . '件(' . ($firstIndex + 1) . 'から' . min($firstIndex + WORDS_PER_PAGE, $hitAmount) . '件目)');
		}
		echo '</p>';

		foreach (array_slice($hitKeys, $firstIndex, WORDS_PER_PAGE) as $entryKey){
			renderEntry($words[$entryKey], $type, $mode, $exampleIndex);
		}
	}

	renderNavigation($hitAmount, $page, $keyWords, $type, $mode);
	?>
	</main>
<?php require __DIR__ . '/footer.php'; ?>
