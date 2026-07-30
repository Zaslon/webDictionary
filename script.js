// OSのライト/ダーク設定に追従する
// 表示前に確定させる必要があるため、head内で同期的に読み込むこと
(function () {
	const query = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;

	function setMode(isDark) {
		const root = document.documentElement;
		root.classList.toggle('dark-mode', isDark);
		root.classList.toggle('light-mode', !isDark);
	}

	setMode(query ? query.matches : false);

	// 表示中にOSの設定が変わった場合も追従する
	if (query && query.addEventListener) {
		query.addEventListener('change', (event) => setMode(event.matches));
	}
})();
