<?php

	if (Page()->isAdminInterface) {
		if (ActiveUser()->can(Page()->currentController, "pārvaldīt")) {
			$menuItem = $this->addNav("Lietotāji", Page()->currentController . "/users/");
			if (Page()->controller == Page()->currentController) {
				Page()->on("after_dependicies", function () {
					Page()->addBreadcrumb("Lietotāji", Page()->aHost . "front-users/users/");
				});
			}
		}
	}

	Page()->registerController()
		->setGroupPerms(array("pārvaldīt"))
		->setName("Lietotāji");
