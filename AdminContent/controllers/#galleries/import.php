<?php

	$dg = glob(Page()->controllers[ Page()->controller ]->getPath() . "import_data/*", GLOB_ONLYDIR);

	$galleries = array();

	foreach ($dg as $d) {
		$file = file($d . "/info.txt");
		$galleries[] = array(
			"title"      => trim($file[0]),
			"time_added" => trim($file[1]),
			"slug"       => trim($file[2])
		);
	}

	$ids = array();
	$parent = false;
	$idx = false;

	$language = "lv";

	foreach ($galleries as $gal) {

		$node = false;

		if (!$node) {
			$parent = Page()->getNode(array(
				"filter"        => array(
					"controller" => "galleries",
					"view"       => "list",
					"language"   => $language
				),
				"returnResults" => "first"
			));
		}

		$settings = array(
			"title"       => $gal["title"],
			"description" => "",
			"enabled"     => 1,
			"subid"       => 0,
			"controller"  => Page()->controller,
			"time_added"  => $gal["time_added"],
			"view"        => "gallery"
		);

		$settings["parent"] = $parent->id;
		$settings["slug"] = $gal["slug"];
		$settings["cover"] = Page()->getEmptyImage(300, 300);

		$id = Page()->setNode($settings);

		$gallery = DataBase()->getVar("SELECT MAX(`gallery_id`) FROM %s", DataBase()->galleries) + 1;

		DataBase()->insert("galleries", array(
			"gallery_id" => $gallery,
			"node_id"    => $id,
			"language"   => $language
		), true);
	}