<?php
	function myErrorHandler($errno, $errstr, $errfile, $errline) {
		if (E_RECOVERABLE_ERROR === $errno) {
			echo "'catched' catchable fatal error\n";
			throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
			// return true;
		}

		return false;
	}

	set_error_handler('myErrorHandler');


	/**
	 * Class DataBase
	 */
	class DataBase {

		public $link;
		public $schema;
		public $prefix = 'tz_';
		public $lastResult;
		public $rowsAffected;
		public $insertid;
		public $dostripslashes = true;
		public $silentError = false;
		public $queryCount = 0;
		public $debug = false;
		public $json_decode = false;
		/**
		 * @var bool $countResults
		 */
		public $countResults = false;
		/**
		 * @var int $resultFound
		 */
		public $resultFound = 0;
		public $tables = array();

		function __construct($host, $user, $pass, $schema, $prefix) {
			$GLOBALS["_database"] = &$this;
			$this->link = @mysql_connect($host, $user, $pass) or $this->debug_error("not_connected");
			@mysql_select_db($schema, $this->link) or $this->debug_error("use_failed");
			@mysql_set_charset("utf8", $this->link);
			$this->schema = $schema;
			$this->prefix = $prefix;

			$tables = $this->getRows("SHOW TABLES FROM %s LIKE '%s%%'", $this->schema, $this->prefix);
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
			$this->lastResult = mysql_query($query, $this->link);
			if (!$this->lastResult) {
				$this->debug_error("query_failed", false);
			}
			if (!$this->lastResult && !$this->silentError) {
				printf('<div style="display: inline-block; z-index: 999999; background: white; color: black; padding: 5px;">SQL Kļūda: %s (%d)</div>', mysql_error(), mysql_errno());
				if ($this->debug) printf('<div style="display: inline-block; z-index: 999999; border-top: 0px; background: white; color: black; padding: 5px;">SQL Vaicājums: %s</div>', $query);
				if ($this->debug) printf('<div style="display: inline-block; z-index: 999999; border-top: 0px; background: white; color: black; padding: 5px;">Backtrce: %s</div>', $this->error());
			}
			$this->rowsAffected = mysql_affected_rows($this->link);
			if (preg_match("/^INSERT/", trim($query))) $this->insertid = mysql_insert_id($this->link);
			if ($thisIsSelect && $this->countResults) {
				$result = mysql_query("SELECT FOUND_ROWS( )", $this->link);
				$this->resultsFound = mysql_result($result, 0, 0);
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

		function fetch($result = null, $type = MYSQL_ASSOC, $prevent_ss = false) {
			if (is_null($result)) $result = $this->lastResult;
			if (is_resource($result)) $row = mysql_fetch_array($result, $type);
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
			if (is_resource($result) && $this->rows()) $r = mysql_result($result, $row, $field);
			if ($this->dostripslashes && !$prevent_ss) {
				$r = stripslashes($r);
			}

			return $r;
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

		/*
		 * @return array|null
		 */
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

		/*
		 * @return object|string|null
		 */
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
				try {
					$this->query(vsprintf($query, $args));
				} catch (Exception $e) {
					Page()->debug($e);
				}
			}
			$arr = $this->fetch();

			return $this->rows() ? reset($arr) : null;
		}

		function get_var() { // Atpakaļ-savienojamība
			return call_user_func_array(array($this, "getVar"), func_get_args());
		}

		function rows($result = null) {
			if (is_null($result)) $result = $this->lastResult;

			if (!$result) throw new Exception();

			return mysql_num_rows($result);
		}

		function escape($str) {
			return mysql_real_escape_string($str, $this->link);
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
						if (is_array($val)) {
							$where_q[] = "`{$key}` IN ('".join("','",array_map(function($n){ return DataBase()->escape($n); }, $val))."')";
						} else {
							if (is_string($val) && !is_numeric($val)) $val = $this->escape($val);
							$where_q[] = "`{$key}`='{$val}'";
						}
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
			$error = (object)array();
			$error->num = mysql_errno();
			$error->str = mysql_error();

			return $error;
		}

		function error() {
			$backtrace = debug_backtrace();

			return sprintf("<pre>%s</pre>", print_r($backtrace, true));
		}

		function __get($param) {
			return "`{$this->schema}`.`" . $param . "`";
		}

		function __destruct() {
			@mysql_close($this->link);
		}

		function debug_error($msg, $exit = true) {
			if ((isset($_SERVER["HTTP_X_CHECK"]) && $_SERVER["HTTP_X_CHECK"] == "Pulse") || isset($_GET["pulse"])) {
				print(json_encode(array(
					"server"    => "ok",
					"mysql"     => "err",
					"mysql_err" => array(
						"msg"  => $msg,
						"data" => $this->lastError()
					)
				)));
				exit;
			} else {
				header("Content-Type: text/plain; Charset=UTF-8");
				print("Ar mājas lapu noticis kaut kas nelāgs. \r\nMēs noteikti jau mēģinam atrisināt radušās problēmas, \r\nun mājas lapa drīz atkal būs pieejama.");
			}
			if ($exit) exit;
		}
	}


	/**
	 * @return DataBase Pointer to DataBase object
	 */
	function DataBase() {
		return $GLOBALS["_database"];
	}
