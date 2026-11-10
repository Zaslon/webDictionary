# 発音記号
検索結果の見出し語の直後に `/miːla/` の形で出す。

1. `idyer.json` の発音記号欄（`zpdicOnline.pronunciationTitle` の項目）に中身があればそれを出す（空文字列は「無し」扱い）
2. 無ければ `idyer.json` の `snoj` にある [akrantiain](https://github.com/Ziphil/AkrantiainTypescript) の規則を見出し語に適用する

akrantiain は TypeScript 版しか無いため、変換はブラウザ側で行う。

- `dict.php` が `vendor/akrantiain.min.js`・`pronunciation.js`・`livesearch.js` をこの順に `defer` で読む
- 規則は `view.php` の `renderPronunciationRules()` が `<script type="application/json" id="snojRules">` として埋め込む
- `view.php` は値を持たない単語に `<span class="wordPronunciation" data-form="見出し語"></span>` を置き、`pronunciation.js` はこれだけを埋める
- インクリメンタルサーチ後は `dict:resultsupdated` を受けて埋め直す。規則の中身が同じ間は読み込み結果を使い回す
- 変換に失敗した単語は空欄のまま

## vendor/akrantiain.min.js
npm の akrantiain を esbuild でまとめたもの。手で編集しない。
```
npm install akrantiain@1.2.1
npx esbuild node_modules/akrantiain/dist/index.js --bundle --format=iife \
  --global-name=AkrantiainLib --minify --target=es2017 --legal-comments=eof \
  --outfile=vendor/akrantiain.min.js
```
同梱物は akrantiain 1.2.1 と依存の parsimmon 1.18.1（codemirror は取り込まれない）。どちらも MIT。
作り直したら次の**両方**を行うこと。

- 各パッケージのライセンス全文を `vendor/LICENSE-akrantiain.txt` / `vendor/LICENSE-parsimmon.txt` に写す
- 同じ全文を `vendor/akrantiain.min.js` の先頭コメントにも入れる（単体で配信されるため）

バージョンを上げたら依存が増えていないか確かめ、増えていればそのライセンス全文も足す。

## 書体（vendor/DoulosSIL-Regular.woff2）
発音記号は `dict.css` の `span.wordPronunciation` で `Doulos SIL` にする（`local()` を先に指定）。
[SIL 公式配布](https://github.com/silnrsi/font-doulos/releases) 7.000 の `web/DoulosSIL-Regular.woff2` を無改変で置いている。

SIL Open Font License 1.1。差し替えるときは配布物の `OFL.txt` を `vendor/LICENSE-DoulosSIL.txt` に写し直すこと。
予約名 `Doulos` `SIL` を含む改変版の配布は禁じられているため、フォント自体は加工しない（サブセット化もしない）。
