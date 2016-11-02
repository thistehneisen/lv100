<?php Page()->header(); ?>

	<div class="container">
		<div class="lg-2-3 xs-1-1 sm-1-1 md-3-4 page bodytext">
			<div class="content">
				<header>
					<h1><?php print(Node()->title); ?></h1>
					<span class="date"><?php print(strftime("%d.%m.%Y", strtotime(Node()->time_updated))); ?></span>
				</header>

				<?php print(Node()->content); ?>
			</div>

		</div>

		<div class="sidebar lg-1-3 xs-1-1 sm-1-1 md-1-4">

			<?php Page()->widget("hashtag-cloud"); ?><?php Page()->widget("similar-events"); ?><?php Page()->widget("next-events"); ?>

		</div>
	</div>
<?php Page()->footer(); ?>