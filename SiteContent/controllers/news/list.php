<?php Page()->header();

	$entries = Page()->getNode(array(
		"filter" => array(
			"parent"  => Node()->id,
			"view"    => "entry",
			"enabled" => 1
		),
		"order"  => array("time_added" => "DESC"),
		"limit"  => array("page" => Page()->pageCurrent, "perPage" => Page()->pager["news"])
	), $totalEntries);
	$pages = ceil($totalEntries / Page()->pager["news"]);
?>

	<div class="container">
		<div class="lg-2-3 xs-1-1 sm-1-1 md-3-4">
			<div class="content">
				<header>
					<h1><?php print(Node()->title); ?></h1>
				</header>

				<?php foreach ($entries as $entry) { ?>
					<a class="item item-horizontal<?php print(!$entry->cover ? ' no-cover' : ''); ?>" href="<?php print($entry->fullAddress); ?>">
						<?php if ($entry->cover) { ?>
							<div class="img-ct">
								<img src="<?php print(Page()->host . $entry->cover); ?>" alt="">
							</div>
						<?php } ?>
						<div class="text-ct">
							<span class="date"><b><?php print(strftime("%e", strtotime($entry->time_added))); ?>.</b> <?php print(strftime("%B", strtotime($entry->time_added))); ?>
								<?php if (date("Y") != substr($entry->time_added,0,4)) {?>, <?php print(substr($entry->time_added,0,4)); ?>. gads<?php } ?>
							</span>
							<h2><?php print($entry->title); ?></h2>
							<?php if ($entry->description) { ?>
								<p><?php print($entry->description); ?></p>
							<?php } ?>
						</div>
					</a>
				<?php } ?>
			</div>

		</div>

		<div class="sidebar lg-1-3 xs-1-1 sm-1-1 md-1-4">

			<?php Page()->widget("hashtag-cloud"); ?><?php Page()->widget("similar-events"); ?>
		</div>
	</div>
<?php Page()->footer(); ?>