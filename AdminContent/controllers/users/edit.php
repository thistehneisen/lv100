<?php

	if (!ActiveUser()->isAdmin() && ActiveUser()->id != $this->reqParams[0]) {
		Page()->accessDenied();
	}

	$user = Users()->getUser($this->reqParams[0]);

	if ($_POST) {
		if (!$_POST['email']) {
			$post_error = "{{Users: Empty e-mail message}}";
		} else if (!$_POST['first_name']) {
			$post_error = "{{Users: Empty name message}}";
		} else if (!$_POST['fpassword'] && $user->notfound) {
			$post_error = "{{Users: Empty password message}}";
		} else if ($_POST['fpassword'] && mb_strlen($_POST['fpassword']) < 6) {
			$post_error = "{{Users: Password too small message}}";
		} else if ($_POST['fpassword'] && $_POST['fpassword'] !== $_POST['rpassword']) {
			$post_error = "{{Users: Passwords mismatch message}}";
		} else if ($user->email != mb_strtolower($_POST["email"]) && !Users()->findUserByEmail($_POST["email"])->notfound) {
			$post_error = "{{Users: E-mail already exists message}}";
		} else {
			$user = Users()->setUser(array(
				"first_name" => $_POST["first_name"],
				"last_name"  => $_POST["last_name"],
				"email"      => $_POST["email"],
				"password"   => $_POST["fpassword"],
				"level"      => 3,
				"disabled"   => (ActiveUser()->id == $this->reqParams[0] ? ActiveUser()->disabled : !$_POST["enabled"]),
				"id"         => $user->id
			));

			$user->updateAllowedFrom($_POST["allowed_from"]);

			if (ActiveUser()->isAdmin() && ActiveUser()->id != $user->id) {
				if (!isset($_POST["groups"])) {
					$_POST["groups"] = array();
				}
				if (!is_array($_POST["groups"]) && $_POST["groups"]) {
					$_POST["groups"] = array($_POST["groups"]);
				}
				DataBase()->queryf("DELETE FROM %s WHERE `user_id`='%s'", DataBase()->user_group_relations, $user->is);
				foreach ($_POST["groups"] as $group) {
					if ($group == 1 && !ActiveUser()->isDev()) continue;
					DataBase()->insert("user_group_relations", array(
						"user_id"  => $user->id,
						"group_id" => $group
					));
				}
			}

			$_SESSION['post_success'] = str_replace("%s%", $user->getName(), $this->getTranslate("{{Users: User edited}}"));
			xLog("users: Lietotājs " . ActiveUser()->getName() . " laboja lietotāja " . $user->getName() . " datus.", "success", $user->id);

			header("Location: {$this->adminHost}{$this->controller}");
			exit;
		}
	}

	$this->addBreadcrumb("CMS lietotāji", $this->adminHost . "users/list/");
	if ($user->id > 0) {
		$this->addBreadcrumb($user->getName(), $this->adminHost . $this->controller . "/edit/" . $this->reqParams[0] . "/");
	} else $this->addBreadcrumb("Jauns lietotājs", $this->adminHost . $this->controller . "/edit/");

	$this->header();
	$this->addStyle("users.css");

	$userGroups = DataBase()->getRows("SELECT * FROM %s WHERE `id`>%d ORDER BY `id` ASC", DataBase()->user_groups, ActiveUser()->isDev() ? 0 : 1);

?>
<form class="addbody new" method="post" action="<?php echo $this->fullRequestUri ?>">

	<header>
		<a href="<?php echo $this->adminHost . $this->controller ?>" class="btn btn-primary btn-lg pull-left btn-back">{{Back}}</a>
		<h1>
			<span class="mce-i-othericons ic-user"></span> <?php if ($user->id > 0) { ?><?php $user->echoName() ?><?php } else { ?>Jauns lietotājs<?php } ?>
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
			<h1>{{Basic settings}}</h1>

			<div class="form-group row">
				<div class="col-xs-6">
					<label class="control-label" for="first_name">{{First name}}
						<span style="color:red;">*</span>:</label>
					<input type="text" id="first_name" name="first_name" autocomplete="off" class="form-control" value="<?php echo htmlspecialchars(!$user->notfound ? $user->first_name : "") ?>"/>
				</div>
				<div class="col-xs-6">
					<label class="control-label" for="last_name">{{Last name}}:</label>
					<input type="text" id="last_name" name="last_name" autocomplete="off" class="form-control" value="<?php echo htmlspecialchars($user->last_name) ?>"/>
				</div>
			</div>
			<div class="form-group">
				<label class="control-label" for="email">{{E-mail}} <span style="color:red;">*</span>:</label>
				<input class="form-control" type="text" id="email" name="email" value="<?php echo htmlspecialchars($user->email) ?>"/>
			</div>
			<div class="form-group row">
				<div class="col-xs-6">
					<label class="control-label" for="fpassword">{{Password}}:</label>
					<input class="form-control" type="password" id="fpassword" name="fpassword" autocomplete="off"/>
				</div>
				<div class="col-xs-6">
					<label class="control-label" for="rpassword">{{Password again}}:</label>
					<input class="form-control" type="password" id="rpassword" name="rpassword"/>
				</div>
			</div>
			<div class="form-group row">
				<div class="col-xs-12">
					<label for="allowed_ips">Atļautās IP adreses:</label>
					<input type="text" class="form-control" name="allowed_from" value="<?php Page()->e(join(", ",$user->allowed_from),1); ?>">
					<div class="help-block">
						<p>Ja norādītas IP adreses, lietotājam tiks atļauta piekļuve tikai no šīm IP adresēm.</p>
						<p>IP adreses atdalīt ar komatu.</p>
					</div>
				</div>
			</div>
		</section>
	</div>
	<div class="col-sidebar">
		<aside class="rightbar">
			<section>
				<div class="content">
					<h1>{{Users: Properties headline}}</h1>
					<?php if (!$user->isDev() && $user->id != ActiveUser()->id && ActiveUser()->isAdmin()) { ?>
					<div class="form-group form-horizontal">
						<label for="enabled" class="control-label">{{Users: Account activate}}:</label>
						<span class="pull-right">
							<input type="checkbox" name="enabled" value="1" class="selector" id="enabled" <?php echo !$user->disabled ? "checked" : "" ?> />
						</span>
					</div>
					<?php } ?>
					<?php if (ActiveUser()->isAdmin() && $user->id != ActiveUser()->id) { ?>
						<div>
							<h1>Lietotāja grupas</h1>
							<select multiple name="groups[]">
								<?php foreach ($userGroups as $group) { ?>
									<option value="<?php print($group["id"]); ?>"<?php print($user->inGroup($group["id"]) ? ' selected' : ''); ?>><?php print($group["name"]); ?></option>
								<?php } ?>
							</select>
						</div>
					<?php } ?>
				</div>
				<p class="span form-actions">
					<a href="<?php echo $this->adminHost . $this->controller ?>" class="btn btn-default">{{Cancel}}</a>
					<button type="submit" class="btn btn-success pull-right">{{Save}}</button>
					<?php if (ActiveUser()->id != $user->id && !$user->isDev()) { ?>
					<a href="<?php echo $this->adminHost . $this->controller ?>/delete/<?php echo $user->id ?>" class="btn btn-danger" style="margin-left: 5px;" data-confirm="<?php echo htmlspecialchars(str_replace("%s%", $user->getName(), $this->getTranslate("{{Users: Confirm user deletion}}"))) ?>">{{Delete}}</a><?php } ?>
				</p>
			</section>
		</aside>
	</div>
</form>
<?php $this->footer(); ?>
