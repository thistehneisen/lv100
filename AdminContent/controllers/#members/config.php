<?php
	if (Page()->isAdminInterface && ActiveUser()->can(Page()->currentController,"pārvaldīt")) {
		Page()->addNav("Vadība", Page()->currentController . "/");
	}


	Page()->registerController()
		->setGroupPerms(array("pārvaldīt"))
		->setName("Vadība");
