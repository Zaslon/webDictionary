<?php echo '<' . '?xml version="1.0" encoding="utf-8"?' . '>'; ?>
<?php
//エスケープしてprintする関数
function print_h($str)
{
    print htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

//前方一致検索
function startsWith($haystack, $needle){
    return strpos($haystack, $needle, 0) === 0;
}

//完全一致検索
function perfectHit($haystack, $needle){
    return $haystack == $needle;
}

/* xmlファイルを読み込む */
$xml = new SimpleXMLElement("dicData.xml",0,true);
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="ja" xml:lang="ja" dir="ltr">
<head>
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,minimum-scale=1.0,user-scalable=no" />
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
		<li><a class="menu" href="http://starlightensign.com/idyer">ホームへ戻る</a></li>
	</ul>
	<div class="dictVer">
		<p>オンライン辞書 ver:1.3.02</p>
		<?php
		date_default_timezone_set('Asia/Tokyo');
		$mod = filemtime("dicData.xml");
		print "<p>辞書更新日:".date("Y/m/d",$mod)."<br />";
		print "単語数：".$xml->count()."</p>";
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
			case "ex":
			$checked_3 = "checked";
			break;
			case "all":
			$checked_4 = "checked";
			break;
		}
	}else{
		//デフォルトで訳語検索を選択
		$checked_2 = "checked";
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
			$func = "strstr";
			break;
			case "fwd":
			$checked_7 = "checked";
			$func = "startsWith";
			break;
			case "perf":
			$checked_8 = "checked";
			$func = "perfectHit";
			break;
		}
	}else{
		//デフォルトで部分一致を選択
		$checked_6 = "checked";
	}
	?>
	
	<form action="" method="GET">
		<input type="text" name="keyBox">
		<input type="submit" name="submit" value="検索">
		<input type="radio" name="type" id="c1" value="word" <?php echo $checked_1; ?>><label for="c1">見出し語検索</label>
		<input type="radio" name="type" id="c2" value="trans" <?php echo $checked_2; ?>><label for="c2">訳語検索</label>
		<input type="radio" name="type" id="c3" value="ex" <?php echo $checked_3; ?>><label for="c3">用例検索</label>
		<input type="radio" name="type" id="c4" value="all" <?php echo $checked_4; ?>><label for="c4">全文検索</label>
		<input type="checkbox" name="Idf" id="c5" value="true" <?php echo $checked_5; ?>><label for="c5">イジェール文字表示</label>
		<input type="radio" name="mode" id="c6" value="prt" <?php echo $checked_6; ?>><label for="c6">部分一致</label>
		<input type="radio" name="mode" id="c7" value="fwd" <?php echo $checked_7; ?>><label for="c7">前方一致</label>
		<input type="radio" name="mode" id="c8" value="perf" <?php echo $checked_8; ?>><label for="c8">完全一致</label>
		<input type="hidden" name="page" value="1">
	</form>
	</div>

	<div id="main">
	<?php
	$target = "";
	$hitAmount = 0;
	$data = "";
	$keyWord = "";
	$totalPages = 0;
	//keyBoxに入力されているときのみ，$keyWordに代入
		if (isset($_GET['keyBox'])){
		//数字が一部にでも含まれていたら$keyWordは空になる．
			if (preg_match("/^.*[0-9].*/", $_GET['keyBox'])) {
				print "<p>検索ワードに数字を入力しないでください．数字を検索する場合は漢数字で入力してください．</p>";
			} else {
				$keyWord = preg_replace('/[　]/u', ' ', $_GET["keyBox"]);//全角スペースを半角スペースに変換
				$keyWord = preg_replace('/\s\s+/u', ' ', $keyWord);//スペース2つ以上であれば，1つに削減
				$keyWord = explode(' ',$keyWord);//スペースで区切られた検索語を分離して配列に格納
				$i = 0;
				foreach ($keyWord as $eachKey) {
					$keyWord[$i] = mb_strtolower($keyWord[$i],'UTF-8');//検索の便宜のため小文字にする
					$i++;
				}
			}
		}
		//ここから検索部
		//$keyWordが空なら警告を表示して終了する．
		if(empty($keyWord[0])){
			print "<p>検索ワードを入力してください．</p>";
	    }else{
			//検索対象を取得
			if (!($_GET["type"]=='word' || $_GET["type"]=='trans' || $_GET["type"]=='ex' || $_GET["type"]=='all')) {
				print '<p>検索対象指定が不正です．</p>';
				exit();
			}else{
			    $target = $_GET["type"];
				$data_arr = $xml->record;
				//$data_arrはrecordノードの集合体なので，各ループにおける$rowはword,trans,exノードからなる単語データとなる
				foreach ($data_arr as $row) {
					//各配列の[-1]はあるループにおけるrecordの要素の値，つまり一単語分を抽出している．[-1]に置いているのは，便宜上．
					$word[-1] =$row->word;
					$trans[-1] = $row->trans;
					$ex[-1] = $row->ex;
					$word[-1] = strtolower($word[-1]);//検索時のみ小文字化．表示に影響しない．
					$trans[-1] = strtolower($trans[-1]);//検索時のみ小文字化．表示に影響しない．
					$ex[-1] = strtolower($ex[-1]);//検索時のみ小文字化．表示に影響しない．
					$isHit = 1;
					//allが検索対象の場合の処理
					if($target=='all') {
						//全要素中に$keyWordと一致する部分が無い場合，何も返さない．
						foreach ($keyWord as $eachKey) {
							//すべての検索語にヒットする場合のみisHitが1になる
							if($func($word[-1],$eachKey) == false && $func($trans[-1],$eachKey) == false && $func($ex[-1],$eachKey) == false){
								$isHit = 0;
							}else{
							}
						}
						if($isHit == 1) {
							$word[$hitAmount] =$row->word;
						    $trans[$hitAmount] =$row->trans;
						    $ex[$hitAmount] =$row->ex;
							//訳語の"【"，ex部の”．”の前に改行タグを挿入
						    $trans[$hitAmount] = str_replace("【" , "<br />【" , $trans[$hitAmount]);
						    $ex[$hitAmount] = str_replace("．" , "．<br />" , $ex[$hitAmount]);
						    $ex[$hitAmount] = str_replace("{" , "<span class=\"etymology\">{" , $ex[$hitAmount]);
						    $ex[$hitAmount] = str_replace("}" , "}</span>" , $ex[$hitAmount]);
							//最初の"【"の前には改行タグは必要ないので，各要素を最初の"【"以降のみにする
							//もし"【"をひとつも含まない時は，この処理を行うと空白になってしまうので，それを阻止する．
							if(strstr($trans[$hitAmount],"【") == true) {$trans[$hitAmount] = strstr($trans[$hitAmount],"【");}
							if(strstr($ex[$hitAmount],"【") == true){$ex[$hitAmount] = strstr($ex[$hitAmount],"【");}
							$hitAmount ++;
						}
					}else{
						//検索対象部中に$keyWordと一致する部分が無い場合，何も返さない．
						foreach ($keyWord as $eachKey) {
							//すべての検索語にヒットする場合のみisHitが1になる
							if($func(${$target}[-1],$eachKey) == false){
								$isHit = 0;
							}else{
							}
						}
						if($isHit == 1) {
							$word[$hitAmount] =$row->word;
						    $trans[$hitAmount] =$row->trans;
						    $ex[$hitAmount] =$row->ex;
							//訳語の"【"，ex部の”．”の前に改行タグを挿入
						    $trans[$hitAmount] = str_replace("【" , "<br />【" , $trans[$hitAmount]);
						    $ex[$hitAmount] = str_replace("．" , "．<br />" , $ex[$hitAmount]);
						    $ex[$hitAmount] = str_replace("{" , "<span class=\"etymology\">{" , $ex[$hitAmount]);
						    $ex[$hitAmount] = str_replace("}" , "}</span>" , $ex[$hitAmount]);
							//最初の"【"の前には改行タグは必要ないので，各要素を最初の"【"以降のみにする
							//もし"【"をひとつも含まない時は，この処理を行うと空白になってしまうので，それを阻止する．
							if(strstr($trans[$hitAmount],"【") == true) {$trans[$hitAmount] = strstr($trans[$hitAmount],"【");}
							if(strstr($ex[$hitAmount],"【") == true){$ex[$hitAmount] = strstr($ex[$hitAmount],"【");}
							$hitAmount ++;
						}
					}
				}
			}			
			//ここから表示部
			print('<p>');
			if (!preg_match("/^[0-9]+$/", $_GET['page'])) {
				print ('<p>ページ指定が不正です．</p>');
				exit();
			}else{
				$currentPageID = $_GET["page"];
				$i = (20*($currentPageID-1)+1);
				if($hitAmount==0){
					print_h($_GET["keyBox"].' での検索結果：0件');
				}else{
					print_h($_GET["keyBox"].' での検索結果：'.$hitAmount."件(".$i."から".($i+19)."件目)");
				}
				print("</p>");
				print('<table class=\"dict\"><tr><td>単語</td><td>訳</td><td>語法・用例等</td></tr>');
				while ( $i < (20*$currentPageID+1) && $i <= $hitAmount) {
					if((isset($_GET["Idf"])) && ($_GET["Idf"] != "")) {
						print('<tr><td class="Idf">');
					}else{
						print('<tr><td>');
					}
					print_h($word[($i-1)]);
					print('</td><td>');
					//<br />が含まれるためエスケープしない．
					print($trans[($i-1)]);
					print('</td><td>');
					//<br />が含まれるためエスケープしない．
					print($ex[($i-1)]);
					print('</td></tr>');
					$i ++;
				}
				print('</table>');
			}
		}
	print('<ul class="navigation">');
	if (20<$hitAmount) {
		$totalPages = ceil($hitAmount/20);
		$i = 1;
		$conWord =  implode ("+", $keyWord);//リンク作成のため，検索語を全て+で接続した形に変換
		$mode = $_GET["mode"];
		if ($currentPageID!=1){
			print '<li><a href=dict.php?keyBox=';
			print_h($conWord);
			print '&type=';
			print_h($target);
			if((isset($_GET["Idf"])) && ($_GET["Idf"] != ""))
			{
				print '&Idf=true';
			}
			print '&mode=';
			print_h($mode);
			print '&page=1>&lt;&lt;</a></li>';
		}
		while ($i <= $totalPages) {
			print '<li><a href=dict.php?keyBox=';
			print_h($conWord);
			print '&type=';
			print_h($target);
			if((isset($_GET["Idf"])) && ($_GET["Idf"] != ""))
			{
				print '&Idf=true';
			}
			print '&mode=';
			print_h($mode);
			print '&page=';
			print_h($i);
			print '>';
			print_h($i);
			print '</a></li>';
			$i++;
		}
		if ($currentPageID!=$totalPages) {
			print '<li><a href=dict.php?keyBox=';
			print_h($conWord);
			print '&type=';
			print_h($target);
			if((isset($_GET["Idf"])) && ($_GET["Idf"] != ""))
			{
				print '&Idf=true';
			}
			print '&mode=';
			print_h($mode);
			print '&page=';
			print_h($currentPageID+1);
			print '>&gt;&gt;</a></li>';
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
<script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

  ga('create', 'UA-16151470-3', 'starlightensign.com');
  ga('require', 'displayfeatures');
  ga('send', 'pageview');

</script>
</body>
</html>