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

//HKS順ソート用の比較関数
//紙辞書用と異なる
//strAを先にしたければ-1を返す。
function HKSCmpw($strA,$strB){
	
	$arrHks = array("E\'","e\'","A\'","a\'","O\'","o\'","I\'","i\'","U\'","u\'","S\'","s\'","T\'","t\'","N\'","n\'","R\'","r\'","Z\'","z\'","D\'","d\'","E","e","A","a","O","o","I","i","U","u","H","h","K","k","S","s","T","t","C","c","N","n","R","r","M","m","P","p","F","f","G","g","Z","z","D","d","B","b","V","v","-"," ");

	//変音記号付きを先に置換する必要があるため、変音記号付きを先に置く。
	$odrHks = array("3","4","7","8","11","12","15","16","19","20","27","28","31","32","37","38","41","42","53","54","57","58","1","2","5","6","9","10","13","14","17","18","21","22","23","24","25","26","29","30","33","34","35","36","39","40","43","44","45","46","47","48","49","50","51","52","55","56","59","60","61","62","63","0");
	//置換の順序と文字の早さは異なるため、1から順にはならない。
	
	$strA = $strA["entry"]["form"];
	$strB = $strB["entry"]["form"];
	
	//処理した文字列の生成
	$strA1 = preg_replace('/^-|-$|[()]/u', '', $strA);
	$strB1 = preg_replace('/^-|-$|[()]/u', '', $strB);
	
	//()を除いた文字列の生成
	$strA2 = preg_replace('/[()]/u', '', $strA);
	$strB2 = preg_replace('/[()]/u', '', $strB);
	
	//文字列を一文字ずつ分離して配列に入れる
	$arrA1 = preg_split('/(.\'?)/u',$strA1 , -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
	$arrB1 = preg_split('/(.\'?)/u',$strB1 , -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);	
	
	//処理した文字列を順序の数字に置換する
	$arrA1 = str_replace($arrHks, $odrHks, $arrA1);
	$arrB1 = str_replace($arrHks, $odrHks, $arrB1);
	
	$minLength = min(count($arrA1), count($arrB1));
	//処理した文字列を先頭から比較して、異なる場合は先を先として返す
	for ($i = 0; $i < $minLength; $i++ ){
		$return =  $arrA1[$i] <=> $arrB1[$i];
		if ($return !== 0){
			return $return;
		}
	}
	//短い方の単語の最後まで同じ
	//ここまで大文字も判定済み
	
	//単語全体の長さを比較する。異なる場合、短い方を先とする。
	$return = count($arrA1) <=> count($arrB1);
	if ($return !== 0){
		return $return;
	}
	
	//元の文字列に記号が含まれない方を先とする
	//()を有す場合必ず-を有すことを利用している
	//両方の文字列が語頭か語末に-を含む場合
	if ($strA1 !== $strA2 && $strB1 !== $strB2){
		//-の位置が後ろの方が先
		if (strripos($strA2, "-") > strripos($strB2, "-")){
			return -1;
		}elseif(strripos($strA2, "-") < strripos($strB2, "-")){
			return 1;
		//-の位置が同じ場合、(の位置が先の方が先
		}elseif(strripos($strA2, "(") < strripos($strB2, "(")){
			return -1;
		}elseif(strripos($strA2, "(") > strripos($strB2, "(")){
			return 1;
		//(の位置が同じ場合、)の位置が先の方が先
		}elseif(strripos($strA2, ")") < strripos($strB2, ")")){
			return -1;
		}elseif(strripos($strA2, ")") > strripos($strB2, ")")){
			return 1;
		}
	}elseif($strA1 !== $strA2){
		return 1;
	}else{
		return -1;
	}
}