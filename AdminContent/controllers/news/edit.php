<?php

	if (Page()->reqParams[0]) {
		$node = array_value(Page()->getNode(array(
			"filter" => array(
				"id"         => Page()->reqParams[0],
				"created_by" => "controller",
				"view"       => "entry"
			)
		)));

		$parent = array_value(Page()->getNode(array(
			"filter" => array(
				"id"         => $node->parent,
				"created_by" => array("controller", "manual"),
				"controller" => Page()->controller
			)
		)));
	} else {
		$parent = array_value(Page()->getNode(array(
			"filter" => array(
				"id"         => $_GET["sid"],
				"created_by" => array("controller", "manual"),
				"controller" => Page()->controller
			)
		)));
	}
	if (!ActiveUser()->canWrite("node", $parent->id)) {
		Page()->accessDenied();
	}

	$fpage = Settings()->get("first_page_data", $parent->language);
	$avlists = array();
	$avlists[ (string)$fpage["nb"]["node"] ] = "news";
	$avlists[ (string)$fpage["ab"]["node"] ] = "actualities";

	if ($_SERVER["REQUEST_METHOD"] == "POST") {

		if (!$node) $node = (object)array();
		if (!$node->data) $node->data = (object)array();

		$node->data->caption = $_POST["caption"];
		$settings["cover"] = $_POST["cover"];
		if ($node->data->cover) {
			$settings["cover"] = $node->data->cover;
			unset($node->data->cover);
		}
		$node->data->cover_original = $_POST["cover_original"];

		$settings["title"] = $_POST["title"];
		$settings["description"] = $_POST["description"];
		if (!$_POST["address"]) $_POST["address"] = trim(date("dmy") . "-" . preg_replace("#[\-]+#", "-", preg_replace("#[^a-z0-9]#i", "-", strtolower(Page()->removeAccents($settings["title"])))), "_");
		$settings["slug"] = $_POST["address"];

		$y = Page()->getNode(array("filter" => array("parent" => $parent->id, "slug" => $settings["slug"]), "returnResults" => "first"));
		while ($y && $y->id != $node->id) {
			if (preg_match("#(.*)\.(\d)+$#", $settings["slug"], $matches)) {
				$settings["slug"] = $matches[1] . "." . ($matches[2] + 1);
			} else $settings["slug"] .= ".1";
			$y = Page()->getNode(array("filter" => array("parent" => $parent->id, "slug" => $settings["slug"]), "returnResults" => "first"));
		}

		$settings["view"] = "entry";
		$settings["controller"] = Page()->controller;
		$settings["created_by"] = "controller";
		$settings["show_on_first"] = $_POST["show_on_first"] == 1 ? 1 : 0;

		if ($_POST["added-date"]) {
			$settings["time_added"] = Page()->dateCalendarInputToMySQL($_POST["added-date"]) . " " . $_POST["added-time"];
		}

		$node->data->files = array();
		if (is_array($_POST["file_id"])) {
			foreach ($_POST["file_id"] as $idx => $fileId) {
				$filePath = $_POST["file_path"][ $idx ];
				$fileName = $_POST["file_name"][ $idx ];
				$fileData = array(
					"id"   => $fileId,
					"path" => $filePath,
					"name" => $fileName
				);
				$node->data->files[] = $fileData;
			}
		}

		$node->data->comments = ($_POST["comments"] == "1");
		$node->data->published = ($_POST["published"] == "1");
		if ($_POST["schedule"]) {
			$node->data->schedule = (object)array();
			$node->data->schedule->state = true;
			$node->data->schedule->date = Page()->dateCalendarInputToMySQL($_POST["scheduled-date"]);
			$node->data->schedule->time = $_POST["scheduled-time"];
			$node->data->schedule->datetime = $node->data->schedule->date . " " . $node->data->schedule->time;
		} else {
			$node->data->schedule = (object)array();
			$node->data->schedule->state = false;
		}

		if ($_POST["mail_to_subscribers"] == "1" && $this->newsLettersEnabled) {
			$node->data->mail_to_subscribers = true;
		}

		if (!$node->data->published || ($node->data->schedule->state && strtotime($node->data->schedule->datetime) > time())) {
			$settings["enabled"] = 0;
		} else {
			$settings["enabled"] = 1;
		}

		$settings["data"] = $node->data;
		$settings["content"] = $_POST["content"];
		Page()->filter("content_for_display", $settings["content"]);
		$settings["category"] = $_POST['category'];
		$settings["tags"] = array_map('trim', explode(",", $_POST["tags"]));

		$pq = phpQuery::newDocument($settings["content"]);
		$iframes = pq("iframe");
		$node->data->video = false;
		foreach ($iframes as $iframe) {
			if (preg_match("#(youtube|vimeo)#", pq($iframe)->attr("src"))) {
				$node->data->video = true;
				break;
			}
		}

		if ($node && $node->id) {
			$settings["id"] = $node->id;
		} else $settings["parent"] = $parent->id;

		$x = Page()->setNode($settings);

		if ($x) {
			xLog("news: " . ($node && $node->id ? "Labots" : "Pievienots") . " ieraksts " . $settings["title"], "success", $x);
			FS()->registerMedia($node->cover, $x, "cover", true);
			FS()->registerMedia($node->data->cover_original, $x, "cover_original");
			FS()->unregisterMedia($x, "content");
			FS()->registerMedia($settings["content"], $x, "content");
			FS()->unregisterMedia($x, "files");
			FS()->registerMedia(array_map(function ($n) { return $n["path"]; }, (array)$node->data->files), $x, "files");

			if ($node->data->mail_to_subscribers && $settings["enabled"]) {
				$node = $this->getNode($x);
				if (in_array((string)$node->parent, array_keys($avlists))) {
					$this->mailScheduledPost($node, $avlists[ (string)$node->parent ]);
				}
			}
		}

		$_SESSION["post_response"] = $x ? array("Jūsu ieraksts ir saglabāts." . ($settings["time_added"] ? "Tam piešķirts <em>pievienošanas datums</em> <b>" . $settings["time_added"] . "</b>." : ""), "success", "yes") :
			($y !== false && $y[0]->id != Page()->reqParams[0] ? array("Norādītā ieraksta adrese jau pastāv.", "danger", "no")
				: array("Notika nezināma kļūme", "danger", "no")
			);

		if (!$_POST["redirect"]) $_POST["redirect"] = Page()->aHost . Page()->controller . "/?sid=" . $parent->id;
		header("Location: {$_POST["redirect"]}");
	}

	Page()->addBreadcrumb($parent->title, Page()->aHost . Page()->controller . "/?sid=" . $parent->id . "/");
	Page()->addBreadcrumb($node ? $node->title : "Jauns ieraksts", Page()->aHost . Page()->controller . "/edit/" . Page()->reqParams[0] . "/");

	Page()->header();

	Page()->filter("content_for_edit", $node->content);
	//Page()->debug($node->content);

?>
<form class="addbody new" action="<?= Page()->fullRequestUri ?>" method="post" lang="<?php print($parent->language); ?>">
	<input type="hidden" name="redirect" value="<?php print(htmlspecialchars($_SERVER["HTTP_REFERER"])); ?>"/>

	<header>
		<a href="<?php if ($_GET["redirect"]) echo $_GET["redirect"]; else echo Page()->adminHost . Page()->controller . "/?sid={$_GET["sid"]}" ?>" class="btn btn-primary btn-back pull-left" onclick="">Atpakaļ</a>
		<h1><?php print($node->title ? $node->title : "Jauns ieraksts"); ?></h1>
	</header>

	<div class="col-content">
		<section class="jui">
			<h1>Saturs</h1>
			<div class="form-group">
				<label for="title">Nosaukums:</label>
				<input id="title" class="form-control" type="text" name="title" maxlength="100" value="<?= htmlspecialchars($node->title) ?>" required/>
			</div>
			<div class="form-group"><!-- Vienuma adrese -->
				<label for="address">Adrese:</label>
				<input id="address" name="address" type="text" value="<?= htmlspecialchars(trim($node->slug, '/')) ?>" data-before="<?= Page()->host . $parent->address ?>" data-after="/"/>
			</div>
			<div class="form-group">
				<label for="content">Saturs:</label>
				<textarea id="content" class="tinymce_big form-control" name="content" style="height: 550px;"><?= htmlspecialchars($node->content) ?></textarea>
			</div>
			<div class="form-group">
				<label for="description">Apraksts:</label>
				<textarea id="description" class="form-control" name="description" maxlength="360"><?= htmlspecialchars($node->description) ?></textarea>
			</div>
		</section>
		<section id="files-section">
			<h1>Pievienotie faili</h1>
			<div class="file-items">
				<label class="control-label">Faili:</label>
				<?php if (is_array($node->data->files) && count($node->data->files)) { ?><?php foreach ($node->data->files as $file) { ?>
					<div class="form-group file-item" data-id="<?php Page()->e($file->id, 1); ?>">
						<div class="input-group">
							<input type="hidden" name="file_id[]" value="<?php Page()->e($file->id, 1); ?>" class="file_id">
							<input type="text" name="file_name[]" value="<?php Page()->e($file->name, 1); ?>" class="form-control file-name" placeholder="nosaukums">
							<span class="input-group-addon" style="border-left: 0; border-right: 0;"><span class="mce-i-othericons ic-link"></span></span>
							<input type="text" name="file_path[]" value="<?php Page()->e($file->path, 1); ?>" class="form-control file-path" placeholder="adrese">
							<div class="input-group-btn">
								<button class="btn btn-default file-upload-button" type="button" info="Augšupielādēt failu">
									<span class="ic-upload3 mce-i-othericons"></span></button>
								<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
									<span class="mce-i-othericons ic-cogs"></span>
									<span class="caret"></span></button>
								<ul class="dropdown-menu dropdown-menu-right">
									<li><a href="#" class="file-select-button">Izvēlēties failu no datubāzes</a></li>
									<li role="separator" class="divider"></li>
									<li><a href="#" class="file-delete-button">Dzēst</a></li>
								</ul>
							</div><!-- /btn-group -->
						</div>
					</div>
				<?php } ?><?php } ?>
			</div>
			<a href="#" class="addbutton" id="file-add">Pievienot failu / saiti</a>
		</section>
	</div>
	<div class="col-sidebar">
		<aside class="rightbar tabsontop">
			<div class="groupbutton tabs">
				<div>
					<a class="button selected" href="#settings">Uzstādījumi</a>
					<a class="button" href="#cover">Titulattēls</a>
				</div>
			</div>
			<section>
				<div id="settings">
					<h1>Uzstādījumi</h1>
					<div class="content">
						<div class="form-group form-horizontal">
							<label for="schedule" class="control-label">Ieplānot:</label>
							<span class="pull-right">
								<input type="checkbox" class="selector" <?= ($node && $node->data->schedule->state ? "checked" : "") ?> id="schedule" name="schedule" value="1">
							</span>
						</div>
						<div class="form-group clearfix row" id="schedule-fields" style="margin-top: -10px;">
							<div class="col-xs-8">
								<input class="form-control" name="scheduled-date" type="text" value="<?= $node && $node->data->schedule->date ? Page()->dateMySQLToCalendarInput($node->data->schedule->date) : date("d / m / Y", strtotime("+1 day")) ?>"/>
							</div>
							<div class="col-xs-4 center">
								<input name="added-time" type="text" data-type="time" id="added" value="<?= $node->data->schedule->time ? array_value(explode(" ", $node->data->schedule->time), -1) : date("H:i:s", time()) ?>"/>
							</div>
						</div>
						<div class="form-group form-horizontal">
							<label for="published" class="control-label">Publicēt:</label>
							<span class="pull-right">
								<input type="checkbox" class="selector" <?= ($node && $node->data->published ? "checked" : "") ?> id="published" name="published" value="1">
							</span>
						</div>
						<div class="form-group form-horizontal">
							<label class="control-label" for="comments">Atļaut komentārus:</label>
							<span class="pull-right">
								<input type="checkbox" class="selector" <?= ($node && $node->data->comments ? "checked" : "") ?> id="comments" name="comments" value="1">
							</span>
						</div>
						<?php if (in_array($parent->id, array_keys($avlists)) && (!$node || !$node->data->mail_to_queued)) { ?>
							<div class="form-group form-horizontal">
								<label for="mail_to_subscribers" class="control-label">Izsūtīt jaunumu:</label>
								<span class="pull-right">
									<input type="checkbox" class="selector" <?= ($node && $node->data->mail_to_subscribers ? "checked" : "") ?> <?= ($node && $node->data->mail_to_queued ? "disabled" : "") ?> id="mail_to_subscribers" name="mail_to_subscribers" value="1">
								</span>
							</div>
						<?php } ?>
						<div class="form-group">
							<label class="control-label">Pievienošanas laiks:</label>
							<div class="clearfix row">
								<div class="col-xs-8">
									<input class="form-control" id="added-date" name="added-date" type="text" value="<?= $node && $node->time_added ? Page()->dateMySQLToCalendarInput(array_value(explode(" ", $node->time_added))) : date("d / m / Y", time()) ?>"/>
								</div>
								<div class="col-xs-4 center">
									<input name="added-time" type="text" data-type="time" id="added" value="<?= $node->time_added ? array_value(explode(" ", $node->time_added), -1) : date("H:i:s", time()) ?>"/>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div id="cover" style="display: none;">
					<h1>Titulattēls</h1>
					<div class="content">
						<fieldset>
							<div class="form-group">
								<button type="button" id="upload-fb-share-button" class="btn btn-default btn-upload block">Augšupielādēt
									<div class="progress">
										<div class="progress-bar progress-bar-info" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0;">
											<span></span>
										</div>
									</div>
								</button>
							</div>
							<?php
								if ($node->data->cover_original) {
									$oImg = DataBase()->getRow("SELECT * FROM %s WHERE `filepath`='%s'", DataBase()->media, $node->data->cover_original);
								}
							?>
							<div id="fb-share-pic-content" class="thumbnail">
								<a data-opts="<?php Page()->e(json_encode(array(
									"filename" => basename($node->data->cover_original),
									"filepath" => $node->data->cover_original,
									"width"    => $oImg["width"],
									"height"   => $oImg["height"],
									"type"     => "photo"
								)), 1); ?>" href="#" class="btn btn-xs btn-default croptool<?php if (!$oImg) { ?> hidden<?php } ?>"><img src="<?php print(Page()->bHost); ?>/css/img/icons-edit.png" info="Pielāgot attēlu"></a>
								<img class="cover" width="100%"<?php if ($node->cover) { ?> src="<?= Page()->host . $node->cover ?>"<?php } else { ?> src="<?php print(Page()->host . Page()->getEmptyImage(Page()->thumbnails["news"]["width"], Page()->thumbnails["news"]["height"])); ?>"<?php } ?>/>
								<input type="hidden" name="cover" value="<?= $node->cover ?>"/>
								<input type="hidden" name="cover_original" value="<?= $node->data->cover_original ?>"/>
								<a href="#" class="<?php if (!$node->cover) { ?>hidden<?php } ?> delete2 btn btn-xs btn-default"><span class="glyphicon glyphicon-remove"></span></a>
							</div>
						</fieldset>
					</div>
				</div>
				<p class="span form-actions">
					<a href="<?php if ($_GET["redirect"]) echo $_GET["redirect"]; else echo Page()->adminHost . Page()->controller . "/?sid={$_GET["sid"]}" ?>" class="btn btn-default">Atcelt</a>
					<?php if ($node && (!$node->builtin || ActiveUser()->isDev())) { ?>
					<a href="<?php print(Page()->aHost); ?>structure/delete/<?php print($node->id); ?>/?return-to=<?php print(urlencode($_GET["redirect"] ? $_GET["redirect"] : Page()->adminHost . Page()->controller . "/?sid={$_GET["sid"]}")); ?>" class="btn btn-danger" data-confirm="Tiešām vēlies dzēst šo ierakstu?">Dzēst</a><?php } ?>
					<button type="submit" class="btn btn-success pull-right">Saglabāt</button>
				</p>
			</section>
		</aside>
	</div>
</form>
<div class="form-group file-item hidden" data-id="0" id="file-template">
	<div class="input-group">
		<input type="hidden" name="file_id[]" value="0" class="file_id">
		<input type="text" name="file_name[]" value="" class="form-control file-name" placeholder="nosaukums">
		<span class="input-group-addon" style="border-left: 0; border-right: 0;"><span class="mce-i-othericons ic-link"></span></span>
		<input type="text" name="file_path[]" value="" class="form-control file-path" placeholder="adrese">
		<div class="input-group-btn">
			<button class="btn btn-default file-upload-button" type="button" info="Augšupielādēt failu">
				<span class="ic-upload3 mce-i-othericons"></span></button>
			<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
				<span class="mce-i-othericons ic-cogs"></span>
				<span class="caret"></span></button>
			<ul class="dropdown-menu dropdown-menu-right">
				<li><a href="#" class="file-select-button">Izvēlēties failu no datubāzes</a></li>
				<li role="separator" class="divider"></li>
				<li><a href="#" class="file-delete-button">Dzēst</a></li>
			</ul>
		</div><!-- /btn-group -->
	</div>
</div>

<script type="text/javascript">
	var vurl = "";
	var acta = [];
	$(function() {
		ImageUploaderSingle("#upload-fb-share-button", function(response) {
			$("#fb-share-pic-content img.cover").attr("src", Settings.Host + response.file);
			$('#fb-share-pic-content input[name="cover"]').val(response.file);
			$('#fb-share-pic-content input[name="cover_original"]').val(response.opts.filepath);
			$("#fb-share-pic-content a.delete2").removeClass("hidden");
			$('#fb-share-pic-content .croptool').removeClass("hidden").data("opts", response.opts).click();
			$('#cover_2_1').data("opts", response.opts).prev('input').val("");
			$('#cover_2_2').data("opts", response.opts).prev('input').val("");
			$('.other-thumbs').removeClass("hidden");
		}, "crop", {
			crop: "<?php print(Page()->thumbnails["news"]["width"]); ?>x<?php print(Page()->thumbnails["news"]["height"]); ?>",
			keep: 1
		});
		$("#fb-share-pic-content .croptool").on("click", function(e) {
			e.preventDefault();
			var imgEditor = new imgEditTool($(this).data("opts")).open(function() {
				this.cropToolInit({
					desiredSize: {
						w: <?php print(Page()->thumbnails["news"]["width"]); ?>,
						h: <?php print(Page()->thumbnails["news"]["height"]); ?>}, cancel: function() {
						this.close();
					}, save: function(data) {
						that = this;
						$.getJSON(<?php Page()->e(Page()->host . "media.upload/?raw_crop=1", 3)?>+'&session=' + Settings.session_id, {
							i: data.i,
							x: data.x,
							y: data.y,
							w: data.w,
							h: data.h,
							r: data.r,
							m: {
								w: <?php print(Page()->thumbnails["news"]["width"]); ?>,
								h: <?php print(Page()->thumbnails["news"]["height"]); ?>}
						}, function(response) {
							that.close();
							$("#fb-share-pic-content img.cover").attr({src: Settings.Host + response.fileThumb});
							$('#fb-share-pic-content input[name="cover"]').val(response.fileThumb);
						});
					}
				});
			});
		});

		$("#fb-share-pic-content a.delete2").on("click", function(e) {
			e.preventDefault();
			$("#fb-share-pic-content img")
				.attr("src", <?php Page()->e(Page()->host . Page()->getEmptyImage(Page()->thumbnails["news"]["width"], Page()->thumbnails["news"]["height"]), 3); ?>);
			$("#fb-share-pic-content a.delete2").addClass("hidden");
			$("#fb-share-pic-content a.croptool").addClass("hidden");
			$("#fb-share-pic-content input").val("");
			$('#cover_2_1').prev('input').val("");
			$('#cover_2_2').prev('input').val("");
			$('.other-thumbs').addClass("hidden");
		});

		$('[name="scheduled-date"]').calendar({disablePast: true});
		$('[name="added-date"]').calendar({disablePast: false});
		function toggleSchedule(g) {
			var c = $("#schedule").is(":checked");
			if ((c && g == true) || (!c && (typeof g == "object" || typeof g == "undefined"))) {
				if (g && g == true) {
					$("#schedule-fields").show();
				}
				else {
					$("#schedule-fields").slideDown();
				}
			}
			else {
				if (g && g == true) {
					$("#schedule-fields").hide();
				}
				else {
					$("#schedule-fields").slideUp();
				}
			}
		}

		toggleSchedule(true);
		$("#schedule").prev("a.selector").click(toggleSchedule);

		// Tagi
		$.getJSON("<?=Page()->aHost?>tags/json/?lng=<?php echo $parent->language; ?>", function(resp) {
			if (!resp.error) {
				acta = resp.tags;
			}
			$("#tags").tagit({
				allowSpaces  : true,
				caseSensitive: false,
				fieldName    : "tags",
				availableTags: acta,
				autocomplete : {
					position: {collision: "flip"},
					appendTo: "parent",
					delay   : 200
				}
			});
		}); // End of Tagi

		if ($("[name=address]").val() == "") {
			$("[name=address]").data("changed", false).on("focus", function() {
				if (!$(this).data("changed")) {
					$(this).data("original", $(this).val()).one("blur", function() {
						if ($(this).data("original") != $(this).val()) {
							$(this).data("changed", true);
						}
						$(this).removeData("original");
					});
				}
			});
			$("[name=title]").on("keydown keyup", function() {
				if (!$("[name=address]").data("changed")) {
					$("#address")
						.text(replaceDiacritics($(this).val()).replace(/[^a-zA-Z0-9-]+/gi, "-").replace(/[-]+/gi, '-')
						                                      .trim("-")).trigger("keyup");
				}
			});
		}
		$(".tabsontop > .tabs").each(function() {
			var $tabs = $(this);
			$tabs.find("a").on("click", function(e) {
				e.preventDefault();
				var $section = $($(this).attr("href"));
				$section.show();
				$(this).siblings("a").each(function() {
					var $section = $($(this).attr("href"));
					$section.hide();
				});
			});
		});
		$('#take-cover').selectize({
			create          : false,
			allowEmptyOption: true,
			sortField       : 'text',
			valueField      : 'id',
			labelField      : 'text',
			searchField     : 'text',
			render          : {
				option_create: function(data, escape) {
					return '<div class="create">Pievienot <strong>' + escape(data.input) + '<\/strong>&hellip;<\/div>';
				}
			},
			load            : function(query, callback) {
				$.ajax({
					url     : "",
					type    : 'GET',
					dataType: 'json',
					data    : {
						other_events: query
					},
					error   : function() {
						callback();
					},
					success : function(data) {
						callback(data);
					}
				});
			},
			onChange        : function(value) {
				$.get("", {get_cover: value}, function(data) {
					$("#fb-share-pic-content img.cover").attr("src", Settings.Host + data.file);
					$('#fb-share-pic-content input[name="cover"]').val(data.file);
					$('#fb-share-pic-content input[name="cover_original"]').val(data.opts.filepath);
					$("#fb-share-pic-content a.delete2").removeClass("hidden");
					$('#fb-share-pic-content .croptool').removeClass("hidden").data("opts", data.opts);
				}, "json")
			}
		});
		$(document).on("click", ".file-items .file-item .file-delete-button", function(e) {
			e.preventDefault();
			var thisItem = $(this).parents(".file-item:first");
			cmsConfirm("Vai tiešām vēlies dzēst šo saiti/failu?", function(yes) {
				if (yes) {
					editForm.prepend($("<input\/>").attr("type", "hidden").attr("name", "file-deleted[]")
					                               .val(thisItem.data("id")));
					thisItem.remove();
				}
			});
		});
		var editForm     = $("#edit-form"),
		    addFile      = $("#file-add"),
		    fileTemplate = $("#file-template"),
		    directoryId  = $("#directory-id").val(),
		    nextFileId   = <?php print($node->data->files ? max(array_map(function ($n) { return $n->id; }, (array)$node->data->files)) + 1 : 1); ?>;
		$(document).on("click", ".file-items .file-delete-button", function(e) {
			e.preventDefault();
			$(this).parents(".file-item").fadeOut("fast", function() {$(this).remove();});
		});

		$(addFile).on("click", function(e) {
			e.preventDefault();
			var thisFileId = nextFileId,
			    thisFile   = fileTemplate.clone();
			nextFileId++;
			thisFile.data("id", thisFileId).removeAttr("id").removeClass("hidden").find("input.file_id")
			        .val(thisFileId);
			$(".file-items").append(thisFile);
			FileUploaderSingle($(".file-upload-button", thisFile), function(response) {
				if ($(".file-name", thisFile).val() == "") {
					$(".file-name", thisFile).val(response.name);
				}
				$(".file-path", thisFile).val(response.file);
			}, "resource", [directoryId]);
		});
		FileUploaderSingle($(".file-items .file-item .file-upload-button", editForm), function(response) {
			var thisFile = $(this).parents(".file-item:first");
			if ($(".file-name", thisFile).val() == "") {
				$(".file-name", thisFile).val(response.name);
			}
			$(".file-path", thisFile).val(response.file);
		}, "resource");
		$(document).on("click", ".file-select-button", function(e) {
			e.preventDefault();
			var that = this;
			selectFile(function(file, data) {
				var thisFile = $(that).parents(".file-item:first");
				if ($(".file-name", thisFile).val() == "") {
					$(".file-name", thisFile).val(data.filename);
				}
				$(".file-path", thisFile).val(file);
			});
		});

	});
</script>
<style type="text/css">
	#fb-share-pic-content {
		position: relative;
	}

	#fb-share-pic-content .delete2 {
		position: absolute;
		top: 2px;
		right: 2px;
		color: black;
		line-height: 0;
		font-size: 0;
		padding: 0;
		display: none;
	}

	#fb-share-pic-content .delete2 span {
		font-size: 20px;
	}

	#fb-share-pic-content .delete2:hover {
		color: red;
	}

	.btn.croptool {
		padding: 0;
		font-size: 0;
		line-height: 0;
		position: absolute;
		top: 2px;
		left: 2px;
		display: none;
	}

	.btn.croptool.hidden {
		display: none !important;
	}

	.btn.delete2.hidden {
		display: none !important;
	}

	#fb-share-pic-content:hover .btn.croptool {
		display: block;
	}

	#fb-share-pic-content:hover .btn.delete2 {
		display: block;
	}

</style>
<?php
	Page()->footer();
?>
