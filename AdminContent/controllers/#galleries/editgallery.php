<?php

	if (!ActiveUser()->can(Page()->controller, "pārvaldīt")) {
		Page()->accessDenied();
	}

	$nodes = false;
	if (is_numeric(Page()->reqParams[0])) {
		$nodes = Page()->getNode(array(
			"filter" => array(
				"id" => array_map(function ($n) { return $n["node_id"]; }, DataBase()->getRows("SELECT `node_id` FROM %s WHERE `gallery_id`=%d", DataBase()->galleries, Page()->reqParams[0]))
			)
		));
	}

	if ($_POST["title"]) {
		$ids = array();
		$parent = false;
		$idx = false;

		foreach ($_POST["title"] as $language => $title) {
			if (empty($title)) continue;
			$node = false;
			if ($nodes) $idx = array_search($language, array_map(function ($n) { return $n->language; }, $nodes));
			if ($idx !== false) $node = $nodes[ $idx ];
			if (!$node) {
				$parent = Page()->getNode(array(
					"filter"        => array(
						"controller" => "galleries",
						"view"       => "list",
						"language"   => $language
					),
					"returnResults" => "first"
				));
			}

			$settings = array(
				"title"       => $_POST["title"][ $language ],
				"description" => $_POST["description"][ $language ],
				"enabled"     => (int)$_POST["enabled"],
				"subid"       => (int)$_POST["download"][ $language ],
				"controller"  => Page()->controller,
				"time_added"  => Page()->dateCalendarInputToMySQL($_POST["added-date"]) . " " . $_POST["added-time"],
				"view"        => "gallery"
			);
			if ($node) {
				$settings["id"] = $node->id;
			} else {
				$settings["parent"] = $parent->id;
				$settings["slug"] = trim(date("dmy") . "-" . preg_replace("#[\-]+#", "-", preg_replace("#[^a-z0-9]#i", "-", strtolower(Page()->removeAccents($settings["title"])))), "_");
				$settings["cover"] = Page()->getEmptyImage(300, 300);
			}
			$ids[] = array(Page()->setNode($settings), $language);
		}

		$gallery = ($nodes ? Page()->reqParams[0] : DataBase()->getVar("SELECT MAX(`gallery_id`) FROM %s", DataBase()->galleries) + 1);
		foreach ($ids as $id) {
			if (!$id[0]) continue;
			DataBase()->insert("galleries", array(
				"gallery_id" => $gallery,
				"node_id"    => $id[0],
				"language"   => $id[1]
			), true);
			FS()->unregisterMedia($id[0], "gallery-photo");
			$media = DataBase()->getRows("SELECT `path` FROM %s WHERE `parent`=%d", DataBase()->gallery, $gallery);
			if ($media) {
				FS()->registerMedia(array_map(function ($n) { return $n["path"]; }, $media), $id[0], "gallery-photo");
			}
		}

		$photos = DataBase()->getRows("SELECT `path` FROM %s WHERE `parent`=%d ORDER BY `sort` ASC", DataBase()->gallery, $gallery);
		if (!$photos) {
			$photos = array(array("path" => Page()->getEmptyImage(300, 300)));
		}
		if (count($photos) && $nodes) {
			foreach ($nodes as $node) {
				Page()->setNode(array(
					"id"    => $node->id,
					"cover" => $photos[0]["path"]
				));
				FS()->unregisterMedia($node->id, "gallery-photo");
				FS()->registerMedia(array_map(function ($n) { return $n["path"]; }, $photos), $node->id, "gallery-photo");
			}
		}

		print(json_encode(array(
			"status"     => "ok",
			"new"        => !$nodes,
			"id"         => $gallery,
			"info"       => "Galerijas pamatuzstādījumi saglabāti!",
			"galleryUrl" => Page()->aHost . Page()->controller . "/listgallery/" . $gallery . "/"
		)));

		exit;
	}

	if (Page()->reqParams[0] && !$nodes) {
		die('<div class="alert alert-danger"><p><strong>Notika kļūda.</strong> Pārlādē lapu un mēģini vēlreiz.</p></div>');
	}

?>
<form class="form" action="<?php Page()->e(Page()->getURL(), 1); ?>" method="post" id="editForm" style="overflow-x: hidden">
	<div class="form-group">
		<label for="gallery_enabled" class="control-label">Galerija publicēta:</label>
		<div class="pull-right">
			<input type="checkbox" id="gallery_enabled" name="enabled" class="selector" value="1"<?php print($nodes && $nodes[0]->enabled ? ' checked' : ''); ?>>
		</div>
	</div>
	<div class="form-group form-horizontal">
		<label class="control-label">Pievienošanas laiks:</label>
		<div class="clearfix row pull-right">
			<div class="col-xs-4" style="width: 145px;">
				<input title="Galerijas pievienošanas datums" class="form-control" id="added-date" name="added-date" type="text" value="<?= $nodes && $nodes[0]->time_added ? Page()->dateMySQLToCalendarInput(array_value(explode(" ", $nodes[0]->time_added))) : date("d / m / Y", time()) ?>"/>
			</div>
			<div class="col-xs-2 center" style="width: 80px;">
				<input title="Galerijas pievienošanas laiks" name="added-time" type="text" data-type="time" id="added" value="<?= $nodes && $nodes[0]->time_added ? array_value(explode(" ", $nodes[0]->time_added), -1) : date("H:i:s", time()) ?>"/>
			</div>
		</div>
	</div>
	<div class="tabpanel">
		<ul class="nav nav-tabs" role="tablist">
			<?php foreach (Page()->languages as $k => $language) { ?>
				<li role="presentation" class="<?php if ($k == 0) { ?> active<?php } ?>">
					<a href="#settings-<?php echo $language ?>" aria-controls="<?php echo $language ?>" role="tab" data-toggle="tab"><?php echo Page()->language_labels[ $language ] ?></a>
				</li>
			<?php } ?>
		</ul>
		<div class="tab-content">
			<?php foreach (Page()->languages as $k => $language) {
				$node = null;
				$idx = false;
				if ($nodes) $idx = array_search($language, array_map(function ($n) { return $n->language; }, $nodes));
				if ($idx !== false) $node = $nodes[ $idx ];
				?>
				<div role="tabpanel" class="tab-pane<?php if ($k == 0) { ?> active<?php } ?>" id="settings-<?php echo $language ?>">
					<div class="form-group row">
						<div class="col-xs-12">
							<label for="gallery_name" class="control-label">Galerijas nosaukums:</label>
							<input type="text" id="gallery_name" name="title[<?php print($language); ?>]" class="form-control" value="<?php Page()->e($node->title, 1); ?>">
						</div>
					</div>
					<div class="form-group row">
						<div class="col-xs-12">
							<label for="gallery_description" class="control-label">Galerijas īsais apraksts:</label>
							<textarea id="gallery_description" name="description[<?php print($language); ?>]" class="form-control" style="height: 75px;"><?php Page()->e($node->description, 1); ?></textarea>
						</div>
					</div>
				</div>
			<?php } ?>
		</div>
	</div>
</form>
<script type="text/javascript">
	$("#galleryEditDialog").dialog("option", "buttons", [
		{
			text   : "Atcelt",
			"class": "btn btn-default",
			click  : function() {
				$(this).dialog("close");
			}
		}, {
			text   : "Saglabāt",
			"class": "btn btn-primary",
			click  : function() {
				$(this).find("form").ajaxSubmit({
					dataType: "json",
					success : function(response) {
						if (typeof response.status == "undefined") {
							response.status = "err";
						}
						if (response.status == "ok") {
							if (response["new"]) {
								document.location.href = response["galleryUrl"];
							}
							else {
								$.get(document.location.href, function(resp) {
									$("#ajax-container").html($($.parseHTML(resp)).find("#ajax-container").html());
								});
								$("#galleryEditDialog").dialog("close");
							}
						}
					}
				});
			}
		}
	]).find('[name="added-date"]').calendar({disablePast: false});

</script>
