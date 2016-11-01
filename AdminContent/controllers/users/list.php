<?php

	if (!ActiveUser()->canAccessPanel()) {
		Page()->accessDenied();
	}
	$this->addBreadcrumb("Uzstādījumi", $this->adminHost . "cpanel/");
	$this->addBreadcrumb("CMS lietotāji", $this->adminHost . "users/list/");
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
				<h3 class="panel-title">{{Users}}</h3>
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
							<th>{{Users: Name}}</th>
							<th width="140">{{Users: Last access}}</th>
							<th width="60"></th>
						</tr>
					</thead>
					<?php
						$totalPages = 1;
						$users = Users()->getUsers($this->pageCurrent, 20, $totalpages);
					?>
					<tbody>
						<?php foreach ((array)$users as $user) {
							$access = array();
							if ($user->isAdmin()) $access[] = "A";
							if ($user->isDev()) $access[] = "DEV";

							if (!ActiveUser()->isAdmin() && $user->id != ActiveUser()->id) continue;
							?>
							<tr>
								<td>[#<?php echo $user->id ?>,<?php echo count($access) ? join("|", $access) : "*" ?>]</td>
								<td><strong><?php $user->echoName() ?></strong></td>
								<td><?php if ($user->last_access != "0000-00-00 00:00:00") { ?><?php echo $Com->getdate("7", strtotime($user->last_access), "lv-short") ?>, <?php echo date("H:i", strtotime($user->last_access)) ?><?php } else { ?>{{Never}}<?php } ?></td>
								<td class="actions">
									<a href="<?php echo $this->adminHost . $this->controller ?>/edit/<?php echo $user->id ?>" class="btn btn-default btn-xs pull-right<?php if ((!ActiveUser()->isAdmin() && $user->id != ActiveUser()->id) || ($user->isDev() && $user->id != ActiveUser()->id)) { ?> disabled<?php } ?>" role="button">{{Edit}}</a>
								</td>
							</tr>
						<?php } ?>
					</tbody>
				</table>
			</div>
			<?php if (ceil($totalpages) > 1) { ?>
				<div class="panel-footer">
					<nav>
						<ul class="pagination">
							<?php $this->paging(array(
								"pages"            => $totalpages,
								"delta"            => 5,
								"echo"             => true,
								"page"             => '<li><a href="%1$s">%2$s</a></li>',
								"active"           => '<li><a href="%1$s" class="active">%2$d</a></li>',
								"prev"             => '<li><a href="%1$s" class="%3$s" aria-label="{{{Previous}}}"><span aria-hidden="true">&laquo;</span></a></li>',
								"next"             => '<li><a href="%1$s" class="%3$s" aria-label="{{{Next}}}"><span aria-hidden="true">&raquo;</span></a></li>',
								"dontShowInactive" => false
							)) ?>
						</ul>
					</nav>
				</div>
			<?php } ?>

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