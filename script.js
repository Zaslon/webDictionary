function changeFont(){
	let list = document.getElementsByClassName('wordForm');
	let font = 'inherit';
//	let size = '150%';
	if (document.getElementById('c5').checked) {
		font = 'Endrata';
//		size = '170%';
	}
	for (let i = 0; i < list.length; ++i) {
		list[i].style.fontFamily= font;
//		list[i].style.fontSize= size;
	}
}

//メイン
//チェックボックスを押したときの処理
document.getElementById('c5').addEventListener('change', changeFont);
//チェックボックスが押された状態で読み込まれたときの処理
window.onload = changeFont();
