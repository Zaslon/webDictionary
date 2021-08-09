function changeFont(){
	let list = document.getElementsByClassName('wordForm');
	let font = 'inherit';
	let size = '150%';
	if (document.getElementById('c5').checked) {
		font = 'Fazik';
		size = '170%';
	}
	for (let i = 0; i < list.length; ++i) {
		list[i].style.fontFamily= font;
		list[i].style.fontSize= size;
	}
}

document.getElementById('c5').addEventListener('change', changeFont);
window.onload = changeFont();
