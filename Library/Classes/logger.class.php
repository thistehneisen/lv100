<?php //ā

	class Logger {

		private $dont_log_headers = array(
			"Content-Length", "Origin", "Accept", "Accept-Encoding", "Accept-Language", "Accept-Charset", "Cookie", "Cache-Control", "Connection"
		);

		public function __construct(&$page) {
			$this->db = DataBase();
			$this->page = &$page;
			$GLOBALS["_logger"] = &$this;
		}

		public function log($message, $other_data = null) {
			$time = strftime("%F %X");
			$user = ActiveUSer()->id;
			if (!$user) $user = 0;
			$ip = Recipe::getClientIP(Page()->trustProxyHeaders);
			$headers = apache_request_headers();
			foreach (array_keys($headers) as $key) if (in_array($key, $this->dont_log_headers)) unset($headers[ $key ]);
			$http_headers = json_encode($headers);
			$session_id = session_id();

			DataBase()->insert("log", array(
				"time"       => $time,
				"user"       => $user,
				"ip"         => $ip,
				"headers"    => $http_headers,
				"session_id" => $session_id,
				"message"    => $message,
				"other_data" => json_encode($other_data)
			));
		}

		public function LogNDie($message, $other_data, $die_text = null) {
			$this->log($message, $die_text ? $other_data : null);
			die($die_text ? $die_text : $other_data);
		}

	}


	if (!function_exists('apache_request_headers')) {
		function apache_request_headers() {
			foreach ($_SERVER as $key => $value) {
				if (substr($key, 0, 5) == "HTTP_") {
					$key = str_replace(" ", "-", ucwords(strtolower(str_replace("_", " ", substr($key, 5)))));
					$out[ $key ] = $value;
				}
			}

			return $out;
		}
	}

	function xLog() {
		$args = func_get_args();
		$message = array_shift($args);
		if (func_num_args() > 1) {
			$od = $args;
		} else {
			$od = null;
		}
		$GLOBALS["_logger"]->log($message, $od);
	}
