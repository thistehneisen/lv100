<?php
	if (!ActiveUser()->canAccessPanel()) {
		Page()->accessDenied();
	}

	if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["changestate"]) && $_POST['changestate'] && ActiveUser()->can(Page()->controller, "mainīt lapas statusu")) {
		Settings()->set("site_enabled", $_POST['state'] == '1', $_POST['changestate']);
		die(json_encode(Settings()->get("site_enabled", $_POST['changestate'])));
	}

	foreach (Page()->languages as $lng) {
		$tts = Page()->getNode(array(
			"filter"        => array(
				"parent"   => 0,
				"deleted"  => 0,
				"original" => 0,
				"language" => $lng
			),
			"returnResults" => "first"
		));
		$roots[ $tts->language ] = $tts;
	}

	function build_struct(&$childs) {
		foreach ($childs as &$child) {
			$child->childrens = Page()->getNode(array(
				"filter" => array(
					"parent"     => $child->id,
					"deleted"    => 0,
					"original"   => 0,
					"created_by" => array("core", "manual")
				),
				"order"  => array("sort" => "ASC", "title" => "ASC", "id" => "ASC")
			));
			if ($child->childrens && count($child->childrens)) build_struct($child->childrens);
		}
	}

	build_struct($roots);

	function print_tree(&$unit, $fl) {
		if (!is_array($unit->childrens)) return;
		echo '<ul>';
		foreach ($unit->childrens as &$child) {
			$childs = is_array($child->childrens) ? count($child->childrens) : 0;

			echo '<li id="node-' . $child->id . '"' . (ActiveUser()->canWrite("node", $child->id) || $child->added_by == ActiveUser()->id ? '' : ' disabled') . '>';
			if ($childs) {
				echo '<input type="checkbox"' . ($fl === true ? ' checked' : '') . '><label></label>';
			} else {
				echo '<label></label>';
			}

			echo '<div>';
			if (ActiveUser()->canWrite("node", $child->id) || $child->added_by == ActiveUser()->id) {
				echo '<div class="controls">';
				if (Page()->isContentController($child->controller)) echo '<a href="' . Page()->aHost . $child->controller . '/?sid=' . $child->id . '&lng=' . $child->language . '" class="btn btn-default btn-xs">Labot saturu</a>';
				echo '<a href="' . Page()->aHost . Page()->controller . '/settings/' . $child->id . '/?basic" class="btn btn-success btn-xs settings basicedit" info="Pamata uzstādījumi"><span class="glyphicon glyphicon-cog"></span></a><a href="' . Page()->aHost . Page()->controller . '/settings/' . $child->id . '/" class="btn btn-warning btn-xs" info="Paplašināti uzstādījumi"><span class="glyphicon glyphicon-cog"></span></a>';
				echo '</div>';
			}
			echo '<a href="#" target="_blank" class="' . (!$child->enabled ? ' text-danger' : '') . (!ActiveUser()->canWrite("node", $child->id) && $child->added_by != ActiveUser()->id ? ' text-muted' : '') . '">' . (ActiveUser()->isDev() ? '(#' . $child->id . ') ' : '') . $child->title . '</a>';
			echo '</div>';

			if ($childs) print_tree($child, ($fl === true ? 2 : null));
			echo '</li>';
		}
		echo '</ul>';
	}

	//echo '<pre>'; print_r($roots); echo '</pre>';
	Page()->addBreadcrumb("Lapas struktūra", Page()->aHost . Page()->controller . "/");

	Page()->header();
	Page()->addStyle("structure.css");
?>
	<nav class="sidebar">
		<?php if (ActiveUser()->can(Page()->controller, "pievienot sadaļu")) { ?>
			<div class="btn-group btn-group-justified">
				<div class="btn-group btn-group-lg">
					<button type="button" data-toggle="dropdown" class="btn btn-default dropdown-toggle btn-primary btn-add block">
						Pievienot sadaļu <span class="caret"></span>
					</button>
					<ul class="dropdown-menu">
						<?php foreach (Page()->languages as $language) { ?>
							<li>
								<a class="flag <?php echo $language ?>" href="<?php echo Page()->adminHost ?><?= Page()->controller ?>/settings/<?php echo $language ?>"><?php echo Page()->language_labels[ $language ] ?></a>
							</li>
						<?php } ?>
					</ul>
				</div>

			</div>
		<?php } ?>
		<ul class="sections">
			<li>
				<a class="home<?php echo Page()->action == "list" ? " active" : "" ?>" href="<?php echo Page()->adminHost ?><?= Page()->controller ?>/">Visas sadaļas</a>
			</li>
			<?php foreach (Page()->languages as $language) { ?>
				<li>
					<a class="flag <?php echo $language ?><?php echo Page()->action == $language ? " active" : "" ?>" href="<?php echo Page()->adminHost ?><?= Page()->controller ?>/<?php echo $language ?>"><?php echo Page()->language_labels[ $language ] ?></a>
				</li>
			<?php } ?>
		</ul>
		<ul class="actions">
			<?php if (ActiveUser()->can(Page()->controller, "mainīt izvēlni")) { ?>
				<li><a href="<?php echo Page()->aHost . Page()->controller ?>/menu/">Lapas izvēlne</a></li><?php } ?>
			<?php if (ActiveUser()->can(Page()->controller, "1. lapas saturs")) { ?>
				<li><a href="<?php echo Page()->aHost . Page()->controller ?>/first-page/">1. lapas saturs</a></li><?php } ?>
		</ul>
	</nav>
	<div>
		<section class="block" id="node-list">
			<?php if ($_SESSION['post_response']) { ?>
				<div class="alert alert-<?= $_SESSION['post_response'][1] ?> alert-dismissable">
					<button type="button" class="close" data-dismiss="alert" aria-label="">
						<span aria-hidden="true">×</span></button>
					<p><?php echo $_SESSION['post_response'][0] ?></p>
				</div>
				<?php unset($_SESSION['post_response']);
			} ?>
			<?php foreach (Page()->languages as $language) { ?><?php if (in_array(Page()->action, Page()->languages) && $language != Page()->action) continue;
				$root = &$roots[ $language ];
				?>
				<div class="panel panel-default">
					<div class="panel-heading">
						<h4 class="flag panel-title <?php echo $language ?>"><?php echo $root->title ?></h4>
						<ul class="nav nav-tabs panel-controls">
							<li>
								<?php if (ActiveUser()->can(Page()->controller, "mainīt lapas statusu")) { ?>
									<span class="page_status">
										<label for="page_enabled_<?php echo $language ?>" style="display: inline-block;">Lapa šajā valodā publicēta:</label>
										<input id="page_enabled_<?php echo $language ?>" type="checkbox" value="<?php echo $language ?>" class="selector page_status"<?php echo Settings()->get("site_enabled", $language) ? " checked" : "" ?> />
									</span>
								<?php } ?>
							</li>
							<li class="dropdown">
								<a class="dropdown-toggle btn-sm" data-toggle="dropdown" href="#" role="button" aria-expanded="false">
									<span class="glyphicon glyphicon-cog"></span> <span class="caret"></span>
								</a>
								<ul class="dropdown-menu dropdown-menu-right" role="menu">
									<?php if (ActiveUser()->canWrite("node", $root->id)) { ?>
										<li>
											<a href="<?php echo Page()->adminHost . Page()->controller . '/settings/' . $root->id ?>/">Galvenās lapas uzstādījumi</a>
										</li>
									<?php } ?>
									<?php foreach (Page()->cC["rootCogItems"] as $itemPath => $itemName) { ?>
										<li>
											<a href="<?php echo Page()->adminHost . Page()->controller . '/' . $itemPath . '/' . $root->id ?>/"><?php print($itemName); ?></a>
										</li>
									<?php } ?>
								</ul>
							</li>
						</ul>
					</div>
					<div class="burzum-treeview panel-body"><?php print_tree($root, true); ?></div>
					<?php if (ActiveUser()->can(Page()->controller, "pievienot sadaļu")) { ?>
						<div class="panel-footer clearfix">
							<div class="btn-group pull-right">
								<a href="<?php echo Page()->adminHost . Page()->controller ?>/settings/<?php echo $language ?>" class="btn btn-primary btn-sm pull-right" role="button">Pievienot sadaļu</a>
							</div>
						</div>
					<?php } ?>
				</div>
			<?php } ?>
		</section>
	</div>
	<script type="text/javascript">
		$(function() {
			$(document).on("click", ".burzum-treeview a.basicedit", function(e) {
				e.preventDefault();
				e.stopPropagation();
				$($.parseHTML("<div/>")).append($.parseHTML('<span class="loading"></span>'))
				                        .attr({id: "basic_settings"}).dialog({
					minWidth : 700,
					maxHeight: 500,
					position : ["auto", 190],
					title    : "Uzstādījumi",
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
					cmsConfirm(<?php echo json_encode("Vai tiešām vēlies mainīt lapas statusu?") ?>, function(answer) {
						if (answer) {
							setTimeout(function() {
								$.post('<?php echo Page()->fullRequestUri ?>', {
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