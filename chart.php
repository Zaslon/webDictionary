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
	
	//ソート
	uasort($json["words"] , "HKSCmpw");
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
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script type="text/javascript" src="wordchart.js"></script>
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
			<li><a class="menu" href="https://zaslon.info/dict/dict.php">検索ページへ戻る</a></li>
			<li><a class="menu" href="https://zaslon.info/idyer">ホームへ戻る</a></li>
		</ul>
	</div>
	<div id="main">
		<div id="wordchart"></div>
	</div>
	<div id="footer">
		<p>&copy; 2010-<?php echo date('Y'); ?> Zaslon</p>
	</div>
</div>
<script src="script.js"></script>
</body>
</html>