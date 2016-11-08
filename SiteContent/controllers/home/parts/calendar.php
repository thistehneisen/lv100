<?php
	$filter = array(
		"controller" => "events",
		"view"       => "entry",
		"enabled"    => 1,
		"deleted"    => 0,
		"<SQL>"      => "(DATE(`start`)<=NOW() AND DATE(`end`)>=NOW()) OR DATE(`start`)>NOW()"
	);

	$nodes = Page()->getNode(array(
		"filter"       => $filter,
		"order"        => array("start" => "ASC"),
		"returnFields" => "id,title,fullAddress,start,end,category,cover,description",
		"limit"        => array("page" => 0, "perPage" => 9),
		"debug"        => false
	));

?>
<section>
	<div class="container">
		<header>
			<h2>Kas plānots Latvijas simtgades ietvaros:</h2>
		</header>
		<article class="masonry">

			<?php foreach ($nodes as $node) {
				$ns = strtotime($node->start);
				$ne = strtotime($node->end);
				?>
				<a class="item item-vertical" href="<?php print($node->fullAddress); ?>">
					<?php if ($node->cover) { ?>
						<div class="img-ct"><img src="<?php print($this->host . $node->cover); ?>" alt="">
						</div>
					<?php } ?>
					<div class="text-ct">
						<?php if (!$node->subid) { ?>
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

	</div>
</section>
