<?php

	$year = date("Y", $time);
	$month = date("n", $time);
	$days = date("t", $time);
	$firstDay = date("w", mktime(0, 0, 0, $month, 1, $year));

	$monthArray = array();
	$monthArray[] = array_fill(0, ($firstDay == 0 ? 6 : $firstDay - 1), array("in" => false, "nr" => 55, "ec" => 0));
	$cw = null;
	for ($cd = 1; $cd <= $days; $cd++) {
		if (date("w", mktime(0, 0, 0, $month, $cd, $year)) == 1) $monthArray[] = array();
		$cw = &$monthArray[ count($monthArray) - 1 ];
		$cw[] = array("in" => true, "nr" => $cd, "ec" => 0, "nd" => date("Y-m-d", mktime(0, 0, 0, $month, $cd, $year)));
	}

	$prevMonthTime = mktime(0, 0, 0, $month - 1, 1, $year);
	$nextMonthTime = mktime(0, 0, 0, $month + 1, 1, $year);

	if (count($cw) < 7) {
		$j = 0;
		for ($i = count($cw); $i <= 6; $i++) {
			$j++;
			$cw[ $i ] = array("in" => false, "nr" => $j, "ec" => 0, "nd" => date("Y-m-d", mktime(0, 0, 0, $month, $days + $j, $year)));
		}
	}

	$j = 1;
	for ($i = 6; $i >= 0; $i--) {
		if ($monthArray[0][ $i ]["in"] == false) {
			$j--;
			$jd = mktime(0, 0, 0, $month, $j, $year);
			$monthArray[0][ $i ]["nr"] = date("d", $jd);
			$monthArray[0][ $i ]["nd"] = date("Y-m-d", $jd);
		}
	}

	$sqlStart = $monthArray[0][0]["nd"];
	$sqlEnd = $monthArray[ count($monthArray) - 1 ][6]["nd"];

	$filter = array(
		"parent"     => Node()->id,
		"controller" => "events",
		"view"       => "entry",
		"enabled"    => 1,
		"deleted"    => 0,
		"<SQL>"      => "`subid`!=1 AND ((DATE(`start`)<='" . $sqlStart . "' AND DATE(`end`)>='" . $sqlStart . "') OR (DATE(`start`)>'" . $sqlStart . "' AND DATE(`start`)<='" . $sqlEnd . "'))"
	);

	$nodes = Page()->getNode(array(
		"filter"       => $filter,
		"order"        => array("start" => "ASC"),
		"returnFields" => "id,title,fullAddress,start,end,category",
		"debug"        => false
	));

	Page()->validEvents = $nodes;
	foreach ($monthArray as $week => $days) {
		foreach ($days as $day => $data) {
			$events = Page()->getEventsByDate($data["nd"]);
			$monthArray[ $week ][ $day ]["ec"] = count($events);
			$monthArray[ $week ][ $day ]["ed"] = $events;
		}
	}

?>

<?php foreach ($monthArray as $week) { ?>
	<div class="week">
		<?php foreach ($week as $day) { ?>
			<div data-nd="<?php print($day["nd"]); ?>" class="day <?php if (!$day["in"]) { ?>other<?php } ?> <?php if ($day["nd"] == date("Y-m-d", Page()->time)) { ?>today<?php } ?>">
				<div class="day-number"><?php print($day["nr"]); ?></div>
				<div class="day-events">(<?php print($day["ec"]); ?>)</div>
			</div>
		<?php } ?>
		<div class="events">
			<?php $i = 0;
				foreach ($week as $day) {
					$i++ ?>
					<div class="elst d<?php print($i); ?>">
						<a class="close">×</a>
						<?php
							if (!$day["ec"]) {
								?>
								<p>Šajā dienā nav notikumu</p>
							<?php } else { ?><?php foreach ($day["ed"] as $event) { ?>
								<a href="<?php print($event->fullAddress); ?>"><?php print($event->title); ?></a>
							<?php } ?><?php } ?>
					</div>
				<?php } ?>
		</div>
	</div>
<?php } ?>
