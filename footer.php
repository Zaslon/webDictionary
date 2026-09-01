<?php
//全ページ共通のフッタ。読み込む前に以下の変数を設定できる。
//  $pageBodyScripts : </body>直前で読み込むスクリプトのURL
$pageBodyScripts = isset($pageBodyScripts) ? $pageBodyScripts : array();
$pageBodyScripts[] = 'pwa.js';//ホーム画面に追加したときのため、どのページからでもService Workerを登録する
?>
	<footer id="footer">
		<p><?php echo h(copyrightText()); ?></p>
	</footer>
</div>
<?php foreach ($pageBodyScripts as $singleScript): ?>
<script src="<?php echo h(assetUrl($singleScript)); ?>"></script>
<?php endforeach; ?>
</body>
</html>
