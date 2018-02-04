<?php
	/*
		Classes.php
		Autors:		Arnolds Zvejnieks
					<arnolds.zvejnieks@gmail.com>

		Versija:	2.0
	*/

	set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__));
	set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__) . "/Classes/");
	set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__) . "/Functions/");

	spl_autoload_extensions('.class.php,.php');
	spl_autoload_register(function ($name) {

		$dir = dirname(__FILE__) . "/";
		$file = $dir . "Classes/" .strtolower($name) . ".class.php";
		if (!file_exists($file)) {
			return;
		} else require $file;
	});