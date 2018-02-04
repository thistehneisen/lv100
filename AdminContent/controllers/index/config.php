<?php

	Page()->language_labels = array(
		"lv" => "{{Language: Latvian}}",
		"ru" => "{{Language: Russian}}",
		"en" => "{{Language: English}}",
		"et" => "{{Language: Estonian}}",
		"lt" => "{{Language: Lithuanian}}",
		"de" => "{{Language: German}}",
		"es" => "{{Language: Spanish}}",
		"fr" => "{{Language: French}}",
		"fi" => "{{Language: Finnish}}"
	);

	if (Page()->isAdminInterface) {
		Page()->addBreadcrumb("Sākums", Page()->adminHost);
	}

	Page()->registerController()
		->setName("CMS");
