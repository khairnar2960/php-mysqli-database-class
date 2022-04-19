<?php
function getLetters($string, $length=null){
	$length = $length!==null ? $length : 10; 
	return substr($string, 0, strpos($string, " ", $length));
}

function getWords($string, $length=null){
	$length = $length!==null ? $length : 10;
	return implode(" ", array_slice(explode(" ", $string), 0, $length));
}
function getTitleWords($string, $length=null){
	return ucwords(getWords($string, $length));
}

function dateDiffNow($otherDate=null, $now="now", $offset=null){
	date_default_timezone_set("Asia/Kolkata");
	$now = strtotime($now);
	if($otherDate != null){
		$otherDate = strtotime($otherDate);
		$offset = $now - $otherDate;
	}
	if($offset != null){
		$deltaS = (int) $offset%60;
		$offset /= 60;
		$deltaM = (int) $offset%60;
		$offset /= 60;
		$deltaH = (int) $offset%24;
		$offset /= 24;
		$deltaD = ($offset > 1) ? round($offset, 0, PHP_ROUND_HALF_DOWN) : $offset;		
	} else{
		throw new Exception("Must supply otherdate or offset (from now)");
	}
	if($deltaD > 1){
		if($deltaD >= 365){
			$years = round($deltaD/365, 0, PHP_ROUND_HALF_DOWN);
			$rem_month = round(($deltaD-($years*365))/30, 0, PHP_ROUND_HALF_DOWN);
			if($years==1 && $rem_month<1){
				return "last year"; 
			}elseif($years==1 && $rem_month==1){
				return "1 year & month ago";
			}elseif($years==1 && $rem_month<12){
				return "1 year & {$rem_month} months ago";
			}elseif($years>1){
				return "{$years} years ago";
			}	
		}
		if($deltaD > 30 && date("Y")===date("Y", $otherDate)){
			return date('d-M', strtotime("$deltaD days ago"));
		}elseif ($deltaD==30){
			return "last months ago";
		}elseif ($deltaD > 30 && $deltaD < 365){
			$months = round($deltaD/30, 0, PHP_ROUND_HALF_DOWN);
			return "{$months} months ago";
		}else{
			return "{$deltaD} days ago";
		}
	}
	if($deltaD == 1){
		return "Yesterday";
	}
	if($deltaH == 1){
		return "last hour ago";
	}
	if($deltaM == 1){
		return "last min ago";
	}
	if($deltaH > 0){
		return $deltaH." hours ago";
	}
	if($deltaM > 0){
		return $deltaM." mins ago";
	}
	else{
		return "few seconds ago";
	}
}
function createSlug($string, $wordLimit=0) {
	$separator = "-";
	$quoteSeparator = preg_quote($separator, '#');
	$replace = array(
		"(\+{2})"					=> "pp",
		"&.+?;"						=> "",
		"[^\w\d _-]"				=> "",
		"\s+"						=> $separator,
		"(".$quoteSeparator.")+"	=> $separator,
	);
	$slug = strip_tags($string);
	foreach ($replace as $key => $value) {
		$slug = preg_replace('#'.$key.'#i', $value, $slug);
	}
    $slug = trim(trim(strtolower($slug), $separator));
    if ($wordLimit!=0) {
    	$slug = implode($separator, array_slice(explode($separator, $slug), 0, $wordLimit));
    }
    return $slug; 
}
// echo createSlug("hello World ++ C++   How @ Are! You| there $ is any & if #something ", 7);

// echo dateDiffNow("18-04-2022 10:51 AM");