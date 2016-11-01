<?php
function encode_email_str($string)
{
	$chars = str_split($string);
	$seed = mt_rand(0, (int)abs(crc32($string) / strlen($string)));
	foreach ($chars as $key => $char) {
		$ord = ord($char);
		if ($ord < 128) { // ignore non-ascii chars
			$r = ($seed * (1 + $key)) % 100; // pseudo "random function"
			if ($r > 60 && $char != '@') ; // plain character (not encoded), if not @-sign
			else if ($r < 45) $chars[$key] = '&#x' . dechex($ord) . ';'; // hexadecimal
			else $chars[$key] = '&#' . $ord . ';'; // decimal (ascii)
		}
	}

	return implode('', $chars);
}
