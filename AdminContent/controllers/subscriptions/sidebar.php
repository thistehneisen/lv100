<nav class="sidebar">
	<a class="btn btn-lg btn-primary block icon excel" href="<?php echo Page()->aHost . Page()->controller ?>/export/" title="{{Add news}}">Eksportēt listes</a>
	<ul class="sections">
		<li>
			<a class="<?php print(Page()->action == "list" ? 'active' : ''); ?>" href="<?php echo Page()->adminHost ?><?php echo Page()->controller ?>/list/"><span class="glyphicon glyphicon-list"></span> Abonementi</a>
		</li>
		<?php /*<li>
			<a class="<?php print(Page()->action == "manual" ? 'active' : ''); ?>" href="<?php echo Page()->adminHost ?><?php echo Page()->controller ?>/manual/"><span class="glyphicon glyphicon-send"></span> Manuāla izsūtīšana</a>
		</li>
		<li>
			<a class="<?php print(Page()->action == "settings" ? 'active' : ''); ?>" href="<?php echo Page()->adminHost ?><?php echo Page()->controller ?>/settings/"><span class="glyphicon glyphicon-cog"></span> Uzstādījumi</a>
		</li>*/ ?>
	</ul>
</nav>
