<?php
	if (Page()->isAdminInterface && ActiveUser()->can(Page()->currentController,"pārvaldīt")) {
		Page()->addNav("Galerijas", Page()->currentController . "/");

	}


	Page()->registerController()
		->setGroupPerms(array("pārvaldīt"))
		->setName("Galerijas")
		->setAvailableAsTemplate(
			array("list-galleries") // Views
		)
		->setEditable()
		->setDefaultView("list-galleries");
