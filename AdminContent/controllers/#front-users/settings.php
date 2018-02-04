<?php
if (!ActiveUser()->can(Page()->currentController, "pārvaldīt")) {
	Page()->accessDenied();
}

	Page()->addBreadcrumb("Uzstādījumi", $this->controller . "/settings/");

	$this->header();
	$this->incl($this->controllers[ $this->controller ]["path"] . "sidebar.php");

	$this->footer();