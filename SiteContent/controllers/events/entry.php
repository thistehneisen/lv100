<?php Page()->header(); ?>

	<div class="container opened-event">
		<div class="lg-2-3 xs-1-1 sm-1-1 md-3-4 page bodytext">
			<div class="content">
				<header>
					<h1><?php print(Node()->title); ?></h1>
					<span class="date"><?php print(date("d.m.Y", strtotime(Node()->time_added))); ?></span>
				</header>
				<?php if (Node()->cover) { ?>
					<img src="<?php print(Page()->host . Node()->cover); ?>" alt="">
				<?php } ?>
				<div class="event-meta">
					<?php if (Node()->data->place->name) { ?>
						<span>
							<div class="ico"><?php include(Page()->bPath . 'assets/img/ico/ico-location.svg'); ?></div>
							<p><b>Vieta:</b> <?php print(Node()->data->place->name); ?></p>
						</span>
					<?php } ?>
					<span>
						<div class="ico"><?php include(Page()->bPath . 'assets/img/ico/ico-calendar1.svg'); ?></div>
						<p>
							<b>Datums:</b><?php print(date("d.m.Y", strtotime(Node()->start))); ?><?php if (date("d.m.Y", strtotime(Node()->start)) != date("d.m.Y", strtotime(Node()->end))) { ?> — <?php print(date("d.m.Y", strtotime(Node()->end))); ?><?php } ?>
						</p>
					</span>
					<?php if (date("H:i", strtotime(Node()->start)) != "00:00") { ?>
						<span>
							<div class="ico"><?php include(Page()->bPath . 'assets/img/ico/ico-clock.svg'); ?>
							</div>
							<p>
								<b>Laiks:</b><?php print(date("H:i", strtotime(Node()->start))); ?><?php if (date("H:i", strtotime(Node()->end)) != "23:59") { ?> — <?php print(date("H:i", strtotime(Node()->end))); ?><?php } ?>
							</p>
						</span>
					<?php } ?>
				</div>

				<?php if (Node()->data->extra) { ?>
					<div class="event-notes">
						<?php foreach ((array)Node()->data->extra as $e) { ?>
							<div class="note"><p><?php print($e->value); ?></p></div>
						<?php } ?>
					</div>
				<?php } ?>

				<?php print(Node()->content); ?>

			</div>
			<?php Page()->widget("share"); ?>

		</div>

		<div class="sidebar lg-1-3 xs-1-1 sm-1-1 md-1-4">

			<?php Page()->widget("hashtag-cloud"); ?>

			<?php Page()->widget("similar-events"); ?>

			<?php Page()->widget("next-events"); ?>

		</div>
	</div>
<?php Page()->footer(); ?>