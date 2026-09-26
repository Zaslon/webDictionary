<?php
//dict.php の #results の中身（検索結果の断片）だけを返す。livesearch.js がページ内の差し替えに使う
//クエリは dict.php と同じ。断片の中のリンクは dict.php からの相対URLのまま出す（dict.php に差し込まれるため）
require_once __DIR__ . '/search.php';
require_once __DIR__ . '/view.php';

//断片だけのURLが検索結果に載らないようにし、辞書の更新をすぐ拾えるよう毎回問い合わせさせる
header('X-Robots-Tag: noindex');
header('Cache-Control: no-cache');

//途中で失敗したときに書きかけの断片を返さないよう、組み立て終えてから出す
ob_start();
try{
	$json = loadDictionary(__DIR__ . '/idyer.json');
	$affixTable = loadAffixTable(__DIR__ . '/affixTable.csv');
	$words = $json["words"];
	$exampleIndex = makeExampleIndex($json);

	$request = readSearchRequest();
	$searchResult = runSearch($words, $affixTable, $exampleIndex, $request);
	renderSearchResults($words, $exampleIndex, isset($json['snoj']) ? $json['snoj'] : null, $request, $searchResult);
	$body = ob_get_clean();
}catch (Throwable $error){
	ob_end_clean();
	//例外の文言には辞書のパスが入るため、本文には出さない（クライアントも本文を表示しない）
	http_response_code(500);
	header('Content-Type: text/plain; charset=UTF-8');
	echo 'error';
	return;
}

header('Content-Type: text/html; charset=UTF-8');
echo $body;
