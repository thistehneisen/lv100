<?php
	if (!ActiveUser()->can(Page()->controller,"pārvaldīt")) {
		Page()->accessDenied();
	}

	if (Page()->reqParams[0] == "check") {
		require 'phpquery.class.php';
		$source = $_POST["video_source"];
		if (!filter_var($source, FILTER_VALIDATE_URL, FILTER_NULL_ON_FAILURE)) {
			$pq = phpQuery::newDocumentHTML($source);
			if (pq("iframe")->length) {
				$pq = @phpQuery::newDocumentFileHTML(pq("iframe")->attr("src"));
				$source = pq('link[rel="canonical"]')->attr("href");
			}
		}

		if ($source && filter_var($source, FILTER_VALIDATE_URL, FILTER_NULL_ON_FAILURE)) {
			$pq = @phpQuery::newDocumentFileHTML($source);
			$oembedUrl = $pq->find('link[type="application/json+oembed"]')->attr("href");
			if ($oembedUrl) {
				$embedData = json_decode(@file_get_contents($oembedUrl), true);
				if ($embedData && $embedData["html"]) {
					$pq = phpQuery::newDocumentHTML($embedData["html"]);
					if (pq("iframe")->length) {
						?>
						<div class="embed-responsive embed-responsive-16by9">
							<iframe class="embed-responsive-item" src="<?php print(pq("iframe")->attr("src")); ?>"></iframe>
						</div>
						<?php
						exit;
					}
				}
			}

		} ?>
		<div class="alert alert-warning">
			<p>Neizdevās iegūt video. Pārbaudi adresi un mēģini vēlreiz!</p>
		</div>
		<?php
		exit;
	}

	if ($_POST["video_source"]) {
		require 'phpquery.class.php';
		$source = $_POST["video_source"];
		$return["status"] = "not_ok";
		$return["info"] = "Neizdevās iegūt video.";
		if (!filter_var($source, FILTER_VALIDATE_URL, FILTER_NULL_ON_FAILURE)) {
			$pq = phpQuery::newDocumentHTML($source);
			if (pq("iframe")->length) {
				$pq = @phpQuery::newDocumentFileHTML(pq("iframe")->attr("src"));
				$source = pq('link[rel="canonical"]')->attr("href");
			}
		}

		if ($source && filter_var($source, FILTER_VALIDATE_URL, FILTER_NULL_ON_FAILURE)) {
			$pq = @phpQuery::newDocumentFileHTML($source);
			$oembedUrl = $pq->find('link[type="application/json+oembed"]')->attr("href");
			if ($oembedUrl) {
				$embedData = json_decode(@file_get_contents($oembedUrl), true);
				if ($embedData && $embedData["html"]) {
					$pq = phpQuery::newDocumentHTML($embedData["html"]);
					if (pq("iframe")->length) {
						$thumb = str_replace("hqdefault", "maxresdefault", $embedData["thumbnail_url"]);
						$max    = get_headers($thumb);
						if (substr($max[0], 9, 3) === '404') {
							$thumb = $embedData["thumbnail_url"];
						}
						$src = pq("iframe")->attr("src");
						$src_params = parse_url($src);
						parse_str($src_params["query"], $query_params);
						$query_params["autoplay"] = 1;
						$src_params["query"] = http_build_query($query_params, null, "&");
						$src = http_build_url("", $src_params);
						$return["status"] = "ok";
						$return["thumb"] = Page()->getThumb($thumb, 400, 300, false, false, true);
						DataBase()->insert("gallery", array(
							"added"   => date("Y-m-d H:i:s"),
							"parent"  => Page()->reqParams[0],
							"type"    => "video",
							"path"    => $thumb,
							"caption" => "",
							"source"  => $src
						));
						$return["id"] = DataBase()->insertid;
					}
				}
			}

		}

		echo json_encode($return);
		exit;
	}
?>

	<form class="form" action="<?php Page()->e(Page()->getURL(), 1); ?>" method="post" id="editForm" style="overflow-x: hidden;">
		<div class="alert alert-info">
			<p>Pievienot video var no youtube.com vai vimeo.com.
				<br>Lauciņā var ievadīt video adresi vai iframe kodu un nospiest "Pārbaudīt".<br>Ja parādās vēlamais video, var nospiest "Pievienot".
			</p>
		</div>
		<div class="form-group row">
			<div class="col-xs-12">
				<label for="video_source" class="control-label">Video avots:</label>
				<textarea id="video_source" name="video_source" class="form-control" style="height: 50px;"></textarea>
			</div>
		</div>
	</form>
<script type="text/javascript">
	$("#addVideoDialog").dialog("option", "buttons", [
		{
			text: "Atcelt",
			"class": "btn btn-default",
			click: function () {
				$(this).dialog("close");
			}
		}, {
			text: "Pārbaudīt",
			"class": "btn btn-default",
			click: function () {
				$("<div\/>")
					.dialog({
						dialogClass: "tw-bs",
						modal: true,
						draggable: false,
						resizable: false,
						width: 600,
						maxHeight: "80%",
						open: function () {
							var dialog = this;
							$.post("<?php print(Page()->aHost.Page()->controller."/addvideo/check/"); ?>", {video_source: $("#video_source").val()}, function (response) {
								$(dialog).html(response).dialog("option", "position", "center center");
							});
						},
						close: function () {
							$(this).dialog("destroy").remove();
						}
					});
			}
		}, {
			text: "Pievienot",
			"class": "btn btn-primary",
			click: function () {
				$(this).
					find("form").
					ajaxSubmit({

						dataType: "json",
						success: function (response) {
							if (response.status == "ok") {
								$(".list").append('<div class="col-xs-3 video" data-id="' + response.id + '"><div class="thumbnail"><div class="panel panel-controls2"><a href="#" class="move" info="Klikšķini šeit un neatlaižot pārvieto video…"><span class="glyphicon glyphicon-move"><\/span><\/a><a href="#" data-href="<?php print(Page()->aHost . Page()->controller . "/editphoto/"); ?>' + response.id + '/?delete=1" class="delete ajax" info="Klikšķini šeit, lai noņemtu šo video…"><span class="glyphicon glyphicon-remove"><\/span><\/a><\/div><div class="wrap"><span class="helper"><\/span><img src="' + response.thumb + '"><\/div><\/div><\/div>');
								makeThingsSortable();
								saveSorting.apply($(".sortable").get(0));
								$("#addVideoDialog").dialog("close");
							}
						}
					});
			}
		}
	]);
</script>