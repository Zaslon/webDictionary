<?php echo '<' . '?xml version="1.0" encoding="utf-8"?' . '>'; ?>
<?php
//エスケープしてprintする関数．
function print_h($str)
{
    print htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
/* xmlファイルを読み込む */
$xml = new SimpleXMLElement("dicData.xml",0,true);
?>

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
		<p>オンライン辞書 ver:1.2.1</p>
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
	?>
	
	<form action="" method="GET">
		<input type="text" name="keyBox">
		<input type="submit" name="submit" value="検索">
		<input type="radio" name="type" value="word" <?php echo $checked_1; ?>><p>見出し語検索</p>
		<input type="radio" name="type" value="trans" <?php echo $checked_2; ?>><p>訳語検索</p>
		<input type="radio" name="type" value="ex" <?php echo $checked_3; ?>><p>用例検索</p>
		<input type="radio" name="type" value="all" <?php echo $checked_4; ?>><p>全文検索</p>
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
		//数字が入力されていたら$keyWordは空になる．
			if (preg_match("/^.*[0-9].*/", $_GET['keyBox'])) {
				print "検索ワードに数字を入力しないでください．";
			} else {
			$keyWord = $_GET["keyBox"];
			$keyWord = strtolower($keyWord);//小文字にする
			}
		}
		//$keyWordが空なら警告を表示して終了する．
		if(empty($keyWord)){
			print "検索ワードを入力してください．";
	    }else{
			//検索対象を取得
			if (!($_GET["type"]=='word' || $_GET["type"]=='trans' || $_GET["type"]=='ex' || $_GET["type"]=='all')) {
				print '検索対象指定が不正です．';
				exit();
			}else{
			    $target = $_GET["type"];
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
			}
			print('<p>');
			if (!preg_match("/^[0-9]+$/", $_GET['page'])) {
				print ('ページ指定が不正です．');
				exit();
			}else{
				$currentPageID = $_GET["page"];
				$i = (20*($currentPageID-1)+1);
				if($hitAmount==0){
					print_h($keyWord.' での検索結果：0件');
				}else{
					print_h($keyWord.' での検索結果：'.$hitAmount."件(".$i."から".($i+19)."件目)");
				}
				print("</p>");
				print('<table class=\"dict\"><tr><td>単語</td><td>訳</td><td>語法・用例等</td></tr>');
				while ( $i < (20*$currentPageID+1) && $i <= $hitAmount) {
					print('<tr><td>');
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
		if ($currentPageID!=1){
			print '<li><a href=dict.php?keyBox=';
			print_h($keyWord);
			print '&type=';
			print_h($target);
			print '&page=1>&lt;&lt;</a></li>';
		}
		while ($i <= $totalPages) {
			print '<li><a href=dict.php?keyBox=';
			print_h($keyWord);
			print '&type=';
			print_h($target);
			print '&page=';
			print_h($i);
			print '>';
			print_h($i);
			print '</a></li>';
			$i++;
		}
		if ($currentPageID!=$totalPages) {
			print '<li><a href=dict.php?keyBox=';
			print_h($keyWord);
			print '&type=';
			print_h($target);
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