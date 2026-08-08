<?php
//辞書サイトの共通設定
//zaslon-site本体（common/config.php）と対になるファイル。値を変えるときは、
//本体側と揃える必要があるもの（サイトURL・アクセス解析ID・コピーライト）は
//本体の同名の設定と一致させること。
return array(
	'site_title' => 'イジェール語 オンライン辞書',

	//本体サイトの絶対URL（メニューやOGタグの組み立てに使う。末尾スラッシュなし）
	'site_url' => 'https://zaslon.info',

	//Googleアナリティクス(GA4)の測定ID。空にすると計測タグを出力しない。
	//本体（zaslon-site）と同じプロパティで計測する。
	'ga_id' => 'G-3EQ8FM89JD',

	//計測を除外するホスト名。手元のXAMPPでの表示確認がGA4に混ざらないようにする。
	'ga_exclude_hosts' => array('localhost', '127.0.0.1', '::1'),

	//コピーライト表記（終了年は表示時に自動で今年になる）
	'copyright_start'  => 2010,
	'copyright_holder' => 'Zaslon',

	//ページ間メニューの並び順。buildPageMenu()が、今開いているページのキーと
	//一致する項目をここから取り除いて返す（自分自身へのリンクは出さないため）。
	'pages' => array(
		'dict'    => array('label' => '検索ページへ戻る', 'path' => '/dict/dict.php'),
		'example' => array('label' => '例文一覧',         'path' => '/dict/example.php'),
		'chart'   => array('label' => '単語数推移',       'path' => '/dict/chart.php'),
	),

	//常に出す固定メニュー項目。'pages'の前後に挟んで使う（buildPageMenu()参照）
	'menu_before' => array(
		'検索仕様' => '/idyerin/%e6%a4%9c%e7%b4%a2%e4%bb%95%e6%a7%98/',
		'凡例'     => '/idyerin/%e8%be%9e%e6%9b%b8%e5%87%a1%e4%be%8b/',
	),
	'menu_after' => array(
		'ホームへ戻る' => '/idyer',
	),
);
