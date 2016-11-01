<?php
	if (!ActiveUser()->can(Page()->controller, "mainīt izvēlni")) {
		Page()->accessDenied();
	}

	if (Page()->method == "POST") {

		foreach (Page()->languages as $language) {
			$menu_item_data = $_POST["menu_item_data"][$language];
			if (!$menu_item_data) $menu_item_data = array();
			array_walk($menu_item_data, function (&$val) { $val = json_decode($val); });
			if (json_last_error()) Page()->debug(json_last_error_msg());
			array_walk($menu_item_data, function (&$val) {
				if ($val->isNode && $val->childs) {
					foreach ($val->childs as $child) {
						Page()->setNode(array(
							"id"   => $child->id,
							"sort" => $child->sort
						));
					}
				}
				unset($val->childs);
			});
			Settings()->set("site_menu", $menu_item_data, $language);
		}
		header("Location: {$_SERVER["HTTP_REFERER"]}");
		exit;
	}

	Page()->addBreadcrumb("Sadaļas", Page()->aHost . Page()->controller);
	Page()->addBreadcrumb("Izvēlne", Page()->aHost . Page()->controller . "/" . Page()->action . "/");

	Page()->header();
?>

	<form class="addbody new" method="post" id="mainmenuform" action="<?php echo Page()->fullRequestUri ?>">
		<button type="submit" class="btn btn-success pull-right" id="save" disabled>Saglabāt</button>
		<header>
			<a href="<?php echo Page()->adminHost . Page()->controller ?>" class="btn btn-primary pull-left btn-back" onclick="">Atpakaļ</a>
			<h1 class="pull-left"><span class="glyphicon glyphicon-list"></span> Lapas izvēlne</h1>
		</header>

		<?php foreach (Page()->roots as $root) { ?>
			<div class="panel panel-default">
				<div class="panel-heading">
					<h3 class="panel-title"><?php print(Page()->language_labels[ $root->language ]); ?></h3>
				</div>
				<div class="panel-body nav-list">
					<ul class="list-inline menu-list" data-language="<?php print($root->language); ?>">

						<?php
							$menu = Settings()->get("site_menu", $root->language);

							if (is_array($menu)) {
								foreach ($menu as $item) {
									$item = (object)$item;
									if ($item->isNode) {
										$item->childs = Page()->getNode(array(
											"filter"       => array(
												"parent"     => $item->isNode,
												"created_by" => array("core", "manual")
											),
											"returnFields" => "id,title,fullAddress,sort",
											"order"        => array("sort" => "ASC", "title" => "ASC")
										));
									}
									?>
									<li class="root-element">
										<textarea title="" class="hidden" name="menu_item_data[<?php print($root->language); ?>][]"><?php Page()->e(json_encode($item), 1); ?></textarea>
										<a href="#" class="remove"><span class="glyphicon glyphicon-remove"></span></a>
										<div class="list-group-item list-group-item-info" title="<?php Page()->e($item->title, 1); ?>"><?php print($item->title); ?></div>
										<ul class="list-group">
											<?php if ($item->isNode && $item->childs) {
												foreach ($item->childs as $child) { ?>
													<li class="list-group-item" title="<?php Page()->e($child->title, 1); ?>" data-id="<?php print($child->id); ?>"><?php print($child->title); ?></li>
												<?php }
											} ?>
										</ul>
									</li>

								<?php }
							} ?>
						<span class="new">
							<div class="list-group-item list-group-item-info">
								<a href="<?php print(Page()->aHost . Page()->controller); ?>/menu-new-item/<?php print($root->language); ?>/" class="new-root"><span class="glyphicon glyphicon-plus"></span></a>
							</div>
						</span>
					</ul>
				</div>
			</div>
		<?php } ?>

	</form>
	<script type="text/javascript">
		var changed = false;
		$(function() {

			window.enableMainSubMenuSort = function(menu) {
				// menu vajag būt ul
				$(menu)
					.sortable({
						placeholder         : "list-group-item",
						containment         : "parent",
						cancel              : ".list-group-item-info",
						forcePlaceholderSize: true,
						forceHelperSize     : true,
						items               : "> li",
						tolerance           : 'pointer',
						axis                : "y",
						revert              : 0,
						start               : function(e, ui) {
							ui.placeholder.width(ui.helper.outerWidth());
						},
						update              : function() {
							changed = true;
							$("#save").prop("disabled", false);
							var thisMenuData = $.parseJSON($(this).parents(".root-element:first").children("textarea")
							                                      .val());
							$(this).parents(".root-element:first").find("ul > li").each(function(i) {
								var that   = this;
								$.grep(thisMenuData.childs, function(v) {
									return v.id == $(that).data("id");
								})[0].sort = i + 1;
							});
							$(this).parents(".root-element:first").children("textarea").val($.toJSON(thisMenuData));
						}
					});
			};

			$(".nav-list > ul").each(function() {
				var thisMenu = $(this);
				enableMainSubMenuSort(thisMenu.find("ul"));
				$(thisMenu)
					.sortable({
						placeholder         : "list-group-item",
						containment         : "parent",
						handle              : ".list-group-item-info",
						forcePlaceholderSize: true,
						forceHelperSize     : true,
						items               : "> li",
						tolerance           : 'pointer',
						revert              : 0,
						start               : function(e, ui) {
							ui.placeholder.width(ui.helper.outerWidth());
							ui.placeholder.height(ui.helper.outerHeight());
						},
						update              : function() {
							changed = true;
							$("#save").prop("disabled", false);
						}
					});
			});

			$(document).on("dblclick", ".nav-list .list-inline > li > a.remove", function(e) {
				e.preventDefault();
				$(this).parent().remove();
				changed = true;
				$("#save").prop("disabled", false);
			});

			$(document).on("click", ".new-root", function(e) {
				e.preventDefault();
				$($.parseHTML("<div/>")).append($.parseHTML('<span class="loading"></span>'))
				                        .attr({id: "new-item"}).dialog({
					minWidth : 700,
					maxHeight: 500,
					position : ["auto", 190],
					title    : "Pievienot sadaļu",
					resizable: false,
					draggable: false,
					buttons  : [
						{
							text : "Atcelt",
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
			});
		});
	</script>
<?php
	Page()->footer();
?>