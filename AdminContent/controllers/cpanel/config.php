<?php //ā
	Page()->registerController()
		->setName("Uzstādījumi")
		->setGroupPerms(array("pamatuzstādījumi", "kontakti", "analytics", "valodas"));

	$entries = Page()->getNode(array(
		"filter"       => array(
			"created_by" => "controller",
			"view"       => "entry",
			"enabled"    => 0
		),
		"returnFields" => "id,data,enabled,controller,parent"
	));
	foreach ($entries as $entry) {
		if ($entry->data->published && $entry->data->schedule->state && strtotime($entry->data->schedule->datetime) <= time()) {
			if ($entry->controller == "news") {
				if ($entry->data->mail_to_subscribers && !$entry->data->schedule->triggered) {
					Page()->mailScheduledPost($entry);
				}
				Page()->cleanNewsCategories($entry->parent, $entry->language);
			}
			if ($entry->controller == "works") {
				Page()->cleanWorkCategories($entry->parent, $entry->language);
			}
			$entry->data->schedule->triggered = true;
			Page()->setNode(array(
				"data"    => $entry->data,
				"enabled" => 1,
				"id"      => $entry->id
			));
		}
	}
