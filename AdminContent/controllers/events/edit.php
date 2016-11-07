<?php

	$aTags = array();
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

	if ($_GET["get_cover"]) {
		$entry = Page()->getNode($_GET["get_cover"]);
		$originalImg = new Image(Page()->path . $entry->data->cover_original);
		die(json_encode(array(
			"jsonrpc" => "2.0",
			"resized" => true,
			"file"    => $entry->cover,
			"thumb"   => false,
			"opts"    => array(
				"filename" => basename($entry->data->cover_original),
				"filepath" => $entry->data->cover_original,
				"width"    => $originalImg->width,
				"height"   => $originalImg->height,
				"type"     => "photo"
			)
		)));
		exit;
	}
	if ($_GET["other_events"]) {
		$otherEvents = Page()->getNode(array(
			"filter"       => array(
				"controller" => Page()->controller,
				"view"       => "entry",
				"<SQL>"      => "`language`!='$parent->language' AND `title` LIKE '%" . DataBase()->escape($_GET["other_events"]) . "%'"
			),
			"order"        => array("language" => "ASC", "title" => "ASC"),
			"returnFields" => "id,title,data,data"
		));
		foreach ($otherEvents as $k => $event) {
			if (!$event->cover) unset($otherEvents[ $k ]);
		}
		$otherEvents = array_values($otherEvents);
		echo json_encode(array_map(function ($n) { return array("id" => $n->id, "text" => $n->title); }, $otherEvents));
		exit;
	}

	if ($_SERVER["REQUEST_METHOD"] == "POST") {
		if (!$node) $node = (object)array();
		if (!$node->data) $node->data = (object)array();

		$settings["title"] = $_POST["title"];
		$settings["description"] = $_POST["description"];
		if (!$_POST["address"]) $_POST["address"] = trim(date("dmy") . "-" . preg_replace("#[\-]+#", "-", preg_replace("#[^a-z0-9]#i", "-", strtolower(Page()->removeAccents($settings["title"])))), "_");
		$settings["slug"] = $_POST["address"];

		$y = Page()->getNode(array("filter" => array("parent" => $parent->id, "slug" => $settings["slug"]), "returnResults" => "first"));
		while ($y && $y->id != $node->id) {
			if (preg_match("#(.*)\.(\d+)$#", $settings["slug"], $matches)) {
				$settings["slug"] = $matches[1] . "." . ($matches[2] + 1);
			} else $settings["slug"] .= ".1";
			$y = Page()->getNode(array("filter" => array("parent" => $parent->id, "slug" => $settings["slug"]), "returnResults" => "first"));
		}

		$node->data->caption = $_POST["caption"];
		$settings["cover"] = $_POST["cover"];
		if ($node->data->cover) {
			$settings["cover"] = $node->data->cover;
			unset($node->data->cover);
		}
		$node->data->cover_original = $_POST["cover_original"];

		$settings["view"] = "entry";
		$settings["controller"] = Page()->controller;
		$settings["created_by"] = "controller";
		$settings["tags"] = array_map('trim', explode(",", $_POST["tags"]));
		$settings["show_on_first"] = $_POST["show_on_first"] == 1 ? 1 : 0;

		if (!$_POST["ended-date"]) $_POST["ended-date"] = $_POST["started-date"];
		if (!$_POST["started-date"]) $_POST["started-date"] = $_POST["ended-date"];
		if (!$_POST["started-date"]) $_POST["started-date"] = $_POST["ended-date"] = date("Y-m-d");
		$settings["start"] = Page()->dateCalendarInputToMySQL($_POST["started-date"]) . " " . $_POST["started-time"];
		$settings["end"] = Page()->dateCalendarInputToMySQL($_POST["ended-date"]) . " " . $_POST["ended-time"];

		if ($_POST["added-date"]) {
			$settings["time_added"] = Page()->dateCalendarInputToMySQL($_POST["added-date"]) . " " . $_POST["added-time"];
		}

		$node->data->useful_info = $_POST["useful_info"];
		$node->data->price = preg_replace(array("#[^\d]#", "#((?<=\.)[^.]*)\.#"), array(".", "$1"), $_POST["price"]);
		$node->data->place = array(
			"name"    => $_POST["place_name"],
			"address" => $_POST["place_address"],
			"lat"     => $_POST["place_lat"],
			"lng"     => $_POST["place_lng"]
		);

		// Extra parsing
		$extra = false;
		foreach ((array)$_POST['extra_values'] as $i => $title) {
			$extra[ $i ]["value"] = $_POST['extra_values'][ $i ];
		}
		$node->data->extra = $extra;
		$node->data->project = $_POST["project"];

		$node->data->hideStartTime = ($_POST["hide_start_time"] == "1");
		$node->data->hideEndTime = ($_POST["hide_end_time"] == "1");

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
		$settings["enabled"] = (int)($_POST["published"] == "1");
		$settings["category"] = $_POST["category"];
		$settings["region"] = $_POST["region"];

		$settings["data"] = $node->data;
		$settings["content"] = $_POST["content"];
		Page()->filter("content_for_display", $settings["content"]);

		if ($node && $node->id) {
			$settings["id"] = $node->id;
		} else if ($_POST["parent"]) {
			$settings["parent"] = $_POST["parent"];
		} else $settings["parent"] = $parent->id;

		$x = Page()->setNode($settings);
		if ($x) {
			xLog("events: " . ($node && $node->id ? "Labots" : "Pievienots") . " ieraksts " . $settings["title"], "success", $x);
			FS()->registerMedia($node->cover, $x, "cover", true);
			FS()->registerMedia($node->data->cover_original, $x, "cover_original");
			FS()->unregisterMedia($x, "content");
			FS()->registerMedia($settings["content"], $x, "content");
			FS()->unregisterMedia($x, "files");
			FS()->registerMedia(array_map(function ($n) { return $n["path"]; }, (array)$node->data->files), $x, "files");
		}

		$_SESSION["post_response"] = $x ? array("Ieraksts saglabāts.", "success", "yes") : array("Notika nezināma kļūda. Ieraksts netika saglabāts.", "danger", "no");

		if (!$_POST["redirect"]) $_POST["redirect"] = Page()->aHost . Page()->controller . "/?sid=" . $parent->id;
		header("Location: {$_POST["redirect"]}");
	}

	Page()->addBreadcrumb("Sadaļas", Page()->aHost . "structure/");
	Page()->addBreadcrumb($parent->title, Page()->aHost . Page()->controller . "/?sid=" . $parent->id . "/");
	Page()->addBreadcrumb($node ? $node->title : "Jauns ieraksts", Page()->aHost . Page()->controller . "/edit/" . Page()->reqParams[0] . "/");
	Page()->fluid = true;
	Page()->header();
?>
<form class="addbody new" action="<?= Page()->fullRequestUri ?>" method="post" lang="<?php print($parent->language); ?>">
	<input type="hidden" name="redirect" value="<?= htmlspecialchars($_SERVER["HTTP_REFERER"]) ?>"/>
	<header>
		<a href="<?php if ($_GET["redirect"]) echo $_GET["redirect"]; else echo Page()->adminHost . Page()->controller . "/?sid={$_GET["sid"]}" ?>" class="btn btn-primary btn-lg pull-left btn-back" onclick="">Atpakaļ</a>
		<h1><?= $node->title ?: "[Nenosaukts notikums]" ?></h1>
	</header>

	<div class="col-content">

		<section>
			<h1>Saturs</h1>
			<div class="form-group">
				<label for="title">Notikuma nosaukums:</label>
				<input id="title" class="form-control" type="text" name="title" value="<?php Page()->e($node ? $node->title : "", 1); ?>" maxlength="200"/>
			</div>
			<?php /*<div class="form-group">
					<label for="project">Projekta nosaukums:</label>
					<input id="project" class="form-control" type="text" name="project" value="<?php Page()->e($node ? $node->data->project : "", 1); ?>" maxlength="200" />
				</div>*/ ?>
			<div class="form-group"><!-- Vienuma adrese -->
				<label for="address">Adrese:</label>
				<input id="address" name="address" type="text" value="<?= htmlspecialchars(trim($node->slug, '/')) ?>" data-before="<?= Page()->host . $parent->address ?>" data-after="/"/>
			</div>
			<div class="form-group">
				<label for="description">Īsumā par notikumu:</label>
				<textarea id="description" class="form-control" name="description" maxlength="360"><?php Page()->e($node ? $node->description : "", 1); ?></textarea>
			</div>
			<div class="form-group">
				<label for="content">Saturs:</label>
				<textarea id="content" class="tinymce_big" name="content"><?php Page()->e($node ? $node->content : "", 1); ?></textarea>
			</div>
			<div class="form-group">
				<label for="place-name">Norises vieta:</label>
				<input id="place-name" class="form-control" name="place_name" type="text" value="<?php print(htmlspecialchars($node->data->place->name)); ?>"/>
			</div>
		</section>
		<section>
			<h1>Norises laiks</h1>
			<div class="form-group row">
				<div class="col-xs-6">
					<label for="event-start-date">No:</label>
					<div class="row" id="started-fields">
						<div class="col-xs-9">
							<input id="event-start-date" class="calendar form-control" name="started-date" type="text" value="<?= $node->start ? Page()->dateMySQLToCalendarInput($node->start) : "" ?>" required="required"/>
						</div>
						<div class="col-xs-3 text-center">
							<input name="started-time" type="text" data-type="time" id="started" value="<?= $node->start ? array_value(explode(" ", $node->start), -1) : "00:00" ?>"/>
						</div>
					</div>
				</div>
				<div class="col-xs-6 row">
					<label for="event-end-date">Līdz:</label>
					<div class="row" id="ended-fields">
						<div class="col-xs-9">
							<input id="event-end-date" class="form-control calendar" name="ended-date" type="text" value="<?= $node->end ? Page()->dateMySQLToCalendarInput($node->end) : "" ?>"/>
						</div>
						<div class="col-xs-3 text-center">
							<input name="ended-time" type="text" data-type="time" id="ended" value="<?= $node->end ? array_value(explode(" ", $node->end), -1) : "23:59" ?>"/>
						</div>
					</div>
				</div>
				<div class="col-xs-12">
					<p class="help-block">Ja pasākums norisinās visu dienu, tad sākuma laiku norādīt
						<b>00:00</b> un beigu laiku —
						<b>23:59</b>. <br>Ja nepieciešams norādīt tikai sākuma laiku, tad beigu laikā jāatstāj <b>23:59</b>.
					</p>
				</div>
			</div>
		</section>
		<section>
			<h1>Piezīmes</h1>
			<div class="alert alert-info">
				Parādīsies izceltos blokos.
			</div>
			<ol id="extra-values">
				<?php
					$extra = $node->data->extra;
					foreach ((array)$extra as $e) { ?>
						<li class="form-horizontal">
							<div class="form-group form-custom-1">
								<div class="col-xs-12" >
									<div class="input-group">
										<input type="text" name="extra_values[]" class="form-control" placeholder="" value="<?php echo htmlspecialchars($e->value) ?>">
										<span class="input-group-btn">
											<button type="button" class="btn btn-default delete remove-extra">
												<span class="glyphicon glyphicon-remove"></span>
											</button>
										</span>
									</div>
								</div>
							</div>
						</li>
					<?php } ?>
			</ol>
			<a href="javascript:;" id="add-extra" class="addbutton">Pievienot</a>
		</section>
		<?php /*<section id="files-section">
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
		</section>*/ ?>

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

						<?php if ($parent->subid == "all-events" && !$node) {
							$childs = Page()->getNode(array(
								"filter"       => array(
									"parent"     => $parent->id,
									"controller" => "events",
									"view"       => "list"
								),
								"order"        => array("sort" => "ASC"),
								"returnFields" => "fullAddress,id,title"
							));
							?>
							<div class="form-group">
								<label for="parent" style="display: block;">Sadaļa:</label>
								<select id="parent" name="parent" class="form-control">
									<?php foreach ($childs as $child) { ?>
										<option value="<?php print($child->id); ?>" data-url="<?php Page()->e($child->fullAddress, 1); ?>"><?php Page()->e($child->title, 1); ?></option>
									<?php } ?>
								</select>
							</div>
						<?php } ?>

						<div class="form-group form-horizontal">
							<label class="control-label" for="published">Publicēt:</label>
							<span class="pull-right">
								<input type="checkbox" class="selector" <?= ($node && $node->enabled ? "checked" : "") ?> id="published" name="published" value="1">
							</span>
						</div>
						<?php /*<div class="form-group form-horizontal">
							<label class="control-label" for="comments">Atļaut komentārus:</label>
							<span class="pull-right">
								<input type="checkbox" class="selector" <?= ($node && $node->data->comments ? "checked" : "") ?> id="comments" name="comments" value="1">
							</span>
						</div>*/ ?>
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
								if (!$node->data->cover_original && $node) $node->data->cover_original = $node->cover;
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
								<img class="cover" width="100%"<?php if ($node->cover) { ?> src="<?= Page()->host . $node->cover ?>"<?php } else { ?> src="<?php print(Page()->host . Page()->getEmptyImage(400)); ?>"<?php } ?>/>
								<input type="hidden" name="cover" value="<?= $node->cover ?>"/>
								<input type="hidden" name="cover_original" value="<?= $node->data->cover_original ?>"/>
								<a href="#" class="<?php if (!$node->cover) { ?>hidden<?php } ?> delete2 btn btn-xs btn-default"><span class="glyphicon glyphicon-remove"></span></a>
							</div>
						</fieldset>
					</div>
				</div>
				<p class="span form-actions">
					<a href="<?php if ($_GET["redirect"]) echo $_GET["redirect"]; else echo Page()->adminHost . Page()->controller . "/?sid={$_GET["sid"]}" ?>" class="btn btn-default">Atcelt</a>
					<button type="submit" class="btn btn-success pull-right">Saglabāt</button>
					<?php if ($node && (!$node->builtin || ActiveUser()->isDev())) { ?>
					<a href="<?php print(Page()->aHost); ?>structure/delete/<?php echo $node->id ?>/?return-to=<?= urlencode($_SERVER["HTTP_REFERER"]) ?>" class="btn btn-danger" data-confirm="<?php echo htmlspecialchars("Tiešām vēlies dzēst šo ierakstu?") ?>">Dzēst ierakstu</a><?php } ?>
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


<div id="extra-markup" style="display: none;">
	<li class="form-horizontal">
		<div class="form-group form-custom-1">
			<div class="col-xs-12">
				<div class="input-group">
					<input type="text" name="extra_values[]" class="form-control" placeholder="">
					<span class="input-group-btn">
						<button type="button" class="btn btn-default delete remove-extra">
							<span class="glyphicon glyphicon-remove"></span>
						</button>
					</span>
				</div>
			</div>
		</div>
	</li>
</div>
<script type="text/javascript" src="https://maps.googleapis.com/maps/api/js"></script>
<script type="text/javascript">
	$(document).on('click', '#add-extra', function(e) {
		e.preventDefault();
		$('#extra-values').append($('#extra-markup').html());
	});

	$(document).on('click', '.remove-extra', function(e) {
		e.preventDefault();
		var elem = $(this);

		cmsConfirm("Vai tiešām vēlies dzēst šo papildus saiti?", function(response) {
			if (response == true) {
				elem.parents('li').fadeOut(500, function() {
					elem.parents('li').remove();
				});
			}
		});
	});

	var map, geocoder, marker, latlngupdated = true;
	var update_timeout                       = null;
	var vurl                                 = "";
	var acta                                 = [];
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
		}, "crop", {resize: "780x10000", keep: 1});
		$("#fb-share-pic-content .croptool").on("click", function(e) {
			e.preventDefault();
			var imgEditor = new imgEditTool($(this).data("opts")).open(function() {
				this.cropToolInit({
					cancel: function() {
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
				.attr("src", <?php Page()->e(Page()->host . Page()->getEmptyImage(400), 3); ?>);
			$("#fb-share-pic-content a.delete2").addClass("hidden");
			$("#fb-share-pic-content a.croptool").addClass("hidden");
			$("#fb-share-pic-content input").val("");
			$('#cover_2_1').prev('input').val("");
			$('#cover_2_2').prev('input').val("");
			$('.other-thumbs').addClass("hidden");
		});
		$(document).ready(function() {
			$('.combobox').combobox();
		});
		geocoder = new google.maps.Geocoder();
		$("#place_name").on("blur", function(e) {
			if ($("#place_lng").val() == "" && $("#place_lat").val() == "") {
				latlngupdated = false;
				geocoder.geocode({address: $("#place_name").val()}, function(res, status) {
					if (status == google.maps.GeocoderStatus.OK) {
						$("#place_lat").val(res[0].geometry.location.lat());
						$("#place_lng").val(res[0].geometry.location.lng());
						latlngupdated = true;
					}
				});
			}
		});
		$(".calendar").calendar({disablePast: false});
		$(".tabsontop > .tabs").each(function() {
			var $tabs = $(this);
			$tabs.find("a").on("click", function(e) {
				e.preventDefault();
				var $section = $($(this).attr("href"));
				$section.show().siblings("section").hide();
			});
		});
		$("#map").on("click", function(e) {
			e.preventDefault();
			var mapOpener = setInterval(function() {
				if (!latlngupdated) {
					return;
				}
				$($.parseHTML("<div\/>"))
					.append('<div id="map-canvas" style="width: 640px; height: 400px;"><\/div>')
					.dialog({
						modal    : true,
						draggable: false,
						resizable: false,
						width    : 640,
						height   : 470,
						title    : "Vietas pozicionēšana",
						buttons  : [
							{
								text   : "Labi",
								click  : function() {
									$("#place_lat").val(marker.getPosition().lat());
									$("#place_lng").val(marker.getPosition().lng());
									$(this).dialog("close");
								},
								"class": "btn btn-success"
							},
							{
								text   : "Atcelt",
								click  : function() {
									$(this).dialog("close");
								},
								"class": "btn btn-default"
							}
						],
						open     : function() {
							$("body").css({overflow: "hidden"});
							if ($("#place_lat").val() != "" && $("#place_lng").val() != "") {
								var mapOptions = {
									zoom  : 12,
									center: new google.maps.LatLng(parseFloat($("#place_lat")
										.val()), parseFloat($("#place_lng").val()))
								};
							}
							else {
								var mapOptions = {
									zoom  : 7,
									center: new google.maps.LatLng(56.879635, 24.60318899999993)
								};
							}
							map    = new google.maps.Map(document.getElementById("map-canvas"), mapOptions);
							marker = new google.maps.Marker({
								map      : map,
								position : mapOptions.center,
								draggable: true
							});

							google.maps.event.addListener(map, 'click', function(event) {
								update_timeout = setTimeout(function() {
									marker.setPosition(event.latLng);
									/*$("#place_lat").val(marker.getPosition().lat());
									 $("#place_lng").val(marker.getPosition().lng());*/
								}, 200);
							});

							google.maps.event.addListener(map, 'dblclick', function(event) {
								clearTimeout(update_timeout);
							});
						},
						close    : function() {
							$("body").css({overflow: "auto"});
							$(this).dialog("destroy").remove();
						}
					});
				clearInterval(mapOpener);
			}, 10);
		});
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
		$("select[name=parent]").on("change", function() {
			var u = $(this).find("option:selected").data("url");
			if (u) {
				$("input[name=address]").data("before", u);
			}
		}).trigger("change");
		$("[name=address]").on("change", function() {
			var s = $(this).val();
			var p = <?php echo json_encode((int)$parent->id)?>;
			var c = <?php echo json_encode((int)$node->id)?>;
			$.getJSON(<?php echo json_encode(Page()->aHost . "structure/check-slug/")?>, {
				p: p,
				c: c,
				s: s
			}, function(response) {
				if (response.available) {
					$("[name=address]").parent().css("border-color", "");
				}
				else {
					$("[name=address]").parent().css("border-color", "red");
				}
			});
		});
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
					appendTo: ".jui:first",
					delay   : 200
				}
			});
		}); // End of Tagi

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

		// $("#tags").tagit("destroy").val("yo ya,y'w,bla").tagit();
		// tinymce.get("content").setContent("lala");
		$("#import").on("change", function() {
			var that = this;
			cmsConfirm("Vai tiešām vēlies turpināt? Visa esošā informācija tiks nomainīta.", function(yes) {
				var eId = $(that).val();
				if (yes) {
					$.get("<?php print(Page()->aHost . Page()->controller . "/getEvent/"); ?>" + eId + "/", function(response) {
						$("#tags").tagit("destroy").val(response.tags_input).tagit();
						tinymce.get("content").setContent(response.controller_content);
						$("#title").val(response.title).trigger("keyup");
						$("#description").val(response.description).trigger("keyup");
						$("#place_title").val(response.data.place.name);
						$("#place_address").val(response.data.place.address);
						$("#place_lat").val(response.data.place.lat);
						$("#place_lng").val(response.data.place.lng);
						var opt = $("select.combobox.eventname option").filter(function() {
							return $(this).attr("value") == response.category;
						});
						if (!opt.length) {
							opt = $("<option\/>").attr({value: response.category}).html(response.category)
							                     .appendTo($("select.combobox.eventname")).prop({selected: true});
						}
						else {
							opt.eq(0).prop({selected: true});
						}
						$("select.combobox.eventname").combobox("refresh");
						var extra = $.parseJSON(response.data.extra);
						if ($.isArray(extra)) {
							$('#extra-values').empty();
							$.each(extra, function(k, v) {
								var cl = $('#extra-markup').clone();
								cl.find('[name^="extra_titles"]').attr({value: v.title});
								cl.find('[name^="extra_values"]').attr({value: v.value});
								$('#extra-values').append(cl.html());
							});
						}
					});
				}
				else {
					$(that).children().eq(0).prop({selected: true});
				}
			});
		});
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

<?php Page()->footer(); ?>
