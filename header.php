<?php
//全ページ共通のヘッダ。読み込む前に以下の変数を設定できる。
//  $pageMenu    : メニュー項目 array(ラベル => URL)。buildPageMenu()で組み立てる
//  $pageScripts : <head>で読み込む追加スクリプトのURL
//ページ固有の見出し要素を続けて置けるよう、<header>は開いたまま返す。
//読み込んだ側が</header>を閉じること。
require_once __DIR__ . '/func.php';

$config = dictConfig();
$pageMenu = isset($pageMenu) ? $pageMenu : array();
$pageScripts = isset($pageScripts) ? $pageScripts : array();
?>
<!DOCTYPE html>
<html lang="ja" dir="ltr">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width,initial-scale=1,user-scalable=yes" />
<meta name="description" content="イジェール語オンライン辞書" />
<meta name="keywords" content="人工言語,辞書" />
<meta property="og:type" content="website" />
<meta property="og:title" content="<?php echo h($config['site_title']); ?>" />
<meta property="og:description" content="イジェール語 オンライン辞書" />
<meta property="og:url" content="https://zaslon.info/dict/dict.php" />
<meta property="og:site_name" content="<?php echo h($config['site_title']); ?>" />
<meta property="og:image" content="https://zaslon.info/wordpress/wp-content/uploads/2020/08/cropped-ZaslonI-1.png" />
<meta name="twitter:card" content="summary" />
<meta name="twitter:site" content="@Zaslon" />
<?php //スマホのブラウザ枠の色。今の明暗に合わせてscript.jsが書き換える ?>
<meta name="theme-color" id="theme-color-meta" content="#E5E5E0" />
<?php $gaId = gaMeasurementId(); if ($gaId !== ''): ?>
<!-- Google tag (gtag.js)。設定はconfig.phpのga_id / ga_exclude_hosts参照。zaslon-site本体と同じプロパティ -->
<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo h($gaId); ?>"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', '<?php echo h($gaId); ?>');
</script>
<?php endif; ?>
<!-- 表示前にライト/ダークを確定させるため、head内で同期読み込みする -->
<script src="<?php echo h(assetUrl('script.js')); ?>"></script>
<?php foreach ($pageScripts as $singleScript): ?>
<script src="<?php echo h(assetUrl($singleScript)); ?>"></script>
<?php endforeach; ?>
<link rel="stylesheet" href="<?php echo h(assetUrl('dict.css')); ?>" />
<link rel="icon" href="favicon.ico" sizes="16x16 32x32 48x48" />
<link rel="icon" href="icon-512.png" type="image/png" sizes="512x512" />
<link rel="apple-touch-icon" href="apple-touch-icon.png" />
<title><?php echo h($config['site_title']); ?></title>
</head>
<body>
<div class="all">
	<header id="header">
		<h1><?php echo h($config['site_title']); ?></h1>
		<nav>
			<ul id="menu">
<?php foreach ($pageMenu as $label => $url): ?>
				<li><a class="menu" href="<?php echo h($url); ?>"><?php echo h($label); ?></a></li>
<?php endforeach; ?>
			</ul>
		</nav>
<?php
//明暗の切り替えボタン。JavaScriptが無いと動かないのでhiddenで置き、script.jsが外す。
//絵は「今と逆の表示」を示す（明るいとき＝月／暗いとき＝太陽）。
?>
		<button type="button" class="theme-toggle" id="theme-toggle" hidden>
			<svg class="icon-moon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
				<path d="M20.7 14.5A8.6 8.6 0 0 1 9.5 3.3a8.7 8.7 0 1 0 11.2 11.2z"/>
			</svg>
			<svg class="icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor"
			     stroke-width="2" stroke-linecap="round" aria-hidden="true">
				<circle cx="12" cy="12" r="4.2"/>
				<path d="M12 1.8v2.6M12 19.6v2.6M4.6 4.6l1.9 1.9M17.5 17.5l1.9 1.9M1.8 12h2.6M19.6 12h2.6M4.6 19.4l1.9-1.9M17.5 6.5l1.9-1.9"/>
			</svg>
		</button>
