<?php
	if (!ActiveUser()->can(Page()->controller, "valodas")) {
		Page()->accessDenied();
	}

	$tFile = Page()->reqParams[0] ? Page()->reqParams[0] : 'front';

	$texts_added = 0;

	$fe_controllers = array();

	if ($tFile == "front") {
		$fe_controllers = glob(Page()->path . "SiteContent/*.php");
		$fe_controllers = array_merge((array)$fe_controllers, (array)glob(Page()->path . "CronJobs/*.php"));
		$fe_controllers = array_merge((array)$fe_controllers, (array)glob(Page()->path . "SiteContent/controllers/*/*.php"));
		$fe_controllers = array_merge((array)$fe_controllers, (array)glob(Page()->path . "SiteContent/widgets/*.php"));
		$fe_controllers = array_merge((array)$fe_controllers, (array)glob(Page()->path . "SiteContent/callbacks/*.php"));
	} else if ($tFile == "cms") {
		$fe_controllers = glob_recursive(Page()->path . "AdminContent", "*.php", 0, 4);
		$fe_controllers = array_merge((array)$fe_controllers, glob_recursive(Page()->path . "AdminContent", "*.js", 0, 4));
	}
	$have_texts = array();
	foreach ((array)$fe_controllers as $controller) {
		if (!$controller) continue;
		$file = file_get_contents($controller);
		if (preg_match_all("#(\{\{[^\}\{]+\}\})#m", $file, $matches)) {
			if (count($matches[0])) {
				foreach ($matches[0] as $match) {
					foreach (Page()->languages as $lang) {
						$check = DataBase()->getRow("SELECT `id` FROM %s WHERE `language`='%s' AND `text`='%s' AND `file`='%s'",
							DataBase()->table("translate"), $lang, $match, $tFile);
						if (!$check) {
							DataBase()->insert("translate", array(
								"file"      => $tFile,
								"text"      => $match,
								"translate" => "",
								"language"  => $lang,
								"force"     => 0
							), true);
							$tid = DataBase()->insertid;
							if ($tid) {
								$have_texts[] = DataBase()->insertid;
								$texts_added++;
							}
						} else {
							$have_texts[] = $check["id"];
						}
					}
				}
			}
		}
	}
	if ($have_texts && is_array($have_texts) && count($have_texts)) {
		DataBase()->queryf("DELETE FROM %s WHERE `id` NOT IN (%s) AND `file`='%s' AND `force`='0'", DataBase()->translate, join(",", $have_texts), $tFile);
	} else DataBase()->queryf("DELETE FROM %s WHERE `file`='%s' AND `force`='0'", DataBase()->translate, $tFile);

	$post_success = $_SESSION['post_success'];
	unset($_SESSION['post_success']);
?>


<section class="block">
	<input type="text" id="search_trigger" class="search form-control" placeholder="{{Keep typing to get results}}" value="<?php echo $_GET['q'] ?>" />
	<a href="<?php echo Page()->adminHost ?>cpanel/edit_translate/?file=<?php echo $tFile; ?>" class="btn btn-success btn-add pull-right ajax" id="add">{{Add translate}}</a>
	<a data-file="<?= $tFile ?>" class="btn btn-default ajaxExport icon excel">{{Export}}</a>
	<a href="javascript:;" class="btn btn-default icon excel" id="import">{{Import}}</a>
	<div id="settings">
		<?php
			$_GET['q'] = $_GET['q'] ?: $_SESSION['last_translate_filter'];
			$_SESSION['last_translate_filter'] = $_GET['q'];
			if (!empty($_GET['q'])) {
				$l = DataBase()->getRows("SELECT * FROM %1\$s WHERE (`text` LIKE '%%%2\$s%%' OR `translate` LIKE '%%%2\$s%%') AND `file`='%3\$s' ORDER BY `file`,`text`", DataBase()->translate, $_GET['q'], $tFile);
			} else {
				$l = DataBase()->getRows("SELECT * FROM %1\$s WHERE `file`='%2\$s' ORDER BY `file`,`text`", DataBase()->translate, $tFile);
			}

			$ln = array();
			foreach ($l as $t) {
				$ln[ $t["text"] ][ $t["language"] ] = array($t["translate"], $t["id"]);
			}
		?>
		<ul class="contentlist compact" id="records">
			<?php foreach ($ln as $x => $t) {
				$_t = reset($t); ?>
				<li>
					<div class="controls">
						<a href="<?php echo Page()->adminHost ?>cpanel/edit_translate/<?php echo end($_t) ?>" class="btn btn-default ajax">{{Edit}}</a>
					</div>
					<div class="content" id="trns-<?php echo end($_t) ?>">
						<h1><?php echo str_replace(array("{", "}"), array("", ""), $x) ?></h1>
						<?php foreach ($t as $lng => $y) { ?>
							<p><b>[<?php echo $lng ?>]</b> <?php echo htmlspecialchars($y[0]) ?></p>
						<?php } ?>
					</div>
				</li>
			<?php } ?>
		</ul>
	</div>
</section>
<script type="text/javascript">
	var stimer;
	$(function () {
		$('#search_trigger').bind('keyup change paste', function (e) {
			if ($(this).val().length >= 3 || ($(this).val().length == 0 && e.which == 8)) {
				clearTimeout(stimer);
				stimer = setTimeout(function () {
					$.get('<?php echo Page()->adminHost."cpanel/list_translates/".Page()->reqParams[0]."/"?>', {q: $('#search_trigger').val()}, function (htmlText) {
						$('#records').html($(htmlText).find('#records').html());
					});
				}, 500);
			}
		});
	});
</script>
<script>
	$(function () {
		$(document).on('click', '.ajax', ajaxclickfunc = function (e) {
			e.preventDefault();
			$.modal({
				content: this.href,
				appendClose: "{{Cancel}}",
				buttons: [
					{
						label: "{{Save}}",
						callback: function () {
							$("#modal").find("form").submit();
							$.modal("destroy");
						},
						className: "btn-success"
					}
				]
			});
		});
		$(document).on('click', '.ajaxExport', function (e) {
			e.preventDefault();
			var url = '<?php echo Page()->adminHost?>cpanel/export';
			var file = $(this).data('file');
			$(this).text(<?=json_encode(Page()->t("{{Loading...}}"))?>);
			$.post(url, {
				ajax: "ajax",
				file: file
			}, function (r) {
				var jsonrpc = $.parseJSON(r);
				var dwn = jsonrpc.file;
				window.location.href = '<?php echo Page()->host?>Uploads/Translates/' + dwn;
				$('.ajaxExport').text(<?=json_encode(Page()->t("{{Export}}"))?>);
			});


		});
	});

	var uploader = new plupload.Uploader({
		runtimes: 'html5,flash,silverlight',
		browse_button: 'import',
		max_file_size: '20mb',
		multi_selection: false,
		url: <?=json_encode(Page()->aHost."media/upload_file")?>,
		flash_swf_url: '<?=Page()->bHost?>js/plupload/plupload.flash.swf',
		silverlight_xap_url: '<?=Page()->bHost?>js/plupload/plupload.silverlight.xap',
		filters: [{title: "Files", extensions: "xls"}]
	});
	if ($("#import").length) uploader.init();
	uploader.bind('FilesAdded', function (up, files) {
		$.modal({content: '<span class="loading">Importing file (<span id="upload-progress"><\/span>)...<\/span>'});

		up.refresh(); // Reposition Flash/Silverlight
		uploader.start();
	});
	uploader.bind('UploadProgress', function (up, file) {
		$('#upload-progress').html(file.percent + '%');
	});
	uploader.bind('FileUploaded', function (up, file, response) {
		var jsonrpc = $.parseJSON(response.response);
		if (jsonrpc.error) {
			$.modal({content: jsonrpc.error.message, appendClose: "OK"});
			return;
		}
		$.modal("destroy");
		$.post('<?php echo Page()->adminHost?>cpanel/import/<?=$tFile?>/', {
			ajax: "ajax",
			file: jsonrpc.file
		}, function (r) {
			var jsonrpc = $.parseJSON(r);
			var resp = jsonrpc.response;
			$.modal({content: resp});
			setTimeout(function () {
				window.location.href = '<?php echo Page()->adminHost?>cpanel/translate/<?=$tFile?>/';
			}, 3000);

		});


	});
</script>