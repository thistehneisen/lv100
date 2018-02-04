<?php
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

	function print_struct_for_select($parent_id, &$that, $deep = 1, $sid = 0, $selectedParent, $language = null) {
		$filter = array(
			"parent"     => $parent_id,
			"created_by" => array("core", "manual"),
			"type"       => array(1,2)
		);
		if ($that->reqParams[0] && !is_numeric($that->reqParams[0])) {
			$filter["language"] = $that->reqParams[0];
		}
		if (!isset($filter["language"]) && $language) {
			$filter["language"] = $language;
		}
		$nodes = Page()->getNode(array(
			"filter" => $filter,
			"order"  => array(
				"title" => "asc"
			)));
		if (is_array($nodes)) {
			foreach ($nodes as $node) {
				if ($node->id != $sid) {
					echo '<option value="' . htmlspecialchars($node->id) . '" data-url="' . htmlspecialchars($that->host . $node->address) . '"' . ($node->id == $selectedParent ? ' selected' : '') . '>' . ($deep ? str_repeat("-", $deep) . ' ' : '') . $node->title . '</option>';
					print_struct_for_select($node->id, $that, $deep + 1, $sid, $selectedParent, $node->language);
				}
			}
		}
	}

	$node = Page()->getNode(Page()->reqParams[0]);
	if (($node && (!ActiveUser()->canWrite("node", $node->id) && $node->added_by != ActiveUser()->id)) || (!$node && !ActiveUser()->can(Page()->controller,"pievienot sadaļu"))) {
		Page()->accessDenied();
	}

	if ($_GET["ol"]) {
		$ols = Page()->getNode(array(
			"filter" => array(
				"created_by" => array("core", "manual"),
				"<SQL>"      => "`title` LIKE '%" . $_GET["ol"] . "%'",
				"language"   => $_GET["l"]
			)
		));
		echo json_encode(array_map(function ($n) { return array("id" => $n->id, "text" => $n->title); }, $ols));
		exit;
	}

	if ($node) {
		$parent = Page()->getNode($node->parent);
		$language = $node->language;
	} else if (in_array(Page()->reqParams[0], Page()->languages)) $language = Page()->reqParams[0];
	$posibleParents = Page()->getNode(array(
		"filter" => array(
			"language"   => $language,
			"created_by" => array("manual", "core")
		),
		"order"  => array("title" => "ASC")
	));

	foreach (Page()->roots as $root) {
		if ($root->language == $language) break;
	}
	if (!$parent) {
		$parent = Page()->getNode($root->id);
	}

	$rootId = $root->id;

	if ($_SERVER["REQUEST_METHOD"] == "POST") {
		if (!$node->data) $node->data = new stdClass();
		$y = Page()->getNode(array(
			"filter"        => array(
				"parent" => $_POST["parent"],
				"slug"   => $_POST["address"]
			),
			"returnResults" => "first"
		));
		if (is_numeric(Page()->reqParams[0])) {
			$settings["id"] = Page()->reqParams[0];
			$settings["language"] = $node->language;
		} else $settings["created_by"] = "manual";
		$settings["title"] = $_POST["title"];
		$settings["type"] = 1;
		$settings["description"] = $_POST["description"];
		$settings["enabled"] = (int)($_POST["enabled"] == 1);
		$settings["slug"] = $_POST["address"];
		if ($_POST["parent"]) $settings["parent"] = $_POST["parent"];

		if (isset($_POST["uc_enabled"])) {
			if ($_POST["uc_enabled"]) {
				$settings["type"] = 4;
			} else $settings["type"] = 1;
		}

		if (!$_GET["basic"] && ActiveUser()->isDev()) {
			if ($_POST["subid"]) $settings["subid"] = $_POST["subid"];
			if ($_POST["builtin"]) {
				$settings["builtin"] = 1;
			} else $settings["builtin"] = 0;
		}

		if ($_POST["view"]) $settings["view"] = $_POST["view"];


		if ($_POST["controller"]) $settings["controller"] = $_POST["controller"];

		if (isset($_POST["force_redirect"]) && $_POST["force_redirect"]) {
			$uri = $_POST["force_redirect"];
			$ic = Page()->getNode(array(
				"filter"        => array(
					"address" => $uri
				),
				"returnResults" => "first"
			));
			if ($ic || !preg_match("#^(http[s]?|ftp[s]?|mailto):#", $uri)) {
				$internal = true;
			} else $internal = false;
			$node->data->url = $uri;
			$node->data->internal = $internal;
			$node->data->code = 307;
			$settings["type"] = 2;
		} else {
			unset($node->data->url, $node->data->internal, $node->data->code);
		}

		if (!$_GET["basic"]) {
			foreach (Page()->languages as $lng3) {
				if ($node->data->ols && $node->data->ols->{$lng3} && $node->data->ols->{$lng3} != $_POST["ols"][ $lng3 ]) {
					$oldOls[] = Page()->getNode($node->data->ols->{$lng3});
				}
				if ($_POST["ols"][ $lng3 ] && (!$node->data->ols || $node->data->ols->{$lng3} != $_POST["ols"][ $lng3 ])) {
					$newOls[] = Page()->getNode($_POST["ols"][ $lng3 ]);
				}
			}
			$node->data->ols = $_POST["ols"];
		}

		if ($_POST["addhtml"]) {
			$node->data->body_html = $_POST["addhtml"]["body"];
			$node->data->head_html = $_POST["addhtml"]["head"];
		}
		if ($_POST["og-type"]) {
			$node->data->facebook = new stdClass();
			$node->data->facebook->type = $_POST["og-type"];
			$node->data->facebook->image = $_POST["fb-share-pic"];
		} else {
			$node->data->facebook = false;
		}
		if ($_POST["keywords"]) $node->data->keywords = $_POST["keywords"];

		$settings["data"] = $node->data;

		$x = Page()->setNode($settings);
		if ($x) {

			xLog("structure: ".($node && $node->id ? "Labota":"Pivienota")." sadaļa ".$_POST["title"], "success", $x);


			if ($_POST["groups"]) {
				if (!is_array($_POST["groups"]) && is_numeric($_POST["groups"])) {
					$_POST["groups"] = array($_POST["groups"]);
				}
			} else {
				$_POST["groups"] = array();
			}
			if (ActiveUser()->isAdmin() && !isset($_GET["basic"])) {
				DataBase()->queryf("DELETE FROM %s WHERE `controller`='node' AND `unit`='%s' AND `type`='write'", DataBase()->permissions, $x);
				foreach ($_POST["groups"] as $group) {
					DataBase()->insert("permissions", array(
						"group"      => $group,
						"controller" => "node",
						"unit"       => $x,
						"type"       => "write"
					));
				}
			}

			if (isset($oldOls)) {
				foreach ($oldOls as $oldOl) {
					$oldOl->data->ols->{$parent->language} = 0;
					Page()->setNode(array(
						"id"   => $oldOl->id,
						"data" => $oldOl->data
					));
				}
			}
			if (isset($newOls)) {
				foreach ($newOls as $newOl) {
					$newOl->data = json_decode(json_encode($newOl->data), true);
					if (isset($newOl->data["ols"][ $parent->language ]) && $newOl->data["ols"][ $parent->language ] != $x) {
						$newOlsOldOl = Page()->getNode($newOl->data["ols"][ $parent->language ]);
						if ($newOlsOldOl) {
							$newOlsOldOl->data->ols->{$parent->language} = 0;
							Page()->setNode(array(
								"id"   => $newOlsOldOl->id,
								"data" => $newOlsOldOl->data
							));
						}
					}
					$newOl->data["ols"][ $parent->language ] = $x;
					Page()->setNode(array(
						"id"   => $newOl->id,
						"data" => $newOl->data
					));
				}
			}
			Page()->lastNodeUpdated = $x;
		}

		Page()->cache->purge("menu");

		if (isset($_GET["basic"])) {
			die(json_encode($x ? array("Uzstādījumi saglabāti.", "success", false) :
				($y !== false && $y[0]->id != Page()->reqParams[0] ? array("Norādītā adrese jau eksistē.", "danger", false)
					: array("Notika nezināma kļūda. Netika saglabāts.", "danger", false)
				)
			));
		} else {
			$_SESSION["post_response"] = $x ? array("Uzstādījumi saglabāti.", "success", "yes") :
				($y !== false && $y[0]->id != Page()->reqParams[0] ? array("Norādītā adrese jau eksistē.", "danger", "no")
					: array("Notika nezināma kļūda. Netika saglabāts.", "danger", "no")
				);
			header("Location: {$_POST["redirect"]}");
			exit;
		}
	}

	$controllers = array();
	/**
	 * @var Controller $controller
	 */
	$controller = null;
	foreach (Page()->controllers as $slug => $controller) {
		if ($controller->isAvailableAsTemplate()) {
			$controllers[ $slug ] = array(
				"name"  => $controller->getName(),
				"views" => (array)$controller->templateData["views"],
				"ids"   => (array)$controller->templateData["ids"]
			);
		}
	}

	$userGroups = DataBase()->getRows("SELECT * FROM %s WHERE `id`>%d ORDER BY `id` ASC", DataBase()->user_groups, 2);
	$permittedGoups = array();
	if ($node && $node->id) {
		$perms = DataBase()->getRows("SELECT `group` FROM %s WHERE `unit`='%s' AND `controller`='node' AND `type`='write'", DataBase()->permissions, $node->id);
		if ($perms) {
			$permittedGoups = array_map(function($n){
				return $n["group"];
			},$perms);
		}
	}
	if (isset($_GET["basic"])) {

		?>
		<form action="<?php echo Page()->fullRequestUri ?>" method="POST" class="ajaxify container-fluid" id="settings-form">
			<div class="form-group"><!-- Vienuma adrese -->
				<label for="address">Adrese:</label>
				<input id="address" name="address" type="text" value="<?php echo htmlspecialchars(trim($node->slug, '/')) ?>" data-before="<?php echo Page()->host ?><?php echo $parent->address ?>" data-after="/"/>
			</div>
			<div class="form-group row"><!-- Vienuma nosaukums un meta description -->
				<div class="col-xs-6">
					<label for="title">Nosaukums:</label>
					<input class="form-control" id="title" name="title" type="limited" maxlength="160" value="<?php echo htmlspecialchars($node->title) ?>"/>
				</div>
				<div class="col-xs-6">
					<label for="description">Apraksts:
						<span class="info" info="<?php echo htmlspecialchars("Šī informācija tiek dota meklētājiem un izmantota Facebook ierakstos.") ?>"></span></label>
					<input class="form-control" id="description" name="description" type="limited" maxlength="2048" value="<?php echo htmlspecialchars($node->description) ?>"/>
				</div>
			</div>
			<div class="form-group">
				<label for="keywords">Atslēgas vārdi:</label>
				<div class="form-control tagit-container">
					<input id="keywords" name="keywords" value="<?php echo htmlspecialchars($node->data->keywords) ?>">
				</div>
			</div>
			<div class="form-group row"><!-- Vienuma vecāks un status -->
				<div class="col-xs-8">
					<label for="parent">Sadaļa:</label>
					<select id="parent" class="form-control" name="parent">
						<?php print_struct_for_select(0, $this, 0, $node->id, $parent->id, $root->langauge); ?>
					</select>
				</div>
				<div class="col-xs-4">
					<div class="form-horizontal">
						<label class="block">&nbsp;</label>
						<label for="enabled" class="control-label">Publicēt:</label>
						<div class="pull-right">
							<input id="enabled" name="enabled" type="checkbox" value="1" class="selector" <?php echo $node->enabled ? 'checked ' : '' ?>/>
						</div>
					</div>
				</div>
			</div>
			<div class="form-group">
				<label for="force_redirect">Piespiedu pārvirze:
					<span class="info" info="<?php echo htmlspecialchars("Tur lietotāji tiks pārvirzīti, kad mēģinās atvērt šo adresi."); ?>"</span>
				</label>
				<div class="combobox">
					<input name="force_redirect" type="text" id="force_redirect" value="<?php echo htmlspecialchars($node->data->url) ?>"/>
					<ul>
						<?php print_struct(0, $this, 1, $node ? $node->id : 0) ?>
					</ul>
				</div>
			</div>
		</form>
		<script type="text/javascript">
			$("#basic_settings").dialog("addbutton", {
				text   : "Saglabāt",
				"class": "btn-success",
				click  : function() {
					$(this).find("form").submit();
				}
			});
			$("select[name=parent]").on("change", function() {
				var u = $(this).find("option:selected").data("url");
				if (u) {
					$("input[name=address]").data("before", u);
				}
			}).trigger("change");
			$("#settings-form").on("submit", function(e) {
				e.preventDefault();
				var opts = {
					dataType: "json",
					success : function(resp) {
						cmsAlert((<?php echo json_encode(Page()->cmsInfotip("%0", "%1", "", false, false))?>).replace("%0", resp[0])
						                                                                                     .replace("%1", resp[1])
						                                                                                     .replace("%2", resp[2]), function() {
							if (resp[1] != "red") {
								$("#basic_settings").dialog("close");
							}
						});
						$.get(<?php echo json_encode($_SERVER["HTTP_REFERER"])?>, function(htmlText) {
							$("#node-list").empty().append($($.parseHTML(htmlText)).find("#node-list").html());
						});
					}
				};
				$(this).ajaxSubmit(opts);
				return false;
			});
			$("[name=address]").on("change", function() {
				var s = $(this).val();
				var p = <?php echo json_encode((int)$parent->id)?>;
				var c = <?php echo json_encode((int)$node->id)?>;
				$.getJSON(<?php echo json_encode(Page()->aHost . Page()->controller . "/check-slug/")?>, {
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
			$("#keywords").tagit({
				allowSpaces  : true,
				caseSensitive: false,
				animate      : false,
				fieldName    : "keywords",
				availableTags: [],
				autocomplete : {
					position: {collision: "flip"},
					appendTo: "parent",
					delay   : 200
				}
			});
		</script>
		<?php
		exit;
	} else {

		Page()->addBreadcrumb("Lapas struktūra", Page()->aHost . Page()->controller . "/");
		Page()->addBreadcrumb($node ? $node->title : "jauns ieraksts", Page()->aHost . Page()->controller . "/settings/" . Page()->reqParams[0] . "/");

		if (!isset($_SERVER["HTTP_REFERER"]) || $_SERVER["HTTP_REFERER"] == "") {
			$_SERVER["HTTP_REFERER"] = Page()->aHost . Page()->controller . "/";
		}

		Page()->fluid = true;
		Page()->header();
		?>
		<style>
			aside.rightbar p {
				margin-bottom: 5px;
			}
		</style>
		<form class="addbody new" action="<?php echo htmlspecialchars(Page()->fullRequestUri) ?>" method="POST">
			<input type="hidden" name="redirect" value="<?php echo htmlspecialchars($_SERVER["HTTP_REFERER"]) ?>"/>
			<header>
				<a href="<?php echo $_POST["referer"] ? $_POST["referer"] : $_SERVER["HTTP_REFERER"] ?>" class="btn btn-lg btn-primary pull-left btn-back">Atpakaļ</a>
				<h1><?php echo $node ? $node->title : "Jauns ieraksts" ?></h1>
			</header>
			<div class="col-content">
				<section>
					<h1>Vispārīgi</h1>
					<div class="form-group">
						<label for="title">Nosaukums:</label>
						<input id="title" name="title" class="form-control" type="text" maxlength="160" value="<?php echo htmlspecialchars($node->title) ?>"/>
					</div>
					<div class="form-group"><!-- Vienuma adrese -->
						<label for="address">Adrese:</label>
						<input id="address" name="address" type="text" value="<?php echo htmlspecialchars(trim($node->slug, '/')) ?>" data-before="<?php echo Page()->host ?><?php echo $parent->address ?>" data-after="/"/>
					</div>
					<div class="form-group">
						<label for="force_redirect">Piespiedu pārvirze:
							<span class="info" info="<?php echo htmlspecialchars("Tur lietotāji tiks pārvirzīti, kad mēģinās atvērt šo adresi.") ?>"></span></label>
						<div class="combobox">
							<input name="force_redirect" type="text" id="force_redirect" value="<?php echo htmlspecialchars($node->data->url) ?>"/>
							<ul>
								<?php print_struct(0, $this, 1, $node ? $node->id : 0) ?>
							</ul>
						</div>
					</div>
					<div class="form-group"><!-- Vienuma nosaukums un meta description -->
						<label for="description">Apraksts:
							<span class="info" info="<?php echo htmlspecialchars("Šī informācija tiek dota meklētājiem un izmantota Facebook ierakstos.") ?>"></span></label>
						<textarea id="description" class="form-control" name="description" maxlength="2048"><?php echo htmlspecialchars($node->description) ?></textarea>
					</div>
					<div class="form-group">
						<label for="keywords">Atlsēgas vārdi:</label>
						<div class="form-control tagit-container">
							<input id="keywords" name="keywords" value="<?php echo htmlspecialchars($node->data->keywords) ?>">
						</div>
					</div>
					<div class="form-group hidden">
						<label for="addhtml-head">Papildus HTML kods, ko ievietot lapas galvenē (&lt;head&gt;):</label>
						<textarea id="addhtml-head" class="form-control" name="addhtml[head]"><?php echo htmlspecialchars($node->data->head_html) ?></textarea>
					</div>
					<div class="form-group hidden">
						<label for="addhtml-body">Papildus HTML kods, ko ievietot lapas saturā (&lt;body&gt;):</label>
						<textarea id="addhtml-body" class="form-control" name="addhtml[body]"><?php echo htmlspecialchars($node->data->body_html) ?></textarea>
					</div>
				</section>
			</div>
			<div class="col-sidebar">
				<aside class="rightbar tabsontop">
					<div class="groupbutton tabs">
						<div>
							<a class="button selected" href="#general-bar">Uzstādījumi</a>
							<?php if (Page()->facebookEnabled) { ?>
								<a class="button" href="#facebook-bar">Facebook</a>
							<?php } ?>
							<?php if (count(Page()->languages) > 1) { ?>
								<a class="button" href="#ols">Citās valodas</a>
							<?php } ?>
						</div>
					</div>
					<section>
						<div class="content" id="general-bar">
							<h1>Uzstādījumi</h1>
							<?php if (!$node || $node->parent) { ?>
								<div class="form-group">
								<label for="parent" style="display: block;">Sadaļa:</label>
								<select id="parent" name="parent" class="form-control">
									<?php print_struct_for_select(0, $this, 0, $node->id, $parent->id, $root->language); ?>
								</select>
								</div><?php } ?>
							<?php if (ActiveUser()->isDev()) { ?>
								<p>
									<label for="controller">Controller:</label>
									<input type="text" class="form-control" name="controller" id="controller" value="<?php print(htmlspecialchars($node->controller)); ?>">
								</p>                                <p>
									<label for="view">Controller View:</label>
									<input type="text" class="form-control" name="view" id="view" value="<?php print(htmlspecialchars($node->view)); ?>">
								</p>                                <p>
									<label for="subid">Controller SubId:</label>
									<input type="text" class="form-control" name="subid" id="subid" value="<?php print(htmlspecialchars($node->subid)); ?>">
								</p>
							<?php } else { ?><?php if (!$node->builtin || ActiveUser()->isDev()) { ?>
								<p>
									<label for="controller" style="display: block;">Veidne:</label>
									<select id="controller" name="controller" style="width: 100%;" class="form-control">
										<?php foreach ($controllers as $c => $s) {
											?>
											<option value="<?php echo $c ?>" <?php echo($c == $node->controller || ($c == "static-page" && !$node) ? ' selected' : '') ?>><?php echo $s["name"] ?: $c ?></option>
										<?php } ?>
									</select>
								</p>                                <p>
									<label for="view" style="display: block;">Skats:</label>
									<select id="view" name="view" style="width: 100%;" class="form-control">
									</select>
								</p>
							<?php } ?><?php } ?>
							<div class="form-group">
								<label for="enabled">Publicēt:</label>
								<span class="pull-right"><input id="enabled" name="enabled" type="checkbox" value="1" class="selector" <?php echo $node->enabled ? 'checked ' : '' ?>/></span>
							</div>
							<?php if (ActiveUser()->isDev()) { ?>
								<div class="form-group">
									<label for="builtin">Iebūvēta:</label>
									<span class="pull-right"><input id="builtin" name="builtin" type="checkbox" value="1" class="selector" <?php echo $node->builtin ? 'checked ' : '' ?>/></span>
								</div>
								<div class="form-group hidden">
									<label for="uc_enabled">Izstrādes stadijā:</label>
									<span class="pull-right"><input id="uc_enabled" name="uc_enabled" type="checkbox" value="1" class="selector" <?php echo $node->type == 4 ? 'checked ' : '' ?>/></span>
								</div>
							<?php } ?>
							<?php if (ActiveUser()->isAdmin()) { ?>
								<div>
									<h1>Atļaujas</h1>
									<select multiple name="groups">
										<?php foreach ($userGroups as $group) { ?>
											<option value="<?php print($group["id"]); ?>"<?php print(in_array($group["id"],$permittedGoups) ? ' selected' : ''); ?>><?php print($group["name"]); ?></option>
										<?php } ?>
									</select>
								</div>
							<?php } ?>

						</div>
						<?php if (Page()->useFacebookMeta) { ?>
							<div class="content" id="facebook-bar" style="display: none;">
								<h1>Facebook
									<span class="info" info="Lieto tikai tādus atēlus, kas ir lielāki par 1200px platumā"></span>
								</h1>
								<fieldset>
									<a href="#" id="upload-fb-share-button" class="btn btn-default btn-upload block">Augšupielādēt</a>
									<div id="fb-share-pic-content">
										<img width="100%"<?php if ($node->data->facebook) { ?> src="<?= Page()->host . $node->data->facebook->image ?>"<?php } ?>/>
										<input type="hidden" name="fb-share-pic" value="<?= $node->data->facebook->image ?>"/>
									</div>
									<label for="og-type" style="display: block;">Tips:</label>
									<select id="og-type" name="og-type" class="form-control">
										<option value=""></option>
										<?php if ($node->parent == 0) { ?>
										<option value="website"<?= $node->data->facebook->type == "website" ? ' selected' : '' ?>>Weblapa</option><?php } ?>
										<option value="article"<?= $node->data->facebook->type == "article" ? ' selected' : '' ?>>Raksts</option>
										<option value="object"<?= $node->data->facebook->type == "object" ? ' selected' : '' ?>>Cits objekts</option>
									</select>
								</fieldset>
							</div>
						<?php } ?>
						<?php if (count(Page()->languages) > 1) { ?>
							<div id="ols" style="display: none;" class="content">
								<h1>Citās valodās</h1>
								<div class="content">
									<fieldset>
										<?php foreach (Page()->languages as $language) {
											if ($language == $parent->language) continue; ?>
											<div class="form-group jui">
												<label for="language-<?php print($language); ?>"><?php print(Page()->language_labels[ $language ]); ?>:</label>
												<select data-language="<?php print($language); ?>" id="language-<?php print($language); ?>" class="form-control ols" name="ols[<?php print($language); ?>]">
													<?php if ($node->data->ols->{$language}) {
														$ol = Page()->getNode($node->data->ols->{$language});
														?>
														<option value="<?php print($ol->id); ?>"><?php print($ol->title); ?></option>
													<?php } ?>
												</select>
											</div>
										<?php } ?>
									</fieldset>
								</div>
							</div>
						<?php } ?>
						<p class="span form-actions">
							<a href="<?php echo $_SERVER["HTTP_REFERER"] ?>" class="btn btn-default">Atcelt</a>
							<button type="submit" class="btn btn-success pull-right">Saglabāt</button>
							<?php if ($node->id && (!$node->builtin || ActiveUser()->isDev())) { ?>
								<a href="<?php echo Page()->aHost . Page()->controller ?>/delete/<?php echo $node->id ?>/?return-to=<?php echo urlencode($_SERVER["HTTP_REFERER"]) ?>" onclick="var h=this.href;event.preventDefault(); cmsConfirm('Vai tiešām vēlies dzēst šo ierakstu?',function(r){if (r) {document.location.href=h;
}});" class="btn btn-danger" style="margin-left: 5px;">Dzēst</a>
							<?php } ?>
						</p>
					</section>
				</aside>
			</div>
		</form>
		<script type="text/javascript">
			function upl(button/*Džeikverī Obdžekt*/, callback/*Funkšen On Suksesss*/, func, size) {
				if (typeof window.uplinc == "undefined") {
					window.uplinc = 0;
				}
				else {
					uplinc++;
				}

				if (!button.length) {
					return;
				}

				button[0].setAttribute("id", "uplb_" + uplinc);
				button.parent()[0].setAttribute("id", "uplc_" + uplinc);

				var uploader = new plupload.Uploader({
					runtimes           : 'html5,flash,silverlight',
					browse_button      : "uplb_" + uplinc,
					container          : "uplc_" + uplinc,
					max_file_size      : '20mb',
					multi_selection    : false,
					url                : <?php echo json_encode(Page()->aHost . "media/upload_")?>+func + "/" + size[0] + "/" + size[1] + "/?allowStretch",
					flash_swf_url      : '<?php echo Page()->bHost?>js/plupload/plupload.flash.swf',
					silverlight_xap_url: '<?php echo Page()->bHost?>js/plupload/plupload.silverlight.xap',
					filters            : [{title: "Images", extensions: "png,jpg,gif,jpeg"}]
				});
				if ($("#uplb_" + uplinc).length) {
					uploader.init();
				}
				uploader.bind('FilesAdded', function(up, files) {
					$.modal({content: '<span class="loading">Augšupielādē (<span id="upload-progress"><\/span>)...<\/span>'});

					up.refresh();
					uploader.start();
				});
				uploader.bind('UploadProgress', function(up, file) {
					$('#upload-progress').html(file.percent + '%');
				});
				uploader.bind('FileUploaded', function(up, file, response) {
					var jsonrpc = $.parseJSON(response.response);

					if (jsonrpc.error) {
						$.modal({content: jsonrpc.error.message, appendClose: "Ok"});
						return;
					}
					$.modal("destroy");
					callback && callback(jsonrpc.file);
				});
			}
			$(function() {

				var controllers = <?php Page()->e($controllers, 3); ?>;

				$("#controller").on("change", function() {
					$("#view").empty();
					var controller = controllers[$(this).val()];
					if (controller && typeof controller.views != "undefined" && controller.views) {
						$.each(controller.views, function(k, v) {
							$("#view").append('<option value="' + v + '">' + v + '<\/option>');
						});
					}
					$("#view").focus();
				}).change();

				upl($("#upload-fb-share-button"), function(file) {
					$("#fb-share-pic-content img").attr("src", <?=json_encode(Page()->host)?>+file);
					$("#fb-share-pic-content input").val(file);
				}, "file", [0, 0]);

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
								.text(removeAccents($(this).val()).toLowerCase().replace(/[^a-zA-Z0-9-]+/gi, "-")
								                                  .replace(/[-]+/gi, '-').trim("-"))
								.trigger("keyup");
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
					$.getJSON(<?php echo json_encode(Page()->aHost . Page()->controller . "/check-slug/")?>, {
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
				$("#addhtml-head, #addhtml-body").autosize({
					className: "form-control"
				});
				$("#keywords").tagit({
					allowSpaces  : true,
					animate      : false,
					caseSensitive: false,
					fieldName    : "keywords",
					availableTags: [],
					autocomplete : {
						position: {collision: "flip"},
						appendTo: "parent",
						delay   : 200
					}
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

			});
		</script>
		<?php
		Page()->footer();
		exit;
	}

?>