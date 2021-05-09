<?php
//エスケープしてechoする関数
function echo_h($str){
    echo htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

//前方一致検索
function startsWith($haystack, $needle){
	if ($needle){
		return mb_stripos($haystack, $needle, 0) === 0;
	}else{
		return false;
	}
}

//最後尾文字チェック
function endsWith($haystack, $needle){
	if ($needle){
		return substr($haystack, -strlen($needle)) === $needle;
	}else{
		return false;
	}
}

//完全一致検索
function perfectHit($haystack, $needle){
	$haystack = mb_strtolower($haystack,'UTF-8');//検索の便宜のため小文字にする
	$needle = mb_strtolower($needle,'UTF-8');//検索の便宜のため小文字にする
    return $haystack === $needle;
}

//母音で始まるかをチェック
function startsWithVowel($haystack){
	return (bool)preg_match('/^[eaoiu]/u', $haystack);
}

//母音で終わるかチェック
function endsWithVowel($haystack){
	return (bool)preg_match('/[eaoiu]$/u', $haystack);
}

//訳語部の検索用に)と】以左の文字列を消去する
function deleteSymbolsForTrans($string){
	$string = preg_replace('/.+[)）】]/u', '', $string);
	return $string;
}

//見出し語部の変音記号以外の記号を削除
function deleteNonIdyerinCharacters($string){
	$string = preg_replace('/[-\(\)\#]/u', '', $string);
	return $string;
}

//指定を取り込んだリンク生成
function makeLinkStarter($word, $type, $mode, $page = 1,$id = false){
	echo '<a href=dict.php?keyBox=';
	echo_h($word);
	echo '&type=';
	echo_h($type);
	if((isset($_GET["Idf"])) && ($_GET["Idf"] != "")){
		echo '&Idf=true';
	}
	echo '&mode=';
	echo_h($mode);
	echo '&page=';
	echo_h($page);
	if ($id){
		echo '&id=' . $id;
	}
	echo '>';
}

//頭文字の連濁
function initialVoicing($string) {
	$pattern = array('/^h/u','/^k/u','/^s/u','/^t/u','/^c/u','/^p/u','/^f/u');
	$replacement = array('g','g','z','d',"d'",'b','v');
	return preg_replace($pattern, $replacement, $string);
}

//頭文字の連濁を戻す
function initialUnvoicing($string) {
	$replacement = array('/^h/u','/^k/u','/^s/u','/^t/u','/^c/u','/^p/u','/^f/u');
	$pattern = array('g','g','z','d',"d'",'b','v');
	return preg_replace($pattern, $replacement, $string);
}

//アルファベットのみで構成されているかの判定
function isDoublebyte($string) {
	return strlen($string) !== mb_strlen($string);
}

//
function isHit($singleEntry, $needle, $type, $mode){
	$func = setFunc($mode);
	switch ($type){
		case "word":
			return $func($singleEntry["entry"]["form"],$needle);
			break;
		case "trans":
			foreach ($singleEntry["translations"] as $singleTranslation){
				foreach ($singleTranslation["forms"] as $singleTranslationForm){
					if ($func(deleteSymbolsForTrans($singleTranslationForm),$needle) !== false){
						return true;
					}
				}
			}
			break;
		case "both":
			if ($func($singleEntry["entry"]["form"],$needle) !== false){
				return true;
			}
			foreach ($singleEntry["translations"] as $singleTranslation){
				foreach ($singleTranslation["forms"] as $singleTranslationForm){
					if ($func(deleteSymbolsForTrans($singleTranslationForm),$needle) !== false){
						return true;
					}
				}
			}
			break;
		case "all":
			if ($func($singleEntry["entry"]["form"],$needle) !== false){
				return true;
			}
			foreach ($singleEntry["translations"] as $singleTranslation){
				foreach ($singleTranslation["forms"] as $singleTranslationForm){
					if ($func(deleteSymbolsForTrans($singleTranslationForm),$needle) !== false){
						return true;
					}
				}
				foreach ($singleEntry["contents"] as $singleContent){
					if ($func($singleContent["text"],$needle) !== false){
						return true;
					}
				}
			}
			break;
	}
}

//func関数を指定する
function setFunc($mode){
	switch($mode){
		case "prt":
			return "stripos";
		case "fwd":
			return "startsWith";
		case "perf":
			return "endsWith";
		default:
			return "stripos";
	}
}