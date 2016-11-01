<?php
	if (!ActiveUser()->can(Page()->controller, "pārvaldīt")) {
		Page()->accessDenied();
	}

	$node = Page()->getNode(array(
		"filter"        => array(
			"id"                 => Page()->reqParams[0],
			"controller"         => "members",
			"view" => array("big", "small", "small-2")
		),
		"returnResults" => "first"
	));

	if ($_SERVER["REQUEST_METHOD"] == "POST") {
		$settings["title"] = $_POST["name"];
		if (!$_POST["picture"]) {
			$_POST["picture"] = Page()->getEmptyImage(300);
		}
		$settings["data"] = array(
			"url" => $_POST["url"],
			"position" => $_POST["position"],
			"picture" => $_POST["picture"],
			"picture_original" => $_POST["picture_original"]
		);
		$settings["view"] = $node ? $node->view : $_GET["subtype"];
		$settings["type"] = 5;
		$settings["controller"] = "members";

		if ($node && $node->id) {
			$settings["id"] = $node->id;
		} else $settings["parent"] = Page()->roots[0]->id;

		Page()->setNode($settings);
		Page()->cache->purge("partners", "all");

		header("Location: {$_SERVER["HTTP_REFERER"]}");
		exit;
	}
?>
<form action="<?php print(Page()->getURL()); ?>" method="post" id="saveForm">
	<div class="container-fluid big">
		<div class="row">
			<div class="col-xs-6">
				<?php
					if (!$node->data->picture_original && $node) $node->data->picture_original = $node->data->picture;
					if ($node->data->picture_original) {
						$oImg = DataBase()->getRow("SELECT * FROM %s WHERE `filepath`='%s'", DataBase()->media, $node->data->picture_original);
					}
				?>
				<div class="thumbnail">
					<div class="wrap">
						<span class="helper"></span><img src="<?php Page()->e($node ? Page()->host . $node->data->picture : Page()->host . Page()->getEmptyImage(300, 300), 1); ?>" id="picturePreview">
						<input type="hidden" name="picture" value="<?php Page()->e($node ? $node->data->picture : "", 1); ?>" id="pictureInput">
						<input type="hidden" name="picture_original" value="<?php Page()->e($node ? $node->data->picture_original : "", 1); ?>" id="pictureOriginalInput">
					</div>
				</div>
				<div class="row">
					<div class="col-xs-7">
						<button type="button" class="btn btn-default btn-upload" id="upload-picture">
							Augšupielādēt
							<div class="progress">
								<div class="progress-bar progress-bar-info" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0;">
									<span></span>
								</div>
							</div>
						</button>
					</div>
					<div class="col-xs-5 right">
						<a data-opts="<?php Page()->e(json_encode(array(
							"filename" => basename($node->data->picture_original),
							"filepath" => $node->data->picture_original,
							"width"    => $oImg["width"],
							"height"   => $oImg["height"],
							"type"     => "photo"
						)), 1); ?>" href="#" class="btn btn-default disabled" id="cropPicture">Pielāgot</a>
					</div>
				</div>
			</div>
			<div class="col-xs-6">
				<div class="form-group">
					<label for="partner-name">Vārds, uzvārds:</label>
					<input id="partner-name" name="name" class="form-control" type="text" value="<?php Page()->e($node ? $node->title : "", 1); ?>">
				</div>
				<div class="form-group">
					<label for="partner-decription">Amats:</label>
					<div class="input-group">
						<?php foreach (Page()->languages as $k => $lng) { ?>
							<input type="text" name="position[<?php print($lng); ?>]" data-lang="<?php print($lng); ?>" class="form-control<?php print($lng != Page()->languages[0] ? ' hidden' : ''); ?>" value="<?php Page()->e($node->data->position->{$lng}, 1) ?>"/>
						<?php } ?>
						<div class="input-group-btn lng-switcher">
							<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
								<span class="lbl"><?php print(strtoupper(Page()->languages[0])); ?></span>
								<span class="caret"></span></button>
							<ul class="dropdown-menu dropdown-menu-right">
								<?php foreach ($this->languages as $language) {
									echo '<li><a href="#" data-lang="' . $language . '">' . strtoupper($language) . '</a></li>';
								} ?>
							</ul>
						</div>
					</div>
				</div>
				<div class="form-group">
					<label for="partner-url">Web adrese:</label>
					<div class="input-group dropup">
						<?php foreach (Page()->languages as $k => $lng) { ?>
							<input type="text" name="url[<?php print($lng); ?>]" data-lang="<?php print($lng); ?>" class="form-control<?php print($lng != Page()->languages[0] ? ' hidden' : ''); ?>" value="<?php Page()->e($node->data->url->{$lng}, 1) ?>"/>
						<?php } ?>
						<div class="input-group-btn lng-switcher">
							<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
								<span class="lbl"><?php print(strtoupper(Page()->languages[0])); ?></span>
								<span class="caret"></span></button>
							<ul class="dropdown-menu dropdown-menu-right">
								<?php foreach ($this->languages as $language) {
									echo '<li><a href="#" data-lang="' . $language . '">' . strtoupper($language) . '</a></li>';
								} ?>
							</ul>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</form>
<script type="text/javascript">
	ImageUploaderSingle("#upload-picture", function (response) {
		$(picturePreview).attr("src", Settings.Host + response.file);
		$(pictureInput).val(response.file);
		$(editSaveButton).prop("disabled",false);
		$(cropPicture).removeClass("disabled").data("opts", response.opts);
		$(pictureOriginalInput).val(response.opts.filepath);
		$(cropPicture).click();
	}, "crop", {"crop":"300x300", "keep":1});
	$(editSaveButton).prop("disabled",true);
	<?php if ($node) { ?>
	$(editSaveButton).prop("disabled",false);
	<?php } ?>
	$(saveForm).on("submit", function (e) {
		e.preventDefault();
		$(saveForm).ajaxSubmit({
			success: function (response) {
				$("#ajax-container").html($($.parseHTML(response)).find("#ajax-container").html());
				makeThingsSortable();
				$(editDialog).dialog("close");
			}
		});
	});
	$(cropPicture).on("click", function (e) {
		e.preventDefault();
		var imgEditor = new imgEditTool($(this).data("opts")).open(function () {
			this.cropToolInit({
				desiredSize: {w: 300, h: 300}, cancel: function () {
					this.close();
				}, save: function (data) {
					that = this;
					$.getJSON(<?php Page()->e(Page()->host."media.upload/?raw_crop=1",3)?>+'&session='+Settings.session_id, {
						i: data.i,
						x: data.x,
						y: data.y,
						w: data.w,
						h: data.h,
						r: data.r
					}, function (response) {
						that.close();
						$(picturePreview).attr({src: Settings.Host + response.fileThumb});
						$(pictureInput).val(response.fileThumb);
					});
				}
			});
		});
	});
</script>