<div id="calendar">
	<div class="header">
		<h2>Oktobris 2016</h2>
		<div class="right"><?php include(Page()->bPath . 'assets/img/ico/arrow-right.svg'); ?></div>
		<div class="left"><?php include(Page()->bPath . 'assets/img/ico/arrow-left.svg'); ?></div>
	</div>
	<div class="filter">
		<a href="#">Izstādes</a>
		<a href="#">Koncerti</a>
		<a href="#">Lekcijas</a>
		<a href="#">Kinoseansi</a>
		<a href="#">Uzvedumi</a>
		<a href="#">Performances</a>
		<a href="#">Diskusijas</a>
		<a href="#">Bērniem</a>
	</div>

	<div class="weekdays">
		<div>P</div>
		<div>O</div>
		<div>T</div>
		<div>C</div>
		<div>P</div>
		<div>S</div>
		<div>Sv</div>
	</div>

	<div class="month">
		<?php include('snippets/month.php'); ?>
	</div>

	<div class="month new">
		<?php include('snippets/month.php'); ?>
	</div>

	<div class="month">
		<?php include('snippets/month.php'); ?>
	</div>

</div>