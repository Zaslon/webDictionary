<?php
//エスケープしてprintする関数
function print_h($str){
    print htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
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
    return $haystack == $needle;
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
	print '<a href=dict.php?keyBox=';
	print_h($word);
	print '&type=';
	print_h($type);
	if((isset($_GET["Idf"])) && ($_GET["Idf"] != "")){
		print '&Idf=true';
	}
	print '&mode=';
	print_h($mode);
	print '&page=';
	print_h($page);
	if ($id){
		print '&id=' . $id;
	}
	print '>';
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