<?php

	Page()->header();
	Page()->incl(Page()->controllers[ Page()->controller ]->getPath() . "sidebar.php");

	$entries = DataBase()->getRows("SELECT * FROM %s ORDER BY `time` DESC LIMIT %d, %d", DataBase()->log, 0, 50);

?>
	<section class="block">
		<table class="table table-condensed">
			<thead>
				<tr>
					<th width="50">Laiks</th>
					<th width="50">IP adrese</th>
					<th width="50">Lietotājs</th>
					<th>Notikums</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($entries as $k => $entry) {
					$entry["other_data"] = DataBase()->getJSON($k, "other_data");
					?>
					<tr class="<?php print($entry["other_data"][0] == "failed" ? "danger" : ""); ?>">
						<td style="white-space: nowrap;"><?php print($entry["time"]); ?></td>
						<td style="white-space: nowrap;"><?php print($entry["ip"]); ?></td>
						<td style="white-space: nowrap;"><?php print(Users()->getUser($entry["user"])->getName()); ?></td>
						<td><?php print(htmlspecialchars($entry["message"])); ?></td>
					</tr>
				<?php } ?>
			</tbody>
		</table>
	</section>
<?php

	Page()->footer();
?>