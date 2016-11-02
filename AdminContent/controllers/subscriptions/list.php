<?php

	if (!ActiveUser()->can(Page()->controller, "sarakstu skatīšana")) {
		Page()->accessDenied();
	}

	if ($_GET["delete"]) {
		if (!ActiveUser()->can(Page()->controller, "abonentu dzēšana")) exit;
		$email = DataBase()->getRow("SELECT * FROM %s WHERE `id`=%d", DataBase()->emails, $_GET["delete"]);
		DataBase()->queryf("DELETE FROM %s WHERE `id`=%d", DataBase()->emails, $_GET["delete"]);

		Page()->addCmsInfotip("E-pasta adrese <strong>{$email["email"]}</strong> dzēsta no datubāzes.","success");

		header("Location: {$_SERVER["HTTP_REFERER"]}");
		exit;
	}

	$language = "lv";
	if ($_GET["l"]) $language = $_GET["l"];

	if ($_GET["save_subscriber"]) {

		if ($_POST["email"]) {
			DataBase()->insert("emails", array(
				"email"    => $_POST["email"],
				"language" => $language
			), true);
			Page()->addCmsInfotip("Jauns abonaments ir pievienots.","success");
		}
		header("Location: {$_SERVER["HTTP_REFERER"]}");
		exit;
	}

	Page()->addBreadcrumb("Abonementi", Page()->aHost . Page()->controller . "/");
	Page()->header();

?><?php include(Page()->controllers[ Page()->controller ]->getPath() . "sidebar.php"); ?>
	<section class="block">
		<h1>Abonementi</h1>
		<?php if ($_SESSION['post_response']) { ?>
			<div class="alert alert-<?= $_SESSION['post_response'][1] ?> alert-dismissable">
				<button type="button" class="close" data-dismiss="alert" aria-label="Close">
					<span aria-hidden="true">&times;</span></button>
				<p><?php echo $_SESSION['post_response'][0] ?></p>
			</div>
			<?php unset($_SESSION['post_response']);
		} ?>

		<div>

			<!-- Nav tabs -->
			<?php /*<ul class="nav nav-tabs" role="tablist">
				<li role="presentation"<?php if ($language == "lv") { ?> class="active"<?php } ?>>
					<a href="<?php print(Page()->getURL(array("l" => "lv"))); ?>" role="tab"><?php print(Page()->language_labels["lv"]); ?></a>
				</li>
				<li role="presentation"<?php if ($language == "ru") { ?> class="active"<?php } ?>>
					<a href="<?php print(Page()->getURL(array("l" => "ru"))); ?>" role="tab"><?php print(Page()->language_labels["ru"]); ?></a>
				</li>
				<li role="presentation"<?php if ($language == "en") { ?> class="active"<?php } ?>>
					<a href="<?php print(Page()->getURL(array("l" => "en"))); ?>" role="tab"><?php print(Page()->language_labels["en"]); ?></a>
				</li>
			</ul>*/ ?>
			<div class="tab-content">
				<?php
					DataBase()->countResults = true;
					$emails = DataBase()->getRows("SELECT `e`.*, (SELECT COUNT(*) FROM %s `q` WHERE `q`.`to`=`e`.`email`) `sent` FROM %s `e` WHERE `e`.`language`='%s' ORDER BY `e`.`time` DESC LIMIT %d,%d", DataBase()->queue, DataBase()->emails, $language, Page()->pageCurrent * 30, 30);
					$total = DataBase()->resultsFound;

				?>

				<div role="tabpanel" class="tab-pane active" id="emails">
					<div class="panel panel-default">
						<div class="panel-body">
							<form action="<?php print(Page()->getURL(array("save_subscriber" => 1))); ?>" method="post">
								<div class="form-group row">
									<div class="col-xs-5">
										<input type="email" class="form-control" name="email" placeholder="Ieraksti abonenta e-pasta adresi">
									</div>

									<div class="col-xs-3">
										<button type="submit" class="btn btn-default btn-block">Pievienot abonamentu</button>
									</div>
								</div>
							</form>
							<hr>
							<table width="100%" class="table table-condensed table-striped table-hover">
								<thead>
									<tr>
										<th>E-pasta adrese</th>
										<th width="175">Pierakstīšanās laiks</th>
										<th width="1"></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($emails as $email) { ?>
										<tr>
											<td><?php print($email["email"]); ?></td>
											<td><?php echo $Com->getdate("1", strtotime($email["time"]), "lv-short") ?>, <?php echo date("H:i", strtotime($email["time"])) ?></td>
											<td class="actions">
												<?php if (ActiveUser()->can(Page()->controller,"abonentu dzēšana")) { ?>
												<a href="<?php print(Page()->aHost . Page()->controller); ?>/?delete=<?php print($email["id"]); ?>" class="btn btn-default btn-xs" data-confirm="Tiešām vēlies dzēst šo e-pasta adresi no datubāzes?">Dzēst</a><?php } ?>
											</td>
										</tr>
									<?php } ?>
								</tbody>
							</table>
						</div>
						<?php if (ceil($total / 30) > 1) { ?>
							<div class="panel-footer">
								<nav>
									<ul class="pagination">
										<?php Page()->paging(array(
											"pages"            => ceil($total / 30),
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

				</div>
			</div>

		</div>
	</section>
<?php Page()->footer(); ?>