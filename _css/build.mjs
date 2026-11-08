// ZasDesignSystem が書き出した design-system.css（全部品入り）から、辞書で使う部分だけを残して
// design-system.css（辞書フォルダ直下）を作る。デプロイはビルドせず git の差分を送るので、生成物もコミットする。
//
//   node _css/build.mjs
//
// zaslon-site 本体の _css/build.mjs と同じ処理で、違うのは読む場所と書き出す場所だけ。
// キーカラーの置き換え（KEY_TO_GRAY・LINK）は本体と同じ値にしておくこと。食い違うと辞書と本体で色が変わる。
//
// 部品を使い始めたり ZasDesignSystem を更新したりしたら、design-system.full.css を差し替えて実行し直すこと。
// 使っているかどうかは、下の SCAN にあるファイルに dads-* / sw-* のクラス名が書かれているかで判定する。
// クラス名を文字列の連結で組み立てると見つけられず、その部品の CSS が消える。
import { readFile, writeFile, readdir } from 'node:fs/promises';
import { join, dirname, extname } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const SOURCE = join(root, '_css/design-system.full.css');
const OUTPUT = join(root, 'design-system.css');
const SCAN_EXT = new Set(['.php', '.js', '.css']);
const SKIP = new Set(['design-system.css']);

// 凡例は本体（zaslon-site）の common/markdown.php が HTML にするため、そこで付くクラスは辞書のファイルに現れない。
// 本体の markdown.php が出すクラスを変えたら、ここも合わせること。
const RENDERER_CLASSES = [
  'dads-blockquote', 'dads-button', 'dads-list', 'dads-table', 'dads-table__col-header', 'dads-table__table',
  'sw-code', 'sw-code-block', 'sw-code-block__code', 'sw-code-block__header',
];

const texts = [];
for (const name of await readdir(root)) {
  if (SCAN_EXT.has(extname(name)) && !SKIP.has(name)) texts.push(await readFile(join(root, name), 'utf8'));
}
texts.push(RENDERER_CLASSES.join(' '));
const siteText = texts.join('\n');
const usedClasses = new Set(siteText.match(/\b(?:dads|sw)-[a-zA-Z0-9_-]+/g));

// ---- 構文解析 ----
// 入れ子の CSS は使われていない前提で、宣言と子ブロックの並びとして読む。文字列とコメントは飛ばす。
const exported = await readFile(SOURCE, 'utf8');

// ---- キーカラーをグレーにする ----
// ZasDesignSystem の書き出しはキーカラーが赤紫だが、本体は無彩色を基調にしている。辞書も本体に合わせる。
// 灰色は各段階と相対輝度が同じ値にしてあるので、白・背景とのコントラスト比は書き出し時の検証結果のまま保たれる。
// 赤紫の値はテーマの層にしか現れない（部品の CSS は変数で参照する）ので、値の文字列で置き換えてよい。
const KEY_TO_GRAY = {
  '#FFE6F7': '#EDEDED', '#FBDAF1': '#E3E3E3', '#EAC2DE': '#CDCDCD', '#D8AACB': '#B8B8B8',
  '#C590B6': '#A0A0A0', '#B074A0': '#878787', '#985387': '#6B6B6B', '#823B72': '#555555',
  '#722D63': '#474747', '#69235A': '#3F3F3F', '#561549': '#303030', '#49013C': '#242424',
  '#320029': '#171717',
};
// リンクだけは青にする。灰色のままだと地の文と色で見分けられないため。
// 置き換え前の値で明暗を見分ける（ライトはキーの 700/600、ダークは 300/200 が入っている）。
const LINK = [
  ['--sw-color-link', '#823B72', '#1A5FB4'],
  ['--sw-color-link-hover', '#985387', '#3A75C4'],
  ['--sw-color-link', '#D8AACB', '#85B4F0'],
  ['--sw-color-link-hover', '#EAC2DE', '#A9CBF5'],
];
let recolored = exported;
for (const [name, from, to] of LINK) {
  recolored = recolored.replace(new RegExp(`(${name}\\s*:\\s*)${from}(?=\\s*[;}])`, 'gi'), `$1${to}`);
}
const keyPattern = new RegExp(Object.keys(KEY_TO_GRAY).join('|') + '(?![0-9a-fA-F])', 'gi');
const src = recolored.replace(keyPattern, (hex) => KEY_TO_GRAY[hex.toUpperCase()]);

const license = src.match(/^\s*\/\*[\s\S]*?\*\//)[0].trim();

function parse(text) {
  let i = 0;
  function skipString(q) { i++; while (i < text.length && text[i] !== q) { if (text[i] === '\\') i++; i++; } i++; }
  function block() {
    const items = [];
    let buf = '';
    while (i < text.length) {
      const c = text[i];
      if (c === '/' && text[i + 1] === '*') { i = text.indexOf('*/', i + 2) + 2; buf += ' '; continue; }
      if (c === '"' || c === "'") { const s = i; skipString(c); buf += text.slice(s, i); continue; }
      if (c === '{') { i++; const prelude = buf.trim(); buf = ''; items.push({ prelude, children: block() }); continue; }
      if (c === '}') { i++; if (buf.trim()) items.push({ decl: buf.trim() }); return items; }
      if (c === ';') { i++; if (buf.trim()) items.push({ decl: buf.trim() }); buf = ''; continue; }
      buf += c; i++;
    }
    if (buf.trim()) items.push({ decl: buf.trim() });
    return items;
  }
  return block();
}

const RAW_AT = /^@(keyframes|font-face|property|counter-style|page)\b/;

// セレクタの並びのうち、使っていないクラスを含むものを落とす。全部落ちたら規則ごと消す。
function pruneSelectors(prelude) {
  const parts = prelude.split(/,(?![^()]*\))/).map((s) => s.trim());
  const kept = parts.filter((sel) => {
    const classes = sel.replace(/:not\([^()]*\)/g, "").match(/\.(?:dads|sw)-[a-zA-Z0-9_-]+/g) || [];
    return classes.every((c) => usedClasses.has(c.slice(1)));
  });
  return kept.join(',');
}

function pruneRules(items) {
  const out = [];
  for (const it of items) {
    if (it.decl !== undefined) { out.push(it); continue; }
    if (RAW_AT.test(it.prelude)) { out.push(it); continue; }
    if (it.prelude.startsWith('@')) {
      const children = pruneRules(it.children);
      if (children.length) out.push({ prelude: it.prelude, children });
      continue;
    }
    const prelude = pruneSelectors(it.prelude);
    if (prelude && it.children.length) out.push({ prelude, children: it.children });
  }
  return out;
}

let tree = pruneRules(parse(src));

// ---- 使われない CSS 変数を落とす ----
// 参照が消えると別の変数が不要になることがあるので、減らなくなるまで繰り返す。
// 辞書の CSS・PHP・JavaScript からの参照（getPropertyValue 等）も数える。
function serialize(items) {
  return items.map((it) => it.decl !== undefined ? it.decl + ';' : `${it.prelude}{${serialize(it.children)}}`).join('');
}
function pruneVars(items, used) {
  const out = [];
  for (const it of items) {
    if (it.decl !== undefined) {
      const m = it.decl.match(/^(--[a-zA-Z0-9_-]+)\s*:/);
      if (m && !used.has(m[1])) continue;
      out.push(it);
      continue;
    }
    if (RAW_AT.test(it.prelude)) { out.push(it); continue; }
    const children = pruneVars(it.children, used);
    if (children.length) out.push({ prelude: it.prelude, children });
  }
  return out;
}
for (;;) {
  const css = serialize(tree);
  const refs = (css + siteText).match(/var\(\s*--[a-zA-Z0-9_-]+|['"]--[a-zA-Z0-9_-]+['"]/g) || [];
  const used = new Set(refs.map((r) => r.replace(/^var\(\s*|['"]/g, '')));
  const next = pruneVars(tree, used);
  if (serialize(next).length === css.length) break;
  tree = next;
}

// ---- 書き出し ----
// 空白は文字列の外でだけ詰める。セレクタ内の空白（子孫結合子）は 1 つ残す。
function minify(css) {
  return css.replace(/("(?:\\.|[^"\\])*"|'(?:\\.|[^'\\])*')|\s+/g, (m, str) => str ?? ' ')
    .replace(/("(?:\\.|[^"\\])*"|'(?:\\.|[^'\\])*')|\s*([{};])\s*/g, (m, str, p) => str ?? p)
    .replace(/;}/g, '}');
}
// トークンのライセンス文は全部品入りの書き出しに含まれないため、ここで添える。
const tokensLicense = (await readFile(join(root, 'vendor/LICENSE-dads-design-tokens.txt'), 'utf8')).trim();
const header = `${license}\n\n/*\nIncludes values from @digital-go-jp/design-tokens.\n\n${tokensLicense}\n*/\n`
  + '/* 生成物。_css/build.mjs で作る。手で編集しないこと。 */\n';
const body = minify(serialize(tree)).replace(/}/g, '}\n');
await writeFile(OUTPUT, header + body);
const before = Buffer.byteLength(src), after = Buffer.byteLength(header + body);
console.log(`${OUTPUT}: ${before} → ${after} bytes`);
