<?php
	if (!ActiveUser()->can(Page()->controller,"pārvaldīt")) {
		Page()->accessDenied();
	}

	$gallery = DataBase()->getVar("SELECT `parent` FROM %s WHERE `id`='%s'", DataBase()->gallery, Page()->reqParams[0]);
	$nodes = Page()->getNode(array(
		"filter" => array(
			"id" => array_map(function ($n) { return $n["node_id"]; }, DataBase()->getRows("SELECT `node_id` FROM %s WHERE `gallery_id`=%d", DataBase()->galleries, $gallery))
		)
	));
	$photo = DataBase()->getRow("SELECT * FROM %s WHERE `id`=%d", DataBase()->gallery, Page()->reqParams[0]);


	if ($_POST["delete"]) {
		$photo = DataBase()->getRow("SELECT * FROM %s WHERE `id`=%d", DataBase()->gallery, Page()->reqParams[0]);
		if ($photo && is_file(Page()->path.$photo["path"]) && file_exists(Page()->path.$photo["path"])) @unlink(Page()->path.$photo["path"]);
		DataBase()->queryf("DELETE FROM %s WHERE `id`=%d", DataBase()->gallery, Page()->reqParams[0]);

		$photos = DataBase()->getRows("SELECT `path` FROM %s WHERE `parent`=%d ORDER BY `sort` ASC", DataBase()->gallery, $gallery);
		if (!$photos) {
			$photos = array(array("path"=>Page()->getEmptyImage(300, 300)));
		}
		if (count($photos)) {
			foreach ($nodes as $node) {
				Page()->setNode(array(
					"id"    => $node->id,
					"cover" => $photos[0]["path"]
				));
				FS()->unregisterMedia($node->id, "gallery-photo");
				FS()->registerMedia(array_map(function ($n) { return $n["path"]; }, $photos), $node->id, "gallery-photo");
			}
		}

		if ($photo["type"] == "photo") {
			$path = Page()->path . "Uploads/galleries/";
			$path .= $gallery->slug . ".zip";
			if (file_exists($path)) unlink($path);
		}

		print(json_encode(array(
			"status" => "deleted",
			"id" => Page()->reqParams[0],
			"info" => "Attēls no galerijas izdzēsts."
		)));
		exit;
	}
	if (isset($_POST["caption"]) || isset($_POST["author"])) {

		foreach ($nodes as $node) {
			$nodeId = $node->id;
			$node = Page()->getNode($nodeId);
			$node->data->captions = json_decode(json_encode($node->data->captions), 1);
			$node->data->authors = json_decode(json_encode($node->data->authors), 1);
			$node->data->captions["i".Page()->reqParams[0]] = $_POST["caption"][$nodeId];
			$node->data->authors["i".Page()->reqParams[0]] = $_POST["author"][$nodeId];

			$settings["id"] = $nodeId;
			$settings["struct_data"] = $node->data;
			Page()->setNode($settings);
		}

		print(json_encode(array(
			"status" => "ok",
			"id"     => Page()->reqParams[0],
			"info"   => "Attēla apraksts ir nomainīts."
		)));
		exit;
	}


	if ($_GET["delete"]) {
		?>
		<div class="tw-bs">
			<form class="form" action="<?php Page()->e(Page()->getURL(), 1); ?>" method="post" id="editForm">
				<input type="hidden" name="delete" value="1">
				<div class="alert alert-info">
					<p><strong>Vai tiešām vēlies dzēst šo attēlu/video no galerijas?</strong></p>
				</div>
			</form>
		</div>
		<?php
	} else {

		$photo = DataBase()->getRow("SELECT * FROM %s WHERE `id`=%d", DataBase()->gallery, Page()->reqParams[0]);
		?>
		<form class="form" action="<?php Page()->e(Page()->getURL(), 1); ?>" method="post" id="editForm">
			<div class="container-fluid">
				<div class="form-group row">
					<div class="col-xs-12">
						<div class="tabpanel">
							<ul class="nav nav-tabs" role="tablist">
								<?php foreach (Page()->languages as $k => $language) {
									$node = null; $idx = false;
									if ($nodes) $idx = array_search($language, array_map(function ($n) { return $n->language; }, $nodes));
									if ($idx !== false) $node = $nodes[ $idx ];
									else continue;
									?>
									<li role="presentation" class="<?php if ($k == 0) { ?> active<?php } ?>">
										<a href="#settings-<?php echo $language ?>" aria-controls="<?php echo $language ?>" role="tab" data-toggle="tab"><?php echo Page()->language_labels[ $language ] ?></a>
									</li>
								<?php } ?>
							</ul>
							<div class="tab-content">
								<?php foreach (Page()->languages as $k => $language) {
									$node = null; $idx = false;
									if ($nodes) $idx = array_search($language, array_map(function ($n) { return $n->language; }, $nodes));
									if ($idx !== false) $node = $nodes[ $idx ];
									else continue;
									?>
									<div role="tabpanel" class="tab-pane<?php if ($k == 0) { ?> active<?php } ?>" id="settings-<?php echo $language ?>">
										<textarea id="gallery_description" name="caption[<?php print($node->id); ?>]" class="form-control" style="height: 75px;"><?php Page()->e($node->data->captions->{"i".Page()->reqParams[0]},1); ?></textarea><br>
										<input type="text" class="form-control" name="author[<?php print($node->id); ?>]" value="<?php Page()->e($node->data->authors->{"i".Page()->reqParams[0]},1); ?>" placeholder="Autors">
									</div>
								<?php } ?>
							</div>
						</div>
					</div>
				</div>
			</div>
		</form>
	<?php } ?>
<script type="text/javascript">
	<?php if (!$_GET["delete"]) { ?>
	$("#galleryEditDialog").dialog("option","title","Attēla apraksts");
	<?php } ?>
	$("#galleryEditDialog").dialog("option", "buttons", [
		{
			text:    "Atcelt",
			"class": "btn btn-default",
			click:   function () {
				$(this).dialog("close");
			}
		}, {
			text:    "<?php print($_GET["delete"] ? "Dzēst" : "Saglabāt"); ?>",
			"class": "btn <?php print($_GET["delete"] ? "btn-danger" : "btn-primary"); ?>",
			click:   function () {
				$(this).
				       find("form").
				       ajaxSubmit({
					dataType: "json",
					success:  function (response) {
						if (response.status == "ok") {
							$("#galleryEditDialog").dialog("close");
						}
						else if (response.status == "deleted") {
							$(".list > div").filter(function(){
								return $(this).data("id") == response.id;
							}).remove();
							if ($(".list > div").length == 0) {
								$("#empty-gal-info").removeClass("hidden");
							}
							$("#galleryEditDialog").dialog("close");
						}
					}
				});
			}
		}
	]);
</script>
