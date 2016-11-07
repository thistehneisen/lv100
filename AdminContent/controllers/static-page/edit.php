<?php
	$node = page()->getNode(array(
		"filter"        => array(
			"id" => page()->reqParams[0]
		),
		"returnResults" => "first"
	));

	if (!ActiveUser()->canWrite("node", $node->id)) {
		Page()->accessDenied();
	}

	if ($_GET["ol"]) {
		$ols = page()->getNode(array(
			"filter" => array(
				"created_by" => array("core", "manual"),
				"<SQL>"      => "`title` LIKE '%" . $_GET["ol"] . "%'",
				"language"   => $_GET["l"]
			)
		));
		echo json_encode(array_map(function ($n) { return array("id" => $n->id, "text" => $n->title); }, $ols));
		exit;
	}

	if ($_POST) {
		$settings = array();

		$settings["content"] = $_POST["body"];
		$pq = phpQuery::newDocument($settings["content"]);
		$galleries = pq(".gallery[data-id]");
		foreach ($galleries as $gallery) {
			pq($gallery)->parent()
				->replaceWith('<div class="gallery" data-id="' . pq($gallery)->attr("data-id") . '">' . pq($gallery)->html() . '</div>');
		}

		foreach (page()->languages as $language) {
			if ($node->data->ols && $node->data->ols->{$language} && $node->data->ols->{$language} != $_POST["ols"][ $language ]) {
				$oldOls[] = page()->getNode($node->data->ols->{$language});
			}
			if ($_POST["ols"][ $language ] && (!$node->data->ols || $node->data->ols->{$language} != $_POST["ols"][ $language ])) $newOls[] = page()->getNode($_POST["ols"][ $language ]);
		}
		$node->data->ols = $_POST["ols"];

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

		$settings["content"] = $pq;
		Page()->filter("content_for_display", $settings["content"]);
		$x = page()->setNode(array(
			"content" => $settings["content"],
			"title"   => $_POST["title"],
			"data"    => $node->data,
			"id"      => $node->id
		));
		if ($x) {
			xLog("static-page: Labota lapa " . $_POST["title"], "success", $x);
			FS()->unregisterMedia($x, "content");
			FS()->registerMedia($settings["content"], $x, "content");
			FS()->unregisterMedia($x, "files");
			FS()->registerMedia(array_map(function ($n) { return $n["path"]; }, (array)$node->data->files), $x, "files");
			if (isset($oldOls)) {
				foreach ($oldOls as $oldOl) {
					$oldOl->data->ols->{$parent->language} = 0;
					page()->setNode(array(
						"id"   => $oldOl->id,
						"data" => $oldOl->data
					));
				}
			}
			if (isset($newOls)) {
				foreach ($newOls as $newOl) {
					$newOl->data = json_decode(json_encode($newOl->data), true);
					if (isset($newOl->data["ols"][ $parent->language ]) && $newOl->data["ols"][ $parent->language ] != $x) {
						$newOlsOldOl = page()->getNode($newOl->data["ols"][ $parent->language ]);
						if ($newOlsOldOl) {
							$newOlsOldOl->data->ols->{$parent->language} = 0;
							page()->setNode(array(
								"id"   => $newOlsOldOl->id,
								"data" => $newOlsOldOl->data
							));
						}
					}
					$newOl->data["ols"][ $parent->language ] = $x;
					page()->setNode(array(
						"id"   => $newOl->id,
						"data" => $newOl->data
					));
				}
			}
			page()->lastNodeUpdated = $x;
			FS()->unregisterMedia($x, "content");
			FS()->registerMedia((string)$settings["content"], $x, "content");
		}

		Settings()->set("sitemap.modtimes." . $node->parent, date("c"));
		Settings()->set("sitemap.modtimes." . $node->id, date("c"));
		$node = page()->getNode(array(
			"filter"        => array(
				"id" => page()->reqParams[0]
			),
			"returnResults" => "first"
		));
		$_SESSION["post_success"] = "Lapa ir saglabāta.";
		page()->cache->purge("staticPageContent", $node->id);
		header("Location: {$_SERVER["HTTP_REFERER"]}");
		exit;
	}
	if ($node) {
		$node->content = phpQuery::newDocument($node->content);
		$galleries = pq(".gallery[data-id]");
		foreach ($galleries as $gallery) {
			pq($gallery)->replaceWith('<p><span class="gallery mceNonEditable" data-id="' . pq($gallery)->attr("data-id") . '">' . pq($gallery)->html() . '</span></p>');
		}
	}
	Page()->filter("content_for_edit", $node->content);

	page()->header();
?>
	<form class="addbody new" action="<?php echo page()->fullRequestUri ?>" method="post" lang="<?php print($node->language); ?>">
		<input type="hidden" name="referer" value="<?php echo $_POST["referer"] ? $_POST["referer"] : $_SERVER["HTTP_REFERER"] ?>"/>
		<header>
			<a href="<?php echo $_POST["referer"] ? $_POST["referer"] : $_SERVER["HTTP_REFERER"] ?>" class="btn btn-lg btn-primary pull-left btn-back">Atpakaļ</a>
			<h1 class="icon page"><?php echo $node->title ?></h1>
		</header>

		<div class="col-content">
			<input type="text" class="title" name="title" id="pagetitle" placeholder="Lapas nosaukums" value="<?php echo htmlspecialchars($node->data->headline ? $node->data->headline : $node->title) ?>">
			<?php if ($_SESSION["post_success"]) { ?>
				<div class="alert alert-success">
					<p class="info">Lapa ir saglabāta!</p>
				</div>
				<?php unset($_SESSION["post_success"]);
			} ?>
			<section id="general">
				<h1>Saturs</h1>

				<?php page()->trigger("static_page_prepend_conetnt"); ?>
				<fieldset>
					<label for="editor">Saturs:</label>
					<textarea class="bigger tinymce_big" id="editor" name="body"><?php echo htmlspecialchars($node->content) ?></textarea>
				</fieldset>
				<?php page()->trigger("static_page_append_conetnt"); ?>
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
										<li><a href="#" class="file-select-button">Izvēlēties failu no datubāzes</a>
										</li>
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
			<aside class="rightbar">
				<section id="settings">
					<h1>&nbsp;</h1>
					<div class="content">
						<fieldset>
							<?php foreach (page()->languages as $language) {
								if ($language == $node->language) continue; ?>
								<div class="form-group jui">
									<label for="language-<?php print($language); ?>"><?php print(page()->language_labels[ $language ]); ?>:</label>
									<select data-language="<?php print($language); ?>" id="language-<?php print($language); ?>" class="form-control ols" name="ols[<?php print($language); ?>]">
										<?php if ($node->data->ols->{$language}) {
											$ol = page()->getNode($node->data->ols->{$language});
											?>
											<option value="<?php print($ol->id); ?>"><?php print($ol->title); ?></option>
										<?php } ?>
									</select>
								</div>
							<?php } ?>
						</fieldset>
					</div>
					<?php page()->trigger("static_page_sidebar"); ?>
					<p class="form-actions">
						<a href="<?php echo $_POST["referer"] ? $_POST["referer"] : $_SERVER["HTTP_REFERER"] ?>" class="btn btn-default">Atcelt</a>
						<button type="submit" class="btn btn-success pull-right">Saglabāt</button>
					</p>
				</section>
			</aside>
		</div>

		<?php page()->trigger("static_page_after_general"); ?>
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
		$(function() {
			ImageUploaderSingle("#upload-cover", function(response, uploader) {
				$("#uploaded-cover-img").attr({src: Settings.Host + response.file}).show().siblings(".delete2").show();
				$("#uploaded-cover-input").val(response.file);
			}, "resize", {resize: "1250x625"});
			$("#uploaded-cover-img").siblings(".delete2").on("click", function(e) {
				e.preventDefault();
				var that = this;
				cmsConfirm("Tiešām vēlies noņemt šo attēlu?", function(yes) {
					if (yes) {
						$(that).hide();
						$(that).siblings('img').attr({src: "about:blank"}).hide();
						$(that).parent().siblings('input').val("");
					}
				});
			});
			$('.ols').each(function() {
				var that = this;
				$(this).selectize({
					create          : false,
					createOnBlur    : false,
					allowEmptyOption: false,
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
								ol: query,
								l : $(that).data("language")
							},
							error   : function() {
								callback();
							},
							success : function(data) {
								callback(data);
							}
						});
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
		.img-container {
			position: relative;
		}

		.img-container .delete2 {
			background: white;
			width: auto;
			height: auto;
			display: inline-block;
			position: absolute;
			top: 0;
			right: 0;
			color: black;
			line-height: 0;
			font-size: 0;
		}

		.img-container .delete2 span {
			font-size: 24px;
		}

		.img-container .delete2:hover {
			color: red;
		}
	</style>
<?php page()->footer(); ?>