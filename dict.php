<?php echo '<' . '?xml version="1.0" encoding="utf-8"?' . '>'; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="ja" xml:lang="ja" dir="ltr">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta http-equiv="Content-Script-Type" content="text/javascript" />
<meta http-equiv="Content-Style-Type" content="text/css" /> 
<meta name="Description" content="イジェール語オンライン辞書" />
<meta name="keywords" content="人工言語,辞書," />
<link rel="stylesheet" type="text/css" href="dict.css" />
<title>イジェール語 オンライン辞書</title>
</head>
<body>
<div class="all">
	<div id="header">
	
	<h1>イジェール語 オンライン辞書 </h1>
	<div id="menu">
		<a class="menu" href="http://starlightensign.com">ホームへ戻る</a>
	</div>
	<div class="dictVer">
		<p>オンライン辞書 ver:1.1.0</p>
		<?php
		$mod = filemtime("dicData.xml");
		print "<p>辞書更新日:".date("Y/m/d",$mod)."</p>";
		?>
	</div>
	<?php
	$checked_1 = "";
	$checked_2 = "";
	$checked_3 = "";
	$checked_4 = "";
	
	if((isset($_POST["type"])) && ($_POST["type"] != "")) {
		switch($_POST["type"]) {
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
		//デフォルトで見出し語検索を選択
		$checked_1 = "checked";
	}
	?>
	
	<form action="#" method="POST">
		<input type="text" name="keyBox">
		<input type="radio" name="type" value="word" <?php echo $checked_1; ?>>見出し語検索
		<input type="radio" name="type" value="trans" <?php echo $checked_2; ?>>訳語検索
		<input type="radio" name="type" value="ex" <?php echo $checked_3; ?>>用例検索
		<input type="radio" name="type" value="all" <?php echo $checked_4; ?>>全文検索
		<input type="submit" name="submit" value="検索">
	</form>
	</div>

	<div id="main">
	<?php
//keyBoxに入力されているときのみ，$keyWordに代入
		if (isset($_POST['keyBox'])){
			$keyWord = htmlspecialchars($_POST["keyBox"]);//タグやスクリプトのを不活性化
			$keyWord = strtolower($keyWord);//小文字にする
		}
		if(empty($keyWord)){
			echo "検索ワードを入力してください．";
	    }else{
//検索対象を取得
		    $target = $_POST["type"];
			$hitAmount = 0;
			$data = "";
/* xmlファイルを読み込む */
			$xml = new SimpleXMLElement("dicData.xml",0,true);
			$data_arr = $xml->record;
//$data_arrはrecordノードの集合体なので，各ループにおける$rowはword,trans,exノードからなる単語データとなる
			foreach ($data_arr as $row) {
//各配列の[-1]はあるループにおけるrecordの要素の値，つまり一単語分を抽出している
				$word[-1] =$row->word;
				$word[-1] = strtolower($word[-1]);//検索時のみ小文字化．表示に影響しない．
				$trans[-1] = $row->trans;
				$ex[-1] = $row->ex;
//allが検索対象の場合の処理
				if($target=='all') {
//全要素中に$keyWordと一致する部分が無い場合，何も返さない．
					if(strstr($word[-1],$keyWord) == false && strstr($trans[-1],$keyWord) == false && strstr($ex[-1],$keyWord) == false){
					}else{
					    $word[$hitAmount] =$row->word;
					    $trans[$hitAmount] =$row->trans;
					    $ex[$hitAmount] =$row->ex;
//訳語の"【"，ex部の”．”の前に改行タグを挿入
					    $trans[$hitAmount] = str_replace("【" , "<br />【" , $trans[$hitAmount]);
						//$ex[$hitAmount] = str_replace("【" , "<br />【" , $ex[$hitAmount]);
					    $ex[$hitAmount] = str_replace("．" , "．<br />" , $ex[$hitAmount]);
//最初の"【"の前には改行タグは必要ないので，各要素を最初の"【"以降のみにする
//もし"【"をひとつも含まない時は，この処理を行うと空白になってしまうので，それを阻止する．
						if(strstr($trans[$hitAmount],"【") == true) {$trans[$hitAmount] = strstr($trans[$hitAmount],"【");}
						if(strstr($ex[$hitAmount],"【") == true){$ex[$hitAmount] = strstr($ex[$hitAmount],"【");}
					    $hitAmount ++;
					}
				}else{
//検索対象部中に$keyWordと一致する部分が無い場合，何も返さない．
					if(strstr(${$target}[-1],$keyWord) == false){
//検索対象部中に$keyWordと一致する部分がある場合，その単語を表示用の$dataに格納する．
					}else{
					    $word[$hitAmount] =$row->word;
					    $trans[$hitAmount] =$row->trans;
					    $ex[$hitAmount] =$row->ex;
//訳語の"【"，ex部の”．”の前に改行タグを挿入
					    $trans[$hitAmount] = str_replace("【" , "<br />【" , $trans[$hitAmount]);
					    $ex[$hitAmount] = str_replace("．" , "．<br />" , $ex[$hitAmount]);
//最初の"【"の前には改行タグは必要ないので，各要素を最初の"【"以降のみにする
//もし"【"をひとつも含まない時は，この処理を行うと空白になってしまうので，それを阻止する．
						if(strstr($trans[$hitAmount],"【") == true) {$trans[$hitAmount] = strstr($trans[$hitAmount],"【");}
						if(strstr($ex[$hitAmount],"【") == true){$ex[$hitAmount] = strstr($ex[$hitAmount],"【");}
					    $hitAmount ++;
					}
				}
			}
			
			print "<p>検索結果：".$hitAmount."件(最大表示数50件)</p>";
			echo '<table class=\"dict\"><tr><td>単語</td><td>訳</td><td>語法・用例等</td></tr>';
			$i = 0;
			while($i < 50 && $i < $hitAmount) {
				echo '<tr>';
				echo '<td>' , $word[$i] , '</td>';
				echo '<td>' , $trans[$i] , '</td>';
				echo '<td>' , $ex[$i] , '</td>';
				echo '</tr>';
				$i ++;
			}
			echo "</table>";
		}
	?>
	

	
	</div>
	<div id="footer">
		<p>&copy 2012 Zaslon all rights reserved. This dictionary is powered by Zaslon.</p>
	</div>
</div>
<script type="text/javascript">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-16151470-2']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>

</body>
</html>