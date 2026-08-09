<?php
//検索処理
require_once __DIR__ . '/func.php';

function setFunc($mode){
	switch ($mode){
		case "prt":
			return "stripos";
		case "fwd":
			return "startsWith";
		case "perf":
			return "perfectHit";
		default:
			return "stripos";
	}
}

//stripos は先頭一致で0を返すため、必ず !== false で判定して真偽値に揃える
//$exampleText はこの見出し語を使う例文をつないだもの。全文検索のときだけ使う
function isHit($singleEntry, $needle, $type, $mode, $exampleText = ''){
	$func = setFunc($mode);

	if ($type !== "trans" && $func($singleEntry["entry"]["form"], $needle) !== false){
		return true;
	}
	if ($type === "word"){
		return false;
	}

	foreach ($singleEntry["translations"] as $singleTranslation){
		//全文検索のときは品詞名も対象にする
		if ($type === "all" && $func($singleTranslation["title"], $needle) !== false){
			return true;
		}
		foreach ($singleTranslation["forms"] as $singleTranslationForm){
			$haystack = ($type === "all") ? $singleTranslationForm : deleteSymbolsForTrans($singleTranslationForm);
			if ($func($haystack, $needle) !== false){
				return true;
			}
		}
	}

	if ($type === "all"){
		foreach ($singleEntry["contents"] as $singleContent){
			if ($func($singleContent["text"], $needle) !== false){
				return true;
			}
		}
		//例文は単語欄に表示されるので、全文検索の対象にも含める
		if ($exampleText !== '' && $func($exampleText, $needle) !== false){
			return true;
		}
	}

	return false;
}

//スペース区切りの検索語に分解する。空白の揺れは区切りの判定を狂わせるため先に均す
function parseKeywords($keyBox, $type, $mode){
	if ($keyBox === null || $keyBox === ''){
		return array();
	}

	$keyWord = preg_replace('/[　]/u', ' ', $keyBox);
	$keyWord = preg_replace('/\s\s+/u', ' ', $keyWord);
	$keyWord = preg_replace('/(^[\s]|[\s]$)/u', '', $keyWord);
	if (!keepsSymbols($type, $mode)){
		$keyWord = deleteNonIdyerinCharacters($keyWord);
	}

	//ダブルコーテーションで囲めば空白を含む語をひとつとして扱えるよう、CSVとして分解する
	$keyWords = str_getcsv($keyWord, ' ', "\"");
	if ($mode === 'perf'){
		$keyWords = array(implode(" ", $keyWords));//完全一致では空白ごと一致させる
	}

	$keyWords = array_values(array_filter($keyWords, function ($keyWord){
		return $keyWord !== null && $keyWord !== '';
	}));
	return $keyWords;
}

//全文検索と完全一致検索では記号そのものを検索したいため、検索語からも見出し語からも記号を落とさない
function keepsSymbols($type, $mode){
	return $type === 'all' || $mode === 'perf';
}

//全ての検索語に一致した見出し語のキーを返す
//$exampleIndex は makeExampleIndex() の返り値。渡すと全文検索が例文も対象にする
//返り値：$words の添字の配列
function searchEntries(array $words, array $keyWords, $type, $mode, $includeVoicing, array $exampleIndex = array()){
	if (!$keyWords){
		return array();
	}

	$exampleTexts = ($type === 'all' && isset($exampleIndex['textByWordId'])) ? $exampleIndex['textByWordId'] : array();

	//連濁形はキーワードごとに一度求めれば足りる
	$voicedKeyWords = array();
	if ($includeVoicing){
		foreach ($keyWords as $index => $singleKey){
			$voicedKeyWords[$index] = initialVoicing($singleKey);
		}
	}

	$hitsPerKeyword = array_fill(0, count($keyWords), array());
	foreach ($words as $entryKey => $singleEntry){
		$entryId = $singleEntry["entry"]["id"];
		if (!keepsSymbols($type, $mode)){
			$singleEntry["entry"]["form"] = deleteNonIdyerinCharacters($singleEntry["entry"]["form"]);
		}
		$exampleText = isset($exampleTexts[$entryId]) ? $exampleTexts[$entryId] : '';
		foreach ($keyWords as $index => $singleKey){
			if (isHit($singleEntry, $singleKey, $type, $mode, $exampleText)
				|| ($includeVoicing && isHit($singleEntry, $voicedKeyWords[$index], $type, $mode, $exampleText))){
				$hitsPerKeyword[$index][] = $entryKey;
			}
		}
	}

	$hitKeys = array_shift($hitsPerKeyword);
	foreach ($hitsPerKeyword as $singleHits){
		$hitKeys = array_intersect($hitKeys, $singleHits);
	}
	return array_values($hitKeys);//array_intersectが残す歯抜けの添字を、ページ送りが使えるよう詰め直す
}

function findEntryKeyById(array $words, $id){
	foreach ($words as $entryKey => $singleEntry){
		if ($singleEntry["entry"]["id"] === $id){
			return $entryKey;
		}
	}
	return null;
}

//返り値：array('suffix' => 接尾辞用の語幹, 'prefix' => 接頭辞用の語幹の配列)
function derivationStems($singleEntry){
	$wordForm = $singleEntry["entry"]["form"];
	$title = isset($singleEntry["translations"][0]["title"]) ? $singleEntry["translations"][0]["title"] : '';

	//動詞の場合、接尾辞はeを外した形を語幹としているので、それにあわせる。
	if (mb_stripos($title, "動詞") !== false){
		$stemForSuffix = substr($wordForm, 0, strlen($wordForm) - 1);
	}else{
		$stemForSuffix = $wordForm;
	}

	//記述詞の場合、末尾の(i)nを外した形に対しての派生があるので、それをチェックする。
	$stemsForPrefix = array();
	if (mb_stripos($title, "記述詞") !== false){
		$stemsForPrefix[] = substr($wordForm, 0, strlen($wordForm) - 1);
		if (endsWith($wordForm, 'in')){
			$stemsForPrefix[] = substr($wordForm, 0, strlen($wordForm) - 2);
		}
	}else{
		$stemsForPrefix[] = $wordForm;
	}

	return array('suffix' => $stemForSuffix, 'prefix' => $stemsForPrefix);
}

//返り値：array(array(対象品詞, 派生形, 説明), ...)
function makeDerivationTable($singleEntry, array $affixTable){
	$wordForm = $singleEntry["entry"]["form"];
	$stems = derivationStems($singleEntry);
	$stemForSuffix = $stems['suffix'];
	$stemsForPrefix = $stems['prefix'];

	$startsWithVowel = startsWithVowel($wordForm);
	$endsWithVowel = endsWithVowel($wordForm);

	$returnTable = array();
	foreach ($affixTable as $singleAffix){
		$texts = array();
		if ($singleAffix['isSuffix']){
			//母音で終わる単語の場合はカッコ内を落とす
			$body = $endsWithVowel ? $singleAffix['withoutBracket'] : $singleAffix['withBracket'];
			$texts[] = $stemForSuffix . substr($body, 1);
		}elseif ($singleAffix['isPrefix']){
			//母音で始まる単語の場合はカッコ内を落とす
			$body = $startsWithVowel ? $singleAffix['withoutBracket'] : $singleAffix['withBracket'];
			$head = substr($body, 0, strlen($body) - 1);
			foreach ($stemsForPrefix as $singleStem){
				if (!$startsWithVowel && $singleAffix['noVoicing']){
					$texts[] = $head . $singleStem;
				}else{
					$texts[] = $head . initialVoicing($singleStem);
				}
			}
		}else{
			//接周辞：今の所存在しない
			continue;
		}
		foreach ($texts as $singleText){
			$returnTable[] = array($singleAffix['pos'], $singleText, $singleAffix['description']);
		}
	}
	return $returnTable;
}

//検索語がこの語幹からの派生形になりうるかを、文字列の一致だけで大まかに判定する
//派生形は「語幹＋接尾辞」か「接頭辞＋（連濁した）語幹」の形にしかならないことを使う
//取りこぼすと結果が変わるため、判定できない場合は必ずtrueを返す
function canDerive($keyWord, array $stems){
	if ($stems['suffix'] === '' || strncmp($keyWord, $stems['suffix'], strlen($stems['suffix'])) === 0){
		return true;
	}
	foreach ($stems['prefix'] as $singleStem){
		if ($singleStem === ''){
			return true;
		}
		foreach (array($singleStem, initialVoicing($singleStem)) as $singleCandidate){
			$length = strlen($singleCandidate);
			if ($length <= strlen($keyWord) && substr_compare($keyWord, $singleCandidate, -$length) === 0){
				return true;
			}
		}
	}
	return false;
}

//返り値：array(array('form' => 見出し語, 'id' => 単語ID, 'description' => 説明), ...)
function findDerivationSuggestions(array $words, array $affixTable, $keyWord){
	if ($keyWord === null || $keyWord === ''){
		return array();
	}

	$suggestions = array();
	foreach ($words as $singleEntry){
		//全単語分の派生形を組み立てると重いため、明らかに一致しないものは先に落とす
		if (!canDerive($keyWord, derivationStems($singleEntry))){
			continue;
		}
		$title = isset($singleEntry["translations"][0]["title"]) ? $singleEntry["translations"][0]["title"] : '';
		foreach (makeDerivationTable($singleEntry, $affixTable) as $singleDerivation){
			if ($keyWord === $singleDerivation[1] && mb_stripos($title, $singleDerivation[0]) !== false){
				$suggestions[] = array(
					'form'        => $singleEntry["entry"]["form"],
					'id'          => $singleEntry["entry"]["id"],
					'description' => $singleDerivation[2],
				);
			}
		}
	}
	return $suggestions;
}
