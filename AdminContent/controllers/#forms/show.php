<?php

	if ($_GET["delete"]) {
		DataBase()->queryf("DELETE FROM %s WHERE `id`='%d'", DataBase()->forms_data, $_GET["delete"]);
		header("Location: {$_SERVER["HTTP_REFERER"]}");
		exit;
	}

	$ojd = DataBase()->json_decode;
	DataBase()->json_decode = true;
	$form = DataBase()->getRow("SELECT * FROM %s WHERE `id`='%d'", DataBase()->forms, Page()->reqParams[0]);
	$data = DataBase()->getRows("SELECT * FROM %s WHERE `form_id`='%d' ORDER BY `time` DESC", DataBase()->forms_data, Page()->reqParams[0]);
	DataBase()->json_decode = $ojd;

	function customjoin($sep, $arr) {
		$x = 0;
		$str = "";
		foreach ($arr as $val) {
			if (!empty($val)) {
				$str .= ($x > 0 ? $sep : '') . $val;
				$x++;
			}
		}

		return $str;
	}

	Page()->fluid = true;
	Page()->header();
?>
	<header class="row text-center">
		<div class="col-xs-10">
			<a href="<?php print($_GET["referer"] ? $_GET["referer"] : $_SERVER["HTTP_REFERER"]); ?>" class="btn btn-lg btn-primary btn-back pull-left" role="button">{{Back}}</a>
			<h4 class="icon page"><?php print($form["title"]); ?></h4>
		</div>
		<div class="col-xs-2 right">
			<a href="<?php echo Page()->aHost . Page()->controller ?>/export/<?php echo Page()->reqParams[0]; ?>" class="btn btn-default ajaxExport icon excel" style="float: right;">Eksportēt</a>
		</div>
	</header>
	<section class="row">
		<div class="col-xs-12 table-responsive">
		<table width="100%" cellpadding="0" cellspacing="0" class="table table-condensed">
			<thead>
				<tr>
					<th width="150"></th>
					<th width="115">Laiks</th>
					<?php $cols = 2; foreach ($form["fields"] as $field) {
						if (!$field["show"] || !$field["showlist"] || $field["deleted"]) continue; $cols++; ?>
						<th><?= $field["title"] ?></th>
					<?php } ?>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($data as $datarow) { ?>
					<tr class="summary">
						<td class="left buttons">
							<a class="btn btn-xs btn-danger" href="<?php echo Page()->fullRequestUri(array("delete" => $datarow["id"], "referer" => ($_GET["referer"] ? $_GET["referer"] : $_SERVER["HTTP_REFERER"]))); ?>" data-confirm="Tiešām vēlies dzēst šo ierakstu?">Dzēst</a>
							<a class="btn btn-xs btn-default show-details" href="#">Detaļas</a>
						</td>
						<td><?= $datarow["time"] ?></td>
						<?php foreach ($form["fields"] as $field) {
							if (!$field["show"] || !$field["showlist"] || $field["deleted"]) continue;
							if (is_array($field["values"]) && count($field["values"])) {
								if (!is_array($datarow[ "f_" . $field["id"] ])) $datarow[ "f_" . $field["id"] ] = array($datarow[ "f_" . $field["id"] ]);
								$z = 0;
								foreach ($datarow[ "f_" . $field["id"] ] as $key => $value) {
									foreach ($field["values"] as $fvalue) {
										if ($fvalue["id"] == $value && $field["subtype"] != "custom1") {
											$datarow[ "f_" . $field["id"] ][ $key ] = $fvalue["text"];
											break;
										} else if ($fvalue["id"] == $key && $field["subtype"] == "custom1") {
											if ($value) $datarow[ "f_" . $field["id"] ][ $key ] = '<nobr>' . $value . " - " . $fvalue["text"] . '</nobr>';
											break;
										}
									}
									$z++;
								}
								$datarow[ "f_" . $field["id"] ] = customjoin(", ", $datarow[ "f_" . $field["id"] ]);
							}
							if ($field["type"] == "input" && $field["subtype"] == "file" && is_file(Page()->path . $datarow[ "f_" . $field["id"] ])) {
								$datarow[ "f_" . $field["id"] ] = '<a href="' . Page()->host . $datarow[ "f_" . $field["id"] ] . '" target="_blank">Skatīt (' . pathinfo($datarow[ "f_" . $field["id"] ], PATHINFO_EXTENSION) . ')</a>';
							}
							?>
							<td><?= nl2br($datarow[ "f_" . $field["id"] ]) ?></td>
						<?php } ?>
					</tr>
					<tr class="details">
						<td colspan="<?php print($cols); ?>">
							<div>
								<?php foreach ($form["fields"] as $field) {
									if (!$field["show"] || $field["deleted"]) continue;
									if (is_array($field["values"]) && count($field["values"])) {
										if (!is_array($datarow[ "f_" . $field["id"] ])) $datarow[ "f_" . $field["id"] ] = array($datarow[ "f_" . $field["id"] ]);
										$z = 0;
										foreach ($datarow[ "f_" . $field["id"] ] as $key => $value) {
											foreach ($field["values"] as $fvalue) {
												if ($fvalue["id"] == $value && $field["subtype"] != "custom1") {
													$datarow[ "f_" . $field["id"] ][ $key ] = $fvalue["text"];
													break;
												} else if ($fvalue["id"] == $key && $field["subtype"] == "custom1") {
													if ($value) $datarow[ "f_" . $field["id"] ][ $key ] = '<nobr>' . $value . " - " . $fvalue["text"] . '</nobr>';
													break;
												}
											}
											$z++;
										}
										$datarow[ "f_" . $field["id"] ] = customjoin(", ", $datarow[ "f_" . $field["id"] ]);
									}
									if ($field["type"] == "input" && $field["subtype"] == "file" && is_file(Page()->path . $datarow[ "f_" . $field["id"] ])) {
										$datarow[ "f_" . $field["id"] ] = '<a href="' . Page()->host . $datarow[ "f_" . $field["id"] ] . '" target="_blank">Skatīt (' . pathinfo($datarow[ "f_" . $field["id"] ], PATHINFO_EXTENSION) . ')</a>';
									}
									?>
									<div class="row">
										<div class="col-xs-3 right"><strong><?= $field["title"] ?>: </strong></div>
										<div class="col-xs-9"><?= nl2br($datarow[ "f_" . $field["id"] ]) ?></div>
									</div>
								<?php } ?>
							</div>
						</td>
					</tr>
				<?php } ?>
			</tbody>
		</table>
		</div>
	</section>
	<style type="text/css">
		tr.details td {
			padding: 0!important;
		}
		tr.details a {
			color: RGB(0, 141, 198);
			text-decoration: underline;
		}
		tr.details td > div {
			padding: 5px;
			border-bottom: 2px solid #ddd;
			display: none;
		}
		tr.summary {
			font-size: 10px;
		}
		tr.summary .buttons a {
			visibility: hidden;
		}
		tr.summary:hover .buttons a {
			visibility: visible;
		}
		tr th {
			font-weight: bold;
			text-transform: uppercase;
			font-size: 10px;
		}
	</style>
	<script type="application/javascript">
		$(function(){
			$(".show-details").on("click",function(e){
				e.preventDefault();
				var target = $(this).closest("tr.summary").next("tr.details").find("td > div");
				if (target.is(":visible")) target.slideUp("fast");
				else target.slideDown("fast");
			});
		});
	</script>
<?php
	Page()->footer();
?>