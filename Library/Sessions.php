<?php


	class SessionSaveHandler {

		public function __construct() {
			session_set_save_handler(
				array($this, "open"),
				array($this, "close"),
				array($this, "read"),
				array($this, "write"),
				array($this, "destroy"),
				array($this, "gc")
			);
		}

		public function open($savePath, $sessionName) {
			return true;
		}

		public function close() {
			return true;
		}

		public function read($id, $attempt = 0) {
			$sess = DataBase()->getRow("SELECT `data`, `locked` FROM %s WHERE `session_id`='%s'", DataBase()->table("sessions"), $id);
			if (!$sess) return "";
			if ($sess['locked'] && $attempt < 100 && (strtotime($sess["locked"]) > time()-30)) {
				usleep(200000);

				return $this->read($id, $attempt++);
			} else if ($attempt >= 100) $this->cant_save = true;
			DataBase()->queryf("UPDATE %s SET `locked`='%s' WHERE `session_id`='%s'", DataBase()->table("sessions"), strftime("%F %X"), $id);

			return (string)$sess['data'];
		}

		public function write($id, $data) {
			if (!isset($this->cant_save) || $this->cant_save == false) DataBase()->queryf("INSERT INTO %s (`session_id`,`ip_address`,`modified`,`data`,`locked`) VALUES ('%s','%s','%s','%s','0') ON DUPLICATE KEY UPDATE `modified`='%d', `data`='%s', `locked`=''", DataBase()->table("sessions"), $id, Recipe::getClientIP(Page()->trustProxyHeaders), strftime("%F %X"), $data, strftime("%F %X"), $data);

			return true;
		}

		public function destroy($id) {
			DataBase()->queryf("DELETE FROM %s WHERE `session_id`='%s'", DataBase()->table("sessions"), $id);

			return true;
		}

		public function gc($maxlifetime) {
			$maxlifetime = "24:00:00"; // 1 dienas
			$maxlifetime_alt = "2160:00:00"; // 90 dienas
			DataBase()->queryf("DELETE FROM %1\$s WHERE (ADDTIME(`modified`,'%2\$d')<'%3\$d' AND `data`='') OR ADDTIME(`modified`,'%4\$d'<'%3\$d')", DataBase()->table("sessions"), $maxlifetime, strftime("%F %X"), $maxlifetime_alt);

			return true;
		}

	}


	new SessionSaveHandler();
