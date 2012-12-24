<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>Id'er Webdictionary</title>
</head>
<body>
<form action="dic.php" method="POST">
	<input type="text" name="keyBox">
	<input type="radio" name="type" value="trans" checked>英和
	<input type="radio" name="type" value="trans">和英
	<input type="submit" name="submit" value="検索">
</form>

<?php
	$KeyWord="none";
	$KeyWord = $_POST["keyBox"];
	$target = $_REQUEST["type"];
	if(empty($KeyWord)){
		exit( "hitしませんでした．" );
    }


?>

<table>

<?php
//データの抽出
	$data = '';
	$xml = new SimpleXMLElement('dicData.xml',0,true);

	
/* $KeyWordに一致するword要素を持つrecord要素を探し，その子要素を返す */
	if ($target='word'){
	$resultW = $xml->xpath("record[word='$KeyWord']/word");
	$resultT = $xml->xpath("record[word='$KeyWord']/trans");
	}else{
	$resultW = $xml->xpath("record[trans='$KeyWord']/word");
	$resultT = $xml->xpath("record[trans='$KeyWord']/trans");
	}

	print $target;
	print $KeyWord;
	if(empty($resultW)){
		print "ヒットしませんでした。";
		exit;
	}
	
	echo '<tr>';
	while(list( , $node) = each($resultW)) {
	    echo '<td>',$node,'</td><br />';
	}
	while(list( , $node) = each($resultT)) {
	    echo '<td>',$node,'</td><br />';
	}
	echo '</tr>';
?>
</table>

</body>
</html>