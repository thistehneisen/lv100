<?php
	Page()->registerController()
		->setGroupPerms(array("pārvaldīt"))
		->setName("Media");

	$this->extensions = array(
		"photo"    => array("jpg", "jpeg", "png", "bmp", "gif"),
		"audio"    => array("mp3", "mp2", "m4a", "ogg", "oga"),
		"video"    => array("m4v", "mp4", "mpg", "mpeg", "avi", "wmv", "mov"),
		"document" => array("docx", "doc", "xlsx", "xls", "pdf")
	);

	$this->editablePhotos = array("jpg", "jpeg", "png", "gif");
	$this->editablePhotoTypes = array(IMAGETYPE_JPEG, IMAGETYPE_GIF, IMAGETYPE_PNG);

	$this->mediaCategories = array("photo", "other");