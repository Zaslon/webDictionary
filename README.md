# webDictionary
イジェール語オンライン辞書。本番は [zaslon.info/dict/](https://zaslon.info/dict/dict.php)。
zaslon.info 本体（zaslon-site）の `dict/` に置く前提で動く。

## 使い方
### 手元での確認
`辞書チェック.bat` を開くと PHP の組み込みサーバが立ち上がり、`http://127.0.0.1:8081/dict/dict.php` が開く。止めるときは Ctrl+C。

- 辞書データ `idyer.json`（[別リポジトリ](https://github.com/Zaslon/IdyerinDictionary)）をこのフォルダに置くこと
- PHP は PATH → `C:\php` → `C:\xampp\php` の順に探す。環境変数 `PHP_EXE` で指定可。`mbstring` が無ければ `ext` から足して起動する
- 隣に `zaslon-site` があればそれをドキュメントルートにする（サイト直下のアイコンも出る）。凡例は辞書側の簡易変換で出る
- 待ち受けは `127.0.0.1:8081` のみ。本体の `ブログチェック.bat`（8080）と同時に動かせる
- `Endrata` フォントはリポジトリに無いため、イジェール文字表示は既定のフォントになる

### テスト
```
php tests/run.php
```
凡例の Markdown 変換のテストは、本体の中の `dict/` に置いたときだけ動く。

### 単語数の記録
`chart.php` のグラフの元データ `logger/dictLog.csv` に、単語数が変わっていれば「日付, 単語数」を追記する。cron で毎日1回まわす。
```
php logger/idyer_logger.php
```
`logger/` は公開ディレクトリにあるため、CLI 以外からの実行は 403 で弾く。

### キャッシュ
ソート結果を `cache/`（Git 管理外）に保存する。`idyer.json` と `func.php` の更新時刻・サイズが変われば作り直すので通常は触らなくてよい。手動で消すならディレクトリごと消してよい。

## ファイル構成
| ファイル | 役割 |
| --- | --- |
| `dict.php` | 検索ページ。パラメータの解釈と組み立てのみ |
| `results.php` | 検索結果の断片（`#results` の中身）。`livesearch.js` が使う |
| `example.php` / `legend.php` / `chart.php` | 例文一覧・凡例・単語数推移のページ |
| `offline.php` | 通信できないときに Service Worker が出すページ |
| `config.php` | サイト共通設定 |
| `func.php` | 設定読み込み、文字列処理、辞書順ソート、データ読み込み、キャッシュ |
| `search.php` | 検索と接辞サジェスト |
| `view.php` | 検索結果の HTML 出力 |
| `markdown.php` | 凡例用の簡易 Markdown 変換（本体が無い環境用） |
| `header.php` / `footer.php` | 全ページ共通のレイアウト |
| `pwa.php` / `manifest.php` / `sw.php` / `sw.js` / `pwa.js` / `icon-maskable.php` | PWA |
| `design-system.css` | ZasDesignSystem の部品とトークン。生成物なので手で編集しない |
| `dict.css` | 辞書のスタイル |
| `_css/` | `design-system.css` の生成スクリプトと生成元。サーバには送らない |
| `script.js` | 明暗切り替え、メニューのドロワー、画面の色 |
| `dict.js` | イジェール文字表示の切り替え |
| `pronunciation.js` | 発音記号の自動生成 |
| `livesearch.js` | インクリメンタルサーチ |
| `wordchart.js` | 単語数推移のグラフ |
| `affixTable.csv` | 接辞テーブル（[docs/search.md](docs/search.md)） |
| `vendor/` | 第三者製のスクリプト・書体と、ライセンス全文 |
| `logger/` | 単語数の記録。`dictLog.csv` の正はサーバ側 |
| `tests/run.php` | テスト |
| `辞書チェック.bat` / `_local/router.php` | 手元での確認用 |

サイトアイコン（`/favicon.ico` `/icon-512.png` `/apple-touch-icon.png`）は本体のサイト直下のものを共用する。

## 詳細
| 文書 | 内容 |
| --- | --- |
| [docs/search.md](docs/search.md) | 検索・接辞サジェスト・インクリメンタルサーチ・ページ送り・辞書順・例文 |
| [docs/pronunciation.md](docs/pronunciation.md) | 発音記号と vendor の作り直し・ライセンス |
| [docs/appearance.md](docs/appearance.md) | デザインシステム・CSS・明暗・メニュー・画面の色・イジェール文字表示 |
| [docs/site.md](docs/site.md) | 共通設定・本体との連携（アクセス解析・OGP・検索エンジン・凡例） |
| [docs/pwa.md](docs/pwa.md) | ホーム画面への追加・Service Worker |
| [docs/deploy.md](docs/deploy.md) | デプロイ |

## 参照
- [文法書](https://zaslon.info/idyer/)
- [web辞書本番環境](https://zaslon.info/dict/dict.php)
- [辞書の内容データ](https://github.com/Zaslon/IdyerinDictionary)
