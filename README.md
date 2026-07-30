# webDictionary
イジェール語オンライン辞書

## このリポジトリについて
このリポジトリはイジェール語のオンライン辞書システムを管理するものです。

## ファイル構成

| ファイル | 役割 |
| --- | --- |
| `dict.php` | 検索ページ。パラメータの解釈と組み立てのみを行う |
| `chart.php` | 単語数推移のグラフページ |
| `func.php` | 文字列処理、HKS順ソート、データ読み込み、キャッシュ |
| `search.php` | 検索と接辞サジェスト |
| `view.php` | 検索結果のHTML出力 |
| `header.php` / `footer.php` | 全ページ共通のレイアウト |
| `anal.php` | アクセス解析タグの差し込み口 |
| `idyer.json` | 辞書データ（[別リポジトリ](https://github.com/Zaslon/IdyerinDictionary)で管理） |
| `affixTable.csv` | 接辞テーブル。[0]対象品詞、[1]形態、[2]説明、[3]特殊処理 |
| `dictLog.csv` | 単語数の記録。グラフの元データ |
| `tests/run.php` | テスト |

`Endrata` フォント（`*.woff` / `*.ttf`）は意図的にリポジトリに含めていないため、
クローンした環境では「イジェール文字表示」は既定のフォントで表示される。

## 辞書順
見出し語の並び順は[辞書順について](https://zaslon.info/idyerin/%E8%BE%9E%E6%9B%B8%E9%A0%86%E3%81%AB%E3%81%A4%E3%81%84%E3%81%A6/)の規則に従う。
実装は `func.php` の `hksSortKey()` と `HKSCmpw()`、規則ごとのテストは `tests/run.php` にある。
仕様書に載っている並び順の例も、そのままテストに入れてある。

## キャッシュ
辞書のソートは重いため、結果を `cache/` に保存して使い回している。
`idyer.json` か `func.php` の更新時刻かサイズが変われば自動で作り直されるので、通常は触らなくてよい
（並び順の規則を変えたときにも作り直されるよう、比較関数のあるファイルも無効化の対象にしている）。
キャッシュが壊れていたり書き込めなかったりしても、その場でソートし直して動作する。
手動で消す場合はディレクトリごと削除してよい（`cache/` はGit管理外）。

## テスト
```
php tests/run.php
```

## 参照
[文法書](https://zaslon.info/idyer/)

[web辞書本番環境](https://zaslon.info/dict/dict.php)

[辞書の内容データ](https://github.com/Zaslon/IdyerinDictionary)
