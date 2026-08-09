// 発音記号を持たない単語の発音記号を、辞書データのsnoj規則から作って埋める
// 辞書データにsnoj規則があるため変換はブラウザ側で行う（サーバ側にakrantiainの実装が無い）
// 発音記号を辞書データに持つ単語はview.phpが出力済みで、ここでは触らない
(function () {
	'use strict';

	// 規則が壊れていても検索結果自体は出したいため、失敗しても発音記号を空のままにするだけにする
	function loadRules() {
		const element = document.getElementById('snojRules');
		if (!element || typeof AkrantiainLib === 'undefined') {
			return null;
		}
		try {
			return AkrantiainLib.Akrantiain.load(JSON.parse(element.textContent));
		} catch (error) {
			return null;
		}
	}

	function fillPronunciations() {
		const targets = document.querySelectorAll('.wordPronunciation[data-form]');
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

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', fillPronunciations);
	} else {
		fillPronunciations();
	}
})();
