<?php

	if (!ActiveUser()->can(Page()->controller, "skatīt")) {
		Page()->accessDenied();
	}

	Page()->addBreadcrumb("Komentāri", Page()->aHost . Page()->controller . "/?sid=" . $_GET["sid"]);

	if ($_POST["confirm"] && ActiveUser()->can("comments", "labot")) {
		DataBase()->update("comments", array(
			"enabled"        => 1,
			"time_published" => strftime("%F %X")
		), array(
			"id" => $_POST["confirm"]
		));
		$_SESSION["post_response"] = array("Komentārs publicēts.", "success");
		echo "ok";
		exit;
	}
	if ($_POST["save"] && ActiveUser()->can("comments", "labot")) {
		DataBase()->update("comments", array(
			"body" => $_POST["text"]
		), array(
			"id" => $_POST["save"]
		));
		$_SESSION["post_response"] = array("Komentārs saglabāts.", "success");
		echo "ok";
		exit;
	}
	if ($_POST["bulk"] && ActiveUser()->can("comments", "labot")) {
		$settings = array();
		$message = "";
		if (count($_POST["ids"])) {
			switch ($_POST["bulk"]) {
				case "hide":
					$settings["enabled"] = 0;
					DataBase()->update("comments", $settings, array(
						"id" => array_map(function ($n) { return (int)$n; }, $_POST["ids"])
					));
					print_r(array_map(function ($n) { return (int)$n; }, $_POST["ids"]));
					$message = "Atzīmētie komentāri ir paslēpti!";
					break;
				case "confirm":
					$settings["enabled"] = 1;
					$settings["time_published"] = strftime("%F %X");
					DataBase()->update("comments", $settings, array(
						"id" => array_map(function ($n) { return (int)$n; }, $_POST["ids"])
					));
					$message = "Atzīmētie komentāri ir publicēti!";
					break;
				case "delete":
					DataBase()->queryf("DELETE FROM %s WHERE `id` IN (%s)", DataBase()->comments, join(",", array_map(function ($n) { return (int)$n; }, $_POST["ids"])));
					$message = "Atzīmētie komentāri ir dzēsti!";
					break;
			}

			$_SESSION["post_response"] = array($message, "success");
		}
		echo "ok";
		exit;
	}
	if ($_POST["hide"] && ActiveUser()->can("comments", "labot")) {
		DataBase()->update("comments", array(
			"enabled" => 0
		), array(
			"id" => $_POST["hide"]
		));
		$_SESSION["post_response"] = array("Komentārs paslēpts.", "success");
		echo "ok";
		exit;
	}
	if ($_POST["remove"] && ActiveUser()->can("comments", "labot")) {
		DataBase()->queryf("DELETE FROM %s WHERE `id`='%s'", DataBase()->comments, $_POST["remove"]);
		$_SESSION["post_response"] = array("Komentārs dzēsts.", "success");
		echo "ok";
		exit;
	}
	if ($_POST["block"] && ActiveUser()->can("comments", "labot")) {
		$comment = DataBase()->getRow("SELECT * FROM %s WHERE `id`='%s'", DataBase()->comments, $_POST["block"]);
		DataBase()->update("comments", array(
			"enabled" => 0
		), array(
			"id" => $_POST["hide"]
		));
		$blockedUsers = Settings()->get("blocked_users");
		if (!is_array($blockedUsers)) $blockedUsers = array();
		$alreadyBlocked = false;
		foreach ($blockedUsers as $user) {
			if ($user["type"] == $comment["account_type"] && $user["id"] == $comment["account_id"]) {
				$alreadyBlocked = true;
				break;
			}
		}
		if (!$alreadyBlocked) {
			$blockedUsers[] = array("type" => $comment["account_type"], "id" => $comment["account_id"]);
			Settings()->set("blocked_users", $blockedUsers);
		}
		$_SESSION["post_response"] = array("Lietotājs bloķēts.", "success");
		echo "ok";
		exit;
	}
	Page()->header();

	DataBase()->countResults = true;
	$comments = DataBase()->getRows("SELECT * FROM %s ORDER BY `time_added` DESC LIMIT %d,%d", DataBase()->comments, Page()->pageCurrent * 50, 50);
	$totalentries = DataBase()->resultsFound;

	Page()->fluid = true;
?>
	<div class="block" id="container">
		<div id="content">
			<?php if ($_SESSION['post_response']) { ?>
				<div class="alert alert-<?= $_SESSION['post_response'][1] ?> alert-dismissable">
					<button type="button" class="close" data-dismiss="alert" aria-label="Close">
						<span aria-hidden="true">&times;</span></button>
					<p><?php echo $_SESSION['post_response'][0] ?></p>
				</div>
				<?php unset($_SESSION['post_response']);
			} ?>
			<div class="panel panel-default">
				<div class="panel-heading clearfix">
					<h3 class="panel-title">Komentāri </h3>
				</div>
				<div class="panel-body">
					<?php if (count($comments)) { ?>
						<table width="100%" class="table table-condensed table-striped table-hover">
							<thead>
								<tr>
									<?php if (ActiveUser()->can("comments", "labot")) { ?>
										<th width="1"><input type="checkbox"></th>
									<?php } ?>
									<th width="175">Vārds</th>
									<th width="175">Laiks</th>
									<th width="200">Ieraksts</th>
									<th>Komentārs</th>
									<th width="100">Statuss</th>
									<th width="1"></th>
								</tr>
							</thead>
							<tbody>
								<?php
									foreach ($comments as $comment) {
										$body = strip_tags($comment["body"]);
										if (mb_strlen($body) > "120") $body = substr($body, 0, 120) . "...";
										$node = Page()->getNode(array(
											"filter"        => array(
												"id" => $comment["sid"]
											),
											"returnFields"  => "title,id,fullAddress",
											"returnResults" => "first"
										));
										$comment["body"] = htmlspecialchars(strip_tags($comment["body"]));
										$comment["body_original"] = htmlspecialchars($comment["body_original"]);
										$comment["name"] = htmlspecialchars(strip_tags($comment["name"]));
										$comment["node"] = $node;
										?>
										<tr>
											<?php if (ActiveUser()->can("comments", "labot")) { ?>
												<td><input type="checkbox"></td>
											<?php } ?>
											<td><?php Page()->e(strip_tags($comment["name"]), 1); ?></td>
											<td><?php print($comment["time_added"]); ?></td>
											<td>
												<?php if ($node) { ?>
													<a href="<?php print($node->fullAddress); ?>" target="entry"><?php print($node->title); ?></a><?php } else { ?>
													<em>Ieraksts dzēsts</em>
												<?php } ?>
											</td>
											<td><?php Page()->e(mb_substr($comment["body"], 0, 140) . (mb_strlen($comment["body"]) > 140 ? '...' : ''), 1); ?></td>
											<td><?php print($comment["enabled"] ? "Publicēts" : "Gaida apstiprinājumu"); ?></td>
											<td class="actions">
												<a href="#" data-toggle="modal" data-target="#details" class="show-details btn btn-xs btn-default" data-details="<?php Page()->e(json_encode($comment), 1); ?>">Detaļas</a>
											</td>
										</tr>
										<?php
									}
								?>
							</tbody>
						</table>
						<?php if (ActiveUser()->can("comments", "labot")) { ?>
							<div class="bulk-actions">
								Iezīmētos:
								<a href="#" class="btn btn-xs btn-danger" data-action="delete" disabled>Dzēst</a>
								<a href="#" class="btn btn-xs btn-success" data-action="confirm" disabled>Publicēt</a>
								<a href="#" class="btn btn-xs btn-warning" data-action="hide" disabled>Paslēpt</a>
							</div>
						<?php } ?><?php } else {
						Page()->cmsInfotip("Nav atrasti komentāri", "yellow", "", false);
					} ?>
				</div>
				<?php if (ceil($totalentries / 50) > 1) { ?>
					<div class="panel-footer">
						<nav>
							<ul class="pagination">
								<?php Page()->paging(array(
									"pages"            => ceil($totalentries / 50),
									"delta"            => 5,
									"echo"             => true,
									"page"             => '<li><a href="%1$s">%2$s</a></li>',
									"active"           => '<li><a href="%1$s" class="active">%2$d</a></li>',
									"prev"             => '<li><a href="%1$s" class="%3$s" aria-label="Iepriekšējā"><span aria-hidden="true">&laquo;</span></a></li>',
									"next"             => '<li><a href="%1$s" class="%3$s" aria-label="Nākamā"><span aria-hidden="true">&raquo;</span></a></li>',
									"dontShowInactive" => false
								)) ?>
							</ul>
						</nav>
					</div>
				<?php } ?>
			</div>
		</div>
	</div>
	<script type="text/javascript">
		$(function() {
			$(document).on("click", ".show-details", function() {
				var dialog  = $("#details");
				var details = $(this).data("details");
				dialog.data("id", details.id);
				dialog.find(".name").html(details.name);
				dialog.find(".body textarea").val(details.body);
				dialog.find(".body_original").html(details.body_original);
				dialog.find(".time").html(details.time_added);
				dialog.find(".time_pub").html(details.time_published);
				dialog.find(".status").html(details.enabled == 1 ? 'Publicēts' :
				                            (details.time_published == '0000-00-00 00:00:00' ? 'Gaida apstiprinājumu' :
				                             'Paslēpts'));
				dialog.find(".ip").html(details.ip);
				dialog.find(".post").empty()
				      .append($("<a><\/a>").attr("href", details.node.fullAddress).attr("target", "entry")
				                           .html(details.node.title));
				if (details.enabled == 1) {
					dialog.find(".do-confirm").addClass("hidden");
					dialog.find(".do-hide").removeClass("hidden");
				}
				else {
					dialog.find(".do-hide").addClass("hidden");
					dialog.find(".do-confirm").removeClass("hidden");
				}
			});
			$(".do-confirm").on("click", function() {
				$.post(document.location.href, {confirm: $("#details").data("id")}, function() {
					$("#container").load(document.location.href + ' #content');
					$("#details").modal("hide");
				});
			});
			$(".do-save").on("click", function() {
				$.post(document.location.href, {
					save: $("#details").data("id"),
					text: $("#comment-edit").val()
				}, function() {
					$("#container").load(document.location.href + ' #content');
					$("#details").modal("hide");
				});
			});
			$(".do-hide").on("click", function() {
				$.post(document.location.href, {hide: $("#details").data("id")}, function() {
					$("#container").load(document.location.href + ' #content');
					$("#details").modal("hide");
				});
			});
			$(".do-block").on("click", function() {
				$.post(document.location.href, {block: $("#details").data("id")}, function() {
					$("#container").load(document.location.href + ' #content');
					$("#details").modal("hide");
				});
			});
			$(".do-delete").on("click", function() {
				$.post(document.location.href, {remove: $("#details").data("id")}, function() {
					$("#container").load(document.location.href + ' #content');
					$("#details").modal("hide");
				});
			});
			$(document).on("change", "#content table thead th input", function(e) {
				$("#content table tbody td input").prop("checked", $(this).prop("checked"));
			}).on("change", "#content table tbody td input", function(e) {
				var totalChecked = $("#content table tbody td input:checked").length;
				var total        = $("#content table tbody td input").length;
				if (total != totalChecked) {
					if (totalChecked) {
						$("#content table thead th input").prop("indeterminate", true);
						$("#content .bulk-actions a").removeAttr("disabled");
					}
					else {
						$("#content table thead th input").prop("indeterminate", false).prop("checked", false);
						$("#content .bulk-actions a").attr("disabled", "true")
						                             .on("click", function(e) {e.preventDefault();});
					}
				}
				else {
					$("#content .bulk-actions a").removeAttr("disabled");
					$("#content table thead th input").prop("indeterminate", false).prop("checked", true);
				}
			}).on("click", "#content table tbody tr td", function(e) {
				if (!$(e.target).is("a") && !$(e.target).is("input")) {
					$(this).parent().find("input").prop("checked", !$(this).parent().find("input").prop("checked"))
					       .change();
				}
			}).on("click", "#content .bulk-actions a", function(e) {
				e.preventDefault();
				e.stopPropagation();
				if (!$(this).is("[disabled]")) {
					if ($("#content table tbody td input:checked").length) {
						var ids = $("#content tbody tr:has(input:checked) .actions a")
							.map(function() {return $(this).data("details").id; }).toArray();
						$.post(document.location.href, {bulk: $(this).data("action"), ids: ids}, function() {
							$("#container").load(document.location.href + ' #content');
						});
					}
				}
				else {
					cmsAlert("Vispirms izvēlies komentārus!");
				}
			});
		});
	</script>
	<div class="modal fade" tabindex="-1" role="dialog" id="details">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span></button>
					<h4 class="modal-title">Komentāra detaļas</h4>
				</div>
				<div class="modal-body" style="max-height:500px;overflow:auto;">
					<table class="table table-condensed table-bordered">
						<tr>
							<th width="30%">Pievienošanas laiks</th>
							<td class="time"></td>
						</tr>
						<tr>
							<th>Publicēšanas laiks</th>
							<td class="time_pub"></td>
						</tr>
						<tr>
							<th>Statuss</th>
							<td class="status"></td>
						</tr>
						<tr>
							<th>Ieraksts</th>
							<td class="post"></td>
						</tr>
						<tr>
							<th>Vārds</th>
							<td class="name"></td>
						</tr>
						<tr>
							<th>Komentārs</th>
							<td class="body"><textarea id="comment-edit"></textarea></td>
						</tr>
						<tr>
							<th>Oriģinālais</th>
							<td class="body_original"></td>
						</tr>
						<tr>
							<th>IP adrese</th>
							<td class="ip"></td>
						</tr>
					</table>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Aizvērt</button>
					<?php if (ActiveUser()->can("comments", "labot")) { ?>
						<button type="button" class="btn btn-sm btn-success do-save">Saglabāt</button>
						<button type="button" class="btn btn-sm btn-success do-confirm">Publicēt</button>
						<button type="button" class="btn btn-sm btn-warning do-hide">Paslēpt</button>
						<button type="button" class="btn btn-sm btn-danger do-block">Bloķēt lietotāju</button>
						<button type="button" class="btn btn-sm btn-danger do-delete">Dzēst komentāru</button>
					<?php } ?>
				</div>
			</div>
		</div>
	</div>
<?php Page()->footer(); ?>