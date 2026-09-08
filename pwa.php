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
	//'maskable'はAndroid等が丸背景に収める際に使う見せ方で、OS側が安全円の外側を切り落として
	//丸く見せる（絵柄が大きく見えるのはこちら）。config.phpのapp_icon_maskable_scaleで
	//さらにズームする場合だけ、icon-maskable.phpが作る版に差し替える
	$icons = array();
	foreach ($config['app_icons'] as $iconIndex => $icon){
		foreach (array('any', 'maskable') as $purpose){
			$icons[] = array(
				'src'     => ($purpose === 'maskable') ? maskableIconSrc($base, $iconIndex, $icon) : $icon['src'],
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

	//アプリの枠と、起動時に一瞬出る画面の色。ここはページを開く前に使われてCSSのメディアクエリが
	//効かないため、端末のダークモード設定をmanifestTheme()で受け取って選ぶ
	$themeColor = themeColor(manifestTheme());

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
		'theme_color'      => $themeColor,
		'background_color' => $themeColor,
		'icons'            => $icons,
		'shortcuts'        => $shortcuts,
	);
}

//maskable用に渡すアイコンのURL。ズームしないなら元画像をそのまま渡す。
//icon-maskable.phpが読めない環境（設置し忘れ・GD無し・元画像が無い）で404を返すと、
//maskableのアイコンごと使われずに'any'（四角の中に小さく収まる見せ方）へ戻ってしまうため、
//生成できると分かっているときだけ差し替える
function maskableIconSrc($base, $iconIndex, array $icon){
	$config = dictConfig();
	if ((float)$config['app_icon_maskable_scale'] <= 1.0){
		return $icon['src'];
	}
	if (!function_exists('imagecreatefromstring') || !is_file(maskableIconSourceFile($icon))){
		return $icon['src'];
	}
	return $base . maskableIconUrl($iconIndex, $icon);
}

//maskable用アイコンのURL。絵柄そのものかconfig.phpの倍率設定が変わったときはブラウザが
//古いキャッシュを使い続けないよう、更新時刻を版として付ける（assetUrl()と同じ考え方）
function maskableIconUrl($iconIndex, array $icon){
	$stamp = appVersion();//pwa.php・config.php等の更新はここに含まれる
	$sourceFile = maskableIconSourceFile($icon);
	if (is_file($sourceFile)){
		$stamp = max($stamp, filemtime($sourceFile));//本体側で絵柄だけ差し替えた場合はこちらで拾う
	}
	return 'icon-maskable.php?i=' . $iconIndex . '&v=' . $stamp;
}

//アイコンのsrcはサイト直下からの絶対パス（本体の中でもリポジトリ単体でも解決できるよう
//appBasePath()と同じ考え方で書いてある）。実ファイルはDOCUMENT_ROOTを基準に読む
function maskableIconSourceFile(array $icon){
	$documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/') : '';
	return $documentRoot . $icon['src'];
}

//maskable用に絵柄を拡大したPNGを作る。生成はGDを使うため軽くはなく、絵柄も倍率も
//滅多に変わらないため、結果をキャッシュして元画像とconfig.phpが変わったときだけ作り直す
function maskableIconPng(array $icon){
	$sourceFile = maskableIconSourceFile($icon);
	if (!is_file($sourceFile)){
		return null;
	}

	$sources = array($sourceFile, __DIR__ . '/config.php');
	$cacheName = 'icon-maskable-' . md5($sourceFile);
	$cached = readCache($cacheName, $sources);
	if ($cached !== null){
		return $cached;
	}

	$config = dictConfig();
	$scale = (float)$config['app_icon_maskable_scale'];
	$png = renderMaskableIcon($sourceFile, $scale);
	if ($png !== null){
		writeCache($cacheName, $png, $sources);
	}
	return $png;
}

//画像の中心を軸に$scale倍へズームし、元と同じ大きさに切り出す。$scaleが1.0以下なら加工しない
function renderMaskableIcon($sourceFile, $scale){
	$raw = @file_get_contents($sourceFile);
	if ($raw === false){
		return null;
	}
	$source = @imagecreatefromstring($raw);
	if ($source === false){
		return null;
	}

	$width = imagesx($source);
	$height = imagesy($source);
	$dest = imagecreatetruecolor($width, $height);
	//透過部分を保ったまま切り出すため、コピー前にアルファチャンネルを用意しておく
	imagealphablending($dest, false);
	imagesavealpha($dest, true);
	imagefill($dest, 0, 0, imagecolorallocatealpha($dest, 0, 0, 0, 127));

	if ($scale <= 1.0){
		imagecopy($dest, $source, 0, 0, 0, 0, $width, $height);
	}else{
		//$scale倍に見えるよう、中央から1/$scaleの範囲だけを元の大きさへ引き伸ばして敷き詰める
		$cropWidth = $width / $scale;
		$cropHeight = $height / $scale;
		$srcX = (int)round(($width - $cropWidth) / 2);
		$srcY = (int)round(($height - $cropHeight) / 2);
		imagecopyresampled($dest, $source, 0, 0, $srcX, $srcY, $width, $height, (int)round($cropWidth), (int)round($cropHeight));
	}
	imagedestroy($source);

	ob_start();
	imagepng($dest);
	imagedestroy($dest);
	return ob_get_clean();
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
