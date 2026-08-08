// 明暗の表示（dict.css のダークモード）
// zaslon.info 本体（zaslon-site の common/header.php）と同じ仕組みで、
// localStorage のキー 'theme' と <html> の data-theme 属性を共有する。
// 同じドメインに置いているので、これでサイト全体が1つの設定で動く。
// キー名（'theme'）・値（'dark' / 'light'）・属性名（data-theme）は本体と揃えてあり、変えると設定が分かれる。
// 保存済みの選択を表示前に確定させる必要があるため、head内で同期的に読み込むこと
(function () {
	const STORAGE_KEY = 'theme';
	const BG = {light: '#E5E5E0', dark: '#131417'};//dict.css の --page-bg と揃える
	const root = document.documentElement;
	const query = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;

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

	// ボタンの説明とスマホのブラウザ枠の色を、今の表示に合わせる
	function sync() {
		const now = current();
		const meta = document.getElementById('theme-color-meta');
		if (meta) {
			meta.setAttribute('content', BG[now]);
		}
		const button = document.getElementById('theme-toggle');
		if (button) {
			const next = (now === 'dark') ? 'ライトモード' : 'ダークモード';
			button.setAttribute('aria-label', next + 'に切り替え');
			button.setAttribute('title', next + 'に切り替え');
		}
	}

	// ボタンはbody側にあるため、読み込み終わってから結びつける
	document.addEventListener('DOMContentLoaded', function () {
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
			if (!root.hasAttribute('data-theme')) {
				sync();
			}
		});
	}
})();
