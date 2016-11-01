<?php
	if (!ActiveUser()->can(Page()->controller, "moduļa uzstādījumi")) {
		Page()->accessDenied();
	}


	if (Page()->method == "POST") {
		foreach ($_POST as $key => $langval) {
			foreach ($langval as $language => $value) {
				Settings()->set("subscribtions:".$key, $value, $language, null, true);
			}
		}
		$_SESSION["post_response"] = "Uzstādījumi saglabāti.";
		header("Location: {$_SERVER["HTTP_REFERER"]}");
		exit;
	}

	Page()->addBreadcrumb("Abonementi", Page()->aHost . Page()->controller . "/");
	Page()->addBreadcrumb("Uzstādījumi", Page()->aHost . Page()->controller . "/settings/");
	Page()->header();

?>

<?php include(Page()->controllers[ Page()->controller ]->getPath() . "sidebar.php"); ?>

<section class="block" id="content">
	<h1>Uzstādījumi</h1>
	<?php if ($_SESSION['post_response']) { ?>
		<div class="alert alert-success alert-dismissable">
			<button type="button" class="close" data-dismiss="alert" aria-label="Close">
				<span aria-hidden="true">&times;</span></button>
			<p><?php echo $_SESSION['post_response'] ?></p>
		</div>
		<?php unset($_SESSION['post_response']);
	} ?>
	<form action="<?php echo Page()->fullRequestUri ?>" method="post">
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
						<div class="panel panel-default">
							<div class="panel-body">
								<div class="form-group">
									<label for="more_text-<?php print($language); ?>">"Uzzināt vairāk" pogas teksts:</label>
									<input id="more_text-<?php print($language); ?>" class="form-control" type="text" placeholder="" name="more_text[<?php print($language); ?>]" value="<?php Page()->e(Settings()->get("subscribtions:more_text", $language), 1); ?>" />
								</div>
								<div class="form-group">
									<label for="unsubscribe_info-<?php print($language); ?>">Atrakstīšanās iespējas teksts:</label>
									<textarea id="unsubscribe_info-<?php print($language); ?>" class="form-control" placeholder="" name="unsubscribe_info[<?php print($language); ?>]"><?php Page()->e(Settings()->get("subscribtions:unsubscribe_info", $language), 1); ?></textarea>
								</div>
								<hr>
								<div class="form-group">
									<label for="default_from_name-<?php print($language); ?>">Noklusētais sūtītāja vārds:</label>
									<input id="default_from_name-<?php print($language); ?>" class="form-control" type="text" placeholder="" name="default_from_name[<?php print($language); ?>]" value="<?php Page()->e(Settings()->get("subscribtions:default_from_name", $language), 1); ?>" />
								</div>
								<div class="form-group">
									<label for="default_from_address-<?php print($language); ?>">Noklusētā sūtītāja e-pasta adrese:</label>
									<input id="default_from_address-<?php print($language); ?>" class="form-control" type="text" placeholder="" name="default_from_address[<?php print($language); ?>]" value="<?php Page()->e(Settings()->get("subscribtions:default_from_address", $language), 1); ?>" />
								</div>
								<hr>
								<div class="form-group">
									<label for="unsubscribe_display_headline-<?php print($language); ?>">Atrakstīšanās skata lapas virsraksts:</label>
									<input id="unsubscribe_display_headline-<?php print($language); ?>" class="form-control" type="text" placeholder="" name="unsubscribe_display_headline[<?php print($language); ?>]" value="<?php Page()->e(Settings()->get("subscribtions:unsubscribe_display_headline", $language), 1); ?>" />
								</div>
								<div class="form-group">
									<label for="unsubscribe_display_content0-<?php print($language); ?>">Atrakstīšanās skata lapas saturs pirms atrakstīšanās:</label>
									<textarea id="unsubscribe_display_content0-<?php print($language); ?>" class="form-control tinymce_big" placeholder="" name="unsubscribe_display_content0[<?php print($language); ?>]"><?php Page()->e(Settings()->get("subscribtions:unsubscribe_display_content0", $language), 1); ?></textarea>
								</div>
								<div class="form-group">
									<label for="unsubscribe_display_content-<?php print($language); ?>">Atrakstīšanās skata lapas saturs:</label>
									<textarea id="unsubscribe_display_content-<?php print($language); ?>" class="form-control tinymce_big" placeholder="" name="unsubscribe_display_content[<?php print($language); ?>]"><?php Page()->e(Settings()->get("subscribtions:unsubscribe_display_content", $language), 1); ?></textarea>
								</div>
							</div>
						</div>
					</div>
				<?php } ?>
				<div class="row">
					<div class="col-xs-12">
						<button class="btn btn-success" type="submit">{{Save}}</button>
					</div>
				</div>
			</div>
		</div>
	</form>
</section>

<?php Page()->footer(); ?>
