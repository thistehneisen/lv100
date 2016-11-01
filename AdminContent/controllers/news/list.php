<?php
	$parent = Page()->getNode($_GET["sid"]);

	if (!ActiveUser()->canWrite("node", $parent->id)) {
		Page()->accessDenied();
	}


	Page()->addBreadcrumb($parent->title, Page()->aHost . Page()->controller . "/?sid=" . $_GET["sid"]);
	Page()->header();

	if (Page()->structIdCheck()) {
		if (!Page()->perPage) Page()->perPage = 20;
		$totalItems= 0;
		$nodes = Page()->getNode(array(
			"filter"       => array(
				"parent"             => $_GET["sid"],
				"controller"         => Page()->controller,
				"view" => "entry",
				"created_by"         => "controller"
			),
			"order"        => array(
				"time_added" => "DESC"
			),
			"returnFields" => "title,time_added,fullAddress,id,time_updated,data",
			"limit"        => array("page" => Page()->pageCurrent, "perPage" => 50),
			"debug"        => false
		), $totalItems);
		?>
		<nav class="sidebar">
			<?php if (ActiveUser()->can("node","write",$parent->id)) { ?><a class="btn btn-add btn-lg btn-primary block" href="<?php echo Page()->aHost . Page()->controller ?>/edit/?sid=<?php echo $_GET["sid"] ?>" title="Pievienot ziņu">Pievienot ziņu</a><?php } ?>
		</nav>
		<section class="block">
			<?php if ($_SESSION['post_response']) { ?>
				<div class="alert alert-<?= $_SESSION['post_response'][1] ?> alert-dismissable">
					<button type="button" class="close" data-dismiss="alert" aria-label="Close">
						<span aria-hidden="true">&times;</span></button>
					<p><?php echo $_SESSION['post_response'][0] ?></p>
				</div>
				<?php unset($_SESSION['post_response']);
			} ?>
			<div class="panel panel-default">
				<div class="panel-heading clearfix">
					<h3 class="panel-title">Ziņu saraksts
						<small>(<?php echo $parent->title ?>)</small>
					</h3>
				</div>
				<div class="panel-body">
					<?php if (count($nodes)) { ?>
						<table width="100%" class="table table-condensed table-striped table-hover">
							<thead>
								<tr>
									<th>{{Title}}</th>
									<th width="175">Pievienošanas laiks</th>
									<th width="175">Rediģēšanas laiks</th>
									<th width="100">Statuss</th>
									<th width="1"></th>
								</tr>
							</thead>
							<tbody>
								<?php
									foreach ($nodes as $k => $node) {
										?>
										<tr>
											<td>
												<a href="<?= $node->fullAddress ?>" target="_blank"><?= $node->title ?></a>
											</td>
											<td><?php echo $Com->getdate("1", strtotime($node->time_added), "lv-short") ?>, <?php echo date("H:i", strtotime($node->time_added)) ?></td>
											<td><?php echo $Com->getdate("1", strtotime($node->time_updated), "lv-short") ?>, <?php echo date("H:i", strtotime($node->time_updated)) ?></td>
											<td><?php
													if ($node->data->published && $node->data->schedule->state && strtotime($node->data->schedule->datetime) > time()) {
														echo 'Ieplānots';
													} else if (!$node->data->published) {
														echo 'Melnraksts';
													} else echo 'Publicēts';
												?></td>
											<td class="actions">
										<?php if (ActiveUser()->canWrite(Page()->controller)) { ?><a href="<?php echo Page()->aHost . Page()->controller ?>/edit/<?php echo $node->id ?>/?sid=<?= $_GET["sid"] ?>" class="btn btn-default btn-xs">Rediģēt</a><?php } ?>
											</td>
										</tr>
									<?php } ?>
							</tbody>
						</table>
					<?php } else {
						Page()->cmsInfotip(Page()->t("Šobrīd šis saraksts ir tukšs. Pievieno kādu ierakstu!"), "yellow", "", false);
					} ?>
				</div>
				<?php if (ceil($totalItems / 50) > 1) { ?>
					<div class="panel-footer">
						<nav>
							<ul class="pagination">
								<?php Page()->paging(array(
									"pages"            => ceil($totalItems / 50),
									"delta"            => 5,
									"echo"             => true,
									"page"             => '<li><a href="%1$s">%2$s</a></li>',
									"active"           => '<li><a href="%1$s" class="active">%2$d</a></li>',
									"prev"             => '<li><a href="%1$s" class="%3$s" aria-label="Iepriekšējā"><span aria-hidden="true">&laquo;</span></a></li>',
									"next"             => '<li><a href="%1$s" class="%3$s" aria-label="Nākamā"><span aria-hidden="true">&raquo;</span></a></li>',
									"dontShowInactive" => false
								)) ?>
							</ul>
						</nav>
					</div>
				<?php } ?>
			</div>
		</section>
	<?php } else { ?>
		<div class="alert alert-warning">
			<p><strong>Nav atrasta neviena lapas sadaļa, kas būtu konfigurēta, lai izmantotu šo moduli.</strong> Dodies <a href="<?php print(Page()->aHost  . "structure/"); ?>">šeit</a> un pievieno kādu lapu norādot <strong><?php print(Page()->controllers[Page()->controller]->getName()); ?></strong> kā moduli.</p>
		</div>
	<?php } ?>
<?php Page()->footer(); ?>