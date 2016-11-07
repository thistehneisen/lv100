<div id="calendar">
	<div class="header">
		<h2></h2>
		<div class="right"><?php include(Page()->bPath . 'assets/img/ico/arrow-right.svg'); ?></div>
		<div class="left"><?php include(Page()->bPath . 'assets/img/ico/arrow-left.svg'); ?></div>
	</div>
	<?php /*<div class="filter">
		<a href="#">Izstādes</a>
		<a href="#">Koncerti</a>
		<a href="#">Lekcijas</a>
		<a href="#">Kinoseansi</a>
		<a href="#">Uzvedumi</a>
		<a href="#">Performances</a>
		<a href="#">Diskusijas</a>
		<a href="#">Bērniem</a>
	</div>*/ ?>

	<div class="weekdays">
		<div>P</div>
		<div>O</div>
		<div>T</div>
		<div>C</div>
		<div>P</div>
		<div>S</div>
		<div>Sv</div>
	</div>

	<?php
		$time = $today = Page()->time;
		if (isset($_GET["month"])) {
			$time = strtotime($_GET["month"] . "-01");
		}
		$time = mktime(0, 0, 0, date("n", $time) - 1, 1, date("Y", $time));
	?>
	<div class="month" data-month="<?php print(date("Y-m", $time)); ?>" data-monthvisible="<?php print(ucfirst(strftime("%B %Y", $time))); ?>">
		<?php
			Page()->widget("calendar-month", array("time" => $time));
		?>
	</div>

	<?php
		$time = $today = Page()->time;
		if (isset($_GET["month"])) {
			$time = strtotime($_GET["month"] . "-01");
		}
	?>
	<div class="month new" data-month="<?php print(date("Y-m", $time)); ?>" data-monthvisible="<?php print(ucfirst(strftime("%B %Y", $time))); ?>">
		<?php
			Page()->widget("calendar-month", array("time" => $time));
		?>
	</div>

	<?php
		$time = $today = Page()->time;
		if (isset($_GET["month"])) {
			$time = strtotime($_GET["month"] . "-01");
		}
		$time = mktime(0, 0, 0, date("n", $time) + 1, 1, date("Y", $time));
	?>
	<div class="month" data-month="<?php print(date("Y-m", $time)); ?>" data-monthvisible="<?php print(ucfirst(strftime("%B %Y", $time))); ?>">
		<?php
			Page()->widget("calendar-month", array("time" => $time));
		?>
	</div>

</div>