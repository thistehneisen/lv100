<?php
	if (!ActiveUser()->can(Page()->controller, "1. lapas saturs")) {
		Page()->accessDenied();
	}

	function print_struct($parent_id, &$that, $deep = 1, $sid = 0) {
		$nodes = Page()->getNode(array(
			"filter" => array(
				"parent"     => $parent_id,
				"created_by" => array("core", "manual"),
				"type"       => array(1,2)
			),
			"order"  => array(
				"title" => "asc"
			)));
		if (is_array($nodes)) {
			foreach ($nodes as $node) {
				if ($node->id != $sid) echo '<li data-value="' . htmlspecialchars($node->address) . '" data-deep="' . $deep . '">' . $node->title . '</li>';
				print_struct($node->id, $that, $deep + 1, $sid);
			}
		}
	}

	if (Page()->method == "POST") {

		$fpdata = array();
		foreach ($_POST as $block => $blockdata) {
			foreach ($blockdata as $key => $keydata) {
				foreach ($keydata as $language => $value) {
					$fpdata[ $language ][ $block ][ $key ] = $value;
				}
			}
		}

		foreach ($fpdata as $lng => $data) {
			Settings()->set("first_page_data", $data, $lng);
		}

		header("Location: {$_SERVER["HTTP_REFERER"]}");
		exit;
	}

	Page()->addBreadcrumb("Sadaļas", Page()->aHost . Page()->controller);
	Page()->addBreadcrumb("1. lapa", Page()->aHost . Page()->controller . "/" . Page()->action . "/");

	Page()->header();
?>

	<form class="addbody new" method="post" id="fp" action="<?php echo Page()->fullRequestUri ?>">
		<button type="submit" class="btn btn-success pull-right" id="save">Saglabāt</button>
		<header>
			<a href="<?php echo Page()->adminHost . Page()->controller ?>" class="btn btn-primary pull-left btn-back" onclick="">Atpakaļ</a>
			<h1 class="pull-left"><span class="glyphicon glyphicon-list"></span> 1. lapa</h1>
		</header>

		<ul class="nav nav-tabs" role="tablist">
			<?php foreach (Page()->roots as $k => $root) { ?>

				<li role="presentation" class="<?php print($k == 0 ? 'active' : ''); ?>">
					<a href="#ln-<?php print($root->language); ?>" aria-controls="home" role="tab" data-toggle="tab"><?php print(Page()->language_labels[ $root->language ]); ?></a>
				</li>
			<?php } ?>
		</ul>
		<div class="tab-content">
			<?php foreach (Page()->roots as $k => $root) {
				$fpdata = Settings()->get("first_page_data", $root->language);
				$newsPages = Page()->getNode(array(
					"filter" => array(
						"controller" => "news",
						"created_by" => array("core", "manual")
					),
					"order"  => array("title" => "ASC")
				));

				?>
				<div class="panel panel-default tab-pane <?php print($k == 0 ? 'active' : ''); ?>" id="ln-<?php print($root->language); ?>">
					<div class="panel-heading">
						<h3 class="panel-title"><?php print(Page()->language_labels[ $root->language ]); ?></h3>
					</div>
					<div class="panel-body">
						<ul class="nav nav-tabs" role="tablist">
							<li role="presentation" class="active">
								<a href="#cb-<?php print($root->language); ?>" role="tab" data-toggle="tab">Eventa bloks</a>
							</li>

						</ul>
						<div class="tab-content">
							<div id="cb-<?php print($root->language); ?>" class="tab-pane active">
								<div class="form-group">
									<label for="cb-title-<?php print($root->language); ?>">Nosaukums:</label>
									<input id="cb-title-<?php print($root->language); ?>" type="text" name="cb[title][<?php print($root->language); ?>]" class="form-control" value="<?php Page()->e($fpdata["cb"]["title"], 1); ?>">
								</div>
								<div class="form-group">
									<label for="cb-description-<?php print($root->language); ?>">Apraksts:</label>
									<textarea id="cb-description-<?php print($root->language); ?>" name="cb[description][<?php print($root->language); ?>]"><?php Page()->e($fpdata["cb"]["description"], 1); ?></textarea>
								</div>
								<div class="form-group">
									<label for="cb-address-<?php print($root->language); ?>">Adrese:</label>
									<input id="cb-address-<?php print($root->language); ?>" type="text" name="cb[address][<?php print($root->language); ?>]" class="form-control" value="<?php Page()->e($fpdata["cb"]["address"], 1); ?>">
								</div>
								<div class="form-group row pic-ct">
									<label for="cb-picture-<?php print($root->language); ?>" class="col-xs-12">Fona attēls:</label>
									<?php
										$oImg = false;
										if ($fpdata["cb"]["picture_original"]) {
											$oImg = DataBase()->getRow("SELECT * FROM %s WHERE `filepath`='%s'", DataBase()->media, $fpdata["cb"]["picture_original"]);
										}
									?>
									<div class="col-xs-8 <?php print($oImg ? '' : 'hidden'); ?>">
										<a data-opts="<?php Page()->e(json_encode($oImg), 1); ?>" href="#" class="btn btn-xs btn-default croptool"><img src="<?php print(Page()->bHost); ?>/css/img/icons-edit.png" info="Pielāgot attēlu"></a>
										<a href="#" class="delete2 btn btn-xs btn-default"><span class="glyphicon glyphicon-remove"></span></a>
										<input type="hidden" class="pic" name="cb[picture][<?php print($root->language); ?>]" value="<?php Page()->e($fpdata["cb"]["picture"], 1); ?>">
										<input type="hidden" class="pic_original" name="cb[picture_original][<?php print($root->language); ?>]" value="<?php Page()->e($fpdata["cb"]["picture_original"], 1); ?>">
										<img src="<?php print($fpdata["cb"]["picture"] ? Page()->host . $fpdata["cb"]["picture"] : 'about:blank;'); ?>" class="pic" width="100%">
									</div>
									<div class="col-xs-4">
										<button type="button" class="btn btn-default btn-upload upl-pc" style="margin-bottom: 10px;" id="cb-picture-<?php print($root->language); ?>">Augšupielādēt</button>
										<button type="button" class="btn btn-default btn-upload upl-db">Izvēlēties no datubāzes</button>
									</div>
								</div>
							</div>
							<div id="nb-<?php print($root->language); ?>" class="tab-pane">
								<div class="form-group">
									<label for="nb-title-<?php print($root->language); ?>">Nosaukums:</label>
									<input id="nb-title-<?php print($root->language); ?>" type="text" name="nb[title][<?php print($root->language); ?>]" class="form-control" value="<?php Page()->e($fpdata["nb"]["title"], 1); ?>">
								</div>
								<div class="form-group">
									<label for="nb-node-<?php print($root->language); ?>">Ielādēt saturu no:</label>
									<select id="nb-node-<?php print($root->language); ?>" name="nb[node][<?php print($root->language); ?>]" class="form-control">
										<option value="0"></option>
										<?php foreach ($newsPages as $node) { ?>
											<option value="<?php print($node->id); ?>"<?php print($node->id == $fpdata["nb"]["node"] ? ' selected' : ''); ?>><?php print($node->title); ?></option>
										<?php } ?>
									</select>
								</div>
							</div>
							<div id="ab-<?php print($root->language); ?>" class="tab-pane">
								<div class="form-group">
									<label for="ab-title-<?php print($root->language); ?>">Nosaukums:</label>
									<input id="ab-title-<?php print($root->language); ?>" type="text" name="ab[title][<?php print($root->language); ?>]" class="form-control" value="<?php Page()->e($fpdata["ab"]["title"], 1); ?>">
								</div>
								<div class="form-group">
									<label for="ab-node-<?php print($root->language); ?>">Ielādēt saturu no:</label>
									<select id="ab-node-<?php print($root->language); ?>" name="ab[node][<?php print($root->language); ?>]" class="form-control">
										<option value="0"></option>
										<?php foreach ($newsPages as $node) { ?>
											<option value="<?php print($node->id); ?>"<?php print($node->id == $fpdata["ab"]["node"] ? ' selected' : ''); ?>><?php print($node->title); ?></option>
										<?php } ?>
									</select>
								</div>
							</div>
							<div id="mb-<?php print($root->language); ?>" class="tab-pane">
								<div class="row">
									<div class="col-xs-4">
										<div class="form-group">
											<label for="mb-title-<?php print($root->language); ?>-brivdabasmuzejs">Nosaukums:</label>
											<input type="text" class="form-control" id="mb-title-<?php print($root->language); ?>-brivdabasmuzejs" name="mb[title][<?php print($root->language); ?>][brivdabasmuzejs]" value="<?php Page()->e($fpdata["mb"]["title"]["brivdabasmuzejs"], 1); ?>">
										</div>
										<div class="form-group">
											<label for="mb-address-<?php print($root->language); ?>-brivdabasmuzejs">Adrese:</label>
											<div class="combobox">
												<input style="width: 293px;" name="mb[address][<?php print($root->language); ?>][brivdabasmuzejs]" type="text" id="mb-address-<?php print($root->language); ?>-brivdabasmuzejs" value="<?php Page()->e($fpdata["mb"]["address"]["brivdabasmuzejs"], 1); ?>"/>
												<ul>
													<?php print_struct(0, $this, 1, 0) ?>
												</ul>
											</div>
										</div>
										<div class="form-group">
											<label for="mb-description-<?php print($root->language); ?>-brivdabasmuzejs">Apraksts:</label>
											<textarea class="form-control" id="mb-description-<?php print($root->language); ?>-brivdabasmuzejs" name="mb[description][<?php print($root->language); ?>][brivdabasmuzejs]"><?php Page()->e($fpdata["mb"]["description"]["brivdabasmuzejs"], 1); ?></textarea>
										</div>
									</div>
									<div class="col-xs-4">
										<div class="form-group">
											<label for="mb-title-<?php print($root->language); ?>-veveri">Nosaukums:</label>
											<input type="text" class="form-control" id="mb-title-<?php print($root->language); ?>-veveri" name="mb[title][<?php print($root->language); ?>][veveri]" value="<?php Page()->e($fpdata["mb"]["title"]["veveri"], 1); ?>">
										</div>
										<div class="form-group">
											<label for="mb-address-<?php print($root->language); ?>-veveri">Adrese:</label>
											<div class="combobox">
												<input style="width: 293px;" name="mb[address][<?php print($root->language); ?>][veveri]" type="text" id="mb-address-<?php print($root->language); ?>-veveri" value="<?php Page()->e($fpdata["mb"]["address"]["veveri"], 1); ?>"/>
												<ul>
													<?php print_struct(0, $this, 1, 0) ?>
												</ul>
											</div>
										</div>
										<div class="form-group">
											<label for="mb-description-<?php print($root->language); ?>-veveri">Apraksts:</label>
											<textarea class="form-control" id="mb-description-<?php print($root->language); ?>-veveri" name="mb[description][<?php print($root->language); ?>][veveri]"><?php Page()->e($fpdata["mb"]["description"]["veveri"], 1); ?></textarea>
										</div>
									</div>
									<div class="col-xs-4">
										<div class="form-group">
											<label for="mb-title-<?php print($root->language); ?>-vitolnieki">Nosaukums:</label>
											<input type="text" class="form-control" id="mb-title-<?php print($root->language); ?>-vitolnieki" name="mb[title][<?php print($root->language); ?>][vitolnieki]" value="<?php Page()->e($fpdata["mb"]["title"]["vitolnieki"], 1); ?>">
										</div>
										<div class="form-group">
											<label for="mb-address-<?php print($root->language); ?>-vitolnieki">Adrese:</label>
											<div class="combobox">
												<input style="width: 293px;" name="mb[address][<?php print($root->language); ?>][vitolnieki]" type="text" id="mb-address-<?php print($root->language); ?>-vitolnieki" value="<?php Page()->e($fpdata["mb"]["address"]["vitolnieki"], 1); ?>"/>
												<ul>
													<?php print_struct(0, $this, 1, 0) ?>
												</ul>
											</div>
										</div>
										<div class="form-group">
											<label for="mb-description-<?php print($root->language); ?>-vitolnieki">Apraksts:</label>
											<textarea class="form-control" id="mb-description-<?php print($root->language); ?>-vitolnieki" name="mb[description][<?php print($root->language); ?>][vitolnieki]"><?php Page()->e($fpdata["mb"]["description"]["vitolnieki"], 1); ?></textarea>
										</div>
									</div>
								</div>
							</div>
							<div id="lb-<?php print($root->language); ?>" class="tab-pane">
								<div class="form-group">
									<label for="lb-title-<?php print($root->language); ?>">Nosaukums:</label>
									<input id="lb-title-<?php print($root->language); ?>" type="text" name="lb[title][<?php print($root->language); ?>]" class="form-control" value="<?php Page()->e($fpdata["lb"]["title"], 1); ?>">
								</div>
								<div class="form-group">
									<label for="lb-address-<?php print($root->language); ?>">Ielādēt saturu no:</label>
									<div class="combobox">
										<input style="width: 940px;" name="lb[address][<?php print($root->language); ?>]" type="text" id="lb-address-<?php print($root->language); ?>" value="<?php Page()->e($fpdata["lb"]["address"], 1); ?>"/>
										<ul>
											<?php print_struct(0, $this, 1, 0) ?>
										</ul>
									</div>
								</div>
							</div>
							<div id="qb-<?php print($root->language); ?>" class="tab-pane questions">
								<input type="hidden" class="counter" name="qb[counter][<?php print($root->language); ?>]" value="<?php Page()->e($fpdata["qb"]["counter"] ? $fpdata["qb"]["counter"] : 1, 1); ?>">
								<table class="table table-condensed table-hover">
									<thead>
										<tr>
											<th width="1"></th>
											<th>Jautājums</th>
											<th width="175">Peivienošanas laiks</th>
											<th width="120">Vērtējums / Respondenti</th>
											<th width="1"></th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ((array)$fpdata["qb"]["data"] as $data) {
												$data = json_decode($data,1);
												$score = Settings()->get("question_score_".$data["id"],$root->language);
											if (!$score) $score = array();
											?>
										<tr>
											<td class="actions">
												<input type="hidden" class="question-data" name="qb[data][<?php print($root->language); ?>][]" value="<?php print(htmlspecialchars(json_encode($data))); ?>">
												<a href="#" class="move "><span class="glyphicon glyphicon-move"></span></a>
											</td>
											<td class="question-title"><?php print($data["title"]); ?></td>
											<td class="question-time"><?php print($data["time"]); ?></td>
											<td class="question-score"><?php print(count($score) == 0 ? "N/A" : number_format(array_sum(array_map(function($n){return $n[0];},$score))/count($score),1,'.','')); ?> / <?php print(count($score)); ?></td>
											<td class="actions">
												<a href="#" class="remove btn btn-danger btn-xs">Dzēst</a>
												<a href="#" class="edit btn btn-default btn-xs">Labot</a>
											</td>
										</tr>
										<?php } ?>
									</tbody>
								</table>
								<div>
									<a href="#" class="addbutton add-new-question" data-language="<?php print($root->language); ?>">Pievienot jautājumu</a>
								</div>
							</div>
						</div>
					</div>
				</div>
			<?php } ?>
		</div>

	</form>
	<div class="modal fade" tabindex="-1" role="dialog" id="question-details">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span></button>
					<h4 class="modal-title">Jautājuma uzstādījumi</h4>
				</div>
				<div class="modal-body" style="max-height:500px;overflow:auto;">
					<div class="form-grpup">
						<label for="question-title">Jautājums:</label>
						<input type="text" id="question-title" class="form-control input-lg" value="">
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal">Aizvērt</button>
					<button type="button" class="btn btn-success do-confirm" id="question-save">Gatavs</button>
				</div>
			</div>
		</div>
	</div>

	<script type="text/javascript">
		function twoDigits(d) {
			if (0 <= d && d < 10) {
				return "0" + d.toString();
			}
			if (-10 < d && d < 0) {
				return "-0" + (-1 * d).toString();
			}
			return d.toString();
		}
		Date.prototype.toMysqlFormat = function() {
			return this.getUTCFullYear() + "-" + twoDigits(1 + this.getUTCMonth()) + "-" + twoDigits(this.getUTCDate()) + " " + twoDigits(this.getUTCHours()) + ":" + twoDigits(this.getUTCMinutes()) + ":" + twoDigits(this.getUTCSeconds());
		};
		$(function() {

			function save_question() {
				// data.data = {id, title, time}
				currentQuestion.data.title = $("#question-title").val();
				if ($(currentQuestion.element).is(".edit")) {
					var cq = $(currentQuestion.element).parents("tr");
					cq.find(".question-data").val($.toJSON(currentQuestion.data));
					cq.find(".question-title").text(currentQuestion.data.title);
				}
				else {
					$(currentQuestion.element).parents(".questions").find("table tbody").append($("<tr><\/tr>")
						.append($("<td><\/td>").addClass("actions")
						                       .append($("<input\/>").val($.toJSON(currentQuestion.data))
						                                             .attr("type", "hidden")
						                                             .attr("name", "qb[data][" + currentQuestion.language + "][]")
						                                             .addClass("question-data"))
						                       .append('<a href="#" class="move "><span class="glyphicon glyphicon-move"><\/span><\/a>'))
						.append($("<td><\/td>").addClass("question-title").text(currentQuestion.data.title))
						.append($("<td><\/td>").addClass("question-time").text(currentQuestion.data.time))
						.append($("<td><\/td>").addClass("question-score").text("N/A / 0"))
						.append($("<td><\/td>").addClass("actions")
						                       .append('<a href="#" class="remove btn btn-danger btn-xs">Dzēst<\/a>')
						                       .append('\t<a href="#" class="edit btn btn-default btn-xs">Labot<\/a>')));
				}
			}

			function edit_question(data) {
				if (typeof data == "undefined") {
					var counter = parseInt($(this).parents(".questions").find(".counter").val());
					$(this).parents(".questions").find(".counter").val(counter + 1);
					data = {id: counter, title: "", time: (new Date()).toMysqlFormat()};
				}
				$("#question-details").modal("show");
				window.currentQuestion = {
					data    : data,
					language: $(this).data("language"),
					element : this
				};
				$("#question-title").val(currentQuestion.data.title);
			}

			$(".add-new-question").on("click", function(e) {
				e.preventDefault();
				edit_question.call(this);
			});
			$(document).on("click", ".questions .edit", function(e) {
				e.preventDefault();
				edit_question.call(this,$.parseJSON($(this).parents("tr").find(".question-data").val()));
			});
			$(document).on("click", ".questions .remove", function(e) {
				e.preventDefault(); var that = this;
				cmsConfirm("Tiešām vēlies noņemt šo jautājumu?",function(yes){
					if (yes) {
						$(that).parents("tr").remove();
					}
				});
			});
			$("#question-save").on("click", function(e) {
				e.preventDefault();
				save_question();
				$("#question-details").modal("hide");
			});

			ImageUploaderSingle(".upl-pc", function(response) {
				var $btn   = $(this);
				var $picCt = $btn.parents(".pic-ct");
				$picCt.find("img.pic").attr("src", Settings.Host + response.file).parent().removeClass("hidden");
				$picCt.find("input.pic").val(response.file);
				$picCt.find("input.pic_original").val(response.opts.filepath);
				$picCt.find(".croptool").data("opts", response.opts).click();
			}, "crop", {crop: "1580x760", keep: 1});
			$(".upl-db").on("click", function(e) {
				e.preventDefault();
				var $btn   = $(this);
				var $picCt = $btn.parents(".pic-ct");
				selectFile(function(file, data) {
					$picCt.find("img.pic").attr("src", Settings.Host + file).parent().removeClass("hidden");
					$picCt.find("input.pic").val(file);
					$picCt.find("input.pic_original").val(file);
					var opts = {
						filepath: file,
						basename: data.filename,
						width   : data.width,
						height  : data.height,
						type    : "photo"
					};
					$picCt.find(".croptool").data("opts", opts).click();
				}, "photo");
			});
			$(".pic-ct").find(".croptool").on("click", function(e) {
				var $btn   = $(this);
				var $picCt = $btn.parents(".pic-ct");
				e.preventDefault();
				var imgEditor = new imgEditTool($(this).data("opts")).open(function() {
					this.cropToolInit({
						desiredSize: {w: 1580, h: 760}, cancel: function() {
							this.close();
						}, save    : function(data) {
							that = this;
							$.getJSON(<?php Page()->e(Page()->host . "media.upload/?raw_crop=1", 3)?>+'&session=' + Settings.session_id, {
								i: data.i,
								x: data.x,
								y: data.y,
								w: data.w,
								h: data.h,
								r: data.r,
								m: {w: 1580, h: 760}
							}, function(response) {
								that.close();
								$picCt.find("img.pic").attr("src", Settings.Host + response.fileThumb).parent()
								      .removeClass("hidden");
								$picCt.find("input.pic").val(response.fileThumb);
							});
						}
					});
				});
			});

			$(".pic-ct").find(".delete2").on("click", function(e) {
				e.preventDefault();
				var $btn   = $(this);
				var $picCt = $btn.parents(".pic-ct");
				$picCt.find("img.pic").attr("src", "about:blank").parent().addClass("hidden");
				$picCt.find("input.pic").val("");
				$picCt.find("input.pic_original").val("");
			});
		});
	</script>
	<style type="text/css">
		.pic-ct {
			position: relative;
		}

		.pic-ct .delete2 {
			position: absolute;
			top: 2px;
			right: 17px;
			color: black;
			line-height: 0;
			font-size: 0;
			padding: 0;
			display: none;
		}

		.pic-ct .delete2 span {
			font-size: 20px;
		}

		.pic-ct .delete2:hover {
			color: red;
		}

		.btn.croptool {
			padding: 0;
			font-size: 0;
			line-height: 0;
			position: absolute;
			top: 2px;
			left: 17px;
			display: none;
		}

		.btn.croptool.hidden {
			display: none !important;
		}

		.btn.delete2.hidden {
			display: none !important;
		}

		.pic-ct:hover .btn.croptool {
			display: block;
		}

		.pic-ct:hover .btn.delete2 {
			display: block;
		}

	</style>

<?php
	Page()->footer();
?>