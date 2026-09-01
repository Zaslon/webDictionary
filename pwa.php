<?php
//ホーム画面に追加したとき（PWA）の設定をまとめる。
//manifest.php（マニフェスト）とsw.php（Service Worker）が、ここで組み立てた中身を出す。
require_once __DIR__ . '/func.php';

//通信できないときのために取っておくページ数の上限。検索のたびにページが増えるため、際限なく溜めない
const PAGE_CACHE_LIMIT = 50;

//辞書を置いた場所。本体の中の`/dict/`でも、リポジトリ単体（`localhost/webDictionary/`等）でも
//同じ物が動くよう、マニフェストのscopeやキャッシュ対象は設定に書かず今開いているURLから組み立てる
//末尾は必ずスラッシュで終える（scopeとして使うため）
function appBasePath(){
	$script = isset($_SERVER['SCRIPT_NAME']) ? (string)$_SERVER['SCRIPT_NAME'] : '';
	$dir = str_replace('\\', '/', dirname($script));//Windows（XAMPP）では区切りが\になる
	$dir = rtrim($dir, '/');
	return ($dir === '' || $dir === '.') ? '/' : $dir . '/';
}

//キャッシュ名に入れる版。プログラムを更新すれば変わるため、古いキャッシュは自動で捨てられる
function appVersion(){
	return (string)programUpdatedAt();
}

//取っておく静的ファイル。増減に追従させるため、プログラムの更新日と同じくファイルを数え上げて作る
//書体は端末に入っていれば読まれない上に大きいため、ここには入れず実際に読まれたときだけ取っておく
function appAssetPaths(){
	$paths = array();
	foreach (array('/*.css', '/*.js', '/vendor/*.js') as $pattern){
		foreach (glob(__DIR__ . $pattern) as $file){
			$path = str_replace('\\', '/', substr($file, strlen(__DIR__) + 1));
			if ($path === 'sw.js'){
				continue;//sw.jsはページが読む物ではなく、sw.phpが版を付けて返す中身
			}
			$paths[] = $path;
		}
	}
	sort($paths);
	return $paths;
}

//Service Workerに渡す、取っておくファイルのURL
//ページが読むURLと同じ版付き（`?v=更新時刻`）にしないとキャッシュに当たらないため、assetUrl()を通す
function appPrecacheUrls(){
	$base = appBasePath();
	$urls = array($base . 'offline.php');
	foreach (appAssetPaths() as $path){
		$urls[] = $base . assetUrl($path);
	}
	return $urls;
}

//ホーム画面に追加したときの見た目。値はconfig.phpのapp_*から取る
function appManifest(){
	$config = dictConfig();
	$base = appBasePath();
	$startUrl = $base . 'dict.php';

	//purposeは'any'と'maskable'を両方出す。'any'は画像をそのまま四角く使う従来の見せ方、
	//'maskable'はAndroid等が丸背景に収める際に使う見せ方で、画像の内接円だけが見えるよう
	//四隅を切り落として敷き詰める（アイコン画像は元々中央に主要な絵柄を収めてあるため安全）
	$icons = array();
	foreach ($config['app_icons'] as $icon){
		foreach (array('any', 'maskable') as $purpose){
			$icons[] = array(
				'src'     => $icon['src'],
				'sizes'   => $icon['sizes'],
				'type'    => $icon['type'],
				'purpose' => $purpose,
			);
		}
	}

	//アイコンを長押しすると出るショートカット。メニューと同じくconfig.phpのpagesから作る。
	//pathは本体の中に置いた場合のパスなので、辞書だけを別の場所に置いても揃うよう、
	//ファイル名だけを取って今いる場所に付け直す
	$shortcuts = array();
	foreach ($config['pages'] as $pageKey => $page){
		if ($pageKey === 'dict'){
			continue;//起動時に開くページ自身は、ショートカットに出さない
		}
		$shortcuts[] = array(
			'name' => $page['label'],
			'url'  => $base . basename($page['path']),
		);
	}

	return array(
		//マニフェストのURLが変わってもアプリが別物にならないよう、識別子を明示する
		'id'               => $startUrl,
		'name'             => $config['site_title'],
		'short_name'       => $config['app_name'],
		'description'      => $config['site_tagline'],
		'lang'             => 'ja',
		'dir'              => 'ltr',
		'start_url'        => $startUrl,
		'scope'            => $base,
		'display'          => 'standalone',
		'theme_color'      => $config['app_theme_color'],
		'background_color' => $config['app_background_color'],
		'icons'            => $icons,
		'shortcuts'        => $shortcuts,
	);
}

//日本語とスラッシュはそのまま出す。人が読める形にしておく
function manifestJson(){
	return json_encode(appManifest(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
}

//Service Workerの中身。版と対象URLだけをPHPで作り、動きの部分はそのまま返す
function serviceWorkerScript(){
	$settings = "//以下の数行はsw.php（PHP）が組み立てる。版と対象URLは置き場所と更新時刻で変わる\n"
		. 'const CACHE_VERSION = ' . jsValue(appVersion()) . ";\n"
		. 'const SCOPE = ' . jsValue(appBasePath()) . ";\n"
		. 'const OFFLINE_URL = ' . jsValue(appBasePath() . 'offline.php') . ";\n"
		. 'const PAGE_CACHE_LIMIT = ' . PAGE_CACHE_LIMIT . ";\n"
		. 'const PRECACHE = ' . jsValue(appPrecacheUrls()) . ";\n\n";
	return $settings . file_get_contents(__DIR__ . '/sw.js');
}

//JavaScriptのリテラルとして書き出す。値がそのままコードにならないよう、記号はエスケープする
function jsValue($value){
	return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_PRETTY_PRINT);
}
