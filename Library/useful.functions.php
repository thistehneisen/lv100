<?php

function array_value($arr, $pos = 0) {
	$tmp = array_slice($arr, $pos, 1);
	return $tmp ? reset($tmp) : null;
}
function random_value($arr) {
	$tmp = array_slice($arr, rand(0,count($arr)-1), 1);
	return $tmp ? reset($tmp) : null;
}
function array_merge_recursive_distinct ( array &$array1, array &$array2 )
{
	$merged = $array1;

	foreach ( $array2 as $key => &$value )
	{
		if ( is_array( $value ) && isset ( $merged [$key] ) && is_array( $merged [$key] ) )
		{
			$merged [$key] = array_merge_recursive_distinct ( $merged [$key], $value );
		}
		else
		{
			$merged [$key] = $value;
		}
	}

	return $merged;
}

function array_merge_recursive_unique($array0, $array1)
{
	$arrays = func_get_args();
	$remains = $arrays;

	// We walk through each arrays and put value in the results (without
	// considering previous value).
	$result = array();

	// loop available array
	foreach($arrays as $array) {

		// The first remaining array is $array. We are processing it. So
		// we remove it from remaing arrays.
		array_shift($remains);

		// We don't care non array param, like array_merge since PHP 5.0.
		if(is_array($array)) {
			// Loop values
			foreach($array as $key => $value) {
				if(is_array($value)) {
					// we gather all remaining arrays that have such key available
					$args = array();
					foreach($remains as $remain) {
						if(array_key_exists($key, $remain)) {
							array_push($args, $remain[$key]);
						}
					}

					if(count($args) > 2) {
						// put the recursion
						$result[$key] = call_user_func_array(__FUNCTION__, $args);
					} else {
						foreach($value as $vkey => $vval) {
							$result[$key][$vkey] = $vval;
						}
					}
				} else {
					// simply put the value
					$result[$key] = $value;
				}
			}
		}
	}
	return $result;
}

if (!function_exists('glob_recursive')) {
	function glob_recursive($path, $pattern = '*', $flags = 0, $depth = 0) {
		$matches = array();
		$folders = array(rtrim($path, DIRECTORY_SEPARATOR));

		while ($folder = array_shift($folders)) {
			$matches = array_merge($matches, glob($folder.DIRECTORY_SEPARATOR.$pattern, $flags) ? glob($folder.DIRECTORY_SEPARATOR.$pattern, $flags) : array());
			if ($depth != 0) {
				$moreFolders = glob($folder.DIRECTORY_SEPARATOR.'*', GLOB_ONLYDIR) ? glob($folder.DIRECTORY_SEPARATOR.'*', GLOB_ONLYDIR) : array();
				$depth   = ($depth < -1) ? -1: $depth + count($moreFolders) - 2;
				$folders = array_merge($folders, $moreFolders);
			}
		}
		return $matches;
	}
}

function mb_str_pad($input, $pad_length, $pad_string = ' ', $pad_type = STR_PAD_RIGHT, $encoding = null)
{
	if (!$encoding) {
		$diff = strlen($input) - mb_strlen($input);
	}
	else {
		$diff = strlen($input) - mb_strlen($input, $encoding);
	}
	return str_pad($input, $pad_length + $diff, $pad_string, $pad_type);
}
function valid_personal_id($id, $country = "lv") {
	$valid = false;
	switch ($country) {
	case "lv":
		if (preg_match("#^[0-3][0-9][0-1][0-9]{3}-[0-2][0-9]{4}$#",$id)) {
			list($date,) = explode("-",$id);
			list($day, $month, $year) = str_split($date, 2);
			if (checkdate($month, $day, $year)) {
				$checksum = ((1101 - $id[0] - $id[1]*6 - $id[2]*3 - $id[3]*7 - $id[4]*9 - $id[5]*10 -
						$id[7]*5 - $id[8]*8 - $id[9]*4 - $id[10]*2) % 11);
				if ($checksum == $id[11]) $valid = true;
			}
		}
		return $valid;
	case "ee":
	case "lt":
		if (preg_match("#^[1-8][0-9]{10}$#",$id)) {
			$date = substr($id,1,6);
			list($year, $month, $day) = str_split($date, 2);
			if (checkdate($month, $day, $year)) {
				$d = 0; $e = 0;
				$b = 1; $c = 3;
				for ($i=0; $i<10; $i++) {
					$digit = $id[$i];
					$d += $digit * $b;
					$e += $digit * $c;
					$b++;
					$c++;
					if ($b == 10) $b = 1;
					if ($c == 10) $c = 1;
				}
				$d = $d % 11;
				$e = $e % 11;

				if ($d == 10) {
					if ($e == 10) $checksum = 0;
					else $checksum = $e;
				}
				else $checksum = $d;
				if ($checksum == $id[10]) $valid = true;
			}
		}
		return $valid;
	default:
		return $valid;
	}
}
function valid_company_id($id, $country = "lv") {
	$valid = false;
	switch ($country) {
	case "lv":
		if (preg_match("#^[0-9]{11}$#",$id)) $valid = true;
		return $valid;
	case "lt":
		if (preg_match("#^[0-9]{9}$#",$id)) $valid = true;
		return $valid;
	default:
		return $valid;
	}
}
