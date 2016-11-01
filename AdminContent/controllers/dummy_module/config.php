<?php
	if (Page()->isAdminInterface) {
		Page()->registerController()
			->setName("Netiek lietots")
			->setAvailableAsTemplate()
			->setDefaultView("redirect");
	}

