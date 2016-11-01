<?php
	$parent = Page()->getNode($_GET["sid"]);
	if (!ActiveUser()->canWrite("node", $parent->id)) {
		Page()->accessDenied();
	}

	Page()->addBreadcrumb("Sadaļas", Page()->aHost . "structure/");
	Page()->addBreadcrumb($parent->title, Page()->aHost . Page()->controller . "/?sid=" . $_GET["sid"]);
	Page()->header();

	if (Page()->structIdCheck()) {
		if (!Page()->perPage) Page()->perPage = 20;

		if ($parent->subid == "all-events") {

			$childs = Page()->getNode(array(
				"filter"       => array(
					"parent"     => $parent->id,
					"controller" => "events",
					"view"       => "list",
				),
				"order"        => array("sort" => "ASC"),
				"returnFields" => "id,title,fullAddress,subid"
			));
		} else {
			$childs = array($parent);
		}

		$nodes = Page()->getNode(array(
			"filter"       => array(
				"parent"     => array_map(function ($n) { return $n->id; }, $childs),
				"controller" => Page()->controller,
				"view"       => "entry",
				"created_by" => "controller"
			),
			"order"        => array(
				"start" => "DESC"
			),
			"returnFields" => "title,time_added,fullAddress,id,time_updated,data,start,end,enabled",
			"limit"        => array("page" => Page()->pageCurrent, "perPage" => 50),
			"debug"        => false
		), $totalentries);
		//$pages = ceil(DataBase()->resultsFound/Page()->perPage);
		?>
		<nav class="sidebar">
			<a class="btn btn-add btn-lg btn-primary block" href="<?php echo Page()->aHost . Page()->controller ?>/edit/?sid=<?php echo $_GET["sid"] ?>" title="Pievienot notikumu">Pievienot notikumu</a>
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
					<h3 class="panel-title">Notikumu saraksts
						<small>(<?php echo $parent->title ?>)</small>
					</h3>
				</div>
				<div class="panel-body">
					<?php if (count($nodes)) { ?>

						<table width="100%" class="table table-condensed table-hover">
							<thead>
								<tr>
									<th>Nosaukums</th>
									<th width="150">Notikuma laiks</th>
									<th width="150">Pievienošanas laiks</th>
									<th width="90">Statuss</th>
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
											<td><?php if ($node->start == "0000-00-00 00:00:00") { ?>--<?php } else {
													echo $Com->getdate("1", strtotime($node->start), "lv-short") ?><?php print(date("Y") != date("Y", strtotime($node->start)) ? ' ' . date("Y", strtotime($node->start)) : '');
													if ($node->start != $node->end) {
														echo ' - '. $Com->getdate("1", strtotime($node->end), "lv-short") ?><?php print(date("Y") != date("Y", strtotime($node->end)) ? ' ' . date("Y", strtotime($node->end)) : '');
												 } ?>
													<?php } ?>
											</td>
											<td><?php echo $Com->getdate("1", strtotime($node->time_added), "lv-short") ?><?php print(date("Y") != date("Y", strtotime($node->time_added)) ? ' ' . date("Y", strtotime($node->time_added)) : ''); ?>, <?php echo date("H:i", strtotime($node->time_added)) ?></td>
											<td><?php
													if (!$node->enabled) {
														echo 'Melnraksts';
													} else echo 'Publicēts';
												?></td>
											<td class="actions">
												<a href="<?php echo Page()->aHost . Page()->controller ?>/edit/<?php echo $node->id ?>/?sid=<?= $_GET["sid"] ?>" class="btn btn-default btn-xs">Rediģēt</a>
											</td>
										</tr>
									<?php } ?>
							</tbody>
						</table>
					<?php } else {
						Page()->cmsInfotip(Page()->t("Šis saraksts ir tukšs. Pievieno kādu ierakstu!"), "yellow", "", "gear");
					} ?>
				</div>
				<?php if (ceil($totalentries / 50) > 1) { ?>
					<div class="panel-footer">
						<nav>
							<ul class="pagination">
								<?php Page()->paging(array(
									"pages"            => ceil($totalentries / 50),
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

	<?php } ?>
<?php Page()->footer(); ?>
