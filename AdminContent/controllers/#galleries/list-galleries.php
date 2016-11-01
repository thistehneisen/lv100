<?php

	if (!ActiveUser()->can(Page()->controller,"pārvaldīt")) {
		Page()->accessDenied();
	}

	$parent = Page()->getNode(array(
		"filter"        => array(
			"controller" => Page()->controller,
			"view"       => "list"
		),
		"returnResults" => "first"
	));

	DataBase()->countResults = true;
	$galleries = DataBase()->getRows("SELECT * FROM %s GROUP BY `gallery_id` ORDER BY `gallery_id` DESC LIMIT %d,%d", DataBase()->galleries, Page()->pageCurrent * 12, 12);
	$totalEntries = DataBase()->resultsFound;

	if ($galleries) {
		$childs = Page()->getNode(array(
			"filter" => array(
				"view" => "gallery",
				"id"   => array_map(function ($n) { return $n["node_id"]; }, $galleries)
			),
			"order"  => array("id" => "DESC")
		));
		foreach ($childs as $k => $child) {
			$childs[ $k ]->gallery_id = $galleries[ array_search($child->id, array_map(function ($n) { return $n["node_id"]; }, $galleries)) ]["gallery_id"];
		}
	}

	Page()->addBreadcrumb("Galerijas", Page()->aHost . Page()->controller . "/");

	Page()->header();
?>
	<style type="text/css">

		.big .row .wrap {
			white-space: nowrap;
			text-align: center;
			height: 190px;
		}

		.big .row .wrap .helper {
			display: inline-block;
			height: 100%;
			vertical-align: middle;
		}

		.big .row .wrap img {
			vertical-align: middle;
			max-height: 190px;
			max-width: 100%;
		}

		.big .thumbnail {
			padding: 10px;
		}

		.thumbnail {
			position: relative;
			cursor: pointer;
		}

		.thumbnail.disabled {
			opacity: 0.6;
			-moz-opacity: 0.6;
			-webkit-opacity: 0.6;
		}

		.thumbnail .title {
			white-space: nowrap;
			overflow: hidden;
			width: 100%;
			text-overflow: ellipsis;
		}

		.panel-controls2 {
			display: none;
			position: absolute;
			right: 0;
			top: 0;
		}

		.panel-controls2 a {
			font-size: 18px;
			margin: 6px 4px 0;
			display: inline-block;
		}

		.panel-controls2 a.edit:hover {
			color: #008cca;
		}

		.panel-controls2 a.delete:hover {
			color: #ff0000;
		}

		.panel-controls2 a.move {
			cursor: move;
		}

		.thumbnail:hover .panel-controls2 {
			display: block;
		}
	</style>
	<div class="block" id="ajax-container">
		<div class="panel panel-default">
			<div class="panel-heading">
				<h4 class="panel-title">Galerijas</h4>
				<ul class="panel-controls donthide nav nav-tabs">
					<li>
						<a href="<?php echo Page()->aHost . Page()->controller ?>/editgallery/?parent=<?php print($parent->id); ?>" class="btn-sm btn ajax" role="button">
							<span class="glyphicon glyphicon-plus"></span> Pievienot galeriju
						</a>
					</li>
				</ul>
			</div>
			<div class="panel-body big">
				<div class="clearfix"></div>
				<ul class="row sortable">
					<?php foreach ((array)$childs as $child) { ?>
						<li class="col-xs-3" data-id="<?php print($child->gallery_id); ?>">
							<div class="thumbnail<?php print($child->enabled ? '' : ' disabled'); ?>" data-href="<?php print(Page()->aHost . Page()->controller . "/listgallery/" . $child->gallery_id . "/"); ?>">
								<div class="panel panel-controls2">
									<a href="<?php print(Page()->aHost . Page()->controller . "/editgallery/" . $child->gallery_id); ?>" class="edit ajax"><span class="glyphicon glyphicon-pencil"></span></a>
									<a href="<?php print(Page()->aHost . Page()->controller . "/listgallery/" . $child->gallery_id . "/?delete_gallery=1"); ?>" class="delete" data-confirm="Tiešām vēlies dzēst galeriju un visu tās saturu?"><span class="glyphicon glyphicon-remove"></span></a>
								</div>
								<div class="wrap">
									<span class="helper"></span><img src="<?php print(Page()->getThumb((preg_match("#^http#", $child->cover) ? '' : Page()->path) . $child->cover, 300, 300)); ?>">
								</div>
								<div class="title text-center text-disabled"><?php Page()->e($child->title, 1); ?></div>
							</div>
						</li>
					<?php } ?>
				</ul>
			</div>
			<?php if (ceil($totalEntries / 12) > 1) { ?>
				<div class="panel-footer">
					<nav>
						<ul class="pagination">
							<?php Page()->paging(array(
								"pages"            => ceil($totalEntries / 12),
								"delta"            => 5,
								"echo"             => true,
								"page"             => '<li><a href="%1$s">%2$s</a></li>',
								"active"           => '<li><a href="%1$s" class="active">%2$d</a></li>',
								"prev"             => '<li><a href="%1$s" class="%3$s" aria-label="{{{Previous}}}"><span aria-hidden="true">&laquo;</span></a></li>',
								"next"             => '<li><a href="%1$s" class="%3$s" aria-label="{{{Next}}}"><span aria-hidden="true">&raquo;</span></a></li>',
								"dontShowInactive" => false
							)) ?>
						</ul>
					</nav>
				</div>
			<?php } ?>
		</div>
	</div>
	<script type="text/javascript">
		$(function() {
			$(document).on("click", ".ajax", function(e) {
				e.preventDefault();
				var link = this;
				$("<div\/>").attr("id", "galleryEditDialog").dialog({
					dialogClass: "tw-bs",
					modal      : true,
					draggable  : false,
					resizable  : false,
					width      : 600,
					maxHeight  : "80%",
					open       : function() {
						var dialog = this;
						$(dialog).load(link.href, function() {
							$(dialog).dialog("option", "position", "center center");
						});
					},
					close      : function() {
						$(this).dialog("destroy").remove();
					}
				});
				return false;
			}).on("click", ".thumbnail", function(e) {
				if (!$(e.target).parent().is("a")) {
					document.location.href = $(this).data("href");
				}
			});
		});
	</script>

<?php Page()->footer(); ?>