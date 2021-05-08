<?php echo '<' . '?xml version="1.0" encoding="utf-8"?' . '>'; ?>
<?php
require 'func.php';

//変化型テーブル読み込み
$fname = 'affixTable.csv';
$affixTable = new SplFileObject($fname);
$affixTable -> setFlags(SplFileObject::READ_CSV); //[0]対象品詞、[1]形態、[2]説明のcsv

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
		<li><a class="menu" href="https://zaslon.info/idyerin/%e8%be%9e%e6%9b%b8%e5%87%a1%e4%be%8b/">凡例</a></li>
		<li><a class="menu" href="https://zaslon.info/idyer">ホームへ戻る</a></li>
	</ul>
	<div class="dictVer">
		<?php
		date_default_timezone_set('Asia/Tokyo');
		print "<p>プログラム更新日：".date("Y/m/d",filemtime(__FILE__))."</p>";
		print "<p>辞書更新日：".date("Y/m/d",filemtime($fname))."<br />";
		print "単語数：".count($json["words"])."</p>";
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
	
	if((isset($_GET["type"])) && ($_GET["type"] != "")) {
		switch($_GET["type"]) {
			case "word":
				$checked_1 = "checked";
				break;
			case "trans":
				$checked_2 = "checked";
				break;
			case "both":
				$checked_3 = "checked";
				break;
			case "all":
				$checked_4 = "checked";
				break;
			default:
				$checked_3 = "checked";
				break;
		}
	}else{
		//デフォルトで両方検索を選択
		$checked_3 = "checked";
	}
	
	if((isset($_GET["Idf"])) && ($_GET["Idf"] != "")) {
		$checked_5 = "checked";
	}else{
		//デフォルトで空欄
	}
	
	if((isset($_GET["mode"])) && ($_GET["mode"] != "")) {
		switch($_GET["mode"]) {
			case "prt":
				$checked_6 = "checked";
				$func = "stripos";
				break;
			case "fwd":
				// $checked_7 = "checked"; 本来はこの表記だが、前方一致モードで検索された次の検索時は部分一致を選択するようにする
				$checked_6 = "checked";
				$func = "startsWith";
				break;
			case "perf":
				$checked_8 = "checked";
				$func = "perfectHit";
				break;
			default:
				$checked_6 = "checked";
				$func = "stripos";
				break;
		}
	}else{
		//デフォルトで部分一致を選択
		$checked_6 = "checked";
	}
	?>
	
	<form action="" method="GET"><input type="text" name="keyBox"><input type="submit" name="submit" value="検索">
<!--		<div class='buttonAndLabel'><input type="radio" name="type" id="c1" value="word" <?php echo $checked_1; ?>><label for="c1">見出し語検索</label></div> -->
<!--		<div class='buttonAndLabel'><input type="radio" name="type" id="c2" value="trans" <?php echo $checked_2; ?>><label for="c2">訳語検索</label></div> -->
		<div class='buttonAndLabel'><input type="radio" name="type" id="c3" value="both" <?php echo $checked_3; ?>><label for="c3">見出し語・訳語検索</label></div>
		<div class='buttonAndLabel'><input type="radio" name="type" id="c4" value="all" <?php echo $checked_4; ?>><label for="c4">全文検索</label></div>
		<div class='buttonAndLabel'><input type="checkbox" name="Idf" id="c5" value="true" <?php echo $checked_5; ?>><label for="c5">イジェール文字表示</label></div>
		<div class='buttonAndLabel'><input type="radio" name="mode" id="c6" value="prt" <?php echo $checked_6; ?>><label for="c6">部分一致</label></div>
<!--		<div class='buttonAndLabel'><input type="radio" name="mode" id="c7" value="fwd" <?php echo $checked_7; ?>><label for="c7">前方一致</label></div> -->
		<div class='buttonAndLabel'><input type="radio" name="mode" id="c8" value="perf" <?php echo $checked_8; ?>><label for="c8">完全一致</label></div>
		<input type="hidden" name="page" value="1">
	</form>
	</div>

	<div id="main">
	<?php
	$target = "";	//タイプ指定
	$hitWordIds = array();
	$hitEntryIds = array();
	$hitAmount =0;
	$keyWords = "";
	$totalPages = 0;
	$wordNumPerPage = 40;
	//keyBoxに入力されているときのみ，$keyWordsに代入
	if (isset($_GET['keyBox'])){
	//数字が一部にでも含まれていたら$keyWordsは空になる．
		if (preg_match("/^.*[0-9].*/", $_GET['keyBox'])) {
			print "<p>検索ワードに数字を入力しないでください。数字を検索する場合は漢数字で入力してください。</p>";
		} else {
			$keyWords = preg_replace('/[　]/u', ' ', $_GET["keyBox"]);//全角スペースを半角スペースに変換
			$keyWords = preg_replace('/\s\s+/u', ' ', $keyWords);//スペース2つ以上であれば，1つに削減
			$keyWords = deleteNonIdyerinCharacters($keyWords);
			$keyWords = explode(' ',$keyWords);//スペースで区切られた検索語を分離して配列に格納
			$i = 0;
		}
	}

	//ここから検索部。検索の結果を格納する。
	if(empty($keyWords[0])){
		print "<p>検索ワードを入力してください。</p>";//$keyWordsが空なら警告を表示して終了する．
    }else{
    	//全てに優先してid指定時の表示を行う。
		if((isset($_GET["id"])) && ($_GET["id"] != "")) {
			$hitWordIds[] = $_GET["id"];
			foreach ($json["words"] as $entryId => $singleEntry){
				if ($singleEntry["entry"]["id"] == $_GET["id"]){
					$hitEntryIds[]= $entryId;
					break 1;
				}
			}
		}else{
	    	//検索対象を取得
			if (!($_GET["type"]=='word' || $_GET["type"]=='trans' || $_GET["type"]=='both' || $_GET["type"]=='all')) {
				$_SET["type"] = 'both';
			}
			if (!($_GET["mode"]=='fwd' || $_GET["mode"]=='prt' || $_GET["mode"]=='perf')) {
				$_SET["mode"] = 'prt';
			}
			//ここに検索して、内容をarrayに格納する処理を入れる。
		    $target = $_GET["type"];
			foreach ($json["words"] as $entryId =>$singleEntry){
				$wordId = $singleEntry["entry"]["id"];
				$singleEntry["entry"]["form"] = deleteNonIdyerinCharacters($singleEntry["entry"]["form"]);
				$wordForm = $singleEntry["entry"]["form"];
				$isHit= 0;	//いずれかの検索語にヒットする場合にisHitが1になる
				
				////////////////ここから接辞サジェスト機能
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
				foreach ($affixTable as $singleAffix){
					
					$singleAffixWithoutBracket = preg_replace('/\(.*?\)/u', '', $singleAffix[1]); //カッコつき接辞のカッコ内をカッコごとなくした形
					if (preg_match('/(?<=\().*?(?=\))/u',$singleAffix[1]) == 1) {
						preg_match('/(?<=\().*?(?=\))/u',$singleAffix[1], $singleAffixCharBetweenBracket);
						$singleAffixCharBetweenBracket = $singleAffixCharBetweenBracket[0]; //カッコつき接辞のカッコ内を取り出した文字列
					}else{
						$singleAffixCharBetweenBracket = '';
					} 
					$singleAffixWithBracket = preg_replace('/[\(\)]/u', '', $singleAffix[1]); //カッコつき接辞のカッコを外した形
					
					if (startsWith($singleAffix[1], "-")) { //接尾辞
						if (endsWith($wordForm, $singleAffixCharBetweenBracket)){//カッコ内の文字で終わる単語の場合
							$texts[0] = $wordFormForSuffix . substr($singleAffixWithoutBracket, 1);
						}else{
							$texts[0] = $wordFormForSuffix . substr($singleAffixWithBracket, 1);
						}
					}elseif (endsWith($singleAffix[1], "-")){ //接頭辞
						foreach ($wordFormForPreffixs as $index => $singleWordFormForPreffix){
							if (startsWith($wordForm, $singleAffixCharBetweenBracket)){//カッコ内の文字で始まる単語の場合
								$texts[$index] = substr($singleAffixWithoutBracket, 0, strlen($singleAffixWithoutBracket)-1) . initialVoicing($singleWordFormForPreffix);
							}else{
								$texts[$index] = substr($singleAffixWithBracket, 0, strlen($singleAffixWithBracket)-1) . initialVoicing($singleWordFormForPreffix);
							}
						}
					}elseif (stripos($singleAffix[1], "-") !== false){
						//接周辞：今の所存在しない
					}
					foreach ($texts as $singleText) {
						if ($keyWords[0] == $singleText && mb_stripos($singleEntry["translations"][0]["title"], $singleAffix[0])!== false){
							print '<p class="suggest">もしかして、';
							print makeLinkStarter($wordForm, $_GET["type"], $_GET["mode"],1,$wordId) . $wordForm . '</a><span class=wordId>#' . $wordId . '</span>';
							print 'の '. $singleAffix[2] . ' ? </p>';
						}
					}
				}
				/////////ここまで接辞サジェスト機能
				
				//検索部
				$wordForm = $singleEntry["entry"]["form"];
				foreach ($keyWords as $eachKey){
					switch ($target){
						case "word":
							if ($func($wordForm,$eachKey) !== false){
								$isHit = 1;
								break 1;
							}
							break;
						case "trans":
							foreach ($singleEntry["translations"] as $singleTranslation){
								foreach ($singleTranslation["forms"] as $singleTranslationForm){
									if ($func(deleteSymbolsForTrans($singleTranslationForm),$eachKey) !== false){
										$isHit = 1;
										break 3;
									}
								}
							}
							break;
						case "both":
							if ($func($wordForm,$eachKey) !== false){
								$isHit = 1;
								break 1;
							}
							if ($isHit == 0){
								foreach ($singleEntry["translations"] as $singleTranslation){
									foreach ($singleTranslation["forms"] as $singleTranslationForm){
										if ($func(deleteSymbolsForTrans($singleTranslationForm),$eachKey) !== false){
											$isHit = 1;
											break 3;
										}
									}
								}
							}
							break;
						case "all":
							if ($func($wordForm,$eachKey) !== false){
								$isHit = 1;
								break 1;
							}
							if ($isHit == 0){
								foreach ($singleEntry["translations"] as $singleTranslation){
									foreach ($singleTranslation["forms"] as $singleTranslationForm){
										if ($func(deleteSymbolsForTrans($singleTranslationForm),$eachKey) !== false){
											$isHit = 1;
											break 3;
										}
									}
								}
								if ($isHit == 0){
									foreach ($singleEntry["contents"] as $singleContent){
										if ($func($singleContent["text"],$eachKey) !== false){
											$isHit = 1;
											break 2;
										}
									}
								}
							}
							break;
					}
				}
				if($isHit == 1) {
					$hitWordIds[] = $wordId;
					$hitEntryIds[]= $entryId;
				}
			}
		}
		//ここから表示部
		$currentPageID = $_GET['page'];
		$hitAmount = count($hitWordIds);
		print('<p class="result">');		
		if (!preg_match("/^[0-9]+$/", $_GET['page'])) {
			$currentPageID = 1;	//ページIDに数字以外を入力された場合、強制的に1とする。
		}
		$i = $wordNumPerPage*($currentPageID-1);
		if($hitAmount==0){
			print_h($_GET["keyBox"].' での検索結果：0件');
		}else{
			print_h($_GET["keyBox"].' での検索結果：'.$hitAmount."件(".($i+1)."から".min($i+$wordNumPerPage,$hitAmount)."件目)");
		}
		print("</p>");
	
		while ( $i < ($wordNumPerPage*$currentPageID) && $i < $hitAmount) {
		//ここに検索結果の繰り返し表示を入れる。
			print '<ul class="wordEntry">';
			if((isset($_GET["Idf"])) && ($_GET["Idf"] != "")) {
				print '<li class="wordForm"><span class="idyerin">' . $json["words"][$hitEntryIds[$i]]["entry"]["form"] . '</span>';
			}else{
				print '<li class="wordForm">' . $json["words"][$hitEntryIds[$i]]["entry"]["form"];
			}
			print '<span class="wordId">#'. $hitWordIds[$i] . '</span></li>';
			foreach ($json["words"][$hitEntryIds[$i]]["translations"] as $singleTranslation){
				print '<li><span class="wordTitle">' . $singleTranslation["title"] . '</span>';
				foreach ($singleTranslation["forms"] as $singleTranslationForm){
					print $singleTranslationForm;
					if ($singleTranslationForm !== end($singleTranslation["forms"])){
						//最後のとき以外に「、」を追加
						print '、';
					}
				}
				print '</li>';
			}
			foreach ($json["words"][$hitEntryIds[$i]]["contents"] as $singleContent){
				print '<li class="wordContents">';
				print '<span class="wordContentTitle">' . $singleContent["title"] . '</span>';
				if ($singleContent["title"] != "語源"){
				    print $singleContent["text"];
				}else{
					$text = '';
					$isNextLink = true;
					$singleContent["text"] = preg_split ('/([:\/*>+|])/u', $singleContent["text"], -1, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);
					foreach ($singleContent["text"] as $index => $singleContentText){
						if ($isNextLink == false){
							$isLink = false;
							$isNextLink = true;
						}else{
				    		$isLink = true;
				    	}
						//「.」を文字列に含むとき
						if (stripos($singleContentText, '.') != false){
							$isLink = false;
						//文字列が日本語を含むとき
						}elseif (strlen($singleContentText) != mb_strlen($singleContentText)){
							$isLink = false;
						//文字列がデリミタで、次に影響を及ぼさないもののとき
						}elseif (preg_match ('/[:\/>+]/u', $singleContentText) == 1){
							$isLink = false;
						//文字列がデリミタで、次に影響を及ぼすもののとき
						}elseif (preg_match ('/[*|]/u', $singleContentText) == 1){
							$isLink = false;
							$isNextLink = false;
						//右端以外のとき、ひとつ右を見る
						}elseif ($index+1 < count($singleContent["text"])){
							if (preg_match ('/[:\/]/u', $singleContent["text"][$index+1]) == 1){ 
								$isLink = false;
							}
						}
						//表示生成部
						if ($isLink){
							makeLinkStarter($singleContentText,'both', 'fwd', 1);
							print $singleContentText . '</a>';
						}else{
							$isLink = true;
							print $singleContentText;
						}
					}
				}
				print '</li>';
			}

			$relationTitles = array();
			foreach ($json["words"][$hitEntryIds[$i]]["relations"] as $singleRelation){
				if (array_search($singleRelation["title"],$relationTitles) === false){
					print '<li class="wordRelation"><span class="wordRelation">' . $singleRelation["title"] . '</span>';
					$relationTitles[] = $singleRelation["title"];
				}
				$conForm =  str_replace(" ", "+", $singleRelation["entry"]["form"]);//リンク作成のため，スペースを全て+で接続した形に変換
				makeLinkStarter($conForm,$_GET["type"], $_GET["mode"],1,$singleRelation["entry"]["id"]);
				print $singleRelation["entry"]["form"] . '</a><span class="wordId">#' . $singleRelation["entry"]["id"] . '</span>';
//				if ($singleRelation !== end($json["words"][$hitEntryIds[$i]]["relations"])){
//					//最後のとき以外に「, 」を追加
//					print ', ';
//				}
			}
			print '</li>';
			print '</ul>';
			$i++;
		}
	}


	//ページ送り機能

	print('<ul class="navigation">');
	if ($wordNumPerPage<$hitAmount) {
		$totalPages = ceil($hitAmount/$wordNumPerPage);
		$i = 1;
		$conWord =  implode ("+", $keyWords);//リンク作成のため，スペースを全て+で接続した形に変換
		while ($i <= $totalPages) {
			print '<li';
			if ($_GET["page"] == $i){
				print ' class=currentPage';
			}
			print '>';
			if ($_GET["page"] != $i){
				makeLinkStarter($conWord,$_GET["type"],$_GET["mode"], $i);
				print_h($i);
				print '</a>';
			}else{
				print_h($i);
			}
			print '</li>';
			$i++;
		}
	}else{
	}
	print('</ul>');
	?>
	
	</div>
	<div id="footer">
		<p>&copy; 2010-<?php echo date('Y'); ?> Zaslon</p>
	</div>
</div>
</body>
</html>