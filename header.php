<?php
//全ページ共通のヘッダ。読み込む前に以下の変数を設定できる。
//  $pageMenu    : メニュー項目。buildPageMenu()で組み立てる
//  $pageHeading : ページの見出し（h1）。null ならサイト名を h1 にする
//  $pageHeadingInContent : true なら本文が自分で h1 を持つ（凡例）。サイト名も見出しも h1 にしない
//  $pageScripts : <head>で読み込む追加スクリプトのURL。描画前に効かせたいものだけ置く
//  $pageDeferredScripts : deferで読み込む追加スクリプトのURL。描画を待たせたくないもの
//  $pageNoIndex : trueにすると検索避け（noindex）を出し、canonicalは出さない
//  $pageWide    : trueにするとヘッダと本文を本体（zaslon-site）と同じ広い幅にする。グラフや幅の広い表のあるページ用
//<main>は開いたまま返す。footer.php が閉じる。
//見た目の部品は ZasDesignSystem（design-system.css）のものをマークアップごと使う。
//クラス名・data-* 属性・読み上げ用の要素を省くと部品の見た目や読み上げが崩れるので、消さないこと。
require_once __DIR__ . '/func.php';

$config = dictConfig();

//Accept-CHは端末のダークモード設定を送ってもらうための要求。これを出しておくと、このあと
//読まれるmanifest.phpのリクエストに付いてくる（起動時の画面の色に使う。iOSは送ってこない）
if (!headers_sent()){
	header('Accept-CH: Sec-CH-Prefers-Color-Scheme');
}

$pageMenu = isset($pageMenu) ? $pageMenu : array();
$pageScripts = isset($pageScripts) ? $pageScripts : array();
$pageDeferredScripts = isset($pageDeferredScripts) ? $pageDeferredScripts : array();
$pageNoIndex = isset($pageNoIndex) ? $pageNoIndex : false;
$pageHeading = isset($pageHeading) ? $pageHeading : null;
$pageHeadingInContent = isset($pageHeadingInContent) ? $pageHeadingInContent : false;
$pageWide = isset($pageWide) ? $pageWide : false;
$brandTag = ($pageHeading === null && !$pageHeadingInContent) ? 'h1' : 'p';
?>
<!DOCTYPE html>
<html lang="ja" dir="ltr">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width,initial-scale=1,user-scalable=yes" />
<meta name="description" content="<?php echo h($config['site_tagline']); ?>" />
<meta name="keywords" content="人工言語,辞書" />
<?php if ($pageNoIndex): ?>
<meta name="robots" content="noindex" />
<?php endif; ?>
<?php
//noindex のページに別のURLを指す canonical を並べると、検索エンジンに食い違った指示を出すことになるため出さない
$canonicalUrl = $pageNoIndex ? '' : canonicalUrl();
if ($canonicalUrl !== ''): ?>
<link rel="canonical" href="<?php echo h($canonicalUrl); ?>" />
<?php endif; ?>
<?php
//X・Facebook等に貼ったときのカード。zaslon-site本体（common/header.php）と同じ作りで、
//画像はサイトのアイコンを使い、正方形の小さいカード（summary）で出す。横長の大きいカード
//（summary_large_image）は2:1の画像が要るので、正方形のicon-512.pngでは左右が切られてしまう。
//画像・URLは絶対URLでないと読まれないのでabsoluteUrl()を通す。
$ogUrl = absoluteUrl();
$ogImage = absoluteUrl($config['og_image']);
?>
<meta property="og:type" content="website" />
<meta property="og:site_name" content="<?php echo h($config['site_title']); ?>" />
<meta property="og:title" content="<?php echo h($config['site_title']); ?>" />
<meta property="og:description" content="<?php echo h($config['site_tagline']); ?>" />
<?php if ($ogUrl !== ''): ?>
<meta property="og:url" content="<?php echo h($ogUrl); ?>" />
<?php endif; ?>
<?php if ($ogImage !== ''): ?>
<meta property="og:image" content="<?php echo h($ogImage); ?>" />
<meta property="og:image:type" content="image/png" />
<meta property="og:image:width" content="512" />
<meta property="og:image:height" content="512" />
<meta property="og:image:alt" content="<?php echo h($config['site_title']); ?>" />
<?php endif; ?>
<meta name="twitter:card" content="summary" />
<meta name="twitter:site" content="@Zaslon" />
<?php
//スマホのブラウザ枠と、ホーム画面に追加したとき（PWA）の通知バーの色。値は design-system.css の
//--sw-color-background と同じ（config.php の app_theme_color*）。HTMLの属性はCSS変数を参照できないため直接書く。
//スクリプトが動けば、下のインラインスクリプトと script.js が今の表示の色で両方を上書きする。
//mediaを見ない古いブラウザは最初の1つだけを使うため、明るい方を先に置く
?>
<meta name="color-scheme" content="light dark" />
<meta name="theme-color" media="(prefers-color-scheme: light)" content="<?php echo h(themeColor('light')); ?>" />
<meta name="theme-color" media="(prefers-color-scheme: dark)" content="<?php echo h(themeColor('dark')); ?>" />
<link rel="stylesheet" href="<?php echo h(assetUrl('design-system.css')); ?>" />
<link rel="stylesheet" href="<?php echo h(assetUrl('dict.css')); ?>" />
<?php
//前回選んだ明暗を、画面を描く前に <html> の data-theme に付ける。外部ファイルにすると
//読み込みを待つ間に一度 OS の設定の色で描かれ、Android のナビゲーションバーの色も食い違う。
//CSS の後に置くのは、トークン（--sw-color-background）を読むため。
//キー 'sw-theme' は本体（zaslon-site）と共有する。'theme' は旧版のキーで、選び直さなくても前の選択が残るよう読む。
//js-enabled は JavaScript が無いときだけ要る部品（検索ボタン）を CSS で隠すための目印
?>
<script>
(function () {
  var root = document.documentElement;
  var theme;
  root.classList.add('js-enabled');
  try { theme = localStorage.getItem('sw-theme') || localStorage.getItem('theme'); } catch (e) {}
  if (theme === 'light' || theme === 'dark') root.setAttribute('data-theme', theme);
  root.style.backgroundColor = 'var(--sw-color-background)';
  var color = getComputedStyle(root).getPropertyValue('--sw-color-background').trim();
  var metas = document.querySelectorAll('meta[name="theme-color"]');
  for (var i = 0; i < metas.length; i++) metas[i].setAttribute('content', color);
})();
</script>
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
<script src="<?php echo h(assetUrl('script.js')); ?>" defer></script>
<?php foreach ($pageScripts as $singleScript): ?>
<script src="<?php echo h(assetUrl($singleScript)); ?>"></script>
<?php endforeach; ?>
<?php foreach ($pageDeferredScripts as $singleScript): ?>
<script src="<?php echo h(assetUrl($singleScript)); ?>" defer></script>
<?php endforeach; ?>
<?php //アイコンはzaslon.info本体（サイト直下）の物を共用する。辞書側に同じ画像を置かない ?>
<link rel="icon" href="/favicon.ico" sizes="16x16 32x32 48x48" />
<link rel="icon" href="/icon-512.png" type="image/png" sizes="512x512" />
<link rel="apple-touch-icon" href="/apple-touch-icon.png" />
<?php
//ホーム画面に追加したとき（PWA）の設定。中身はmanifest.phpがconfig.phpのapp_*から組み立てる。
//辞書のページは同じディレクトリに並んでいるため、置き場所に依らないよう相対パスで指す。
//iOSはマニフェストのdisplayを見ないため、アプリとして開くにはapple-の指定も要る
?>
<link rel="manifest" href="manifest.php" />
<meta name="mobile-web-app-capable" content="yes" />
<meta name="apple-mobile-web-app-capable" content="yes" />
<?php
//iOSの通知バーは値を3つ（'default'＝白地に黒文字、'black'＝黒地に白文字、'black-translucent'）
//しか選べず、読み込み時に一度しか見られない（JavaScriptで後から書き換えても効かない）ため、
//端末の設定に合わせて出し分けることはできない。iOS15以降は'default'のとき上のtheme-colorが
//使われて端末の設定どおりになるため、それ以前でも文字が読める'default'を選ぶ
?>
<meta name="apple-mobile-web-app-status-bar-style" content="default" />
<meta name="apple-mobile-web-app-title" content="<?php echo h($config['app_name']); ?>" />
<title><?php echo h($config['site_title']); ?></title>
</head>
<body<?php echo $pageWide ? ' class="page-wide"' : ''; ?>>
<?php //livesearch.js がページ内の移動の知らせ（dict:navigate）をこの要素で受けるため、class="all" を消さないこと ?>
<div class="all">
<a class="skip-link" href="#main">本文へ移動</a>
<header class="site-header" id="site-header">
	<div class="site-header__bar">
<?php
//ドロワーは JavaScript（showModal）で開くので、動かない環境では出さない（script.js が hidden を外す）。
//絵と読み上げ名は本体（zaslon-site の common/header.php）と同じ。
?>
		<button class="dads-hamburger-menu-icon-button site-header__menu-button" type="button"
		        aria-haspopup="dialog" aria-controls="site-drawer" data-js-drawer-open hidden>
			<svg class="dads-hamburger-menu-icon-button__icon" width="44" height="44" viewBox="0 0 44 44" role="img" aria-label="Menu">
				<path d="M11 15h22v2H11zM11 21h22v2H11zM11 27h22v2H11z" fill="currentcolor"/>
			</svg>
		</button>
		<div class="site-brand">
			<<?php echo $brandTag; ?> class="site-title"><a href="<?php echo h($config['pages']['dict']['path']); ?>"><?php echo h($config['site_title']); ?></a></<?php echo $brandTag; ?>>
			<p class="site-tagline"><?php echo h($config['site_tagline']); ?></p>
		</div>
<?php
//押した結果（これからなる明暗）を文言と絵で示す。どちらを出すかは script.js が今の表示に合わせる。
//絵は ZasDesignSystem の icons/svg/dark_mode.svg・light_mode.svg（Material Symbols、Apache License 2.0。
//vendor/LICENSE-material-symbols.txt）の写し。
?>
		<button class="dads-button site-header__theme" type="button" data-type="text" data-size="md" data-js-theme-toggle hidden>
			<svg class="dads-button__icon" data-js-theme-next="dark" width="24" height="24" viewBox="0 -960 960 960" fill="currentcolor" aria-hidden="true">
				<path d="M481.15-140q-141.66 0-240.83-99.17-99.16-99.16-99.16-240.83 0-135.77 92.11-232.88 92.11-97.12 225.57-105.2 8.62 0 16.93.62 8.3.62 16.3 1.85-30.61 28.61-48.76 69.15-18.16 40.54-18.16 86.46 0 98.33 68.84 167.17Q562.82-424 661.15-424q46.54 0 86.77-18.15 40.23-18.16 68.46-48.77 1.23 8 1.85 16.31.61 8.3.61 16.92-7.69 133.46-104.8 225.57Q616.92-140 481.15-140Zm0-60q88 0 158-48.5t102-126.5q-20 5-40 8t-40 3q-123 0-209.5-86.5T365.15-660q0-20 3-40t8-40q-78 32-126.5 102t-48.5 158q0 116 82 198t198 82Zm-10-270Z"/>
			</svg>
			<svg class="dads-button__icon" data-js-theme-next="light" width="24" height="24" viewBox="0 -960 960 960" fill="currentcolor" aria-hidden="true" hidden>
				<path d="M480-360q50 0 85-35t35-85q0-50-35-85t-85-35q-50 0-85 35t-35 85q0 50 35 85t85 35Zm0 60q-74.92 0-127.46-52.54Q300-405.08 300-480q0-74.92 52.54-127.46Q405.08-660 480-660q74.92 0 127.46 52.54Q660-554.92 660-480q0 74.92-52.54 127.46Q554.92-300 480-300ZM200-450H50v-60h150v60Zm710 0H760v-60h150v60ZM450-760v-150h60v150h-60Zm0 710v-150h60v150h-60ZM262.92-656.92l-93.69-90.46 42.39-44.39 90.23 92.69-38.93 42.16Zm485.46 488.69-90.84-93.31 39.54-41.54 93.69 90.46-42.39 44.39Zm-91.46-528.85 90.46-93.69 44.39 42.39-92.69 90.23-42.16-38.93ZM168.23-211.62l93.31-90.84 40.77 39.54-90.08 94.07-44-42.77ZM480-480Z"/>
			</svg>
			<span class="site-header__theme-label" data-js-theme-next="dark">ダークモードにする</span>
			<span class="site-header__theme-label" data-js-theme-next="light" hidden>ライトモードにする</span>
		</button>
	</div>
	<nav class="site-nav" aria-label="メインメニュー">
		<ul class="dads-horizontal-menu">
<?php foreach ($pageMenu as $item): ?>
			<li class="dads-horizontal-menu__item">
				<a class="dads-horizontal-menu__item-inner" href="<?php echo h($item['url']); ?>"<?php echo $item['current'] ? ' aria-current="page"' : ''; ?>>
					<span class="dads-horizontal-menu__label"><?php echo h($item['label']); ?></span>
				</a>
			</li>
<?php endforeach; ?>
		</ul>
	</nav>
</header>
<?php
//水平メニューを 1 行に収められないとき（狭い画面を含む）は、同じ項目を同じ順でドロワーに並べる。
//以後の切り替えは script.js が行う。描画前に決めないと水平メニューが一瞬見えるので、ここでも判定する。
//768 は ZasDesignSystem の tokens.md の breakpoint-split。dict.css と script.js の値もそろえること。
?>
<script>
(function () {
  var header = document.getElementById('site-header');
  var list = header.querySelector('.dads-horizontal-menu');
  var narrow = window.matchMedia('(max-width: 767.98px)').matches;
  header.setAttribute('data-nav', narrow || list.scrollWidth > list.clientWidth ? 'drawer' : 'bar');
})();
</script>
<dialog id="site-drawer" class="dads-drawer" data-placement="left" aria-labelledby="site-drawer-heading">
	<h2 id="site-drawer-heading" class="dads-u-visually-hidden">メニュー</h2>
	<div class="dads-drawer__header">
		<button class="dads-hamburger-menu-button" type="button" data-js-drawer-close>
			<svg class="dads-hamburger-menu-button__icon" width="24" height="24" viewBox="0 0 120 120" aria-hidden="true">
				<path d="M32 95L25 88L53 60L25 32L32 25L60 53L88 25L95 32L67 60L95 88L88 95L60 67L32 95Z" fill="currentcolor"/>
			</svg>
			閉じる
		</button>
	</div>
	<div class="dads-drawer__body">
		<ul class="dads-menu-list">
<?php foreach ($pageMenu as $item): ?>
			<li>
				<a class="dads-menu-list__item" data-type="box" data-size="regular" href="<?php echo h($item['url']); ?>"<?php echo $item['current'] ? ' data-current aria-current="page"' : ''; ?>>
					<span class="dads-menu-list__label"><?php echo h($item['label']); ?></span>
				</a>
			</li>
<?php endforeach; ?>
		</ul>
	</div>
</dialog>
<main class="content" id="main">
<?php if ($pageHeading !== null): ?>
<header class="entry-header">
	<h1><?php echo h($pageHeading); ?></h1>
</header>
<?php endif; ?>
