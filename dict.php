<?php echo '<' , '?xml version="1.0" encoding="utf-8"?' , '>'; ?>
<?php
	require 'func.php';

	//変化型テーブル読み込み
	$fname = 'affixTable.csv';
	$affixTable = new SplFileObject($fname);
	$affixTable -> setFlags(SplFileObject::READ_CSV); //[0]対象品詞、[1]形態、[2]説明のcsv、[3]ある場合は特殊処理の記載

	//json読み込み
	$fname = 'idyer.json';
	$json = file_get_contents($fname);
	$json = mb_convert_encoding($json, 'UTF8', 'ASCII,JIS,UTF-8,EUC-JP,SJIS-WIN');
	$json = json_decode($json,true);
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="ja" xml:lang="ja" dir="ltr">
<head>
<?php
	require 'anal.php';
?>
<meta name="viewport" content="width=device-width,initial-scale=1,user-scalable=yes" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta http-equiv="Content-Script-Type" content="text/javascript" />
<meta http-equiv="Content-Style-Type" content="text/css" /> 
<meta name="Description" content="イジェール語オンライン辞書" />
<meta name="keywords" content="人工言語,辞書," />
<meta property="og:type" content="dictionary" />
<meta property="og:title" content="イジェール語 オンライン辞書" />
<meta property="og:description" content="イジェール語 オンライン辞書" />
<meta property="og:url" content="https://zaslon.info/dict/dict.php" />
<meta property="og:site_name" content="イジェール語 オンライン辞書" />
<meta property="og:image" content="https://zaslon.info/wordpress/wp-content/uploads/2020/08/cropped-ZaslonI-1.png">
<meta name="twitter:card" content="summary" />
<meta name="twitter:site" content="@Zaslon" />
<link rel="stylesheet" type="text/css" href="dict.css" />
<link rel="shortcut icon" href="favicon.ico" />
<link rel="icon" href="favicon.ico" />
<title>イジェール語 オンライン辞書</title>
</head>
<body>
<div class="all">
	<div id="header">
	
	<h1>イジェール語 オンライン辞書</h1>
	<ul id="menu">
		<li><a class="menu" href="https://zaslon.info/idyerin/%e6%a4%9c%e7%b4%a2%e4%bb%95%e6%a7%98/">検索仕様</a></li>
		<li><a class="menu" href="https://zaslon.info/idyerin/%e8%be%9e%e6%9b%b8%e5%87%a1%e4%be%8b/">凡例</a></li>
		<li><a class="menu" href="https://zaslon.info/idyer">ホームへ戻る</a></li>
	</ul>
	<div class="dictVer">
		<?php
		date_default_timezone_set('Asia/Tokyo');
		echo "<p>プログラム更新日：",date("Y/m/d",filemtime(__FILE__)),"</p>";
		echo "<p>辞書更新日：",date("Y/m/d",filemtime($fname)),"<br />";
		echo "単語数：",count($json["words"]),"</p>";
		?>
	</div>
	<?php
	$checked_1 = "";
	$checked_2 = "";
	$checked_3 = "";
	$checked_4 = "";
	$checked_5 = "";
	$checked_6 = "";
	$checked_7 = "";
	$checked_8 = "";
	$checked_9 = "";
	
	//スーパーグローバル関数の処理。
	//返り値：
	//文字列 or false
	
	$type = ((isset($_GET["type"])) && ($_GET["type"] !== "")) ? $_GET["type"] :false;
	$mode = ((isset($_GET["mode"])) && ($_GET["mode"] !== "")) ? $_GET["mode"] :false;
	$idf = ((isset($_GET["Idf"])) && ($_GET["Idf"] !== "")) ? true  :false;
	$voicing = ((isset($_GET["voicing"])) && ($_GET["voicing"] !== "")) ? true  :false;
	$keyBox = ((isset($_GET["keyBox"])) && ($_GET["keyBox"] !== "")) ? $_GET["keyBox"]  :false;
	$id = (isset($_GET["id"])) && ($_GET["id"] !== "") ? (int)$_GET["id"] :false;
	$page = ((isset($_GET["page"])) && ($_GET["page"] !== "") && (preg_match("/^[0-9]+$/", $_GET["page"]))) ? (int)$_GET["page"] : 1; //ページIDに数字以外を入力された場合、強制的に1とする。
	
	if($type) {
		switch($type) {
			case "word":
				$checked_1 = "checked";
				break;
			case "trans":
				// $checked_2 = "checked"; 本来はこの表記だが、訳語検索モードで検索された次の検索時は両方モードを選択するようにする
				$checked_3 = "checked";
				break;
			case "both":
				$checked_3 = "checked";
				break;
			case "all":
				$checked_4 = "checked";
				break;
			default:
				$checked_3 = "checked";
				$type = "both";
				break;
		}
	}else{
		//デフォルトで両方検索を選択
		$checked_3 = "checked";
		$type = "both";
	}
	
	if($idf) {
		$checked_5 = "checked";
	}else{
		//デフォルトで空欄
	}
	
	if($voicing) {
		$checked_9 = "checked";
	}else{
		//デフォルトで空欄
	}
		
	if($mode) {
		switch($mode) {
			case "prt":
				$checked_6 = "checked";
				break;
			case "fwd":
				// $checked_7 = "checked"; 本来はこの表記だが、前方一致モードで検索された次の検索時は部分一致を選択するようにする
				$checked_6 = "checked";
				break;
			case "perf":
				$checked_8 = "checked";
				break;
			default:
				$checked_6 = "checked";
				$mode = "prt";
				break;
		}
	}else{
		//デフォルトで部分一致を選択
		$checked_6 = "checked";
		$mode = "prt";
	}
	?>
	
	<form action="" method="GET">
		<div class='textAndSubmit'><input type="text" name="keyBox"><input type="submit" name="submit" id="btn" value="検索"></div>
<!--		<div class='buttonAndLabel'><input type="radio" name="type" id="c1" value="word" <?php echo $checked_1; ?>><label for="c1">見出し語検索</label></div> -->
<!--		<div class='buttonAndLabel'><input type="radio" name="type" id="c2" value="trans" <?php echo $checked_2; ?>><label for="c2">訳語検索</label></div> -->
		<div class='buttonAndLabel'><input type="radio" name="type" id="c3" value="both" <?php echo $checked_3; ?>><label for="c3">見出し語・訳語検索</label></div>
		<div class='buttonAndLabel'><input type="radio" name="type" id="c4" value="all" <?php echo $checked_4; ?>><label for="c4">全文検索</label></div>
		<div class='buttonAndLabel'><input type="checkbox" name="Idf" id="c5" value="true" <?php echo $checked_5; ?>><label for="c5">イジェール文字表示</label></div>
		<div class='buttonAndLabel'><input type="radio" name="mode" id="c6" value="prt" <?php echo $checked_6; ?>><label for="c6">部分一致</label></div>
<!--		<div class='buttonAndLabel'><input type="radio" name="mode" id="c7" value="fwd" <?php echo $checked_7; ?>><label for="c7">前方一致</label></div> -->
		<div class='buttonAndLabel'><input type="radio" name="mode" id="c8" value="perf" <?php echo $checked_8; ?>><label for="c8">完全一致</label></div>
		<div class='buttonAndLabel'><input type="checkbox" name="voicing" id="c9" value="true" <?php echo $checked_9; ?>><label for="c9">検索対象に連濁派生語を含む</label></div>
		<input type="hidden" name="page" value="1">
	</form>
	</div>

	<div id="main">
	<?php
	//////初期化//////
	$func = $mode ? setFunc($mode): "stripos";
	$hitWordIds = array();
	$hitEntryIds = array();
	$hitAmount =0;
	$keyWord = "";
	$totalPages = 0;
	$wordNumPerPage = 40;
	//keyBoxに入力されているときのみ，$keyWordsに代入
	if ($keyBox){
		$keyWord = preg_replace('/[　]/u', ' ', $_GET["keyBox"]);	//全角スペースを半角スペースに変換
		$keyWord = preg_replace('/\s\s+/u', ' ', $keyWord);		//スペース2つ以上であれば，1つに削減
		$keyWord = preg_replace('/(^[\s]|[\s]$)/u', '', $keyWord);	//先頭と末尾のスペースを削除
		if ($type !== 'all' && $mode !== 'perf'){					//全文検索の場合、完全一致検索の場合は記号を削除しない
			$keyWord = deleteNonIdyerinCharacters($keyWord);
		}
		$keyWords = str_getcsv($keyWord, ' ', "\"");				//スペースで区切られた検索語を分離して配列に格納。ただしダブルコーテーションの囲いをより優先する
		if ($mode === 'perf'){
			$keyWords = (array)implode(" ", $keyWords);				//完全一致検索の場合は一つに戻す
		}
	}
	$tempHitWordIds = array(); // i-1番目の検索ワードに対してのヒットids格納
	$tempHitEntryIds = array(); // i-1番目の検索ワードに対してのヒットids格納
	//キーワードの数だけ結果一時保存用の配列を用意
	$keyWordsAmount = ((isset($keyWords)) && ($keyWords !== "")) ? count($keyWords) : 0;
	for ($i = 0; $i < $keyWordsAmount; $i++){
		$tempHitWordIds[$i] = array(); 
		$tempHitEntryIds[$i] = array(); 
	}
	//////ここまで初期化//////
	
	//ここから検索部。検索の結果を格納する。
	if(empty($keyWords[0])){
		echo "<p>検索ワードを入力してください。</p>";//$keyWordsが空なら警告を表示して終了する．
	}else{
	//全てに優先してid指定時の表示を行う。
		if($id) {
			$hitWordIds[] = $id;
			foreach ($json["words"] as $entryId => $singleEntry){
				if ($singleEntry["entry"]["id"] === $id){
					$hitEntryIds[]= $entryId;
					break 1;
				}
			}
		}else{
			//ここに検索して、内容をarrayに格納する処理を入れる。
			foreach ($json["words"] as $entryId =>$singleEntry){
				$wordId = $singleEntry["entry"]["id"];
				if ($type !== 'all' && $mode !== 'perf'){					//全文検索の場合、完全一致検索の場合は記号を削除しない
					$singleEntry["entry"]["form"] = deleteNonIdyerinCharacters($singleEntry["entry"]["form"]);
				}else{
					$singleEntry["entry"]["form"] = $singleEntry["entry"]["form"];
				}
				$wordForm = $singleEntry["entry"]["form"];
				
				////////////////ここから接辞サジェスト機能
				$deviationTable = makeDerivationTable($singleEntry, $affixTable);
				foreach ($deviationTable as $singleDeviation) {
					if ($keyWords[0] === $singleDeviation[1] && mb_stripos($singleEntry["translations"][0]["title"], $singleDeviation[0])!== false){
						echo '<p class="suggest">もしかして、';
						echo makeLinkStarter($wordForm, $_GET["type"], $_GET["mode"],1,$wordId) , $wordForm , '</a><span class=wordId>#' , $wordId , '</span>';
						echo 'の ', $singleDeviation[2] , ' ? </p>';
					}
				}
				
				//検索部
				foreach ($keyWords as $keyIndex => $singleKey ){
					$voicedSingleKey = initialVoicing($singleKey);
					
					if(isHit($singleEntry, $singleKey, $type, $mode) ||($voicing && isHit($singleEntry, $voicedSingleKey, $type, $mode))) {	//通常ヒット OR (連濁検索 AND 連濁ヒット)
						$tempHitWordIds[$keyIndex][] = $wordId;
						$tempHitEntryIds[$keyIndex][] = $entryId;
					}
				}
				unset($singleKey);
			}
			if ($keyWordsAmount === 1) {
				$hitWordIds = $tempHitWordIds[0];
				$hitEntryIds = $tempHitEntryIds[0];
			}else{
				$hitWordIds = array_intersect($tempHitWordIds[0], $tempHitWordIds[1]);
				$hitEntryIds = array_intersect($tempHitEntryIds[0], $tempHitEntryIds[1]);
				for($i = 2; $i < $keyWordsAmount; $i++){
					$hitWordIds = array_intersect($hitWordIds, $tempHitWordIds[$i]);
					$hitEntryIds = array_intersect($hitEntryIds, $tempHitEntryIds[$i]);
				}
			}
			$hitWordIds = array_merge($hitWordIds); // 歯抜けを詰めて再番号付け
			$hitEntryIds = array_merge($hitEntryIds);
		}
		
		//ここから表示部
		$hitAmount = count($hitWordIds);
		echo('<p class="result">');		
		$i = $wordNumPerPage*($page-1);
		if($hitAmount === 0){
			echo_h($_GET["keyBox"].' での検索結果：0件');
		}else{
			echo_h($_GET["keyBox"].' での検索結果：'.$hitAmount."件(".($i+1)."から".min($i+$wordNumPerPage,$hitAmount)."件目)");
		}
		echo("</p>");
	
		while ( $i < ($wordNumPerPage*$page) && $i < $hitAmount) {
		//ここに検索結果の繰り返し表示を入れる。
			echo '<ul class="wordEntry">';
			echo '<li class="wordForm"><span title="' , $json["words"][$hitEntryIds[$i]]["entry"]["form"], '">' , $json["words"][$hitEntryIds[$i]]["entry"]["form"], '</span>';
			echo '<span class="wordId">#', $hitWordIds[$i] , '</span></li>';
			
			$previousTitle = '';
			echo '<li>';
			foreach ($json["words"][$hitEntryIds[$i]]["translations"] as $index => $singleTranslation){
				if ($index === 0){
					echo '<span class="wordTitle">' , $singleTranslation["title"] , '</span>';
					echo '<ol>';
				}else{
					if ($previousTitle !== $singleTranslation["title"]) {
						echo '</ol>';
						echo '<span class="wordTitle">' , $singleTranslation["title"] , '</span>';
						echo '<ol>';
					}
				}
				$previousTitle = $singleTranslation["title"];
				echo '<li>';
				foreach ($singleTranslation["forms"] as $singleTranslationForm){
					echo $singleTranslationForm;
					if ($singleTranslationForm !== end($singleTranslation["forms"])){
						//最後のとき以外に「、」を追加
						echo '、';
					}
				}
				echo '</li>';
			}
			echo '</ol>';
			
			foreach ($json["words"][$hitEntryIds[$i]]["contents"] as $singleContent){
				echo '<li class="wordContents">';
				echo '<span class="wordContentTitle">' , $singleContent["title"] , '</span>';
				if ($singleContent["title"] !== "語源"){
				echo '</br>';
				    echo $singleContent["text"];
				}else{
					$text = '';
					$isNextLink = true;
					$singleContent["text"] = preg_split ('/([:\/*>+|])/u', $singleContent["text"], -1, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);
					foreach ($singleContent["text"] as $index => $singleContentText){
						if ($isNextLink === false){
							$isLink = false;
							$isNextLink = true;
						}else{
							$isLink = true;
						}
						//「.」を文字列に含むとき
						if (stripos($singleContentText, '.') !== false){
							$isLink = false;
						//文字列が日本語を含むとき
						}elseif (isDoublebyte($singleContentText)){
							$isLink = false;
						//文字列がデリミタで、次に影響を及ぼさないもののとき
						}elseif (preg_match ('/[:\/>+]/u', $singleContentText) === 1){
							$isLink = false;
						//文字列がデリミタで、次に影響を及ぼすもののとき
						}elseif (preg_match ('/[*|]/u', $singleContentText) === 1){
							$isLink = false;
							$isNextLink = false;
						//右端以外のとき、ひとつ右を見る
						}elseif ($index+1 < count($singleContent["text"])){
							if (preg_match ('/[:\/]/u', $singleContent["text"][$index+1]) === 1){ 
								$isLink = false;
							}
						}
						//表示生成部
						if ($isLink){
							makeLinkStarter($singleContentText,'both', 'fwd', 1);
							echo $singleContentText , '</a>';
						}else{
							$isLink = true;
							echo $singleContentText;
						}
					}
				}
				echo '</li>';
			}

			$relationTitles = array();
			foreach ($json["words"][$hitEntryIds[$i]]["relations"] as $singleRelation){
				if (array_search($singleRelation["title"],$relationTitles) === false){
					echo '<li class="wordRelation"><span class="wordRelation">' , $singleRelation["title"] , '</span>';
					$relationTitles[] = $singleRelation["title"];
				}
				$conForm =  str_replace(" ", "+", $singleRelation["entry"]["form"]);//リンク作成のため，スペースを全て+で接続した形に変換
				makeLinkStarter($conForm,$_GET["type"], $_GET["mode"],1,$singleRelation["entry"]["id"]);
				echo $singleRelation["entry"]["form"] . '</a><span class="wordId">#' , $singleRelation["entry"]["id"] , '</span>';
//				if ($singleRelation !== end($json["words"][$hitEntryIds[$i]]["relations"])){
//					//最後のとき以外に「, 」を追加
//					echo ', ';
//				}
			}
			echo '</li>';
			echo '</ul>';
			$i++;
		}
	}


	//ページ送り機能

	echo('<ul class="navigation">');
	if ($wordNumPerPage<$hitAmount) {
		$totalPages = ceil($hitAmount/$wordNumPerPage);
		$i = 1;
		$conWord =  implode ("+", $keyWords);//リンク作成のため，スペースを全て+で接続した形に変換
		while ($i <= $totalPages) {
			echo '<li';
			if ($page === $i){
				echo ' class=currentPage';
			}
			echo '>';
			if ($page !== $i){
				makeLinkStarter($conWord, $type, $mode, $i);
				echo_h($i);
				echo '</a>';
			}else{
				echo_h($i);
			}
			echo '</li>';
			$i++;
		}
	}else{
	}
	echo('</ul>');
	?>
	
	</div>
	<div id="footer">
		<p>&copy; 2010-<?php echo date('Y'); ?> Zaslon</p>
	</div>
</div>
<script src="script.js"></script>
</body>
</html>