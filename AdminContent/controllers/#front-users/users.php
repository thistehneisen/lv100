<?php
	if (!ActiveUser()->can(Page()->currentController, "pārvaldīt")) {
		Page()->accessDenied();
	}

	if (isset($_GET["toggle_block"])) {
		$blockedUsers = Settings()->get("blocked_users");
		if (!is_array($blockedUsers)) $blockedUsers = array();
		$alreadyBlocked = false;
		$foundidx = false;
		foreach ($blockedUsers as $k => $user) {
			if ($user["type"] == $_GET["account_type"] && $user["id"] == $_GET["toggle_block"]) {
				$alreadyBlocked = true;
				$foundidx = $k;
				break;
			}
		}

		$user = DataBase()->getRow("SELECT * FROM %s WHERE `id`='%s'", DataBase()->table($_GET["account_type"] == "local" ? "front_users": "social_accounts"), $_GET["toggle_block"]);
		if (!$alreadyBlocked) {
			$blockedUsers[] = array("type" => $_GET["account_type"], "id" => $_GET["toggle_block"]);
			Settings()->set("blocked_users", $blockedUsers);
			Page()->addCmsInfotip("Lietotājs <strong>" . htmlspecialchars($user["first_name"] . ' ' . $user["last_name"]) . "</strong> bloķēts.", "success");
		} else {
			unset($blockedUsers[ $foundidx ]);
			Settings()->set("blocked_users", $blockedUsers);
			Page()->addCmsInfotip("Lietotājs <strong>" . htmlspecialchars($user["first_name"] . ' ' . $user["last_name"]) . "</strong> atbloķēts.", "success");
		}

		header("Location: {$_SERVER["HTTP_REFERER"]}");
		exit;
	}
	if (isset($_GET["delete"])) {

		$user = DataBase()->getRow("SELECT * FROM %s WHERE `id`='%s'", DataBase()->table($_GET["account_type"] == "local" ? "front_users": "social_accounts"), $_GET["delete"]);
		if ($user) {
			DataBase()->queryf("DELETE FROM %s WHERE `id`='%s'", DataBase()->table($_GET["account_type"] == "local" ? "front_users": "social_accounts"), $_GET["delete"]);
			Page()->addCmsInfotip("Lietotājs <strong>" . htmlspecialchars($user["first_name"] . ' ' . $user["last_name"]) . "</strong> dzēsts.", "success");
		}

		header("Location: {$_SERVER["HTTP_REFERER"]}");
		exit;
	}

	$this->header();

	$map = array(
		"local" => "e-pasts",
		"dr"    => "draugiem.lv",
		"fb"    => "facebook.com",
		"go"    => "googleplus.com",
		"tw"    => "twitter.com"
	);

	DataBase()->countResults = true;
	$users = DataBase()->getRows("SELECT `first_name`,`last_name`,`time_registered`,`last_login`,`id`, 'local' `network` FROM %s WHERE `deleted`=0 UNION SELECT `first_name`,`last_name`,`time_added` `time_registered`, `time_updated` `last_login`,`id`,`network` FROM %s ORDER BY `time_registered` DESC LIMIT %d,%d", DataBase()->front_users, DataBase()->social_accounts, Page()->pageCurrent * 20, 20);
	$totalEntries = DataBase()->resultsFound;
	$totalPages = ceil($totalEntries / 20);

	$blockedUsers = Settings()->get("blocked_users");

?>
	<div class="block">
		<div class="panel panel-default">
			<div class="panel-heading"><h4 class="panel-title">Lietotāji</h4></div>
			<div class="panel-body">
				<table class="table table-condensed table-striped table-hover table-responsive">
					<thead>
						<tr>
							<th>Vārds</th>
							<th width="150">Reģistrēts</th>
							<th width="150">Ielogošanās</th>
							<th width="150">Piekļuve</th>
							<th width="1"></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($users as $user) {
							$alreadyBlocked = false;
							foreach ($blockedUsers as $buser) {
								$user["type"] = $user["network"] == "local" ? "local" : "social";
								if ($buser["type"] == $user["type"] && $buser["id"] == $user["id"]) {
									$alreadyBlocked = true;
									break;
								}
							}

							?>
							<tr class="<?php print($alreadyBlocked ? "danger" : ""); ?>">
								<td>
									<?php print(htmlspecialchars($user["first_name"] . ' ' . $user["last_name"])); ?>
								</td>
								<td><?php print($user["time_registered"]); ?></td>
								<td><?php print($user["last_login"]); ?></td>
								<td><?php print($map[ $user["network"] ]); ?></td>
								<td class="actions">
									<a class="btn btn-default btn-xs btn-<?php print($alreadyBlocked ? 'success' : 'warning'); ?>" href="<?php print(Page()->getURL(array("toggle_block" => $user["id"], "account_type" => $user["type"]))); ?>"><?php print($alreadyBlocked ? 'Atbloķēt' : "Bloķēt"); ?></a>
									<a class="btn btn-default btn-xs btn-danger" href="<?php print(Page()->getURL(array("delete" => $user["id"], "account_type" => $user["type"]))); ?>" data-confirm="Tiešām vēlies dzēst šo lieotāju?">Dzēst</a>
								</td>
							</tr>
						<?php } ?>
					</tbody>
				</table>
			</div>
			<?php if (ceil($totalPages) > 1) { ?>
				<div class="panel-footer">
					<nav>
						<ul class="pagination">
							<?php $this->paging(array(
								"pages"            => $totalPages,
								"delta"            => 5,
								"echo"             => true,
								"page"             => '<li><a href="%1$s">%2$s</a></li>',
								"active"           => '<li><a href="%1$s" class="active">%2$d</a></li>',
								"prev"             => '<li><a href="%1$s" class="%3$s" aria-label="{{{Previous}}}"><span aria-hidden="true">&laquo;</span></a></li>',
								"next"             => '<li><a href="%1$s" class="%3$s" aria-label="{{{Next}}}"><span aria-hidden="true">&raquo;</span></a></li>',
								"dontShowInactive" => true
							)) ?>
						</ul>
					</nav>
				</div>
			<?php } ?>
		</div>
	</div>
<?php
	$this->footer();
?>