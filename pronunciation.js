// 発音記号を持たない単語の発音記号を、辞書データのsnoj規則から作って埋める
// 辞書データにsnoj規則があるため変換はブラウザ側で行う（サーバ側にakrantiainの実装が無い）
// 発音記号を辞書データに持つ単語はview.phpが出力済みで、ここでは触らない
// livesearch.js が結果を差し替えたときは dict:resultsupdated で知らされ、差し替えた範囲だけを埋める
(function () {
	'use strict';

	// インクリメンタルサーチでは打つたびに結果が入れ替わるため、規則の文字列が同じ間は読み込んだ結果を使い回す。
	// 規則の要素も差し替えで入れ替わるので、要素ではなく中身の文字列で比べる
	let loadedSource = null;
	let loadedAkrantiain = null;

	// 規則が壊れていても検索結果自体は出したいため、失敗しても発音記号を空のままにするだけにする
	function loadRules() {
		const element = document.getElementById('snojRules');
		if (!element || typeof AkrantiainLib === 'undefined') {
			return null;
		}
		const source = element.textContent;
		if (source === loadedSource) {
			return loadedAkrantiain;
		}
		let akrantiain;
		try {
			akrantiain = AkrantiainLib.Akrantiain.load(JSON.parse(source));
		} catch (error) {
			akrantiain = null;
		}
		loadedSource = source;
		loadedAkrantiain = akrantiain;
		return akrantiain;
	}

	function fillPronunciations(root) {
		const targets = root.querySelectorAll('.wordPronunciation[data-form]');
		if (targets.length === 0) {
			return;
		}
		const akrantiain = loadRules();
		if (akrantiain === null) {
			return;
		}
		targets.forEach(function (target) {
			const form = target.getAttribute('data-form');
			let pronunciation;
			try {
				pronunciation = akrantiain.convert(form);
			} catch (error) {
				return;// 規則のどれにも当たらない語は、発音記号を出さない
			}
			if (pronunciation !== '') {
				target.textContent = '/' + pronunciation + '/';
			}
		});
	}

	document.addEventListener('dict:resultsupdated', function (event) {
		fillPronunciations(event.target);
	});

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function () {
			fillPronunciations(document);
		});
	} else {
		fillPronunciations(document);
	}
})();
