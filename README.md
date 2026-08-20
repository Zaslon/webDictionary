# webDictionary
イジェール語オンライン辞書

## このリポジトリについて
このリポジトリはイジェール語のオンライン辞書システムを管理するものです。

## ファイル構成
このオンライン辞書は`親ディレクトリ/dict/`内に置くことを想定している。

| ファイル | 役割 |
| --- | --- |
| `dict.php` | 検索ページ。パラメータの解釈と組み立てのみを行う |
| `example.php` | 例文の一覧ページ |
| `legend.php` | 凡例のページ。中身は辞書データの `legend`（Markdown）を読む |
| `chart.php` | 単語数推移のグラフページ |
| `config.php` | サイト共通設定（サイト名・説明文・サイトURL・カード画像・アクセス解析ID・コピーライト・ページ間メニュー） |
| `func.php` | 設定読み込み、文字列処理、HKS順ソート、データ読み込み、キャッシュ |
| `markdown.php` | 簡易なMarkdown変換。凡例用。本体（zaslon-site）が無い環境でだけ使う |
| `search.php` | 検索と接辞サジェスト |
| `view.php` | 検索結果のHTML出力 |
| `header.php` / `footer.php` | 全ページ共通のレイアウト |
| `dict.css` | 全ページ共通のスタイル。色は zaslon.info 本体と同じ変数名で持つ |
| `script.js` | 明暗（ライト／ダーク）の切り替え |
| `dict.js` | イジェール文字表示の切り替え |
| `pronunciation.js` | 発音記号の自動生成 |
| `vendor/akrantiain.min.js` | akrantiain（第三者製）をブラウザ用にまとめたもの |
| `vendor/DoulosSIL-Regular.woff2` | 発音記号の書体（第三者製）。配布物をそのまま置く |
| `vendor/LICENSE-*.txt` | 上記の第三者製ソフトウェア・フォントのライセンス全文 |
| `wordchart.js` | 単語数推移のグラフの描画 |
| `idyer.json` | 辞書データ（[別リポジトリ](https://github.com/Zaslon/IdyerinDictionary)で管理） |
| `affixTable.csv` | 接辞テーブル。[0]対象品詞、[1]形態、[2]説明、[3]特殊処理、[4]派生後の品詞 |
| `logger/dictLog.csv` | 単語数の記録。グラフの元データ |
| `logger/idyer_logger.php` | 単語数を記録するスクリプト。cronから定期実行する |
| `tests/run.php` | テスト |
| `../favicon.ico` `../icon-512.png` `../apple-touch-icon.png` | サイトアイコン（親サイトに設置） |

`Endrata` フォント（`*.woff` / `*.ttf`）は意図的にリポジトリに含めていないため、
クローンした環境では「イジェール文字表示」は既定のフォントで表示される。

## 接辞サジェスト（affixTable.csv）
検索語が見出し語の派生形である場合に「もしかして」を出す機能で、`search.php` が担当する。
派生の規則は [派生](https://zaslon.info/idyerin/%e6%b4%be%e7%94%9f/)・[助詞(後置詞)と格](https://zaslon.info/idyerin/%e5%8a%a9%e8%a9%9e%e5%be%8c%e7%bd%ae%e8%a9%9e/)・[記述詞](https://zaslon.info/idyerin/%e8%a8%98%e8%bf%b0%e8%a9%9e/)に従う。

イジェール語では、語幹＋`-e` が動詞、語幹＋`-(i)n` が記述詞、名詞は語幹そのもの、という形で品詞が決まる。
このため接辞の付き方は次の規則で決まり、`derivationStems()` と `makeDerivations()` がこれを実装している。

* 接尾辞は**語幹**に付く（動詞の `-e` や記述詞の `-(i)n` を置き換える形になる）。
* 接頭辞は**語形**の頭に付く。ただし品詞を変える接頭辞は、品詞を決める語尾を置き換えるため語幹に付く。
* 接辞のカッコ内の母音は、接辞が付く語幹が子音で終わる場合にだけ現れる。
* 接頭辞の後ろの要素は連濁する。`[3]` が `NO_VOICING` の接辞（`mir-`）だけは連濁しない。

`[4]` の派生後の品詞は、空欄ならその形からは更に派生しないことを表す（名詞の格がこれにあたる）。

接辞は複数重ねられるため、`findDerivationPaths()` が接辞を1段ずつ重ねながら検索語に辿り着く道筋を
`DERIVATION_MAX_DEPTH` 段まで探す（`mirkereskef` なら「`kere` の 再帰自動詞形 の 対格にあたる名詞派生 の 主格」）。
組み合わせが際限なく増えないよう、次の枝は辿らない。

* 途中の形が検索語の中に現れないもの。接辞は語の前後にしか付かないため、途中の形は必ず検索語の中に連続した部分文字列として残る
* 派生後の品詞が空の接辞。名詞の格のように、そこで語形が閉じる
* 既に見出し語として載っている形。その見出し語から直接辿った道筋と重なる

同じ見出し語について複数の解釈が見つかった場合は、最も浅い解釈だけを出す。
検索語が複数あるときは、それぞれの語について辿る。

## 共通設定（config.php）
サイト名・説明文・zaslon.info本体のURL・カード画像・アクセス解析ID・コピーライト表記・ページ間メニューは
`config.php` に集約している。値は `func.php` の `dictConfig()` で読み込んで使い回す。
zaslon-site本体の `common/config.php` / `site_config()` に対応するファイルで、
本体と揃える必要がある値（`site_url`・`ga_id`・コピーライトの表記形式）は本体側と一致させること。

ページ間メニュー（検索仕様・凡例・検索ページ・例文一覧・単語数推移・ホームへ戻る）は
`func.php` の `buildPageMenu($currentPageKey)` が `config.php` の `pages` から自分自身の項目を除いて組み立てる。
辞書の中のページは `pages` に、文法書側の記事など辞書の外へのリンクは `menu_before` / `menu_after` に書く。

## アクセス解析
`header.php` が `config.php` の `ga_id` を使って zaslon.info 本体と同じGA4プロパティに計測タグを出す
（本体の `common/header.php` / `ga_measurement_id()` と同じ仕組み）。
`ga_exclude_hosts` に載っているホスト（`localhost` 等、手元のXAMPPでの確認用）では出力されない。
`ga_id` を空にすれば全体で計測タグ自体を出さない。

## SNSカード・正規URL（OGP）
`header.php` が zaslon-site本体（`common/header.php`）と同じ形でOGPタグと `canonical` を出す。
URLは `func.php` の `canonicalUrl()` / `absoluteUrl()` が `config.php` の `site_url` と今開いているページのパスから組み立てるため、辞書のどのページを貼っても そのページのURLになる
（検索条件でカードが変わらないよう、クエリは含めない）。
カードは正方形の小さいカード（`summary`）で、画像は `config.php` の `og_image`。
説明文は `site_tagline` で、`meta name="description"` と `og:description` の両方に出る。

## アイコン
ファビコン・アイコン・カード画像は、辞書側に持たず zaslon.info 本体のサイト直下（`/favicon.ico`・
`/icon-512.png`・`/apple-touch-icon.png`）を共用する。同じドメインの `/dict/` に置く前提のため、
`header.php` からはルートからの絶対パスで指す。
辞書だけを別の場所（`localhost/webDictionary/` 等）で開くとアイコンは表示されないが、表示内容には影響しない。

## イジェール文字表示
検索ページと例文一覧ページの「イジェール文字表示」は `dict.js` が担当する。
ページをまたいで設定を共有するため、状態は `localStorage`（キー `idyerFont`）に持ち、
`<html>` に `idyer-font` クラスを付けてフォントを切り替える。
表示前にフォントを確定させるため、`dict.js` は `<head>` で同期読み込みする。

設定の優先順位は次の通り。

1. `localStorage` に保存済みの設定
2. URLの `Idf` パラメータ（保存済みの設定が無いときだけ見る。以後のページに引き継ぐため保存する）
3. どちらも無ければ既定のフォント

なお、CSSやJSを更新してもブラウザが古いものを使い続けないよう、
`func.php` の `assetUrl()` で `dict.css?v=更新時刻` の形にしている。

## 見た目（配色・書体・ダークモード）
配色も書体も zaslon.info 本体（[zaslon-site](https://zaslon.info/) の `css/style.css`）に合わせてある。

### 配色
`dict.css` の先頭で本体と**同じ変数名・同じ値**の色を定義しているので、
どちらかの色を変えたときは、もう一方の同じ名前の値も合わせること。

### 書体
本文の書体・字の大きさ・行間・狭い画面での切り替え（768px）は本体と同じ値にしてある。

| | 値 | 備考 |
| --- | --- | --- |
| 本文 | `"Noto Sans JP","Hiragino Kaku Gothic ProN","Yu Gothic",Meiryo,sans-serif` | |
| 字の大きさ | `100%`（768px以下は `108%`） | |
| 行間 | `1.85`（768px以下は `1.8`） | 一覧（`wordEntry` / `menu` / `navigation`）は本体の `.post-list` と同じ `1.6` に戻す |
| サイト名（`h1`） | `Georgia,"Times New Roman",serif` | 大きさは本体（60px）に揃えず200%のまま。和文で長く、60pxだと折り返すため |

イジェール文字の `Endrata` と発音記号の `Doulos SIL` だけは辞書固有なので、上の指定より優先される。

### ダークモード
切り替えは `script.js` が担当し、ヘッダ右上のボタン（`header.php`）で選ぶ。
**辞書と本体は同じドメインに置いてあり、明暗はサイト全体で1つの設定として共有する。**
そのため、次の3つは本体（`common/header.php` と `css/style.css`）と必ず同じにすること。
片方だけ変えると、辞書と本体で設定が分かれてしまう。

| 何 | 値 |
| --- | --- |
| `localStorage` のキー | `theme` |
| 保存する値 | `dark` / `light` |
| `<html>` に付ける属性 | `data-theme="dark"` / `"light"` |

- ボタンで選んでいなければ、OSの設定（`prefers-color-scheme`）に従う
- 保存済みの選択は `<head>` で同期読み込みする `script.js` が画面を描く前に付けるので、
  明るい画面が一瞬見えることはない
- 暗い色は `dict.css` の `@media (prefers-color-scheme: dark)` と `:root[data-theme="dark"]` の
  2か所に同じ並びで書いてある。JavaScriptが動かない環境でもOSの設定だけで暗くなるようにするためで、
  片方だけ直さないこと
- グラフ（`wordchart.js`）の色はCSSではなく描画時に決まるため、
  `dict.css` の変数を読んで描き、`data-theme` の変化を見張って描き直している

## 辞書順
見出し語の並び順は[辞書順について](https://zaslon.info/idyerin/%E8%BE%9E%E6%9B%B8%E9%A0%86%E3%81%AB%E3%81%A4%E3%81%84%E3%81%A6/)の規則に従う。
実装は `func.php` の `hksSortKey()` と `HKSCmpw()`、規則ごとのテストは `tests/run.php` にある。
仕様書に載っている並び順の例も、そのままテストに入れてある。

## 発音記号
検索結果の見出し語の直後に `/miːla/` の形で併記する。値の決め方は次の通り。

1. `idyer.json` の発音記号欄（`zpdicOnline.pronunciationTitle` の項目）に中身があれば、それをそのまま出す
2. 無ければ、`idyer.json` の `snoj` にある[akrantiain](https://github.com/Ziphil/AkrantiainTypescript)の規則を
   見出し語に適用して作る

辞書データが発音記号を持つのは `AVG` → `aːveg` のような、綴りから機械的に導けない頭字語だけなので、
それ以外は綴りから作る。発音記号欄が空文字列の単語も「無し」として扱う。

akrantiainの実装はTypeScript版しか無く、規則も辞書データの中にあるため、変換はブラウザ側で行う。
`vendor/akrantiain.min.js` が処理系、`pronunciation.js` が組み込み側で、`dict.php` がこの2つを
`defer` で読み込む。規則は `view.php` の `renderPronunciationRules()` が
`<script type="application/json" id="snojRules">` としてページに埋め込む。

`view.php` は、辞書データに発音記号を持つ単語には値をそのまま出し、持たない単語には
`<span class="wordPronunciation" data-form="見出し語"></span>` を置く。
`pronunciation.js` はこの `data-form` を持つ要素だけを埋めるので、辞書データ側の値が上書きされることはない。
規則の読み込みや変換に失敗した単語は、発音記号を空欄のままにする。

### vendor/akrantiain.min.js
[npmのakrantiain](https://www.npmjs.com/package/akrantiain)をesbuildでまとめたもので、手で編集しない。
作り直すときは次のようにする。

```
npm install akrantiain@1.2.1
npx esbuild node_modules/akrantiain/dist/index.js --bundle --format=iife \
  --global-name=AkrantiainLib --minify --target=es2017 --legal-comments=eof \
  --outfile=vendor/akrantiain.min.js
```

同梱されるのは akrantiain 1.2.1 と、その依存の parsimmon 1.18.1 の2つ
（akrantiain のもうひとつの依存 codemirror はエディタ用の色付け定義でしか使われないため取り込まれない）。
どちらもMITライセンスで、**著作権表示と許諾条項の全文を複製物に含めること**が条件なので、
バンドルし直したら次の2つを必ずやり直すこと。**片方だけではライセンス条件を満たさない。**

- `npm install` で入った各パッケージのライセンス全文を `vendor/LICENSE-akrantiain.txt` と
  `vendor/LICENSE-parsimmon.txt` に写す
- 同じ全文を `vendor/akrantiain.min.js` の先頭コメントにも入れる
  （このファイル単体で配信されるため、ファイル自身が全文を持っている必要がある）

バージョンを上げたときは、依存が増えていないかを確かめ、増えていれば同じように全文を足す。

### 書体（vendor/DoulosSIL-Regular.woff2）
発音記号は `dict.css` の `span.wordPronunciation` で `Doulos SIL` にしてある。
本文の書体はIPAの字を十分に持たないため、環境によって字が抜けたり別の書体で代替される。

閲覧者の環境に無くても同じ見た目になるよう、
[SIL公式配布](https://github.com/silnrsi/font-doulos/releases)の 7.000 の
`web/DoulosSIL-Regular.woff2` を無改変で置き、`@font-face` で読む。
`src` は `local()` を先に書いてあるので、端末に入っている環境では読み込まない。
サブセット化はしていない（約310KB）。

SIL Open Font License 1.1 で、**著作権表示とライセンス全文を複製物に含めること**が条件なので、
差し替えるときは配布物の `OFL.txt` も `vendor/LICENSE-DoulosSIL.txt` に写し直すこと。
同ライセンスは予約名（Reserved Font Name）の `Doulos` `SIL` を含む名前での改変版配布を禁じているため、
フォント自体は加工せずそのまま置く。

## 例文
`idyer.json` の `examples` を読んで表示する（閲覧のみで、編集はしない）。
辞書データ側では例文と単語がIDで結ばれているだけなので、
`func.php` の `makeExampleIndex()` で表示にも検索にも使える索引に組み替えている。

- 検索結果の見出し語には、その単語を使う例文を「例文」欄として並べる
- `example.php` に全例文の一覧を出す。原文・訳文・補足・タグ・使用単語・出典を表示する
- 例文の「使用単語」は、単語IDから見出し語への検索リンクにする
- 全文検索（`type=all`）は例文の原文・訳文・補足・タグも対象にする。
  ヒットした例文が紐づく単語が検索結果に出る

`sentence` 以外の項目は辞書データ側で省略できるため、`makeExampleIndex()` で空を埋めて形を揃えている。

## 凡例
`legend.php` が `idyer.json` の `legend` を読んで表示する。
以前は文法書側の記事（`/idyerin/辞書凡例/`）へのリンクだったが、
凡例は辞書データと一体で管理されている（[辞書のリポジトリ](https://github.com/Zaslon/IdyerinDictionary)）ため、
辞書データ側の記述をそのまま出す形にした。記事側とずれることが無くなる。

`legend` はMarkdownで書かれている。対応記法はzaslon-site本体のREADMEの「Markdown対応記法」を参照
（CommonMark + GFM ＋ サイト固有の拡張。辞書データ側の `zpdicOnline.enableMarkdown` はZpDIC側の
**単語欄**の設定で、`legend` とは無関係）。

### Markdownの変換
記法を辞書側で作り直さず、文法書側の記事と同じ見た目にするため、変換は**本体のレンダラに任せる**。
`view.php` の `legendMarkdownToHtml()` が本体の `common/markdown.php` を読み込み、
`Zaslon\Markdown\to_html()` を直接呼ぶ。

- 本体側の呼び出し口は `common/lib.php` の `markdown_to_html()` だが、**`lib.php` は読み込まないこと**。
  同ファイルは辞書側の `func.php` と同じ `h()` を定義しているため、二重定義で落ちる
- 本体の `markdown.php` は、保存させたいリンクの判定で本体の `site_config()` を呼ぶ。
  `lib.php` を読めない代わりに、`view.php` が `function_exists()` で確かめてから
  辞書側の `dictConfig()` を返す同名の関数を置いている（`site_url` の形式は本体と揃えてある）
- 辞書はFTPで本体と別に置けるので、本体の `common/markdown.php` か `vendor/autoload.php` が
  無ければ辞書側の `markdown.php` に落とす（下記）。変換中の例外も同じ扱いにする。
  本体側もサイトマップを作るときに辞書の `config.php` を `is_file()` で確かめてから読んでおり
  （zaslon-site の `index.php` の `sitemap_dict_urls()`）、これと対の関係になっている
- 辞書データに `legend` が無い場合は、その旨だけを出す
- 本体は生HTMLをそのまま通す設定なので、辞書データにHTMLを書けばそのまま出る（自分の辞書データなので許容する）

見た目（`dict.css` の `.legend`）も、本体の `css/style.css` の記事本文（見出しの縦棒・表・
インラインコード・引用）と同じ規則を写してある。表はこのページだけで使うため、
色変数 `--rule` `--tint` `--code-bg` `--th-bg` `--row-alt` `--quote-border` も本体と同じ名前・同じ値で足した。

### 本体が無い環境での変換（markdown.php）
辞書のリポジトリ単体でも凡例を読める形で出せるよう、`markdown.php` に簡易な変換を置いてある。
本体があるときは使われない。凡例で実際に使う記法だけを対象にした最小限の実装で、
対応するのは次の記法に限る。

| 対応する | 対応しない（素の文字のまま出る） |
| --- | --- |
| 見出し（`#`〜`######`）・段落 | 入れ子の箇条書き・引用・脚注 |
| 箇条書き（`-` `*` `+`）・番号付き（`1.`） | 参照形式のリンク（`[表示名][ref]`）・画像 |
| 表（GFM。区切り行が必須） | 下線式の見出し・セル内の `\|` |
| 行内コード・```で囲んだコードブロック | 生HTML（本体と違い必ずエスケープする） |
| 強調（`**` `*`）・取り消し線（`~~`）・区切り線 | 日本語の約物をまたぐ強調（本体はCJK対応の判定を入れている） |

段落内の改行を `<br />` にする点は本体と揃えてある。現在の凡例のように上表の左側だけで書かれていれば、
本体のレンダラと同じHTMLになる（実際の凡例で突き合わせて確認済み）。
凡例で新しい記法を使い始めたときは、本体が無い環境での見え方も確かめること。

本体側の関数名や置き場所が変われば辞書側の簡易な変換に落ちる（ページ自体は出る）ので、
本体のMarkdown変換に手を入れるときはこのページの表示も確かめること。

## キャッシュ
辞書のソートは重いため、結果を `cache/` に保存して使い回している。
`idyer.json` か `func.php` の更新時刻かサイズが変われば自動で作り直されるので、通常は触らなくてよい
（並び順の規則を変えたときにも作り直されるよう、比較関数のあるファイルも無効化の対象にしている）。
キャッシュが壊れていたり書き込めなかったりしても、その場でソートし直して動作する。
手動で消す場合はディレクトリごと削除してよい（`cache/` はGit管理外）。

## 単語数の記録
`chart.php` のグラフの元データは `logger/dictLog.csv` で、`logger/idyer_logger.php` が追記する。
`idyer.json` の単語数が前回の記録と変わっていれば「その日の日付, 単語数」を1行足し、
変わっていなければ何もしない。cronで毎日1回まわす想定。

```
php logger/idyer_logger.php
```

`logger/` は `dictLog.csv` をブラウザから読むため公開ディレクトリに置いてあるので、
Web経由で叩かれて勝手に追記されないよう、スクリプト側でCLI以外の実行を403で弾いている。

## テスト
```
php tests/run.php
```

凡例のMarkdown変換だけは本体（zaslon-site）を必要とするため、
本体の中の `dict/` に置いた状態で実行したときだけ、そのぶんのテストも一緒に動く。
辞書のリポジトリ単体で実行した場合は、原文をそのまま出す側の動きだけを確かめる。

## 参照
[文法書](https://zaslon.info/idyer/)

[web辞書本番環境](https://zaslon.info/dict/dict.php)

[辞書の内容データ](https://github.com/Zaslon/IdyerinDictionary)
