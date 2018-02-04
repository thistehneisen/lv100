<?php

	if (ActiveUser()->canAccessPanel()) {
		$menuItem = Page()->addNav("Sadaļas", Page()->currentController . "/");
	}

	Page()->registerController()
		->setGroupPerms(array("1. lapas saturs", "mainīt lapas statusu", "mainīt izvēlni", "pievienot sadaļu"))
		->setName("Lapas struktūra");

	Page()->on("struct_updated", function () {
		Page()->cache->purge("breadcrumbs");
		Page()->cache->purge("navigation");
	});

	Page()->cC = array(
		"rootCogItems" => array()
	);

	$rootLanguages = Page()->getNode(array(
		"filter"       => array(
			"original" => 0,
			"parent"   => 0
		),
		"returnFields" => "language"
	));

	foreach (Page()->languages as $lng) {
		if (!$rootLanguages || !in_array($lng, $rootLanguages)) {
			Page()->setNode(array(
				"title"      => "not set",
				"slug"       => $lng,
				"controller" => "not-set",
				"created_by" => "core",
				"added_by"   => 0,
				"language"   => $lng,
				"data"       => array()
			));
		}
	}