<?php

	Page()->addBreadcrumb("Uzstādījumi", Page()->adminHost . basename(dirname(__FILE__)));

	if (Page()->method == "POST" && ActiveUser()->can(Page()->controller, "pamatuzstādījumi")) {
		foreach ($_POST as $key => $langval) {
			foreach ($langval as $language => $value) {
				if ($language == "unk") $language = "";
				Settings()->set($key, $value, $language, null, true);
			}
		}
		$_SESSION["post_response"] = "{{Settings saved}}.";
		header("Location: {$_SERVER["HTTP_REFERER"]}");
		exit;
	}

	include('side.php');
	if (ActiveUser()->can(Page()->controller, "pamatuzstādījumi")) {
		?>
		<section class="block" id="content">
			<?php if ($_SESSION['post_response']) { ?>
				<div class="alert alert-success alert-dismissable">
					<button type="button" class="close" data-dismiss="alert" aria-label="Close">
						<span aria-hidden="true">&times;</span></button>
					<p><?php echo $_SESSION['post_response'] ?></p>
				</div>
				<?php unset($_SESSION['post_response']);
			} ?>

			<form action="<?php echo Page()->fullRequestUri ?>" method="post">
				<div class="panel panel-default">
					<div class="panel-heading">
						<h3 class="panel-title"><?php print($action); ?></h3>
					</div>
					<div class="panel-body">
						<div class="tabpanel">
							<ul class="nav nav-tabs" role="tablist">
								<?php foreach (Page()->languages as $k => $language) { ?>
									<li role="presentation" class="<?php if ($k == 0) { ?> active<?php } ?>">
										<a href="#settings-<?php echo $language ?>" aria-controls="<?php echo $language ?>" role="tab" data-toggle="tab"><?php echo Page()->language_labels[ $language ] ?></a>
									</li>
								<?php } ?>
							</ul>
							<div class="tab-content">
								<?php foreach (Page()->languages as $k => $language) { ?>
									<div role="tabpanel" class="tab-pane<?php if ($k == 0) { ?> active<?php } ?>" id="settings-<?php echo $language ?>">
										<div class="form-group">
											<label for="title-<?php print($language); ?>">{{CPanel: Title}}</label>
											<input id="title-<?php print($language); ?>" class="form-control" type="text" placeholder="" name="site_name[<?php print($language); ?>]" value="<?php Page()->e(Settings()->get("site_name", $language), 1); ?>"/>
										</div>
										<div class="form-group">
											<label for="keywords-<?php print($language); ?>">{{CPanel: Keywords}}:</label>
											<input id="keywords-<?php print($language); ?>" class="form-control" type="text" placeholder="" name="site_keywords[<?php print($language); ?>]" value="<?php Page()->e(Settings()->get("site_keywords", $language), 1); ?>"/>
										</div>
										<div class="form-group">
											<label for="description-<?php print($language); ?>">Apraksts:</label>
											<input id="description-<?php print($language); ?>" class="form-control" type="text" name="site_description[<?php print($language); ?>]" value="<?php Page()->e(Settings()->get("site_description", $language), 1); ?>"/>
										</div>
										<div class="form-group">
											<label for="copyright-<?php print($language); ?>">Copyright:</label>
											<input id="copyright-<?php print($language); ?>" class="form-control" type="text" name="general:copyright[<?php print($language); ?>]" value="<?php Page()->e(Settings()->get("general:copyright", $language), 1); ?>"/>
										</div>
									</div>
								<?php } ?>
							</div>
						</div>
					</div>
				</div>
				<div class="panel panel-default">
					<div class="panel-heading">
						<h4 class="panel-title">CMS komunikācijas e-pasta adreses</h4>
					</div>
					<div class="panel-body">
						<div class="form-group">
							<label for="cms-email-comments">Jauns komentārs:</label>
							<input id="cms-email-comments" type="text" name="cms:email:comments[unk]" class="form-control" value="<?php Page()->e(Settings()->get("cms:email:comments", ""), 1); ?>">
							<p class="help-block">E-pasta adreses jāatdala ar komatu.</p>
							<p class="help-block">Uz šīm e-pasta adresēm tiks noūtiīta informācija par jauniem komentāriem.</p>
						</div>
						<div class="form-group">
							<label for="cms-email-bans">Bloķēta IP adrese:</label>
							<input id="cms-email-bans" type="text" name="cms:email:bans[unk]" class="form-control" value="<?php Page()->e(Settings()->get("cms:email:bans", ""), 1); ?>">
							<p class="help-block">E-pasta adreses jāatdala ar komatu.</p>
							<p class="help-block">Uz šīm e-pasta adresēm tiks noūtiīta informācija par IP adrešu bloķēšanu, ja lietotājs neveiksmīgi mēģinājis ielogoties admin panelī 5 reizes.</p>
						</div>
					</div>
				</div>
				<div class="panel panel-default">
					<div class="panel-heading">
						<h4 class="panel-title">Komentāru vārdu filtrs</h4>
					</div>
					<div class="panel-body">
						<div class="form-group">
							<label for="comments-filter-badwords">Aizliegtie vārdi:</label>
							<textarea id="comments-filter-badwords" name="comments:filter:badwords[unk]" class="form-control"><?php Page()->e(Settings()->get("comments:filter:badwords", ""), 1); ?></textarea>
							<p class="help-block">Frāzes, kuras komentāros jāaizstāj ar simboliem (*), jānorāda atdalot tās ar komatu.</p>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-xs-12">
						<button class="btn btn-success" type="submit">{{Save}}</button>
					</div>
				</div>
			</form>
		</section>

	<?php }
	Page()->footer();
?>
