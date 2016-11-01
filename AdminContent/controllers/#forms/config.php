<?php

	Page()->registerController()
		->setGroupPerms(array("pārvaldīt"))
		->setName("Formas")
		->setAvailableAsTemplate(
			array("list") // Views
		)
		->setEditable()
		->setDefaultView("list-galleries");

	Page()->addNav("Formas",Page()->currentController."/");

	Page()->loadWhen("news", "edit", "node_editing.php", "#ALL#");
	Page()->loadWhen("news", "edit", "node_submited.php", "POST");

	Page()->loadWhen("static-page", "edit", "node_editing.php", "#ALL#");
	Page()->loadWhen("static-page", "edit", "node_submited.php", "POST");

	Page()->loadWhen("events", "edit", "node_editing.php", "#ALL#");
	Page()->loadWhen("events", "edit", "node_submited.php", "POST");

	Page()->formFieldTypes = array(
		array("type" => "input", 	"subtype" => "text", 		"label" => "Teksts"),
		array("type" => "input", 	"subtype" => "number", 		"label" => "Numurs"),
		array("type" => "textarea", "subtype" => "general", 	"label" => "Garšs teksts"),
		array("type" => "input", 	"subtype" => "file", 		"label" => "Faila augšupielāde"),
		array("type" => "select", 	"subtype" => "general", 	"label" => "Izvēlne"),
		array("type" => "input", 	"subtype" => "checkbox", 	"label" => "Vairākas izvēles (checkbox)"),
		array("type" => "input", 	"subtype" => "radio", 		"label" => "Izvēle (radio)"),
		array("type" => "seperator","subtype" => "general", 	"label" => "Atdalītājs")

	);



?>