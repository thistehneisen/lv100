<?php
	if (!ActiveUser()->can(Page()->controller, "pārvaldīt")) {
		Page()->accessDenied();
	}

	if ($_GET["delete"]) {
		Page()->remNode($_GET["delete"]);
		Page()->cache->purge("ads");
		$_SESSION["post_success"] = "Baneris veiksmīgi izdzēsts.";
		header("Location: ".Page()->aHost.Page()->controller);
		exit;
	}

	if ($_POST["sort"]) {
		foreach ($_POST["sort"] as $k => $id) {
			Page()->setNode(array(
				"id"   => $id,
				"sort" => $k + 1
			));
		}
		Page()->cache->purge("ads");
		exit;
	}

	$ads1 = Page()->getNode(array(
		"filter"       => array(
			"controller"         => "ads",
			"view" => "jpeg",
			"type"               => 5,
			"parent"            => $_GET["sid"]
		),
		"order"        => array("sort" => "ASC", "id" => "DESC"),
		"debug"        => false
	));
	$ads2 = Page()->getNode(array(
		"filter"       => array(
			"controller"         => "ads",
			"view" => "swf",
			"type"               => 5,
			"parent"            => $_GET["sid"]
		),
		"order"        => array("sort" => "ASC", "id" => "DESC"),
		"debug"        => false
	));
	$ads3 = Page()->getNode(array(
		"filter"       => array(
			"controller"         => "ads",
			"view" => "text",
			"type"               => 5,
			"parent"            => $_GET["sid"]
		),
		"order"        => array("sort" => "ASC", "id" => "DESC"),
		"debug"        => false
	));

	Page()->addBreadcrumb("Baneri", Page()->aHost . Page()->controller . "/");
	Page()->header();
	Page()->fluid = true;


?>
	<style type="text/css">
		.small .row .wrap {
			white-space: nowrap;
			text-align: center;
			height: 90px;
		}

		.small .row .wrap .helper {
			display: inline-block;
			height: 100%;
			vertical-align: middle;
		}

		.small .row .wrap img {
			vertical-align: middle;
			max-height: 90px;
			max-width: 100%;
		}

		.big .row .wrap {
			white-space: nowrap;
			text-align: center;
			height: 130px;
		}

		.big .row .wrap .helper {
			display: inline-block;
			height: 100%;
			vertical-align: middle;
		}

		.big .row .wrap img {
			vertical-align: middle;
			max-height: 130px;
			max-width: 100%;
		}

		.small .thumbnail {
			padding: 5px;
		}

		.big .thumbnail {
			padding: 10px;
		}

		.thumbnail {
			position: relative;
		}

		.thumbnail.disabled {
			opacity: .5;
			-webkit-opacity: .5;
			-moz-opacity: .5;
		}

		.panel-controls {
			display: none;
			position: absolute;
			right: 0;
			top: 0;
		}

		.panel-controls a {
			font-size: 18px;
			margin: 6px 4px 0;
			display: inline-block;
		}

		.panel-controls a.edit:hover {
			color: #008cca;
		}

		.panel-controls a.delete:hover {
			color: #ff0000;
		}

		.panel-controls a.move {
			cursor: move;
		}

		.thumbnail:hover .panel-controls {
			display: block;
		}
	</style>
	<div id="ajax-container">
		<?php if ($_SESSION["post_success"]) { ?>
			<div class="alert alert-success alert-dismissible">
				<button type="button" class="close" data-dismiss="alert" aria-label="Close">
					<span aria-hidden="true">&times;</span></button>
				<strong><?php print($_SESSION["post_success"]); ?></strong>
			</div>
			<?php unset($_SESSION["post_success"]);
		} ?>
		<div class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title">JPEG baneri</h3>
			</div>
			<div class="panel-body">
				<section class="block container-fluid big">
					<div class="clearfix"></div>
					<ul class="row sortable">
						<?php foreach ((array)$ads1 as $ad) { ?>
							<li class="col-xs-4" data-id="<?php print($ad->id); ?>">
								<div class="thumbnail<?php print($ad && !$ad->enabled ? ' disabled' : ''); ?>">
									<div class="panel panel-controls">
										<a href="#" class="move"><span class="glyphicon glyphicon-move"></span></a>
										<a href="<?php print(Page()->aHost . Page()->controller . "/edit/" . $ad->id . "/?subtype=" . $ad->view); ?>" class="edit"><span class="glyphicon glyphicon-pencil"></span></a>
										<a href="<?php print(Page()->aHost . Page()->controller . "/?delete=" . $ad->id); ?>" class="delete"><span class="glyphicon glyphicon-remove"></span></a>
									</div>
									<div class="wrap">
										<span class="helper"></span><img src="<?php print(Page()->host . $ad->data->picture); ?>">
									</div>
								</div>
							</li>
						<?php } ?>
					</ul>
				</section>
			</div>
			<div class="panel-footer">
				<a href="<?php print(Page()->aHost . Page()->controller); ?>/edit/?subtype=jpeg&sid=<?php print($_GET["sid"]); ?>" class="addnew addbutton">Pievienot jpeg baneri</a>
			</div>
		</div>
		<div class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title">Flash baneri</h3>
			</div>
			<div class="panel-body">
				<section class="block container-fluid big">
					<div class="clearfix"></div>
					<ul class="row sortable">
						<?php foreach ((array)$ads2 as $ad) { ?>
							<li class="col-xs-4" data-id="<?php print($ad->id); ?>">
								<div class="thumbnail<?php print($ad && !$ad->enabled ? ' disabled' : ''); ?>">
									<div class="panel panel-controls">
										<a href="#" class="move"><span class="glyphicon glyphicon-move"></span></a>
										<a href="<?php print(Page()->aHost . Page()->controller . "/edit/" . $ad->id . "/?subtype=" . $ad->view); ?>" class="edit"><span class="glyphicon glyphicon-pencil"></span></a>
										<a href="<?php print(Page()->aHost . Page()->controller . "/?delete=" . $ad->id); ?>" class="delete"><span class="glyphicon glyphicon-remove"></span></a>
									</div>
									<div class="wrap">
										<span class="helper"></span><img src="<?php print(Page()->host . "Library/Assets/swf.png"); ?>">
									</div>
								</div>
							</li>
						<?php } ?>
					</ul>
				</section>
			</div>
			<div class="panel-footer">
				<a href="<?php print(Page()->aHost . Page()->controller); ?>/edit/?subtype=swf&sid=<?php print($_GET["sid"]); ?>" class="addnew addbutton">Pievienot flash baneri</a>
			</div>
		</div>
<?php /*		<div class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title">Teksta baneri</h3>
			</div>
			<div class="panel-body">
				<section class="block container-fluid small">
					<div class="clearfix"></div>
					<ul class="row sortable">
						<?php foreach ((array)$ads3 as $ad) { ?>
							<li class="col-xs-4" data-id="<?php print($ad->id); ?>">
								<div class="thumbnail<?php print($ad && !$ad->enabled ? ' disabled' : ''); ?>">
									<div class="panel panel-controls">
										<a href="#" class="move"><span class="glyphicon glyphicon-move"></span></a>
										<a href="<?php print(Page()->aHost . Page()->controller . "/edit/" . $ad->id . "/?subtype=" . $ad->view); ?>" class="edit"><span class="glyphicon glyphicon-pencil"></span></a>
										<a href="<?php print(Page()->aHost . Page()->controller . "/?delete=" . $ad->id); ?>" class="delete"><span class="glyphicon glyphicon-remove"></span></a>
									</div>
									<div class="wrap">
										<span class="helper"></span><img src="<?php print(Page()->host . $ad->data->picture); ?>">
									</div>
								</div>
							</li>
						<?php } ?>
					</ul>
				</section>
			</div>
			<div class="panel-footer">
				<a href="<?php print(Page()->aHost . Page()->controller); ?>/edit/?subtype=text&sid=<?php print($_GET["sid"]); ?>" class="addnew addbutton">Pievienot teksta baneri</a>
			</div>
		</div>
*/ ?>
	</div>
	<script type="text/javascript">
		function makeThingsSortable() {
			$(".sortable").sortable({
				tolerance: 'pointer',
				revert: 100,
				handle: 'a.move',
				containment: "parent",
				stop: function () {
					$.post(document.location.href, {
						sort: $.map($(this).children(), function (n, i) {
							return $(n).data("id");
						})
					}, function (response) {
					});
				}
			});
		}
		$(function () {
			makeThingsSortable();
			$(document).on("click", ".panel-controls .delete", function (e) {
				e.preventDefault();
				var that = this;
				cmsConfirm("Tiešām vēlies dzēst šo baneri?", function (yes) {
					if (yes) {
						$.get($(that).attr("href"), function (response) {
							$("#ajax-container").html($($.parseHTML(response)).find("#ajax-container").html());
							makeThingsSortable();
						});
					}
				});
			});
			$(document).on("click", ".panel-controls .edit, .addnew", function (e) {
				e.preventDefault();
				var that = this;
				var editDialog = $($.parseHTML("<div/>")).attr({id: "editDialog"}).html('<span class="loading">' + I81n.t("{{Loading}}"), +'</span>').dialog({
					width: 600,
					height: 328,
					modal: true,
					draggable: false,
					resizable: false,
					close: function () {
						$(this).dialog("destroy").remove();
					},
					open: function () {
						$(this).load($(that).attr("href"));
					},
					buttons: [
						{
							text: I81n.t("{{Cancel}}"),
							click: function () {
								$(this).dialog("close");
							}
						},
						{
							text: I81n.t("{{Save}}"),
							"class": "btn-success",
							click: function () {
								$(saveForm).submit();
							},
							disabled: true,
							id: "editSaveButton"
						}
					]
				});

			});
		});
	</script>
<?
	Page()->footer();
?>