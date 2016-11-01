<?php

	if (!ActiveUser()->isAdmin()) {
		Page()->accessDenied();
	}

	$throotle = Settings()->get("loginThrootle", "");
	if (!$throotle) $throotle = array();

	if ($_GET["delete"]) {
		if (isset($throotle[ $_GET["delete"] ])) {
			unset($throotle[ $_GET["delete"] ]);
			Settings()->set("loginThrootle", $throotle, "");
		}
		Page()->addCmsInfotip("IP adreses liegums dzēsts.", "success", "");
		header("Location: {$_SERVER["HTTP_REFERER"]}");
		exit;
	}

	$this->addBreadcrumb("Uzstādījumi", $this->adminHost . "cpanel/");
	$this->addBreadcrumb("CMS lietotāji", $this->adminHost . "users/list/");
	$this->addBreadcrumb("IP adrešu liegumi", $this->adminHost . "users/bans/");
	$this->header();

?>

<?php Page()->incl(Page()->controllers[ Page()->controller ]->getPath() . "sidebar.php"); ?>

	<section class="block">
		<?php if ($_SESSION['post_success']) { ?>
			<section class="alert alert-success">
				<strong>OK!</strong>
				<?php echo $_SESSION['post_success'] ?>
			</section>
			<?php unset($_SESSION['post_success']);
		} ?>
		<div class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title">IP adrešu liegumi</h3>
			</div>

			<div class="panel-body">
				<table class="table table-condensed table-striped table-hover">
					<thead>
						<tr>
							<th>IP Adrese</th>
							<th width="225">{{Users: Last access}}</th>
							<th width="50"></th>
						</tr>
					</thead>

					<tbody>
						<?php foreach ((array)$throotle as $k => $t) {
							if ($t >= 5) {
								?>
								<tr>
									<td><strong><?php print($k); ?></strong></td>
									<td><?php
											$last = $t[ count($t) - 1 ];
											print(strftime("%F %X", $last["t"]));
										?></td>
									<td class="actions">
										<a data-confirm="Vai Tu esi pārliecināts?" href="<?php echo $this->adminHost . $this->controller ?>/bans/?delete=<?php print($k); ?>" class="btn btn-danger btn-xs pull-right" role="button">Dzēst</a>
									</td>
								</tr>
							<?php }
						} ?>
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