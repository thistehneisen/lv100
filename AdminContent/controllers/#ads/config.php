<?php
	if (Page()->isAdminInterface && ActiveUser()->can(Page()->currentController, "pārvaldīt")) {
		$navAds = &Page()->addNav("Baneri", Page()->currentController."/");
		foreach (Page()->roots as $root) {
			Page()->addNav("Baneri (" . strtoupper($root->language) . ")", Page()->currentController . "/?sid=" . $root->id, "ALL", $navAds);
		}
	}



	Page()->registerController()
		->setGroupPerms(array("pārvaldīt"))
		->setName("Baneri");
