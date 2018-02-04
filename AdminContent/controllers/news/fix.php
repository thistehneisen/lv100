<?php


	$structs = Page()->getNode(array(
		"filter" => array(
			"parent" => 463
		)
	));


	Page()->debug($structs);

	/*foreach ($structs as $struct) {
		Page()->setNode(array(
			"parent" => 435,
			"id" => $struct->id
		));
	}*/