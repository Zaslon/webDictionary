<?php
//zaslon-site本体（common/config.php）と対になるファイル。
//本体側と揃える必要があるもの（サイトURL・アクセス解析ID・コピーライト）は、
//変えるときに本体の同名の設定とも一致させること。
return array(
	'site_title' => 'イジェール語 オンライン辞書',

	//本体側のsite_taglineと同じ役割。meta descriptionとog:descriptionに出る
	'site_tagline' => 'イジェール語の単語と例文を検索できるオンライン辞書',

	//メニューやOGタグに連結して使うため、末尾スラッシュは付けない
	'site_url' => 'https://zaslon.info',

	//SNSのカード画像。zaslon.info本体のアイコンを共用するため、site_urlからの絶対URLに直して渡す
	'og_image' => '/icon-512.png',

	//本体（zaslon-site）と同じプロパティで計測する。空にすると計測タグを出力しない
	'ga_id' => 'G-3EQ8FM89JD',

	//手元のXAMPPでの表示確認がGA4に混ざらないよう、計測から除外するホスト名
	'ga_exclude_hosts' => array('localhost', '127.0.0.1', '::1'),

	//終了年は表示時に自動で今年になる
	'copyright_start'  => 2010,
	'copyright_holder' => 'Zaslon',

	//並び順がそのままメニューの並び順になる。キーはbuildPageMenu()に渡すページの識別子
	'pages' => array(
		'dict'    => array('label' => '検索ページへ戻る', 'path' => '/dict/dict.php'),
		'example' => array('label' => '例文一覧',         'path' => '/dict/example.php'),
		'chart'   => array('label' => '単語数推移',       'path' => '/dict/chart.php'),
	),

	//どのページでも出す項目。'pages'の前後に挟まれる
	'menu_before' => array(
		'検索仕様' => '/idyerin/%e6%a4%9c%e7%b4%a2%e4%bb%95%e6%a7%98/',
		'凡例'     => '/idyerin/%e8%be%9e%e6%9b%b8%e5%87%a1%e4%be%8b/',
	),
	'menu_after' => array(
		'ホームへ戻る' => '/idyer',
	),
);
