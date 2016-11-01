<?php //ā


class Common {
	public $months = array(
		"en" => array(
			"1" => array("January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December")
		),
		"lv" => array(
			"1" => array("janvāris", "februāris", "marts", "aprīlis", "maijs", "jūnijs", "jūlijs", "augusts", "septembris", "oktobris", "novembris", "decembris"),
			"2" => array("janvāra", "februāra", "marta", "aprīļa", "maija", "jūnija", "jūlija", "augusta", "septembra", "oktobra", "novembra", "decembra"),
			"3" => array("janvārim", "februārim", "martam", "aprīlim", "maijam", "jūnijam", "jūlijam", "augustam", "septembrim", "oktobrim", "novembrim", "decembrim"),
			"4" => array("janvāri", "februāri", "martu", "aprīli", "maiju", "jūniju", "jūliju", "augustu", "septembri", "oktobri", "novembri", "decembri"),
			"5" => array("ar janvāri", "ar februāri", "ar martu", "ar aprīli", "ar maiju", "ar jūniju", "ar jūliju", "ar augustu", "ar septembri", "ar oktobri", "ar novembri", "ar decembri"),
			"6" => array("janvārī", "februārī", "martā", "aprīlī", "maijā", "jūnijā", "jūlijā", "augustā", "septembrī", "oktobrī", "novembrī", "decembrī"),
			"7" => array("janvārī", "februārī", "martā", "aprīlī", "maijā", "jūnijā", "jūlijā", "augustā", "septembrī", "oktobrī", "novembrī", "decembrī")
		)
	);
	var $daysofweek = array(
		"en" => array(
			"1" => array("Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday")
		),
		"lv" => array(
			"1" => array("pirmdiena", "otrdiena", "trešdiena", "ceturtdiena", "piektdiena", "sestdiena", "svētdiena"),
			"2" => array("pirmdienas", "otrdienas", "trešdienas", "ceturtdienas", "piektdienas", "sestdienas", "svētdienas"),
			"3" => array("pirmdienai", "otrdienai", "trešdienai", "ceturtdienai", "piektdienai", "sestdienai", "svētdienai"),
			"4" => array("pirmdienu", "otrdienu", "trešdienu", "ceturtdienu", "piektdienu", "sestdienu", "svētdienu"),
			"5" => array("ar pirmdienu", "ar otrdienu", "ar trešdienu", "ar ceturtdienu", "ar piektdienu", "ar sestdienu", "ar svētdienu"),
			"6" => array("pirmdienā", "otrdienā", "trešdienā", "ceturtdienā", "piektdienā", "sestdienā", "svētdienā"),
			"7" => array("pirmdien", "otrdien", "trešdien", "ceturtdien", "piektdien", "sestdien", "svētdien")
		)
	);
	var $yearFormat = array(
		"lv" => array(
			"1" => "Y. \g\a\d\s",
			"2" => "Y. \g\a\d\a",
			"3" => "Y. \g\a\d\a\m",
			"4" => "Y. \g\a\d\u",
			"5" => "\a\r Y. \g\a\d\u",
			"6" => "Y. \g\a\d\ā"
		)
	);
	private $_lang = "lv";

	function __construct($lang) {
		$this->_lang = $lang;
		for ($i=1; $i<7; $i++) {
			$this->months['en'][$i.''] = $this->months['en']['1'];
			$this->daysofweek['en'][$i.''] = $this->daysofweek['en']['1'];
		}
	}


	function getFormat($lang, $case="1") {
		switch ($lang) {
		case "en":
			return "l, F j, Y";
			break;
		case "lv":
			return "l, ".$this->yearFormat["lv"][$case]." j. F";
			break;
		case "lv-short":
			return "j. F";
			break;
		default:
			return "n/j/y";
			break;
		}
	}


	function getdate($case="1", $time=NULL, $lang=NULL) {
		if (is_string($time) && function_exists($time)) {
			$func = $time; $time = NULL;
		}
		if ($time===NULL) $time = time();
		if ($lang===NULL) $lang = $this->_lang;
		$_elang = explode("-", $lang);
		$elang = reset($_elang);
		$out = str_replace(array_merge((array)$this->daysofweek["en"]["1"], (array)$this->months["en"]["1"]), array_merge((array)$this->daysofweek[$elang][$case], (array)$this->months[$elang][$case]), date($this->getFormat($lang, "2"), $time));
		if (isset($func)) return call_user_func($func, $out);
		else return $out;
	}


	function getDuration($s, $e=null) {
		/* Find out the seconds between each dates */
		if (is_null($e)) $timestamp = $s;
		else $timestamp = $e - $s;

		/* Cleaver Maths! */
		//$years=floor($timestamp/(60*60*24*365));$timestamp%=60*60*24*365;
		//$weeks=floor($timestamp/(60*60*24*7));$timestamp%=60*60*24*7;
		//$days=floor($timestamp/(60*60*24));$timestamp%=60*60*24;
		$hrs=floor($timestamp/(60*60));$timestamp%=60*60;
		$mins=floor($timestamp/60);$secs=$timestamp%60;

		/* Display for date, can be modified more to take the S off */
		//if ($years >= 1) { $str.= $years.':'; }
		//if ($weeks >= 1) { $str.= $weeks.':'; }
		//if ($days >= 1) { $str.=$days.':'; }
		$str.=$this->leadZero($hrs).':';
		$str.=$this->leadZero($mins).':';
		$str .= $this->leadZero($secs);

		return $str;
	}


	function leadZero($n) {
		return $n < 10 ? '0'.$n : $n;
	}


	function humanFilesize($bytes, $decimals = 2) {
		$sz = ' kMGTP';
		$factor = floor((strlen($bytes) - 1) / 3);
		return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . ' ' . @($factor ? $sz[$factor]."iB" : "baiti");
	}


	function getDirectorySize($path) {
		$totalsize = 0;
		$totalcount = 0;
		$dircount = 0;
		if ($handle = opendir($path)) {
			while (false !== ($file = readdir($handle))) {
				$nextpath = $path . '/' . $file;
				if ($file != '.' && $file != '..' && !is_link($nextpath)) {
					if (is_dir($nextpath)) {
						$dircount++;
						$result = $this->getDirectorySize($nextpath);
						$totalsize += $result['size'];
						$totalcount += $result['count'];
						$dircount += $result['dircount'];
					}
					elseif (is_file($nextpath)) {
						$totalsize += filesize($nextpath);
						$totalcount++;
					}
				}
			}
		}
		closedir($handle);
		$total['size'] = $totalsize;
		$total['count'] = $totalcount;
		$total['dircount'] = $dircount;
		return $total;
	}


	static function mf($num = 0, $single_format = "%d", $multiple_format = "%d", $lang = NULL) { // Ieliek pareizas galotnes/locījumu atkarībā no skaita
		if ($lang === NULL) $lang = Page()->language;
		switch ($lang) {
		case "lv":
			if (substr($num, -2) == "11") return sprintf($multiple_format, $num);
			else if (substr($num, -1) == "1") return sprintf($single_format, $num);
				else return sprintf($multiple_format, $num);
				break;
		default:
			if ($num == 1) return sprintf($single_format, $num);
			else return sprintf($multiple_format, $num);
			break;
		}
	}


}


?>