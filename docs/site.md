# 本体（zaslon-site）との連携

## 共通設定（config.php）
サイト名・説明文・`site_url`・カード画像・`app_*`・アクセス解析 ID・コピーライト・ページ間メニューを集約し、`func.php` の `dictConfig()` で読む。
本体の `common/config.php` / `site_config()` に対応し、`site_url`・`ga_id`・コピーライトの表記形式は本体と一致させること。

ページ間メニューは `buildPageMenu($currentPageKey)` が組み立てる。
- 辞書内のページは `pages`、辞書外へのリンクは `menu_before` / `menu_after` に書く
- `pages` の `dict` はヘッダのサイト名のリンク先にも使う
- 今いるページは `aria-current="page"` で示す

## アクセス解析
`header.php` が `ga_id` で本体と同じ GA4 プロパティに計測タグを出す。`ga_exclude_hosts` のホストでは出さず、`ga_id` を空にすれば全体で出さない。

## OGP・正規 URL
`header.php` が本体と同じ形で OGP と `canonical` を出す。URL は `canonicalUrl()` / `absoluteUrl()` が `site_url` と今のパスから組み立てる（クエリは含めない）。
カードは `summary`、画像は `og_image`、説明文は `site_tagline`（`description` と `og:description` の両方）。

## 検索エンジン
載せるのはクエリの無い `dict.php`・`example.php`・`legend.php`・`chart.php` のみ。サイトマップは本体の `index.php` の `sitemap_dict_urls()` が作る。

- クエリ付きの `dict.php` / `example.php` は `noindex` を出し、`canonical` は出さない（`header.php` の `$pageNoIndex`）
- `results.php` は `X-Robots-Tag: noindex`
- `robots.txt` で巡回を止めない（止めると `noindex` が読まれない）

## アイコン
本体のサイト直下のものをルートからの絶対パスで指す。辞書単体で開くとアイコンは出ない（表示には影響しない）。

## 凡例（legend.php）
`idyer.json` の `legend`（Markdown）を表示する。記法は本体 README の「Markdown対応記法」参照。
（`zpdicOnline.enableMarkdown` は ZpDIC の単語欄の設定で、`legend` とは無関係）

### 変換
`view.php` の `legendMarkdownToHtml()` が本体の `common/markdown.php` を読み、`Zaslon\Markdown\to_html()` を直接呼ぶ。

- **本体の `common/lib.php` は読み込まないこと**。辞書の `func.php` と同じ `h()` を定義しており二重定義で落ちる
- 本体の `markdown.php` が呼ぶ `site_config()` は、`view.php` が `function_exists()` で確かめてから `dictConfig()` を返す同名関数として置く
- 本体の `common/markdown.php` か `vendor/autoload.php` が無い、または変換中に例外が出たら辞書側の `markdown.php` に落とす
- `legend` が無ければその旨だけを出す
- 本体は生 HTML を通すので、辞書データの HTML はそのまま出る

見た目（`dict.css` の `.legend`）は本体 `css/style.css` の記事本文と同じ規則で、色変数 `--rule` `--tint` `--code-bg` `--th-bg` `--row-alt` `--quote-border` も本体と同名・同値。

### 本体が無い環境（markdown.php）
凡例で使う記法だけの最小実装。

| 対応する | 対応しない（素の文字で出る） |
| --- | --- |
| 見出し（`#`〜`######`）・段落 | 入れ子の箇条書き・引用・脚注 |
| 箇条書き（`-` `*` `+`）・番号付き（`1.`） | 参照形式のリンク・画像 |
| 表（GFM。区切り行必須） | 下線式の見出し・セル内の `\|` |
| 行内コード・```のコードブロック | 生 HTML（必ずエスケープ） |
| 強調（`**` `*`）・取り消し線（`~~`）・区切り線 | 日本語の約物をまたぐ強調 |

段落内の改行は `<br />`。左列の記法だけなら本体と同じ HTML になる。
凡例で新しい記法を使ったときや、本体の Markdown 変換に手を入れたときは、両方の環境で凡例の表示を確かめること。
