<?php

	$type = "list";
	if (isset($_GET["t"]) && in_array($_GET["t"], array("month", "list"))) $type = $_GET["t"];
	Page()->header();
?>

<div class="container calendar-<?php print($type); ?>">
	<h1 class="calendar-switch">
		<span><?php print(Node()->title); ?></span>
		<div class="switches">
			<a class="<?php print($type == "month" ? "active" : ""); ?>" href="<?php print(Page()->getURL(array("t"=>"month"))); ?>">
				<div class="ico"><?php include(Page()->bPath . 'assets/img/ico/ico-calendar1.svg'); ?></div>
				<span>Mēneša skats</span></a>
			<a class="<?php print($type == "list" ? "active" : ""); ?>" href="<?php print(Page()->getURL(array("t"=>"list"))); ?>">
				<div class="ico"><?php include(Page()->bPath . 'assets/img/ico/ico-calendar2.svg'); ?></div>
				<span>Saraksta skats</span></a>
		</div>
	</h1>
	<?php Page()->incl(Page()->bPath . "controllers/events/types/{$type}.php"); ?>

</div>
<?php Page()->footer(); ?>


