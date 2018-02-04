<?php
	if (Page()->isAdminInterface) {
	}

	Page()->registerController()
		->setName("Notikumi")
		/*->setAvailableAsTemplate(
			array("list","all-events"), // Views
			array("brivdabasmuzejs", "veveri", "vitolnieki") // SubIds
		)*/
		->setEditable()
		->setDefaultView("list");
