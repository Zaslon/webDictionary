<?php
//検索処理
require_once __DIR__ . '/func.php';

//一致判定に使う関数を指定する
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

//検索処理
//stripos は先頭一致で0を返すため、必ず !== false で判定して真偽値に揃える
function isHit($singleEntry, $needle, $type, $mode){
	$func = setFunc($mode);

	//見出し語を対象にする（訳語検索のみ対象外）
	if ($type !== "trans" && $func($singleEntry["entry"]["form"], $needle) !== false){
		return true;
	}
	if ($type === "word"){
		return false;
	}

	foreach ($singleEntry["translations"] as $singleTranslation){
		//全文検索のときは見出しと記号も含めて検索する
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
	}

	return false;
}

//検索語を整形して、スペース区切りの配列に分解する
function parseKeywords($keyBox, $type, $mode){
	if ($keyBox === null || $keyBox === ''){
		return array();
	}

	$keyWord = preg_replace('/[　]/u', ' ', $keyBox);	//全角スペースを半角スペースに変換
	$keyWord = preg_replace('/\s\s+/u', ' ', $keyWord);	//スペース2つ以上であれば，1つに削減
	$keyWord = preg_replace('/(^[\s]|[\s]$)/u', '', $keyWord);	//先頭と末尾のスペースを削除
	if (!keepsSymbols($type, $mode)){
		$keyWord = deleteNonIdyerinCharacters($keyWord);
	}

	$keyWords = str_getcsv($keyWord, ' ', "\"");	//スペースで区切られた検索語を分離して配列に格納。ただしダブルコーテーションの囲いをより優先する
	if ($mode === 'perf'){
		$keyWords = array(implode(" ", $keyWords));	//完全一致検索の場合は一つに戻す
	}

	//空要素を取り除く
	$keyWords = array_values(array_filter($keyWords, function ($keyWord){
		return $keyWord !== null && $keyWord !== '';
	}));
	return $keyWords;
}

//全文検索の場合、完全一致検索の場合は記号を削除しない
function keepsSymbols($type, $mode){
	return $type === 'all' || $mode === 'perf';
}

//全ての検索語に一致した見出し語のキーを返す
//返り値：$words の添字の配列
function searchEntries(array $words, array $keyWords, $type, $mode, $includeVoicing){
	if (!$keyWords){
		return array();
	}

	//連濁形はキーワードごとに一度求めれば足りる
	$voicedKeyWords = array();
	if ($includeVoicing){
		foreach ($keyWords as $index => $singleKey){
			$voicedKeyWords[$index] = initialVoicing($singleKey);
		}
	}

	//キーワードの数だけ結果一時保存用の配列を用意
	$hitsPerKeyword = array_fill(0, count($keyWords), array());
	foreach ($words as $entryKey => $singleEntry){
		if (!keepsSymbols($type, $mode)){
			$singleEntry["entry"]["form"] = deleteNonIdyerinCharacters($singleEntry["entry"]["form"]);
		}
		foreach ($keyWords as $index => $singleKey){
			//通常ヒット OR (連濁検索 AND 連濁ヒット)
			if (isHit($singleEntry, $singleKey, $type, $mode)
				|| ($includeVoicing && isHit($singleEntry, $voicedKeyWords[$index], $type, $mode))){
				$hitsPerKeyword[$index][] = $entryKey;
			}
		}
	}

	//全ての検索語に一致したものだけを残す
	$hitKeys = array_shift($hitsPerKeyword);
	foreach ($hitsPerKeyword as $singleHits){
		$hitKeys = array_intersect($hitKeys, $singleHits);
	}
	return array_values($hitKeys);//歯抜けを詰めて再番号付け
}

//単語IDから見出し語のキーを探す。見つからない場合はnull
function findEntryKeyById(array $words, $id){
	foreach ($words as $entryKey => $singleEntry){
		if ($singleEntry["entry"]["id"] === $id){
			return $entryKey;
		}
	}
	return null;
}

//接辞を付ける前の語幹を求める
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

//単語に接辞を付けた派生形の一覧を返す
//返り値：array(array(対象品詞, 派生形, 説明), ...)
function makeDerivationTable($singleEntry, array $affixTable){
	$wordForm = $singleEntry["entry"]["form"];
	$stems = derivationStems($singleEntry);
	$stemForSuffix = $stems['suffix'];
	$stemsForPrefix = $stems['prefix'];

	$startsWithVowel = startsWithVowel($wordForm);
	$endsWithVowel = endsWithVowel($wordForm);

	//辞書のデータに対して接辞テーブルとの該当を調べる
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

//検索語が既存語の派生形と一致する場合に、その元の語を返す
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
