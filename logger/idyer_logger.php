<?php
	// 単語数を記録するスクリプト。cronからCLIで定期実行する。
	//   php logger/idyer_logger.php

	// Web経由では実行させない。
	// dictLog.csv はグラフがfetchで読むため logger/ を公開しておく必要があり、
	// このスクリプトも公開ディレクトリに置かれてしまうので、ここで弾く。
	// cronがCGI版バイナリでPHPを起動する環境があり PHP_SAPI だけでは判定できないため、
	// HTTPリクエスト由来の変数の有無で見る。
	if (PHP_SAPI !== 'cli' && (isset($_SERVER['REQUEST_METHOD']) || isset($_SERVER['REMOTE_ADDR']))) {
		http_response_code(403);
		exit;
	}

	date_default_timezone_set('Asia/Tokyo');

	$dictionaryFile = __DIR__ . '/../idyer.json';
	$logFile        = __DIR__ . '/dictLog.csv';

	function json_read($fname){
		$json = file_get_contents($fname);
		$json = mb_convert_encoding($json, 'UTF8', 'ASCII,JIS,UTF-8,EUC-JP,SJIS-WIN');
		return json_decode($json,true);
	}

	function csv_read($fname){
		$csv = file($fname);

		// 1行目は見出し行
		$csvBody = array_splice($csv, 1);

		$arr = array();
		foreach ($csvBody as $row => $rowContent) {
			$rowArray = explode(',', $rowContent);
			$arr[$row] = $rowArray;
		}
		return $arr;
	}

	//更新があれば単語数, なければfalse を返す。
	function check_change($dictionaryFile, $logFile){
		$dics = json_read($dictionaryFile);
		$logs = csv_read($logFile);
		$i = array_key_last($logs);

		$lastWordCount = $logs[$i][1];
		$nowWordCount = count($dics["words"]);

		if ((int)$nowWordCount !== (int)$lastWordCount) {
			return $nowWordCount;
		}
		return false;
	}

	//書き込んだ行（Y-m-d, 単語数）を返す
	function idyer_logger($logFile, $wordCount){
		$arr = array(date("Y-m-d"), $wordCount);
		$fp = fopen($logFile, "a");
		fputcsv($fp, $arr);
		fclose($fp);
		return $arr;
	}

	// ---- 実行部 ----

	// STDERR定数はCLI SAPIでしか定義されない。
	function err($message){
		$fp = fopen('php://stderr', 'w');
		fwrite($fp, $message);
		fclose($fp);
	}

	if (!is_readable($dictionaryFile)) {
		err("辞書ファイルが読めません: {$dictionaryFile}\n");
		exit(1);
	}
	if (!is_writable($logFile)) {
		err("ログファイルに書き込めません: {$logFile}\n");
		exit(1);
	}

	$wordCount = check_change($dictionaryFile, $logFile);
	if ($wordCount === false) {
		exit(0);
	}

	$row = idyer_logger($logFile, $wordCount);
	// cronのメールに残るよう、書き込んだ内容を出力する
	echo "logged: {$row[0]},{$row[1]}\n";
