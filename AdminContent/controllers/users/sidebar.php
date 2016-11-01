<nav class="sidebar">
	<?php if (ActiveUser()->isAdmin() && Page()->action == "groups") { ?>
		<a href="<?php echo $this->adminHost . $this->controller . "/edit_group/" ?>" role="button" class="btn btn-lg btn-primary btn-add block">Pievienot grupu</a>
	<?php } ?>
	<?php if (ActiveUser()->isAdmin() && Page()->action == "list") { ?>
		<a href="<?php echo $this->adminHost . $this->controller . "/edit/" ?>" role="button" class="btn btn-lg btn-primary btn-add block">Pievienot lietotāju</a>
	<?php } ?>
	<ul class="sections">
		<li>
			<a class="<?php print(Page()->action == "list" ? 'active' : ''); ?>" href="<?php print(Page()->aHost); ?>users/list/"><span class="glyphicon glyphicon-user"></span> Lietotāji</a>
		</li>
		<?php if (ActiveUser()->isAdmin()) { ?>
			<li><a class="<?php print(Page()->action == "groups" ? 'active' : ''); ?>" href="<?php print(Page()->aHost); ?>users/groups/"><span class="mce-i-othericons ic-users"></span> Grupas</a></li>
			<li><a class="<?php print(Page()->action == "bans" ? 'active' : ''); ?>" href="<?php print(Page()->aHost); ?>users/bans/"><span class="mce-i-othericons ic-blocked"></span> IP Bani</a></li>
		<?php } ?>
	</ul>
</nav>