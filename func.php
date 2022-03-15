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

//頭文字の連濁を戻す。この関数は使えない。連濁時に合流することで一対一対応が崩れているため。
//function initialUnvoicing($string) {
//	$pattern = array('/^g/u','/^g/u','/^z\'/u','/^d\'/u','/^d\'/u','/^b/u','/^v/u');
//	$replacement = array('h','k','s','t','c','p','f');
//	return preg_replace($pattern, $replacement, $string);
//}

//アルファベットのみで構成されているかの判定
function isDoublebyte($string) {
	return strlen($string) !== mb_strlen($string);
}

//検索処理
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
				if ($func($singleTranslation["title"],$needle) !== false){
					return true;
				}
				foreach ($singleTranslation["forms"] as $singleTranslationForm){
					if ($type === 'all'){
						if ($func($singleTranslationForm,$needle) !== false){
							return true;//全文検索のときは記号も含めて検索する
						}
					}else{
						if ($func(deleteSymbolsForTrans($singleTranslationForm),$needle) !== false){
							return true;
						}
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

//接辞サジェスト機能
function makeDerivationTable($singleEntry, $affixTable){
	$wordForm = $singleEntry["entry"]["form"];
	$wordFormForPreffixs = array();
	$texts = array();
	
	//動詞の場合、接尾辞はeを外した形を語幹としているので、それにあわせる。
	if (mb_stripos($singleEntry["translations"][0]["title"],"動詞") !== false) {
		$wordFormForSuffix = substr($wordForm, 0, strlen($wordForm)-1);
	}else{
		$wordFormForSuffix = $wordForm;
	}
	//記述詞の場合、末尾の(i)nを外した形に対しての派生があるので、それをチェックする。
	if (mb_stripos($singleEntry["translations"][0]["title"],"記述詞") !== false) {
		if (endsWith($wordForm, 'in')){
			$wordFormForPreffixs[1] = substr($wordForm, 0, strlen($wordForm)-2);
		}
		$wordFormForPreffixs[0] = substr($wordForm, 0, strlen($wordForm)-1);
	}else{
		$wordFormForPreffixs[0] = $wordForm;
	}
	
	//辞書のデータに対して接辞テーブルとの該当を調べる
	$returnTable = array();
	foreach ($affixTable as $i => $singleAffix){
		
		$singleAffixWithoutBracket = preg_replace('/\(.*?\)/u', '', $singleAffix[1]); //カッコつき接辞のカッコ内をカッコごとなくした形
		if (preg_match('/(?<=\().*?(?=\))/u',$singleAffix[1]) === 1) {
			preg_match('/(?<=\().*?(?=\))/u',$singleAffix[1], $singleAffixCharBetweenBracket);
			$singleAffixCharBetweenBracket = $singleAffixCharBetweenBracket[0]; //カッコつき接辞のカッコ内を取り出した文字列
		}else{
			$singleAffixCharBetweenBracket = "";
		} 
		$singleAffixWithBracket = preg_replace('/[\(\)]/u', '', $singleAffix[1]); //カッコつき接辞のカッコを外した形
		
		if (startsWith($singleAffix[1], "-")) { //接尾辞
			if (endsWithVowel($wordForm)){//母音で終わる単語の場合
				$texts[0] = $wordFormForSuffix . substr($singleAffixWithoutBracket, 1);
			}else{
				$texts[0] = $wordFormForSuffix . substr($singleAffixWithBracket, 1);
			}
		}elseif (endsWith($singleAffix[1], "-")){ //接頭辞
			foreach ($wordFormForPreffixs as $index => $singleWordFormForPreffix){
				if (startsWithVowel($wordForm)){//母音で始まる単語の場合
						$texts[$index] = substr($singleAffixWithoutBracket, 0, strlen($singleAffixWithoutBracket)-1) . initialVoicing($singleWordFormForPreffix);
				}else{
					if (isset($singleAffix[3]) && $singleAffix[3] === 'NO_VOICING'){
						$texts[$index] = substr($singleAffixWithBracket, 0, strlen($singleAffixWithBracket)-1) . $singleWordFormForPreffix;
					}else{
						$texts[$index] = substr($singleAffixWithBracket, 0, strlen($singleAffixWithBracket)-1) . initialVoicing($singleWordFormForPreffix);
					}
				}
			}
		}elseif (stripos($singleAffix[1], "-") !== false){
			//接周辞：今の所存在しない
		}
		foreach ($texts as $singleText){
			$returnTable[$i][] = $singleAffix[0];
			$returnTable[$i][] = $singleText;
			$returnTable[$i][] = $singleAffix[2];
		}
	}
	return $returnTable;
}

//func関数を指定する
function setFunc($mode){
	switch($mode){
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