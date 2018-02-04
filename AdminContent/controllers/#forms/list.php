<?php

	Page()->addBreadcrumb("Formas", Page()->aHost . Page()->controller . "/");
	Page()->header();

	$forms = DataBase()->getRows("SELECT *, (SELECT COUNT(*) FROM %s WHERE `form_id`=`form`.`id`) as `count` FROM %s as `form` WHERE `deleted`=0 ORDER BY `added` DESC", DataBase()->forms_data, DataBase()->forms);
?>
	<nav class="sidebar">
		<a class="btn btn-add btn-primary btn-lg block" href="<?php echo Page()->aHost . Page()->controller ?>/edit/" title="Pievienot formu"><span>Pievienot formu</span></a>
	</nav>
	<section class="block">
		<?php if ($_SESSION['post_response']) { ?>
			<div class="alert alert-<?= $_SESSION['post_response'][1] ?> alert-dismissable">
				<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<p><?php echo $_SESSION['post_response'][0] ?></p>
			</div>
			<?php unset($_SESSION['post_response']);
		} ?>
		<div class="panel panel-default">
			<div class="panel-heading clearfix">
				<h3 class="panel-title">Formas</h3>
			</div>
			<div class="panel-body">
				<?php if (count($forms)) { ?>
						<table width="100%" class="table table-condensed table-striped table-hover">
							<thead>
								<tr>
									<th>Nosaukums</th>
									<th width="175">Pievienošanas laiks</th>
									<th width="50">Dati</th>
									<th width="100">Statuss</th>
									<th width="1"></th>
								</tr>
							</thead>
							<tbody>
								<?php
									foreach ($forms as $k => $form) {
										?>
										<tr>
											<td><strong><?= $form["title"] ?></strong></td>
											<td><?= $form["added"] ?></td>
											<td style="text-align: center;">
												<a class="text-primary" href="<?php echo(Page()->aHost . Page()->controller); ?>/show/<?php echo $form["id"]; ?>/"><strong><?= $form["count"] ?></strong></a>
											</td>
											<td><?php
													if ($form["enabled"]) {
														echo 'Publicēta';
													} else echo 'Melnraksts';
												?></td>
											<td class="actions" style="vertical-align: middle;">
												<a href="<?php echo Page()->aHost . Page()->controller ?>/edit/<?php echo $form["id"] ?>/" class="actionbutton">Labot</a>
											</td>
										</tr>
									<?php } ?>
							</tbody>
						</table>
				<?php
				} else {
					Page()->cmsInfotip("Pašlaik formu saraksts ir tukšs.", "yellow", "", "gear");
				}
				?>
			</div>
		</div>
	</section>
<?php Page()->footer(); ?>