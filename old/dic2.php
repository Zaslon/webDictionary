<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>Id'er Webdictionary</title>
</head>
<body>
<p>イジェール語 オンライン辞書 Ver1.0</p>

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

<form action="dic2.php" method="POST">
	<input type="text" name="keyBox">
	<input type="radio" name="type" value="word" <?php echo $checked_1; ?>>見出し語検索
	<input type="radio" name="type" value="trans" <?php echo $checked_2; ?>>訳語検索
	<input type="radio" name="type" value="ex" <?php echo $checked_3; ?>>用例検索
	<input type="radio" name="type" value="all" <?php echo $checked_4; ?>>全文検索
	<input type="submit" name="submit" value="検索">
</form>

<?php
	//keyBoxに入力されているときのみ，$keyWordに代入
	if (isset($_POST['keyBox'])){
		$keyWord = htmlspecialchars($_POST["keyBox"]);
	}
	if(empty($keyWord)){
		exit( "検索ワードを入力してください．" );
    }
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
//			    $ex[$hitAmount] = str_replace("【" , "<br />【" , $ex[$hitAmount]);
			    $ex[$hitAmount] = str_replace("．" , "．<br />" , $ex[$hitAmount]);
			    //最初の"【"の前には改行タグは必要ないので，各要素を最初の"【"以降のみにする
				$trans[$hitAmount] = strstr($trans[$hitAmount],"【");
				$ex[$hitAmount] = strstr($ex[$hitAmount],"【");
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
//			    $ex[$hitAmount] = str_replace("【" , "<br />【" , $ex[$hitAmount]);
			    $ex[$hitAmount] = str_replace("．" , "．<br />" , $ex[$hitAmount]);
			    //最初の"【"の前には改行タグは必要ないので，各要素を最初の"【"以降のみにする
				$trans[$hitAmount] = strstr($trans[$hitAmount],"【");
				$ex[$hitAmount] = strstr($ex[$hitAmount],"【");
			    $hitAmount ++;
			}
		}
	}
?>

<table class="dict">
	<p>検索結果：<?php echo $hitAmount; ?>件</p>
	<?php
		$i = 0;
		while($i < 20 && $i < $hitAmount) {
			echo '<tr>';
			echo '<td>' , $word[$i] , '</td>';
			echo '<td>' , $trans[$i] , '</td>';
			echo '<td>' , $ex[$i] , '</td>';
			echo '</tr>';
			$i ++;
	}
	?>
</table>

</body>
</html>