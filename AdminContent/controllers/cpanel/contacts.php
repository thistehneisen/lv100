<?php
	if (!ActiveUser()->can(Page()->controller, "kontakti")) {
		Page()->accessDenied();
	}

	if (Page()->method == "POST") {
		foreach ($_POST as $key => $langval) {
			foreach ($langval as $language => $value) {
				Settings()->set($key, $value, $language, null, true);
			}
		}
		$_SESSION["post_response"] = "{{Settings saved}}.";

		header("Location: {$_SERVER["HTTP_REFERER"]}");
		exit;
	}
	include('side.php');
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
		<div class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title"><?php print($action); ?></h3>
			</div>
			<div class="panel-body">
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
									<div class="form-group row">
										<div class="col-xs-12">
											<label for="email-<?php print($language); ?>">E-pasta adrese:</label>
											<input id="email-<?php print($language); ?>" class="form-control" type="text" name="contacts:email[<?php print($language); ?>]" value="<?php Page()->e(Settings()->get("contacts:email", $language), 1); ?>"/>
										</div>
									</div>
									<div class="form-group row">
										<div class="col-xs-12">
											<label for="address-<?php print($language); ?>">Pasta adrese:</label>
											<textarea id="address-<?php print($language); ?>" class="form-control" name="contacts:address[<?php print($language); ?>]"><?php Page()->e(Settings()->get("contacts:address", $language), 1); ?></textarea>
										</div>
									</div>
									<div class="form-group row">
										<div class="col-xs-12">
											<label for="gmaps-<?php print($language); ?>">Google Maps links:</label>
											<input id="gmaps-<?php print($language); ?>" class="form-control" type="text" name="contacts:gmaps[<?php print($language); ?>]" value="<?php Page()->e(Settings()->get("contacts:gmaps", $language), 1); ?>"/>
										</div>
									</div>
									<div class="form-group row">
										<div class="col-xs-12">
											<label for="details-<?php print($language); ?>">Rekvizīti:</label>
											<textarea id="details-<?php print($language); ?>" class="form-control" name="contacts:details[<?php print($language); ?>]"><?php Page()->e(Settings()->get("contacts:details", $language), 1); ?></textarea>
										</div>
									</div>
									<div class="form-group row">
										<div class="col-xs-6">
											<label for="yt-<?php print($language); ?>">Youtube kanāla adrese:</label>
											<input id="yt-<?php print($language); ?>" class="form-control" type="text" name="contacts:yt[<?php print($language); ?>]" value="<?php Page()->e(Settings()->get("contacts:yt", $language), 1); ?>"/>
										</div>
										<div class="col-xs-6">
											<label for="fb-<?php print($language); ?>">Facebook profila adrese:</label>
											<input id="fb-<?php print($language); ?>" class="form-control" type="text" name="contacts:fb[<?php print($language); ?>]" value="<?php Page()->e(Settings()->get("contacts:fb", $language), 1); ?>"/>
										</div>
									</div>
									<div class="form-group row">
										<div class="col-xs-6">
											<label for="dr-<?php print($language); ?>">Draugiem profila adrese:</label>
											<input id="dr-<?php print($language); ?>" class="form-control" type="text" name="contacts:dr[<?php print($language); ?>]" value="<?php Page()->e(Settings()->get("contacts:dr", $language), 1); ?>"/>
										</div>
										<div class="col-xs-6">
											<label for="tw-<?php print($language); ?>">Twitter profila adrese:</label>
											<input id="tw-<?php print($language); ?>" class="form-control" type="text" name="contacts:tw[<?php print($language); ?>]" value="<?php Page()->e(Settings()->get("contacts:tw", $language), 1); ?>"/>
										</div>
									</div>
									<div class="form-group row">
										<div class="col-xs-6">
											<label for="in-<?php print($language); ?>">Instagram profila adrese:</label>
											<input id="in-<?php print($language); ?>" class="form-control" type="text" name="contacts:in[<?php print($language); ?>]" value="<?php Page()->e(Settings()->get("contacts:in", $language), 1); ?>"/>
										</div>
									</div>
								</div>
							<?php } ?>
							<div class="row">
								<div class="col-xs-12"><br>
									<button class="btn btn-success" type="submit">Saglabāt</button>
								</div>
							</div>
						</div>
					</div>
				</form>
			</div>
		</div>
	</section>
<?php Page()->footer(); ?>