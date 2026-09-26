// 明暗の表示（dict.css のダークモード）
// zaslon.info 本体（zaslon-site の common/header.php）と同じ仕組みで、
// localStorage のキー 'theme' と <html> の data-theme 属性を共有する。
// 同じドメインに置いているので、これでサイト全体が1つの設定で動く。
// キー名（'theme'）・値（'dark' / 'light'）・属性名（data-theme）は本体と揃えてあり、変えると設定が分かれる。
// 保存済みの選択を表示前に確定させる必要があるため、head内で同期的に読み込むこと
(function () {
	const STORAGE_KEY = 'theme';
	const root = document.documentElement;
	const query = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;

	// JavaScriptが無いときだけ要る部品（検索ボタン）をCSSで隠すための目印。
	// deferの livesearch.js で隠すと、描画されたボタンが一瞬見えてから消えるため、ここで付ける
	root.classList.add('js-enabled');

	// プライベートモードなどでlocalStorageを使えなくても、そのページ内の切り替えは動かす
	function loadSetting() {
		try {
			const stored = localStorage.getItem(STORAGE_KEY);
			return (stored === 'dark' || stored === 'light') ? stored : null;
		} catch (error) {
			return null;
		}
	}

	function saveSetting(theme) {
		try {
			localStorage.setItem(STORAGE_KEY, theme);
		} catch (error) {
			// 保存できなくても続行する
		}
	}

	// 保存済みの選択を、画面を描く前に反映する
	const saved = loadSetting();
	if (saved !== null) {
		root.setAttribute('data-theme', saved);
	}

	// 今どちらで表示しているか。属性が無ければOSの設定に従っている状態
	function current() {
		const attribute = root.getAttribute('data-theme');
		if (attribute === 'dark' || attribute === 'light') {
			return attribute;
		}
		return (query && query.matches) ? 'dark' : 'light';
	}

	// ボタンの説明を今の表示に合わせる。
	// ブラウザ枠・通知バーの色（header.php の theme-color）は端末の設定に合わせてブラウザが
	// 選ぶため（media 付きで2つ出してある）、ボタンで切り替えても触らない
	function sync() {
		const now = current();
		const button = document.getElementById('theme-toggle');
		if (button) {
			const next = (now === 'dark') ? 'ライトモード' : 'ダークモード';
			button.setAttribute('aria-label', next + 'に切り替え');
			button.setAttribute('title', next + 'に切り替え');
		}
	}

	// 起動時の画面（マニフェストの theme_color・background_color）の色を端末の設定に合わせる。
	// マニフェストはページの外から読まれるため media クエリが効かず、端末の設定を URL に付けて
	// サーバへ渡す。ブラウザがマニフェストを読むのはホーム画面に追加する時点なので、
	// 読み込みが終わってから書き換えても間に合う（Chromium系は Sec-CH-Prefers-Color-Scheme
	// でも同じことを伝えており、こちらはそれを送らない iOS 向け）。
	// アプリ内のボタンで選んだ表示ではなく、端末の設定そのものを見る
	function syncManifest() {
		const link = document.querySelector('link[rel="manifest"]');
		if (!link) {
			return;
		}
		// 置き場所に依らないよう、書かれている相対パスのまま組み立て直す
		const base = link.getAttribute('href').split('?')[0];
		link.setAttribute('href', (query && query.matches) ? base + '?theme=dark' : base);
	}

	// ボタンはbody側にあるため、読み込み終わってから結びつける
	document.addEventListener('DOMContentLoaded', function () {
		syncManifest();
		const button = document.getElementById('theme-toggle');
		if (button) {
			button.hidden = false;
			button.addEventListener('click', function () {
				const next = (current() === 'dark') ? 'light' : 'dark';
				root.setAttribute('data-theme', next);
				saveSetting(next);
				sync();
			});
		}
		sync();
	});

	// まだボタンで選んでいなければ、OS側の設定変更にそのまま追従する
	if (query && query.addEventListener) {
		query.addEventListener('change', function () {
			syncManifest();// 起動時の画面の色は、ボタンで選んでいても端末の設定に合わせる
			if (!root.hasAttribute('data-theme')) {
				sync();
			}
		});
	}
})();
