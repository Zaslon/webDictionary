// OSのモード設定を取得する関数
function getSystemPreference() {
	return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

// 現在のモードを設定する関数
function setMode(mode) {
	if (mode === 'dark') {
		document.documentElement.classList.add('dark-mode');
		document.documentElement.classList.remove('light-mode');
        // document.getElementById('toggle-mode').checked = true;
	} else {
		document.documentElement.classList.add('light-mode');
		document.documentElement.classList.remove('dark-mode');
        // document.getElementById('toggle-mode').checked = false;
	}
	localStorage.setItem('theme', mode);
}

//メイン
// 初期設定
// const savedTheme = localStorage.getItem('theme');
const systemPreference = getSystemPreference();

// if (savedTheme) {
	// setMode(savedTheme);
// } else {
	setMode(systemPreference);
// }

// ボタンのイベントリスナー
// if(document.getElementById('toggle-mode')){
// 	document.getElementById('toggle-mode').addEventListener('click', () => {
// 		const currentMode = document.body.classList.contains('dark-mode') ? 'dark' : 'light';
// 		const newMode = currentMode === 'dark' ? 'light' : 'dark';
// 		setMode(newMode);
// 		}
// 	)
// }