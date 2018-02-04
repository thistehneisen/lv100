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
<?php
	$filter = array(
		"parent"     => Node()->id,
		"controller" => "events",
		"view"       => "entry",
		"enabled"    => 1,
		"deleted"    => 0,
		"<SQL>"      => "(DATE(`start`)<=NOW() AND DATE(`end`)>=NOW()) OR DATE(`start`)>NOW()"
	);

	$nodes = Page()->getNode(array(
		"filter"       => $filter,
		"order"        => array("start" => "ASC"),
		"returnFields" => "id,title,fullAddress,start,end,category,cover,description,subid",
		"debug"        => false
	));

	$months = array();
	foreach ($nodes as $k => $node) {
		$month1 = date("Ym",strtotime($node->start));
		$month2 = date("Ym",strtotime($node->end));
		for($i = $month1; $i<=$month2; $i++) {
			$months[$i.""][] = &$nodes[$k];
		}
	}
	//Page()->debug($months);
?>

<?php foreach ($months as $month => $events) {
	$monthDate = mktime(0,0,0,substr($month,4,2),1,substr($month,0,4));

	?>
<h3><?php print(ucfirst(strftime("%B %Y", $monthDate))); ?></h3>
<article class="masonry">
	<?php foreach ($events as $node) { ?>
		<a class="item item-vertical" href="<?php print($node->fullAddress); ?>">
			<?php if ($node->cover) { ?>
				<div class="img-ct"><img src="<?php print($this->host . $node->cover); ?>" alt="">
				</div>
			<?php } ?>
			<div class="text-ct">
				<?php if (!$node->subid) {
					$ns = strtotime($node->start);
					$ne = strtotime($node->end);
					?>
				<span class="date"><b><?php print(date("j", $ns) . (date("Ym", $ns) == date("Ym", $ne) && date("j", $ns) != date("j", $ne) ? '.-' . date("j", $ne) : '')); ?>.</b> <?php print(strftime("%B", $ns)); ?>
					<?php if (date("Ym", $ns) != date("Ym", $ne) && date("Ymj", $ns) != date("Ymj", $ne)) { ?>
						<b> - <?php print(date("j", $ne)); ?>.</b> <?php print(strftime("%B", $ne)); ?><?php } ?>
				</span>
				<?php } ?>
				<h2><?php print($node->title); ?></h2>
				<?php if ($node->description) { ?>
					<p><?php print($node->description); ?></p>
				<?php } ?>
			</div>
		</a>
	<?php } ?>

</article>
<?php } ?>
