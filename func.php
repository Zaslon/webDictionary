<?php
//イジェール語辞書の共通関数
//文字列処理・ソート・データ読み込みをまとめる。検索はsearch.php、表示はview.phpを参照。

date_default_timezone_set('Asia/Tokyo');

//1ページあたりの表示単語数
const WORDS_PER_PAGE = 20;

//1ページあたりの表示例文数
const EXAMPLES_PER_PAGE = 20;

//////////////////////////////////////////////////
//出力ヘルパ
//////////////////////////////////////////////////

//HTMLエスケープした文字列を返す
function h($str){
	return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

//エスケープしてechoする関数
function echo_h($str){
	echo h($str);
}

//条件が真ならchecked属性を返す
function checkedAttr($isChecked){
	return $isChecked ? ' checked' : '';
}

//静的ファイルのURLを返す
//更新してもブラウザが古いCSSやJSを使い続けないよう、更新時刻を付ける
function assetUrl($path){
	if (preg_match('#^(https?:)?//#u', $path)){
		return $path;//外部のURLはそのまま返す
	}
	$file = __DIR__ . '/' . $path;
	return is_file($file) ? $path . '?v=' . filemtime($file) : $path;
}

//////////////////////////////////////////////////
//リクエストパラメータ
//////////////////////////////////////////////////

//GETパラメータを取得する。未指定・空文字列の場合はnullを返す
function getParam($name){
	return (isset($_GET[$name]) && $_GET[$name] !== '') ? $_GET[$name] : null;
}

//typeパラメータを既知の値に正規化する
function normalizeType($type){
	return in_array($type, array('word', 'trans', 'both', 'all'), true) ? $type : 'both';
}

//modeパラメータを既知の値に正規化する
function normalizeMode($mode){
	return in_array($mode, array('prt', 'fwd', 'perf'), true) ? $mode : 'prt';
}

//イジェール文字表示が要求されているか
function isIdfRequested(){
	return getParam('Idf') !== null;
}

//連濁派生語を検索対象に含めるかどうか
function isVoicingRequested(){
	return getParam('voicing') !== null;
}

//検索リンクの開始タグを返す
//パラメータは必ずURLエンコードし、属性値は必ず引用符で囲う
function makeLink($word, $type, $mode, $page = 1, $id = false){
	$params = array(
		'keyBox' => $word,
		'type'   => normalizeType($type),
		'mode'   => normalizeMode($mode),
		'page'   => $page,
	);
	if (isIdfRequested()){
		$params['Idf'] = 'true';
	}
	if (isVoicingRequested()){
		$params['voicing'] = 'true';
	}
	if ($id){
		$params['id'] = $id;
	}
	return '<a href="dict.php?' . h(http_build_query($params)) . '">';
}

//////////////////////////////////////////////////
//文字列判定
//////////////////////////////////////////////////

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
	$haystack = mb_strtolower($haystack, 'UTF-8');//検索の便宜のため小文字にする
	$needle = mb_strtolower($needle, 'UTF-8');//検索の便宜のため小文字にする
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

//アルファベットのみで構成されているかの判定
function isDoublebyte($string){
	return strlen($string) !== mb_strlen($string);
}

//訳語部の検索用に)と】以左の文字列を消去する
function deleteSymbolsForTrans($string){
	return preg_replace('/.+[)）】]/u', '', $string);
}

//見出し語部の変音記号以外の記号を削除
function deleteNonIdyerinCharacters($string){
	return preg_replace('/[-\(\)\#]/u', '', $string);
}

//頭文字の連濁
//同じ語幹に何度も適用されるため結果を再利用する
function initialVoicing($string){
	static $cache = array();
	if (isset($cache[$string])){
		return $cache[$string];
	}
	static $pattern = array('/^hh/u','/^hk/u','/^hs/u','/^ht/u','/^hc/u','/^hp/u','/^hf/u','/^kh/u','/^kk/u','/^ks/u','/^kt/u','/^kc/u','/^kp/u','/^kf/u','/^sh/u','/^sk/u','/^ss/u','/^st/u','/^sc/u','/^sp/u','/^sf/u','/^th/u','/^tk/u','/^ts/u','/^tt/u','/^tc/u','/^tp/u','/^tf/u','/^ch/u','/^ck/u','/^cs/u','/^ct/u','/^cc/u','/^cp/u','/^cf/u','/^ph/u','/^pk/u','/^ps/u','/^pt/u','/^pc/u','/^pp/u','/^pf/u','/^fh/u','/^fk/u','/^fs/u','/^ft/u','/^fc/u','/^fp/u','/^ff/u','/^s\'h/u','/^s\'k/u','/^s\'s/u','/^s\'t/u','/^s\'c/u','/^s\'p/u','/^s\'f/u','/^t\'h/u','/^t\'k/u','/^t\'s/u','/^t\'t/u','/^t\'c/u','/^t\'p/u','/^t\'f/u','/^h/u','/^k/u','/^s/u','/^t/u','/^c/u','/^p/u','/^f/u');
	static $replacement = array('gg','gg','gz','gd','gd\'','gb','gv','gg','gg','gz','gd','gd\'','gb','gv','zg','zg','zz','zd','zd\'','zb','zv','dg','dg','dz','dd','dd\'','db','dv','d\'g','d\'g','d\'z','d\'d','d\'d\'','d\'b','d\'v','bg','bg','bz','bd','bd\'','bb','bv','vg','vg','vz','vd','vd\'','vb','vv','z\'g','z\'g','z\'z','z\'d','z\'d\'','z\'b','z\'v','d\'g','d\'g','d\'z','d\'d','d\'d\'','d\'b','d\'v','g','g','z','d','d\'','b','v');
	return $cache[$string] = preg_replace($pattern, $replacement, $string);
}

//頭文字の連濁を戻す。この関数は使えない。連濁時に合流することで一対一対応が崩れているため。

//////////////////////////////////////////////////
//辞書順ソート
//仕様：https://zaslon.info/idyerin/辞書順について/
//////////////////////////////////////////////////

//字母順。先頭は空白文字
const HKS_ALPHABET = " eaoiuhkstcnrmpfgzdbv0123456789/,";

//見出し語から辞書順の比較に必要な情報を組み立てる
//ソート中は同じ見出し語が何度も比較されるため結果を再利用する
function hksSortKey($form){
	static $cache = array();
	if (isset($cache[$form])){
		return $cache[$form];
	}
	static $alphabet = null;
	if ($alphabet === null){
		$alphabet = array_flip(preg_split('//u', HKS_ALPHABET, -1, PREG_SPLIT_NO_EMPTY));
	}
	$unknown = count($alphabet);//字母順に定義の無い文字は末尾に置く

	//規則1 全ての「-」「(」「)」「'」を削除し、大文字小文字の区別を無視する
	$processed = mb_strtolower(str_replace(array('-', '(', ')', "'"), '', $form), 'UTF-8');

	//規則2 処理した文字列を字母順の数値列にする
	$codes = array();
	foreach (preg_split('//u', $processed, -1, PREG_SPLIT_NO_EMPTY) as $char){
		$codes[] = isset($alphabet[$char]) ? $alphabet[$char] : $unknown;
	}

	//規則4 元の文字列の、各位置が大文字かどうか
	$uppers = array();
	foreach (preg_split('//u', $form, -1, PREG_SPLIT_NO_EMPTY) as $char){
		$uppers[] = preg_match('/[A-Z]/u', $char) === 1;
	}

	$hyphenPos = mb_strrpos($form, '-');
	$openPos = mb_strpos($form, '(');
	$closePos = mb_strpos($form, ')');

	return $cache[$form] = array(
		'codes'          => $codes,
		'length'         => count($codes),
		'hasApostrophe'  => mb_strpos($form, "'") !== false,
		'uppers'         => $uppers,
		'hasSymbol'      => preg_match('/[-()]/u', $form) === 1,
		//規則6は語末からの距離で比べる
		'hyphenFromEnd'  => $hyphenPos === false ? false : mb_strlen($form) - $hyphenPos,
		'originalLength' => mb_strlen($form),
		'openPos'        => $openPos,
		'closePos'       => $closePos,
	);
}

//辞書順ソート用の比較関数
//紙辞書用と異なる
//strAを先にしたければ-1を返す。
function HKSCmpw($strA, $strB){
	$a = hksSortKey($strA['entry']['form']);
	$b = hksSortKey($strB['entry']['form']);

	//規則2 処理した文字列を先頭から比較して、字母順で先の文字を有す方を先とする
	$minLength = min($a['length'], $b['length']);
	for ($i = 0; $i < $minLength; $i++){
		if ($a['codes'][$i] !== $b['codes'][$i]){
			return $a['codes'][$i] <=> $b['codes'][$i];
		}
	}
	//短い方の単語の最後まで同じ文字列である場合、処理した文字列が短い方を先とする
	if ($a['length'] !== $b['length']){
		return $a['length'] <=> $b['length'];
	}

	//規則3 「'」が無い方を先とする
	if ($a['hasApostrophe'] !== $b['hasApostrophe']){
		return $a['hasApostrophe'] ? 1 : -1;
	}

	//規則4 元の文字列を先頭から比較して、大文字を有する方を先とする
	$minLength = min(count($a['uppers']), count($b['uppers']));
	for ($i = 0; $i < $minLength; $i++){
		if ($a['uppers'][$i] !== $b['uppers'][$i]){
			return $a['uppers'][$i] ? -1 : 1;
		}
	}

	//規則5 記号が含まれない方を先とする
	if ($a['hasSymbol'] !== $b['hasSymbol']){
		return $a['hasSymbol'] ? 1 : -1;
	}

	//規則6 両方が「-」を含む場合、「-」の位置が語末に近い方を先とする
	if ($a['hyphenFromEnd'] !== false && $b['hyphenFromEnd'] !== false
		&& $a['hyphenFromEnd'] !== $b['hyphenFromEnd']){
		return $a['hyphenFromEnd'] <=> $b['hyphenFromEnd'];
	}

	//規則7 元の文字列が短い方を先とする
	if ($a['originalLength'] !== $b['originalLength']){
		return $a['originalLength'] <=> $b['originalLength'];
	}

	//規則8 両方が「(」を含む場合、「(」の位置が語頭に近い方を先とする
	if ($a['openPos'] !== false && $b['openPos'] !== false && $a['openPos'] !== $b['openPos']){
		return $a['openPos'] <=> $b['openPos'];
	}
	//「(」の位置が同じ場合、「)」の位置が語頭に近い方を先とする
	if ($a['closePos'] !== false && $b['closePos'] !== false && $a['closePos'] !== $b['closePos']){
		return $a['closePos'] <=> $b['closePos'];
	}

	return 0;
}

//////////////////////////////////////////////////
//キャッシュ
//////////////////////////////////////////////////

//キャッシュディレクトリのパスを返す
function cacheDir(){
	return __DIR__ . '/cache';
}

//元ファイルの更新時刻とサイズから、キャッシュの有効性を判定する印を作る
function cacheStamp(array $sources){
	$stamp = array();
	foreach ($sources as $source){
		$stamp[] = is_file($source) ? filemtime($source) . ':' . filesize($source) : '-';
	}
	return implode('|', $stamp);
}

//元ファイルが更新されていなければキャッシュの内容を返す。利用できない場合はnull
function readCache($name, array $sources){
	$file = cacheDir() . '/' . $name . '.cache';
	if (!is_file($file)){
		return null;
	}
	$raw = @file_get_contents($file);
	if ($raw === false){
		return null;
	}
	$data = @unserialize($raw);
	if (!is_array($data) || !array_key_exists('stamp', $data) || !array_key_exists('value', $data)){
		return null;
	}
	return ($data['stamp'] === cacheStamp($sources)) ? $data['value'] : null;
}

//キャッシュを書き出す。失敗しても処理は続行できるため、エラーは無視する
function writeCache($name, $value, array $sources){
	$dir = cacheDir();
	if (!is_dir($dir) && !@mkdir($dir, 0777, true)){
		return;
	}
	$file = $dir . '/' . $name . '.cache';
	$temp = $file . '.' . getmypid() . '.tmp';//書きかけのファイルを読ませないため、別名で書いてから差し替える
	$data = serialize(array('stamp' => cacheStamp($sources), 'value' => $value));
	if (@file_put_contents($temp, $data, LOCK_EX) === false){
		return;
	}
	if (!@rename($temp, $file)){
		@unlink($temp);
	}
}

//////////////////////////////////////////////////
//データ読み込み
//////////////////////////////////////////////////

//辞書データを辞書順にソートして読み込む
//ソートは重いため結果を再利用するが、辞書データだけでなく比較関数を含むこのファイルも
//無効化の対象にしないと、並び順の仕様を変えたときに古い順序が残ってしまう
function loadDictionary($path){
	$sources = array($path, __FILE__);
	$cached = readCache('dictionary', $sources);
	if ($cached !== null){
		return $cached;
	}

	$raw = @file_get_contents($path);
	if ($raw === false){
		throw new RuntimeException('辞書ファイルを読み込めませんでした: ' . $path);
	}
	$json = json_decode($raw, true);
	if (!is_array($json) || !isset($json['words']) || !is_array($json['words'])){
		throw new RuntimeException('辞書ファイルの形式が不正です: ' . $path);
	}

	uasort($json['words'], 'HKSCmpw');
	writeCache('dictionary', $json, $sources);
	return $json;
}

//例文を引くための索引を組み立てる
//辞書データ側では例文と単語がIDで結ばれているだけなので、表示にも検索にも使える形にここでまとめる
//返り値：array(
//  'examples'      => id順に並べた例文。任意項目は空で埋めてある
//  'byWordId'      => 単語ID => その単語を使う例文のキーの配列
//  'formById'      => 単語ID => 見出し語
//  'textByWordId'  => 単語ID => その単語を使う例文の検索用テキスト
//)
function makeExampleIndex(array $json){
	$index = array('examples' => array(), 'byWordId' => array(), 'formById' => array(), 'textByWordId' => array());

	if (isset($json['words']) && is_array($json['words'])){
		foreach ($json['words'] as $singleEntry){
			if (isset($singleEntry['entry']['id'], $singleEntry['entry']['form'])){
				$index['formById'][$singleEntry['entry']['id']] = $singleEntry['entry']['form'];
			}
		}
	}
	if (!isset($json['examples']) || !is_array($json['examples'])){
		return $index;
	}

	//任意項目が欠けていても表示と検索で場合分けせずに済むよう、ここで形を揃える
	$examples = array();
	foreach ($json['examples'] as $singleExample){
		if (!isset($singleExample['id'], $singleExample['sentence'])){
			continue;
		}
		$examples[] = array(
			'id'         => $singleExample['id'],
			'sentence'   => $singleExample['sentence'],
			'translation' => isset($singleExample['translation']) ? $singleExample['translation'] : '',
			'supplement' => isset($singleExample['supplement']) ? $singleExample['supplement'] : '',
			'tags'       => isset($singleExample['tags']) && is_array($singleExample['tags']) ? $singleExample['tags'] : array(),
			'words'      => isset($singleExample['words']) && is_array($singleExample['words']) ? $singleExample['words'] : array(),
			'offer'      => isset($singleExample['offer']) && is_array($singleExample['offer']) ? $singleExample['offer'] : array(),
		);
	}

	//辞書データ中の並びは登録順で決まっていないため、id順に直してから通し番号を振る
	usort($examples, function ($a, $b){
		return $a['id'] <=> $b['id'];
	});
	$index['examples'] = $examples;

	foreach ($examples as $exampleKey => $singleExample){
		//全文検索でまとめて照合できるよう、例文1件分の文字列を先に作っておく
		//検索語は空白で区切られるため、改行でつないでおけば項目をまたいだ誤ヒットは起きない
		$text = implode("\n", array_filter(array_merge(
			array($singleExample['sentence'], $singleExample['translation'], $singleExample['supplement']),
			$singleExample['tags']
		), 'strlen'));

		$countedIds = array();
		foreach ($singleExample['words'] as $singleWord){
			if (!isset($singleWord['id']) || isset($countedIds[$singleWord['id']])){
				continue;//同じ単語を複数回使う例文を、二重に並べない
			}
			$countedIds[$singleWord['id']] = true;
			$wordId = $singleWord['id'];
			$index['byWordId'][$wordId][] = $exampleKey;
			$index['textByWordId'][$wordId] = isset($index['textByWordId'][$wordId])
				? $index['textByWordId'][$wordId] . "\n" . $text
				: $text;
		}
	}
	return $index;
}

//例文へのリンクの開始タグを返す
function makeExampleLink($exampleId){
	return '<a href="example.php#' . h(exampleAnchor($exampleId)) . '">';
}

//例文1件を指すアンカー名を返す
function exampleAnchor($exampleId){
	return 'example-' . preg_replace('/[^0-9]/', '', (string)$exampleId);
}

//プログラムの更新日を返す
//ファイルを分割しているため、最も新しいソースの更新時刻を採用する
function programUpdatedAt(){
	$files = array_merge(
		glob(__DIR__ . '/*.php'),
		glob(__DIR__ . '/*.js'),
		glob(__DIR__ . '/*.css')
	);
	$newest = 0;
	foreach ($files as $file){
		$newest = max($newest, filemtime($file));
	}
	return $newest;
}

//接辞テーブルを読み込む
//派生形の生成で単語ごとに使い回すため、接辞側の加工はここで済ませておく
//csvは[0]対象品詞、[1]形態、[2]説明、[3]ある場合は特殊処理の記載
function loadAffixTable($path){
	$table = array();
	$file = new SplFileObject($path);
	$file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::READ_AHEAD);
	foreach ($file as $row){
		if (!is_array($row) || !isset($row[0], $row[1], $row[2]) || $row[1] === ''){
			continue;
		}
		$table[] = array(
			'pos'            => $row[0],
			'form'           => $row[1],
			'description'    => $row[2],
			'noVoicing'      => isset($row[3]) && $row[3] === 'NO_VOICING',
			'isSuffix'       => startsWith($row[1], '-'),
			'isPrefix'       => endsWith($row[1], '-'),
			'withoutBracket' => preg_replace('/\(.*?\)/u', '', $row[1]),//カッコつき接辞のカッコ内をカッコごとなくした形
			'withBracket'    => preg_replace('/[\(\)]/u', '', $row[1]),//カッコつき接辞のカッコを外した形
		);
	}
	return $table;
}
