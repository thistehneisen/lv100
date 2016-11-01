<?php
	/**
	 * Created by Burzum.
	 * User: Arnolds
	 * Date: 28.06.16
	 * Time: 10:10
	 */

	error_reporting(E_ALL & ~(E_WARNING|E_NOTICE));

	if (!function_exists("FrontUsers")) new FrontUsers();

	if (file_exists(Page()->bPath."callbacks/".Page()->reqParams[0].".php")) {
		include(Page()->bPath."callbacks/".Page()->reqParams[0].".php");
	}
	exit;