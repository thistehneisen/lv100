<?php
	require 'useful.functions.php';

	if (!function_exists("php_syntax_error")) require("php_syntax_error.php");
	if (!function_exists("http_build_url")) require("http_build_url.php");
	if (!function_exists("encode_email_str")) require("encode_email_str.php");
	if (!function_exists("password_hash")) require("password.php");


	/**
	 * Class Page
	 * @method string fullRequestUri(array $query)
	 */
	class Page {
		public $version = "2.5.1";
		public $debug = false;
		public $host;
		public $baseHost;
		public $path;
		public $subPath;
		public $title, $siteName;
		public $fullRequestUri;
		public $reqParams;
		public $adminPath = "manage";
		public $languages = array("lv");
		public $cms_languages = array("lv");
		public $rfc_langs = array(
			"lv" => array("lv_LV.UTF-8", "lv_LV.utf8"),
			"ru" => array("ru_RU.UTF-8", "ru_RU.utf8"),
			"en" => array("en_US.UTF-8", "en_US.utf8")
		);
		public $defaultTimezone = "Europe/Helsinki";
		public $language = null;
		public $assets = array("js" => "Assets/js/", "css" => "Assets/css/");
		public $isAdminInterface = false;
		/**
		 * @var object|null $node Current loaded node
		 * @var object|null $root Current root node
		 * @var object|null $fromNode Previous node based on HTTP_REFERER
		 * @var array $roots All root nodes
		 */
		public $node, $root, $roots, $fromNode;

		/**
		 * @var array $menu
		 */
		public $menu = array();
		public $pageCurrent = 0;
		public $perPage = 20;

		/**
		 * @var string $method HTTP Request Method
		 * @var Logger $logger
		 */
		public $method, $logger;

		public $time;

		public $domains = array();
		public $forcePrimaryDomain = false;
		public $sslEnabled = false;
		public $obGzEnabled = false;
		public $aHost, $adminHost;
		public $bHost, $bPath;
		public $nodeId;
		public $controller;
		public $view;
		public $lHost;
		public $siteBaseHost, $siteBasePath;
		public $adminBaseHost, $adminBasePath, $modulePath, $moduleHost;
		private $adminContent = "AdminContent";
		private $siteContent = "SiteContent";
		public $action = "list";
		public $includePath;
		public $protocol;
		public $hostSSL, $hostNonSSL;
		public $hooks;
		public $filters;
		public $session_id;

		public $controllers = array();

		public $facebookEnabled = false;
		public $facebookMeta = array();

		private $mimeType = "text/html";
		private $charset = "UTF-8";

		private $pageInitiated;
		private $pageLoadedIn;
		private $pageFlushed;

		private $scripts = array();

		public $breadcrumbs = array();

		private $includes = array();

		public $pageKeyWord = "page";
		private $perPageKeyWord = "inpage";

		private $defaultNodeFields = array(
			"id", "title", "keywords", "controller", "subid", "view", "language",
			"description", "time_added", "time_updated", "parent", "original", "address", "type", "enabled", "tags",
			"deleted", "builtin", "sort", "inmenu", "added_by", "created_by", "slug", "content", "cover", "comments_count", "tags", "clone"
		);
		public $nodeTable = "nodes";
		private $nodeColumns = array();

		private $currentInjection;
		private $injections = array();
		private $conditions = array();
		private $navLinks = array();
		private $customMethods = array();

		/**
		 * Page constructor.
		 * @param array $options
		 * @return Page
		 */
		function __construct($options = array()) {
			$GLOBALS["_page"] = &$this;
			error_reporting(E_ALL ^ E_NOTICE);
			foreach ($options as $opt_key => $opt_val) $this->$opt_key = $opt_val;
			if (function_exists("mb_internal_encoding")) @mb_internal_encoding($this->charset);
			if (function_exists("date_default_timezone_set")) @date_default_timezone_set($this->defaultTimezone);
			if ($this->debug) {
				$startarray = explode(" ", microtime());
				$this->pageInitiated = $startarray[1] + $startarray[0];
			}

			$this->subPath = dirname($_SERVER["SCRIPT_NAME"]);

			$this->time = time();

			ob_start();

			$this->getNodeFields();
			$this->Crypter = new Crypter();

			new FS();

			$this->checkSession();
			$this->ifI81nDie();
			$this->preCheckRequest();

			register_shutdown_function(array($this, "flush"));

			$this->languages = array_keys($this->domains); // Fix

			$this->currentInjection = (object)array();

			return $this;
		}

		private function checkSession() {

			if (isset($_GET['session']) && !empty($_GET['session'])) {
				session_id($_GET['session']);
			} else if ($_COOKIE['perm_adm']) {
				session_id($_COOKIE['perm_adm']);
				setcookie("perm_adm", $_COOKIE['perm_adm'], time() + 3600 * 24 * 365, $this->subPath);
			}
			session_set_cookie_params(0, $this->subPath);
			session_name("s");
			session_start();
			$this->session_id = DataBase()->getVar("SELECT `id` FROM %s WHERE `session_id`='%s'", DataBase()->sessions, session_id());
		}

		private function parseURI() {
			$uriParts = array();

			$ue = explode("?", str_replace($this->host, "/", $this->fullRequestUri));
			$uri = reset($ue);
			if ($this->subPath != "/") $uri = preg_replace("#^" . preg_quote($this->subPath) . "#", "/", $uri);
			//$this->debug($this->subPath);
			if (preg_match("#^" . preg_quote(rtrim($this->aHost, "/")) . "(\/|$)#", $this->fullRequestUri)) {
				if (!isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != "on" && $this->sslEnabled) {
					header("Location: " . str_replace("http:", "https:", $this->fullRequestUri));
					exit;
				}
				$uriParts = explode("/", trim($uri, "/"));
				$this->isAdminInterface = true;
				if (!isset($uriParts[1])) $uriParts[1] = "index";
				if (!isset($uriParts[2])) $uriParts[2] = "list";
				@list(, $this->controller, $this->action) = $uriParts;
				if (count($uriParts) > 3) {
					$this->reqParams = array_slice($uriParts, 3);
				}
				$this->prepareAdmin($inPage = false);
				$this->language = "lv";
			} else {
				$node = null;
				$urlAnatomy = parse_url($_SERVER["REQUEST_URI"]);
				$reqUri = urldecode(trim($uri, "/") . "/");
				if ($reqUri == "/") $reqUri = "";

				if (!$this->node) {
					$node = $this->getNode(array(
						"filter"            => array(
							"address" => $reqUri,
							"<SQL>"   => '`type`!=5'
						),
						"includeHistorical" => true,
						"returnResults"     => "first"
					));
				} else $node = $this->node;
				if (!$node) {
					if ($reqUri == "/") {
						$node = array_value((array)$this->getNode(array(
							"filter" => array(
								"parent"   => 0,
								"language" => array_value(array_keys($this->domains))
							)
						)));
						header("Location: " . $node->fullAddress, true, 307);
						exit;
					}
					$allParts = explode("/", trim($reqUri, "/"));
					while ($allParts) {
						$tReqUri = join("/", $allParts) . "/";
						$node = $this->getNode(array(
							"filter"            => array(
								"address" => $tReqUri,
								"<SQL>"   => '`type`!=5'
							),
							"includeHistorical" => true,
							"returnResults"     => "first"
						));
						if ($node) {
							break;
						}
						array_pop($allParts);
					}
				}
				if ($node && $node->type == 2 && $node->data->url) {
					if (substr($urlAnatomy["path"], -1) != "/") {
						$nu = $urlAnatomy["path"] . "/" . ($urlAnatomy["query"] ? "?" . $urlAnatomy["query"] : "");
						header("Location: {$nu}", true, 307);
						exit;
					}
					$reqUri = str_replace($node->address, "", $reqUri);
					if ($node->data->url == "/" && $node->data->internal) $node->data->url = "";
					header("Location: " . ($node->data->internal ? $this->host : '') .
						$node->data->url . $reqUri, true, $node->data->code);
					exit;
				}
				if (!$this->node) {
					$node = $this->node = $this->getNode(array(
						"filter"        => array(
							"id" => $node->id
						),
						"returnResults" => "first"
					));
				}
				if ($node) {
					$this->fromNode = $this->getNodeByAddress($_SERVER["HTTP_REFERER"]);
					$this->language = $node->language;
					$this->root = $this->roots[ array_search($this->language, array_map(function ($n) { return $n->language; }, $this->roots)) ];
					$this->controller = $node->controller;
					$this->nodeId = $node->id;
					$this->view = $node->view;
					$this->breadcrumbs = $this->getBreadcrumbs();
					$this->menu = $this->getMenu();
					header("Node-Id: {$this->nodeId}");

					$su = $node->fullAddress;
					$ru = http_build_url($this->fullRequestUri, null, HTTP_URL_STRIP_AUTH | HTTP_URL_JOIN_PATH | HTTP_URL_STRIP_QUERY | HTTP_URL_STRIP_FRAGMENT);
					$rest = trim(preg_replace("#^" . preg_quote($su) . "#", "", $ru), "/");
					$uriParts = explode("/", $rest);
					if ($rest == "") $uriParts = array();
				}

				$this->reqParams = $uriParts;
				$this->prepareAdmin($inPage = true);
			}
			$this->bHost = $this->templateHost();
			$this->bPath = $this->templatePath();
			$GLOBALS["Com"] = new Common($this->language);
		}

		private function setPaths() {
			if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on") {
				$protocol = "https:";
			} else $protocol = "http:";
			$this->subPath = ($this->subPath == '/' || $this->subPath == "" ? '/' : '/' . trim($this->subPath, '/') . '/');
			if ($_SERVER["HTTP_HOST"]) {
				$this->host = "//" . $_SERVER["HTTP_HOST"];
			} else {
				$firstDomains = reset($this->domains);
				$this->host = "//" . reset($firstDomains);
			}
			foreach ($this->domains as $lng => $we) {
				$this->lHost[ $lng ] = $protocol . "//" . $we[0] . $this->subPath;
			}

			$this->fullRequestUri = $protocol . $this->host . $_SERVER['REQUEST_URI'];
			$this->host = $this->baseHost = $protocol . $this->host . $this->subPath;
			$this->hostNonSSL = preg_replace("#^https:#", "http:", $this->host);
			$this->hostSSL = preg_replace("#^http:#", "https:", $this->host);
			$this->path = $_SERVER['DOCUMENT_ROOT'] . $this->subPath;
			$this->adminHost = $this->aHost = $this->baseHost . rawurlencode($this->adminPath) . "/";
			$this->adminBaseHost = $this->baseHost . "AdminContent/";
			$this->adminPath = $this->path . "AdminContent/";
			$this->siteBaseHost = $this->baseHost . "SiteContent/";
			$this->siteBasePath = $this->path . "SiteContent/";
			$this->protocol = $protocol;

			if ($this->remoteAdminPanel) {
				$this->host = $this->frontsideURL;
				$this->aHost = $this->adminHost = $this->adminPanelURL;
				$this->adminBaseHost = $this->aHost . "AdminContent/";
				$this->siteBaseHost = $this->host . "SiteContent/";
				$this->adminPath = "";
			}

			set_include_path(get_include_path() . PATH_SEPARATOR . $this->path . 'Library');
			set_include_path(get_include_path() . PATH_SEPARATOR . $this->path . 'Library/Classes');
			set_include_path(get_include_path() . PATH_SEPARATOR . $this->path . 'AdminContent');
			set_include_path(get_include_path() . PATH_SEPARATOR . $this->path . 'SiteContent');
			$this->includePath = get_include_path();

			//if (!in_array($this->language,$this->languages)) $this->language=$this->languages[0];

			if (preg_match("#\/" . preg_quote($this->pageKeyWord) . "\/(\d+)#", $this->fullRequestUri, $matches)) {
				$this->pageCurrent = $matches[1] >= 1 ? $matches[1] - 1 : 0;
				$this->fullRequestUri = preg_replace("#\/" . preg_quote($this->pageKeyWord) . "\/\d*#", "", $this->fullRequestUri);
			}
			if (preg_match("#\/" . preg_quote($this->perPageKeyWord) . "\/(\d+|all)#", $this->fullRequestUri, $matches)) {
				//die($matches[1]);
				//die(sprintf("<pre>%s</pre>",print_r($matches,true)));
				$this->perPage = $matches[1] == "all" ? "all" : ($matches[1] >= 1 ? $matches[1] : 20);
				$this->fullRequestUri = str_replace("/" . $this->perPageKeyWord . "/" . $this->perPage, "", $this->fullRequestUri);
			}
		}

		function templatePath() {
			return ($this->isAdminInterface === true ? $this->path . $this->adminContent . "/" :
				($this->path . $this->siteContent . "/"));
		}

		function templateHost() {
			return ($this->isAdminInterface === true ? $this->adminBaseHost : $this->siteBaseHost);
		}

		function addBreadcrumb($title, $href) {
			array_push($this->breadcrumbs, array("title" => $title, "href" => $href));
		}

		function loadScript($name, $deps = null, $position = "body") {
			$file = false;
			if (is_file($this->path . $this->assets["js"] . $name . ".min.js")) {
				$file = $name . ".min.js";
			} else if (is_file($this->path . $this->assets["js"] . $name . ".js")) $file = $name . ".js";

			if ($file) {
				array_push($this->scripts, array("loaded" => false, "name" => $name, "file" => $file, "deps" => $deps, "position" => $position));
			}
		}

		function getScriptsForLoad($position, &$script = false) {
			$ret = array();
			if ($script) {
				if ($script["deps"]) {
					foreach ($script["deps"] as $dep) {
						$dep2 = &$this->scripts[ array_search($dep, array_map(function ($n) { return $n["name"]; }, $this->scripts)) ];
						if (!$dep2["loaded"]) {
							$ret[] = $this->getScriptsForLoad(null, $dep2);
						}
					}
				}
				$ret[] = $script["file"];
				$script["loaded"] = true;
			} else {
				foreach ($this->scripts as &$script) {
					if ($script["position"] == $position && !$script["loaded"]) {
						$ret[] = $this->getScriptsForLoad(null, $script);
					}
				}
			}

			return join(",", $ret);
		}

		function loadStyle($name) {
			$file = false;
			if (is_file($this->path . $this->assets["css"] . $name . ".min.css")) {
				$file = $name . ".min.css";
			} else if (is_file($this->path . $this->assets["css"] . $name . ".css")) $file = $name . ".css";

			if ($file) {
				array_push($this->scripts, array("name" => $name, "file" => $file));
			}
		}

		function setType($type) {
			$this->mimeType = $type;
		}

		function header() {
			$this->incl($this->bPath . "header.php");
		}

		function footer() {
			$this->incl($this->bPath . "footer.php");
		}

		function validateRequest() {
			if (!isset($this->_url)) {
				$this->_url = parse_url($this->fullRequestUri);
			}
			if ($this->language && !$this->isAdminInterface) {
				if (!in_array($this->_url["host"], (array)$this->domains[ $this->language ])) {
					header("Location: " . ($this->_url["scheme"] . "://" . $this->domains[ $this->language ][0] . $this->_url["path"]) . ($this->_url["query"] ? "?" . $this->_url["query"] : ""));
					exit;
				}
				if ($this->_url["host"] != $this->domains[ $this->language ][0] && $this->forcePrimaryDomain) {
					header("Location: " . ($this->_url["scheme"] . "://" . $this->domains[ $this->language ][0] . $this->_url["path"]) . ($this->_url["query"] ? "?" . $this->_url["query"] : ""));
					exit;
				}
			}
		}

		function init() {
			$this->trigger("before_init");

			$this->method = $_SERVER["REQUEST_METHOD"];
			$this->setPaths();
			$this->logger = new Logger($this);

			$this->roots = $this->getNode(array(
				"filter"       => array(
					"parent" => 0
				),
				"returnFields" => "id,fullAddress,title,description,data,language,enabled"
			));

			$this->parseURI();
			$this->trigger("before_dependicies");
			$this->buildDependicies();
			$this->trigger("after_dependicies");
			if ($_SERVER["argv"][1] == "cron" || isset($_GET["docron"])) {
				if (!$_SESSION["_active_user"]) session_destroy();
				if (!empty($_GET["docron"])) {
					$this->doCron($_GET["docron"]);
				} else $this->cronInit();

				return $this;
			} else define("WE_ARE_IN_CRON", false);
			if (WE_ARE_IN_CRON === true) return $this;

			$this->validateRequest();

			$this->trigger("before_controller");

			if ($this->isAdminInterface) {
				setlocale(LC_ALL, $this->rfc_langs[ $this->language ]);
				extract($GLOBALS, EXTR_REFS);

				//$this->cC = $this->controllerVars[ $this->controller ];
				$this->moduleHost = $this->bHost . "controllers/" . $this->controller . "/";
				if ($this->controllers[ $this->controller ]) {
					$this->modulePath = $this->controllers[ $this->controller ]->getPath();
				}
				if ($this->modulePath && is_file($this->modulePath . $this->action . ".php")) {
					$this->incl($this->modulePath . $this->action . ".php");
				} else if ($this->modulePath && $this->controllers[ $this->controller ] && is_file($this->modulePath . $this->controllers[ $this->controller ]->getDefaultView() . ".php")) {
					$this->incl($this->modulePath . $this->controllers[ $this->controller ]->getDefaultView() . ".php");
				} else if ($this->modulePath && is_file($this->modulePath . "/list.php")) {
					$this->incl($this->modulePath . "/list.php");
				} else {
					die(sprintf("404 - Not Found", print_r($this, true)));
				}
				exit;
			}

			$noNode = false;
			if (empty($this->node)) {
				$_spa = array_filter(explode("/", $this->subPath), 'strlen');
				$_cpa = array_filter(explode("/", parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH)), 'strlen');
				array_splice($_cpa, 0, count($_spa));

				if (isset($_GET["hl"]) && in_array($_GET["hl"], $this->languages)) {
					$this->language = $_GET["hl"];
				}

				if (count($_cpa) && is_file($this->bPath . $_cpa[0] . ".php")) {
					$this->reqParams = $_cpa;
					array_splice($this->reqParams, 0, 1);
					$noNode = $_cpa[0];
				} else {
					// THIS IS 4OH4
					header('HTTP/1.0 404 Not Found', true);
					$this->language = reset($this->languages);
					if (is_file($this->bPath . "404.php")) $this->incl($this->bPath . "404.php");
					die();
				}
			}
			if (!$this->language) $this->language = $this->languages[0];
			if ($this->language) {
				foreach ($this->roots as $root) {
					if ($root->language == $this->language) $this->root = $root;
				}

				if ($noNode) {
					setlocale(LC_ALL, $this->rfc_langs[ $this->language ]);
					$this->checkBrowser();
					$this->assets = array("js" => "SiteContent/assets/js/", "css" => "SiteContent/assets/css/");
					$this->menu = $this->getMenu();
					$this->incl($this->bPath . $noNode . ".php");
				}

				$this->title = $this->siteName = Settings("site_name");

				$this->facebookMeta["og:type"] = $this->root->data->facebook->type;
				$this->facebookMeta["og:image"] = $this->host . $this->root->data->facebook->image;
				$this->facebookMeta["og:site_name"] = $this->title;
				$this->facebookMeta["og:title"] = $this->root->title;
				$this->facebookMeta["og:url"] = $this->root->fullAddress;
				$this->facebookMeta["og:description"] = $this->root->description;
			}
			if ($this->node && $this->node->data->facebook) {
				$this->facebookMeta["og:type"] = $this->node->data->facebook->type;
				$this->facebookMeta["og:image"] = $this->host . $this->node->data->facebook->image;
				$this->facebookMeta["og:title"] = $this->node->title;
				$this->facebookMeta["og:url"] = $this->node->fullAddress;
				$this->facebookMeta["og:description"] = $this->node->description;
			} else {
				if ($this->node && $this->node->title) {
					$this->facebookMeta["og:type"] = "object";
					if ($this->node->data->cover) {
						$this->facebookMeta["og:image"] = $this->host . $this->node->data->cover;
					}
					$this->facebookMeta["og:title"] = $this->node->title;
					$this->facebookMeta["og:url"] = $this->node->fullAddress;
					$this->facebookMeta["og:description"] = $this->node->description;
				}
			}
			if ((!Settings()->get("site_enabled", $this->language) || (!$this->node->enabled && $this->node->id)) && ActiveUser()->canAccessPanel() !== true) {
				setlocale(LC_ALL, $this->rfc_langs[ $this->language ]);
				extract($GLOBALS, EXTR_REFS);
				if (is_file($this->bPath . "_maintenance.php")) $this->incl($this->bPath . "_maintenance.php");
				$this->incl($this->path . $this->adminContent . "/controllers/cpanel/uc.php");
				die();
			} else {
				if ($this->node->data->ssl && strpos($this->fullRequestUri, "http:") === 0 && $this->sslEnabled) {
					header("Location: " . str_replace("http:", "https:", $this->fullRequestUri));
					exit;
				}
				setlocale(LC_ALL, $this->rfc_langs[ $this->language ]);
				$this->checkBrowser();
				$this->assets = array("js" => "SiteContent/assets/js/", "css" => "SiteContent/assets/css/");
				$viewsPath = "controllers/" . $this->node->controller . "/" . $this->node->view . ".php";
				if (!is_file($this->bPath . $viewsPath)) $viewsPath = "controllers/" . $this->node->controller . "/default.php";
				if (!is_file($this->bPath . $viewsPath)) $viewsPath = $this->node->controller . ".php";
				$this->incl($this->bPath . $viewsPath);
			}
			exit;
		}

		function willRedirect() {
			$headers = headers_list();
			foreach ($headers as $header) {
				if (preg_match("#^Location: #i", $header)) {
					return true;
				}
			}

			return false;
		}

		function flush() {
			if (WE_ARE_IN_CRON === true) exit;
			$INFOTIPS = $_SESSION["infotips"];
			if (!$this->willRedirect()) unset($_SESSION["infotips"]);
			session_write_close();

			$cntrllrs = array($this->controller, "#ALL#");
			$actions = array($this->action, "#ALL#");
			foreach ($cntrllrs as $controller) {
				foreach ($actions as $action) {
					if (is_array($this->conditions[ $controller ][ $action ])) {
						foreach ($this->conditions[ $controller ][ $action ] as $condition) {
							$file = $this->controllers[ $condition["c"] ]->getPath() . $condition["f"];
							if (is_file($file) && file_exists($file) && ($_SERVER["REQUEST_METHOD"] == $condition["m"] || $condition["m"] == "#ALL#")) $this->incl($file);
						}
					}
				}
			}
			header("P3P: CP=\"NOI ADM DEV PSAi COM NAV OUR OTRo STP IND DEM\"");

			if ($this->mimeType != 'text/html') {
				header("Content-Type: {$this->mimeType}" . (strpos($this->mimeType, "text/") > -1 ? '; Charset=' . $this->charset : ''));
				exit;
			}
			extract($GLOBALS, EXTR_REFS);
			$str = ob_get_clean();
			$str = phpQuery::newDocumentHTML($str);

			if (is_array($this->injections)) {
				foreach ($this->injections as $key => $injection) {
					if ($injection->prepend === true) {
						pq($injection->selector)->prepend($injection->content);
					} else pq($injection->selector)->append($injection->content);
				}
			}

			if (!$this->isAdminInterface) {
				$title = ($this->node->title && $this->node->parent ? $this->node->title . " — " : "") . $this->title;
				if (!$this->node) $this->node = (object)array();
				if (!$this->node->data) $this->node->data = (object)array();

				if (!isset($this->noTitle) || $this->noTitle == false) pq("title")->text($title);
				pq("html:first")->attr("lang", $this->language);
				pq("meta[name=description]")->attr("content", $this->node->description ? $this->node->description : ($this->root->description ? $this->root->description : Settings("site_description")));
				pq("meta[name=keywords]")->attr("content", $this->node->data->keywords ? $this->node->data->keywords : ($this->root->data->keywords ? $this->root->data->keywords : Settings("site_keywords")));
				if (isset($this->node->data->noindex) && $this->node->data->noindex) {
					pq("head:first")->prepend('<meta name="ROBOTS" content="NOINDEX"/>');
				}
				pq("body:first")->attr("data-node-id", $this->nodeId);

				foreach (pq("[src][data-local]") as $loc) {
					pq($loc)->attr("src", Page()->host . pq($loc)->attr("src"));
				}
				foreach (pq("[href][data-local]") as $loc) {
					pq($loc)->attr("href", Page()->host . pq($loc)->attr("href"));
				}

				$scriptsLineHead = $this->getScriptsForLoad("head");
				$scriptsLineBody = $this->getScriptsForLoad("body");
				$scriptsLinePrefix = $this->assets["js"];
				if ($scriptsLineHead) pq("head:first")->append('<script type="text/javascript" src="' . $this->host . 'getScripts/?prefix=' . $scriptsLinePrefix . '&files=' . urlencode($scriptsLineHead) . '"></script>');
				if ($scriptsLineBody) pq("body:first")->append('<script type="text/javascript" src="' . $this->host . 'getScripts/?prefix=' . $scriptsLinePrefix . '&files=' . urlencode($scriptsLineBody) . '"></script>');

				if ($this->facebookMeta && is_array($this->facebookMeta) && $this->facebookEnabled) {
					if (!$this->facebookMeta["og:description"]) {
						$this->facebookMeta["og:description"] = ($this->root->description ? $this->root->description : Settings("site_description"));
					}
					foreach ($this->facebookMeta as $property => $content) {
						pq("head:first")->prepend("<meta property=\"" . $property . "\" content=\"" . htmlspecialchars($content) . "\"/>");
					}
				}
				if ($gacode = Settings("ga_code")) {
					pq("body")->append('<scr' . 'ipt type="text/javascript">var _gaq=_gaq||[];_gaq.push(["_setAccount","' . $gacode . '"]),_gaq.push(["_trackPageview"]),function(){var t=document.createElement("script");t.type="text/javascript",t.async=!0,t.src=("https:"==document.location.protocol?"https://":"http://")+"stats.g.doubleclick.net/dc.js";var e=document.getElementsByTagName("script")[0];e.parentNode.insertBefore(t,e)}();</script>');
				}

				if (Settings("favicon")) {
					if (!pq('link[rel="icon"]')->attr("href", $this->host . Settings("favicon"))->length) {
						pq("head:first")->prepend('<link rel="icon" type="image/png" href="' . Settings("favicon") . '"/>');
					}
				}

				$this->trigger("flush_filter", $str);


				$hrefs = pq("a");
				foreach ($hrefs as $href) {
					$href = pq($href);
					if (!$this->isInnerLink($href->attr("href")) && !preg_match("#^mailto:#", $href->attr("href"))) {
						$href->attr("target", "_blank");
					}
					/*if (preg_match("/(?:mailto\:)([a-z0-9]+[_a-z0-9\.-]*[a-z0-9]+@[a-z0-9-]+(?:\.[a-z0-9-]+)*(?:\.[a-z]{2,4}))/i", $href->attr("href"), $matches)) {
						$mailto = json_encode(encode_email_str("mailto:"));
						$var = substr(md5(uniqid() . "a"), rand(0, 20), 6);
						$var2 = substr(md5(uniqid() . "b"), rand(0, 20), 3);
						$address1 = json_encode(encode_email_str($matches[1]));
						if (preg_match("/(?:mailto\:)?([a-z0-9]+[_a-z0-9\.-]*[a-z0-9]+@[a-z0-9-]+(?:\.[a-z0-9-]+)*(?:\.[a-z]{2,4}))/i", $href->html(), $matches)) {
							$address2 = json_encode(encode_email_str($matches[1]));
						} else {
							$address2 = json_encode($href->html());
						}
						$html = <<<EOV
<script type="text/javascript">
var i={$mailto}, a{$var}={$address1}, d={$address2}, x="a", z="/", c{$var2}="hr"+"ef";
document.write('<'+x+' '+c{$var2}+'="'+i+a{$var}+'" class="{$href->attr("class")}">'+d+'<'+z+x+'>');
</script><noscript>{{Antiharvest email protection notice}}</noscript>
EOV;
						$href->replaceWith($html);
					}*/
				}
				/*$str = preg_replace_callback('/[a-z0-9]+[_a-z0-9\.-]*[a-z0-9]+@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})/i', function ($matches) {
					$mailto = json_encode(encode_email_str("mailto:"));
					$var = substr(md5(uniqid() . "a"), rand(0, 20), 6);
					$var2 = substr(md5(uniqid() . "b"), rand(0, 20), 3);
					$address1 = json_encode(encode_email_str($matches[0]));
					$address2 = json_encode(encode_email_str($matches[0]));
					$html = <<<EOV
<script type="text/javascript">
var i={$mailto}, a{$var}={$address1}, d={$address2}, x="a", z="/", c{$var2}="hr"+"ef";
document.write('<'+x+' '+c{$var2}+'="'+i+a{$var}+'">'+d+'<'+z+x+'>');
</script><noscript>{{Antiharvest email protection notice}}</noscript>
EOV;

					return $html;
				}, $str);*/

				$rows = DataBase()->getRows("SELECT `text`, `translate` FROM %s WHERE `language`='%s' AND `file`='front'", DataBase()->table("translate"), $this->language);
				foreach ($rows as $row) {
					ob_start();
					eval('?>' . $row['translate'] . '<?php ');
					$row['translate'] = ob_get_clean();
					$row["text2"] = "{" . $row["text"] . "}";
					$last_pos = null;
					while (($last_pos = strpos($str, $row['text2'], $last_pos ? $last_pos + strlen($row['text2']) : 0)) !== false) $str = str_replace($row['text2'], $row['translate'] ? htmlspecialchars($row['translate']) : $row['text2'], $str);
					$last_pos = null;
					while (($last_pos = strpos($str, $row['text'], $last_pos ? $last_pos + strlen($row['text']) : 0)) !== false) $str = str_replace($row['text'], $row['translate'] ? $row['translate'] : $row['text'], $str);
				}

				if ($this->node->data->head_html) {
					$str = str_replace("</head>", "{$this->node->data->head_html}</head>", $str);
				}

				if ($this->node->data->body_html) {
					$str = str_replace("</body>", "{$this->node->data->body_html}</body>", $str);
				}
			} else {
				$title = Settings("cms_title") . " CMS";
				if (!isset($this->noTitle) || $this->noTitle == false) pq("title")->text($title);

				pq("nav.sitenav ul.sitenavpart")->append($this->getNav());
				pq("body:first")->attr("lang", $this->language);
				pq("body:first")->attr("data-user", ActiveUser()->id);

				$scriptsLineHead = $this->getScriptsForLoad("head");
				$scriptsLineBody = $this->getScriptsForLoad("body");
				$scriptsLinePrefix = $this->assets["js"];

				if ($scriptsLineHead) pq("head:first")->append('<script type="text/javascript" src="' . $this->host . 'getScripts/?prefix=' . $scriptsLinePrefix . '&files=' . urlencode($scriptsLineHead) . '"></script>');
				if ($scriptsLineBody) pq("body:first")->append('<script type="text/javascript" src="' . $this->host . 'getScripts/?prefix=' . $scriptsLinePrefix . '&files=' . urlencode($scriptsLineBody) . '"></script>');

				if (is_array($INFOTIPS) && count($INFOTIPS)) {
					foreach ($INFOTIPS as $k => $it) {
						if (!isset($it[3])) {
							$it[2] = "";
						}
						if (!isset($it[3])) {
							$it[3] = true;
						}
						$it[4] = false;
						$tip = call_user_func_array(array($this, "cmsInfotip"), $it);
						$str[".body > .block"]->prepend($tip);
					}
				}

				pq("title:first")->after('<script type="text/javascript" src="' . $this->host . 'cms.' . $this->language . '.I81n.js"></script>');
				$rows = DataBase()->getRows("SELECT `text`, `translate` FROM %s WHERE `language`='%s' AND `file`='cms'", DataBase()->table("translate"), $this->language);
				foreach ($rows as $row) {
					ob_start();
					eval('?>' . $row['translate'] . '<?php ');
					$row['translate'] = ob_get_clean();
					$row["text2"] = "{" . $row["text"] . "}";
					$last_pos = null;
					while (($last_pos = strpos($str, $row['text2'], $last_pos ? $last_pos + strlen($row['text2']) : 0)) !== false) $str = str_replace($row['text2'], $row['translate'] ? htmlspecialchars($row['translate']) : $row['text2'], $str);
					$last_pos = null;
					while (($last_pos = strpos($str, $row['text'], $last_pos ? $last_pos + strlen($row['text']) : 0)) !== false) $str = str_replace($row['text'], $row['translate'] ? $row['translate'] : $row['text'], $str);
				}
			}

			if ($this->debug) {
				$endarray = explode(" ", microtime());
				$this->pageFlushed = $endarray[1] + $endarray[0];
				$this->pageLoadedIn = round($this->pageFlushed - $this->pageInitiated, 5);
				$str = str_replace("</body>", "<!-- " . $this->pageLoadedIn . " seconds with " . DataBase()->queryCount . " queries -->\r\n\t</body>", $str);
			}

			$this->filter("filter_output", $str);

			ignore_user_abort(true);
			header("Content-Type: {$this->mimeType}; Charset={$this->charset}");
			header("Connection: close");
			$str = str_replace(array("\t"), array(""), $str);
			if ($this->obGzEnabled && stripos($_SERVER['HTTP_ACCEPT_ENCODING'], "gzip") !== false) {
				header('Content-Encoding: gzip');
				$str = gzencode($str, 5);
			}
			header('Content-Length: ' . strlen($str));
			echo $str;

			ob_end_flush();
			flush();

			// Check for table optimization
			$last__opt = Settings()->get("db_tbl_opt");
			if ($last__opt < strtotime("-2 hours")) {
				$resp = $this->optimizeTables();
				$last__opt = time();
				Settings()->set("db_tbl_opt", $last__opt);
				Settings()->set("db_tbl_list", array("tables" => DataBase()->tables, "mysql_status" => $resp));
			}
			$last__chk = Settings()->get("db_tbl_chk");
			if ($last__chk < strtotime("-10 minutes")) {
				$resp = $this->checkTables();
				$last__chk = time();
				Settings()->set("db_tbl_chk", $last__chk);
				Settings()->set("db_tbl_chk_data", array("tables" => DataBase()->tables, "mysql_status" => $resp));
			}
		}

		private function prepareAdmin($inPage = false) {
			$this->initAdminInterface();
			$this->title = $this->getOption("general_title_{$this->language}");
			if (!$inPage) {
				$this->assets = array("js" => "AdminContent/js/", "css" => "AdminContent/css/");
				if (!ActiveUser()->isValid()) {
					$this->buildDependicies();
					$this->bPath = $this->path . "AdminContent/";
					extract($GLOBALS, EXTR_REFS);
					require "controllers/users/login.php";
					exit;
				}
			}
		}

		function vt($tag, $params) {
			return str_replace(array_keys((array)$params), array_values((array)$params), $this->t($tag));
		}

		/*** NEW ***/
		function t($tag) {
			$t = DataBase()->getVar("SELECT `translate` FROM %s WHERE `language`='%s' AND `text`='%s'", DataBase()->table("translate"), $this->language, $tag);
			if (!$t) {
				$t = $tag;
			}

			return $this->peval($t);
		}

		/*** OLD ***/
		function getTranslate($tag) {
			return $this->t($tag);
		}

		function peval($t) {
			ob_start();
			$x = eval('?>' . $t . '<?php ');
			$t = ob_get_clean();

			return $t;
		}

		function uploadFile($tmpFile, $originalFileName) {
			$oldUMask = umask(0);
			$returnVar = false;
			$uplDir = "Uploads/" . date("Y") . "/" . date("m") . "/" . date("d") . "/";
			if (!is_dir($this->path . $uplDir)) mkdir($this->path . $uplDir, 0777, true);
			$ext = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));
			$fileName = base_convert(time(), 10, 16) . "-1";
			while (is_file($this->path . $uplDir . $fileName . "." . $ext)) {
				$fileName = preg_replace_callback("#-(\d*)$#", create_function(
					'$m',
					'return "-".$m+1;'
				), $fileName);
			}
			if (move_uploaded_file($tmpFile, $this->path . $uplDir . $fileName . "." . $ext)) $returnVar = $uplDir . $fileName . "." . $ext;
			umask($oldUMask);

			return $returnVar;
		}

		function copyFile($tmpFile, $forceExtension = false) {
			$oldUMask = umask(0);
			$returnVar = false;
			$uplDir = "Uploads/" . date("Y") . "/" . date("m") . "/" . date("d") . "/";
			if (!is_dir($this->path . $uplDir)) mkdir($this->path . $uplDir, 0777, true);
			$ext = strtolower(pathinfo($tmpFile, PATHINFO_EXTENSION));
			if ($forceExtension) $ext = strtolower($forceExtension);
			$fileName = base_convert(time(), 10, 16) . "-1";
			$i = 1;
			while (is_file($this->path . $uplDir . $fileName . "." . $ext)) {
				$fileName = base_convert(time(), 10, 16) . "-" . $i++;
			}
			if (copy($tmpFile, $this->path . $uplDir . $fileName . "." . $ext)) $returnVar = $uplDir . $fileName . "." . $ext;
			umask($oldUMask);

			return $returnVar;
		}

		function plUpload($tmpFile, $forceExtension = false) {
			$oldUMask = umask(0);
			$returnVar = false;
			$uplDir = "Uploads/" . date("Y") . "/" . date("m") . "/" . date("d") . "/";
			if (!is_dir($this->path . $uplDir)) mkdir($this->path . $uplDir, 0777, true);
			$ext = strtolower(pathinfo($tmpFile, PATHINFO_EXTENSION));
			if ($forceExtension) $ext = strtolower($forceExtension);
			$fileName = base_convert(time(), 10, 16) . "-1";
			$i = 1;
			while (is_file($this->path . $uplDir . $fileName . "." . $ext)) {
				$fileName = base_convert(time(), 10, 16) . "-" . $i++;
			}
			if (rename($tmpFile, $this->path . $uplDir . $fileName . "." . $ext)) $returnVar = $uplDir . $fileName . "." . $ext;
			umask($oldUMask);

			return $returnVar;
		}

		function getNodeFields() {
			$columns = DataBase()->getRows("SHOW FULL COLUMNS FROM %s", DataBase()->{$this->nodeTable});
			$this->nodeSelectableFields = array();
			foreach ($columns as $column) {
				$this->nodeColumns[ $column["Field"] ] = $column;
				$this->nodeSelectableFields[] = $column["Field"];
			}

			return $this->nodeColumns;
		}

		function setNodeField($field) {
			if (empty($field["Field"]) || empty($field["Type"])) {
				$this->error("setNodeField: parametri 'Field' un 'Type' ir obligāti!");
			}
			if (in_array($field["Field"], $this->defaultNodeFields)) {
				$this->error("setNodeField: parametrs 'Field' ir vienāds ar kādu primāro vienību. Tas nevar tikt labots.");
			}
			$oldField = $this->nodeColumns[ $field["Field"] ];
			$update = false;
			if ($oldField) {
				foreach ($field as $key => $val) {
					if ($oldField[ $key ] != $val && !$update) {
						$update = true;
					}
				}
			} else $update = true;
			if ($update) {
				if (!$oldField) {
					DataBase()->query("ALTER TABLE " . DataBase()->{$this->nodeTable} . " ADD `" . DataBase()->escape($field["Field"]) . "` " . DataBase()->escape($field["Type"]) . " NOT NULL DEFAULT '" . DataBase()->escape($field["Default"]) . "' COMMENT '" . DataBase()->escape($field["Comment"]) . "'");
				} else DataBase()->query("ALTER TABLE " . DataBase()->{$this->nodeTable} . " CHANGE  `" . DataBase()->escape($field["Field"]) . "` `" . DataBase()->escape($field["Field"]) . "` " . DataBase()->escape($field["Type"]) . " NOT NULL DEFAULT '" . DataBase()->escape($field["Default"]) . "' COMMENT '" . DataBase()->escape($field["Comment"]) . "'");
				$this->getNodeFields();
			}
		}

		function remNodeField($fieldName) {
			if (in_array($fieldName, $this->defaultNodeFields)) {
				$this->error("setNodeField: parametrs 'Field' ir vienāds ar kādu primāro vienību. Tas nevar tikt labots.");
			}
			$oldField = $this->nodeColumns[ $fieldName ];
			if ($oldField) {
				DataBase()->queryf("ALTER TABLE %s DROP `%s`", DataBase()->table($this->nodeTable), $fieldName);
			}
		}

		function incl($file) {
			if (is_file($file)) {
				if (!$this->isAdminInterface) $this->includes[] = basename($file);
				extract($GLOBALS, EXTR_REFS);
				include $file;
			}
		}

		function widget($widget, $params = null) {
			$widgetFile = $this->bPath . "widgets/" . $widget . ".php";
			if (!is_file($widgetFile)) $widgetFile = $this->bPath . "widget." . $widget . ".php";
			if (is_file($widgetFile)) {
				$this->includes[] = basename($widgetFile);
				extract($GLOBALS, EXTR_REFS);
				if (!is_null($params) && is_array($params)) extract($params, EXTR_REFS);
				include $widgetFile;
			}
		}

		function permTo($action, $controller = null, $uid = null) {
			// TODO: Permissions
			return true;
		}

		function logs($type = null, $message) {
			if (!ActiveUser()->isDev()) {
				$time = date("Y-m-d H:i:s");
				$user = ActiveUser()->id;
				$ip = Recipe::getClientIP(Page()->trustProxyHeaders);
				$session_id = session_id();
				$data = array("address" => $this->fullRequestUri, "agent" => $_SERVER['HTTP_USER_AGENT']);
				$finalMessage = ($type ? $type . chr(32) : '') . $this->getTranslate('{{User}}') . chr(32) . ActiveUser()->getName() . chr(32) . ': ' . $message;
				DataBase()->insert("log", array(
					"time"       => $time,
					"user"       => $user,
					"ip"         => $ip,
					"session_id" => $session_id,
					"message"    => $finalMessage,
					"other_data" => json_encode($data)
				));
			}
		}

		function buildDependicies() {
			ob_start();
			extract($GLOBALS, EXTR_REFS);
			$controllers = glob($this->adminPath . "controllers/[^#]*", GLOB_ONLYDIR);
			foreach ($controllers as $controller) {
				$this->currentController = basename($controller);
				if (is_file($controller . "/config.php")) {
					$this->incl($controller . "/config.php");
				}
				if ($this->controllers[ $this->currentController ]) {
					$this->controllers[ $this->currentController ]->setPaths($controller . "/");
				}
			}
			$controllers = glob($this->siteBasePath . "controllers/*", GLOB_ONLYDIR);
			foreach ($controllers as $controller) {
				if (is_file($controller . "/config.php")) {
					$this->incl($controller . "/config.php");
				}
			}
			$this->currentController = false;
			ob_clean();
		}

		function initAdminInterface() {
			define("NAVBAR_ITEM", 2 ^ 32);
		}

		function pagingWithPerPage($settings) {
			$defSettings = array(
				"pages"           => 1,
				"delta"           => 3,
				"url"             => $this->fullRequestUri,
				"page"            => '<a href="%1$s" class="actionbutton">%2$s</a>',
				"active"          => '<a href="%1$s" class="actionbutton green">%2$d</a>',
				"prev"            => '<a href="%1$s" class="actionbutton" %3$s>Atpakaļ</a>',
				"next"            => '<a href="%1$s" class="actionbutton" %3$s>Uz priekšu</a>',
				"echo"            => true,
				"pageSets"        => array(20, 50, 100, 200, 500),
				"inPerPage"       => true,
				"perPageLabel"    => "Vienā lapā",
				"allEntriesLabel" => "Visi ieraksti",
				"allEntries"      => false
			);
			foreach ((array)$settings as $key => $val) $defSettings[ $key ] = $val;
			$thisWillBeEchoed = $defSettings["echo"];
			$defSettings["echo"] = false;

			$up = explode("?", str_replace("%", "%%", $defSettings['url']));
			$up[0] = rtrim($up[0], "/") . "/{$this->perPageKeyWord}/%s/";
			$defSettings['url'] = join("?", $up);

			$str = '<div class="pp-nav-outer"><div class="dropdown right" tabindex="0" style="float: right;"><span>' . ($this->perPage == "all" ? $defSettings["allEntriesLabel"] : $defSettings["perPageLabel"] . ': ' . $this->perPage) . '</span><ul>';
			foreach ($defSettings["pageSets"] as $pp) {
				$str .= '<li><a href="' . sprintf($defSettings['url'], $pp) . '">' . $defSettings["perPageLabel"] . ': ' . $pp . '</a></li>';
			}
			if ($defSettings["allEntries"]) $str .= '<li><a href="' . sprintf($defSettings['url'], "all") . '">' . $defSettings["allEntriesLabel"] . '</a></li>';
			$defSettings['url'] = sprintf($defSettings['url'], $this->perPage);
			$str .= '</ul></div><nav class="pagger" style="float: left;">' . $this->paging($defSettings) . '</nav></div>';

			if ($thisWillBeEchoed) {
				echo $str;
			} else return $str;
		}

		function paging($settings) {
			$defSettings = array(
				"pages"            => 1,
				"delta"            => 3,
				"url"              => $this->fullRequestUri,
				"page"             => '<a href="%1$s" class="actionbutton">%2$s</a>',
				"active"           => '<a href="%1$s" class="actionbutton green">%2$d</a>',
				"prev"             => '<a href="%1$s" class="actionbutton" %3$s>Atpakaļ</a>',
				"next"             => '<a href="%1$s" class="actionbutton" %3$s>Uz priekšu</a>',
				"dontShowInactive" => false,
				"echo"             => true
			);
			foreach ((array)$settings as $key => $val) $defSettings[ $key ] = $val;

			$up = explode("?", str_replace("%", "%%", $defSettings['url']));
			$up[0] = rtrim($up[0], "/") . "/{$this->pageKeyWord}/%d/";
			$defSettings['url'] = join("?", $up);

			if ($defSettings['pages'] > 1) {
				$str = "";
				$page = $this->pageCurrent + 1;
				if ($page > $defSettings['pages']) $page = $defSettings['pages'];
				$start = $page - $defSettings['delta'];
				if ($page > ($defSettings['pages'] - $defSettings['delta'])) $start -= ($defSettings['delta'] - (($defSettings['pages']) - $page));
				if ($start <= 0) $start = 1;
				$end = $page + $defSettings['delta'];
				if ($page < $defSettings['delta']) $end += ($defSettings['delta'] - $page);
				if ($end >= $defSettings['pages']) $end = $defSettings['pages'];

				if ($page > 1 || !$defSettings['dontShowInactive']) {
					$str .= sprintf($defSettings['prev'], sprintf($defSettings['url'], $page - 1), $page - 1, $page <= 1 ? "disabled" : "");
				}
				if ($start > 1) {
					$start++;
					$str .= sprintf($defSettings['page'], sprintf($defSettings['url'], 1), 1);
					$str .= sprintf($defSettings['page'], sprintf($defSettings['url'], $start - 2), "..");
				}
				if ($end < ($defSettings['pages'])) {
					$end--;
				}
				for ($i = $start; $i <= $end; $i++) {
					$str .= sprintf($defSettings[ ($page == $i ? 'active' : 'page') ], sprintf($defSettings['url'], $i), $i);
				}
				if ($end < ($defSettings['pages'] - 1)) {
					$str .= sprintf($defSettings['page'], sprintf($defSettings['url'], $end + 2), "..");
					$str .= sprintf($defSettings['page'], sprintf($defSettings['url'], $defSettings['pages']), $defSettings['pages']);
				}
				if ($page < $defSettings['pages'] || !$defSettings['dontShowInactive']) {
					$str .= sprintf($defSettings['next'], sprintf($defSettings['url'], $page + 1), $page + 1, $page >= $defSettings['pages'] ? "disabled" : "");
				}

				if ($defSettings['echo']) {
					echo $str;
				} else return $str;
			}
		}

		function dateMySQLToCalendarInput($date) {
			list ($year, $month, $day) = explode("-", array_value(explode(" ", $date)));

			return $year != 0 ? "{$day} / {$month} / {$year}" : '';
		}

		function dateCalendarInputToMySQL($date) {
			list ($day, $month, $year) = explode("/", $date);

			return $year != 0 ? trim($year) . "-" . trim($month) . "-" . trim($day) : '';
		}

		function isInnerLink($link) {
			if (preg_match("#^[\#]#", $link)) return true;
			$host = preg_replace("#^http[s]?\:#i", "", $this->host);
			$link = preg_replace("#^http[s]?\:#i", "", $link);

			return strpos($link, $host) === 0 || !preg_match("#[^\/]*\:#", $link);
		}

		function urlencode($u) {
			return preg_replace_callback("#([\/]+|[\-]+)#", create_function('$m', 'return mb_substr($m[0],0,1);'),
				str_replace(array("%2F", "%3F", "%20", "+", "%23", "%40"),
					array("/", "", "-", "-", "-", "-"), urlencode(mb_strtolower($u))));
		}

		function makeslug($u) {
			return preg_replace_callback("#([\/]+|[\-]+)#", create_function('$m', 'return mb_substr($m[0],0,1);'),
				str_replace(array("%2F", "%3F", "%20", "+", "%23"),
					array("/", "", "-", "-", "-"), preg_replace("#[^a-z0-9]#", "-", mb_strtolower($this->removeAccents($u)))));
		}

		function clearCache($wildcard = "*.cache") {
			if (is_dir($this->path . "Cache")) {
				array_map("unlink", glob($this->path . "Cache/" . $wildcard));
			}
		}

		function ifI81nDie() {
			if (preg_match("#^(.*?)\.(.*?)\.I81n$#", pathinfo($_SERVER["REQUEST_URI"], PATHINFO_FILENAME), $matches)) {
				session_write_close();
				ob_start(function_exists("ob_gzhandler") && !ini_get("zlib.output_compression") ? "ob_gzhandler" : null);
				header("Content-Type: text/javascript; charset=UTF-8");
				$data = array("file" => $matches[1], "language" => $matches[2]);
				$rows = DataBase()->getRows("SELECT * FROM %s WHERE `file`='%s' AND `language`='%s'", DataBase()->translate, $matches[1], $matches[2]);
				foreach ($rows as $row) $data[ $row["text"] ] = $row["translate"];
				echo 'I81n = ' . json_encode($data) . ';' . PHP_EOL;
				echo 'I81n.t = function(t){return typeof I81n[t] != "undefined" ? I81n[t] : t;};' . PHP_EOL;
				die();
			}
		}

		function preCheckRequest() {
			if ((isset($_SERVER["HTTP_X_CHECK"]) && $_SERVER["HTTP_X_CHECK"] == "Pulse") || isset($_GET["pulse"])) {
				header("Content-Type: text/json; charset=utf-8");
				$mysql_session_check = DataBase()->getRows("CHECK TABLE %s", DataBase()->sessions);
				print(json_encode(array(
					"server"         => "ok",
					"mysql"          => "ok",
					"mysql_sessions" => strtolower($mysql_session_check[ count($mysql_session_check) - 1 ]["Msg_text"])
				)));
				exit;
			} else if (preg_match("#/phpinfo/$#", $_SERVER['REQUEST_URI']) && ActiveUser()->isDev()) {
				phpinfo();
				exit;
			} else if (preg_match("#/getScripts/\?.*#", $_SERVER['REQUEST_URI'])) {
				session_write_close();
				ob_start();
				error_reporting(E_ALL ^ E_NOTICE);
				ini_set("display_errors", "On");
				header("Content-Type: application/x-javascript; Charset=UTF-8");
				$bpath = $_GET['prefix'] ?: 'SiteContent/js/';

				if (preg_match("#^[a-zA-Z]{2,}:#", $bpath)) die("lala die!");

				$scripts = explode(",", $_GET['files']);
				$modified = 0;

				if (count($scripts) > 0 && is_array($scripts) && strlen($scripts[0])) {
					foreach ($scripts as $script) {
						if (is_file($bpath . $script)) {
							$m = filemtime($bpath . $script);
							if ($m > $modified) $modified = $m;
							readfile($bpath . $script);
						}
						print(PHP_EOL . ";");
					}
				}

				$etagHeader = (isset($_SERVER['HTTP_IF_NONE_MATCH']) ? trim($_SERVER['HTTP_IF_NONE_MATCH']) : false);
				$content = ob_get_clean();
				$Etag = md5($content);

				header('Date: ' . gmdate('D, d M Y H:i:s T', time()));
				header('Last-Modified: ' . gmdate('D, d M Y H:i:s T', $modified));
				header('Etag: ' . $Etag);
				header('Expires: ' . gmdate('D, d M Y H:i:s T', time() + (3600 * 24 * 30)));
				header('Cache-Control: public');

				if (@strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $modified || $etagHeader == $Etag) {
					header("HTTP/1.1 304 Not Modified");
					exit;
				}

				ob_start(function_exists("ob_gzhandler") && !ini_get("zlib.output_compression") ? "ob_gzhandler" : null);
				echo $content;
				ob_end_flush();
				exit;
			} else if (preg_match("#/sitemap\.xml$#", $_SERVER['REQUEST_URI'])) {
				$this->setPaths();
				$this->roots = $this->getNode(array(
					"filter"       => array(
						"parent"  => 0,
						"enabled" => 1
					),
					"returnFields" => "id,fullAddress,title,description,data,language,enabled"
				));
				//$this->debug($this->roots);
				ob_start(function_exists("ob_gzhandler") && !ini_get("zlib.output_compression") ? "ob_gzhandler" : null);
				header("Content-Type: application/xml; charset=UTF-8");

				echo '<?xml version="1.0" encoding="UTF-8"?>
<urlset
      xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
      xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
            http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">
';

				foreach ($this->roots as $root) {
					$modTime = Settings()->get("sitemap.modtimes." . $root->id);
					if (!$modTime) $modTime = date("c");
					echo '<url><loc>' . $root->fullAddress . '</loc><lastmod>' . $modTime . '</lastmod></url>';

					$items = $this->getNode(array(
						"filter"       => array(
							"parent"   => $root->id,
							"language" => $root->language,
							"enabled"  => 1,
							"<SQL>"    => "`type`!=5"
						),
						"order"        => array("inmenu" => "ASC"),
						"returnFields" => "id,title,fullAddress,parent,type,controller"
					));
					foreach ($items as $k => $m) {
						if ($m && $m->id && $m->parent) {
							$modTime = Settings()->get("sitemap.modtimes." . $m->id);
							if (!$modTime) $modTime = date("c");
							echo '<url><loc>' . $m->fullAddress . '</loc><lastmod>' . $modTime . '</lastmod></url>';

							$childs = $this->getNode(array(
								"filter"       => array(
									"parent"     => $m->id,
									"created_by" => array("manual", "core"),
									"enabled"    => 1,
									"<SQL>"      => "`type`!=5"
								),
								"returnFields" => "id,title,fullAddress,parent"
							));

							foreach ($childs as $child) {
								$modTime = Settings()->get("sitemap.modtimes." . $child->id);
								if (!$modTime) $modTime = date("c");
								echo '<url><loc>' . $child->fullAddress . '</loc><lastmod>' . $modTime . '</lastmod></url>';
							}
						}
					}
				}

				echo '</urlset>';

				die();
			}
		}

		function removeAccents($str) {
			$a = array('À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Æ', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ð', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ø', 'Ù', 'Ú', 'Û', 'Ü', 'Ý', 'ß', 'à', 'á', 'â', 'ã', 'ä', 'å', 'æ', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø', 'ù', 'ú', 'û', 'ü', 'ý', 'ÿ', 'Ā', 'ā', 'Ă', 'ă', 'Ą', 'ą', 'Ć', 'ć', 'Ĉ', 'ĉ', 'Ċ', 'ċ', 'Č', 'č', 'Ď', 'ď', 'Đ', 'đ', 'Ē', 'ē', 'Ĕ', 'ĕ', 'Ė', 'ė', 'Ę', 'ę', 'Ě', 'ě', 'Ĝ', 'ĝ', 'Ğ', 'ğ', 'Ġ', 'ġ', 'Ģ', 'ģ', 'Ĥ', 'ĥ', 'Ħ', 'ħ', 'Ĩ', 'ĩ', 'Ī', 'ī', 'Ĭ', 'ĭ', 'Į', 'į', 'İ', 'ı', 'Ĳ', 'ĳ', 'Ĵ', 'ĵ', 'Ķ', 'ķ', 'Ĺ', 'ĺ', 'Ļ', 'ļ', 'Ľ', 'ľ', 'Ŀ', 'ŀ', 'Ł', 'ł', 'Ń', 'ń', 'Ņ', 'ņ', 'Ň', 'ň', 'ŉ', 'Ō', 'ō', 'Ŏ', 'ŏ', 'Ő', 'ő', 'Œ', 'œ', 'Ŕ', 'ŕ', 'Ŗ', 'ŗ', 'Ř', 'ř', 'Ś', 'ś', 'Ŝ', 'ŝ', 'Ş', 'ş', 'Š', 'š', 'Ţ', 'ţ', 'Ť', 'ť', 'Ŧ', 'ŧ', 'Ũ', 'ũ', 'Ū', 'ū', 'Ŭ', 'ŭ', 'Ů', 'ů', 'Ű', 'ű', 'Ų', 'ų', 'Ŵ', 'ŵ', 'Ŷ', 'ŷ', 'Ÿ', 'Ź', 'ź', 'Ż', 'ż', 'Ž', 'ž', 'ſ', 'ƒ', 'Ơ', 'ơ', 'Ư', 'ư', 'Ǎ', 'ǎ', 'Ǐ', 'ǐ', 'Ǒ', 'ǒ', 'Ǔ', 'ǔ', 'Ǖ', 'ǖ', 'Ǘ', 'ǘ', 'Ǚ', 'ǚ', 'Ǜ', 'ǜ', 'Ǻ', 'ǻ', 'Ǽ', 'ǽ', 'Ǿ', 'ǿ', 'А', 'а', 'К', 'к', 'М', 'м', 'о', 'o', 'Т', 'т', 'В', 'в', 'Е', 'е', 'Н', 'н', 'Р', 'р', 'С', 'с', 'У', 'у', 'Х', 'х', 'Б', 'б', 'Г', 'г', 'Д', 'д', 'З', 'з', 'И', 'и', 'Л', 'л', 'П', 'п', 'Ф', 'ф', 'Э', 'э', 'Ю', 'ю', 'Я', 'я', 'Ё', 'ё', 'Ж', 'ж', 'Ц', 'ц', 'Ч', 'ч', 'Ш', 'ш', 'Щ', 'щ', 'Ы', 'ы', 'Й', 'й', 'Ъ', 'ъ', 'Ь', 'ь');
			$b = array('A', 'A', 'A', 'A', 'A', 'A', 'AE', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'D', 'N', 'O', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'Y', 's', 'a', 'a', 'a', 'a', 'a', 'a', 'ae', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'n', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y', 'A', 'a', 'A', 'a', 'A', 'a', 'C', 'c', 'C', 'c', 'C', 'c', 'C', 'c', 'D', 'd', 'D', 'd', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'G', 'g', 'G', 'g', 'G', 'g', 'G', 'g', 'H', 'h', 'H', 'h', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'IJ', 'ij', 'J', 'j', 'K', 'k', 'L', 'l', 'L', 'l', 'L', 'l', 'L', 'l', 'l', 'l', 'N', 'n', 'N', 'n', 'N', 'n', 'n', 'O', 'o', 'O', 'o', 'O', 'o', 'OE', 'oe', 'R', 'r', 'R', 'r', 'R', 'r', 'S', 's', 'S', 's', 'S', 's', 'S', 's', 'T', 't', 'T', 't', 'T', 't', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'W', 'w', 'Y', 'y', 'Y', 'Z', 'z', 'Z', 'z', 'Z', 'z', 's', 'f', 'O', 'o', 'U', 'u', 'A', 'a', 'I', 'i', 'O', 'o', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'A', 'a', 'AE', 'ae', 'O', 'o', 'A', 'a', 'K', 'k', 'M', 'm', 'O', 'o', 'T', 't', 'V', 'v', 'Je', 'je', 'N', 'n', 'R', 'r', 'S', 's', 'U', 'u', 'H', 'h', 'B', 'b', 'G', 'g', 'D', 'd', 'Z', 'z', 'I', 'i', 'L', 'l', 'P', 'p', 'F', 'f', 'E', 'e', 'Ju', 'ju', 'Ja', 'ja', 'Jo', 'jo', 'Z', 'z', 'C', 'c', 'C', 'c', 'S', 's', 'Sca', 'sca', 'I', 'i', 'I', 'i', '', '', '', '');

			return str_replace($a, $b, $str);
		}

		function &addNav($name, $address, $access = "ALL", &$parent = null) {
			// @param name  Linka nosaukums
			// @param address  Linka adrese aiz $this->aHost
			// @param access  Nosacījumi, kad rādīt linku, pagaidām — SU un Admin
			// #return (string) Linka DOM Id

			$name = $this->t($name);
			if (!isset($this->navLinks) || !is_array($this->navLinks)) {
				$this->navLinks = array();
			}
			$fancyName = $this->removeAccents(mb_strtolower($name));
			$fancyName = preg_replace("#[^a-z0-9]#i", "", $fancyName);

			if (is_null($parent)) {
				$putin = &$this->navLinks;
			} else if (isset($parent["subnav"]) && is_array($parent["subnav"])) {
				foreach ($this->navLinks as $k => $nl) {
					if ($nl["id"] == $parent) {
						$putin = &$this->navLinks[ $k ]["subnav"];
						$subnav = true;
						break;
					}
				}
				if (!isset($putin)) $putin = &$this->navLinks;
				$putin = &$parent["subnav"];
			}

			if (!isset($subnav)) {
				$fancyName = "nav-" . $fancyName;
			}

			$thisNav = array(
				"id"      => $fancyName,
				"title"   => $name,
				"address" => $address,
				"access"  => $access,
				"active"  => false,
				"subnav"  => array()
			);

			$putin[] = &$thisNav;

			return $thisNav;
		}

		function onNav($name) {
			if (!isset($this->navLinks) || !is_array($this->navLinks)) {
				$this->navLinks = array();
			}
			$name = $this->t($name);
			$fancyName = $this->removeAccents(mb_strtolower($name));
			$fancyName = preg_replace("#[^a-z0-9]#i", "", $fancyName);
			$fancyName = "nav-" . $fancyName;
			foreach ($this->navLinks as &$lnk) {
				if ($lnk["id"] == $fancyName) {
					$lnk["active"] = true;
				}
			}
		}

		function getNav() {
			if (!ActiveUser()->canAccessPanel()) return "";
			$patern = '<li%6$s><a id="%1$s" class="%4$s" href="%2$s" tabindex="0" style="white-space: nowrap;">%3$s</a>%5$s</li>';
			$out = array();
			foreach ($this->navLinks as $lnk) {
				$hasSubNavActive = false;
				if ($lnk["access"] == "SU" && !ActiveUser()->isDev()) continue;
				if ($lnk["access"] == "Admin" && !!ActiveUser()->isAdmin()) continue;
				$subnav = "";
				$asubnav = array();
				if (count($lnk["subnav"])) {
					foreach ($lnk["subnav"] as $sn) {
						$thisSubnav = sprintf($patern, $lnk["id"] . "-" . $sn["id"], $this->aHost . $sn["address"], $sn["title"], ($sn["active"] || strpos($this->fullRequestUri, $this->aHost . $sn["address"]) === 0 ? "active" : "") . (count($sn["subnav"]) ? ' dropdown' : ""), "", "");
						if ($sn["active"] || strpos($this->fullRequestUri, $this->aHost . $sn["address"]) === 0) $hasSubNavActive = true;
						$bsubnav = array();
						if (count($sn["subnav"])) {
							foreach ($sn["subnav"] as $sn) {
								$bsubnav[] = sprintf($patern, $lnk["id"] . "-" . $sn["id"], $this->aHost . $sn["address"], $sn["title"], ($sn["active"] || strpos($this->fullRequestUri, $this->aHost . $sn["address"]) === 0 ? "active" : ""), "", "");
								if ($sn["active"] || strpos($this->fullRequestUri, $this->aHost . $sn["address"]) === 0) $hasSubNavActive = true;
							}
							if (count($bsubnav)) {
								$absubnav = "<ul>" . join("", $bsubnav) . "</ul>";
								$thisSubnav = preg_replace("#</li>$#", $absubnav . "</li>", $thisSubnav);
							}
						}
						$asubnav[] = $thisSubnav;
					}
					if (count($asubnav)) {
						$subnav = "<ul>" . join("", $asubnav) . "</ul>";
					}
				}

				$out[] = sprintf($patern, $lnk["id"], $this->aHost . $lnk["address"], $lnk["title"], ($hasSubNavActive == true || $lnk["active"] || strpos($this->fullRequestUri, $this->aHost . $lnk["address"]) === 0 ? "active" : ""), $subnav, $subnav ? ' class="dropdown"' : "");
			}

			return join("", $out);
		}

		/*** TODO: Pievienot nākotnē paramteru (array) ar norādēm uz pievienojamiem failiem. ***/
		function mailIt($to, $subject, $body, $from = null, $nl2br = true) {
			if (is_null($from)) {
				$from = array($this->mailFromAddress ? $this->mailFromAddress : "noreply@{$_SERVER["HTTP_HOST"]}", Settings("site_name"));
			} else if (!is_array($from)) {
				throw new Exception("mailIt(): Sūtītājs jānorāda kā array(address, title)");
			}
			$alt_body = strip_tags($body);
			if ($nl2br == true) $body = nl2br($body);
			if (!is_array($to)) {
				$to = array(array($to, ""));
			} else {
				foreach ($to as &$ta) {
					if (!is_array($ta)) $ta = array($ta, "");
				}
			}

			// Pārbaudīsim adreses
			foreach ($to as $tk => $ta) {
				if (!filter_var($ta[0], FILTER_VALIDATE_EMAIL)) unset($to[ $tk ]);
			}

			if (count($to)) {
				$m = new PHPMailer();
				$m->CharSet = "UTF-8";
				$m->From = $from[0];
				$m->FromName = $from[1];
				$m->Subject = $subject;
				$m->AltBody = strip_tags($alt_body);
				$m->MsgHTML($body);
				foreach ($to as $ta) $m->AddAddress($ta[0], $ta[1]);

				return $m->Send();
			} else throw new Exception("mailIt(): Nav norādīts neviens saņēmējs");
		}

		function __call($name, $args) {
			if ($name == "perPage") {
				return $this->perPage == "all" ? $args[0] : $this->perPage;
			}
			if ($name == "fullRequestUri") {
				if (is_array($args[0])) {
					$uri = $this->fullRequestUri;
					$parts = parse_url($uri);
					parse_str($parts["query"], $query);
					foreach ($args[0] as $k => $v) {
						$query[ $k ] = $v;
					}

					ksort($query);

					return $parts["scheme"] . "://" . (
					$parts["user"] ? $parts["user"] : ""
					) . (
					$parts["pass"] ? ":" . $parts["pass"] : ""
					) . (
					$parts["user"] ? "@" : ""
					) . $parts["host"] . (
					$parts["port"] ? ":" . $parts["port"] : ""
					) . $parts["path"] . (
					count($query) ? "?" . http_build_query($query, '', '&') : ""
					);
				} else return $this->fullRequestUri;
			}
			if (isset($this->$name) === true) {
				$func = $this->$name;
				call_user_func_array(array($this, $name), $args);
			}
			if (isset($this->customMethods[ $name ]) === true) {

				$reflection = new ReflectionFunction($this->customMethods[ $name ]);
				$arguments = $reflection->getParameters();
				foreach ($arguments as $key => $arg) {
					${$arg->name} = $args[ $key ];
				}
				$fi = new FunctionBodyReflection($this->customMethods[ $name ]);
				$body = $fi->getBody(true);

				return eval($body);
			}
		}

		function doCron($name) {
			define("WE_ARE_IN_CRON", true);
			session_write_close();
			$scripts = glob(getcwd() . "/CronJobs/*.php");
			if (is_array($scripts) && count($scripts) > 0) {
				foreach ($scripts as $script) {
					if (preg_match("#^([0-9-\*\|,]+)\.([0-9-\*\|,]+)\.([0-9-\*\|,]+)\.([0-9-\*\|,]+)\.([0-9-\*\|,]+)\.(.*?)\.php$#", basename($script), $m)) {
						$this->currentCronJob = $m[6];
						if ($this->currentCronJob == $name) {
							// We got a job ;)
							$this->incl($script);
						}
					}
				}
			}

			return;
		}

		function cronInit() {
			define("WE_ARE_IN_CRON", true);
			session_write_close();
			$minute = (int)date("i");
			$hour = (int)date("G");
			$day_of_week = (int)date("N");
			$day_of_month = (int)date("j");
			$month_of_year = (int)date("n");

			chdir(dirname($_SERVER["SCRIPT_FILENAME"]));

			$this->path = realpath(dirname($_SERVER["SCRIPT_FILENAME"])) . "/";

			if (is_dir("./CronJobs/")) {
				$scripts = glob(getcwd() . "/CronJobs/*.php");
				if (is_array($scripts) && count($scripts) > 0) {
					foreach ($scripts as $script) {
						$min = 0;
						$hor = 0;
						$daw = 0;
						$dam = 0;
						$moy = 0;
						if (preg_match("#^([0-9-\*\|,]+)\.([0-9-\*\|,]+)\.([0-9-\*\|,]+)\.([0-9-\*\|,]+)\.([0-9-\*\|,]+)\.(.*?)\.php$#", basename($script), $m)) {
							$this->currentCronJob = $m[6];
							if ($m[1] == "*") {
								$min = 1;
							} else {
								$p = explode(",", $m[1]);
								foreach ($p as $b) {
									if (preg_match("#^\d*$#", $b) && $b == $minute) {
										$min = 1;
									} else if ($b == "*") {
										$min = 1;
									} else if (preg_match("#^(\d*)\-(\d*)$#", $b, $n) && $minute >= $n[1] && $minute <= $n[2]) {
										$min = 1;
									} else if (preg_match("#^\*\|(\d+)$#", $b, $n) && $n[1] > 0 && $minute % $n[1] == 0) $min = 1;
								}
							}
							if ($m[2] == "*") {
								$hor = 1;
							} else {
								$p = explode(",", $m[2]);
								foreach ($p as $b) {
									if (preg_match("#^\d*$#", $b) && $b == $hour) {
										$hor = 1;
									} else if ($b == "*") {
										$min = 1;
									} else if (preg_match("#^(\d*)\-(\d*)$#", $b, $n) && $hour >= $n[1] && $hour <= $n[2]) {
										$hor = 1;
									} else if (preg_match("#^\*\|(\d+)$#", $b, $n) && $n[1] > 0 && $hour % $n[1] == 0) $hor = 1;
								}
							}
							if ($m[3] == "*") {
								$dam = 1;
							} else {
								$p = explode(",", $m[3]);
								foreach ($p as $b) {
									if (preg_match("#^\d*$#", $b) && $b == $day_of_month) {
										$dam = 1;
									} else if ($b == "*") {
										$dam = 1;
									} else if (preg_match("#^(\d*)\-(\d*)$#", $b, $n) && $day_of_month >= $n[1] && $day_of_month <= $n[2]) {
										$dam = 1;
									} else if (preg_match("#^\*\|(\d+)$#", $b, $n) && $n[1] > 0 && $day_of_month % $n[1] == 0) $dam = 1;
								}
							}
							if ($m[4] == "*") {
								$moy = 1;
							} else {
								$p = explode(",", $m[4]);
								foreach ($p as $b) {
									if (preg_match("#^\d*$#", $b) && $b == $month_of_year) {
										$moy = 1;
									} else if ($b == "*") {
										$moy = 1;
									} else if (preg_match("#^(\d*)\-(\d*)$#", $b, $n) && $month_of_year >= $n[1] && $month_of_year <= $n[2]) {
										$moy = 1;
									} else if (preg_match("#^\*\|(\d+)$#", $b, $n) && $n[1] > 0 && $month_of_year % $n[1] == 0) $moy = 1;
								}
							}
							if ($m[5] == "*") {
								$daw = 1;
							} else {
								$p = explode(",", $m[5]);
								foreach ($p as $b) {
									if (preg_match("#^\d*$#", $b) && $b == $day_of_week) {
										$daw = 1;
									} else if ($b == "*") {
										$daw = 1;
									} else if (preg_match("#^(\d*)\-(\d*)$#", $b, $n) && $day_of_week >= $n[1] && $day_of_week <= $n[2]) {
										$daw = 1;
									} else if (preg_match("#^\*\|(\d+)$#", $b, $n) && $n[1] > 0 && $day_of_week % $n[1] == 0) $daw = 1;
								}
							}
							if (min($min, $hor, $dam, $moy, $daw) == 1) {
								// We got a job ;)
								$this->incl($script);
							}
						}
					}
				}
			}

			ob_clean();
			exit;
		}

		function cronLog($text) {
			$u = umask(0);
			$path = getcwd() . "/CronJobs/Logs/" . $this->currentCronJob . "/";
			if (!is_dir($path)) mkdir($path, 0777, true);
			file_put_contents($path . date("Y-m") . ".log", date("Y-m-d H:i:s") . "\t" . $text . PHP_EOL, FILE_APPEND);
			umask($u);
		}

		function checkBrowser() {
			return; // TODO: Jāsalabo vispirms
			if (!$_SESSION["browser"]) {
				$bc = new Browscap($this->path . "Uploads/tmp/");
				$_SESSION["browser"] = $bc->getBrowser();
			}
			if (isset($_GET["pass_gate"])) $_SESSION["pass_gate"] = true;
			if (!$_SESSION["pass_gate"]) {
				$b = $_SESSION["browser"];
				$show_warning = false;
				switch ($b->Browser) {
					case "IE":
						if ($b->MajorVer < 8) $show_warning = true;
						break;
					case "Safari":
						if ($b->MajorVer < 5) $show_warning = true;
						break;
					case "Firefox":
						if ($b->MajorVer < 4) $show_warning = true;
						break;
					case "Opera":
						if ($b->MajorVer < 11) $show_warning = true;
						break;
				}
				if ($show_warning === true) {
					$this->incl($this->path . "SiteContent/browser_gate.php");
					exit;
				}
			}
		}

		/// New Structure

		function error($text) {
			ob_clean();
			echo '<b>Kļūda!<br/> ' . $text . '</b>';
			exit;
		}

		/**
		 * @param integer|string|array $one
		 * @param null|integer $two
		 * @return array|object|string|null
		 */
		function getNode($one, &$two = null) {
			$settings = func_get_arg(0);
			if (is_numeric($settings)) {
				$settings = array("filter" => array("id" => $settings), "returnResults" => "first");
			} else if (is_string($settings)) $settings = array("filter" => array("address" => $settings), "returnResults" => "first");

			$opts = array(
				"returnFields"      => "all",
				"returnResults"     => "all",
				"filter"            => array(),
				"order"             => array("sort" => "ASC NUMERICAL", "id" => "ASC NUMERICAL"),
				"includeDeleted"    => false,
				"includeHistorical" => false,
				"limit"             => false,
				"link"              => false,
				"debug"             => false
			);
			$this->array_extend($opts, $settings);
			if ($opts["returnFields"] != "all") {
				$opts["returnFields"] = explode(",", $opts["returnFields"]);
				foreach ($opts["returnFields"] as &$fl) $fl = trim($fl);
			}

			$limit = "";
			$where_a = array();
			$order = "";
			$order_a = array();

			// Lets build a fatal query
			if ($opts["limit"]) {
				$limit = " LIMIT " . (int)$opts["limit"]["page"] * (int)$opts["limit"]["perPage"] . "," . (int)$opts["limit"]["perPage"];
			} else if ($opts["returnResults"] == "first") {
				$limit = " LIMIT 0,1";
			}
			if ($opts["order"] == "random") {
				$order = " ORDER BY RAND() ";
			} else {
				foreach ($opts["order"] as $key => $value) {
					list ($value, $type) = explode(" ", $value);
					$order_a[] = "`" . DataBase()->escape($key) . "`" . ($type == "NUMERICAL" ? '+0' : '') . " " . DataBase()->escape($value);
				}
				if ($opts["orderByAddressLength"]) {
					$order = " ORDER BY LENGTH(`address`) " . $opts["orderByAddressLength"] . " ";
				} else if (count($order_a)) $order = " ORDER BY " . join(", ", $order_a) . " ";
			}

			if (!isset($opts["filter"]["deleted"])) {
				$opts["filter"]["deleted"] = 0;
			}
			if (!isset($opts["filter"]["original"])) {
				$opts["filter"]["original"] = 0;
			}
			foreach ($opts["filter"] as $key => $and) {
				if ($key == "<SQL>") {
					$where_a[] = str_replace("%", "%%", $and);
					continue;
				}
				if ($key == "oneOfParents") {
					$_pp = $this->getNode($and);
					if ($_pp) {
						$where_a[] = "`address` LIKE '" . $_pp->address . "%%'";
						$where_a[] = "`id`!='" . $_pp->id . "'";
					}
					continue;
				}
				if ($key == "tagId") {
					if (isset(DataBase()->tag_relations)) $where_a[] = "(SELECT COUNT(*) FROM " . DataBase()->tag_relations . " `tw` WHERE `tw`.`tag_id`='" . DataBase()->escape($and) . "' AND `tw`.`node_id`=`s`.`id`)=1";
					continue;
				}
				if ($key == "tag") {
					if (isset(DataBase()->tag_relations)) $where_a[] = "(SELECT COUNT(*) FROM " . DataBase()->tag_relations . " `tw` WHERE `tw`.`tag_id`=(SELECT `id` FROM " . DataBase()->tags . " `tw2` WHERE LOWER(`tw2`.`title`)='" . mb_strtolower(DataBase()->escape($and)) . "') AND `tw`.`node_id`=`s`.`id`)=1";
					continue;
				}
				if (is_array($and)) {
					foreach ($and as &$or) $or = DataBase()->escape($or);
					$where_a[] = "`" . DataBase()->escape($key) . "` IN ('" . join("','", $and) . "')";
				} else {
					$where_a[] = "`" . DataBase()->escape($key) . "`='" . DataBase()->escape($and) . "'";
				}
			}

			if (isset($opts["advanced_filter"])) {
				foreach ($opts["advanced_filter"] as $afilter) {
					if (is_array($afilter[2])) {
						array_walk($afilter[2], function (&$item, $key) {
							$item = DataBase()->escape($item);
						});
						$afilter[2] = '(\'' . join("','", $afilter[2]) . '\')';
					} else $afilter[2] = "'" . DataBase()->escape($afilter[2]) . "'";
					$afilter[0] = DataBase()->escape($afilter[0]);
					$where_a[] = "`" . DataBase()->escape($afilter[0]) . "`{$afilter[1]}{$afilter[2]}";
				}
			}

			if (!$opts["includeDeleted"]) $where_a[] = "`deleted`='0'";
			if (!$opts["includeHistorical"]) $where_a[] = "`original`='0'";
			$where = " WHERE " . join(" AND ", $where_a) . " ";

			if ($opts["search"] && is_array($opts["search"])) {
				$forRev = array();
				foreach ($opts["search"] as $fields => $search) {
					$fields = explode(",", $fields);
					foreach ($fields as $field) {
						$forRev[] = 'MATCH(`' . DataBase()->escape($field) . '`) AGAINST(\'' . DataBase()->escape($search) . '\')';
					}
				}
				$searchRev = ", " . join("+", $forRev) . " as `relevance`";
				$searchWhere = " AND (" . join(" OR ", $forRev) . ") ";
			}
			if (isset(DataBase()->tags) && isset(DataBase()->tag_relations)) {
				$countTags = ", (SELECT GROUP_CONCAT(`t1`.`title`) FROM " . DataBase()->tag_relations . " `t2` LEFT JOIN " . DataBase()->tags . " `t1` ON (`t2`.`tag_id`=`t1`.`id`) WHERE `t2`.`node_id`=`s`.`id` GROUP BY `t2`.`node_id`) `tags_input`, (SELECT GROUP_CONCAT(`t1`.`id`) FROM " . DataBase()->tag_relations . " `t2` LEFT JOIN " . DataBase()->tags . " `t1` ON (`t2`.`tag_id`=`t1`.`id`) WHERE `t2`.`node_id`=`s`.`id` GROUP BY `t2`.`node_id`) `tag_ids`";
			} else $countTags = "";
			$opts["query"] = "SELECT *{$countTags}{$searchRev} FROM %s `s`{$where}{$searchWhere}{$order}{$limit}";

			if (func_num_args() == 2) DataBase()->countResults = true;

			$nodes = DataBase()->getRows(
				$opts["query"],
				DataBase()->{$this->nodeTable}
			);
			$node_r = DataBase()->lastResult;

			if (func_num_args() == 2) $two = DataBase()->resultsFound;

			// Lets prepare data
			foreach ($nodes as $nr => &$node) {
				foreach ($node as $key => &$nodeField) {
					$tmp = DataBase()->getJSON($nr, $key, $node_r);
					if (json_last_error() == JSON_ERROR_NONE) $nodeField = $tmp;
				}
				if ($node["tags_input"]) {
					$tagTitles = explode(",", $node["tags_input"]);
					$tagIds = explode(",", $node["tag_ids"]);
					$node["tags"] = array();
					unset($node["tag_ids"]);
					foreach ($tagIds as $_k => $_v) $node["tags"][ $_v ] = $tagTitles[ $_k ];
					$node["tags_literal"] = join(", ", $node["tags"]);
				}
				if ($node && $node["type"] == 2 && $node["data"]["url"]) {
					$node["fullAddress"] = ($node["data"]["internal"] ? $this->host : '') .
						($node["data"]["url"] == "/" ? "" : $node["data"]["url"]);
				} else {
					$node["fullAddress"] = $this->host . $node["address"];
				}
				if ($this->isContentController($node["controller"]) && $node["subid"] && isset(DataBase()->{$node["controller"]}) && $opts["link"]) {
					$data = DataBase()->getRow("SELECT * FROM %s WHERE `id`='%d'", DataBase()->{$node["controller"]}, $node["subid"]);
					if ($data) {
						foreach ($data as $key => $value) {
							if (!array_key_exists($key, $node)) $node[ $key ] = $value;
						}
					}
				}
				if (is_array($opts["returnFields"])) {
					foreach ($node as $key => $we) if (!in_array($key, $opts["returnFields"])) unset($node[ $key ]);
				}

				if (is_array($opts["returnFields"]) && count($opts["returnFields"]) == 1) {
					$node = $node[ array_value($opts["returnFields"]) ];
				} else $node = json_decode(json_encode($node));
				if (isset($node->tags)) $node->tags = json_decode(json_encode($node->tags), true);
			}
			if ($opts["returnResults"] == "first") $nodes = array_value($nodes);

			if ($opts["debug"]) {
				$this->debug(array("opts" => $opts, "nodes" => $nodes));
			}

			return $nodes;
		}

		function setNode($settings, $setHistoricalEntry = true, $preventAddress = true) {
			$cStruct = false;
			$lStruct = false;
			$tStruct = false;
			if (!$this->isAdminInterface) {
				$this->controller = $this->node->controller;
			}
			if (isset($settings["address"]) && $preventAddress) die("setStruct called with address parameter.");
			if ($settings["slug"]) $settings["slug"] = trim($settings["slug"], '/');

			if ($settings["id"]) {
				$cStruct = $this->getNode($settings["id"]);
				if (!$cStruct) {
					// Ooops! Why it isn't an existing structure unit?
					unset($settings["id"]);
				}
				$method = "update";
				$cStructA = (array)$cStruct;
				$settings = array_merge_recursive_distinct($cStructA, $settings);
				$lStruct = $this->getNode($settings["parent"]);
				unset($settings["address"]);
			} else {
				$method = "insert";
				if (!$settings["time_added"]) $settings["time_added"] = strftime("%F %X");
				$settings["added_by"] = ActiveUser()->isValid() ? ActiveUser()->id : Recipe::getClientIP(Page()->trustProxyHeaders);
				if (!isset($settings["created_by"])) {
					$settings["created_by"] = "controller";
					if (!isset($settings["controller"]) && $this->controller) $settings["controller"] = $this->controller;
				}
				if (!isset($settings["type"])) $settings["type"] = 1;
				if (!isset($settings["enabled"])) $settings["enabled"] = 1;
				if (!isset($settings["builtin"])) $settings["builtin"] = 0;
				if (!isset($settings["sort"])) $settings["sort"] = 0;
				if (!isset($settings["title"])) $settings["title"] = "{{Untitled}}";
				if (isset($settings["parent"])) {
					$lStruct = $this->getNode($settings["parent"]);
				}
				if (!isset($settings["language"]) && isset($settings["parent"])) {
					if ($lStruct) {
						$settings["language"] = $lStruct->language;
					}
				}
			}
			if (!isset($settings["language"])) return $this->error("setStruct() jānorāda valoda vai vecāka vienības id<br><pre>" . print_r($settings, true));
			if (!isset($settings["time_updated"])) {
				$settings["time_updated"] = strftime("%F %X");
			}

			if ($lStruct) {
				$myNewAddress = $lStruct->address . $settings["slug"] . "/";
			} else if ($settings["slug"]) {
				$myNewAddress = $settings["slug"] . "/";
			} else $myNewAddress = "";

			$settings["address"] = $myNewAddress;

			if (strpos($settings["slug"], "<SELFID>") === false) {
				$tStruct = $this->getNode(array(
					"filter"        => array(
						"address" => $settings["address"]
					),
					"returnResults" => "first"
				));
			}
			if ($tStruct) {
				// Maybe address collision
				if (!isset($settings["id"]) || $settings["id"] != $tStruct->id) {
					// Surely address collision
					if ($settings["type"] != 5) {
						return false;
					}
				}
			}

			if (isset($settings["tags"])) {
				if (is_array($settings["tags"])) $updateTags = $settings["tags"];
				unset($settings["tags"]);
			}

			foreach ($settings as $key => $value) {
				if (!in_array($key, $this->nodeSelectableFields)) unset($settings[ $key ]);
			}

			$settings["data"] = json_encode($settings["data"]);
			if ($settings["content"] && is_array($settings["content"])) {
				$settings["content"] = json_encode($settings["content"]);
			}

			DataBase()->queryf("DELETE FROM %s WHERE `original`!='0' AND `address`='%s'", DataBase()->{$this->nodeTable}, $settings["address"]);

			DataBase()->insert($this->nodeTable, $settings, (
			$method == "insert" ? true : array("id" => $settings["id"])
			));
			if ($method == "insert") {
				$sid = DataBase()->insertid;
			} else $sid = $settings["id"];

			if (strpos($settings["slug"], "<SELFID>") !== false) {
				if ($sid) {
					DataBase()->update($this->nodeTable, array(
						"address" => str_replace("<SELFID>", $sid, $settings["address"]),
						"slug"    => str_replace("<SELFID>", $sid, $settings["slug"])
					), array(
						"id" => $sid
					));
				}
			}

			if (isset($updateTags) && is_array($updateTags) && count($updateTags) && isset(DataBase()->tags)) {
				$insertedTags = array();
				foreach ($updateTags as $tag) {
					$tag = mb_strtolower(trim($tag));
					if ($tag == "") continue;
					$tagId = DataBase()->getVar("SELECT `id` FROM %s WHERE `title`='%s'", DataBase()->tags, $tag);
					if (!$tagId) {
						DataBase()->insert("tags", array(
							"title"  => $tag,
							"slug"   => $this->urlencode($this->removeAccents($tag)),
							"static" => 0
						));
						$tagId = DataBase()->insertid;
					}
					DataBase()->insert("tag_relations", array(
						"tag_id"  => $tagId,
						"node_id" => $sid
					), true);
					$insertedTags[] = $tagId;
				}
				if (count($insertedTags)) {
					DataBase()->queryf("DELETE FROM %s WHERE `node_id`='%d' AND `tag_id` NOT IN (" . join(",", $insertedTags) . ")", DataBase()->tag_relations, $sid);
					$this->cleanUpTags();
				} else {
					DataBase()->queryf("DELETE FROM %s WHERE `node_id`='%d'", DataBase()->tag_relations, $sid);
					$this->cleanUpTags();
				}
			}

			$this->updateStructChilds(array($cStruct));

			if (!$tStruct && $cStruct && $cStruct->address !== $settings["address"] && $setHistoricalEntry) {
				$oldEntries = $this->getNode(array("filter" => array("original" => $sid)));
				if (is_array($oldEntries)) {
					foreach ($oldEntries as $oe) {
						$this->setNode(array(
							"id"   => $oe->id,
							"data" => array("url" => $settings["address"], "internal" => true, "code" => 307)
						), false);
					}
				}
				$this->setNode(array(
					"address"    => $cStruct->address,
					"slug"       => $cStruct->slug,
					"original"   => $sid,
					"type"       => 2,
					"data"       => array("url" => $settings["address"], "internal" => true, "code" => 307),
					"created_by" => "core",
					"controller" => "core-redirect-historical",
					"parent"     => $cStruct->parent,
					"enabled"    => 1,
					"builtin"    => $cStruct->builtin,
					"sort"       => 0,
					"inmenu"     => 0,
					"deleted"    => 0,
					"language"   => $cStruct->language,
					"title"      => $cStruct->title
				), true, false);
			}

			$this->trigger("struct_updated");

			return $this->lastNodeUpdated = $sid;
		}

		function remNode($id) {
			if (!$id) return false;
			$childs = $this->getNode(array(
				"filter" => array(
					"parent" => $id
				)
			));
			if ($childs) {
				foreach ($childs as $child) $this->remNode($child->id);
			}
			DataBase()->update($this->nodeTable, array(
				"deleted" => 1
			), array(
				"id" => $id
			));
			DataBase()->queryf("DELETE FROM %s WHERE `node_id`='%d'", DataBase()->tag_relations, $id);

			return $this->lastNodeDeleted = $id;
		}

		function updateStructChilds($nodes) {
			if ($nodes) {
				foreach ($nodes as $node) {
					$childs = $this->getNode(array(
						"filter" => array(
							"parent" => $node->id
						)
					));
					if ($childs) {
						foreach ($childs as $child) {
							$this->setNode(array("id" => $child->id));
						}
					}
				}
			}
		}

		function cleanUpTags() {
			DataBase()->queryf("DELETE FROM %1\$s WHERE `id` IN (SELECT * FROM (SELECT `t1`.`id` FROM %1\$s `t1` LEFT JOIN %2\$s `t2` ON (`t1`.`id`=`t2`.`tag_id`) WHERE `t1`.`static`=0 AND `t2`.`tag_id` IS NULL GROUP BY `t2`.`tag_id`) `t3`)", DataBase()->tags, DataBase()->tag_relations);
		}

		function getTags($language, $top = false) {
			if ($top == false) {
				$tagData = DataBase()->getRows("SELECT * FROM %s `t1` WHERE `id` IN (SELECT `tag_id` FROM %s `t2` WHERE `t2`.`node_id` IN (SELECT `id` FROM %s `s` WHERE `original`=0 AND `deleted`=0 AND `enabled`=1 AND (`language`='%s')) GROUP BY `tag_id`) OR `static`='1' ORDER BY `static` DESC, `title` ASC", DataBase()->tags, DataBase()->tag_relations, DataBase()->{$this->nodeTable}, $language);
			} else $tagData = DataBase()->getRows("SELECT * FROM %s `t1` WHERE `id` IN (SELECT `tag_id` FROM %s `t2` WHERE `t2`.`node_id` IN (SELECT `id` FROM %s `s` WHERE `original`=0 AND `deleted`=0 AND `enabled`=1 AND (`language`='%s')) GROUP BY `tag_id`) OR `static`='1' ORDER BY `static` DESC, (SELECT COUNT(*) FROM %s `t3` WHERE `t3`.`tag_id`=`t1`.`id`) DESC, `t1`.`title` ASC LIMIT 40", DataBase()->tags, DataBase()->tag_relations, DataBase()->{$this->nodeTable}, $language, DataBase()->tag_relations);
			$tags = array();
			foreach ($tagData as $tag) {
				$tags[ $tag["id"] ] = $tag["title"];
			}

			return $tags;
		}

		function getTag($name, $language) {
			return DataBase()->getVar("SELECT `id`, `title` FROM %s `t1` WHERE `t1`.`title`='%s' AND (`id` IN (SELECT `tag_id` FROM %s `t2` WHERE `t2`.`node_id` IN (SELECT `id` FROM %s `s` WHERE `s`.`original`=0 AND `s`.`deleted`=0 AND `s`.`enabled`=1 AND (`s`.`language`='%s')) GROUP BY `t2`.`tag_id`) OR `t1`.`static`='1')", DataBase()->tags, $name, DataBase()->tag_relations, DataBase()->{$this->nodeTable}, $language);
		}

		function getTagById($id) {
			return DataBase()->getRow("SELECT * FROM %s WHERE `id`='%d'", DataBase()->tags, $id);
		}

		function cmsInfotip($text, $color, $title = "", $dismisable = false, $echo = true) {
			$coloToTypeMap = array("yellow" => "warning", "red" => "danger", "green" => "success", "blue" => "info");
			$htmlData = '<div class="alert alert-' . str_replace(array_keys($coloToTypeMap), array_values($coloToTypeMap), $color) . ($dismisable ? ' alert-dismissable' : '') . '">' . ($dismisable ? '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' : '') . ($title ? '<strong>' . $title . '</strong> ' : '') . $text . '</div>';
			if ($echo) {
				echo $htmlData;
			} else return $htmlData;
		}

		function addCmsInfotip($text, $color, $title = "", $icon = false, $echo = false) {
			$_SESSION["infotips"][] = func_get_args();
		}

		function structController() {
			$this->controllers[ $this->currentController ]->setAvailableAsTemplate();
		}

		function contentController() {
			$this->controllers[ $this->currentController ]->setEditable();
		}

		function isStructController($cn) {
			return $this->controllers[ $cn ] && $this->controllers[ $cn ]->isAvailableAsTemplate();
		}

		function isContentController($cn) {
			//$this->debug($this->controllers);
			return $this->controllers[ $cn ] && $this->controllers[ $cn ]->isEditable();
		}

		function registerController() {
			$this->controllers[ $this->currentController ] = new Controller();

			return $this->controllers[ $this->currentController ];
		}

		function structIdCheck() {
			if (!$this->isContentController($this->controller)) {
				return true;
			} else {
				if (!isset($_GET["sid"]) || $this->getNode($_GET["sid"])->controller != $this->controller) {
					$nodes = $this->getNode(array(
						"filter" => array(
							"controller" => $this->controller,
							"created_by" => array("manual", "core")
						)
					));
					$lis = array();
					if ($nodes) {
						foreach ($nodes as &$node) {
							$node->contentUrl = $this->getURL(array("sid" => $node->id));
							$lis[] = '<li><a href="' . $node->contentUrl . '"><b>' . $node->title . '</b> <small>(' . $node->address . ')</small></a></li>';
						}
					}

					if (count($lis)) {
						$cntnt = '<div id="csi-dialog">' . $this->cmsInfotip($this->t("{{CSI: Infotip}}"), "", "", "", false) . '<ul>' . join('', $lis) . '</ul></div>';

						$output = <<<EOV
<script type="text/javascript">
$(function(){
	$($.parseHTML(%s)).dialog({
		title: %s,
		modal: true,
		resizable: false,
		draggable: false,
		buttons: [{
			"text": %s,
			"click": function(){
				document.location.href=%s;
			}
		},{
			"text": %s,
			"click": function(){
				document.location.href=%s;
			}
		}],
		close: function(){document.location.href=%s;}
	});
});
</script>
EOV;
						$output = sprintf($output, json_encode($cntnt), json_encode($this->t("{{CSI: Dialog Title}}")), json_encode($this->t("{{Nav: Structure}}")), json_encode($this->aHost . "structure/"), json_encode($this->t("{{Nav: Home}}")), json_encode($this->aHost), json_encode($this->aHost . "structure/"));
						print($output);
					} else {
						return false;
					}
				} else {
					return true;
				}
			}
		}

		function array_extend(&$result) {
			if (!is_array($result)) {
				$result = array();
			}

			$args = func_get_args();

			for ($i = 1; $i < count($args); $i++) {
				// we only work on array parameters:
				if (!is_array($args[ $i ])) continue;

				// extend current result:
				foreach ($args[ $i ] as $k => $v) {
					if (!isset($result[ $k ])) {
						$result[ $k ] = $v;
					} else {
						$result[ $k ] = $v;
					}
				}
			}

			return $result;
		}

		function optimizeTables() {
			$tables = join(",", DataBase()->tables);

			return DataBase()->getRows("OPTIMIZE TABLE " . $tables);
		}

		function checkTables() {
			$tables = join(",", DataBase()->tables);
			$data = DataBase()->getRows("CHECK TABLE " . $tables);
			$data2 = array();
			foreach ($data as $row) $data2[ $row["Table"] ][] = $row;

			return $data2;
		}

		function getEmptyImage($width, $height = null) {
			if (is_null($height)) $height = $width;
			if (!is_file($this->path . "Cache/no-img.{$width}.{$height}.png")) {
				$dst = imagecreate($width, $height);
				imagecolorallocate($dst, 255, 255, 255);

				$icon = new Image($this->path . "Library/Assets/no-img.png");
				$size = min($width, $height);
				if ($size > 550) {
					$size = 550;
				} else $size -= 20;
				$icon->resize($size, $size);

				$x = ($width - $size) / 2;
				$y = ($height - $size) / 2;

				imagecopy($dst, $icon->image, $x, $y, 0, 0, $size, $size);

				imagepng($dst, $this->path . "Cache/no-img.{$width}.{$height}.png");
				imagedestroy($dst);
			}

			return "Cache/no-img.{$width}.{$height}.png";
		}

		function getBreadcrumbs() {
			$bc = array();
			$t = $this->node;
			while ($t) {
				$bc[] = $t;
				$t = array_value($this->getNode(array(
					"filter"       => array(
						"id" => $t->parent
					),
					"returnFields" => "id,parent,title,fullAddress,type,controller"
				)));
			}

			return array_reverse($bc);
		}

		function getMenu() {
			$language = Page()->language ? Page()->language : Page()->languages[0];
			$menu = Settings()->get("site_menu", $language);

			if (is_array($menu)) {
				array_walk($menu, function (&$it) {
					$a = new Address($it["address"]);
					$it["fullAddress"] = $a->getURL();
					if ($it["isNode"]) {
						$it["node"] = Page()->getNode(array(
							"filter"        => array(
								"id" => $it["isNode"]
							),
							"returnFields"  => "id,enabled",
							"returnResults" => "first"
						));
						$it["childs"] = Page()->getNode(array(
							"filter"       => array(
								"enabled"    => 1,
								"parent"     => $it["isNode"],
								"created_by" => array("core", "manual")
							),
							"order"        => array("sort" => "ASC", "title" => "ASC"),
							"returnFields" => "id,title,fullAddress"
						));
					}
					unset($it["isNode"], $it["address"]);
					$it = (object)$it;
					if ($it->node && !$it->node->enabled) unset($it);
				});
			} else $menu = array();

			return $menu;
		}

		function register_filter($filter, $func) {
			$this->filters[ $filter ][] = $func;
		}

		function filter($filter, &$arg1 = null) {
			$args = func_get_args();
			array_shift($args);
			if (is_array($this->filters[ $filter ])) {
				foreach ($this->filters[ $filter ] as $func) {
					$reflection = new ReflectionFunction($func);
					$arguments = $reflection->getParameters();
					foreach ($arguments as $key => $arg) {
						if ($key == 0) {
							${$arg->name} = &$arg1;
						} else ${$arg->name} = $args[ $key ];
					}
					$fi = new FunctionBodyReflection($func);
					$body = $fi->getBody(true);

					return eval($body);
				}
			}
		}

		function on($event, $func) {
			$this->hooks[ $event ][] = $func;
		}

		function trigger($event, &$arg1 = null) {
			$args = func_get_args();
			array_shift($args);
			if (is_array($this->hooks[ $event ])) {
				foreach ($this->hooks[ $event ] as $func) {
					$reflection = new ReflectionFunction($func);
					$arguments = $reflection->getParameters();
					foreach ($arguments as $key => $arg) {
						if ($key == 0) {
							${$arg->name} = &$arg1;
						} else ${$arg->name} = $args[ $key ];
					}
					$fi = new FunctionBodyReflection($func);
					$body = $fi->getBody(true);

					return eval($body);
				}
			}
		}

		function setMethod($name, $func) {
			$this->customMethods[ $name ] = $func;
		}

		function accessDenied() {
			$this->header();
			echo '<div class="alert alert-danger"><strong>Access Denied!</strong><p>You haven\'t any permissions to view this content.</p></div>';
			$this->footer();
			exit;
		}

		function getThumb($file, $sizex, $sizey, $force = false, $woHost = false, $resize = false) {
			return ($woHost ? '' : $this->host) . FS()->getThumb($file, $sizex, $sizey);
		}

		function getNodeByAddress($address = null, $include_historical = false) {
			$hostIsLocal = false;
			$uri = false;

			if (is_null($address)) $address = $this->getURL();
			$addressParts = parse_url($address);
			$host = $addressParts["host"];

			foreach ($this->domains as $lng => $domains) {
				if (in_array($host, $domains)) {
					$hostIsLocal = true;
					break;
				}
			}

			if ($hostIsLocal && strpos($addressParts["path"], $this->subPath) === 0) {
				$uri = substr($addressParts["path"], strlen($this->subPath));
			} else if (is_null($host)) {
				$uri = $address;
			}

			if ($uri) {
				return $this->getNode(array(
					"filter"               => array(
						"<SQL>" => "'" . DataBase()->escape($uri) . "' LIKE CONCAT(`address`,'%%')"
					),
					"returnResults"        => "first",
					"orderByAddressLength" => "DESC",
					"includeHistorical"    => $include_historical,
					"debug"                => false
				));
			}

			return false;
		}

		function getURL($param = null) {
			// TODO: Address() klase
			if (is_null($param)) {
				return $this->fullRequestUri;
			} else if (is_array($param)) {
				return $this->fullRequestUri($param);
			} else if (is_numeric($param)) {
				return $this->getNode(array("filter" => array("id" => $param), "returnFields" => "fullAddress", "returnResults" => "first"));
			} else if (filter_var($param, FILTER_VALIDATE_URL)) return $param;
			if (file_exists($param)) {
				return str_replace($this->path, $this->host, $param);
			}
			$test = $this->getNode(array(
				"filter"            => array("address" => $param),
				"includeHistorical" => true,
				"includeDeleted"    => true,
				"returnFields"      => "id,original,fullAddress",
				"returnResults"     => "first"
			));
			if ($test) {
				if ($test->original) {
					return $this->getNode(array("filter" => array("id" => $test->original), "returnFields" => "fullAddress", "includeDeleted" => true, "returnResults" => "first"));
				} else return $test->fullAddress;
			} else if (preg_match("#^[a-z0-9]+\.[a-z0-9]+#i", $param)) {
				return "http://" . $param;
			} else return $param;
		}

		function e($str, $processing = 0) {
			// 3 for json_encode
			// 2 for urlencode
			// 1 for htmlspecialchars
			if ($processing == 4) $str = rawurldecode($str);
			if ($processing == 3) $str = json_encode($str); // Protams, $str = jebkas, nevis tikai String ;)
			if ($processing == 2) $str = urlencode($str);
			if ($processing == 1) $str = htmlspecialchars($str);
			echo $str;
		}

		function injectStart($selector, $prepend = false) {
			ob_start();
			$this->currentInjection->selector = $selector;
			$this->currentInjection->prepend = $prepend;
		}

		function injectEnd() {
			$this->currentInjection->content = ob_get_clean();
			$this->injections[] = $this->currentInjection;
			$this->currentInjection = (object)array();
		}

		function loadWhen($controller, $action, $file, $requestMethod = "GET") {
			$bt = debug_backtrace();
			list($controllers, $caller_controller, $caller_script) = explode("/", str_replace($this->adminPath, "", $bt[0]["file"]));
			$this->conditions[ $controller ][ $action ][] = array(
				"c" => $caller_controller,
				"m" => $requestMethod,
				"f" => $file
			);
		}

		function debug($mixed) {
			ob_clean();
			$this->obGzEnabled = false;
			$this->setType("text/html");
			echo '<pre style="font-family: \'Menlo\',\'Courier New\', monospace; font-size: 14px;">';
			var_dump($mixed);
			exit;
		}

		function cidr_match($ip, $cidr) {
			list($subnet, $mask) = explode('/', $cidr);

			if ((ip2long($ip) & ~((1 << (32 - $mask)) - 1)) == ip2long($subnet)) {
				return true;
			}

			return false;
		}
	}


	class FunctionBodyReflection extends ReflectionFunction {
		/**
		 * get the body of a function as a string
		 *
		 * @access public
		 * @param bollean $withDeclaration true to return entire function delcaration
		 * @return String
		 */
		public function getBody($withDeclaration = true) {
			$totalparams = count($this->getParameters());
			$source = file($this->getFileName(), FILE_IGNORE_NEW_LINES);
			$startindex = $this->getStartLine() - 1;
			$endindex = $this->getEndline();
			$funcbody = ($startindex == $endindex) ? $source[ $startindex ] :
				trim(implode("\n", array_slice($source, $startindex, $endindex - $startindex)));
			$phpfuncbody = sprintf("<?php\n %s \n?>", $funcbody);
			$body = array();
			if (!$withDeclaration) {
				$skiptokens = array('T_FUNCTION', 'T_WHITESPACE', 'T_STRING', '(', ')');
				for ($i = 0; $i < $totalparams; $i++) {
					array_push($skiptokens, 'T_VARIABLE', ',');
				}
			}
			$tokens = token_get_all($phpfuncbody);
			$totaltokens = count($tokens);
			for ($i = 0; $i < $totaltokens; $i++) {
				$token = $tokens[ $i ];

				if (is_array($token)) {
					list($tokenid, $tokenstr, $tokenline) = $token;

					// skip open/close php tags
					$tokenname = token_name($tokenid);
					if ($tokenname == 'T_OPEN_TAG' || $tokenname == 'T_CLOSE_TAG') continue;

					if (!isset($body[ $tokenline ])) $body[ $tokenline ] = "";
				} else {
					end($body);
					$tokenid = null;
					$lokenline = key($body);
					$tokenstr = $tokenname = $token;
				}
				if (!$withDeclaration && ($tokenkey = array_search($tokenname, $skiptokens)) !== false) {
					unset($skiptokens[ $tokenkey ]);
					if ($lokenline <= 1) continue;
				}
				$body[ $tokenline ] .= $tokenstr;
			}

			return preg_replace("#(^.*?\{|\}\);$)#", "", trim(implode("", $body), (!$withDeclaration) ? " \t\n\r\0\x0B{})" : " \t\n\r\0\x0B"));
		}

		/**
		 * get the length of the body
		 *
		 * @access public
		 * @param boolean $countWhitespace true if whitespace should count against body size
		 * @return Integer
		 */
		public function getBodyLen($countWhitespace = true) {
			return strlen(($countWhitespace) ? $this->getBody(false) : preg_replace("/\s+/", "", $this->getBody(false)));
		}
	}


	/**
	 * Class EmptyNode
	 */
	class EmptyNode {
		function __construct() { return true; }

		function __get($name) { return false; }

		function __set($name, $value) { return true; }
	}


	/**
	 * @return Page Pointer to Page instance
	 */
	function Page() {
		return $GLOBALS["_page"];
	}

	/**
	 * @return object Pointer to Node object
	 */
	function Node() {
		if (Page()->node && Page()->node->id) {
			return Page()->node;
		} else return new EmptyNode();
	}


	/**
	 * Class Controller
	 */
	class Controller {
		/**
		 * @var string $name
		 * @var bool $availableAsTemplate
		 * @var bool $editable
		 * @var string $defaultView
		 * @var string $host
		 * @var string $path
		 * @var array|null $templateData
		 */
		private $name;
		private $availableAsTemplate = false;
		private $editable = false;
		private $defaultView = "default";
		private $host;
		private $path;
		private $perms = array();
		public $templateData;

		/**
		 * @return Controller
		 */
		function _construct() {
			return $this;
		}

		/**
		 * @return Controller
		 */
		function setAvailableAsTemplate($views = array(), $ids = array()) {
			$this->templateData["views"] = $views;
			$this->templateData["ids"] = $ids;
			$this->availableAsTemplate = true;

			return $this;
		}

		/**
		 * @return bool
		 */
		function isAvailableAsTemplate() {
			return $this->availableAsTemplate;
		}

		/**
		 * @return Controller
		 */
		function setEditable() {
			$this->editable = true;

			return $this;
		}

		/**
		 * @return bool
		 */
		function isEditable() {
			return $this->editable;
		}

		/**
		 * @return Controller
		 */
		function setName($name) {
			$this->name = $name;

			return $this;
		}

		/**
		 * @return string
		 */
		function getName() {
			return $this->name;
		}

		/**
		 * @return Controller
		 */
		function setDefaultView($view) {
			$this->defaultView = $view;

			return $this;
		}

		/**
		 * @return string
		 */
		function getDefaultView() {
			return $this->defaultView;
		}

		/**
		 * @return Controller
		 */
		function setPaths($base) {
			$this->host = Page()->adminHost . basename($base) . "/";
			$this->path = $base;

			return $this;
		}

		/**
		 * @return string
		 */
		function getPath() {
			return $this->path;
		}

		/**
		 * @return string
		 */
		function getHost() {
			return $this->host;
		}

		/**
		 * @var array $perms
		 * @return Controller
		 */
		function setGroupPerms($perms) {
			$this->perms = $perms;

			return $this;
		}

		/**
		 * @return array
		 */
		function getGroupPerms() {
			return $this->perms;
		}

	}


	/**
	 * @param string $name
	 * @return Controller|null
	 */
	function &Controller($name) {
		if (isset(Page()->controllers[ $name ])) {
			return Page()->controllers[ $name ];
		} else return null;
	}