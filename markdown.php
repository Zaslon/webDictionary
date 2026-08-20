<?php
//簡易なMarkdown変換。辞書を本体（zaslon-site）と切り離して置いた環境で凡例を出すためだけに使う。
//本体があるときは本体のレンダラ（CommonMark + GFM）を使うので、こちらは出番が無い。
//凡例で実際に使う記法に絞った最小限の実装で、対応記法はREADMEの「凡例」に書いてある。
//対応しない記法（入れ子の箇条書き・引用・脚注・参照リンク等）は素の文字のまま出す。
//本体と違い生HTMLは通さず、必ずエスケープしてから記号を見る。
require_once __DIR__ . '/func.php';

//凡例は見出しの直後に空行なしで表や段落が続くため、空行ではなく行の形で塊を決める
function markdownToHtml($markdown){
	if (!is_string($markdown)){
		return '';
	}
	$lines = explode("\n", str_replace(array("\r\n", "\r"), "\n", $markdown));
	$lineAmount = count($lines);

	$html = '';
	$paragraph = array();//続いている地の文の行
	$listTag = null;     //今開いている箇条書きのタグ。閉じ忘れないよう持ち回る

	//地の文の改行は<br />にする。本体のレンダラも同じ扱いにしてあるため
	//（原稿が1行1文で書かれており、段落内で詰めると表示が変わってしまう）
	$closeParagraph = function () use (&$html, &$paragraph){
		if (!$paragraph){
			return;
		}
		$html .= '<p>' . implode('<br />', array_map('markdownInline', $paragraph)) . '</p>';
		$paragraph = array();
	};
	$closeList = function () use (&$html, &$listTag){
		if ($listTag === null){
			return;
		}
		$html .= '</' . $listTag . '>';
		$listTag = null;
	};

	for ($index = 0; $index < $lineAmount; $index++){
		$line = rtrim($lines[$index]);
		$body = preg_replace('/^ {0,3}/u', '', $line);//行頭3つまでの空白は記法の判定に影響しない

		//空行は地の文と箇条書きの切れ目
		if (trim($line) === ''){
			$closeParagraph();
			$closeList();
			continue;
		}

		//```で囲んだコードブロック。閉じが無いまま終わってもそこまでを出す
		if (strpos($body, '```') === 0){
			$closeParagraph();
			$closeList();
			$code = array();
			for ($index++; $index < $lineAmount; $index++){
				if (strpos(preg_replace('/^ {0,3}/u', '', rtrim($lines[$index])), '```') === 0){
					break;
				}
				$code[] = $lines[$index];
			}
			$html .= '<pre><code>' . h(implode("\n", $code)) . '</code></pre>';
			continue;
		}

		//見出し
		if (preg_match('/^(#{1,6})\s+(.+)$/u', $body, $matched)){
			$closeParagraph();
			$closeList();
			$level = strlen($matched[1]);
			$html .= '<h' . $level . '>' . markdownInline(trim($matched[2])) . '</h' . $level . '>';
			continue;
		}

		//表。語源欄の説明のように地の文に「|」が出るだけの行を表にしないよう、
		//行頭が「|」で、かつ次の行が区切り行のときだけ表として読む
		if (strpos($body, '|') === 0 && isset($lines[$index + 1]) && isMarkdownTableDelimiter($lines[$index + 1])){
			$closeParagraph();
			$closeList();
			$header = markdownTableCells($body);
			$rows = array();
			for ($index += 2; $index < $lineAmount; $index++){
				$rowLine = preg_replace('/^ {0,3}/u', '', rtrim($lines[$index]));
				if (strpos($rowLine, '|') !== 0){
					$index--;//表ではない行はこの後の判定にもう一度かける
					break;
				}
				$rows[] = markdownTableCells($rowLine);
			}
			$html .= markdownTable($header, $rows);
			continue;
		}

		//区切り線
		if (preg_match('/^(-{3,}|\*{3,}|_{3,})$/u', $body)){
			$closeParagraph();
			$closeList();
			$html .= '<hr />';
			continue;
		}

		//箇条書き。入れ子は読まないため、字下げした行も同じ深さの項目になる
		if (preg_match('/^([-*+]|\d+\.)\s+(.*)$/u', $body, $matched)){
			$closeParagraph();
			$tag = ($matched[1] === '-' || $matched[1] === '*' || $matched[1] === '+') ? 'ul' : 'ol';
			if ($listTag !== $tag){
				$closeList();
				$html .= '<' . $tag . '>';
				$listTag = $tag;
			}
			$html .= '<li>' . markdownInline($matched[2]) . '</li>';
			continue;
		}

		//上のどれでもなければ地の文
		$closeList();
		$paragraph[] = $line;
	}
	$closeParagraph();
	$closeList();

	return $html;
}

//表の区切り行（| --- | :---: |）かどうか
function isMarkdownTableDelimiter($line){
	return preg_match('/^\s*\|(?:\s*:?-+:?\s*\|)+\s*$/u', $line) === 1;
}

//1行をセルに割る。セルの中の「\|」は読まない（凡例では使っていない）
function markdownTableCells($line){
	$line = trim($line);
	$line = preg_replace('/^\|/u', '', $line);
	$line = preg_replace('/\|$/u', '', $line);
	return array_map('trim', explode('|', $line));
}

//セルの数が行によって違っても表が崩れないよう、見出しの列数に合わせる
function markdownTable(array $header, array $rows){
	$columnAmount = count($header);
	$html = '<table><thead><tr>';
	foreach ($header as $singleCell){
		$html .= '<th>' . markdownInline($singleCell) . '</th>';
	}
	$html .= '</tr></thead><tbody>';
	foreach ($rows as $singleRow){
		$singleRow = array_slice(array_pad($singleRow, $columnAmount, ''), 0, $columnAmount);
		$html .= '<tr>';
		foreach ($singleRow as $singleCell){
			$html .= '<td>' . markdownInline($singleCell) . '</td>';
		}
		$html .= '</tr>';
	}
	return $html . '</tbody></table>';
}

//行内の記法。行内コードは中身に他の記法を効かせないため、先に切り分けてから残りを読む
function markdownInline($text){
	$html = '';
	foreach (preg_split('/(`[^`]+`)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE) as $singlePart){
		if (strlen($singlePart) > 1 && $singlePart[0] === '`'){
			$html .= '<code>' . h(substr($singlePart, 1, -1)) . '</code>';
			continue;
		}
		$html .= markdownEmphasis(h($singlePart));
	}
	return $html;
}

//リンクと強調。エスケープ済みの文字列を受け取る（「"」は&quot;になっているので属性値を壊さない）
//本体のレンダラは日本語の約物をまたぐ強調にも対応しているが、ここでは素直に前後の記号だけを見る
function markdownEmphasis($escaped){
	$escaped = preg_replace_callback('/\[([^\]]+)\]\(([^)\s]+)\)/u', function ($matched){
		return markdownLink($matched[1], $matched[2]);
	}, $escaped);
	$escaped = preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $escaped);
	$escaped = preg_replace('/~~(.+?)~~/u', '<del>$1</del>', $escaped);
	return preg_replace('/(?<!\*)\*([^*]+)\*(?!\*)/u', '<em>$1</em>', $escaped);
}

//javascript:のような危険なURLはリンクにせず、書いたままの文字で出す
function markdownLink($text, $url){
	$hasScheme = preg_match('#^[A-Za-z][A-Za-z0-9+.\-]*:#u', $url) === 1;
	if ($hasScheme && preg_match('#^(?:https?|mailto|tel):#iu', $url) !== 1){
		return '[' . $text . '](' . $url . ')';
	}
	return '<a href="' . $url . '">' . $text . '</a>';
}
