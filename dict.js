// イジェール文字表示チェックボックスに合わせて見出し語と例文のフォントを切り替える
function changeFont() {
	const list = document.querySelectorAll('.wordForm, .exampleText');
	const font = document.getElementById('c5').checked ? 'Endrata' : 'inherit';
	for (let i = 0; i < list.length; ++i) {
		list[i].style.fontFamily = font;
	}
}

if (document.getElementById('c5')) {
	// チェックボックスを押したときの処理
	document.getElementById('c5').addEventListener('change', changeFont);
	// チェックボックスが押された状態で読み込まれたときの処理
	changeFont();
}
