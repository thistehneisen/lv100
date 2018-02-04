<?php
	Page()->addBreadcrumb("Kopsavilkums", Page()->adminHost);
	Page()->header();

	$ga_auth = Page()->getOption("stats_ga_auth", array("started" => time(), "token" => null));
	if (!$ga_auth['token'] || $ga_auth['started'] < strtotime('1 day ago')) $ga_auth = array("started" => time(), "token" => null);

?>
	<nav class="sidebar">
		<ul class="sections">
			<li><a class="stats" href="<?php echo Page()->adminHost ?>index/">{{Index: Statistics}}</a></li>
			<li><a class="stats" href="<?php echo Page()->adminHost ?>index/map">{{Index: MapStatistics}}</a></li>
		</ul>
		<ul class="actions">
			<li>
				<a href="<?php echo Page()->adminHost ?>users/edit/<?php echo ActiveUser()->id ?>">{{Index: Edit user info}}</a>
			</li>
		</ul>
	</nav>

	<section class="block pull-right clearfix">
		<?php
			if (!Settings("ga_service_email")) {
				die('<section class="alert alert-info">
			<strong>Informācija</strong>
			<p>Lai varētu vākt un atspoguļot statistiku, nepieciešams aizpildīt <a href="' . Page()->aHost . 'cpanel/stats/" class="alert-link"><em>Google Analytics</em> uzstādījumus</a>.</p>
		</section>');
			}
			$ga = new gapi(Settings("ga_service_email"), Page()->path . Settings("ga_service_p12"));
			$metrics = array('visits');
			$dimensions = array('country');
			$ga->requestReportData((int)Settings("ga_profile_id"), $dimensions, $metrics, null, null, date("Y-m-d", strtotime("-1 year")), date("Y-m-d"), 1, 366);

			$data = array(array('Country', 'Visits'));
			foreach ($ga->getResults() as $result) {
				$visits = $result->getVisits();
				$country = $result->getCountry();
				$data[] = array($country, $visits);
			}
		?>
		<div id="chart_div" style="width: 100%; height: 500px;"></div>
	</section>
	<script type='text/javascript' src='https://www.google.com/jsapi'></script>
	<script type='text/javascript'>
		google.load('visualization', '1', {'packages': ['geochart']});
		google.setOnLoadCallback(drawRegionsMap);

		function drawRegionsMap() {
			var data = google.visualization.arrayToDataTable(<?php echo json_encode($data); ?>);

			var options = {};

			var chart = new google.visualization.GeoChart(document.getElementById('chart_div'));
			chart.draw(data, options);
		}
		;
	</script>
<?php
	Page()->footer();
?>