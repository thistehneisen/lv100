<?php
	if (!ActiveUser()->canAccessPanel()) {
		Page()->accessDenied();
	}

	$nodeId = Page()->lastNodeUpdated;
	$galleryAssocs = Page()->getOption("node_galleries_assoc", array());
	$galleryAssocs[$nodeId] = (int)$_POST["custom_gallery_id"];
	Page()->setOption("node_galleries_assoc", $galleryAssocs);

?>