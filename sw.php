<?php
//Service Workerを返すファイル。中身の動きはsw.jsにあり、ここでは版と対象URLを頭に付ける。
//キャッシュ名にプログラムの更新時刻を入れるため、静的なJSではなくPHPで組み立てて返している。
//Service Workerが受け持つ範囲はこのファイルの置き場所（辞書のディレクトリ）で決まる。
require_once __DIR__ . '/pwa.php';

header('Content-Type: text/javascript; charset=UTF-8');
//更新をすぐ拾えるよう、Service Worker自体はブラウザにキャッシュさせない
header('Cache-Control: no-cache');

echo serviceWorkerScript();
