<?php

	session_write_close();

	ini_set("display_errors", "On");
	error_reporting(E_ALL ^ E_NOTICE);
	define('sortMetric', '-visits');
	define('filter', 'ga:source==(direct)');
	define('startIndex', 1);
	define('maxResult', (int)$_GET['inp'] ? (int)$_GET['inp'] : 20);
	$dimensionsL = array('landingPagePath');
	$metricsL = array('visits', 'pageviews', 'pageviewsPerVisit', 'avgTimeOnSite', 'percentNewVisits', 'visitBounceRate');
	function gd($s) {
		return substr($s, 0, 4) . "-" . substr($s, 4, 2) . "-" . substr($s, 6, 2);
	}

	if ($_GET['period'] == "day") {
		$sorting = array("date", "hour");
		$dimensions = array("hour", "date");
	} else {
		$sorting = "date";
		$dimensions = array("date");
	}

	$ga_auth = Page()->getOption("stats_ga_auth", array("started" => time(), "token" => null));
	if (!$ga_auth['token'] || $ga_auth['started'] < strtotime('1 day ago')) $ga_auth = array("started" => time(), "token" => null);

	if (!Settings("ga_service_email")) {
		die('<section class="alert alert-info">
			<strong>Informācija</strong>
			<p>Lai varētu vākt un atspoguļot statistiku, nepieciešams aizpildīt <a href="' . Page()->aHost . 'cpanel/stats/" class="alert-link"><em>Google Analytics</em> uzstādījumus</a>.</p>
		</section>');
	}

	$ga = new gapi(Settings("ga_service_email"), Page()->path . Settings("ga_service_p12"));
	$filter = '';

	if ($_GET['period'] == "day") {
		$from1 = date("Y-m-d");
		$to1 = date("Y-m-d");
		$from2 = date("Y-m-d", strtotime("yesterday"));
		$to2 = date("Y-m-d", strtotime("yesterday"));
	} else {
		$from1 = date("Y-m-d", strtotime("1 " . ($_GET['period'] ?: "month") . " ago"));
		$to1 = date("Y-m-d", strtotime("today"));
		$from2 = date("Y-m-d", strtotime("2 " . ($_GET['period'] ?: "month") . " ago"));
		$to2 = date("Y-m-d", strtotime("1 " . ($_GET['period'] ?: "month") . " ago"));
	}

	try {
		$ga->requestReportData((int)Settings("ga_profile_id"), $dimensions, array('pageviews', 'visits', 'visitors', 'avgTimeOnSite'), $sorting, null, $from1, $to1, 1, 366);
	} catch (Exception $e) {
		$ga->requestReportData((int)Settings("ga_profile_id"), $dimensions, array('pageviews', 'visits', 'visitors', 'avgTimeOnSite'), $sorting, null, $from1, $to1, 1, 366);
	}

	$pageviews = $ga->getPageviews();
	$visits = $ga->getVisits();
	$visitors = $ga->getVisitors();
	$timeonsite = $ga->getavgTimeOnSite();

	foreach ($ga->getResults() as $result) {
		$data_visitors[] = array($_GET['period'] == "day" ? gd($result->getDate()) . ' ' . $result->getHour() . ':00' : gd($result->getDate()), $result->getVisitors());
		$data_views[] = array($_GET['period'] == "day" ? gd($result->getDate()) . ' ' . $result->getHour() . ':00' : gd($result->getDate()), $result->getPageViews());
	}

	$ga->requestReportData((int)Settings("ga_profile_id"), $dimensions, array('pageviews', 'visits', 'visitors', 'avgTimeOnSite'), $sorting, null, $from2, $to2, 1, 0);

	$ppageviews = $ga->getPageviews();
	$pvisits = $ga->getVisits();
	$pvisitors = $ga->getVisitors();
	$ptimeonsite = $ga->getavgTimeOnSite();

	if ($ppageviews) $dpageviews = round(($pageviews > $ppageviews ? ($pageviews - $ppageviews) / $ppageviews : 1 - $pageviews / $ppageviews) * 100);
	if ($pvisits) $dvisits = round(($visits > $pvisits ? ($visits - $pvisits) / $pvisits : 1 - $visits / $pvisits) * 100);
	if ($pvisitors) $dvisitors = round(($visitors > $pvisitors ? ($visitors - $pvisitors) / $pvisitors : 1 - $visitors / $pvisitors) * 100);
	if ($ptimeonsite) $dtimeonsite = round(($timeonsite > $ptimeonsite ? ($timeonsite - $ptimeonsite) / $ptimeonsite : 1 - $timeonsite / $ptimeonsite) * 100);

?>

<div>
	<h1 class="icon chart">{{Index: Statistics summary}}</h1>
	<div class="btn-group pull-right" id="period_selector">
		<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
			{{Index: Period}}: <?php
				if ($_GET['period'] == "year") {
					echo "{{Year}}";
				} else if ($_GET['period'] == "week") {
					echo "{{Week}}";
				} else if ($_GET['period'] == "day") {
					echo "{{Today}}";
				} else echo "{{Month}}";
			?> <span class="caret"></span>
		</button>
		<ul class="dropdown-menu" role="menu">
			<li><a href="<?php echo Page()->adminHost . Page()->controller ?>/get.stats?period=day">{{Today}}</a></li>
			<li><a href="<?php echo Page()->adminHost . Page()->controller ?>/get.stats?period=week">{{Week}}</a></li>
			<li><a href="<?php echo Page()->adminHost . Page()->controller ?>/get.stats?period=month">{{Month}}</a></li>
			<li><a href="<?php echo Page()->adminHost . Page()->controller ?>/get.stats?period=year">{{Year}}</a></li>
		</ul>
	</div>
	<table class="stats-summary-table" style="clear:both;width:100%;">
		<tr>
			<td>
				<span>{{GA: Views}}:</span>
				<strong><?php echo $pageviews ?></strong>
				<?php if ($dpageviews) { ?>
					<sub class="<?php echo $pageviews < $ppageviews ? 'down' : 'up' ?>" title="Iepriekšējā periodā: <?php echo $ppageviews ?>"><?php echo $dpageviews ?>%</sub><?php } else { ?>
					<sub class="up">0%</sub><?php } ?>
			</td>
			<td>
				<span>{{GA: Visits}}:</span>
				<strong><?php echo $visits ?></strong>
				<?php if ($dvisits) { ?>
					<sub class="<?php echo $visits < $pvisits ? 'down' : 'up' ?>" title="Iepriekšējā periodā: <?php echo $pvisits ?>"><?php echo $dvisits ?>%</sub><?php } else { ?>
					<sub class="up">0%</sub><?php } ?>
			</td>
			<td>
				<span>{{GA: Visitors}}:</span>
				<strong><?php echo $visitors ?></strong>
				<?php if ($dvisitors) { ?>
					<sub class="<?php echo $visitors < $pvisitors ? 'down' : 'up' ?>" title="Iepriekšējā periodā: <?php echo $pvisitors ?>"><?php echo $dvisitors ?>%</sub><?php } else { ?>
					<sub class="up">0%</sub><?php } ?>
			</td>
			<td>
				<span>{{GA: Time wasted}}:</span>
				<strong><?php echo $Com->getDuration($timeonsite) ?></strong>
				<?php if ($dtimeonsite) { ?>
					<sub class="<?php echo $timeonsite < $ptimeonsite ? 'down' : 'up' ?>" title="Iepriekšējā periodā: <?php echo $Com->getDuration($ptimeonsite) ?>"><?php echo $dtimeonsite ?>%</sub><?php } else { ?>
					<sub class="up">0%</sub><?php } ?>
			</td>
		</tr>
	</table>
</div>
<div id="placeholder" style="width:750px;height:300px"></div><p id="hoverdata"></p>

<script type="text/javascript">
	$(function() {
		$('#period_selector, #inp').on('click', 'a', function(e) {
			e.preventDefault();
			$('#stats-summary').css({height: $('#stats-summary').height()})
			                   .html('<h1 class="icon chart">{{Index: Statistics summary}}</h1><div class="l-w"><span class="loading">{{Loading...}}</span></div>')
			                   .load($(this).attr('href'), function() {$('#stats-summary').css({height: 'auto'})});
		});
		var line1                     = <?php echo json_encode($data_visitors)?>;
		var line3                     = <?php echo json_encode($data_views)?>;
		$.jqplot.config.enablePlugins = true;
		window.plot                   = $.jqplot('placeholder', [line3, line1], {
			title         : '{{GA: Stats about visits}}',
			seriesColors  : ["#007abe", "#69204c"],
			fontSize      : '10pt',
			series        : [
				{label: "{{GA: Views}}"},
				{label: "{{GA: Visitors}}"}
			],
			legend        : {
				show           : true,
				placement      : 'insideGrid',
				renderer       : $.jqplot.EnhancedLegendRenderer,
				rendererOptions: {
					fontSize: '10pt'
				}
			},
			axes          : {
				xaxis: {
					renderer       : $.jqplot.DateAxisRenderer,
					rendererOptions: {
						tickRenderer: $.jqplot.CanvasAxisTickRenderer
					},
					tickOptions    : {
						<?php if ($_GET['period'] == "day") { ?>formatString: "%H:%M"
						<?php } else { ?>formatString                       : "%#d. %b"<?php } ?>
					},
					min            : line1[0][0],
					max            : line1[line1.length - 1][0]
				},
				yaxis: {
					rendererOptions: {
						tickRenderer: $.jqplot.CanvasAxisTickRenderer
					},
					tickOptions    : {
						formatString: "%d"
					},
					min            : 0,
					pad            : 1.2
				}
			},
			highlighter   : {
				show              : false,
				bringSeriesToFront: true
			},
			grid          : {
				gridLineColor: '#f0f0f0',
				background   : '#fff',
				borderColor  : '#ccc',
				borderWidth  : 0,
				shadow       : false,
			},
			seriesDefaults: {
				lineWidth    : 3,
				shadow       : false,
				markerOptions: {size: 9}
			},
			cursor        : {
				zoom                    : true,
				showTooltip             : true,
				showVerticalLine        : true,
				useAxesFormatters       : true,
				showTooltipDataPosition : true,
				followMouse             : true,
				cursorLegendFormatString: '%1$s: %3$s',
				tooltipFormatString     : '%1$s: %3$s',
				tooltipLocation         : 'nw'
			}
		});
	});
</script>
<div class="clear"></div>
<hr>
<section class="block">
	<div class="btn-group pull-right" id="inp">
		<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
			{{Index: InPage}}: <?php
				if ($_GET['inp']) echo (int)$_GET['inp'];
			?> <span class="caret"></span>
		</button>
		<ul class="dropdown-menu" role="menu">
			<li><a href="<?php echo Page()->adminHost . Page()->controller ?>/get.stats?inp=100">100</a></li>
			<li><a href="<?php echo Page()->adminHost . Page()->controller ?>/get.stats?inp=200">200</a></li>
			<li><a href="<?php echo Page()->adminHost . Page()->controller ?>/get.stats?inp=500">500</a></li>
			<li><a href="<?php echo Page()->adminHost . Page()->controller ?>/get.stats?inp=1000">1000</a></li>
		</ul>
	</div>
	<h1 class="icon hierarchy">{{Index: link stats}}</h1>

	<table width="100%" class="table table-condensed table-hover">
		<thead>
			<tr>

				<th>{{Index: link}}</th>
				<th width="30">{{Index: pageviews}}</th>
				<th width="30">{{Index: total visits}}</th>
				<th width="30">{{Index: avgTimeOnSite}}</th>
				<th width="1"></th>
			</tr>
		</thead>
		<tbody>
			<?php
				$ga->requestReportData((int)Settings("ga_profile_id"), $dimensionsL, $metricsL, sortMetric, filter, $from1, $to1, startIndex, maxResult);
				foreach ($ga->getResults() as $result) {
					$visits = $result->getVisits();
					$pageviewsPerVisit = $result->getpageviewsPerVisit();
					$pageviews = $result->getpageviews();
					$timeonsite = $result->getavgTimeOnSite();
					$precentNW = $result->getpercentNewVisits();
					$bounceRate = $result->getvisitBounceRate();
					$path = $result->getlandingPagePath(); ?>
					<tr>

						<td style="word-break: break-word;"><?php echo $path; ?></td>
						<td class="num"><?php echo $pageviews; ?></td>
						<td class="num"><?php echo $visits; ?></td>
						<td class="actions"><?php echo $Com->getDuration($timeonsite); ?></td>

					</tr>
				<?php } ?>
		</tbody>
	</table>
</section>