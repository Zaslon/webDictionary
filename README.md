# webDictionary
イジェール語オンライン辞書

## このリポジトリについて
このリポジトリはイジェール語のオンライン辞書システムを管理するものです。

## ファイル構成

| ファイル | 役割 |
| --- | --- |
| `dict.php` | 検索ページ。パラメータの解釈と組み立てのみを行う |
| `example.php` | 例文の一覧ページ |
| `chart.php` | 単語数推移のグラフページ |
| `func.php` | 文字列処理、HKS順ソート、データ読み込み、キャッシュ |
| `search.php` | 検索と接辞サジェスト |
| `view.php` | 検索結果のHTML出力 |
| `header.php` / `footer.php` | 全ページ共通のレイアウト |
| `dict.css` | 全ページ共通のスタイル。色は zaslon.info 本体と同じ変数名で持つ |
| `script.js` | 明暗（ライト／ダーク）の切り替え |
| `dict.js` | イジェール文字表示の切り替え |
| `wordchart.js` | 単語数推移のグラフの描画 |
| `anal.php` | アクセス解析タグの差し込み口 |
| `idyer.json` | 辞書データ（[別リポジトリ](https://github.com/Zaslon/IdyerinDictionary)で管理） |
| `affixTable.csv` | 接辞テーブル。[0]対象品詞、[1]形態、[2]説明、[3]特殊処理 |
| `logger/dictLog.csv` | 単語数の記録。グラフの元データ |
| `logger/idyer_logger.php` | 単語数を記録するスクリプト。cronから定期実行する |
| `tests/run.php` | テスト |

`Endrata` フォント（`*.woff` / `*.ttf`）は意図的にリポジトリに含めていないため、
クローンした環境では「イジェール文字表示」は既定のフォントで表示される。

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

イジェール文字の `Endrata` だけは辞書固有なので、上の指定より優先される。

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

## 参照
[文法書](https://zaslon.info/idyer/)

[web辞書本番環境](https://zaslon.info/dict/dict.php)

[辞書の内容データ](https://github.com/Zaslon/IdyerinDictionary)
