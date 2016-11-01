<?php

	switch (Page()->action) {
		case "contacts":
			Page()->addBreadcrumb("Kontakti", Page()->aHost . Page()->controller . "/contacts");
			break;
		case "stats":
			Page()->addBreadcrumb("Google Analytics", Page()->aHost . Page()->controller . "/stats");
			break;
		case "links":
			Page()->addBreadcrumb("Saites", Page()->aHost . Page()->controller . "/links");
			break;
		case "translate":
			Page()->addBreadcrumb("Valodas", Page()->aHost . Page()->controller . "/translate/front/");
			break;
		case "header_logo":
			Page()->addBreadcrumb("CMS izskats", Page()->aHost . Page()->controller . "/header_logo");
			break;
		default:
			Page()->addBreadcrumb("Pamatuzstādījumi", Page()->aHost . Page()->controller);
			break;
	}

	Page()->header();

	switch (Page()->action) {
		case "contacts":
			$action = "Kontakti";
			break;
		case "stats":
			$action = "Google Analytics";
			break;
		case "links":
			$action = "Saites";
			break;
		case "translate":
			$action = "Valodas";
			break;
		case "header_logo":
			$action = "CMS izskats";
			break;
		default:
			$action = "Pamatuzstādījumi";
			break;
	}

?>

<nav class="sidebar">
	<ul class="sections">
		<?php if (ActiveUser()->can(Page()->controller, "pamatuzstādījumi")) { ?>
			<li>
				<a class="home<?php echo Page()->action == "list" ? " active" : "" ?>" href="<?php echo Page()->aHost . Page()->controller ?>/">Pamatuzstādījumi</a>
			</li>
		<?php } ?>
		<?php if (ActiveUser()->can(Page()->controller, "kontakti")) { ?>
			<li>
				<a class="stats<?php echo Page()->action == "contacts" ? " active" : "" ?>" href="<?php echo Page()->aHost . Page()->controller ?>/contacts/">Kontakti</a>
			</li>
		<?php } ?>
		<?php if (ActiveUser()->can(Page()->controller, "analytics")) { ?>
			<li>
				<a class="stats<?php echo Page()->action == "stats" ? " active" : "" ?>" href="<?php echo Page()->aHost . Page()->controller ?>/stats/">Google Analytics</a>
			</li>
		<?php } ?>
		<?php if (ActiveUser()->can(Page()->controller, "valodas")) { ?>
			<li>
				<a class="comments<?php echo Page()->action == "translate" && Page()->reqParams[0] == "front" ? " active" : "" ?>" href="<?php echo Page()->aHost . Page()->controller ?>/translate/front/">Valodas</a>
			</li>
		<?php } ?>
		<?php if (ActiveUser()->isDev()) { ?>
			<li>
				<a class="comments<?php echo Page()->action == "header_logo" ? " active" : "" ?>" href="<?php echo Page()->aHost . Page()->controller ?>/header_logo/">CMS izskats</a>
			</li>
		<?php } ?>
		<li><a href="<?php echo Page()->adminHost ?>users/list/" class="users">CMS Lietotāji</a></li>
	</ul>
</nav>