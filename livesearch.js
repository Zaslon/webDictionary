// 検索ページのインクリメンタルサーチ。入力や条件の変更に応じて、ページを遷移させずに結果の領域だけを差し替える
// 結果の断片は results.php が dict.php と同じ renderSearchResults() で組み立てるため、
// 書き換えたURLを開き直しても同じ結果になる。JavaScriptが無ければフォームのGET送信のまま動く
//
// 作り: フォーム・結果・状態表示の3つのビューは、ブラウザのイベントを dict:* のイベントに翻訳して
// 泡立てるだけで判断をしない。ルート（div.all）で受ける Mediator が状態を持ち、ビューを操作する
(function () {
	'use strict';

	const DEBOUNCE_MS = 300;
	const OFFLINE_MESSAGE = '通信できないため検索結果を更新できませんでした。';
	// func.php の normalizeType() / normalizeMode() と同じ値
	const TYPES = ['word', 'trans', 'both', 'all'];
	const MODES = ['prt', 'fwd', 'perf'];

	function emit(element, name, detail) {
		element.dispatchEvent(new CustomEvent(name, {bubbles: true, detail: detail}));
	}

	function isDigits(value) {
		return value !== null && /^[0-9]+$/.test(value);
	}

	// 並びは func.php の makeLink() と同じ。検索語が空ならクエリを付けない（サーバも案内文だけを返す）
	function buildQuery(conditions) {
		if (conditions.keyBox === '') {
			return '';
		}
		const params = new URLSearchParams();
		params.append('keyBox', conditions.keyBox);
		params.append('type', conditions.type);
		params.append('mode', conditions.mode);
		params.append('page', conditions.page);
		if (conditions.idf) {
			params.append('Idf', 'true');
		}
		if (conditions.voicing) {
			params.append('voicing', 'true');
		}
		if (conditions.id !== null) {
			params.append('id', conditions.id);
		}
		return params.toString();
	}

	// URLのクエリを、サーバが同じ結果を返すものどうしで同じ文字列になる形に揃える。
	// フォーム送信のURLは submit を含み並びも違うため、揃えないと同じ条件でも問い合わせ直してしまう。
	// リンクから来た fwd・trans・id は、今表示している条件としてそのまま残す
	function normalizeQuery(search) {
		const params = new URLSearchParams(search);
		const type = params.get('type');
		const mode = params.get('mode');
		const page = params.get('page');
		const id = params.get('id');
		return buildQuery({
			keyBox: params.get('keyBox') || '',
			type: TYPES.indexOf(type) !== -1 ? type : 'both',
			mode: MODES.indexOf(mode) !== -1 ? mode : 'prt',
			page: isDigits(page) ? String(Math.max(1, parseInt(page, 10))) : '1',
			idf: !!params.get('Idf'),
			voicing: !!params.get('voicing'),
			id: isDigits(id) ? id : null,
		});
	}

	// dict.php の $checkedType / $checkedMode / checkedAttr() と同じ規則でフォームに戻す値
	function formValuesFromQuery(search) {
		const params = new URLSearchParams(search);
		let type = params.get('type');
		let mode = params.get('mode');
		type = TYPES.indexOf(type) !== -1 ? type : 'both';
		mode = MODES.indexOf(mode) !== -1 ? mode : 'prt';
		return {
			keyBox: params.get('keyBox') || '',
			type: (type === 'trans') ? 'both' : type,
			mode: (mode === 'fwd') ? 'prt' : mode,
			voicing: !!params.get('voicing'),
		};
	}

	function SearchFormView(form) {
		const keyBox = form.elements.namedItem('keyBox');
		const typeRadios = form.querySelectorAll('input[name="type"]');
		const modeRadios = form.querySelectorAll('input[name="mode"]');
		const voicing = form.querySelector('#c9');
		const idf = form.querySelector('#c5');

		function checkedValue(radios, fallback) {
			for (let i = 0; i < radios.length; i++) {
				if (radios[i].checked) {
					return radios[i].value;
				}
			}
			return fallback;
		}

		keyBox.addEventListener('input', function (event) {
			if (event.isComposing) {
				return;// 変換の途中の文字では問い合わせない。確定は compositionend で拾う
			}
			emit(form, 'dict:input');
		});
		keyBox.addEventListener('compositionend', function () {
			emit(form, 'dict:input');
		});
		form.addEventListener('change', function (event) {
			const name = event.target.name;
			if (name === 'type' || name === 'mode' || name === 'voicing') {
				emit(form, 'dict:optionchange');
			} else if (name === 'Idf') {
				emit(form, 'dict:fontchange');
			}
		});
		form.addEventListener('submit', function (event) {
			event.preventDefault();
			emit(form, 'dict:submit');
		});

		return {
			getValues: function () {
				return {
					keyBox: keyBox.value,
					type: checkedValue(typeRadios, 'both'),
					mode: checkedValue(modeRadios, 'prt'),
					voicing: !!(voicing && voicing.checked),
					idf: !!(idf && idf.checked),
				};
			},
			// イジェール文字表示は dict.js が保存済みの設定で持つ（URLより優先する）ため、ここでは触らない
			setValues: function (values) {
				keyBox.value = values.keyBox;
				// word のように該当するラジオが無い値では、どれも選ばない（dict.php と同じ）
				typeRadios.forEach(function (radio) {
					radio.checked = (radio.value === values.type);
				});
				modeRadios.forEach(function (radio) {
					radio.checked = (radio.value === values.mode);
				});
				if (voicing) {
					voicing.checked = values.voicing;
				}
			},
		};
	}

	function ResultsView(element) {
		const dictPath = new URL('dict.php', window.location.href).pathname;

		// 新しいタブで開く操作や、検索ページ以外（例文一覧など）へのリンクは普段どおり遷移させる
		element.addEventListener('click', function (event) {
			if (event.defaultPrevented || event.button !== 0
				|| event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) {
				return;
			}
			const link = event.target.closest ? event.target.closest('a[href]') : null;
			if (!link || !element.contains(link) || link.hasAttribute('target') || link.hasAttribute('download')) {
				return;
			}
			const url = new URL(link.href, window.location.href);
			if (url.origin !== window.location.origin || url.pathname !== dictPath) {
				return;
			}
			event.preventDefault();
			emit(element, 'dict:navigate', {url: url.href});
		});

		return {
			setHtml: function (html) {
				element.innerHTML = html;
				emit(element, 'dict:resultsupdated');
			},
			setBusy: function (isBusy) {
				if (isBusy) {
					element.setAttribute('aria-busy', 'true');
				} else {
					element.removeAttribute('aria-busy');
				}
			},
		};
	}

	function StatusView(element) {
		return {
			show: function (message) {
				element.textContent = message;
				element.hidden = false;
			},
			hide: function () {
				element.hidden = true;
				element.textContent = '';
			},
		};
	}

	// historyMode:
	//   'session' … 入力のまとまり。最初の結果で1件積み、続きはその1件を書き換える
	//   'push'    … 結果の中のリンク。毎回1件積む
	//   'none'    … 戻る/進む。履歴は既に動いているので触らない
	function Mediator(root, form, results, status) {
		let timer = null;
		// 問い合わせ中の要求 {query, historyMode, url, closeSession, controller}。
		// 応答はこれと同じ要求のものだけを使い、古い要求の応答で新しい結果を上書きさせない
		let pending = null;
		let sessionOpen = false;  // 入力のまとまりが続いているか（続いていれば履歴を書き換える）
		let displayedQuery = normalizeQuery(window.location.search);

		function stopTimer() {
			if (timer !== null) {
				clearTimeout(timer);
				timer = null;
			}
		}

		function cancelPending() {
			if (pending !== null) {
				pending.controller.abort();
				pending = null;
				results.setBusy(false);
			}
		}

		function formQuery() {
			const values = form.getValues();
			return buildQuery({
				keyBox: values.keyBox,
				type: values.type,
				mode: values.mode,
				page: '1',
				idf: values.idf,
				voicing: values.voicing,
				id: null,
			});
		}

		function urlForQuery(query) {
			return window.location.pathname + (query ? '?' + query : '');
		}

		function writeHistory(request) {
			if (request.historyMode === 'push') {
				history.pushState(null, '', request.url);
			} else if (request.historyMode === 'session') {
				if (sessionOpen) {
					history.replaceState(null, '', urlForQuery(request.query));
				} else {
					history.pushState(null, '', urlForQuery(request.query));
					sessionOpen = true;
				}
			}
			if (request.closeSession) {
				sessionOpen = false;
			}
		}

		function request(query, historyMode, options) {
			const url = (options && options.url) || null;
			const closeSession = !!(options && options.closeSession);
			if (historyMode !== 'none') {
				if (query === displayedQuery) {
					// 問い合わせ中に元の条件へ戻したときに、古い条件の結果で上書きさせない
					cancelPending();
					status.hide();
					if (closeSession) {
						sessionOpen = false;
					}
					return;
				}
				if (pending !== null && pending.query === query && pending.historyMode === historyMode) {
					pending.closeSession = pending.closeSession || closeSession;
					return;
				}
			}

			cancelPending();
			const current = {
				query: query,
				historyMode: historyMode,
				url: url,
				closeSession: closeSession,
				controller: new AbortController(),
			};
			pending = current;
			results.setBusy(true);

			const endpoint = new URL('results.php', window.location.href).href + (query ? '?' + query : '');
			fetch(endpoint, {signal: current.controller.signal, credentials: 'same-origin'})
				.then(function (response) {
					if (!response.ok) {
						throw new Error('HTTP ' + response.status);
					}
					return response.text();
				})
				.then(function (html) {
					// 中断の前に本文を受け取り終えていることがあるため、中断されたかも見る
					if (pending !== current) {
						return;
					}
					pending = null;
					results.setBusy(false);
					results.setHtml(html);
					status.hide();
					writeHistory(current);
					displayedQuery = current.query;
					if (historyMode !== 'session') {
						window.scrollTo(0, 0);
					}
				})
				.catch(function () {
					if (pending !== current) {
						return;// 次の要求に取って代わられて中断したもの
					}
					pending = null;
					results.setBusy(false);
					if (historyMode === 'push') {
						// 通常の遷移に任せれば、Service Worker の控えか offline.php が出る
						window.location.href = current.url;
						return;
					}
					status.show(OFFLINE_MESSAGE);
				});
		}

		function restoreForm(search) {
			form.setValues(formValuesFromQuery(search));
		}

		root.addEventListener('dict:input', function () {
			stopTimer();
			timer = setTimeout(function () {
				timer = null;
				request(formQuery(), 'session');
			}, DEBOUNCE_MS);
		});
		root.addEventListener('dict:optionchange', function () {
			stopTimer();
			request(formQuery(), 'session');
		});
		root.addEventListener('dict:submit', function () {
			stopTimer();
			request(formQuery(), 'session', {closeSession: true});
		});
		// 書体の切り替えは dict.js が済ませるので、結果は取り直さずURLの Idf だけを合わせる
		root.addEventListener('dict:fontchange', function () {
			if (displayedQuery === '') {
				return;
			}
			const params = new URLSearchParams(displayedQuery);
			if (form.getValues().idf) {
				params.set('Idf', 'true');
			} else {
				params.delete('Idf');
			}
			const query = normalizeQuery(params.toString());
			if (query === displayedQuery) {
				return;
			}
			displayedQuery = query;
			history.replaceState(null, '', urlForQuery(query));
		});
		root.addEventListener('dict:navigate', function (event) {
			stopTimer();
			sessionOpen = false;
			const url = new URL(event.detail.url);
			restoreForm(url.search);
			request(normalizeQuery(url.search), 'push', {url: url.pathname + url.search});
		});
		window.addEventListener('popstate', function () {
			stopTimer();
			sessionOpen = false;
			restoreForm(window.location.search);
			request(normalizeQuery(window.location.search), 'none');
		});
	}

	function start() {
		const root = document.querySelector('div.all');
		const formElement = document.getElementById('searchForm');
		const resultsElement = document.getElementById('results');
		const statusElement = document.getElementById('searchStatus');
		if (!root || !formElement || !resultsElement || !statusElement
			|| typeof window.fetch !== 'function' || typeof window.AbortController !== 'function'
			|| !window.history || typeof window.history.pushState !== 'function') {
			return;// 足りないものがあればフォーム送信のまま使う
		}
		Mediator(root, SearchFormView(formElement), ResultsView(resultsElement), StatusView(statusElement));
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', start);
	} else {
		start();
	}
})();
