google.charts.load('current', {'packages': ['corechart']});
google.charts.setOnLoadCallback(drawChart);

let chart = null;
let chartData = null;

// 描画済みのデータを使い回して、リサイズのたびに読み込み直さないようにする
window.addEventListener('resize', () => {
	if (chart && chartData) {
		chart.draw(chartData, chartOptions());
	}
});

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

function chartOptions() {
	return {
		title: '単語数推移',
		hAxis: {
			title: '日付',
			format: 'YYYY/MM'
		},
		vAxis: {title: '単語数'},
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
