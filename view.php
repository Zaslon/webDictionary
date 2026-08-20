<?php
//検索結果の表示
require_once __DIR__ . '/func.php';

function renderSuggestions(array $suggestions, $type, $mode){
	foreach ($suggestions as $singleSuggestion){
		echo '<p class="suggest">もしかして、';
		echo makeLink($singleSuggestion['form'], $type, $mode, 1, $singleSuggestion['id']);
		echo h($singleSuggestion['form']), '</a>';
		echo '<span class="wordId">#', h($singleSuggestion['id']), '</span>';
		echo 'の ', h($singleSuggestion['description']), ' ? </p>';
	}
}

//$exampleIndex は makeExampleIndex() の返り値。渡すとその単語を使う例文も並べる
function renderEntry(array $entry, $type, $mode, array $exampleIndex = array()){
	echo '<ul class="wordEntry">';
	renderWordForm($entry);
	renderTranslations($entry);
	renderContents($entry);
	renderRelations($entry, $type, $mode);
	renderExamples($entry, $exampleIndex, $type, $mode);
	echo '</ul>';
}

function renderWordForm(array $entry){
	$form = $entry["entry"]["form"];
	echo '<li class="wordForm"><span title="', h($form), '">', h($form), '</span>';
	renderPronunciation($entry);
	echo '</li>';
}

//辞書データに発音記号を持つのは綴りから導けない頭字語だけなので、
//それ以外は見出し語を渡して空欄で置き、pronunciation.jsがsnoj規則から埋める
function renderPronunciation(array $entry){
	foreach ($entry["contents"] as $singleContent){
		if ($singleContent["title"] !== PRONUNCIATION_TITLE){
			continue;
		}
		$pronunciation = trim($singleContent["text"]);
		if ($pronunciation !== ''){
			echo '<span class="wordPronunciation">/', h($pronunciation), '/</span>';
			return;
		}
	}
	echo '<span class="wordPronunciation" data-form="', h($entry["entry"]["form"]), '"></span>';
}

//pronunciation.jsに渡す発音規則。辞書データのsnojをそのまま埋め込む
//規則の中身に</script>があってもscript要素を閉じさせないよう、JSON_HEX_TAGで「<」を逃がす
function renderPronunciationRules($rules){
	if (!is_string($rules) || trim($rules) === ''){
		return;
	}
	echo '<script type="application/json" id="snojRules">';
	echo json_encode($rules, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
	echo '</script>';
}

//同じ品詞が続く間はひとつのリストにまとめる
function renderTranslations(array $entry){
	if (!$entry["translations"]){
		return;
	}
	echo '<li>';
	$previousTitle = null;
	foreach ($entry["translations"] as $singleTranslation){
		if ($singleTranslation["title"] !== $previousTitle){
			if ($previousTitle !== null){
				echo '</ol>';
			}
			echo '<span class="wordTitle">', h($singleTranslation["title"]), '</span>';
			echo '<ol>';
			$previousTitle = $singleTranslation["title"];
		}
		echo '<li>', h(implode('、', $singleTranslation["forms"])), '</li>';
	}
	echo '</ol>';
	echo '</li>';
}

function renderContents(array $entry){
	foreach ($entry["contents"] as $singleContent){
		$title = $singleContent["title"];
		if ($title === "語源"){
			echo '<li class="wordContents">';
			echo '<span class="wordContentTitle">', h($title), '</span><br />';
			renderEtymology($singleContent["text"]);
			echo '</li>';
		}elseif ($title === "文化" || $title === "語法" || $title === "用例"){
			echo '<li class="wordContents">';
			echo '<span class="wordContentTitle">', h($title), '</span><br />';
			echo nl2br(h($singleContent["text"]));//辞書データ側で整形された改行に意味があるため保つ
			echo '</li>';
		}
		//発音記号など、上記以外の項目は表示対象外
	}
}

//語源欄。デリミタで区切り、単語とみなせる部分だけを検索リンクにする
//辞書データの凡例にある {造語者/言語略称:単語|意味} の書式に沿って読む。
//イジェール文字に切り替えてよいのはイジェール語の綴りだけなので、
//単語部分には直前に置かれた言語略称を持ち回って判定する
function renderEtymology($text){
	$parts = preg_split('/([:\/*>+|])/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
	$partsAmount = count($parts);
	$isNextLink = true;
	$language = '';       //空文字は略称なし。造語者がイジェール語の要素から作ったことを表す
	$previousPart = '';
	foreach ($parts as $index => $singlePart){
		$isLink = $isNextLink;
		$isNextLink = true;
		$isWord = true;

		//「.」を文字列に含むとき
		if (stripos($singlePart, '.') !== false){
			$isWord = false;
		//文字列が日本語を含むとき
		}elseif (isDoublebyte($singlePart)){
			$isWord = false;
		//文字列がデリミタで、次に影響を及ぼさないもののとき
		}elseif (preg_match('/[:\/>+]/u', $singlePart) === 1){
			$isWord = false;
			//「:」の左が言語略称。「/」「>」「+」は要素の切れ目なので略称なしに戻す
			$language = ($singlePart === ':') ? $previousPart : '';
		//文字列がデリミタで、次に影響を及ぼすもののとき
		}elseif (preg_match('/[*|]/u', $singlePart) === 1){
			$isWord = false;
			$isNextLink = false;
		//右端以外のとき、ひとつ右を見る
		}elseif ($index + 1 < $partsAmount){
			if (preg_match('/[:\/]/u', $parts[$index + 1]) === 1){
				$isWord = false;
			}
		}
		$previousPart = $singlePart;

		//「*」付きの廃用語はリンクにならないがイジェール語なので、書体だけは切り替える
		$isIdyer = $isWord && isIdyerLanguage($language);
		if ($isIdyer){
			echo '<span class="etymologyWord">';
		}
		if ($isWord && $isLink){
			echo makeLink($singlePart, 'both', 'fwd', 1), h($singlePart), '</a>';
		}else{
			echo h($singlePart);
		}
		if ($isIdyer){
			echo '</span>';
		}
	}
}

//語源欄の言語略称のうち、イジェール語を指すもの。
//i.a（宗教語）やi.s（セコーレ方言）のような方言・位相の略称も同じ文字を使う
function isIdyerLanguage($language){
	return ($language === '' || $language === 'i' || strpos($language, 'i.') === 0);
}

//例文を持たない語では欄ごと出さない
function renderExamples(array $entry, array $exampleIndex, $type, $mode){
	$wordId = $entry["entry"]["id"];
	if (!isset($exampleIndex['byWordId'][$wordId])){
		return;
	}
	echo '<li class="wordExamples">';
	echo '<span class="wordContentTitle">例文</span><br />';
	foreach ($exampleIndex['byWordId'][$wordId] as $exampleKey){
		renderExample($exampleIndex['examples'][$exampleKey], $exampleIndex, $type, $mode, $wordId);
	}
	echo '</li>';
}

//単語欄と例文一覧ページの両方から使う
//$currentWordId は表示中の見出し語のID。例文一覧ページのようにどの単語の欄でもない場合はnull
//ひとつの例文は複数の単語の欄に出るため、id属性は重複しない例文一覧ページ側にだけ振る
function renderExample(array $example, array $exampleIndex, $type, $mode, $currentWordId = null){
	$anchor = ($currentWordId === null) ? ' id="' . h(exampleAnchor($example['id'])) . '"' : '';
	echo '<div class="example"', $anchor, '>';

	echo '<div class="exampleSentence">';
	echo '<span class="exampleText">', h($example['sentence']), '</span>';//exampleTextはdict.jsがイジェール文字に切り替える対象
	echo '<span class="wordId">', makeExampleLink($example['id']), '#', h($example['id']), '</a></span>';
	echo '</div>';

	if ($example['translation'] !== ''){
		echo '<div class="exampleTranslation">', nl2br(h($example['translation'])), '</div>';//辞書データ側の改行に意味があるため保つ
	}
	if ($example['supplement'] !== ''){
		echo '<div class="exampleSupplement">', nl2br(h($example['supplement'])), '</div>';
	}
	if ($example['tags']){
		echo '<div class="exampleTags">';
		foreach ($example['tags'] as $singleTag){
			echo '<span class="exampleTag">', h($singleTag), '</span>';
		}
		echo '</div>';
	}

	renderExampleWords($example, $exampleIndex, $type, $mode, $currentWordId);
	renderExampleOffer($example);
	echo '</div>';
}

//辞書に無いIDと、表示中の単語自身は並べない
function renderExampleWords(array $example, array $exampleIndex, $type, $mode, $currentWordId = null){
	$formById = isset($exampleIndex['formById']) ? $exampleIndex['formById'] : array();
	$links = array();
	$shownIds = array();
	foreach ($example['words'] as $singleWord){
		if (!isset($singleWord['id'])){
			continue;
		}
		$wordId = $singleWord['id'];
		if ($wordId === $currentWordId || isset($shownIds[$wordId]) || !isset($formById[$wordId])){
			continue;
		}
		$shownIds[$wordId] = true;
		$links[] = makeLink($formById[$wordId], $type, $mode, 1, $wordId) . h($formById[$wordId]) . '</a>';
	}
	if (!$links){
		return;
	}
	echo '<div class="exampleWords"><span class="exampleLabel">使用単語</span>', implode('、', $links), '</div>';
}

function renderExampleOffer(array $example){
	if (!isset($example['offer']['catalog'], $example['offer']['number'])){
		return;
	}
	echo '<div class="exampleOffer">';
	echo h($example['offer']['catalog']), ' #', h($example['offer']['number']);
	echo '</div>';
}

function renderExampleNavigation($exampleAmount, $page){
	echo '<nav aria-label="ページ送り"><ul class="navigation">';
	if ($exampleAmount > EXAMPLES_PER_PAGE){
		$totalPages = (int)ceil($exampleAmount / EXAMPLES_PER_PAGE);
		for ($i = 1; $i <= $totalPages; $i++){
			if ($page === $i){
				echo '<li class="currentPage" aria-current="page">', h($i), '</li>';
			}else{
				echo '<li><a href="example.php?', h(http_build_query(array('page' => $i))), '">', h($i), '</a></li>';
			}
		}
	}
	echo '</ul></nav>';
}

//同じ見出しの関連語はまとめて読点で並べる
function renderRelations(array $entry, $type, $mode){
	$shownTitles = array();
	$isOpen = false;
	$isFirstOfTitle = true;
	foreach ($entry["relations"] as $singleRelation){
		if (!in_array($singleRelation["title"], $shownTitles, true)){
			if ($isOpen){
				echo '</li>';
			}
			echo '<li class="wordRelation"><span class="wordRelation">', h($singleRelation["title"]), '</span>';
			$shownTitles[] = $singleRelation["title"];
			$isOpen = true;
			$isFirstOfTitle = true;
		}
		if (!$isFirstOfTitle){
			echo ', ';
		}
		$isFirstOfTitle = false;
		echo makeLink($singleRelation["entry"]["form"], $type, $mode, 1, $singleRelation["entry"]["id"]);
		echo h($singleRelation["entry"]["form"]), '</a>';
	}
	if ($isOpen){
		echo '</li>';
	}
}

//本体（zaslon-site）のmarkdown.phpは、保存させたいリンクの判定で本体のsite_config()を呼ぶ。
//辞書側はcommon/lib.phpを読み込めない（後述）ため、同じ役割の辞書側の設定を返す代わりを置く
if (!function_exists('site_config')){
	function site_config(){
		return dictConfig();
	}
}

//辞書データのlegendのMarkdownを本体（zaslon-site）のレンダラでHTMLにする。
//記法の再実装を避け、文法書側の記事と同じ見た目にするため、変換は本体に任せる。
//辞書はFTPで別に置けるようにしてあるので、本体が無ければnullを返して呼び出し元で原文表示に落とす
//（本体もサイトマップで辞書のconfig.phpをis_fileで確かめてから読む。index.phpのsitemap_dict_urls()）。
//本体側の呼び出し口はcommon/lib.phpのmarkdown_to_html()だが、lib.phpは辞書側と同じh()を定義するため
//読み込めない。名前空間付きの関数を直接呼ぶこと。
//$rendererPath : 本体のmarkdown.phpの場所。テストから差し替えるためだけの引数
function legendMarkdownToHtml($markdown, $rendererPath = null){
	if (!is_string($markdown)){
		return null;
	}
	if ($rendererPath === null){
		$rendererPath = __DIR__ . '/../common/markdown.php';//辞書はサイト直下の/dict/に置く
	}
	$autoloadPath = dirname($rendererPath) . '/../vendor/autoload.php';//markdown.phpが自分で読むが、無ければ致命的エラーになる
	if (!is_file($rendererPath) || !is_file($autoloadPath)){
		return null;
	}
	try{
		require_once $rendererPath;
		return \Zaslon\Markdown\to_html($markdown);
	}catch (Throwable $error){
		return null;//変換に失敗しても凡例のページ自体は出す
	}
}

//凡例。辞書データのlegendはMarkdownなので、本体のレンダラに通したHTMLを出す。
//本体が無い環境では変換せず、原文をそのまま読める形で出す
function renderLegend($legend, $rendererPath = null){
	if (!is_string($legend) || trim($legend) === ''){
		echo '<p>凡例はまだ登録されていません。</p>';
		return;
	}
	$html = legendMarkdownToHtml($legend, $rendererPath);
	echo '<div class="legend">';
	if ($html !== null){
		echo $html;
	}else{
		echo '<pre class="legendSource">', h($legend), '</pre>';
	}
	echo '</div>';
}

function renderNavigation($hitAmount, $page, array $keyWords, $type, $mode){
	echo '<nav aria-label="ページ送り"><ul class="navigation">';
	if ($hitAmount > WORDS_PER_PAGE){
		$totalPages = (int)ceil($hitAmount / WORDS_PER_PAGE);
		$keyWord = implode(' ', $keyWords);
		for ($i = 1; $i <= $totalPages; $i++){
			if ($page === $i){
				echo '<li class="currentPage" aria-current="page">', h($i), '</li>';
			}else{
				echo '<li>', makeLink($keyWord, $type, $mode, $i), h($i), '</a></li>';
			}
		}
	}
	echo '</ul></nav>';
}
