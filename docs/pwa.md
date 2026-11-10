# ホーム画面への追加（PWA）
`header.php` がマニフェストへのリンクを、`footer.php` が `pwa.js`（Service Worker の登録）を全ページに出す。
表示は `config.php` の `app_name`・`app_theme_color`・`app_theme_color_dark`・`app_icons`・`app_icon_maskable_scale` で決まる。
アイコンは本体のサイト直下のものを使うため、辞書単体の環境では追加できない。

## アイコン
マニフェストには同じ画像を `purpose` 違いで2つ載せる（`any` と `maskable`）。`maskable` が取れないと `any` の見せ方に戻る。

`app_icon_maskable_scale` が `1.0` より大きいと、`maskable` 側だけ `icon-maskable.php` が GD でズームした版（`cache/` に保存）にする。
GD が無い・元画像を読めないときは元画像を返す（404 にすると `maskable` ごと失われるため）。倍率を上げるなら `icon-maskable.php` をサーバに置くこと。

## manifest.php・sw.php
どちらも辞書の置き場所を書く必要があるため PHP で返す。`pwa.php` の `appBasePath()` が今の URL から組み立てるので、`/dict/` でも `/webDictionary/` でも動く。

- `manifest.php`：`start_url` は検索ページ、`scope` は辞書のディレクトリ。ショートカットは `config.php` の `pages` から作る
- `sw.php`：`sw.js` に版（キャッシュ名）と取っておくファイルの一覧を付けて返す。版は `programUpdatedAt()` なので CSS・JS を更新すればキャッシュも作り直される

辞書外へのリンクはアプリの scope 外なのでブラウザで開く。

## Service Worker（sw.js）
- ページ（HTML）は通信優先。通信できなければキャッシュ、無ければ `offline.php`
- CSS・JS・書体はキャッシュ優先（`assetUrl()` の `?v=` で URL が変わる）
- 取っておくページ数の上限は `PAGE_CACHE_LIMIT`。超えたら古い順に捨てる
- ページ遷移以外で読まれたページ、`manifest.php` のような静的でないもの、`results.php`、他所のサーバ、辞書外のページには手を出さない

検索はサーバ側なので、オフラインで新しく引くことはできない。
