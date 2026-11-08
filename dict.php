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

//検索条件の付いたURLは語ごとに際限なく増えるため、検索エンジンには条件の無い dict.php だけを載せる。
//canonical だけでは、中身の違うページとして個別に載せられることがある
$pageNoIndex = ($_GET !== array());
$pageMenu = buildPageMenu('dict');
$pageScripts = array('dict.js');//表示前にフォントを確定させるため、head内で読み込む
//発音記号の生成とインクリメンタルサーチ。検索結果より後で構わないため、描画を止めずに読み込む
$pageDeferredScripts = array('vendor/akrantiain.min.js', 'pronunciation.js', 'livesearch.js');
require __DIR__ . '/header.php';
?>
		<div class="dictVer">
			<p>プログラム更新日：<?php echo h(date("Y/m/d", programUpdatedAt())); ?></p>
			<p>辞書更新日：<?php echo h(date("Y/m/d", filemtime($dictionaryFile))); ?></p>
			<p>単語数：<?php echo h(count($words)); ?></p>
		</div>

		<?php //部品は ZasDesignSystem の検索ボックス・ラジオグループ・チェックボックス。id と name は livesearch.js・dict.js が使う ?>
		<form id="searchForm" class="searchForm" action="" method="GET" role="search">
			<div class="sw-search-box" data-size="md">
				<div class="sw-search-box__fields">
					<label class="sw-search-box__input">
						<svg class="sw-search-box__input-icon" width="24" height="24" viewBox="0 0 24 24" aria-hidden="true"><path d="m21 20.5-6-6a7.4 7.4 0 0 0 1.9-5A7.4 7.4 0 0 0 9.5 2 7.5 7.5 0 1 0 14 15.5l6 6 1-1ZM3.5 9.5a6 6 0 0 1 6-6 6 6 0 0 1 6 6 6 6 0 0 1-6 6 6 6 0 0 1-6-6Z" fill="currentcolor"/></svg>
						<span class="dads-u-visually-hidden">検索語</span>
						<input class="sw-search-box__input-control" type="search" name="keyBox" id="keyBox" placeholder="検索語を入力" value="<?php echo h($request['keyBox']); ?>" autocomplete="off" enterkeyhint="search">
					</label>
				</div>
				<button class="dads-button sw-search-box__submit" type="submit" name="submit" value="検索" id="btn" data-type="solid-fill" data-size="md">検索</button>
			</div>
			<div class="searchOptions">
				<div class="sw-radio-group" role="radiogroup" aria-labelledby="typeLabel">
					<span class="sw-radio-group__legend" id="typeLabel">対象</span>
					<span class="sw-radio-group__options">
<!--						<label class="sw-radio-group__option"><input class="sw-radio-group__input" type="radio" name="type" id="c1" value="word"<?php echo checkedAttr($checkedType === "word"); ?>><span class="sw-radio-group__label">見出し語</span></label> -->
<!--						<label class="sw-radio-group__option"><input class="sw-radio-group__input" type="radio" name="type" id="c2" value="trans"<?php echo checkedAttr($checkedType === "trans"); ?>><span class="sw-radio-group__label">訳語</span></label> -->
						<label class="sw-radio-group__option"><input class="sw-radio-group__input" type="radio" name="type" id="c3" value="both"<?php echo checkedAttr($checkedType === "both"); ?>><span class="sw-radio-group__label">見出し語・訳語</span></label>
						<label class="sw-radio-group__option"><input class="sw-radio-group__input" type="radio" name="type" id="c4" value="all"<?php echo checkedAttr($checkedType === "all"); ?>><span class="sw-radio-group__label">全文</span></label>
					</span>
				</div>
				<div class="sw-radio-group" role="radiogroup" aria-labelledby="modeLabel">
					<span class="sw-radio-group__legend" id="modeLabel">方式</span>
					<span class="sw-radio-group__options">
						<label class="sw-radio-group__option"><input class="sw-radio-group__input" type="radio" name="mode" id="c6" value="prt"<?php echo checkedAttr($checkedMode === "prt"); ?>><span class="sw-radio-group__label">部分一致</span></label>
<!--						<label class="sw-radio-group__option"><input class="sw-radio-group__input" type="radio" name="mode" id="c7" value="fwd"<?php echo checkedAttr($checkedMode === "fwd"); ?>><span class="sw-radio-group__label">前方一致</span></label> -->
						<label class="sw-radio-group__option"><input class="sw-radio-group__input" type="radio" name="mode" id="c8" value="perf"<?php echo checkedAttr($checkedMode === "perf"); ?>><span class="sw-radio-group__label">完全一致</span></label>
					</span>
				</div>
				<div class="optionChecks">
					<label class="dads-checkbox" data-size="sm">
						<span class="dads-checkbox__checkbox"><input class="dads-checkbox__input" type="checkbox" name="voicing" id="c9" value="true"<?php echo checkedAttr($request['includeVoicing']); ?>></span>
						<span class="dads-checkbox__label">連濁派生語を含む</span>
					</label>
					<label class="dads-checkbox" data-size="sm">
						<span class="dads-checkbox__checkbox"><input class="dads-checkbox__input" type="checkbox" name="Idf" id="c5" value="true"<?php echo checkedAttr(isIdfRequested()); ?>></span>
						<span class="dads-checkbox__label">イジェール文字表示</span>
					</label>
				</div>
			</div>
			<input type="hidden" name="page" value="1">
		</form>

		<p id="searchStatus" class="searchStatus" role="status" hidden></p>
		<?php //results.php の応答と突き合わせるテストがあるため、#results の開閉タグと中身の間に空白を入れない ?>
		<div id="results"><?php renderSearchResults($words, $exampleIndex, isset($json['snoj']) ? $json['snoj'] : null, $request, $searchResult); ?></div>
<?php require __DIR__ . '/footer.php'; ?>
