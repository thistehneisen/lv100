<?php


	class Settings {
		private $settings = array();
		private $loaded = false;
		private $oldjd = false;

		function Settings() {
			$GLOBALS["_settings"] = &$this;

			return $this;
		}

		public function get($key, $language = "", $user = "0") {
			if (!$this->loaded) $this->load();
			if (!isset($this->settings[$language][$user][$key])) {
				$this->enableJSON();
				$this->settings[$language][$user][$key] = DataBase()->getVar("SELECT `value` FROM %s WHERE `language`='%s' AND `user`='%s' AND `key`='%s'", DataBase()->settings, $language, $user, $key);
				$this->disableJSON();
			}
			return $this->settings[$language][$user][$key];
		}

		public function set($key, $value, $language = "", $user = "0", $autoload = false) {
			$this->settings[$language][$user][$key] = $value;
			DataBase()->insert("settings",array(
				"user" => $user,
				"key" => $key,
				"language" => $language,
				"value" => json_encode($value),
				"autoload" => ($autoload ? 1 : 0)
			),true);
			return $this->settings[$language][$user][$key];
		}

		private function load() {
			$this->enableJSON();
			$dbSettings = DataBase()->getRows("SELECT * FROM %s WHERE `autoload`=1 AND (`language`='%s' OR `language`='') AND `user`='0'", DataBase()->settings, Page()->language);
			$this->disableJSON();

			if ($dbSettings) {
				foreach ($dbSettings as $row) {
					$this->settings[$row["language"]][$row["user"]][$row["key"]] = $row["value"];
				}
			}
			$this->loaded = true;
		}

		private function enableJSON() {
			$this->oldjd = DataBase()->json_decode;
			DataBase()->json_decode = true;
		}

		private function disableJSON() {
			DataBase()->json_decode = $this->oldjd;
		}
	}


	/**
	 * @param null|string $key
	 * @return Settings|mixed
	 */
	function Settings($key = null) {
		if (!is_null($key)) {
			// Ja norādīts $key, tad meklēsim jau gatavu ierakstu. Sākumā ar pašreizējo valodu. Pēc tam globāli.
			return $GLOBALS["_settings"]->get($key, Page()->language) ? $GLOBALS["_settings"]->get($key, Page()->language) : $GLOBALS["_settings"]->get($key);
		}
		return $GLOBALS["_settings"];
	}