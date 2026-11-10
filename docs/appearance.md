# 見た目
zaslon.info 本体と同じく ZasDesignSystem（デジタル庁デザインシステムが基）に従う。
ヘッダ・フッタ・ページ見出しは本体の `common/header.php` / `css/style.css` と同じマークアップと規則。

## CSS の構成
| ファイル | 中身 | 編集 |
| --- | --- | --- |
| `design-system.css` | 部品の見た目と、色・文字・寸法のトークン（ライト／ダーク） | しない（生成物） |
| `dict.css` | 配置と、部品の無い要素（単語欄・例文・ページ送り・凡例） | する |

`design-system.css` はカスケードレイヤーに入っており、`dict.css` が常に優先される。
`dict.css` では部品（`.dads-*` / `.sw-*`）の見た目を上書きせず、色は `--sw-color-*` トークンだけを使う（ダーク用の指定は不要）。

## design-system.css の作り直し
`_css/design-system.full.css`（ZasDesignSystem の `local-design-system/web/design-system.css` の写し）から、使う部品だけを残して作る。デプロイ時にビルドしないので生成物もコミットする。
```
node _css/build.mjs
```
- 使っている部品は辞書直下の `*.php` / `*.js` / `*.css` 中の `dads-*` / `sw-*` クラス名で判定する。**クラス名を文字列連結で組み立てると検出されず CSS が消える**
- 凡例で本体の `common/markdown.php` が付けるクラスは `build.mjs` の `RENDERER_CLASSES` に並べてある。本体側を変えたら合わせる
- `KEY_TO_GRAY`・`LINK` は本体の `_css/build.mjs` と同じ値にすること
- ZasDesignSystem を更新したら `_css/design-system.full.css` を差し替えて実行し直す

## 書体
本文は `--font-family-sans`、サイト名は本体と同じ `"Noto Serif Display", "Noto Serif JP"` 系（狭い画面では 24px）。
`Endrata`（イジェール文字）と `Doulos SIL`（発音記号）は上の指定より優先される。

## ダークモード
`script.js` が担当し、ヘッダ右上のボタンで選ぶ。**明暗は本体と1つの設定を共有する**ため、次は本体（`common/header.php`・`js/site.js`）と必ず揃えること。

| 何 | 値 |
| --- | --- |
| `localStorage` のキー | `sw-theme`（旧キー `theme` も読み、選び直すと消す） |
| 保存する値 | `dark` / `light` |
| `<html>` の属性 | `data-theme="dark"` / `"light"` |

- 未選択なら `prefers-color-scheme` に従う
- 保存済みの選択は `header.php` の `<head>` のインラインスクリプトで描画前に付ける。外部ファイルにするとちらつく
- `wordchart.js` はトークンを読んで描き、`data-theme` の変化で描き直す

## メニュー
幅 768px 未満か、項目が1行に収まらないときはドロワーにする。描画前の判定は `header.php` のインラインスクリプト、以後は `script.js`。
JavaScript や `showModal` が無ければ水平メニューを横スクロールで出す。

## 通知バー・ブラウザ枠・起動時の画面の色
`config.php` の `app_theme_color` / `app_theme_color_dark` を `--sw-color-background` と揃える（本体の `theme-color` と同じ値）。

- `header.php` は `theme-color` を `media` 付きで light → dark の順に出す。`media` を見ない古いブラウザは先頭を使うため**順を入れ替えない**
- スクリプトが動けば、表示中の `--sw-color-background` で両方の `theme-color` を上書きする
- iOS の `apple-mobile-web-app-status-bar-style` は読み込み時の1回しか効かないため `default` 固定
- 起動時の画面の色はマニフェストの `theme_color` / `background_color` で、端末の明暗に合わせる（`func.php` の `manifestTheme()`）。端末の設定は次で受け取り、どちらも無ければ明るい方

  | 経路 | 対象 |
  | --- | --- |
  | `Sec-CH-Prefers-Color-Scheme` ヘッダ（`header.php` が `Accept-CH` で要求） | Chromium 系 |
  | マニフェスト URL の `?theme=dark`（`script.js` が付ける） | iOS |

  マニフェストは追加時と更新確認時にしか読まれないため、端末の設定変更は次の読み直しまで反映されない

## イジェール文字表示（dict.js）
状態は `localStorage` の `idyerFont` に持ち、`<html>` に `idyer-font` クラスを付ける。描画前に確定させるため `<head>` で同期読み込みする。
優先順位：保存済みの設定 → URL の `Idf`（保存済みが無いときだけ見て保存する）→ 既定のフォント。

## アセットの版
CSS・JS は `func.php` の `assetUrl()` で `?v=更新時刻` を付けて読む。
