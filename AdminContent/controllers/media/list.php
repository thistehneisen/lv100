<?php
	if (!ActiveUser()->can(Page()->controller, "pārvaldīt")) {
		Page()->accessDenied();
	}


	Page()->header();
	Page()->addStyle("structure.css");
?>
	<nav class="sidebar">
		<ul class="sections">
			<li>
				<a class="home<?php echo !in_array(Page()->action, Page()->mediaCategories) ? " active" : "" ?>" href="<?php echo Page()->adminHost ?><?= Page()->controller ?>/">{{Media: All files}}</a>
			</li>
			<?php foreach (Page()->mediaCategories as $cat) { ?>
				<li>
					<a class="<?php echo $cat ?><?php echo Page()->action == $cat ? " active" : "" ?>" href="<?php echo Page()->adminHost ?><?= Page()->controller ?>/<?php echo $cat ?>"><?php echo Page()->t("{{Media: {$cat} category}}") ?></a>
				</li>
			<?php } ?>
		</ul>
	</nav>
<?php if ($_SESSION['post_response']) { ?>
	<section class="infotip <?= $_SESSION['post_response'][1] ?> <?= $_SESSION['post_response'][2] ?> icon">
		<p><?php echo $_SESSION['post_response'][0] ?></p>
	</section>
	<?php unset($_SESSION['post_response']);
} ?><?php
	$cat = !in_array(Page()->action, Page()->mediaCategories) ? "all" : Page()->action;
?>
	<section class="block" id="node-list">
		<h1 class="icon hierarchy">{{Media: Headline}}</h1>
		<div class="page-structure media" data-settings="<?php echo htmlspecialchars(json_encode(array(
			"aHost" => Page()->aHost,
			"host"  => Page()->host
		))) ?>">
			<header>
				<h1 class="<?php echo $cat ?>"><?php echo(!in_array($cat, Page()->mediaCategories) ? Page()->t("{{Media: All Files}}") : Page()->t("{{Media: {$cat} category}}")) ?></h1>
			</header>
			<?php
				if ($cat != "all") {
					$where = " AND `type`='" . DataBase()->escape($cat) . "' ";
				} else $where = "";
				DataBase()->countResults = true;
				$perPage = 15;
				$mediaFiles = DataBase()->getRows("SELECT * FROM %s WHERE `original`=0 {$where}ORDER BY `created` DESC LIMIT %d,%d", DataBase()->media, Page()->pageCurrent * $perPage, $perPage);
				$pages = ceil(DataBase()->resultsFound / $perPage);
				foreach ($mediaFiles as $key => $mediaFile) {
					foreach ($mediaFile as $k => $v) {
						if (is_numeric($v)) $mediaFile[ $k ] = (int)$v;
					}
					if ($mediaFile["type"] == "photo") {
						$thumb = Page()->getThumb(Page()->path . $mediaFile["filepath"], 128, 109);
					} else $thumb = Page()->host . "Library/Assets/thumb-general-128x109.png";
					?>
					<article data-opts="<?php echo htmlspecialchars(json_encode($mediaFile)) ?>" class="<?= $mediaFile["type"] ?>">
						<?php if ($mediaFile["type"] != "photo") { ?>
							<span class="ext"><?= $mediaFile["ext"] ?></span>
						<?php } ?>
						<img src="<?php echo $thumb ?>"/>
						<h3><?php echo $mediaFile["filename"] ?></h3>
						<a class="link" href="#" title="{{{Link}}}">{{Link}}</a>
						<?php if ($mediaFile["type"] == "photo" && in_array($mediaFile["ext"], array("jpg", "jpeg", "png"))) { ?>
							<a class="edit" href="#" title="{{{Edit}}}">{{Edit}}</a><?php } ?>
						<a href="<?php print(Page()->host); ?>media.upload/?delete=<?php print($mediaFile["id"]); ?>&session=<?php print(session_id()); ?>" title="{{{Delete}}}" data-confirm="{{{Are you sure want to delete this unit?}}}">{{Delete}}</a>
					</article>
					<?php
				}
			?>
			<footer>
				<nav class="pagger" style="float: left;"><?php Page()->paging(array(
						"echo"             => true,
						"pages"            => $pages,
						"delta"            => 3,
						"dontShowInactive" => true
					)); ?></nav>
				<a href="<?php echo Page()->adminHost . Page()->controller ?>/upload/" class="addbutton">{{Media: Upload file}}</a>
			</footer>
		</div>
	</section>
	<script type="text/javascript">
		function multi_upl(button/*Džeikverī Obdžekt*/, callback/*Funkšen On Suksesss*/) {
			if (typeof window.uplinc == "undefined") {
				window.uplinc = 0;
			}
			else {
				uplinc++;
			}

			button[0].setAttribute("id", "uplb_" + uplinc);
			button.parent()[0].setAttribute("id", "uplc_" + uplinc);

			var uploader = new plupload.Uploader({
				runtimes           : 'html5,flash,silverlight',
				browse_button      : "uplb_" + uplinc,
				container          : "uplc_" + uplinc,
				max_file_size      : '200mb',
				multi_selection    : true,
				url                : Settings.Host + 'media.upload/?type=file&session=' + Settings.session_id,
				flash_swf_url      : '<?php echo Page()->bHost?>js/plupload/plupload.flash.swf',
				silverlight_xap_url: '<?php echo Page()->bHost?>js/plupload/plupload.silverlight.xap'
			});
			if ($("#uplb_" + uplinc).length) {
				uploader.init();
			}
			uploader.bind('FilesAdded', function(up, files) {
				$.modal({content: '<span class="loading">{{Uploading file}} <span id="files-c">0<\/span>\/<span id="files-t">0<\/span> (<span id="upload-progress"><\/span>)...<\/span>'});

				$("#files-t").html(up.files.length);

				up.refresh();
				uploader.start();
			});
			uploader.bind('UploadProgress', function(up, file) {
				if (up.total.uploaded < up.files.length) {
					$("#files-c").html(parseInt(up.total.uploaded) + 1);
				}
				$('#upload-progress').html(file.percent + '%');
			});
			uploader.bind('UploadComplete', function(up, files) {
				$.modal("destroy");
				callback && callback();
			});
		}
		$(".page-structure article:nth-of-type(5n+5)").addClass("last");
		$(".page-structure article").each(function() {
			var h = $(this).children("h3");
			var t = h.text();
			$(this).attr("title", t);
			for (var i = 0; i < t.length; i++) {
				if (h.height() > 17) {
					var n = t.substring(0, (t.length - i) / 2) + '...' + t.substring(t.length - (t.length - i) / 2);
					h.text(n);
				}
				else {
					break;
				}
			}
		});
		$(function() {
			var host = $(".media").data().settings.host;
			multi_upl($(".addbutton"), function() {
				document.location.href =<?php echo json_encode(Page()->aHost . Page()->controller . "/list/all/")?>;
			});
			$(".media a.edit").on("click", function(e) {
				e.preventDefault();
				var article    = $(this).parent(),
				    imgOptions = article.data().opts;
				window.test    = new imgEditTool(imgOptions).open();
			});
			$(".media a.link").on("click", function(e) {
				e.preventDefault();
				var article    = $(this).parent(),
				    imgOptions = article.data().opts;
				$($.parseHTML("<div><input type=\"text\" value=\"" + host + imgOptions.filepath + "\" onfocus=\"this.select();\"\/><\/div>"))
					.dialog({
						title      : imgOptions.filename,
						position   : ["center", e.clientY],
						width      : 600,
						minHeight  : 0,
						dialogClass: "fixed-dialog",
						modal      : true,
						resizable  : false,
						draggable  : false
					});
			});
		});
	</script>
	<script type="text/javascript">
		$(function() {
			$(document).on("click", "ul.tree a.basicedit", function(e) {
				e.preventDefault();
				e.stopPropagation();
				$($.parseHTML("<div/>")).append($.parseHTML('<span class="loading"></span>'))
				                        .attr({id: "basic_settings"}).dialog({
					minWidth : 700,
					maxHeight: 500,
					position : ["auto", 190],
					title    : "{{Settings}}",
					buttons  : [
						{
							text : "{{Cancel}}",
							click: function() {
								$(this).dialog("close");
							}
						}
					],
					modal    : true,
					close    : function() {
						$(this).parent().remove();
					}
				}).load(this.getAttribute("href"));
				return false;
			});
			fixSwitch();
		});
		function fixSwitch() {
			var inev = true;
			$(document).on("click", '.page_status a.selector', function() {
				var input = $(this).next();
				if (inev) {
					var el = this;
					cmsConfirm(<?php echo json_encode(Page()->t("{{Structure: Confirm status change}}"))?>, function(answer) {
						if (answer) {
							setTimeout(function() {
								$.post('<?php echo Page()->fullRequestUri?>', {
									changestate: input.val(),
									state      : input.is(':checked') ? '1' : '0'
								});
							}, 50);
						}
						else {
							inev = false;
							$(el).click();
							inev = true;
						}
					});
				}
			});
		}
	</script>
<?php Page()->footer(); ?>