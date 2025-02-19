function changeFont() {
	let list = document.getElementsByClassName('wordForm');
	let font = 'inherit';
//	let size = '150%';
	if (document.getElementById('c5').checked) {
		font = 'Endrata';
//		size = '170%';
	}
	for (let i = 0; i < list.length; ++i) {
		list[i].style.fontFamily = font;
//		list[i].style.fontSize= size;
	}
}

// OSのモード設定を取得する関数
function getSystemPreference() {
	return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

// 現在のモードを設定する関数
function setMode(mode) {
	if (mode === 'dark') {
		document.body.classList.add('dark-mode');
		document.body.classList.remove('light-mode');
        document.getElementById('toggle-mode').checked = true;
	} else {
		document.body.classList.add('light-mode');
		document.body.classList.remove('dark-mode');
        document.getElementById('toggle-mode').checked = false;
	}
	localStorage.setItem('theme', mode);
}

//メイン
// 初期設定
const savedTheme = localStorage.getItem('theme');
const systemPreference = getSystemPreference();

if (savedTheme) {
	setMode(savedTheme);
} else {
	setMode(systemPreference);
}

// ボタンのイベントリスナー
if(document.getElementById('toggle-mode')){
	document.getElementById('toggle-mode').addEventListener('click', () => {
		const currentMode = document.body.classList.contains('dark-mode') ? 'dark' : 'light';
		const newMode = currentMode === 'dark' ? 'light' : 'dark';
		setMode(newMode);
		}
	)
}

if(document.getElementById('c5')){
	//チェックボックスを押したときの処理
	document.getElementById('c5').addEventListener('change', changeFont);
	//チェックボックスが押された状態で読み込まれたときの処理
	window.onload = changeFont();
}