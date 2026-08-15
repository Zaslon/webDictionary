<?php
//検索処理
require_once __DIR__ . '/func.php';

function setFunc($mode){
	switch ($mode){
		case "prt":
			return "stripos";
		case "fwd":
			return "startsWith";
		case "perf":
			return "perfectHit";
		default:
			return "stripos";
	}
}

//stripos は先頭一致で0を返すため、必ず !== false で判定して真偽値に揃える
//$exampleText はこの見出し語を使う例文をつないだもの。全文検索のときだけ使う
function isHit($singleEntry, $needle, $type, $mode, $exampleText = ''){
	$func = setFunc($mode);

	if ($type !== "trans" && $func($singleEntry["entry"]["form"], $needle) !== false){
		return true;
	}
	if ($type === "word"){
		return false;
	}

	foreach ($singleEntry["translations"] as $singleTranslation){
		//全文検索のときは品詞名も対象にする
		if ($type === "all" && $func($singleTranslation["title"], $needle) !== false){
			return true;
		}
		foreach ($singleTranslation["forms"] as $singleTranslationForm){
			$haystack = ($type === "all") ? $singleTranslationForm : deleteSymbolsForTrans($singleTranslationForm);
			if ($func($haystack, $needle) !== false){
				return true;
			}
		}
	}

	if ($type === "all"){
		foreach ($singleEntry["contents"] as $singleContent){
			if ($func($singleContent["text"], $needle) !== false){
				return true;
			}
		}
		//例文は単語欄に表示されるので、全文検索の対象にも含める
		if ($exampleText !== '' && $func($exampleText, $needle) !== false){
			return true;
		}
	}

	return false;
}

//スペース区切りの検索語に分解する。空白の揺れは区切りの判定を狂わせるため先に均す
function parseKeywords($keyBox, $type, $mode){
	if ($keyBox === null || $keyBox === ''){
		return array();
	}

	$keyWord = preg_replace('/[　]/u', ' ', $keyBox);
	$keyWord = preg_replace('/\s\s+/u', ' ', $keyWord);
	$keyWord = preg_replace('/(^[\s]|[\s]$)/u', '', $keyWord);
	if (!keepsSymbols($type, $mode)){
		$keyWord = deleteNonIdyerinCharacters($keyWord);
	}

	//ダブルコーテーションで囲めば空白を含む語をひとつとして扱えるよう、CSVとして分解する
	$keyWords = str_getcsv($keyWord, ' ', "\"");
	if ($mode === 'perf'){
		$keyWords = array(implode(" ", $keyWords));//完全一致では空白ごと一致させる
	}

	$keyWords = array_values(array_filter($keyWords, function ($keyWord){
		return $keyWord !== null && $keyWord !== '';
	}));
	return $keyWords;
}

//全文検索と完全一致検索では記号そのものを検索したいため、検索語からも見出し語からも記号を落とさない
function keepsSymbols($type, $mode){
	return $type === 'all' || $mode === 'perf';
}

//全ての検索語に一致した見出し語のキーを返す
//$exampleIndex は makeExampleIndex() の返り値。渡すと全文検索が例文も対象にする
//返り値：$words の添字の配列
function searchEntries(array $words, array $keyWords, $type, $mode, $includeVoicing, array $exampleIndex = array()){
	if (!$keyWords){
		return array();
	}

	$exampleTexts = ($type === 'all' && isset($exampleIndex['textByWordId'])) ? $exampleIndex['textByWordId'] : array();

	//連濁形はキーワードごとに一度求めれば足りる
	$voicedKeyWords = array();
	if ($includeVoicing){
		foreach ($keyWords as $index => $singleKey){
			$voicedKeyWords[$index] = initialVoicing($singleKey);
		}
	}

	$hitsPerKeyword = array_fill(0, count($keyWords), array());
	foreach ($words as $entryKey => $singleEntry){
		$entryId = $singleEntry["entry"]["id"];
		if (!keepsSymbols($type, $mode)){
			$singleEntry["entry"]["form"] = deleteNonIdyerinCharacters($singleEntry["entry"]["form"]);
		}
		$exampleText = isset($exampleTexts[$entryId]) ? $exampleTexts[$entryId] : '';
		foreach ($keyWords as $index => $singleKey){
			if (isHit($singleEntry, $singleKey, $type, $mode, $exampleText)
				|| ($includeVoicing && isHit($singleEntry, $voicedKeyWords[$index], $type, $mode, $exampleText))){
				$hitsPerKeyword[$index][] = $entryKey;
			}
		}
	}

	$hitKeys = array_shift($hitsPerKeyword);
	foreach ($hitsPerKeyword as $singleHits){
		$hitKeys = array_intersect($hitKeys, $singleHits);
	}
	return array_values($hitKeys);//array_intersectが残す歯抜けの添字を、ページ送りが使えるよう詰め直す
}

function findEntryKeyById(array $words, $id){
	foreach ($words as $entryKey => $singleEntry){
		if ($singleEntry["entry"]["id"] === $id){
			return $entryKey;
		}
	}
	return null;
}

//ひとつの見出し語が複数の訳語欄で同じ品詞を持つことがあるため、重複を取り除いて返す
function entryPosList($singleEntry){
	$posList = array();
	if (isset($singleEntry["translations"]) && is_array($singleEntry["translations"])){
		foreach ($singleEntry["translations"] as $singleTranslation){
			$posList[$singleTranslation["title"]] = true;
		}
	}
	return array_keys($posList);
}

//語幹＋-eが動詞、語幹＋-(i)nが記述詞、名詞は語幹そのもの、という体系なので、
//見出し語から品詞を決める語尾を落とせば語幹が得られる
//仕様：https://zaslon.info/idyerin/派生/
//返り値：語幹の候補の配列
function derivationStems($wordForm, $pos){
	$stems = array($wordForm);
	if (mb_stripos($pos, "動詞") !== false && endsWith($wordForm, 'e')){
		$stems = array(substr($wordForm, 0, -1));
	}elseif (mb_stripos($pos, "記述詞") !== false && endsWith($wordForm, 'n')){
		//語幹が子音で終わる場合の(i)は語幹の一部ではないため、外した形も候補にする
		$stems = endsWith($wordForm, 'in')
			? array(substr($wordForm, 0, -1), substr($wordForm, 0, -2))
			: array(substr($wordForm, 0, -1));
	}
	$stems = array_values(array_filter($stems, 'strlen'));
	return $stems ? $stems : array($wordForm);
}

//派生を重ねる間に同じ品詞を何度も引き当てるため、絞り込んだ結果を再利用する
//固有名詞や法性記述詞のように、品詞名を含む品詞も対象にする
function affixesForPos($pos, array $affixTable){
	static $cache = array();
	if (isset($cache[$pos])){
		return $cache[$pos];
	}
	$affixes = array();
	foreach ($affixTable as $singleAffix){
		if (mb_stripos($pos, $singleAffix['pos']) !== false){
			$affixes[] = $singleAffix;
		}
	}
	return $cache[$pos] = $affixes;
}

//返り値：array(array('form' => 派生形, 'pos' => 派生後の品詞, 'description' => 説明), ...)
function makeDerivations($wordForm, $pos, array $affixTable){
	$stems = derivationStems($wordForm, $pos);

	$derivations = array();
	foreach (affixesForPos($pos, $affixTable) as $singleAffix){
		if ($singleAffix['isSuffix']){
			//接尾辞は語幹に付く。動詞の-eや記述詞の-(i)nはこのとき置き換えられる
			foreach ($stems as $singleStem){
				//カッコ内の母音は、接辞が付く語幹が子音で終わる場合にだけ現れる
				$body = endsWithVowel($singleStem) ? $singleAffix['withoutBracket'] : $singleAffix['withBracket'];
				$derivations[] = array(
					'form'        => $singleStem . substr($body, 1),
					'pos'         => $singleAffix['resultPos'],
					'description' => $singleAffix['description'],
				);
			}
		}elseif ($singleAffix['isPrefix']){
			//品詞を変えない接頭辞は語形の頭に付き、品詞を変える接頭辞は語尾を置き換えるため語幹に付く
			$bases = $singleAffix['changesPos'] ? $stems : array($wordForm);
			foreach ($bases as $singleBase){
				$body = startsWithVowel($singleBase) ? $singleAffix['withoutBracket'] : $singleAffix['withBracket'];
				$head = substr($body, 0, -1);
				$derivations[] = array(
					'form'        => $head . ($singleAffix['noVoicing'] ? $singleBase : initialVoicing($singleBase)),
					'pos'         => $singleAffix['resultPos'],
					'description' => $singleAffix['description'],
				);
			}
		}
		//接周辞：今の所存在しない
	}
	return $derivations;
}

//検索語がこの語からの派生形になりうるかを、文字列の一致だけで大まかに判定する
//接辞は語の前後にしか付かないため、何段重ねても語幹は検索語の中に連続した部分文字列として残る
//（接頭辞が付いた場合は連濁した形で残る）
//取りこぼすと結果が変わるため、判定できない場合は必ずtrueを返す
function canDerive($keyWord, array $stems){
	foreach ($stems as $singleStem){
		foreach (array($singleStem, initialVoicing($singleStem)) as $singleCandidate){
			if (stripos($keyWord, $singleCandidate) !== false){
				return true;
			}
		}
	}
	return false;
}

//見出し語として載っている形かどうか。品詞は固有名詞と名詞のように包含関係で見る
function isKnownForm(array $knownForms, $wordForm, $pos){
	$key = strtolower($wordForm);
	if (!isset($knownForms[$key])){
		return false;
	}
	foreach ($knownForms[$key] as $singleTitle){
		if (mb_stripos($singleTitle, $pos) !== false){
			return true;
		}
	}
	return false;
}

//返り値：見出し語（小文字）=> その見出し語が持つ品詞の配列
function makeKnownForms(array $words){
	$knownForms = array();
	foreach ($words as $singleEntry){
		$key = strtolower($singleEntry["entry"]["form"]);
		foreach (entryPosList($singleEntry) as $pos){
			$knownForms[$key][$pos] = true;
		}
	}
	foreach ($knownForms as $key => $posList){
		$knownForms[$key] = array_keys($posList);
	}
	return $knownForms;
}

//接辞をいくつ重ねた形まで探すか。接頭辞＋接尾辞＋格のような3段の形までを想定している
const DERIVATION_MAX_DEPTH = 3;

//この見出し語から検索語に辿り着く派生の道筋を、接辞を1段ずつ重ねながら探す
//途中の形も検索語の中に現れていなければならないので、それを満たさない枝はここで捨てる
//これをしないと段数分だけ組み合わせが増えて終わらない
//返り値：array(array(1段目の説明, 2段目の説明, ...), ...)
function findDerivationPaths($wordForm, $pos, array $affixTable, $keyWord, $maxDepth, array $knownForms){
	$paths = array();
	$queue = array(array($wordForm, $pos, array()));
	//名詞派生と動詞派生のように互いを行き来する接辞があるため、一度通った形は辿り直さない
	$visited = array($wordForm . "\0" . $pos => true);
	$keyLength = strlen($keyWord);

	for ($depth = 0; $depth < $maxDepth && $queue; $depth++){
		$nextQueue = array();
		foreach ($queue as $singleState){
			list($stateForm, $statePos, $steps) = $singleState;
			foreach (makeDerivations($stateForm, $statePos, $affixTable) as $singleDerivation){
				$derivedSteps = array_merge($steps, array($singleDerivation['description']));
				if (perfectHit($singleDerivation['form'], $keyWord)){
					$paths[] = $derivedSteps;
					continue;//一致した形に更に接辞を付けても検索語より長くなるだけ
				}
				//派生後の品詞が空の接辞は、名詞の格のようにそこで語形が閉じるもの
				if ($singleDerivation['pos'] === '' || strlen($singleDerivation['form']) >= $keyLength){
					continue;
				}
				if (stripos($keyWord, $singleDerivation['form']) === false
					&& stripos($keyWord, initialVoicing($singleDerivation['form'])) === false){
					continue;
				}
				//既に見出し語として載っている形を経由する道筋は、その見出し語から直接辿った道筋と重なる
				if (isKnownForm($knownForms, $singleDerivation['form'], $singleDerivation['pos'])){
					continue;
				}
				$visitKey = $singleDerivation['form'] . "\0" . $singleDerivation['pos'];
				if (isset($visited[$visitKey])){
					continue;
				}
				$visited[$visitKey] = true;
				$nextQueue[] = array($singleDerivation['form'], $singleDerivation['pos'], $derivedSteps);
			}
		}
		$queue = $nextQueue;
	}
	return $paths;
}

//$keyWords は parseKeywords() の返り値。どの検索語の派生形も拾う
//返り値：array(array('form' => 見出し語, 'id' => 単語ID, 'description' => 説明), ...)
function findDerivationSuggestions(array $words, array $affixTable, array $keyWords, $maxDepth = DERIVATION_MAX_DEPTH){
	$keyWords = array_values(array_filter($keyWords, 'strlen'));
	if (!$keyWords){
		return array();
	}

	$knownForms = makeKnownForms($words);

	$foundPerDepth = array();
	foreach ($words as $singleEntry){
		$wordForm = $singleEntry["entry"]["form"];
		foreach (entryPosList($singleEntry) as $pos){
			$stems = derivationStems($wordForm, $pos);
			foreach ($keyWords as $singleKey){
				//全単語分の派生形を組み立てると重いため、明らかに一致しないものは先に落とす
				if (!canDerive($singleKey, $stems)){
					continue;
				}
				foreach (findDerivationPaths($wordForm, $pos, $affixTable, $singleKey, $maxDepth, $knownForms) as $singleSteps){
					$foundPerDepth[count($singleSteps)][] = array(
						'form'        => $wordForm,
						'id'          => $singleEntry["entry"]["id"],
						'description' => implode(' の ', $singleSteps),
					);
				}
			}
		}
	}

	//段数を重ねるほど無理のある解釈になるため、同じ見出し語については最も浅い解釈だけを出す
	ksort($foundPerDepth);
	$suggestions = array();
	$shownIds = array();
	foreach ($foundPerDepth as $singleGroup){
		$idsInGroup = array();
		foreach ($singleGroup as $singleSuggestion){
			if (isset($shownIds[$singleSuggestion['id']])){
				continue;
			}
			$idsInGroup[$singleSuggestion['id']] = true;
			//別々の品詞から同じ派生形が同じ説明で得られることがあるため、提案を重複させない
			$suggestions[$singleSuggestion['id'] . "\0" . $singleSuggestion['description']] = $singleSuggestion;
		}
		foreach (array_keys($idsInGroup) as $singleId){
			$shownIds[$singleId] = true;
		}
	}
	return array_values($suggestions);
}
