<?php
	if (!ActiveUser()->can(Page()->controller, "pārvaldīt")) {
		Page()->accessDenied();
	}

	if ($_GET["delete"]) {
		Page()->remNode($_GET["delete"]);
		$_SESSION["post_success"] = "Ieraksts veiksmīgi izdzēsts.";
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
		Page()->cache->purge("partners", "all");
		exit;
	}

	$partners1 = Page()->getNode(array(
		"filter"       => array(
			"controller"         => "members",
			"parent"             => Page()->roots[0]->id,
			"view" => "big",
			"type"               => 5
		),
		"order"        => array("sort" => "ASC", "id" => "DESC"),
		"returnFields" => "id,title,data,view",
		"debug"        => false
	));

	Page()->addBreadcrumb("Vadība", Page()->aHost . Page()->controller . "/");
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

		.thumbnail .title {
			white-space: nowrap;
			overflow: hidden;
			width: 100%;
			text-overflow: ellipsis;
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
				<h3 class="panel-title">Muzeja vadība</h3>
			</div>
			<div class="panel-body">
				<section class="block container-fluid big">
					<div class="clearfix"></div>
					<ul class="row sortable">
						<?php foreach ((array)$partners1 as $partner) { ?>
							<li class="col-xs-3" data-id="<?php print($partner->id); ?>">
								<div class="thumbnail">
									<div class="panel panel-controls">
										<a href="#" class="move"><span class="glyphicon glyphicon-move"></span></a>
										<a href="<?php print(Page()->aHost . Page()->controller . "/edit/" . $partner->id . "/?subtype=" . $partner->view); ?>" class="edit"><span class="glyphicon glyphicon-pencil"></span></a>
										<a href="<?php print(Page()->aHost . Page()->controller . "/?delete=" . $partner->id); ?>" class="delete"><span class="glyphicon glyphicon-remove"></span></a>
									</div>
									<div class="wrap">
										<span class="helper"></span><img src="<?php print(Page()->host . $partner->data->picture); ?>">
									</div>
									<div class="title text-center text-disabled"><?php print($partner->title); ?></div>
								</div>
							</li>
						<?php } ?>
					</ul>
				</section>
			</div>
			<div class="panel-footer">
				<a href="<?php print(Page()->aHost . Page()->controller); ?>/edit/?subtype=big" class="addnew addbutton">Pievienot personu</a>
			</div>
		</div>
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
				confirm("Tiešām vēlies dzēst šo ierakstu?", function (yes) {
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