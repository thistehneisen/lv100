<?php

	$nodeId = Page()->lastNodeUpdated;
	$formAssocs =Settings()->get("node_forms_assoc", "");
	$formAssocs[$nodeId] = (int)$_POST["custom_form_id"];
	Settings()->set("node_forms_assoc", $formAssocs, "");
