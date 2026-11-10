# 検索

## 接辞サジェスト（search.php・affixTable.csv）
検索語が見出し語の派生形なら「もしかして」を出す。規則は文法書の
[派生](https://zaslon.info/idyerin/%e6%b4%be%e7%94%9f/)・[助詞(後置詞)と格](https://zaslon.info/idyerin/%e5%8a%a9%e8%a9%9e%e5%be%8c%e7%bd%ae%e8%a9%9e/)・[記述詞](https://zaslon.info/idyerin/%e8%a8%98%e8%bf%b0%e8%a9%9e/)に従う。

`affixTable.csv` の列：`[0]` 対象品詞、`[1]` 形態、`[2]` 説明、`[3]` 特殊処理、`[4]` 派生後の品詞（空欄ならそれ以上派生しない。名詞の格など）。

接辞の付き方（`derivationStems()` / `makeDerivations()`）：
* 接尾辞は語幹に付く（動詞の `-e`・記述詞の `-(i)n` を置き換える）
* 接頭辞は語形の頭に付く。品詞を変える接頭辞だけは語幹に付く
* 接辞のカッコ内の母音は、語幹が子音で終わるときだけ現れる
* 接頭辞の後ろは連濁する。`[3]` が `NO_VOICING` の接辞は連濁しない

`findDerivationPaths()` が接辞を `DERIVATION_MAX_DEPTH` 段まで重ねて検索語への道筋を探す。次の枝は辿らない。
* 途中の形が検索語の部分文字列でないもの
* 派生後の品詞が空の接辞
* 既に見出し語として載っている形

同じ見出し語に複数の解釈があれば最も浅いものだけを出す。

## インクリメンタルサーチ（livesearch.js・results.php）
* 検索語は最後の入力から300ms後に問い合わせる。IME 変換中は問い合わせない。「検索」ボタンと Enter は即時
* 検索条件の変更は即時。「イジェール文字表示」は書体だけの変更なので問い合わせず、URL の `Idf` だけ合わせる
* 結果内の検索ページへのリンクもページ内で差し替える。修飾キー付きクリックと他ページへのリンクは通常遷移
* 古い応答が新しい結果を上書きしないようにしている

### URL と履歴
URL のクエリの並びは `func.php` の `makeLink()` と同じ（`keyBox, type, mode, page, [Idf], [voicing], [id]`）。
どの URL も `dict.php` で開けば同じ結果になる。

* 入力中は1件の履歴にまとめ、「検索」・Enter・リンクで区切る
* 結果内のリンクは押すたびに1件積む
* リンクや戻る/進むではフォームも URL の条件に戻す。`fwd` → 部分一致、`trans` → 見出し語・訳語検索の読み替えは `dict.php` と同じ。「イジェール文字表示」は URL から戻さない
* リンクで開いた後にフォームを触ると `id` は外れる

GA4 の拡張計測が有効だと URL 書き換えごとに page_view が数えられる。要らなければ GA の管理画面で切る。

### results.php
`dict.php` と同じクエリを受け、`#results` の中身だけを返す。どちらも `readSearchRequest()` / `runSearch()` / `renderSearchResults()` を通すため同じ HTML になる（`tests/run.php` で突き合わせ）。
`X-Robots-Tag: noindex`・`Cache-Control: no-cache` を付け、辞書の読み込み失敗時は内部情報を出さずに500を返す。

### 通信できないとき・JavaScript が無いとき
* 入力・条件変更で失敗したら、表示中の結果を残してメッセージを出す。URL は変えない
* リンクで失敗したら通常遷移に切り替え、Service Worker か `offline.php` に任せる
* `fetch`・`AbortController`・`history.pushState` が無ければ何もせず、フォームの GET 送信で動く

### 作り
フォーム（`#searchForm`）・結果（`#results`）・状態表示（`#searchStatus`）の3つのビューは、ブラウザのイベントを
`dict:input` / `dict:optionchange` / `dict:fontchange` / `dict:submit` / `dict:navigate` に翻訳して泡立てるだけで判断しない。
ルート（`div.all`）の Mediator が待ち時間・問い合わせ・履歴を受け持つ。
差し替え後は `#results` から `dict:resultsupdated` を出す（`pronunciation.js` が受ける）。

## ページ送り
総ページ数が `PAGE_NAVIGATION_COLLAPSE_FROM` 以上のとき、先頭・末尾 `PAGE_NAVIGATION_EDGE` ページと今のページの前後 `PAGE_NAVIGATION_AROUND` ページを出し、間を省略記号にする。
番号の並びは `view.php` の `navigationPages()` だけが決める（JavaScript の有無で変わらない）。
例：総ページ数20で10ページ目なら `1 2 3 … 9 10 11 … 18 19 20`。例文一覧のページ送りは省略しない。

## 辞書順
[辞書順について](https://zaslon.info/idyerin/%E8%BE%9E%E6%9B%B8%E9%A0%86%E3%81%AB%E3%81%A4%E3%81%84%E3%81%A6/)に従う。
実装は `func.php` の `hksSortKey()` / `HKSCmpw()`。仕様書の例を含む規則ごとのテストが `tests/run.php` にある。

## 例文
`idyer.json` の `examples` を表示する（閲覧のみ）。`func.php` の `makeExampleIndex()` が単語 ID との対応を索引に組み替え、省略可能な項目を埋めて形を揃える。

- 検索結果の見出し語に、その単語を使う例文を並べる
- `example.php` に全例文を出す。使用単語は見出し語への検索リンクにする
- 全文検索（`type=all`）は例文の原文・訳文・補足・タグも対象にし、紐づく単語を結果に出す
