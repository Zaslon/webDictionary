// 全ページ共通の操作（明暗の切り替え、ドロワー、起動時の画面の色）。
// zaslon.info 本体（zaslon-site の js/site.js）と同じ動きにしてある。
// 描画前に済ませる必要のあるもの（保存済みの明暗の反映・js-enabled）は header.php のインラインスクリプトが行う。
// header.php で defer 読み込みするので、ここが動く時点で DOM はできている。
(function () {
	const root = document.documentElement;
	// 768 は ZasDesignSystem の tokens.md の breakpoint-split。header.php と dict.css の値もそろえること。
	const narrowQuery = window.matchMedia('(max-width: 767.98px)');
	const darkQuery = window.matchMedia('(prefers-color-scheme: dark)');

	/* ---------- 明暗 ---------- */

	// 本体と同じキー。同じドメインに置いているので、サイト全体で1つの設定になる。
	// 'theme' は旧版のキーで、選び直したら消す（header.php は残っていれば読む）
	const STORAGE_KEY = 'sw-theme';

	function currentTheme() {
		const set = root.getAttribute('data-theme');
		return (set === 'dark' || set === 'light') ? set : (darkQuery.matches ? 'dark' : 'light');
	}

	function syncThemeColor() {
		const color = getComputedStyle(root).getPropertyValue('--sw-color-background').trim();
		document.querySelectorAll('meta[name="theme-color"]').forEach(function (meta) {
			meta.setAttribute('content', color);
		});
	}

	const themeButton = document.querySelector('[data-js-theme-toggle]');

	// ボタンには押した結果（今と逆の明暗）を出す。
	// 絵は <svg> で、SVGElement には hidden プロパティが無いため、属性を直接付け外しする。
	function syncThemeButton() {
		if (!themeButton) {
			return;
		}
		const next = (currentTheme() === 'dark') ? 'light' : 'dark';
		themeButton.querySelectorAll('[data-js-theme-next]').forEach(function (element) {
			element.toggleAttribute('hidden', element.getAttribute('data-js-theme-next') !== next);
		});
	}

	if (themeButton) {
		themeButton.hidden = false;
		themeButton.addEventListener('click', function () {
			const next = (currentTheme() === 'dark') ? 'light' : 'dark';
			root.setAttribute('data-theme', next);
			try {
				localStorage.setItem(STORAGE_KEY, next);
				localStorage.removeItem('theme');
			} catch (error) {
				// プライベートモードなどで保存できなくても、そのページ内の切り替えは効かせる
			}
		});
	}

	// data-theme をどこから書き換えても、ボタンとブラウザ枠の色を追従させる
	new MutationObserver(function () {
		syncThemeColor();
		syncThemeButton();
	}).observe(root, {attributes: true, attributeFilter: ['data-theme', 'data-contrast']});
	// theme-color は両方のメタタグを同じ値にしているため、OS の設定の変化は media では反映されない
	darkQuery.addEventListener('change', function () {
		syncThemeColor();
		syncThemeButton();
		syncManifest();
	});
	syncThemeColor();
	syncThemeButton();

	/* ---------- 起動時の画面の色（マニフェスト） ---------- */

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
		link.setAttribute('href', darkQuery.matches ? base + '?theme=dark' : base);
	}
	syncManifest();

	/* ---------- メニュー（水平メニュー／ドロワー） ---------- */

	const header = document.getElementById('site-header');
	const drawer = document.getElementById('site-drawer');
	const menuButton = document.querySelector('[data-js-drawer-open]');

	// 幅が足りない、または項目が 1 行に収まらないときはドロワーに切り替える。
	// 収まるかを測るには水平メニューを出しておく必要があるため、一度 bar にしてから測る。
	// showModal の無い古いブラウザではドロワーを開けないので、JavaScript が無いときと同じく
	// data-nav を外して水平メニュー（はみ出す分は横スクロール）だけにする
	const canUseDrawer = !!(drawer && menuButton && typeof drawer.showModal === 'function');

	function updateNavMode() {
		if (!header) {
			return;
		}
		if (!canUseDrawer) {
			header.removeAttribute('data-nav');
			return;
		}
		const list = header.querySelector('.dads-horizontal-menu');
		let mode = 'drawer';
		if (!narrowQuery.matches && list) {
			header.setAttribute('data-nav', 'bar');
			mode = (list.scrollWidth > list.clientWidth) ? 'drawer' : 'bar';
		}
		header.setAttribute('data-nav', mode);
		if (mode === 'bar' && drawer && drawer.open) {
			drawer.close();
		}
	}

	if (canUseDrawer) {
		menuButton.hidden = false;
		menuButton.addEventListener('click', function () {
			drawer.showModal();
		});
		const closeButton = drawer.querySelector('[data-js-drawer-close]');
		if (closeButton) {
			closeButton.addEventListener('click', function () {
				drawer.close();
			});
		}
		// ドロワーの外（背面の幕）を押したら閉じる。幕への押下は dialog 自身に届く。
		drawer.addEventListener('click', function (event) {
			if (event.target === drawer) {
				drawer.close();
			}
		});
		drawer.addEventListener('close', function () {
			if (header && header.getAttribute('data-nav') === 'drawer') {
				menuButton.focus();
			}
		});
	}

	// ResizeObserver は使わない。測るために data-nav を書き換えると自身の大きさが変わり、通知が循環する。
	narrowQuery.addEventListener('change', updateNavMode);
	window.addEventListener('resize', updateNavMode);
	if (document.fonts && document.fonts.ready) {
		document.fonts.ready.then(updateNavMode);
	}
	updateNavMode();
})();
