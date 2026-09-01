<?php
//通信できないときにService Workerが出すページ。
//キャッシュから出すため、辞書データを読まず、このファイルだけで表示できるようにしてある。
require_once __DIR__ . '/func.php';

header('Content-Type: text/html; charset=UTF-8');

$pageMenu = buildPageMenu(null);
$pageNoIndex = true;//検索結果に出す物ではない
require __DIR__ . '/header.php';
?>
	</header>

	<main id="main">
		<p>オフラインのため、このページを表示できませんでした。</p>
		<p>単語の検索はサーバ側で行うため、通信できる場所で開き直してください。一度開いたページは、そのままでも表示できます。</p>
	</main>
<?php require __DIR__ . '/footer.php'; ?>
