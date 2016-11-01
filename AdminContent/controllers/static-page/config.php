<?php

	Page()->registerController()
		->setName("Statiska lapa")
		->setAvailableAsTemplate(
			array("default") // Views
		)
		->setEditable()
		->setDefaultView("default");
	
	if (Page()->isAdminInterface && Page()->controller == Page()->currentController && $_GET["sid"]) {
		if (($s = Page()->getNode($_GET["sid"])) && $s->controller == "static-page") {
			header("Location: ".Page()->aHost."static-page/edit/{$_GET["sid"]}/");
			exit;
		}
		else {
			header("Location: ".Page()->aHost."structure/");
			exit;
		}
	}