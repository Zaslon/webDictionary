// イジェール文字表示の切り替え
// 検索ページと例文一覧ページで状態を共有するため、設定はlocalStorageに持つ
// 表示前にフォントを確定させる必要があるため、head内で同期読み込みすること
(function () {
	const STORAGE_KEY = 'idyerFont';
	const FONT_CLASS = 'idyer-font';

	// プライベートモードなどでlocalStorageを使えなくても、そのページ内の切り替えは動かす
	function loadSetting() {
		try {
			const stored = localStorage.getItem(STORAGE_KEY);
			return (stored === null) ? null : (stored === 'true');
		} catch (error) {
			return null;
		}
	}

	function saveSetting(isOn) {
		try {
			localStorage.setItem(STORAGE_KEY, isOn ? 'true' : 'false');
		} catch (error) {
			// 保存できなくても続行する
		}
	}

	function applyFont(isOn) {
		document.documentElement.classList.toggle(FONT_CLASS, isOn);
	}

	// 保存済みの設定を最優先する
	// まだ無ければ、Idf付きのURLで来た場合に限りイジェール文字とし、他のページにも引き継ぐ
	let isOn = loadSetting();
	if (isOn === null) {
		const parameter = new URLSearchParams(window.location.search).get('Idf');
		isOn = (parameter !== null && parameter !== '');
		if (isOn) {
			saveSetting(true);
		}
	}
	applyFont(isOn);

	document.addEventListener('DOMContentLoaded', function () {
		const checkBox = document.getElementById('c5');
		if (!checkBox) {
			return;
		}
		// サーバはURLしか見ていないため、保存済みの設定に合わせ直す
		checkBox.checked = isOn;
		checkBox.addEventListener('change', function () {
			applyFont(checkBox.checked);
			saveSetting(checkBox.checked);
		});
	});
})();
