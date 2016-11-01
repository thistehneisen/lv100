<?php
	if (!ActiveUser()->can(Page()->controller,"pārvaldīt")) {
		Page()->accessDenied();
	}

	$nodes = false;
	$gallery = false;

	if (is_numeric(Page()->reqParams[0])) {
		/*		$node = Page()->getNode(array(
					"filter"        => array(
						"id" => Page()->reqParams[0]
					),
					"returnResults" => "first"
				));
				$parent = Page()->getNode(array(
					"filter"        => array(
						"id" => $node->parent
					),
					"returnResults" => "first"
				));*/

		$gallery = Page()->reqParams[0];
		$nodes = Page()->getNode(array(
			"filter" => array(
				"id"         => array_map(function ($n) { return $n["node_id"]; }, DataBase()->getRows("SELECT `node_id` FROM %s WHERE `gallery_id`='%d'", DataBase()->galleries, $gallery)),
				"controller" => "galleries",
				"view"       => "gallery"
			)
		));
	}

	if ($_GET["delete_gallery"] == 1) {

		if ($gallery) {
			$photos = DataBase()->getRows("SELECT * FROM %s WHERE `parent`=%d AND `type`='photo'", DataBase()->galleries, $gallery);
			foreach ($photos as $photo) {
				if (file_exists(Page()->path . $photo["path"]) && is_file(Page()->path . $photo["path"])) unlink(Page()->path . $photo["path"]);
			}
		}

		DataBase()->queryf("DELETE FROM %s WHERE `parent`=%d", DataBase()->gallery, $gallery);
		DataBase()->queryf("DELETE FROM %s WHERE `gallery_id`=%d", DataBase()->galleries, $gallery);
		if ($nodes) {
			foreach ($nodes as $node) {
				Page()->remNode($node->id);
				FS()->unregisterMedia($node->id, "gallery_photo");
			}
		}

		$_SESSION["post_success"] = "Galerija <strong>" . $nodes[0]->title . "</strong> izdzēsta!";
		header("Location: ".Page()->aHost.Page()->controller."/");
		exit;
	}

	if ($_POST["sort"]) {
		foreach ($_POST["sort"] as $k => $id) {
			if (!$id) continue;
			DataBase()->update("gallery", array(
				"sort" => $k
			), array(
				"id" => $id
			));
		}
		$photos = DataBase()->getRows("SELECT `path` FROM %s WHERE `parent`=%d ORDER BY `sort` ASC", DataBase()->gallery, $gallery);

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
		exit;
	}

	if ($_POST["gallery_title"]) {
		$ids = array();
		$parent = false;
		$idx = false;

		foreach ($_POST["gallery_title"] as $language => $title) {
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
				"title"       => $_POST["gallery_title"][ $language ],
				"description" => $_POST["gallery_description"][ $language ],
				"content" => $_POST["gallery_content"][ $language ],
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

		header("Location: {$_SERVER["HTTP_REFERER"]}");

		exit;
	}


	$galleryTitle = join(" / ", array_map(function ($n) { return $n->title; }, $nodes));

	$photos = DataBase()->getRows("SELECT * FROM %s WHERE `parent`=%d ORDER BY `sort` ASC", DataBase()->gallery, $gallery);

	Page()->fluid = true;
	Page()->addBreadcrumb("Galerijas", Page()->aHost . Page()->controller . "/");
	Page()->addBreadcrumb($galleryTitle, Page()->aHost . Page()->controller . "/listgallery/" . $gallery . "/");
	Page()->header();
?>
	<section class="container-fluid tw-bs">

		<form action="<?php print(page()->getURL()); ?>" method="post">
			<div class="panel panel-primary">
				<div class="panel-heading">
					<h4 class="panel-title">Galerijas uzstādījumi</h4>
				</div>
				<div class="panel-body">
					<div class="row">
						<div class="col-xs-7">
							<ul class="nav nav-tabs" role="tablist">
								<?php foreach (Page()->languages as $k => $language) { ?>
									<li role="presentation" class="<?php if ($k == 0) { ?> active<?php } ?>">
										<a href="#gallery-<?php echo $language ?>" aria-controls="<?php echo $language ?>" role="tab" data-toggle="tab"><?php echo Page()->language_labels[ $language ] ?></a>
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
									<div role="tabpanel" class="tab-pane<?php if ($k == 0) { ?> active<?php } ?>" id="gallery-<?php echo $language ?>">
										<div class="form-group">
											<label for="gallery-title-<?php print($language); ?>">Galerijas nosaukums:</label>
											<input type="text" name="gallery_title[<?php print($language); ?>]" id="gallery-title-<?php print($language); ?>" class="form-control" value="<?php Page()->e($node->title,1); ?>">
										</div>
										<div class="form-group">
											<label for="gallery-description-<?php print($language); ?>">Galerijas īsais apaksts:</label>
											<textarea name="gallery_description[<?php print($language); ?>]" id="gallery-description-<?php print($language); ?>" class="form-control"><?php Page()->e($node->description,1); ?></textarea>
										</div>
										<div class="form-group">
											<label for="gallery-content-<?php print($language); ?>">Galerijas pilns apaksts:</label>
											<textarea name="gallery_content[<?php print($language); ?>]" id="gallery-content-<?php print($language); ?>" class="form-control tinymce_big"><?php Page()->e($node->content,1); ?></textarea>
										</div>
									</div>
								<?php } ?>
							</div>
						</div>
						<div class="col-xs-5">
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

						</div>

					</div>
				</div>
				<div class="panel-footer clearfix">
					<button class="pull-right btn btn-sm btn-primary" type="submit">Saglabāt</button>
				</div>
			</div>
		</form>

		<div class="panel panel-default">
			<div class="panel-heading">
				<h4 class="panel-title"><?php print($galleryTitle); ?></h4>
				<ul class="nav nav-tabs panel-controls">
					<li><span class="page_status">&nbsp;</span></li>
					<li>
						<a href="#" class="btn-sm btn add-video" role="button" id="add-video">
							<span class="glyphicon glyphicon-facetime-video"></span> Pievienot video
						</a>
					</li>
					<li>
						<a href="#" class="btn-sm btn upload-pictures" role="button" id="upload-picture1">
							<span class="glyphicon glyphicon-picture"></span> Pievienot attēlus
						</a>
					</li>
					<li>
						<a href="#" class="btn-sm btn upload-pictures hq" role="button" id="upload-picture2" info="Izvēloties šo opciju, augšupielādētie attēli netiks pakļauti nekādām izmaiņām. Uzmanību!! Pārlieku smagi attēli ātri piepildīs servera diska vietu!">
							<span class="glyphicon glyphicon-picture"></span> Pievienot HQ attēlus
						</a>
					</li>
				</ul>
			</div>
			<div class="panel-body">
				<div class="alert alert-info<?php print(!$photos ? '' : ' hidden'); ?>" id="empty-gal-info">
					<p>Šobrīd galerijā nav nevienas bidles/video.</p>
				</div>
				<div class="row list clearfix sortable">
					<?php foreach ($photos as $photo) { ?>
						<div class="col-xs-3 <?php print($photo["type"]); ?>" data-id="<?php print($photo["id"]); ?>">
							<div class="thumbnail">
								<div class="panel panel-controls2">
									<a href="#" class="move" info="Klikšķini šeit un neatlaižot pārvieto <?php print($photo["type"] == "photo" ? "attēlu" : "video"); ?>…"><span class="glyphicon glyphicon-move"></span></a>
									<?php if ($photo["type"] == "photo") { ?>
									<a href="#" data-href="<?php print(Page()->aHost . Page()->controller . "/editphoto/" . $photo["id"] . "/"); ?>" class="edit ajax" info="Klikšķini šeit, lai mainītu attēla aprakstu…">
											<span class="glyphicon glyphicon-pencil"></span></a><?php } ?>
									<a href="#" data-href="<?php print(Page()->aHost . Page()->controller . "/editphoto/" . $photo["id"] . "/?delete=1"); ?>" class="delete ajax" info="Klikšķini šeit, lai noņemtu šo <?php print($photo["type"] == "photo" ? "attēlu" : "video"); ?>…"><span class="glyphicon glyphicon-remove"></span></a>
								</div>
								<div class="wrap">
									<span class="helper"></span><img src="<?php print(Page()->host.FS()->getThumb(($photo["type"] == "photo" ? Page()->path : "") . $photo["path"], 400, 300)); ?>">
								</div>
							</div>
						</div>
					<?php } ?>
				</div>
			</div>
			<div class="panel-footer clearfix">
				<a href="<?php print(Page()->getURL(array("delete_gallery" => 1))); ?>" class="btn btn-danger btn-sm pull-right" data-confirm="Tiešām vēlies dzēst galeriju un visu tās saturu?">Dzēst galeriju</a>
			</div>
		</div>
	</section>
	<style type="text/css">
		.list .thumbnail img {
			max-width: 100%;
		}

		.list .thumbnail .wrap {
			white-space: nowrap;
			text-align: center;
			height: 152px;
		}

		.list .thumbnail .wrap .helper {
			display: inline-block;
			height: 100%;
			vertical-align: middle;
		}

		.list .thumbnail .wrap img {
			vertical-align: middle;
			max-height: 152px;
			max-width: 100%;
		}

		.list .thumbnail {
			position: relative;
		}

		.panel-controls2 {
			display: none;
			position: absolute;
			right: 0;
			top: 0;
		}

		.panel-controls2 a {
			font-size: 18px;
			margin: 6px 4px 0;
			display: inline-block;
		}

		.panel-controls2 a.edit, .panel-controls2 a.move, .panel-controls2 a.delete {
			color: #000;
		}

		.panel-controls2 a.edit:hover {
			color: #008cca;
		}

		.panel-controls2 a.delete:hover {
			color: #ff0000;
		}

		.panel-controls2 a.move {
			cursor: move;
		}

		.thumbnail:hover .panel-controls2 {
			display: block;
		}

		.loading-overlay {
			position: absolute;
			bottom: 0;
			left: 0;
			width: 100%;
			height: 100%;
			z-index: 30;
			background: rgba(255, 255, 255, .7);
		}

		@keyframes bounce {
			0% {
				transform: scale(1);
			}
			50% {
				transform: scale(1.2);
			}
			100% {
				transform: scale(1);
			}
		}

		@-webkit-keyframes bounce {
			0% {
				transform: scale(1);
			}
			50% {
				transform: scale(1.2);
			}
			100% {
				transform: scale(1);
			}
		}

		@-moz-keyframes bounce {
			0% {
				transform: scale(1);
			}
			50% {
				transform: scale(1.2);
			}
			100% {
				transform: scale(1);
			}
		}

		.list > div.done {
			animation-name: bounce;
			animation-duration: 500ms;
			-webkit-animation-name: bounce;
			-webkit-animation-duration: 500ms;
			-moz-animation-name: bounce;
			-moz-animation-duration: 500ms;
		}

		.ui-sortable {
			position: relative;
		}

		.tw-bs div.video .thumbnail:before {
			display: inline-block;
			font-family: 'Glyphicons Halflings';
			font-style: normal;
			font-weight: normal;
			line-height: 1;
			background: white;
			content: "\e059";
			border-radius: 3px;
			-moz-border-radius: 3px;
			-webkit-border-radius: 3px;
			padding: 3px;
			position: absolute;
			left: 1px;
			top: 1px;
		}

		.tw-bs div.photo .thumbnail:before {
			display: inline-block;
			font-family: 'Glyphicons Halflings';
			font-style: normal;
			font-weight: normal;
			line-height: 1;
			background: white;
			content: "\e060";
			border-radius: 3px;
			-moz-border-radius: 3px;
			-webkit-border-radius: 3px;
			padding: 3px;
			position: absolute;
			left: 1px;
			top: 1px;
		}

		.media-object {
			display: block;
			max-width: 100px;
		}

		.ui-dialog-buttonset button + button {
			margin-left: 5px;
		}

		.tw-bs a.selector:hover, .tw-bs a.selector:focus, .tw-bs a.selector:active {
			text-decoration: none;
		}

		.tw-bs a.selector em {
			top: -2px;
		}

		.tw-bs a.selector {
			color: #585858;
			background-color: #c5c5c5;
		}

		.tw-bs a.selector.enabled {
			color: #003e59;
			background-color: #49a4cc;
		}

	</style>
	<script>
		function saveSorting() {
			$.post(document.location.href, {
				sort: $.map($(this).children(), function(n, i) {
					return $(n).data("id");
				})
			}, function(response) {
			});
		}
		function makeThingsSortable() {
			$(".sortable").sortable({
				tolerance  : 'pointer',
				revert     : 100,
				handle     : 'a.move',
				containment: "parent",
				stop       : function() {
					saveSorting.apply(this);
				},
				start      : function(e, ui) {
					ui.placeholder.height(ui.item.height());
				}
			});
		}
		function addImageToQueue(btn, file) {
			var item         = $('<div class="col-xs-3"><div class="thumbnail"><div class="loading-overlay"><\/div><div class="wrap"><span class="helper"><\/span><\/div><\/div><\/div>')
				.attr("id", file.id).appendTo($(".list"));
			var image        = $(new Image()).appendTo(item.find(".wrap"));
			var preloader    = new mOxie.Image();
			preloader.onload = function() {
				preloader.downsize(400, 300);
				image.prop("src", preloader.getAsDataURL());
				btn.data().uploader.files_added--;
				if (btn.data().uploader.files_added == 0) {
					$.modal("destroy");
					btn.data().uploader.start();
				}
			};
			preloader.load(file.getSource());
			$("#empty-gal-info").addClass("hidden");
		}
		var ImageUploaderMultiple = function(uplButtons, callback, func, params) {
			if (typeof uplButtons == "string") {
				uplButtons = $(uplButtons);
			}

			uplButtons.each(function() {
				var uplButton          = $(this),
				    uplButtonContainer = $(this).parent();

				if (!uplButton.attr("id")) {
					uplButton.attr({"id": "uplb_" + (new Date()).getTime()});
				}
				if (!uplButtonContainer.attr("id")) {
					uplButtonContainer.attr({"id": "uplb_" + (new Date()).getTime()});
				}

				if (typeof params == "undefined") {
					params = new Array();
				}

				var haveProgress = uplButton.find(".progress").length,
				    progressBar  = uplButton.find(".progress");

				uplButton.data().uploader = new plupload.Uploader({
					runtimes           : 'html5,flash,silverlight',
					browse_button      : uplButton.attr("id"),
					container          : uplButtonContainer.attr("id"),
					max_file_size      : '20mb',
					multi_selection    : true,
					url                : Settings.Host + 'media.upload/?type=photo' + (params ? '&' + $.param(params) :
					                                                                   '') + (uplButton.is(".hq") ?
					                                                                          "&hq=1" :
					                                                                          "&hq=0") + '&session=' + Settings.session_id,
					flash_swf_url      : Settings.adminBaseHost + 'js/plupload/plupload.flash.swf',
					silverlight_xap_url: Settings.adminBaseHost + 'js/plupload/plupload.silverlight.xap',
					filters            : [{title: "Images", extensions: "png,jpg,gif,jpeg"}]
				});
				if (uplButton.length) {
					uplButton.data().uploader.init();
				}
				uplButton.data().uploader.bind('FilesAdded', function(up, files) {
					if (haveProgress) {
						progressBar.show().find(".progress-bar").css({width: "0"})
						           .attr("aria-valuenow", 0).find("span").html("0 %");
					}
					else {
						$.modal({content: '<span class="loading">Notiek augšupielāde (<span id="upload-progress"><\/span>)...<\/span>'});
					}
					up.refresh();
					$.modal({content: "Notiek sagatavošanās augšupielādei..."});
					up.files_added = files.length;
					uplButton.blur();
					$.each(files, function(k, file) {
						addImageToQueue(uplButton, file);
					});
					window.onbeforeunload = function(e) {
						return "Notiek augšupielāde. Pametot lapu tā tiks pārtraukta.";
					};
				});
				uplButton.data().uploader.bind("UploadFile", function() {
					if (haveProgress) {
						progressBar.show().find(".progress-bar").css({width: "0%"})
						           .attr("aria-valuenow", 0).find("span").html("0 %");
					}

				});
				uplButton.data().uploader.bind('BeforeUpload', function(up, file) {
					up.settings.multipart_params = {fileid: file.id}
				});
				uplButton.data().uploader.bind('UploadProgress', function(up, file) {
					if (haveProgress) {
						progressBar.show().find(".progress-bar").css({width: file.percent + "%"})
						           .attr("aria-valuenow", file.percent).find("span")
						           .html(file.percent + " %");
					}
					else {
						$('#upload-progress').html(file.percent + '%');
					}
					$(".list > div").filter(function() {
						return $(this).attr("id") == file.id;
					}).find(".loading-overlay").animate({height: (100 - file.percent) + "%"});
				});
				uplButton.data().uploader.bind('FileUploaded', function(up, file, response) {
					var jsonrpc = $.parseJSON(response.response);
					if (jsonrpc.error) {
						$.modal({content: jsonrpc.error.message, appendClose: "Ok"});
						return;
					}
					if (typeof callback == "function") {
						callback.apply(uplButton.get(0), [
							jsonrpc, uplButton.data().uploader
						]);
					}
				});
				uplButton.data().uploader.bind("UploadComplete", function() {
					if (haveProgress) {
						progressBar.hide();
					}
					else {
						$.modal("destroy");
					}
					window.onbeforeunload = null;
				});
			});
		};
		$(function() {
			makeThingsSortable();
			ImageUploaderMultiple($(".upload-pictures"), function(response) {
				var photoId   = response.id,
				    divId     = response.fileid,
				    photoDiv  = $(".list > div").filter(function() {
					    return $(this).attr("id") == divId
				    }),
				    thumbUrl  = response.thumb,
				    editUrl   = "<?php print(Page()->aHost . Page()->controller . "/editphoto/"); ?>" + photoId + "/",
				    deleteUrl = "<?php print(Page()->aHost . Page()->controller . "/editphoto/"); ?>" + photoId + "/?delete=1";
				photoDiv.find("img").attr("src", Settings.Host + thumbUrl);
				photoDiv.addClass("done").data("id", photoId);
				setTimeout(function() {
					photoDiv.removeClass("done");
				}, 1000);

				var toolsPanel = $("<div\/>").addClass("panel panel-controls2");
				toolsPanel.append('<a href="#" class="move" info="Klikšķini šeit un neatlaižot pārvieto attēlu…"><span class="glyphicon glyphicon-move"><\/span><\/a>');
				toolsPanel.append('<a href="#" data-href="' + editUrl + '" class="edit ajax" info="Klikšķini šeit, lai mainītu attēla aprakstu…"><span class="glyphicon glyphicon-pencil"><\/span><\/a>');
				toolsPanel.append('<a href="#" data-href="' + deleteUrl + '" class="delete ajax" info="Klikšķini šeit, lai noņemtu šo attēlu…"><span class="glyphicon glyphicon-remove"><\/span><\/a>');

				photoDiv.addClass("photo").find(".thumbnail").prepend(toolsPanel);
				makeThingsSortable();
				saveSorting.apply($(".sortable").get(0));
			}, "gallery", {gallery: <?php print($gallery); ?>, make_thumb: "400x300"});
			$('[name="added-date"]').calendar({disablePast: false});
		});

	</script>
	<script type="text/javascript">
		$(function() {
			$(document).on("click", ".ajax", function(e) {
				e.preventDefault();
				var link = this;
				$("<div\/>").attr("id", "galleryEditDialog").dialog({
					modal    : true,
					draggable: false,
					resizable: false,
					width    : 600,
					maxHeight: "80%",
					open     : function() {
						var dialog = this;
						$(dialog).load($(link).data("href"), function() {
							$(dialog).dialog("option", "position", "center center");
						});
					},
					close    : function() {
						$(this).dialog("destroy").remove();
					}
				});
				return false;
			});
			$(document).on("click", "#add-video", function(e) {
				e.preventDefault();
				$("<div\/>")
					.attr("id", "addVideoDialog")
					.dialog({
						title    : "Video pievienošana",
						modal    : true,
						draggable: false,
						resizable: false,
						width    : 600,
						maxHeight: "80%",
						open     : function() {
							var dialog = this;
							$(dialog)
								.load("<?php print(Page()->aHost . Page()->controller . "/addvideo/" . $gallery . "/"); ?>", function() {
									$(dialog).dialog("option", "position", "center center");
								});
						},
						close    : function() {
							$(this).dialog("destroy").remove();
						}
					});
			});
		});
	</script>

<?php Page()->footer(); ?>