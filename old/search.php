<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>var1.php</title>
</head>
<body>
<form action="search.php" method="POST">
	<input type="text" name="key">
	<input type="radio" name="type" value="0">英和
	<input type="radio" name="type" value="1">和英
	<input type="submit" name="submit" value="検索">
</form>

<?php

$key=01234;
$KeyWord="none";

//検索ワード取得
if(isset($_POST["key"])){
	if($_POST["key"] != ""){
		$KeyWord = $_POST["key"];
		$KeyWord = htmlspecialchars($KeyWord);
//	$KeyType = $_GET["type"];
	}
}

define ("WORD_DELIMITER","///");
define ("OTHERS_DELIMITER","/");

//ファイルを開く
$fp = fopen("idier.txt", 'r') or die('ファイルが開けません');

//テーブルを出力
echo "<dl>\n";
while ($field_array = fgets($fp, 4096)) {
	if(mb_eregi($KeyWord, $field_array) or mb_eregi($KeyWord . " ", $field_array)){		//2つ目の条件はスペースを追加しEnterの影響を排除したもの．1つ目と別個に追加しないと”名詞”で[名詞]がヒットしなくなる．
		list ($word, $others) = explode (WORD_DELIMITER, $field_array , 2);				//単語そのものと他の説明を分離
		$other = explode (OTHERS_DELIMITER, $others);									//その他の要素othersを行ごとのotherに分離
		echo "<dt>" . htmlspecialchars($word, ENT_QUOTES) . "</dt>\n";
		$i=0;
		while ($i < 6){
			if( isset($other[$i]) ){
				echo "\t<dd>" . htmlspecialchars($other[$i], ENT_QUOTES) ."</dd>\n";
			}
			$i++;
		}
	}
}
echo "</dl>\n";

//ファイルを閉じる
fclose($fp);
?>
</body>
</html>