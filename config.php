<?php
//zaslon-site本体（common/config.php）と対になるファイル。
//本体側と揃える必要があるもの（サイトURL・アクセス解析ID・コピーライト）は、
//変えるときに本体の同名の設定とも一致させること。
return array(
	'site_title' => 'イジェール語 オンライン辞書',

	//本体側のsite_taglineと同じ役割。meta descriptionとog:descriptionに出る
	'site_tagline' => 'イジェール語の単語と例文を検索できるオンライン辞書',

	//canonicalやOGタグに連結して使うため、末尾スラッシュは付けない
	'site_url' => 'https://zaslon.info',

	//SNSのカード画像。zaslon.info本体のアイコンを共用するため、site_urlからの絶対URLに直して渡す
	'og_image' => '/icon-512.png',

	//////ホーム画面に追加したとき（PWA）の表示。manifest.phpに出る//////

	//端末のアイコンの下に出る名前。長いと省略されるため、site_titleより短くする
	'app_name' => 'イジェール語辞書',

	//ブラウザ枠・通知バーと、起動時に一瞬出る背景の色。dict.cssの--page-bg（暗い方）と揃える。
	//アプリ内で明るい表示を選んでいても、端末がダークモードでなくても、常にこの色で固定する
	'app_theme_color' => '#131417',

	//ホーム画面のアイコン。og_imageと同じくzaslon.info本体のサイト直下の物を共用する
	//Androidでの追加には192px以上の物が要るため、512pxを載せておく
	'app_icons' => array(
		array('src' => '/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png'),
	),

	//maskable用アイコンの拡大倍率。AndroidなどはOSが安全円の外側を切り落として丸く見せるため、
	//四角用の画像をそのまま使うと絵柄が中央に小さく寄って見える。中心を軸にこの倍率まで
	//ズームしてから元の大きさに切り出す（pwa.phpのmaskableIconPng()）。
	//1.0なら加工せず元画像をそのまま渡す。1.0より大きくするときはicon-maskable.phpの設置が要る
	'app_icon_maskable_scale' => 1.0,

	//本体（zaslon-site）と同じプロパティで計測する。空にすると計測タグを出力しない
	'ga_id' => 'G-3EQ8FM89JD',

	//手元のXAMPPでの表示確認がGA4に混ざらないよう、計測から除外するホスト名
	'ga_exclude_hosts' => array('localhost', '127.0.0.1', '::1'),

	//終了年は表示時に自動で今年になる
	'copyright_start'  => 2010,
	'copyright_holder' => 'Zaslon',

	//並び順がそのままメニューの並び順になる。キーはbuildPageMenu()に渡すページの識別子
	//pathはそのままhrefに出るため、サイト内の絶対パスで書く
	'pages' => array(
		'legend'  => array('label' => '凡例',             'path' => '/dict/legend.php'),
		'dict'    => array('label' => '検索ページへ戻る', 'path' => '/dict/dict.php'),
		'example' => array('label' => '例文一覧',         'path' => '/dict/example.php'),
		'chart'   => array('label' => '単語数推移',       'path' => '/dict/chart.php'),
	),

	//どのページでも出す項目。'pages'の前後に挟まれる
	'menu_before' => array(
		'検索仕様' => '/idyerin/%e6%a4%9c%e7%b4%a2%e4%bb%95%e6%a7%98/',
	),
	'menu_after' => array(
		'ホームへ戻る' => '/idyer',
	),
);
