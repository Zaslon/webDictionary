<?php
//テスト実行スクリプト。プロジェクト直下で `php tests/run.php` として実行する。
require_once __DIR__ . '/../search.php';
require_once __DIR__ . '/../view.php';

$passed = 0;
$failed = 0;

function is_same($label, $expected, $actual){
	global $passed, $failed;
	if ($expected === $actual){
		$passed++;
		return;
	}
	$failed++;
	echo "FAIL: $label\n";
	echo "  期待: ", json_encode($expected, JSON_UNESCAPED_UNICODE), "\n";
	echo "  実際: ", json_encode($actual, JSON_UNESCAPED_UNICODE), "\n";
}

function makeEntry($form, $title, array $forms, array $contents = array(), $id = 1){
	return array(
		'entry'        => array('id' => $id, 'form' => $form),
		'translations' => array(array('title' => $title, 'forms' => $forms)),
		'tags'         => array(),
		'contents'     => $contents,
		'variations'   => array(),
		'relations'    => array(),
	);
}

//////////////////////////////////////////////////
//パラメータの正規化
//////////////////////////////////////////////////

is_same('normalizeType は既知の値をそのまま返す', 'all', normalizeType('all'));
is_same('normalizeType は未知の値をbothにする', 'both', normalizeType('word; DROP'));
is_same('normalizeType はnullをbothにする', 'both', normalizeType(null));
is_same('normalizeMode は未知の値をprtにする', 'prt', normalizeMode('../../etc/passwd'));
is_same('normalizeMode は既知の値をそのまま返す', 'perf', normalizeMode('perf'));

//////////////////////////////////////////////////
//リンク生成（XSS対策）
//////////////////////////////////////////////////

$_GET = array();
is_same(
	'makeLink は属性を引用符で囲みURLエンコードする',
	'<a href="dict.php?keyBox=zere&amp;type=both&amp;mode=prt&amp;page=1">',
	makeLink('zere', 'both', 'prt', 1)
);
is_same(
	'makeLink は不正なtypeを既定値に落とし、属性を注入させない',
	'<a href="dict.php?keyBox=zere&amp;type=both&amp;mode=prt&amp;page=1">',
	makeLink('zere', 'both onmouseover=alert(1)', 'prt', 1)
);
is_same(
	'makeLink は検索語の記号をURLエンコードする',
	'<a href="dict.php?keyBox=a%26b%22%3E%3Cscript%3E&amp;type=both&amp;mode=prt&amp;page=1">',
	makeLink('a&b"><script>', 'both', 'prt', 1)
);
is_same(
	'makeLink はidを付けられる',
	'<a href="dict.php?keyBox=zere&amp;type=both&amp;mode=prt&amp;page=2&amp;id=42">',
	makeLink('zere', 'both', 'prt', 2, 42)
);

$_GET = array('Idf' => 'true', 'voicing' => 'true');
is_same(
	'makeLink はイジェール文字表示と連濁の指定を引き継ぐ',
	'<a href="dict.php?keyBox=zere&amp;type=both&amp;mode=prt&amp;page=1&amp;Idf=true&amp;voicing=true">',
	makeLink('zere', 'both', 'prt', 1)
);
$_GET = array();

//////////////////////////////////////////////////
//静的ファイルのURL
//////////////////////////////////////////////////

is_same(
	'assetUrl は更新時刻を付ける',
	'dict.css?v=' . filemtime(__DIR__ . '/../dict.css'),
	assetUrl('dict.css')
);
is_same(
	'assetUrl は外部のURLをそのまま返す',
	'https://www.gstatic.com/charts/loader.js',
	assetUrl('https://www.gstatic.com/charts/loader.js')
);
is_same('assetUrl は無いファイルをそのまま返す', 'none.js', assetUrl('none.js'));

//////////////////////////////////////////////////
//検索
//////////////////////////////////////////////////

$verb = makeEntry('zere', '動詞', array('食べる'), array(array('title' => '語源', 'text' => 'ze:re')), 10);
$noun = makeEntry('kazere', '名詞', array('食事(名)'), array(array('title' => '語法', 'text' => 'zereの派生')), 11);

//stripos が0を返す先頭一致でも取りこぼさないこと
is_same('見出し語検索は先頭一致でもヒットする', true, isHit($verb, 'zere', 'word', 'prt'));
is_same('見出し語検索は途中一致でもヒットする', true, isHit($noun, 'zere', 'word', 'prt'));
is_same('見出し語検索は訳語を対象にしない', false, isHit($verb, '食べる', 'word', 'prt'));
is_same('訳語検索は見出し語を対象にしない', false, isHit($verb, 'zere', 'trans', 'prt'));
is_same('訳語検索は訳語にヒットする', true, isHit($verb, '食べる', 'trans', 'prt'));
is_same('見出し語・訳語検索は両方にヒットする', true, isHit($verb, 'zere', 'both', 'prt'));
is_same('見出し語・訳語検索は解説を対象にしない', false, isHit($noun, '派生', 'both', 'prt'));
is_same('全文検索は解説にもヒットする', true, isHit($noun, '派生', 'all', 'prt'));
is_same('全文検索は品詞にもヒットする', true, isHit($noun, '名詞', 'all', 'prt'));
is_same('見出し語・訳語検索はカッコ以左を除いて訳語を照合する', false, isHit($noun, '食事(', 'both', 'prt'));
is_same('完全一致は部分一致でヒットしない', false, isHit($noun, 'zere', 'both', 'perf'));
is_same('完全一致は大文字小文字を無視して一致する', true, isHit($verb, 'ZERE', 'both', 'perf'));
is_same('前方一致は先頭のみ一致する', true, isHit($noun, 'kaze', 'both', 'fwd'));
is_same('前方一致は途中一致でヒットしない', false, isHit($noun, 'zere', 'both', 'fwd'));

//////////////////////////////////////////////////
//検索語の整形
//////////////////////////////////////////////////

is_same('全角スペースで区切れる', array('a', 'b'), parseKeywords('a　b', 'both', 'prt'));
is_same('連続スペースと前後の空白を詰める', array('a', 'b'), parseKeywords('  a   b  ', 'both', 'prt'));
is_same('ダブルコーテーションの囲いを優先する', array('a b'), parseKeywords('"a b"', 'both', 'prt'));
is_same('完全一致では区切らずひとつに戻す', array('a b'), parseKeywords('a b', 'both', 'perf'));
is_same('通常検索では記号を落とす', array('arin'), parseKeywords('-(a)rin', 'both', 'prt'));
is_same('全文検索では記号を残す', array('-(a)rin'), parseKeywords('-(a)rin', 'all', 'prt'));
is_same('空の検索語は空配列になる', array(), parseKeywords('', 'both', 'prt'));
is_same('未入力は空配列になる', array(), parseKeywords(null, 'both', 'prt'));
is_same('記号のみの検索語は空配列になる', array(), parseKeywords('-()', 'both', 'prt'));

//////////////////////////////////////////////////
//複数語検索
//////////////////////////////////////////////////

$words = array(
	'a' => makeEntry('zere', '動詞', array('食べる'), array(), 10),
	'b' => makeEntry('kazere', '名詞', array('食事'), array(), 11),
	'c' => makeEntry('mira', '名詞', array('水'), array(), 12),
);
is_same('複数語はAND条件になる', array('b'), searchEntries($words, array('ze', '食事'), 'both', 'prt', false));
is_same('一致しない組み合わせは0件になる', array(), searchEntries($words, array('ze', '水'), 'both', 'prt', false));
is_same('単一語の検索も動く', array('a', 'b'), searchEntries($words, array('zere'), 'both', 'prt', false));
is_same('検索語なしは0件になる', array(), searchEntries($words, array(), 'both', 'prt', false));

//連濁: sere -> zere
is_same('連濁を含めない場合はヒットしない', array(), searchEntries($words, array('sere'), 'both', 'perf', false));
is_same('連濁を含める場合はヒットする', array('a'), searchEntries($words, array('sere'), 'both', 'perf', true));

is_same('idから見出し語を引ける', 'c', findEntryKeyById($words, 12));
is_same('存在しないidはnullを返す', null, findEntryKeyById($words, 999));

//////////////////////////////////////////////////
//例文
//////////////////////////////////////////////////

$exampleJson = array(
	'words' => array(
		makeEntry('zere', '動詞', array('食べる'), array(), 10),
		makeEntry('mira', '名詞', array('水'), array(), 12),
	),
	'examples' => array(
		array(
			'id' => 5, 'sentence' => 'Ref mirau zere.', 'translation' => '私は水を飲む。',
			'tags' => array(), 'words' => array(array('id' => 12), array('id' => 10), array('id' => 999)),
			'offer' => array('catalog' => 'zpdicDaily', 'number' => 3),
		),
		array(
			'id' => 1, 'sentence' => 'Ref zere.', 'translation' => '私は食べる。', 'supplement' => '補足',
			'tags' => array('日常'), 'words' => array(array('id' => 10), array('id' => 10)),
			'offer' => array('catalog' => 'zpdicDaily', 'number' => 1),
		),
	),
);
$exampleIndex = makeExampleIndex($exampleJson);

is_same('例文をid順に並べ替える', array(1, 5), array_column($exampleIndex['examples'], 'id'));
is_same('欠けている任意項目を空で埋める', '', $exampleIndex['examples'][1]['supplement']);
is_same('単語IDから例文を引ける', array(0, 1), $exampleIndex['byWordId'][10]);
is_same('例文を持たない単語は索引に載らない', false, isset($exampleIndex['byWordId'][11]));
is_same('単語IDから見出し語を引ける', 'mira', $exampleIndex['formById'][12]);
is_same('検索用テキストに原文と訳文を含む', true, strpos($exampleIndex['textByWordId'][12], '水を飲む') !== false);
is_same('検索用テキストにタグを含む', true, strpos($exampleIndex['textByWordId'][10], '日常') !== false);
is_same('例文が無い辞書でも索引を作れる', array(), makeExampleIndex(array('words' => array()))['examples']);

//全文検索の対象に例文を含める
$exampleWords = $exampleJson['words'];
is_same('全文検索は例文の原文にヒットする',
	array(0, 1), searchEntries($exampleWords, array('mirau'), 'all', 'prt', false, $exampleIndex));
is_same('全文検索は例文の訳文にヒットする',
	array(0), searchEntries($exampleWords, array('食べる。'), 'all', 'prt', false, $exampleIndex));
is_same('見出し語・訳語検索は例文を対象にしない',
	array(), searchEntries($exampleWords, array('食べる。'), 'both', 'prt', false, $exampleIndex));
is_same('索引を渡さなければ例文は検索されない',
	array(), searchEntries($exampleWords, array('食べる。'), 'all', 'prt', false));

//例文の表示
ob_start();
renderEntry($exampleWords[1], 'both', 'prt', $exampleIndex);
$html = ob_get_clean();
is_same('単語欄に例文を出す', true, strpos($html, 'Ref mirau zere.') !== false);
is_same('単語欄では例文にid属性を振らない', false, strpos($html, 'id="example-5"'));
is_same('例文の使用単語をリンクにする', true, strpos($html, '&amp;id=10">zere</a>') !== false);
is_same('表示中の単語自身は使用単語に並べない', false, strpos($html, '&amp;id=12">mira</a>'));
is_same('辞書に無い単語IDは並べない', false, strpos($html, 'id=999'));

$relatedEntry = makeEntry('mira', '名詞', array('水'), array(array('title' => '語法', 'text' => '解説')), 12);
$relatedEntry['relations'] = array(array('title' => '参照', 'entry' => array('id' => 10, 'form' => 'zere')));
ob_start();
renderEntry($relatedEntry, 'both', 'prt', $exampleIndex);
$html = ob_get_clean();
is_same('例文は関連語より下に出す', true, strpos($html, 'wordRelation') < strpos($html, 'wordExamples'));

ob_start();
renderEntry(makeEntry('kere', '動詞', array('する'), array(), 11), 'both', 'prt', $exampleIndex);
$html = ob_get_clean();
is_same('例文が無ければ欄を出さない', false, strpos($html, 'wordExamples'));

//例文一覧ページ側の表示
ob_start();
renderExample($exampleIndex['examples'][1], $exampleIndex, 'both', 'prt');
$html = ob_get_clean();
is_same('例文一覧では例文にid属性を振る', true, strpos($html, 'id="example-5"') !== false);
is_same('例文一覧では全ての使用単語を並べる', true, strpos($html, '&amp;id=12">mira</a>') !== false);
is_same('出典を出す', true, strpos($html, 'zpdicDaily #3') !== false);

is_same('同じ単語を複数回使っても例文は1回だけ紐づく', 2, count($exampleIndex['byWordId'][10]));

//辞書データはエスケープして出力する
ob_start();
renderExample(array(
	'id' => 1, 'sentence' => '<script>', 'translation' => "一行目\n二行目", 'supplement' => '',
	'tags' => array('a&b'), 'words' => array(), 'offer' => array(),
), $exampleIndex, 'both', 'prt');
$html = ob_get_clean();
is_same('例文の原文をエスケープする', false, strpos($html, '<script>'));
is_same('例文の訳文の改行を<br />にする', true, strpos($html, '一行目<br />') !== false);
is_same('例文のタグをエスケープする', true, strpos($html, 'a&amp;b') !== false);
is_same('出典が無ければ出さない', false, strpos($html, 'exampleOffer'));

is_same('アンカー名は数字だけにする', 'example-1', exampleAnchor('1"><script>'));

//例文一覧のページ送り
ob_start();
renderExampleNavigation(45, 2);
$html = ob_get_clean();
is_same('例文数どおりにページ送りを出す', 3, substr_count($html, '<li'));
is_same('現在のページはリンクにしない', true, strpos($html, '<li class="currentPage" aria-current="page">2</li>') !== false);

ob_start();
renderExampleNavigation(20, 1);
$html = ob_get_clean();
is_same('1ページに収まる場合はページ送りを出さない', '<nav aria-label="ページ送り"><ul class="navigation"></ul></nav>', $html);

//////////////////////////////////////////////////
//連濁
//////////////////////////////////////////////////

is_same('単子音の連濁', 'gere', initialVoicing('kere'));
is_same('二重子音の連濁', 'gzere', initialVoicing('ksere'));
is_same('変音記号付きの連濁', 'z\'gere', initialVoicing('s\'here'));
is_same('連濁しない頭子音はそのまま', 'mira', initialVoicing('mira'));
is_same('母音で始まる語はそのまま', 'eref', initialVoicing('eref'));

//////////////////////////////////////////////////
//派生形
//////////////////////////////////////////////////

$affixTable = loadAffixTable(__DIR__ . '/../affixTable.csv');
is_same('接辞テーブルを読み込める', 43, count($affixTable));

function derivedForms($wordForm, $pos, array $affixTable){
	$forms = array();
	foreach (makeDerivations($wordForm, $pos, $affixTable) as $singleDerivation){
		$forms[] = $singleDerivation['form'];
	}
	return $forms;
}

//名詞 mira + -(e)f（主格）。母音で終わるのでカッコ内は落ちる
$derivations = derivedForms('mira', '名詞', $affixTable);
is_same('母音で終わる名詞にはカッコ内を落とした接尾辞が付く', true, in_array('miraf', $derivations, true));
is_same('母音で終わる名詞には対格が付く', true, in_array('mirau', $derivations, true));

//子音で終わる名詞 sampan にはカッコ内が残る
$derivations = derivedForms('sampan', '名詞', $affixTable);
is_same('子音で終わる名詞にはカッコ内を残した接尾辞が付く', true, in_array('sampanef', $derivations, true));
is_same('名詞は語形のまま動詞化する', true, in_array('sampane', $derivations, true));
is_same('品詞を変えない接頭辞は語形に付く', true, in_array('mozampan', $derivations, true));

//記述詞は -n を外した語幹と -in を外した語幹の両方が語幹の候補になる
$derivations = derivedForms('kasin', '記述詞', $affixTable);
is_same('記述詞は-nを外した語幹に接頭辞が付く', true, in_array('tegasi', $derivations, true));
is_same('記述詞は-inを外した語幹にも接頭辞が付く', true, in_array('tegas', $derivations, true));
is_same('記述詞は語幹から動詞を派生する', true, in_array('kasie', $derivations, true));
is_same('品詞を変えない接頭辞は記述詞の語尾を残す', true, in_array('amgasin', $derivations, true));

//NO_VOICING 指定の接辞は連濁させない
$derivations = derivedForms('kere', '動詞', $affixTable);
is_same('NO_VOICING指定の接頭辞は連濁させない', true, in_array('mirkere', $derivations, true));
is_same('動詞の接尾辞は語末のeを外した語幹に付く', true, in_array('keresk', $derivations, true));
is_same('接尾辞のカッコ内母音は語幹の末尾で決まる', true, in_array('kerin', $derivations, true));
is_same('接尾辞のカッコ内母音を見出し語の末尾で決めない', false, in_array('kern', $derivations, true));

//eで終わらない動詞、nで終わらない記述詞から余分な1文字を落とさない
$derivations = derivedForms('ennast', '動詞', $affixTable);
is_same('eで終わらない動詞は語形がそのまま語幹になる', true, in_array('ennastesk', $derivations, true));
$derivations = derivedForms('poko', '記述詞', $affixTable);
is_same('nで終わらない記述詞は語形がそのまま語幹になる', true, in_array('teboko', $derivations, true));

//複数の品詞を持つ見出し語は、全ての品詞の派生形を出す
$multiPos = makeEntry('kasin', '記述詞', array('赤い'));
$multiPos['translations'][] = array('title' => '名詞', 'forms' => array('赤'));
$multiPos['translations'][] = array('title' => '記述詞', 'forms' => array('赤く'));
is_same('同じ品詞が複数の訳語欄にあっても一度だけ扱う', array('記述詞', '名詞'), entryPosList($multiPos));
$derivations = array();
foreach (entryPosList($multiPos) as $singlePos){
	$derivations = array_merge($derivations, derivedForms('kasin', $singlePos, $affixTable));
}
is_same('1つ目の訳語欄の品詞で派生する', true, in_array('tegasi', $derivations, true));
is_same('2つ目以降の訳語欄の品詞も派生の対象にする', true, in_array('kasinef', $derivations, true));

//////////////////////////////////////////////////
//接辞サジェスト
//////////////////////////////////////////////////

function suggestionLabels(array $words, array $affixTable, array $keyWords, $maxDepth = DERIVATION_MAX_DEPTH){
	$labels = array();
	foreach (findDerivationSuggestions($words, $affixTable, $keyWords, $maxDepth) as $singleSuggestion){
		$labels[] = $singleSuggestion['form'] . '#' . $singleSuggestion['id'] . ' の ' . $singleSuggestion['description'];
	}
	return $labels;
}

$verbOnly = array(makeEntry('kere', '動詞', array('する'), array(), 1));

is_same('1段の派生形を辿る', array('kere#1 の 対格にあたる名詞派生'),
	suggestionLabels($verbOnly, $affixTable, array('keresk')));
is_same('接頭辞と接尾辞を同時に適用した形を辿る', array('kere#1 の 再帰自動詞形 の 対格にあたる名詞派生'),
	suggestionLabels($verbOnly, $affixTable, array('mirkeresk')));
is_same('3段重ねた形も辿る', array('kere#1 の 再帰自動詞形 の 対格にあたる名詞派生 の 主格'),
	suggestionLabels($verbOnly, $affixTable, array('mirkereskef')));
is_same('段数の上限を超える形は辿らない', array(),
	suggestionLabels($verbOnly, $affixTable, array('mirkereskef'), 2));
is_same('派生後の品詞が空の接辞からは更に派生させない', array(),
	suggestionLabels($verbOnly, $affixTable, array('mirkereskefu')));
is_same('同じ形になる別の接辞はそれぞれ出す',
	array('kere#1 の 命令形', 'kere#1 の 再帰自動詞形の主格にあたる名詞派生'),
	suggestionLabels($verbOnly, $affixTable, array('kera')));
is_same('検索語と関係のない語からは辿らない', array(),
	suggestionLabels($verbOnly, $affixTable, array('zzzzz')));

//語幹を共有する見出し語が複数ある場合、遠回りの解釈は出さない
$sharedStem = array(
	makeEntry('kere', '動詞', array('する'), array(), 1),
	makeEntry('ker', '名詞', array('こと'), array(), 2),
);
is_same('既にある見出し語を経由する解釈は出さない', array('kere#1 の 対格にあたる名詞派生'),
	suggestionLabels($sharedStem, $affixTable, array('keresk')));
is_same('検索語が複数あるときは全ての語について辿る',
	array('kere#1 の 対格にあたる名詞派生', 'ker#2 の 主格'),
	suggestionLabels($sharedStem, $affixTable, array('keresk', 'keref')));

//////////////////////////////////////////////////
//辞書順ソート
//仕様：https://zaslon.info/idyerin/辞書順について/
//////////////////////////////////////////////////

function hksSorted(array $forms){
	$entries = array();
	foreach ($forms as $index => $form){
		$entries[$index] = array('entry' => array('id' => $index, 'form' => $form));
	}
	uasort($entries, 'HKSCmpw');
	$sorted = array();
	foreach ($entries as $entry){
		$sorted[] = $entry['entry']['form'];
	}
	return $sorted;
}

//2語の前後関係を確かめる。ソートの安定性に頼らず、比較関数の返り値で直接判定する
function is_before($label, $former, $latter){
	$result = HKSCmpw(array('entry' => array('form' => $former)), array('entry' => array('form' => $latter)));
	if ($result < 0){
		global $passed;
		$passed++;
		return;
	}
	global $failed;
	$failed++;
	echo "FAIL: $label\n";
	echo "  「$former」が「$latter」より先になるはずが ", $result === 0 ? '同順（決着せず）' : '逆順', "\n";
}

//規則2 字母順
is_same('母音はeaoiuの順に並ぶ', array('e', 'a', 'o', 'i', 'u'), hksSorted(array('u', 'i', 'o', 'a', 'e')));
is_same('子音はhkstcnrmpfgzdbvの順に並ぶ',
	array('ha', 'ka', 'sa', 'ta', 'ca', 'na', 'ra', 'ma', 'pa', 'fa', 'ga', 'za', 'da', 'ba', 'va'),
	hksSorted(array('va', 'ba', 'da', 'za', 'ga', 'fa', 'pa', 'ma', 'ra', 'na', 'ca', 'ta', 'sa', 'ka', 'ha')));
is_same('数字と記号は字母の後に並ぶ', array('va', 'v0', 'v9', 'v/', 'v,'), hksSorted(array('v,', 'v/', 'v9', 'v0', 'va')));
is_same('空白は字母順の先頭', array('et a', 'ete'), hksSorted(array('ete', 'et a')));
is_before('空白を含む語は同じ綴りで始まる語より先', 'etig amziskaomin', 'etige');
is_same('前方が同じ場合は処理した文字列が短い方を先', array('ka', 'kata'), hksSorted(array('kata', 'ka')));

//規則1 「-」「(」「)」「'」は比較前に削除し、大文字小文字を区別しない
//「-」は規則2の比較対象から消えるため、3語とも処理した文字列は eta になり、規則5と規則6で決着する
is_same('ハイフンは語頭でも語中でも語末でも比較対象から外れる',
	array('eta', 'eta-', 'et-a'), hksSorted(array('et-a', 'eta-', 'eta')));
is_before('括弧は無視されるので et(e)- は eta より先', "et'(e)-", 'ETa');
is_before('アポストロフィは字母として数えない', "et'a", 'etas');

//規則3 「'」が無い方を先
is_before('処理した文字列が同一なら「\'」が無い方を先', '-(e)ta', "et'a");
is_before('処理した文字列が同一なら「\'」が無い方を先（記号つき同士）', 'eta-s', "et'as");

//規則4 元の文字列を先頭から比較して、大文字を有する方を先
is_before('先頭の大文字が先', 'ETa', 'EtA');
is_before('後ろの大文字も小文字より先', 'EtA', 'Eta');
is_before('大文字を持つ語は持たない語より先', 'eTa', 'eta');

//規則5 記号が含まれない方を先
is_before('記号が無い方を先', 'Eta', 'Eta-');
is_before('記号が無い方を先（小文字）', 'eta', 'eta-');
is_same('記号を含まない語が先に並ぶ', array('rin', '-rin'), hksSorted(array('-rin', 'rin')));

//規則6 「-」の位置が語末に近い方を先
is_before('語末の「-」が語中の「-」より先', 'et(a)-', 'et-a');
is_before('語中の「-」が語頭の「-」より先', 'et-a', '-eta');

//規則7 元の文字列が短い方を先
is_before('規則6まで同着なら元の文字列が短い方を先', 'eta-', 'et(a)-');
is_before('規則6まで同着なら元の文字列が短い方を先（語頭ハイフン）', '-eta', '-(e)ta');

//仕様書に掲載されている並び順の例を、そのまま再現できること
$specExample = array(
	"et'(e)-", 'ETa', 'EtA', 'Eta', 'Eta-', 'eTa', 'eta', 'eta-',
	'et(a)-', 'et-a', '-eta', '-(e)ta', "et'a", 'etas', 'eta-s', "et'as", 'etasa',
);
is_same('仕様書の例をソートで再現する', $specExample, hksSorted(array_reverse($specExample)));
//ソートの安定性に助けられていないことを、隣接する組すべてで確かめる
foreach ($specExample as $index => $singleForm){
	if (isset($specExample[$index + 1])){
		is_before('仕様書の例 ' . ($index + 1) . '番目と' . ($index + 2) . '番目', $singleForm, $specExample[$index + 1]);
	}
}

//////////////////////////////////////////////////
//表示
//////////////////////////////////////////////////

//辞書データもエスケープして出力する
ob_start();
renderEntry(makeEntry('<script>', '名詞', array('a&b')), 'both', 'prt');
$html = ob_get_clean();
is_same('見出し語をエスケープする', false, strpos($html, '<script>'));
is_same('訳語をエスケープする', true, strpos($html, 'a&amp;b') !== false);

ob_start();
renderEntry(makeEntry('mira', '名詞', array('水'), array(array('title' => '用例', 'text' => "一行目\n二行目"))), 'both', 'prt');
$html = ob_get_clean();
is_same('用例の改行を<br />にする', true, strpos($html, '一行目<br />') !== false);

ob_start();
renderEntry(makeEntry('mira', '名詞', array('水'), array(array('title' => '発音記号', 'text' => 'mira'))), 'both', 'prt');
$html = ob_get_clean();
is_same('表示対象外の項目は要素ごと出さない', false, strpos($html, 'wordContents'));

//発音記号
ob_start();
renderEntry(makeEntry('AVG', '名詞', array('水'), array(array('title' => '発音記号', 'text' => 'aːveg'))), 'both', 'prt');
$html = ob_get_clean();
is_same('辞書データの発音記号をそのまま出す', true, strpos($html, '<span class="wordPronunciation">/aːveg/</span>') !== false);
is_same('辞書データに発音記号があればJSに任せない', false, strpos($html, 'data-form'));

ob_start();
renderEntry(makeEntry('mira', '名詞', array('水'), array(array('title' => '発音記号', 'text' => ''))), 'both', 'prt');
$html = ob_get_clean();
is_same('発音記号が空欄なら見出し語を渡して空で置く', true, strpos($html, '<span class="wordPronunciation" data-form="mira"></span>') !== false);

ob_start();
renderEntry(makeEntry('mira', '名詞', array('水')), 'both', 'prt');
$html = ob_get_clean();
is_same('発音記号の項目自体が無くても見出し語を渡す', true, strpos($html, '<span class="wordPronunciation" data-form="mira"></span>') !== false);

ob_start();
renderEntry(makeEntry('a"><script>', '名詞', array('水')), 'both', 'prt');
$html = ob_get_clean();
is_same('JSに渡す見出し語をエスケープする', false, strpos($html, '<script>'));

ob_start();
renderPronunciationRules('"a" -> /X/; # </script><script>alert(1)</script>');
$html = ob_get_clean();
is_same('発音規則から<script>を閉じさせない', false, strpos($html, '</script><script>'));
is_same('発音規則をJSONとして埋め込む', '"a" -> /X/; # </script><script>alert(1)</script>',
	json_decode(substr($html, strlen('<script type="application/json" id="snojRules">'), -strlen('</script>'))));

ob_start();
renderPronunciationRules(null);
is_same('発音規則が無ければ要素ごと出さない', '', ob_get_clean());

//ページ送り
ob_start();
renderNavigation(45, 2, array('a', 'b'), 'both', 'prt');
$html = ob_get_clean();
is_same('総ページ数どおりにページ送りを出す', 3, substr_count($html, '<li'));
is_same('現在のページはリンクにしない', true, strpos($html, '<li class="currentPage" aria-current="page">2</li>') !== false);
is_same('ページ送りは検索語を引き継ぐ', true, strpos($html, 'keyBox=a+b') !== false);

ob_start();
renderNavigation(20, 1, array('a'), 'both', 'prt');
$html = ob_get_clean();
is_same('1ページに収まる場合はページ送りを出さない', '<nav aria-label="ページ送り"><ul class="navigation"></ul></nav>', $html);

//////////////////////////////////////////////////

echo "\n", $failed === 0 ? "OK" : "NG", ": {$passed} passed, {$failed} failed\n";
exit($failed === 0 ? 0 : 1);
