<?php

/*
		Jā nu visā būtībā šīs funkcijas ir paņemtas un pielāgotas no PHPExcel. Ovācijas PHPExcel izstrādātājiem.
	*/

/** MAX_VALUE */
define('MAX_VALUE', 1.2e308);

/** 2 / PI */
define('M_2DIVPI', 0.63661977236758134307553505349006);

/** MAX_ITERATIONS */
define('MAX_ITERATIONS', 256);

/** PRECISION */
define('PRECISION', 8.88E-016);

/** FINANCIAL_MAX_ITERATIONS */
define('FINANCIAL_MAX_ITERATIONS', 128);

/** FINANCIAL_PRECISION */
define('FINANCIAL_PRECISION', 1.0e-08);

$savedPrecision = ini_get('precision');
if ($savedPrecision < 16) {
	ini_set('precision', 16);
}

class Finances {

	public static function flattenSingleValue($value = '') {
		while (is_array($value)) {
			$value = array_pop($value);
		}

		return $value;
	} // function flattenSingleValue()

	/**
	 * PPMT
	 *
	 * Returns the interest payment for a given period for an investment based on periodic, constant payments and a constant interest rate.
	 *
	 * @param float $rate Interest rate per period
	 * @param int  $per Period for which we want to find the interest
	 * @param int  $nper Number of periods
	 * @param float $pv  Present Value
	 * @param float $fv  Future Value
	 * @param int  $type Payment type: 0 = at the end of each period, 1 = at the beginning of each period
	 * @return float
	 */
	public static function PPMT($rate, $per, $nper, $pv, $fv = 0, $type = 0) {
		$rate = self::flattenSingleValue($rate);
		$per = (int) self::flattenSingleValue($per);
		$nper = (int) self::flattenSingleValue($nper);
		$pv  = self::flattenSingleValue($pv);
		$fv  = self::flattenSingleValue($fv);
		$type = (int) self::flattenSingleValue($type);

		// Validate parameters
		if ($type != 0 && $type != 1) {
			return self::NaN();
		}
		if ($per <= 0 || $per > $nper) {
			return self::VALUE();
		}

		// Calculate
		$interestAndPrincipal = self::_interestAndPrincipal($rate, $per, $nper, $pv, $fv, $type);
		return $interestAndPrincipal[1];
	} // function PPMT()

	/**
	 * PMT
	 *
	 * Returns the constant payment (annuity) for a cash flow with a constant interest rate.
	 *
	 * @param float $rate Interest rate per period
	 * @param int  $nper Number of periods
	 * @param float $pv  Present Value
	 * @param float $fv  Future Value
	 * @param int  $type Payment type: 0 = at the end of each period, 1 = at the beginning of each period
	 * @return float
	 */
	public static function PMT($rate = 0, $nper = 0, $pv = 0, $fv = 0, $type = 0) {
		$rate = self::flattenSingleValue($rate);
		$nper = self::flattenSingleValue($nper);
		$pv  = self::flattenSingleValue($pv);
		$fv  = self::flattenSingleValue($fv);
		$type = self::flattenSingleValue($type);

		// Validate parameters
		if ($type != 0 && $type != 1) {
			return self::NaN();
		}

		// Calculate
		if (!is_null($rate) && $rate != 0) {
			return (-$fv - $pv * pow(1 + $rate, $nper)) / (1 + $rate * $type) / ((pow(1 + $rate, $nper) - 1) / $rate);
		} else {
			return (-$pv - $fv) / $nper;
		}
	} // function PMT()

	/**
	 * IPMT
	 *
	 * Returns the interest payment for a given period for an investment based on periodic, constant payments and a constant interest rate.
	 *
	 * @param float $rate Interest rate per period
	 * @param int  $per Period for which we want to find the interest
	 * @param int  $nper Number of periods
	 * @param float $pv  Present Value
	 * @param float $fv  Future Value
	 * @param int  $type Payment type: 0 = at the end of each period, 1 = at the beginning of each period
	 * @return float
	 */
	public static function IPMT($rate, $per, $nper, $pv, $fv = 0, $type = 0) {
		$rate = self::flattenSingleValue($rate);
		$per = (int) self::flattenSingleValue($per);
		$nper = (int) self::flattenSingleValue($nper);
		$pv  = self::flattenSingleValue($pv);
		$fv  = self::flattenSingleValue($fv);
		$type = (int) self::flattenSingleValue($type);

		// Validate parameters
		if ($type != 0 && $type != 1) {
			return self::NaN();
		}
		if ($per <= 0 || $per > $nper) {
			return self::VALUE();
		}

		// Calculate
		$interestAndPrincipal = self::_interestAndPrincipal($rate, $per, $nper, $pv, $fv, $type);
		return $interestAndPrincipal[0];
	} // function IPMT()

	/**
	 * RATE
	 *
	 **/
	function RATE($nper, $pmt, $pv, $fv = 0.0, $type = 0, $guess = 0.1) {

		$rate = $guess;
		if (abs($rate) < FINANCIAL_PRECISION) {
			$y = $pv * (1 + $nper * $rate) + $pmt * (1 + $rate * $type) * $nper + $fv;
		} else {
			$f = exp($nper * log(1 + $rate));
			$y = $pv * $f + $pmt * (1 / $rate + $type) * ($f - 1) + $fv;
		}
		$y0 = $pv + $pmt * $nper + $fv;
		$y1 = $pv * $f + $pmt * (1 / $rate + $type) * ($f - 1) + $fv;

		// find root by secant method
		$i  = $x0 = 0.0;
		$x1 = $rate;
		while ((abs($y0 - $y1) > FINANCIAL_PRECISION) && ($i < FINANCIAL_MAX_ITERATIONS)) {
			$rate = ($y1 * $x0 - $y0 * $x1) / ($y1 - $y0);
			$x0 = $x1;
			$x1 = $rate;
			if (($nper * abs($pmt)) > ($pv - $fv))
				$x1 = abs($x1);

			if (abs($rate) < FINANCIAL_PRECISION) {
				$y = $pv * (1 + $nper * $rate) + $pmt * (1 + $rate * $type) * $nper + $fv;
			} else {
				$f = exp($nper * log(1 + $rate));
				$y = $pv * $f + $pmt * (1 / $rate + $type) * ($f - 1) + $fv;
			}

			$y0 = $y1;
			$y1 = $y;
			++$i;
		}
		return $rate;
	}   //  function RATE()

	private static function _interestAndPrincipal($rate=0, $per=0, $nper=0, $pv=0, $fv=0, $type=0) {
		$pmt = self::PMT($rate, $nper, $pv, $fv, $type);
		$capital = $pv;
		for ($i = 1; $i<= $per; ++$i) {
			$interest = ($type && $i == 1) ? 0 : -$capital * $rate;
			$principal = $pmt - $interest;
			$capital += $principal;
		}
		return array($interest, $principal);
	} // function _interestAndPrincipal()

	private static function NaN() {
		return "#NUM!";
	}


	private static function VALUE() {
		return "#VALUE!";
	}


	// From: http://php.lv/f/topic/4064-skaitlu-parveidotajs-par-tekstu/page__hl__skait%C4%BCu__st__15

	function number2string($n) {
		//$n = (int)$n;
		if ($n < 0) $b = 'mīnus ';
		//$n = abs($n);
		if ($n == '0') {
			return 'nulle';
		}
		else {
			return $b . self::_number2stringBig($n);
		}
	}


	function _number2stringBig($n) {
		if ($n == '0') return;
		$e = array(
			array(
				'1' => 'tūkstotis',
				'2' => 'miljons',
				'3' => 'miljards',
				'4' => 'triljons',
				'5' => 'kvadriljons',
				'6' => 'kvintiljons',
				'7' => 'sekstiljons',
				'8' => 'septiljons',
				'9' => 'oktiljons',
				'10' => 'nontiljons',
				'11' => 'deciljons',
				'12' => 'undeciljons',
				'13' => 'duodeciljons',
				'14' => 'trideciljons',
				'15' => 'kvartdeciljons',
				'16' => 'kvintdeciljons',
				'17' => 'seksdeciljons',
				'18' => 'septdeciljons',
				'19' => 'oktdeciljons',
				'20' => 'nondeciljons',
			),
			array(
				'1' => 'tūkstoši',
				'2' => 'miljoni',
				'3' => 'miljardi',
				'4' => 'triljoni',
				'5' => 'kvadriljoni',
				'6' => 'kvintiljoni',
				'7' => 'sekstiljoni',
				'8' => 'septiljoni',
				'9' => 'oktiljoni',
				'10' => 'nontiljoni',
				'11' => 'deciljoni',
				'12' => 'undeciljoni',
				'13' => 'duodeciljoni',
				'14' => 'trideciljoni',
				'15' => 'kvartdeciljoni',
				'16' => 'kvintdeciljoni',
				'17' => 'seksdeciljoni',
				'18' => 'septdeciljoni',
				'19' => 'oktdeciljoni',
				'20' => 'nondeciljoni',
			),
		);
		//$n = (string)abs($n);
		$length = strlen((string)$n);
		$pow = ceil($length / 3) - 1;
		$digits = ($length - 1) % 3 + 1;

		$begin = substr($n, 0, $digits);
		$s = self::_number2stringSmall($begin);
		if ($pow > 0) {
			$end = substr($n, $digits);
			if (substr($begin, -1) == 1 && substr($begin, 0, 1) == 1) {
				$middle = $e[0][$pow];
			}
			else {
				$middle = $e[1][$pow];
			}
			$s .= ' ' . $middle;
			$s .= ' ' . self::_number2stringBig($end);
		}

		return $s;
	}


	function _number2stringSmall($n) {
		$digits = array('', 'viens', 'divi', 'trīs', 'četri', 'pieci', 'seši', 'septiņi', 'astoņi', 'deviņi');
		$preDigits = array('', 'vien', 'div', 'trīs', 'četr', 'piec', 'seš', 'septiņ', 'astoņ', 'deviņ');
		$n = (string)$n;
		$l = strlen($n);
		if ($l > 3) return false;
		if ($l == 3) {
			return ( $n{0} != '0' ? $digits[$n{0}] . ($n{0} == 1 ? ' simts' : ' simti') : '' ) . ( substr($n, 1) != '00' ? ' ' . self::_number2stringSmall(substr($n, 1)) : '' );
		}
		else {
			if ($l == 1) return $digits[$n];
			if ($n{0} == 1) {
				if ($n == '10') return 'desmit';
				return $preDigits[$n{1}] . 'padsmit';
			}
			$s = '';
			if ($n{0} != '0') $s = $preDigits[$n{0}] . 'desmit';
			if ($n{1} != '0') {
				$s .= ' ' . $digits[$n{1}];
			}
			return $s;
		}
	}


	function addBigCurrency($n) {
		$last = substr($n, -1);
		if ($last == 0) {
			return " latu";
		}
		elseif ($last == 1) {
			return " lats";
		}
		else {
			return " lati";
		}
	}


	function addSmallCurrency($n) {
		$last = substr($n, -1);
		if ($last == 0) {
			return " santīmu";
		}
		elseif ($last == 1) {
			return " santīms";
		}
		else {
			return " santīmi";
		}
	}


	function amount2words($num) {
		if ($num > 999999999999999999999999999999999999999999999999999999999999999.99) {
			return "ERROR: Skaitlis ir par lielu!";
		}
		elseif ($num < -999999999999999999999999999999999999999999999999999999999999999.99) {
			return "ERROR: Skaitlis ir par mazu!";
		}
		else {
			$parts = explode(".", str_replace(",", ".", $num));
			if (count($parts) <= 2) {
				$int = $parts[0];
				$dec = $parts[1];
				$str_resp = self::number2string($int).self::addBigCurrency($int);
				if (count($parts) == 2) {
					if ($dec > 99 || $dec < 0) {
						return "ERROR: Unknown situation!";
					}
					elseif ($dec > 0 || $dec < 10) {
						if (strlen($dec) > 1) {
							$dec = intval($dec);
						}
						else {
							$dec = $dec * 10;
						}
						$str_resp .= " un ".$dec.self::addSmallCurrency($dec);
					}
					else {
						$str_resp .= " un ".$dec.self::addSmallCurrency($dec);
					}
				} //RG: ja nepieciešams izvadīt arī 00 santīmus, kad tie nav pierakstīti! /*
				else {
					$str_resp .= " un 00".self::addSmallCurrency($dec);
				} //*/
				return $str_resp;
			}
			else {
				return "ERROR: Unknown situation!";
			}
		}
	}


}


?>
