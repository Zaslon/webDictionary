<?php
//ホーム画面に追加したとき（PWA）のマニフェスト。中身はpwa.phpのappManifest()が組み立てる。
//置き場所によってscopeとstart_urlが変わるため、静的なJSONではなくPHPで返している。
require_once __DIR__ . '/pwa.php';

header('Content-Type: application/manifest+json; charset=UTF-8');
//設定を変えたときにすぐ反映されるよう、ブラウザにキャッシュさせない
header('Cache-Control: no-cache');

echo manifestJson();
