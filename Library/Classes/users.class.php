<?php


	class Permissions {
		private $perms = array();
		private $userId;
		private $permsLoaded = false;
		private $groups = array();

		function Permissions($userId, $groups) {
			$this->userId = $userId;
			$this->groups = array_map(function ($n) { return (int)$n; }, $groups);
		}

		public function can($controller, $type, $unit = 0) {
			if (is_null($controller)) {
				$controller = Page()->controller;
			}
			if (Users()->getUser($this->userId)->notfound) return false;
			if (Users()->getUser($this->userId)->isAdmin()) return true;

			if (!$this->permsLoaded) $this->load();
			if (isset($this->perms[ $controller ]) && isset($this->perms[ $controller ][ (int)$unit ]) && in_array($type, $this->perms[ $controller ][ (int)$unit ])) {
				return true;
			} else return false;
		}

		function load() {
			$dbPerms = DataBase()->getRows("SELECT * FROM %s WHERE `group` IN (%s)", DataBase()->permissions, join(",", $this->groups));
			$this->perms = array();
			foreach ($dbPerms as $row) {
				$this->perms[ $row["controller"] ][ (int)$row["unit"] ][] = $row["type"];
			}
			$this->permsLoaded = true;
		}

		function save($perms) {
			DataBase()->queryf("DELETE FROM %s WHERE `user`='%s'", DataBase()->permissions, $this->userId);
			foreach ((array)$perms as $controller => $unitPerms) {
				foreach ((array)$unitPerms as $unit => $types) {
					foreach ($types as $type) {
						if ($controller && $type) {
							DataBase()->insert("permissions", array(
								"user"       => $this->userId,
								"controller" => $controller,
								"unit"       => $unit,
								"type"       => $type
							));
						}
					}
				}
			}
		}

	}


	/**
	 * Class User
	 * @property $id User's ID
	 * @property $allowed_from Allowed IPs
	 */
	class User {
		private $userData = array(
			"notfound"    => true,
			"deleted"     => false,
			"disabled"    => false,
			"first_name"  => "user_not_found",
			"last_name"   => "",
			"phone"       => "",
			"email"       => "",
			"last_login"  => "0000-00-00 00:00:00",
			"last_access" => "0000-00-00 00:00:00",
			"level"       => 4,
			"id"          => -1
		);
		public $perms;
		private $groups = array();

		function User($data) {
			$this->userData = array_merge($this->userData, (array)$data);
			if ($this->userData["id"] > 0 && !$this->userData["deleted"]) {
				$this->userData["notfound"] = false;
				$this->groups = array_map(function ($n) { return (int)$n["group_id"]; }, (array)DataBase()->getRows("SELECT `group_id` FROM %s WHERE `user_id`='%s'", DataBase()->user_group_relations, $this->userData["id"]));
			}
			$this->perms = new Permissions($this->userData["id"], $this->groups);

			return $this;
		}

		function __get($param) {
			if ($param != "password" && $param != "pass_reset_key" && isset($this->userData[ $param ])) {
				return $this->userData[ $param ];
			} else if ($param == "allowed_from") {
				$ips = Settings()->get("allowed_ips","",$this->id);
				if (empty($ips)) $ips = array();
				return $ips;
			} else return null;
		}

		public function isValid() {
			return (!$this->userData["notfound"] && !$this->userData["deleted"] && !$this->userData["disabled"]);
		}

		public function isAdmin() {
			return $this->inGroup(2) || $this->isDev();
		}

		public function isDev() {
			return $this->inGroup(1);
		}

		public function inGroup($group) {
			return in_array($group, $this->groups);
		}

		public function validPassword($password) {
			return md5($password) === $this->userData["password"];
		}

		public function canAccessPanel() {
			return $this->isValid();
		}

		public function getName() {
			return trim(join(" ", array($this->first_name, $this->last_name)));
		}

		public function echoName() {
			print($this->getName());
		}

		public function setAccessTime() {
			$this->userData["last_access"] = date("Y-m-d H:i:s");
			if ($this->isValid()) DataBase()->update("users", array("last_access" => $this->last_access), array("id" => $this->id));

			return $this;
		}

		public function setLoginTime() {
			$this->userData["last_login"] = date("Y-m-d H:i:s");
			if ($this->isValid()) DataBase()->update("users", array("last_login" => $this->last_access), array("id" => $this->id));

			return $this;
		}

		public function canRead($controller, $unit = 0) {
			return $this->can($controller, "read", $unit);
		}

		public function canWrite($controller, $unit = 0) {
			return $this->can($controller, "write", $unit);
		}

		public function can($controller, $type, $unit = 0) {
			return $this->perms->can($controller, $type, $unit);
		}

		public function updateAllowedFrom($ips) {
			$ips = explode(",", $ips);
			$ips = array_map(function ($s) { return trim($s); }, $ips);
			$ips = array_filter($ips, function ($var) {
				if (preg_match("#^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}(\/\d{1,2})?$#", $var)) {
					return true;
				} else return false;
			});
			if ($this->isValid()) {
				Settings()->set("allowed_ips", $ips, "", $this->id, false);
			}
		}

	}


	class Users {
		private $count = 0;
		private $users = array();

		private $last_error = array(0, "");

		function Users() {
			$GLOBALS["_users"] = &$this;

			$this->setCount(1);

			return $this;
		}

		private function setCount($c) {
			$this->count = $c;
		}

		public function getActiveUser() {
			if (!isset($_SESSION["_active_user"]) || !$_SESSION["_active_user"]) {
				return new User(array("notfound" => true));
			} else return $this->getUser($_SESSION["_active_user"])->setAccessTime();
		}

		public function getUser($id) {
			$id = (string)$id;
			if (!isset($this->users[ $id ])) {
				$user = DataBase()->getRow("SELECT * FROM %s WHERE `id`='%d'", DataBase()->users, $id);
				if (!$user) return new User(array("notfound" => true));
				$this->users[ $user["id"] ] = new User($user);
				$id = $user["id"];
				unset($user);
			}

			return $this->users[ $id ];
		}

		public function findUserByEmail($email) {
			$id = array_search($email, array_map(function ($n) { return $n->email; }, $this->users));
			if (!isset($this->users[ $id ])) {
				$user = DataBase()->getRow("SELECT * FROM %s WHERE LOWER(`email`)='%s' AND `deleted`=0", DataBase()->users, mb_strtolower($email));
				if (!$user) return new User(array("notfound" => true));
				$this->users[ $user["id"] ] = new User($user);
				$id = $user["id"];
				unset($user);
			}

			return $this->users[ $id ];
		}

		public function getUsers($page = 0, $perPage = 20, &$totalPages = null) {
			DataBase()->countResults = true;
			$db_users = DataBase()->getRows("SELECT * FROM %s WHERE `deleted`=0 ORDER BY `id` ASC LIMIT %d, %d", DataBase()->users, $page * $perPage, $perPage);
			$re_users = array();
			$totalPages = ceil(DataBase()->resultsFound / $perPage);
			foreach ($db_users as $user) {
				$this->users[ $user["id"] ] = new User($user);
				$re_users[] = &$this->users[ $user["id"] ];
			}

			return $re_users;
		}

		public function logout() {
			$_SESSION["_active_user"] = false;
			setcookie("perm_adm", "", time() - 3600 * 24, Page()->subPath);
		}

		public function login($email, $password, $permanent = false) {
			$user = $this->findUserByEmail($email);
			if ($user->notfound || !$user->validPassword($password)) {
				return $this->setError(1, "E-pasta adrese un/vai parole nepareiza.");
			}
			if ($user->disabled) {
				return $this->setError(2, "Lieotājs bloķēts.");
			}
			$AddressRestriction = false;
			if (count($user->allowed_from)) {
				$AddressRestriction = true;
				foreach ($user->allowed_from as $ip) {
					if (IpUtils::checkIp(Recipe::getClientIP(Page()->trustProxyHeaders),$ip)) {
						$AddressRestriction = false;
						break;
					}
				}
			}
			if ($AddressRestriction) {
				return $this->setError(3, "Lietotājam liegta piekļuve no šīs adreses.");
			}
			$_SESSION["_active_user"] = $user->id;
			if ($permanent) setcookie("perm_adm", session_id(), time() + 3600 * 24 * 365, Page()->subPath);
			$user->setLoginTime();

			return true;
		}

		public function setUser($params) {
			if (isset($params["password"]) && !$params["password"]) {
				unset($params["password"]);
			} else $params["password"] = md5($params["password"]);

			$defaultParams = array(
				"first_name" => "",
				"last_name"  => "",
				"level"      => 4,
				"email"      => "",
				"disabled"   => 0,
				"deleted"    => 0
			);

			$oldId = $params["id"];
			unset($params["id"]);

			$params = array_merge($defaultParams, $params);
			$method = $oldId > 0 ? "update" : "insert";

			DataBase()->{$method}("users", $params, $method == "insert" ? true : array("id" => $oldId));
			if ($method == "insert") $oldId = DataBase()->insertid;

			return $this->getUser($oldId);
		}

		public function removeUser($id) {
			$user = $this->getUser($id);
			if (!$user->notfound && ActiveUser()->id != $user->id && !$user->isDev()) {
				DataBase()->update("users", array("deleted" => 1), array("id" => $id));
				$user = new User(DataBase()->getRow("SELECT * FROM %s WHERE `id`='%d'", DataBase()->users, $id));
			}

			return $user;
		}

		public function setPermissions($userId, $permissions) {
			if (!$userId) throw new Exception("\$userId cannot be empty!", 1);
			if (!is_array($permissions)) throw new Exception("\$permissions must by of type (array)!", 1);
			$user = $this->getUser($userId);
		}

		public function getLastError() {
			return ($this->last_error[0] ? $this->last_error : false);
		}

		private function setError($errno, $error) {
			$this->last_error = array($errno, $error);

			return false;
		}

	}


	/**
	 * @return Users
	 */
	function Users() {
		return $GLOBALS["_users"];
	}

	/**
	 * @return User
	 */
	function ActiveUser() {
		return Users()->getActiveUser();
	}
