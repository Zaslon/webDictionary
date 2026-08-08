<?php
//全ページ共通のフッタ。読み込む前に以下の変数を設定できる。
//  $pageBodyScripts : </body>直前で読み込むスクリプトのURL
$pageBodyScripts = isset($pageBodyScripts) ? $pageBodyScripts : array();
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
