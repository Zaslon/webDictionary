// Service Workerの動きの定義。ブラウザはこのファイルを直接読まない。
// 版（CACHE_VERSION）や対象URL（PRECACHE・SCOPE）はsw.phpが頭に付けて返すため、
// 開くときのURLは sw.php の方になる。
//
// 方針
// - ページ（HTML）は通信を優先する。辞書は更新され、検索もサーバ側で行うため、
//   キャッシュを返すのは通信できないときだけにする
// - CSS・JS・書体はキャッシュを優先する。版付きのURL（`?v=更新時刻`）で来るため、
//   更新されれば別のURLになり、古い物を返し続けることはない
// - それ以外（ページ遷移以外で読まれたページ、manifest.php など）には手を出さない
// - 辞書の外（本体のページ）と他所のサーバ（アクセス解析・グラフ）には手を出さない
// - 検索結果の断片（results.php）は毎回サーバに問い合わせ、控えも取らない。
//   通信できないときは livesearch.js が表示中の結果を残して注意書きを出す

// 版が変わると別の名前になるため、古いキャッシュはactivateでまとめて消える
const STATIC_CACHE = 'idyer-dict-static-' + CACHE_VERSION;
const PAGE_CACHE = 'idyer-dict-page-' + CACHE_VERSION;

// キャッシュを優先してよいファイル。版付きのURLで来る物と、書体に限る
const STATIC_PATTERN = /\.(?:css|js|woff2?|ttf|png|jpg|svg|ico)$/i;

// 取り付け時に、辞書のCSS・JSとオフライン用のページを取っておく。
// 環境によって置いていないファイルがあっても他は取れるよう、1つずつ入れて失敗は流す
self.addEventListener('install', (event) => {
	event.waitUntil((async () => {
		const cache = await caches.open(STATIC_CACHE);
		await Promise.all(PRECACHE.map((url) => {
			// ブラウザのキャッシュに残っている古い物を入れないよう、取り直す
			return cache.add(new Request(url, {cache: 'reload'})).catch(() => {});
		}));
		await self.skipWaiting();
	})());
});

self.addEventListener('activate', (event) => {
	event.waitUntil((async () => {
		const names = await caches.keys();
		await Promise.all(names
			.filter((name) => name.startsWith('idyer-dict-') && name !== STATIC_CACHE && name !== PAGE_CACHE)
			.map((name) => caches.delete(name)));
		await self.clients.claim();
	})());
});

self.addEventListener('fetch', (event) => {
	const request = event.request;
	if (request.method !== 'GET') {
		return;
	}
	const url = new URL(request.url);
	if (url.origin !== self.location.origin || !url.pathname.startsWith(SCOPE)) {
		return;// 辞書の外は普段どおりブラウザに任せる
	}
	if (url.pathname === SCOPE + 'results.php') {
		return;// 直接開いたとき（navigate）も、下の handlePage で控えを取らせない
	}
	if (request.mode === 'navigate') {
		event.respondWith(handlePage(request));
		return;
	}
	// ページ遷移以外で読まれたページ（開発者ツールがソースを表示し直すときなど）は、
	// キャッシュを優先する handleAsset に入れず、SW が無いときと同じくブラウザに任せる
	if (!STATIC_PATTERN.test(url.pathname)) {
		return;
	}
	event.respondWith(handleAsset(request));
});

// ページ。通信できたらそれを返し、控えとして取っておく
async function handlePage(request) {
	const cache = await caches.open(PAGE_CACHE);
	try {
		const response = await fetch(request);
		if (response.ok && response.type === 'basic') {
			await cache.put(request, response.clone());
			await trim(cache, PAGE_CACHE_LIMIT);
		}
		return response;
	} catch (error) {
		// 検索語ごとに別のページなので、同じURLで開いたことがある場合だけ控えを返す
		const cached = await cache.match(request);
		if (cached) {
			return cached;
		}
		const offline = await caches.match(OFFLINE_URL);
		if (offline) {
			return offline;
		}
		throw error;
	}
}

// CSS・JS・書体。取ってあればそのまま返し、無ければ取りに行って次回のために控える
async function handleAsset(request) {
	const cached = await caches.match(request);
	if (cached) {
		return cached;
	}
	const response = await fetch(request);
	if (response.ok && response.type === 'basic') {
		const cache = await caches.open(STATIC_CACHE);
		await cache.put(request, response.clone());
	}
	return response;
}

// 取っておくページが増えすぎないよう、古い物から捨てる
async function trim(cache, limit) {
	const keys = await cache.keys();
	if (keys.length <= limit) {
		return;
	}
	await Promise.all(keys.slice(0, keys.length - limit).map((key) => cache.delete(key)));
}
