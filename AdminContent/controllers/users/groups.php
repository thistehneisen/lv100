<?php

	if (!ActiveUser()->isAdmin()) {
		Page()->accessDenied();
	}

	$this->addBreadcrumb("Uzstādījumi", $this->adminHost . "cpanel/");
	$this->addBreadcrumb("CMS lietotāju grupas", $this->adminHost . "users/groups/");

	$this->header();

?>

<?php Page()->incl(Page()->controllers[ Page()->controller ]->getPath() . "sidebar.php"); ?>

	<section class="block">
		<?php if ($_SESSION['post_success']) { ?>
			<div class="alert alert-success">
				<strong>OK!</strong>
				<?php echo $_SESSION['post_success'] ?>
			</div>
			<?php unset($_SESSION['post_success']);
		} ?>
		<div class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title">Grupas</h3>
			</div>
			<?php if ($_SESSION['post_success']) { ?>
				<section class="alert alert-success">
					<strong>OK!</strong>
					<?php echo $_SESSION['post_success'] ?>
				</section>
				<?php unset($_SESSION['post_success']);
			} ?>
			<div class="panel-body">
				<table class="table table-condensed table-striped table-hover">
					<thead>
						<tr>
							<th width="30">ID</th>
							<th>Nosaukums</th>
							<th width="400">Apraksts</th>
							<th width="60"></th>
						</tr>
					</thead>
					<?php
						$totalPages = 1;
						$groups = DataBase()->getRows("SELECT * FROM %s ORDER BY `id` ASC", DataBase()->user_groups);
					?>
					<tbody>
						<?php foreach ((array)$groups as $group) {

							?>
							<tr>
								<td>[#<?php print($group["id"]); ?>]</td>
								<td><strong><?php print($group["name"]); ?></strong></td>
								<td><?php print($group["description"]); ?></td>
								<td class="actions">
									<a href="<?php echo $this->aHost . $this->controller ?>/edit_group/<?php echo $group["id"] ?>/" class="btn btn-default btn-xs pull-right<?php if ($group["builtin"]) { ?> disabled<?php } ?>" role="button">Labot</a>
								</td>
							</tr>
						<?php } ?>
					</tbody>
				</table>
			</div>

		</div>
	</section>
	<style type="text/css">
		table.table tr td.actions a.btn {
			visibility: hidden;
		}

		table.table tr:hover td.actions a.btn {
			visibility: visible;
		}
	</style>
<?php
	$this->footer();
?>