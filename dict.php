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

$request = readSearchRequest();
$searchResult = runSearch($words, $affixTable, $exampleIndex, $request);

//訳語検索と前方一致はフォームから外しているため、次の検索では既定のラジオを選択させる
$checkedType = ($request['type'] === "trans") ? "both" : $request['type'];
$checkedMode = ($request['mode'] === "fwd") ? "prt" : $request['mode'];

$pageMenu = buildPageMenu('dict');
$pageScripts = array('dict.js');//表示前にフォントを確定させるため、head内で読み込む
//発音記号の生成とインクリメンタルサーチ。検索結果より後で構わないため、描画を止めずに読み込む
$pageDeferredScripts = array('vendor/akrantiain.min.js', 'pronunciation.js', 'livesearch.js');
require __DIR__ . '/header.php';
?>
		<div class="dictVer">
			<p>プログラム更新日：<?php echo h(date("Y/m/d", programUpdatedAt())); ?></p>
			<p>辞書更新日：<?php echo h(date("Y/m/d", filemtime($dictionaryFile))); ?><br />
			単語数：<?php echo h(count($words)); ?></p>
		</div>

		<form id="searchForm" action="" method="GET">
			<div class="textAndSubmit"><input type="text" name="keyBox" id="keyBox" aria-label="検索語" value="<?php echo h($request['keyBox']); ?>" autocomplete="off" enterkeyhint="search"><input type="submit" name="submit" id="btn" value="検索"></div>
<!--		<div class='buttonAndLabel'><input type="radio" name="type" id="c1" value="word"<?php echo checkedAttr($checkedType === "word"); ?>><label for="c1">見出し語検索</label></div> -->
<!--		<div class='buttonAndLabel'><input type="radio" name="type" id="c2" value="trans"<?php echo checkedAttr($checkedType === "trans"); ?>><label for="c2">訳語検索</label></div> -->
			<div class="buttonAndLabel"><input type="radio" name="type" id="c3" value="both"<?php echo checkedAttr($checkedType === "both"); ?>><label for="c3">見出し語・訳語検索</label></div>
			<div class="buttonAndLabel"><input type="radio" name="type" id="c4" value="all"<?php echo checkedAttr($checkedType === "all"); ?>><label for="c4">全文検索</label></div>
			<div class="buttonAndLabel"><input type="checkbox" name="Idf" id="c5" value="true"<?php echo checkedAttr(isIdfRequested()); ?>><label for="c5">イジェール文字表示</label></div>
			<div class="buttonAndLabel"><input type="radio" name="mode" id="c6" value="prt"<?php echo checkedAttr($checkedMode === "prt"); ?>><label for="c6">部分一致</label></div>
<!--		<div class='buttonAndLabel'><input type="radio" name="mode" id="c7" value="fwd"<?php echo checkedAttr($checkedMode === "fwd"); ?>><label for="c7">前方一致</label></div> -->
			<div class="buttonAndLabel"><input type="radio" name="mode" id="c8" value="perf"<?php echo checkedAttr($checkedMode === "perf"); ?>><label for="c8">完全一致</label></div>
			<div class="buttonAndLabel"><input type="checkbox" name="voicing" id="c9" value="true"<?php echo checkedAttr($request['includeVoicing']); ?>><label for="c9">検索対象に連濁派生語を含む</label></div>
			<input type="hidden" name="page" value="1">
		</form>
	</header>

	<main id="main">
		<p id="searchStatus" class="searchStatus" role="status" hidden></p>
		<?php //results.php の応答と突き合わせるテストがあるため、#results の開閉タグと中身の間に空白を入れない ?>
		<div id="results"><?php renderSearchResults($words, $exampleIndex, isset($json['snoj']) ? $json['snoj'] : null, $request, $searchResult); ?></div>
	</main>
<?php require __DIR__ . '/footer.php'; ?>
