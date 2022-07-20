google.charts.load('current', {'packages':['corechart']});
google.charts.setOnLoadCallback(drawChart);
window.onresize = drawChart;

function getCsv(url){
//CSVファイルを文字列で取得。
	let txt = new XMLHttpRequest();
	txt.open('get', url, false);
	txt.send();

//改行ごとに配列化
	let arr = txt.responseText.split('\n');

//1次元配列を2次元配列に変換
	let res = [];
	for(let i = 0; i < arr.length; i++){
		//空白行が出てきた時点で終了
		if(arr[i] == '') break;

		//","ごとに配列化
		//年月日がYYYY-MM-DDのデータになっているので"-"で分ける
		res[i] = arr[i].split(/[,-]/);
		
		for(let i2 = 0; i2 < res[i].length; i2++){
		//数字の場合は「"」を削除
			if(res[i][i2].match(/\-?\d+(.\d+)?(e[\+\-]d+)?/)){
				res[i][i2] = parseFloat(res[i][i2].replace('"', ''));
			}
		}
	}
	
	//先頭行を削除する
	res.shift();
//	res.unshift(['Year','Month','Day','Word count']);
	return res;
}

function drawChart() {
	let data = new google.visualization.DataTable();
	data.addColumn('date', '日付');
	data.addColumn('number', '単語数');
	
	let dictLogs = getCsv('dictLog.csv');
	dictLogs.forEach ((dictLog) => {
		console.log(dictLog);
		data.addRows([
			[new Date(dictLog[0],dictLog[1]-1, dictLog[2]), dictLog[3]]
		]);
	});

	let options = {
		title: '単語数推移',
		hAxis: {
			title:'日付',
			format: 'YYYY/MM'
			},
		vAxis: {title:'単語数'},
		legend: 'none'
	};

	let chart = new google.visualization.LineChart(document.getElementById('wordchart'));
	chart.draw(data, options);

}