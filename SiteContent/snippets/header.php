<header class="header">
	<div class="container">
		<a href="<?php print(Page()->root->fullAddress); ?>" class="logo"><?php include(Page()->bPath . 'assets/img/lv100-logo.svg'); ?></a>
		<div class="mob-nav">
			<div class="social pull-right">
				<a target="_blank" href="#"><?php include(Page()->bPath . 'assets/img/ico/ico-facebook.svg'); ?></a>
				<a target="_blank" href="#"><?php include(Page()->bPath . 'assets/img/ico/ico-twitter.svg'); ?></a>
				<a target="_blank" href="#"><?php include(Page()->bPath . 'assets/img/ico/ico-instagram.svg'); ?></a>
			</div>

			<nav>
				<?php
					if (is_array(Page()->menu)) {
						foreach (Page()->menu as $item) { ?>
							<a href="<?php print($item->fullAddress); ?>"><?php print($item->title); ?></a>
						<?php }
					} ?>
			</nav>
		</div>
		<a href="#" class="nav-toggle hidden-md hidden-lg">
			<hr>
			<hr>
			<hr>
		</a>
	</div>
</header>