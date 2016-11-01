<?php
	if (Page()->isAdminInterface && ActiveUser()->canRead(Page()->contentController)) {
		Page()->addNav("Komentāri", Page()->currentController . "/");
	}


	Page()->registerController()
		->setGroupPerms(array("skatīt","labot"))
		->setName("Komentāri");
