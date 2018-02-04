<?php
	if (!ActiveUser()->can(Page()->controller, "pārvaldīt")) {
		Page()->accessDenied();
	}
	$node = Page()->getNode(array(
		"filter"        => array(
			"id" => Page()->reqParams[0],
			"controller" => "ads",
			"view" => array("jpeg", "swf", "text")
		),
		"returnResults" => "first"
	));

	if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST["picture"]) {
		$settings["title"] = $_POST["name"];
		$settings["data"] = array("url" => $_POST["url"], "picture" => $_POST["picture"], "picture_original" => $_POST["picture_original"]);
		$settings["view"] = $node ? $node->view : $_GET["subtype"];
		$settings["type"] = 5;
		$settings["controller"] = "ads";

		$settings["enabled"] = (int)$_POST["enabled"];
		$settings["data"]["bsize"] = $_POST["size"];

		if ($node && $node->id) {
			$settings["id"] = $node->id;
		} else $settings["parent"] = $_POST["parent"];

		Page()->setNode($settings);
		Page()->cache->purge("ads");

		header("Location: {$_SERVER["HTTP_REFERER"]}");
		exit;
	}
?>
<form action="<?php print(Page()->getURL()); ?>" method="post" id="saveForm">
	<input type="hidden" name="parent" value="<?php print((int)$_GET["sid"]); ?>">
	<div class="container-fluid big">
		<div class="row">
			<div class="col-xs-6">
				<?php
					if (!$node->data->picture_original && $node && $_GET["subtype"] != "swf") $node->data->picture_original = $node->data->picture;
					if ($node->data->picture_original && DataBase()->getVar()) {
						$oImg = DataBase()->getRow("SELECT * FROM %s WHERE `filepath`='%s'", DataBase()->media, $node->data->picture_original);
					}
				?>
				<div class="thumbnail">
					<div class="wrap">
						<span class="helper"></span><img src="<?php Page()->e($node ? Page()->host . ($_GET["subtype"] == "swf" ? 'Library/Assets/swf.png' : $node->data->picture) : Page()->host . Page()->getEmptyImage(300, 150), 1); ?>" id="picturePreview">
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
					<label for="ad-size">Izmērs:</label>
					<select name="size" id="ad-size" class="form-control">
						<option value="1"<?php print($node && $node->data->bsize == "1" ? ' selected' : ''); ?>>380x200 (autopielāgošana)</option>
					</select>
				</div>
				<div class="form-group">
					<label for="ad-name">Nosaukums:</label>
					<input id="ad-name" name="name" class="form-control" type="text" value="<?php Page()->e($node ? $node->title : "", 1); ?>">
				</div>
				<div class="form-group">
					<label for="ad-url">Web adrese:</label>
					<?php if ($_GET["subtype"] == "swf") { ?>
						<p class="help-block">Jābūt iestrādātai flashā</p>
					<?php } else { ?>
						<input id="ad-url" name="url" class="form-control" type="text" value="<?php Page()->e($node ? $node->data->url : "", 1); ?>">
					<?php } ?>
				</div>
				<div class="form-group form-horizontal">
					<label for="ad-enabled">Publicēts:</label>
					<input id="ad-enabled" name="enabled" class="selector" type="checkbox" value="1"<?php print($node && $node->enabled ? ' checked' : ''); ?>>
				</div>
			</div>
		</div>
	</div>
</form>
<script type="text/javascript">
	$(editDialog).dialog("option", "height", "auto");
	$(editDialog).dialog("option", "position", "center");
	<?php if ($_GET["subtype"] != "swf") { ?>Image<?php } else { ?>File<?php } ?>UploaderSingle("#upload-picture", function(response) {
		$(picturePreview)
			.attr("src", Settings.Host + response.<?php if ($_GET["subtype"] != "swf") { ?>file<?php } else { ?>thumb<?php } ?>);
		$(pictureInput).val(response.file);
		$(editSaveButton).prop("disabled", false);
		<?php if ($_GET["subtype"] != "swf") { ?>$(cropPicture).removeClass("disabled")
		                                                       .data("opts", response.opts);<?php } ?>
		$(pictureOriginalInput).val(response.opts.filepath);
	}, "", {crop: "380x200", "keep": 1, "subtype": "<?php print($_GET["subtype"]); ?>"});
	<?php if ($node) { ?>$(editSaveButton).prop("disabled", false);
	<?php if ($_GET["subtype"] != "swf") { ?>$(cropPicture).removeClass("disabled");<?php } ?><?php } ?>
	$(saveForm).on("submit", function(e) {
		e.preventDefault();
		$(saveForm).ajaxSubmit({
			success: function(response) {
				$("#ajax-container").html($($.parseHTML(response)).find("#ajax-container").html());
				makeThingsSortable();
				$(editDialog).dialog("close");
			}
		});
	});
	$(cropPicture).on("click", function(e) {
		e.preventDefault();
		var imgEditor = new imgEditTool($(this).data("opts")).open(function() {
			this.cropToolInit({
				desiredSize: {w: 380, h: 200}, cancel: function() {
					this.close();
				}, save    : function(data) {
					that = this;
					$.getJSON(<?php Page()->e(Page()->host . "media.upload/?raw_crop=1", 3)?>+'&session=' + Settings.session_id, {
						i: data.i,
						x: data.x,
						y: data.y,
						w: data.w,
						h: data.h,
						r: data.r
					}, function(response) {
						that.close();
						$(picturePreview).attr({src: Settings.Host + response.fileThumb});
						$(pictureInput).val(response.fileThumb);
					});
				}
			});
		});
	});
</script>