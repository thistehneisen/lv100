<?php
	/*

		Projekts: Burzum CMS
		2012. - 2016. gads

	*/
	ini_set("display_errors", "On");
	ini_set("log_errors", "On");
	ini_set("error_log", dirname(__FILE__) . "/Cache/error.log");
	error_reporting(E_ALL ^ E_NOTICE);

	require dirname(__FILE__) . "/Library/Classes.php";
	require dirname(__FILE__) . "/Library/Sessions.php";

	new DataBase("elza", "dev2burzumlv", "F2pIPeydaYW7", "dev2burzumlv", "lv100_");

	$CMS = new Page(array(
		"domains"            => array(
			"lv" => array("dev2.burzum.lv")
		),
		"forcePrimaryDomain" => false,
		"sslEnabled"         => false,
		"searchEnabled"      => false,

		"useFacebookMeta" => false,

		// OLD
		"maxImageWidth"   => 1170,
		// NEW
		"defaultImageBox" => array(1170, 5000),
		"maxImageQuality" => 90,

		"remoteAdminPanel" => false,

		"GoogleReCAPTCHA" => array(
			// TODO
			"secret" => "...",
			"key"    => "..."
		),

		"GoogleClient" => array(
			"id"       => "...",
			"secret"   => "...",
			"endpoint" => "https://www.googleapis.com/oauth2/v3"
		),

		"FacebookApp" => array(
			"id"       => "...",
			"secret"   => "...",
			"endpoint" => "https://graph.facebook.com"
		),

		"TwitterApp" => array(
			"key"    => "...",
			"secret" => "..."
		),

		"DraugiemApp" => array(
			"id"       => "...",
			"key"      => "...",
			"endpoint" => "http://api.draugiem.lv/json/"
		),

		"development" => true,
		"trustProxyHeaders" => false,
		"newsLettersEnabled" => false,
		"email_from_address" => "info@lnkc.gov.lv",

		"cache"    => new Cache(dirname(__FILE__) . "/Cache/cd"),
		"users"    => new Users(),
		"settings" => new Settings()

	));
	$CMS->init();