<?php


	class iDataBase {

		public $link;
		public $host;
		private $schema;
		public $prefix = 'tz_';
		public $lastResult;
		public $dostripslashes = true;
		public $silentError = false;
		public $queryCount = 0;
		public $debug = false;
		public $json_decode = false;

		function __construct($host, $user, $pass, $schema, $prefix) {
			$GLOBALS["_database"] = &$this;
			$mysqli = mysqli_init();
			if (!$mysqli) {
				die('mysqli_init failed');
			}

			if (!$mysqli->options(MYSQLI_INIT_COMMAND, 'SET AUTOCOMMIT = 1')) {
				die('1001');
			}

			if (!$mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5)) {
				die('1002');
			}

			if (!$mysqli->real_connect($host, $user, $pass, $schema)) {
				die('1003');
			}
			mysqli_set_charset($mysqli, "utf8");
			$this->link = $mysqli;
			$this->prefix = $prefix;
			$this->schema = $schema;

			$tables = $this->getRows("SHOW TABLES FROM %s LIKE '%s%%'", $this->schema, $this->prefix);
			$this->tables = array();
			foreach ($tables as $table) {
				$this->tables[] = "`" . reset($table) . "`";
				if (!isset($this->{substr(reset($table), strlen($this->prefix))})) {
					$this->{substr(reset($table), strlen($this->prefix))} = "`{$this->schema}`.`" . reset($table) . "`";
				}
			}
		}

		function query($query) {
			$thisIsSelect = false;
			$this->queryCount++;
			$this->rowsAffected = $this->insertid = null;
			if (preg_match("/^SELECT/", trim($query)) && $this->countResults) {
				$thisIsSelect = true;
				$query = preg_replace("#^SELECT#", "SELECT SQL_CALC_FOUND_ROWS", trim($query));
			}
			$this->lastResult = mysqli_query($this->link, $query, false);
			if (!$this->lastResult && !$this->silentError) {
				printf("<span style=\"display: inline-block; z-index: 999999; border: 1px solid red; background: white; color: black; padding: 5px;\">SQL Kļūda: %s (%d)</span>", mysqli_error($this->link), mysqli_errno($this->link));
				if ($this->debug) printf("<span style=\"display: inline-block; z-index: 999999; border: 1px solid red; border-top: 0px; background: white; color: black; padding: 5px;\">SQL Vaicājums: %s</span>", $query);
			}
			$this->rowsAffected = mysqli_affected_rows($this->link);
			if ($thisIsSelect && $this->countResults) {
				$result = mysqli_query($this->link, "SELECT FOUND_ROWS( )", false);
				$this->resultsFound = mysqli_fetch_row($result)[0];
			} else $this->resultsFound = 0;
			$this->countResults = false;

			return $this->lastResult;
		}

		function queryf() {
			$numargs = func_num_args();
			$arg_list = func_get_args();
			if ($numargs == 0) {
				return null;
			} else if ($numargs == 1) {
				return $this->query($arg_list[0]);
			} else {
				$args = array();
				$query = $arg_list[0];
				unset($arg_list[0]);
				foreach ($arg_list as $arg) {
					if (is_string($arg) && !is_numeric($arg)) {
						$args[] = $this->escape($arg);
					} else $args[] = $arg;
				}

				return $this->query(vsprintf($query, $args));
			}
		}

		function fetch($result = null, $type = MYSQLI_ASSOC, $prevent_ss = false) {
			if (is_null($result)) $result = $this->lastResult;
			if ($this->rows()) $row = mysqli_fetch_array($result, $type);
			if (!is_array($row)) return null;
			if ($this->json_decode === true) {
				foreach ($row as $key => $val) {
					$newVal = json_decode($val, true);
					if (json_last_error() === JSON_ERROR_NONE) $row[ $key ] = $newVal;
				}
			}
			if ($this->dostripslashes && !$prevent_ss) {
				foreach ($row as $key => $val) if (!is_array($val)) $row[ $key ] = stripslashes($val);
			}

			return $row;
		}

		function result($result = null, $row = 0, $field = 0, $prevent_ss = false) {
			if (is_null($result)) $result = $this->lastResult;
			if ($row >= 0 && mysqli_num_rows($result) > $row) {
				mysqli_data_seek($result, $row);
				$resrow = mysqli_fetch_row($result);
				if (isset($resrow[ $field ])) {
					return ($this->dostripslashes && !$prevent_ss ? stripslashes($resrow[ $field ]) : $resrow[ $field ]);
				}
			}

			return false;
		}

		function inTable($table, $where) {
			return $this->getVar("SELECT COUNT(*) FROM %s WHERE " . $where, $this->table($table)) > 0;
		}

		function getJSON($row = 0, $field = 0, $result = null) {
			if (is_null($result)) $result = $this->lastResult;

			return json_decode($this->result($result, $row, $field, true), true);
		}

		function getRow() {
			$numargs = func_num_args();
			$arg_list = func_get_args();
			if ($numargs == 0) {
				return null;
			} else if ($numargs == 1) {
				$this->query($arg_list[0]);
			} else {
				$args = array();
				$query = $arg_list[0];
				unset($arg_list[0]);
				foreach ($arg_list as $arg) {
					if (is_string($arg) && !is_numeric($arg)) {
						$args[] = $this->escape($arg);
					} else $args[] = $arg;
				}
				$this->query(vsprintf($query, $args));
			}

			return $this->rows() ? $this->fetch() : null;
		}

		function get_row() { // Atpakaļ-savienojamība
			return call_user_func_array(array($this, "getRow"), func_get_args());
		}

		function getRows() {
			$numargs = func_num_args();
			$arg_list = func_get_args();
			if ($numargs == 0) {
				return null;
			} else if ($numargs == 1) {
				$this->query($arg_list[0]);
			} else {
				$args = array();
				$query = $arg_list[0];
				unset($arg_list[0]);
				foreach ($arg_list as $arg) {
					if (is_string($arg) && !is_numeric($arg)) {
						$args[] = $this->escape($arg);
					} else $args[] = $arg;
				}
				$this->query(vsprintf($query, $args));
			}
			$return = array();
			while ($r = $this->fetch()) $return[] = $r;

			return $return;
		}

		function get_all_rows() { // Atpakaļ-savienojamība
			return call_user_func_array(array($this, "getRows"), func_get_args());
		}

		function getVar() {
			$numargs = func_num_args();
			$arg_list = func_get_args();
			if ($numargs == 0) {
				return null;
			} else if ($numargs == 1) {
				$this->query($arg_list[0]);
			} else {
				$args = array();
				$query = $arg_list[0];
				unset($arg_list[0]);
				foreach ($arg_list as $arg) {
					if (is_string($arg) && !is_numeric($arg)) {
						$args[] = $this->escape($arg);
					} else $args[] = $arg;
				}
				$this->query(vsprintf($query, $args));
			}
			$arr = $this->fetch();

			return $this->rows() ? reset($arr) : null;
		}

		function get_var() { // Atpakaļ-savienojamība
			return call_user_func_array(array($this, "getVar"), func_get_args());
		}

		function rows($result = null) {
			if (is_null($result)) $result = $this->lastResult;
			if (!$result) return null;

			return mysqli_num_rows($result);
		}

		function escape($str) {
			return mysqli_real_escape_string($this->link, $str);
		}

		function table($str) {
			if (preg_match("#`(.*)`\.`.*`#", $str, $m) && $m[1] == $this->schema) return $str;

			return "`{$this->schema}`.`{$this->prefix}{$str}`";
		}

		function insert($table, $values, $update_on_duplicate = false) {
			$x__ = array_keys($values);
			if (!is_string($x__[0])) {
				return null;
			} else {
				$sql = array();
				foreach ($values as $key => $val) {
					if (is_string($val) && !is_numeric($val)) $val = $this->escape($val);
					$sql[] = "`{$key}`='{$val}'";
				}

				return $this->query("INSERT INTO " . $this->table($table) . " SET " . join(",", $sql) .
					($update_on_duplicate ? "ON DUPLICATE KEY UPDATE " . join(",", $sql) : ""));
			}
		}

		function update($table, $values, $where = null) {
			$x__ = array_keys($values);
			if (!is_string($x__[0])) {
				return null;
			} else {
				$sql = array();
				foreach ($values as $key => $val) {
					if (is_string($val) && !is_numeric($val)) $val = $this->escape($val);
					$sql[] = "`{$key}`='{$val}'";
				}
				if (is_array($where)) {
					foreach ($where as $key => $val) {
						if (is_string($val) && !is_numeric($val)) $val = $this->escape($val);
						$where_q[] = "`{$key}`='{$val}'";
					}
					$where = join(" AND ", $where_q);
					unset($where_q);
				}

				return $this->query("UPDATE " . $this->table($table) . " SET " . join(",", $sql) . (!is_null($where) ? " WHERE {$where}" : ""));
			}
		}

		function escapeDSS($off = false) {
			if ($off) {
				$this->dostripslashes = $this->tmpDSS;
			} else {
				$this->tmpDSS = $this->dostripslashes;
				$this->dostripslashes = false;
			}
		}

		function lastError() {
			$error = new stdClass();
			$error->num = mysqli_errno($this->link);
			$error->str = mysqli_error($this->link);

			return $error;
		}

		function error() {
			$backtrace = debug_backtrace();
			die(sprintf("<pre>%s</pre>", print_r($backtrace, true)));
		}

		function __destruct() {
			@mysqli_close($this->link);
		}

		function __get($name) {
			if ($name == "insertid") return $this->link->insert_id;
			if ($name == "rowsAffected") return $this->link->affected_rows;
		}
	}


	function DataBase() {
		return $GLOBALS["_database"];
	}

