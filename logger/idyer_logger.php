<?php
	// 単語数を記録するスクリプト。cronからCLIで定期実行する。
	//   php logger/idyer_logger.php
	// 前回の記録から単語数が変わっていれば dictLog.csv に1行追記する。

	// Web経由では実行させない。
	// dictLog.csv はグラフがfetchで読むため logger/ を公開しておく必要があり、
	// このスクリプトも公開ディレクトリに置かれてしまうので、ここで弾く。
	if (PHP_SAPI !== 'cli') {
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
		// ファイルの中身を配列で取得.
		$csv = file($fname);

		// ヘッダー削除
		$csvBody = array_splice($csv, 1);

		// 各行を配列に直す
		$arr = array();
		foreach ($csvBody as $row => $rowContent) {
			$rowArray = explode(',', $rowContent);
			$arr[$row] = $rowArray;
		}
		return $arr;
	}

	//更新があれば単語数, なければfalse を返す。
	function check_change($dictionaryFile, $logFile){
		//辞書読み込み
		$dics = json_read($dictionaryFile);
		//ログ読み込み
		$logs = csv_read($logFile);
		$i = array_key_last($logs);

		$lastWordCount = $logs[$i][1];
		$nowWordCount = count($dics["words"]);

		if ((int)$nowWordCount !== (int)$lastWordCount) {
			return $nowWordCount;
		}
		return false;
	}

	//書き込んだ配列を返す（Y-m-d, 単語数）
	function idyer_logger($logFile, $wordCount){
		$arr = array(date("Y-m-d"), $wordCount);
		$fp = fopen($logFile, "a");
		fputcsv($fp, $arr); //追記で書き込む
		fclose($fp);
		return $arr;
	}

	// ---- 実行部 ----

	if (!is_readable($dictionaryFile)) {
		fwrite(STDERR, "辞書ファイルが読めません: {$dictionaryFile}\n");
		exit(1);
	}
	if (!is_writable($logFile)) {
		fwrite(STDERR, "ログファイルに書き込めません: {$logFile}\n");
		exit(1);
	}

	$wordCount = check_change($dictionaryFile, $logFile);
	if ($wordCount === false) {
		// 単語数に変化なし。何も書かずに終了する
		exit(0);
	}

	$row = idyer_logger($logFile, $wordCount);
	// cronのメールに残るよう、書き込んだ内容を出力する
	echo "logged: {$row[0]},{$row[1]}\n";
