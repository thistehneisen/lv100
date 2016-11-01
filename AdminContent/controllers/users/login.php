<?php
	if (Page()->controller == "password_reset") {
		if ($_POST) {
			if ($_POST["password1"] !== $_POST["password2"]) {
				$_SESSION["loginerror"] = "Ievadītās paroles nesakrīt.";
				header("Location: ".Page()->getURL());
				exit;
			} else if (empty($_POST["password1"])) {
				$_SESSION["loginerror"] = "Nav ievadīta parole.";
				header("Location: ".Page()->getURL());
				exit;
			} else if (mb_strlen($_POST["password1"]) < 6) {
				$_SESSION["loginerror"] = "Ievadītā parole ir pārāk īsa";
				header("Location: ".Page()->getURL());
				exit;
			} else {
				$user = DataBase()->getRow("SELECT * FROM %s WHERE `pass_reset_key` LIKE '%s:%%'", DataBase()->users, Page()->action);
				if (!$user) {
					$_SESSION["loginerror"] = "Paroles atkopšanas informācija netika atrasta. Mēģini pieprasīt paroles atkopšanu vēlreiz.";
					header("Location: ".Page()->getURL());
					exit;
				}
				$d = explode(":", $user["pass_reset_key"]);
				if ($d < strtotime("-1 hour")) {
					$_SESSION["loginerror"] = "Paroles atkopšanas saite ir pārāk veca. Mēģini pieprasīt paroles atkopšanu vēlreiz.";
					header("Location: ".Page()->getURL());
					exit;
				}
				DataBase()->update("users", array(
					"pass_reset_key" => "",
					"password"       => md5($_POST["password1"])
				), array(
					"id" => $user["id"]
				));

				$user2 = Users()->getUser($user["id"]);

				Users()->setUser(array(
					"first_name" => $user2->first_name,
					"last_name"  => $user2->last_name,
					"email"      => $user2->email,
					"password"   => $_POST["password1"],
					"level"      => $user2->level,
					"disabled"   => $user2->disabled,
					"id"         => $user2->id
				));

				$to = $user["email"];
				$toname = $user["display"];

				$from = array(trim(Page()->title . " CMS"), "web@" . reset(reset(Page()->domains)));

				$subject = "Paroles atkopšana lietotājam " . $user2->first_name . " " . $user2->last_name;

				$msg = "Jūsu lietotāja parole tika nomainīta.";

				$m = new PHPMailer();
				$m->CharSet = "UTF-8";
				$m->From = end($from);
				$m->FromName = reset($from);
				$m->Subject = $subject;
				$m->AltBody = strip_tags($msg);
				$m->MsgHTML(nl2br($msg));
				$m->AddAddress($to, $toname);
				$m->Send();
				$_SESSION["logininfo"] = "Parole tika nomainīta.";
				header("Location: ".Page()->aHost);
				exit;
			}
		} else {
			Page()->header();
			?>
			<div class="container-fluid">
				<div class="row">
					<div class="col-xs-4 col-xs-offset-4">
						<?php if (!empty($_SESSION["loginerror"])) { ?>
							<section class="alert alert-danger">
								<strong>Kļūda!</strong><br>
								<?php echo $_SESSION["loginerror"] ?>
							</section>
							<?php unset($_SESSION["loginerror"]);
						} ?>
					</div>
				</div>
			</div>
			<div class="container-fluid">
				<div class="row">
					<div class="col-xs-4 col-xs-offset-4">
						<form class="panel panel-default" action="<?php echo Page()->fullRequestUri ?>" method="post">
							<div class="panel-heading">
								<h3 class="panel-title">Paroles atkopšana</h3>
							</div>
							<div class="panel-body">
								<div class="form-group">
									<label for="password1">Parole:</label><input class="form-control" type="password" id="password1" value="" name="password1" autocomplete="off"/>
								</div>
								<div class="form-group">
									<label for="password2">Parole vēlreiz:</label><input class="form-control" type="password" id="password2" value="" name="password2" autocomplete="off"/>
								</div>
							</div>
							<div class="panel-footer">
								<button type="submit" name="" class="btn btn-primary" id="submit">Mainīt paroli</button>
							</div>
						</form>
					</div>
				</div>
			</div>
			<style type="text/css">
				label {
					margin: 1px 0;
					padding: 0;
				}
			</style>
			<?php
			Page()->footer();
			exit;
		}
	}

	if (Page()->controller == "lostpassword" && $_POST) {
		// TODO: Salabot paroles lietas

		$user = Users()->findUserByEmail($_POST["email"]);

		if ($user->isValid()) {

			$to = $user->email;
			$toname = $user->getName();

			$reset_key = md5($to . $toname . time());
			$resetlink = Page()->aHost . "password_reset/" . $reset_key;

			$from = array(trim(Page()->title . " CMS"), Page()->email_from_address);

			$subject = "Paroles atiestatīšana lietotājam ".$toname;

			$ip = Recipe::getClientIP(Page()->trustProxyHeaders);
			$msg = <<<EOV
Kāds pieprasīja paroles atiestatīšanas linku.

Pieprasītāja IP adrese: {$ip}
Paroles atiestatīšanas links: {$resetlink}
EOV;


			$m = new PHPMailer();
			$m->CharSet = "UTF-8";
			$m->From = end($from);
			$m->FromName = reset($from);
			$m->Subject = $subject;
			$m->AltBody = strip_tags($msg);
			$m->MsgHTML(nl2br($msg));
			$m->AddAddress($to, $toname);

			if ($m->Send()) {
				$logininfo = "Paroles atiestatīšanas e-pasts nosūtīts!";
				DataBase()->update("users", array(
					"pass_reset_key" => join(":", array($reset_key, time()))
				), array(
					"id" => $user->id
				));
			} else {
				$loginerror = "Operācija neizdevās!";
			}
		} else {
			$loginerror = "Lietotājs netika atrasts!";
		}
	} else if ($_POST) {
		$throotle = Settings()->get("loginThrootle", "");
		if (!$throotle) $throotle = array();

		if (isset($throotle[ Recipe::getClientIP(Page()->trustProxyHeaders) ]) && count($throotle[ Recipe::getClientIP(Page()->trustProxyHeaders) ]) > 4) {
			xLog("users: Pievienošanās pārkāpums: bloķēta IP adrese ".Recipe::getClientIP(Page()->trustProxyHeaders)." mēģina apiet liegumu.", "failed");
		} else if (Users()->login($_POST["email"], $_POST["password"], $_POST["remember"] == "1")) {
			xLog("users: Lietotājs " . ActiveUser()->getName() . " pievienojies.", "success");
			unset($throotle[ Recipe::getClientIP(Page()->trustProxyHeaders) ]);
			Settings()->set("loginThrootle", $throotle);
			header("Location: {$_POST["referer"]}");
			exit;
		} else {
			$error = Users()->getLastError();
			$_POST["email"] = htmlspecialchars(strip_tags($_POST["email"]));
			$_POST["password"] = htmlspecialchars(strip_tags($_POST["password"]));
			$loginerror = $error[1];
			xLog("users: Pievienošanās kļūme: nepareizs e-pasts/parole ({$_POST["email"]}:{$_POST['password']}).", "failed");
			$throotle[ Recipe::getClientIP(Page()->trustProxyHeaders) ][] = array(
				"e" => $_POST["email"],
				"p" => $_POST["password"],
				"t" => Page()->time
			);
			if (count($throotle[ Recipe::getClientIP(Page()->trustProxyHeaders) ]) > 4) {
				$admin_emails = Settings()->get("cms:email:bans", "");
				$admin_emails = explode(",",$admin_emails);
				$admin_emails = array_map(function($e){return trim($e);},$admin_emails);
				Page()->mailIt($admin_emails,"Lietotājs bloķēts","Pievienošanās pārkāpums: bloķēta IP adrese ".Recipe::getClientIP(Page()->trustProxyHeaders)." pec 5 neveiksmīgiem mēģinājumiem.
				
				Apskatīt visas bloķētās IP adreses: ".Page()->aHost."users/bans/",null,true);
				xLog("users: IP adrese ".Recipe::getClientIP(Page()->trustProxyHeaders)." tika bloķēta.", "failed");
			}
			Settings()->set("loginThrootle", $throotle, "");
		}
	}
	if ($_SESSION["logininfo"]) {
		$logininfo = $_SESSION["logininfo"];
		unset($_SESSION["logininfo"]);
	}
	Page()->header();
?>
	<div class="container-fluid">
		<div class="row">
			<div class="col-xs-4 col-xs-offset-4">
				<?php if (!empty($loginerror)) { ?>
					<section class="alert alert-danger">
						<strong>Kļūda!</strong><br>
						<?php echo $loginerror ?>
					</section>
				<?php } ?>
				<?php if (!empty($logininfo)) { ?>
					<section class="alert alert-warning">
						<strong>Informācija!</strong><br>
						<?php echo $logininfo ?>
					</section>
				<?php } ?>
			</div>
		</div>
	</div>
<?php if (Page()->controller == "lostpassword" && (!$_POST || !$logininfo)) { ?>
	<div class="container-fluid">
		<div class="row">
			<div class="col-xs-4 col-xs-offset-4">
				<form class="panel panel-default login" action="<?php echo Page()->adminHost ?>lostpassword/" method="post">
					<div class="panel-heading">
						<h3 class="panel-title">Paroles atkopšana</h3>
					</div>
					<div class="panel-body">
						<div class="form-group">
							<label for="uname">E-pasta adrese:</label><input class="form-control" type="text" id="uname" value="" name="email"/>
						</div>
					</div>
					<div class="panel-footer">
						<button type="submit" name="" class="btn btn-primary">Nosūtīt</button>
					</div>
				</form>
			</div>
		</div>
	</div>
<?php } else { ?>
	<div class="container-fluid">
		<div class="row">
			<div class="col-xs-4 col-xs-offset-4">
				<form class="panel panel-default login" action="<?php echo Page()->adminHost ?>" method="post">
					<div class="panel-heading">
						<h3 class="panel-title">Pievienošanās</h3>
					</div>
					<?php
						$throotle = Settings()->get("loginThrootle", "");
						if (!$throotle) $throotle = array(); ?>

					<div class="panel-body">
						<?php
							$dontShowButton = false;
							if (isset($throotle[ Recipe::getClientIP(Page()->trustProxyHeaders) ]) && count($throotle[ Recipe::getClientIP(Page()->trustProxyHeaders) ]) > 4) {
								$dontShowButton = true;
								?>
								<div class="alert alert-danger">
									<p>No šīs IP adreses reģistrēti pārāk daudz neveiksmīgi pievienošanās mēģinājumi. Lūdzu, sazinieties ar vietnes administratoru!</p>
								</div>
								<?php
							} else {
								?>
								<input type="hidden" name="referer" value="<?php echo Page()->fullRequestUri; ?>"/>
								<div class="form-group">
									<input class="form-control" type="text" id="email" value="" name="email" placeholder="E-pasta adrese"/>
								</div>
								<div class="form-group">
									<input class="form-control" type="password" id="password" value="" name="password" placeholder="Parole"/>
								</div>
								<div class="checkbox-inline">
									<input type="checkbox" name="remember" value="1" id="field-remember"/>
									<label for="field-remember">Atceerēties mani šajā pārlūkā</label>
								</div>
							<?php } ?>
					</div>
					<?php if (!$dontShowButton) { ?>
						<div class="panel-footer">

						<button type="submit" name="" class="btn btn-primary" id="submit">Pievienoties</button>
						<a class="lostpasswordlink" href="<?php echo Page()->adminHost ?>lostpassword/">Pazudusi parole?</a>
						</div><?php } ?>
				</form>
			</div>
		</div>
	</div>
<?php } ?>
	<style type="text/css">
		label {
			margin: 1px 0;
			padding: 0;
		}
	</style>
<?php
	Page()->footer();
?>