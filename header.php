<?php
//全ページ共通のヘッダ。読み込む前に以下の変数を設定できる。
//  $pageMenu    : メニュー項目 array(ラベル => URL)
//  $pageScripts : <head>で読み込む追加スクリプトのURL
//ページ固有の見出し要素を続けて置けるよう、<header>は開いたまま返す。
//読み込んだ側が</header>を閉じること。
require_once __DIR__ . '/func.php';

$pageMenu = isset($pageMenu) ? $pageMenu : array();
$pageScripts = isset($pageScripts) ? $pageScripts : array();
?>
<!DOCTYPE html>
<html lang="ja" dir="ltr">
<head>
<meta charset="UTF-8" />
<?php require __DIR__ . '/anal.php'; ?>
<meta name="viewport" content="width=device-width,initial-scale=1,user-scalable=yes" />
<meta name="description" content="イジェール語オンライン辞書" />
<meta name="keywords" content="人工言語,辞書" />
<meta property="og:type" content="website" />
<meta property="og:title" content="イジェール語 オンライン辞書" />
<meta property="og:description" content="イジェール語 オンライン辞書" />
<meta property="og:url" content="https://zaslon.info/dict/dict.php" />
<meta property="og:site_name" content="イジェール語 オンライン辞書" />
<meta property="og:image" content="https://zaslon.info/wordpress/wp-content/uploads/2020/08/cropped-ZaslonI-1.png" />
<meta name="twitter:card" content="summary" />
<meta name="twitter:site" content="@Zaslon" />
<!-- 表示前にライト/ダークを確定させるため、head内で同期読み込みする -->
<script src="<?php echo h(assetUrl('script.js')); ?>"></script>
<?php foreach ($pageScripts as $singleScript): ?>
<script src="<?php echo h(assetUrl($singleScript)); ?>"></script>
<?php endforeach; ?>
<link rel="stylesheet" href="<?php echo h(assetUrl('dict.css')); ?>" />
<link rel="shortcut icon" href="favicon.ico" />
<link rel="icon" href="favicon.ico" />
<title>イジェール語 オンライン辞書</title>
</head>
<body>
<div class="all">
	<header id="header">
		<h1>イジェール語 オンライン辞書</h1>
		<nav>
			<ul id="menu">
<?php foreach ($pageMenu as $label => $url): ?>
				<li><a class="menu" href="<?php echo h($url); ?>"><?php echo h($label); ?></a></li>
<?php endforeach; ?>
			</ul>
		</nav>
