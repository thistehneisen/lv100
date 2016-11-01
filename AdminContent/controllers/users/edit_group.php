<?php

	if (!ActiveUser()->isAdmin() || (Page()->reqParams[0] > 0 && Page()->reqParams[0] < 3)) {
		Page()->accessDenied();
	}

	$group = null;
	if (Page()->reqParams[0]) {
		$group = DataBase()->getRow("SELECT * FROM %s WHERE `id`='%s'", DataBase()->user_groups, Page()->reqParams[0]);
	}

	if ($_POST) {

		if ($group) {
			DataBase()->update("user_groups", array(
				"name"        => $_POST["name"],
				"description" => $_POST["description"]
			), array(
				"id" => $group["id"]
			));
		} else {
			DataBase()->insert("user_groups", array(
				"name"        => $_POST["name"],
				"description" => $_POST["description"]
			));
			$group = DataBase()->getRow("SELECT * FROM %s WHERE `id`='%s'", DataBase()->user_groups, DataBase()->insertid);
		}

		DataBase()->queryf("DELETE FROM %s WHERE `group`='%s' AND `unit`=0", DataBase()->permissions, $group["id"]);
		if (is_array($_POST["perms"])) {
			foreach ($_POST["perms"] as $controller => $perms) {
				foreach ($perms as $perm) {
					DataBase()->insert("permissions", array(
						"group"      => $group["id"],
						"controller" => $controller,
						"unit"       => 0,
						"type"       => $perm
					));
				}
			}
		}

		Page()->addCmsInfotip("Lietotāju grupa saglabāta.", "success");
		header("Location: {$_POST["return-to"]}");
		exit;
	}

	Page()->addBreadcrumb("Uzstādījumi", Page()->adminHost . "cpanel/");
	Page()->addBreadcrumb("CMS lietotāju grupas", Page()->adminHost . "users/groups/");
	if ($group["id"] > 0) {
		Page()->addBreadcrumb($group["name"], Page()->adminHost . Page()->controller . "/edit_group/" . Page()->reqParams[0] . "/");
	} else Page()->addBreadcrumb("Jauna grupa", Page()->adminHost . Page()->controller . "/edit_group/");

	Page()->header();
	Page()->addStyle("users.css");

	$groupPerms = DataBase()->getRows("SELECT * FROM %s WHERE `group`='%s' AND `unit`=0", DataBase()->permissions, $group["id"]);

?>
<form class="addbody new" method="post" action="<?php echo Page()->fullRequestUri ?>">
	<input type="hidden" name="return-to" value="<?php print($_POST["return-to"] ? $_POST["return-to"] : ($_SERVER["HTTP_REFERER"] ? $_SERVER["HTTP_REFERER"] : Page()->aHost . "users/groups/")); ?>">
	<header>
		<a href="<?php print($_POST["return-to"] ? $_POST["return-to"] : ($_SERVER["HTTP_REFERER"] ? $_SERVER["HTTP_REFERER"] : Page()->aHost . "users/groups/")); ?>" class="btn btn-primary btn-lg pull-left btn-back">Atpakaļ</a>
		<h1>
			<span class="mce-i-othericons ic-users"></span> <?php if ($group["id"] > 0) { ?><?php print($group["name"]); ?><?php } else { ?>Jauna grupa<?php } ?>
		</h1>
	</header>
	<div class="col-content">
		<?php if ($post_error) { ?>
			<div class="alert alert-danger">
				<p><?php echo $post_error ?></p>
			</div>
		<?php } ?>
		<?php if ($_SESSION['post_error']) { ?>
			<div class="alert alert-danger">
				<p><?php echo $_SESSION['post_error'] ?></p>
			</div>
			<?php unset($_SESSION['post_error']);
		} ?>
		<section>
			<h1>Informācija par grupu</h1>

			<div class="form-group">
				<label class="control-label" for="name">Nosaukums <span style="color:red;">*</span>:</label>
				<input class="form-control" type="text" id="name" name="name" value="<?php echo htmlspecialchars($group["name"]) ?>"/>
			</div>
			<div class="form-group">
				<label class="control-label" for="description">Apraksts <span style="color:red;">*</span>:</label>
				<textarea class="form-control" id="description" name="description"><?php echo htmlspecialchars($group["description"]) ?></textarea>
			</div>
		</section>
		<section>
			<h1>Piekļuve moduļiem</h1>
			<div class="panel-group" id="perms" role="tablist" aria-multiselectable="true">
				<?php $it = 0;
					foreach (Page()->controllers as $controllerName => $controller) {
						/**
						 * @var Controller $controller
						 */
						if (!count($controller->getGroupPerms())) continue;

						?>
						<div class="panel panel-default">
							<div class="panel-heading" role="tab" id="perm-h-<?php print($controllerName); ?>">
								<h4 class="panel-title">
									<a role="button" data-toggle="collapse" data-parent="#accordion" href="#perm-c-<?php print($controllerName); ?>" aria-expanded="<?php print(!$it ? 'true' : 'true'); ?>" aria-controls="perm-c-<?php print($controllerName); ?>">
										<?php print($controller->getName()); ?>
									</a>
								</h4>
							</div>
							<div id="perm-c-<?php print($controllerName); ?>" class="panel-collapse collapse <?php print(!$it ? 'in' : 'in'); ?>" role="tabpanel" aria-labelledby="perm-h-<?php print($controllerName); ?>">
								<div class="panel-body">
									<?php foreach ($controller->getGroupPerms() as $k => $perm) {
										$cb_checked = false;
										foreach ($groupPerms as $row) {
											if ($row["controller"] == $controllerName && $row["type"] == $perm) {
												$cb_checked = true;
												break;
											}
										}
										?>
										<div class="form-inline form-horizontal">
											<input type="checkbox" name="perms[<?php print($controllerName); ?>][]" value="<?php print($perm); ?>" id="perm-cb-<?php print($controllerName); ?>-<?php print($k); ?>"<?php print($cb_checked ? ' checked' : ''); ?>>
											<label style="margin-left: 1em;" for="perm-cb-<?php print($controllerName); ?>-<?php print($k); ?>"><?php print($perm); ?></label>
										</div>
									<?php } ?>
								</div>
							</div>
						</div>
						<?php $it++;
					} ?>
			</div>
		</section>
	</div>
	<div class="col-sidebar">
		<aside class="rightbar">
			<section>
				<div class="content">
					<h1>&nbsp;</h1>
				</div>
				<p class="span form-actions">
					<a href="<?php print($_POST["return-to"] ? $_POST["return-to"] : ($_SERVER["HTTP_REFERER"] ? $_SERVER["HTTP_REFERER"] : Page()->aHost . "users/groups/")); ?>" class="btn btn-default">Atcelt</a>
					<button type="submit" class="btn btn-success pull-right">Saglabāt</button>
					<?php if ($group) { ?>
						<a href="<?php echo Page()->adminHost . Page()->controller ?>/delete_group/<?php echo $group["id"] ?>" class="btn btn-danger" style="margin-left: 5px;" data-confirm="Tiešām vēlies izdzēst šo grupu?">Dzēst</a><?php } ?>
				</p>
			</section>
		</aside>
	</div>
</form>
<?php Page()->footer(); ?>
