// Service Worker（sw.php）の登録。
// ホーム画面に追加したアプリで、通信できないときにも開いたことのあるページを出せるようにする。
// 登録できなくても辞書はそのまま動くため、失敗は握りつぶす。
(function () {
	if (!('serviceWorker' in navigator)) {
		return;
	}
	// httpsかlocalhostでないと登録できない。file://で開いたときなどは何もしない
	if (!window.isSecureContext) {
		return;
	}

	// 辞書はページを並べて置いているため、このスクリプトと同じ場所のsw.phpを指す
	const script = document.currentScript;
	const url = new URL('sw.php', script ? script.src : window.location.href);

	// 表示と検索結果の読み込みを待たせないよう、読み終わってから登録する
	window.addEventListener('load', function () {
		navigator.serviceWorker.register(url.href).catch(function () {
			// 登録できなくても表示には影響しない
		});
	});
})();
