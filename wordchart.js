google.charts.load('current', {'packages': ['corechart']});
google.charts.setOnLoadCallback(drawChart);

let chart = null;
let chartData = null;

// 描画済みのデータを使い回して、描き直しのたびに読み込み直さないようにする
function redraw() {
	if (chart && chartData) {
		chart.draw(chartData, chartOptions());
	}
}

window.addEventListener('resize', redraw);

// グラフの色はCSSではなく描画時に決まるため、明暗が変わったら描き直す
// script.jsが<html>のdata-themeを書き換えるので、それを見張る
new MutationObserver(redraw).observe(document.documentElement, {
	attributes: true,
	attributeFilter: ['data-theme']
});

// まだボタンで選んでいなければ、OS側の設定変更にも追従する
if (window.matchMedia) {
	const query = window.matchMedia('(prefers-color-scheme: dark)');
	if (query.addEventListener) {
		query.addEventListener('change', () => {
			if (!document.documentElement.hasAttribute('data-theme')) {
				redraw();
			}
		});
	}
}

// CSVを取得して[年, 月, 日, 単語数]の二次元配列にする
async function getCsv(url) {
	const response = await fetch(url);
	if (!response.ok) {
		throw new Error(`${url} の取得に失敗しました (${response.status})`);
	}

	// 改行ごとに配列化
	const lines = (await response.text()).split('\n');

	const rows = [];
	for (const line of lines) {
		// 空白行が出てきた時点で終了
		if (line.trim() === '') break;

		// ","ごとに配列化
		// 年月日がYYYY-MM-DDのデータになっているので"-"で分ける
		rows.push(line.split(/[,-]/).map((cell) => {
			const value = parseFloat(cell.replace(/"/g, ''));
			return Number.isNaN(value) ? cell : value;
		}));
	}

	// 先頭行（見出し行）を削除する
	rows.shift();
	return rows;
}

// design-system.css の役割のトークンをそのまま使う。トークンの値が変わればグラフも一緒に変わる。
// 変数名は引用符付きの文字列で書くこと。_css/build.mjs はこの形の参照を数えて、使っている変数を残す
function themeColor(name, fallback) {
	const value = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
	return (value === '') ? fallback : value;
}

// 見出しはページの h1（chart.php）にあるので、グラフの中には題を出さない
function chartOptions() {
	const background = themeColor('--sw-color-background', '#ffffff');
	const secondary = themeColor('--sw-color-text-secondary', '#666666');
	const divider = themeColor('--sw-color-divider', '#e6e6e6');
	const link = themeColor('--sw-color-link', '#1A5FB4');
	const axis = {
		titleTextStyle: {color: secondary},
		textStyle: {color: secondary},
		gridlines: {color: divider},
		baselineColor: divider
	};

	return {
		backgroundColor: background,
		colors: [link],
		hAxis: Object.assign({
			title: '日付',
			format: 'YYYY/MM'
		}, axis),
		vAxis: Object.assign({
			title: '単語数',
			viewWindow: { min: 0 }
		}, axis),
		legend: 'none'
	};
}

async function drawChart() {
	const target = document.getElementById('wordchart');

	let dictLogs;
	try {
		dictLogs = await getCsv('logger/dictLog.csv');
	} catch (error) {
		target.textContent = 'グラフのデータを読み込めませんでした。';
		console.error(error);
		return;
	}

	chartData = new google.visualization.DataTable();
	chartData.addColumn('date', '日付');
	chartData.addColumn('number', '単語数');
	dictLogs.forEach((dictLog) => {
		chartData.addRows([
			[new Date(dictLog[0], dictLog[1] - 1, dictLog[2]), dictLog[3]]
		]);
	});

	chart = new google.visualization.LineChart(target);
	chart.draw(chartData, chartOptions());
}
