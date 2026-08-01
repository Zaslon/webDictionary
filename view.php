<?php
//検索結果の表示
require_once __DIR__ . '/func.php';

//接辞サジェストを表示する
function renderSuggestions(array $suggestions, $type, $mode){
	foreach ($suggestions as $singleSuggestion){
		echo '<p class="suggest">もしかして、';
		echo makeLink($singleSuggestion['form'], $type, $mode, 1, $singleSuggestion['id']);
		echo h($singleSuggestion['form']), '</a>';
		echo '<span class="wordId">#', h($singleSuggestion['id']), '</span>';
		echo 'の ', h($singleSuggestion['description']), ' ? </p>';
	}
}

//見出し語1件分を表示する
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

//見出し語
function renderWordForm(array $entry){
	$form = $entry["entry"]["form"];
	echo '<li class="wordForm"><span title="', h($form), '">', h($form), '</span></li>';
}

//訳語。同じ品詞が続く間はひとつのリストにまとめる
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

//語法・用例などの解説欄
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
			echo nl2br(h($singleContent["text"]));//原文の改行を保つ
			echo '</li>';
		}
		//発音記号など、上記以外の項目は表示対象外
	}
}

//語源欄。デリミタで区切りつつ、単語とみなせる部分を検索リンクにする
function renderEtymology($text){
	$parts = preg_split('/([:\/*>+|])/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
	$partsAmount = count($parts);
	$isNextLink = true;
	foreach ($parts as $index => $singlePart){
		$isLink = $isNextLink;
		$isNextLink = true;

		//「.」を文字列に含むとき
		if (stripos($singlePart, '.') !== false){
			$isLink = false;
		//文字列が日本語を含むとき
		}elseif (isDoublebyte($singlePart)){
			$isLink = false;
		//文字列がデリミタで、次に影響を及ぼさないもののとき
		}elseif (preg_match('/[:\/>+]/u', $singlePart) === 1){
			$isLink = false;
		//文字列がデリミタで、次に影響を及ぼすもののとき
		}elseif (preg_match('/[*|]/u', $singlePart) === 1){
			$isLink = false;
			$isNextLink = false;
		//右端以外のとき、ひとつ右を見る
		}elseif ($index + 1 < $partsAmount){
			if (preg_match('/[:\/]/u', $parts[$index + 1]) === 1){
				$isLink = false;
			}
		}

		if ($isLink){
			echo makeLink($singlePart, 'both', 'fwd', 1), h($singlePart), '</a>';
		}else{
			echo h($singlePart);
		}
	}
}

//見出し語に紐づく例文。例文を持たない語では欄ごと出さない
function renderExamples(array $entry, array $exampleIndex, $type, $mode){
	$wordId = $entry["entry"]["id"];
	if (!isset($exampleIndex['byWordId'][$wordId])){
		return;
	}
	echo '<li class="wordExamples">';
	echo '<span class="wordContentTitle">例文</span><br />';
	foreach ($exampleIndex['byWordId'][$wordId] as $exampleKey){
		//表示中の単語自身は「使用単語」に並べない
		renderExample($exampleIndex['examples'][$exampleKey], $exampleIndex, $type, $mode, $wordId);
	}
	echo '</li>';
}

//例文1件分を表示する。単語欄と例文一覧ページの両方から使う
//$currentWordId は表示中の見出し語のID。例文一覧ページのようにどの単語の欄でもない場合はnull
//ひとつの例文は複数の単語の欄に出るため、id属性は重複しない例文一覧ページ側にだけ振る
function renderExample(array $example, array $exampleIndex, $type, $mode, $currentWordId = null){
	$anchor = ($currentWordId === null) ? ' id="' . h(exampleAnchor($example['id'])) . '"' : '';
	echo '<div class="example"', $anchor, '>';

	echo '<div class="exampleSentence">';
	echo '<span class="exampleText">', h($example['sentence']), '</span>';//イジェール文字表示の切り替え対象
	echo '<span class="wordId">', makeExampleLink($example['id']), '#', h($example['id']), '</a></span>';
	echo '</div>';

	if ($example['translation'] !== ''){
		echo '<div class="exampleTranslation">', nl2br(h($example['translation'])), '</div>';//原文の改行を保つ
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

//例文が使っている単語。辞書に無いIDと、表示中の単語自身は並べない
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

//例文の出典
function renderExampleOffer(array $example){
	if (!isset($example['offer']['catalog'], $example['offer']['number'])){
		return;
	}
	echo '<div class="exampleOffer">';
	echo h($example['offer']['catalog']), ' #', h($example['offer']['number']);
	echo '</div>';
}

//例文一覧のページ送り
function renderExampleNavigation($exampleAmount, $page){
	echo '<ul class="navigation">';
	if ($exampleAmount > EXAMPLES_PER_PAGE){
		$totalPages = (int)ceil($exampleAmount / EXAMPLES_PER_PAGE);
		for ($i = 1; $i <= $totalPages; $i++){
			if ($page === $i){
				echo '<li class="currentPage">', h($i), '</li>';
			}else{
				echo '<li><a href="example.php?', h(http_build_query(array('page' => $i))), '">', h($i), '</a></li>';
			}
		}
	}
	echo '</ul>';
}

//関連語。同じ見出しの語はまとめて読点で並べる
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

//ページ送り
function renderNavigation($hitAmount, $page, array $keyWords, $type, $mode){
	echo '<ul class="navigation">';
	if ($hitAmount > WORDS_PER_PAGE){
		$totalPages = (int)ceil($hitAmount / WORDS_PER_PAGE);
		$keyWord = implode(' ', $keyWords);
		for ($i = 1; $i <= $totalPages; $i++){
			if ($page === $i){
				echo '<li class="currentPage">', h($i), '</li>';
			}else{
				echo '<li>', makeLink($keyWord, $type, $mode, $i), h($i), '</a></li>';
			}
		}
	}
	echo '</ul>';
}
