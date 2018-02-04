<?php


	/**
	 * Created by Burzum.
	 * User: Arnolds
	 * Date: 28.06.16
	 * Time: 10:37
	 */
	class FrontUsers {
		private $currentUser;
		private $authStatus = false;
		public $lastError;

		public function FrontUsers() {
			if (isset($GLOBALS["_front_users"])) {
				throw new Exception("Only one instance of FrontUsers allowed");
			}
			$GLOBALS["_front_users"] = &$this;
			if (isset($_SESSION["_active_front_user"])) {
				if ($_SESSION["_active_front_user_type"] == "local") {
					if ($this->setCurrentUser($_SESSION["_active_front_user"])) {
						$this->authStatus = true;
					} else {
						$this->authStatus = false;
					}
				} else if ($_SESSION["_active_front_user_type"] == "social") {
					$this->currentUser = DataBase()->getRow("SELECT * FROM %s WHERE `id`='%s'", DataBase()->social_accounts, $_SESSION["_active_front_user"]);
					$this->authStatus = true;
				}
			}
		}

		public function getCurrentUser() {
			return $this->currentUser;
		}

		public function get($key) {
			return $this->currentUser[$key];
		}

		private function setCurrentUser($id) {
			$data = $this->findUser(array("id" => $id));
			if ($data) {
				$this->currentUser = $data;
				DataBase()->update("front_users", array("last_access" => strftime("%F %X")), array("id" => $data["id"]));
			}

			return $data;
		}

		public function findUser($filter) {
			$where_q = array();
			if (is_array($filter)) {
				foreach ($filter as $key => $val) {
					if (is_string($val) && !is_numeric($val)) $val = DataBase()->escape($val);
					$where_q[] = "`{$key}`='{$val}'";
				}
				$filter = join(" AND ", $where_q);
				unset($where_q);
			}
			$match = DataBase()->getRow("SELECT * FROM %s" . (!is_null($filter) ? " WHERE {$filter}" : "") . ' LIMIT 1', DataBase()->front_users);

			return $match;
		}

		public function getName() {
			return $this->currentUser["first_name"] . ' ' . $this->currentUser["last_name"];
		}

		public function insertUser($data) {
			if (!isset($data["password"]) || !isset($data["email"])) {
				return false;
			}
			$data["email"] = mb_strtolower($data["email"]);
			if (!$this->findUser(array("email" => $data["email"]))) {
				$data["email"] = mb_strtolower($data["email"]);
				$default = array(
					"first_name"      => "",
					"last_name"       => "",
					"email"           => "",
					"email_hash"      => "",
					"profile_url"     => "",
					"picture_url"     => "",
					"type"            => 1,
					"time_registered" => strftime("%F %X"),
					"last_access"     => "0000-00-00 00:00:00",
					"last_login"      => "0000-00-00 00:00:00",
					"blocked"         => 0,
					"deleted"         => 0,
					"secret_key"      => "",
					"password"        => ""
				);

				$data = array_merge($default, $data);
				if ($data["password"] !== false) {
					$data["password"] = password_hash($data["password"], PASSWORD_DEFAULT);
				}

				if (DataBase()->insert("front_users", $data)) {
					return DataBase()->insertid;
				} else {
					$this->lastError = "Lietotāju pievienot neizdevās. Lūdzu mēģini vēlāk.";

					return false;
				}
			} else {
				$this->lastError = "Lietotājs ar šādu e-pasta adresi jau ir reģistrēts";

				return false;
			}
		}

		public function updateUser($data) {
			if (!isset($data["id"])) {
				return false;
			} else {
				if (isset($data["email"])) {
					$data["email"] = mb_strtolower($data["email"]);
					$match = $this->findUser(array("email" => $data["email"]));
					if ($match && $match["id"] != $data["id"]) {
						$this->lastError = "Lietotājs ar šādu e-pasta adresi jau ir reģistrēts";

						return false;
					}
				}
				if ($data["password"] !== false) {
					$data["password"] = password_hash($data["password"], PASSWORD_DEFAULT);
				} else {
					unset($data["password"]);
				}
				DataBase()->update("front_users", $data, array("id" => $data["id"]));
			}

			return true;
		}

		public function removeUser($id) {
			DataBase()->update("front_users", array("deleted" => 1), array("id" => $id));

			return true;
		}

		private function validPassword($password, $hashed) {
			return password_verify($password, $hashed);
		}

		private function updatePassword($password) {
			if (password_verify($password, $this->currentUser["password"])) {
				if (password_needs_rehash($this->currentUser["password"], PASSWORD_DEFAULT)) {
					$this->currentUser["password"] = password_hash($password, PASSWORD_DEFAULT);
					DataBase()->update("front_users", array("password" => $this->currentUser["password"]), array("id" => $this->currentUser["id"]));
				}
			}
		}

		public function logout() {
			DataBase()->queryf("DELETE FROM %s WHERE `user_id`='%s' AND `session_id`='%s'", DataBase()->front_user_sessions, $_SESSION["_active_front_user"], Page()->session_id);
			$this->currentUser = null;
			$_SESSION["_active_front_user"] = false;
		}

		public function login($email, $password) {
			$user = $this->findUser(array("email" => $email));
			if (!$user || !$this->validPassword($password, $user["password"])) {
				$this->lastError = "E-pasta adrese un/vai parole ievadīta nepareizi.";

				return false;
			}
			if ($user["blocked"]) {
				$this->lastError = "Lietotājs ir bloķēts.";

				return false;
			}
			$_SESSION["_active_front_user"] = $user["id"];
			$_SESSION["_active_front_user_type"] = "local";
			$this->setCurrentUser($user["id"]);
			$this->authStatus = true;
			$this->updatePassword($password);
			DataBase()->update("front_users", array("last_login" => strftime("%F %X")), array("id" => $user["id"]));
			DataBase()->insert("front_user_sessions", array("user_id" => "local-".$user["id"], "session_id" => Page()->session_id), true);

			return true;
		}

		public function loginSocial($profile, $network) {
			$profile->picture_url = FS()->getThumb($profile->picture_url, 130, 130);
			$actualUser = DataBase()->getRow("SELECT * FROM %s WHERE `sid`='%s' AND `network`='%s'", DataBase()->social_accounts, $profile->id, $network);
			if ($actualUser) {
				DataBase()->update("social_accounts", array(
					"sid"          => $profile->id,
					"first_name"   => $profile->first_name,
					"last_name"    => $profile->last_name,
					"profile_url"  => $profile->profile_url,
					"picture_url"  => $profile->picture_url,
					"email"        => $profile->email,
					"email_hash"   => $profile->email_hash,
					"time_updated" => strftime("%F %X"),
					"network"      => $network
				), array(
					"id" => $actualUser["id"]
				));
			} else {
				DataBase()->insert("social_accounts", array(
					"sid"          => $profile->id,
					"first_name"   => $profile->first_name,
					"last_name"    => $profile->last_name,
					"profile_url"  => $profile->profile_url,
					"picture_url"  => $profile->picture_url,
					"email"        => $profile->email,
					"email_hash"   => $profile->email_hash,
					"time_added"   => strftime("%F %X"),
					"time_updated" => strftime("%F %X"),
					"network"      => $network
				), true);
				$newUsersId = DataBase()->insertid;

				if ($profile->email) {
					$actualSystemUser = DataBase()->getRow("SELECT * FROM %s WHERE `email_hash`='%s'", DataBase()->front_users, $profile->email_hash);
					if ($actualSystemUser) {
						DataBase()->insert("front_user_relations", array(
							"user_id"   => $actualSystemUser["id"],
							"social_id" => $newUsersId
						), true);
					}
				}
				$actualUser = DataBase()->getRow("SELECT * FROM %s WHERE `id`='%s'", DataBase()->social_accounts, $newUsersId);
			}
			DataBase()->insert("front_user_sessions", array("user_id" => "social-".$actualUser["id"], "session_id" => Page()->session_id), true);
			$_SESSION["_active_front_user"] = $actualUser["id"];
			$_SESSION["_active_front_user_type"] = "social";
			$this->currentUser = $actualUser;
			$this->authStatus = true;
			$profile->connected = true;

			return $profile;
		}

		public function isActive() {
			return $this->authStatus;
		}

	}


	/**
	 * @return FrontUsers
	 */
	function FrontUsers() {
		if (!isset($GLOBALS["_front_users"])) {
			new FrontUsers();
		}

		return $GLOBALS["_front_users"];
	}

	if (isset($_GET["front_user_login"])) {
		$state = FrontUsers()->login($_POST["email"], $_POST["password"]);
		if ($state) {
			$return = array("status" => "ok");
			DataBase()->insert("stats", array(
				"uid"  => $_SESSION["_active_front_user"],
				"sid"  => session_id(),
				"rid"  => 0,
				"fid"  => 0,
				"time" => strftime("%F %X"),
				"ip"   => Recipe::getClientIP(Page()->trustProxyHeaders)
			));
		} else {
			$return = array("status" => "error", "error" => FrontUsers()->lastError);
		}

		if ($_GET["front_user_login"] == "ajax") {
			die(json_encode($return));
		} else {
			$_SESSION["login_error"] = $return["error"];
			header("Location: {$_SERVER["HTTP_REFERER"]}");
			exit;
		}
	}

	if (isset($_GET["front_user_logout"])) {
		FrontUsers()->logout();
		header("Location: {$_SERVER["HTTP_REFERER"]}");
		exit;
	}