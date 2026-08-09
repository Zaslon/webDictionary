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

// dict.cssの色をそのまま使う。CSSの色を変えればグラフも一緒に変わる
function themeColor(name, fallback) {
	const value = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
	return (value === '') ? fallback : value;
}

function chartOptions() {
	const paper = themeColor('--paper', '#F6F6F8');
	const text = themeColor('--text', '#111');
	const muted = themeColor('--muted', '#777');
	const rule = themeColor('--rule-light', '#ddd');
	const link = themeColor('--link', '#1a5fb4');
	const axis = {
		titleTextStyle: {color: muted},
		textStyle: {color: muted},
		gridlines: {color: rule},
		baselineColor: rule
	};

	return {
		title: '単語数推移',
		backgroundColor: paper,
		titleTextStyle: {color: text},
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
