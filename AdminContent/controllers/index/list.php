<?php //ā

	Page()->addBreadcrumb("Kopsavilkums", Page()->adminHost);
	Page()->header();

?>
<script src="<?php print(Page()->bHost); ?>js/jqplot/jquery.jqplot.min.js" type="text/javascript"></script>
<script src="<?php print(Page()->bHost); ?>js/jqplot/plugins/jqplot.canvasAxisTickRenderer.js" type="text/javascript"></script>
<script src="<?php print(Page()->bHost); ?>js/jqplot/plugins/jqplot.dateAxisRenderer.js" type="text/javascript"></script>
<script src="<?php print(Page()->bHost); ?>js/jqplot/plugins/jqplot.canvasTextRenderer.js" type="text/javascript"></script>
<script src="<?php print(Page()->bHost); ?>js/jqplot/plugins/jqplot.canvasAxisLabelRenderer.js" type="text/javascript"></script>
<script src="<?php print(Page()->bHost); ?>js/jqplot/plugins/jqplot.enhancedLegendRenderer.js" type="text/javascript"></script>
<script src="<?php print(Page()->bHost); ?>js/jqplot/plugins/jqplot.highlighter.js" type="text/javascript"></script>
<script src="<?php print(Page()->bHost); ?>js/jqplot/plugins/jqplot.cursor.js" type="text/javascript"></script>
<link rel="stylesheet" href="<?php print(Page()->bHost); ?>css/jquery.jqplot.min.css">
<!--[if lte IE 8]>
<script language="javascript" type="text/javascript" src="<?php echo Page()->adminBaseHost?>/js/jqplot/excanvas.js"></script><![endif]-->
<nav class="sidebar">


	<ul class="sections">
		<?php /*<li><a class="home active" href="#">Kopsavilkums</a></li>*/ ?>
		<li><a class="stats" href="#stats-summary">{{Index: Statistics}}</a></li>
		<li><a class="stats" href="<?php echo Page()->adminHost ?>index/map">{{Index: MapStatistics}}</a></li>
	</ul>
	<ul class="actions">
		<li>
			<a href="<?php echo Page()->adminHost ?>users/edit/<?php echo ActiveUser()->id ?>/">{{Index: Edit user info}}</a>
		</li>
	</ul>
</nav>

<section class="block" id="stats-summary">
	<h1 class="icon chart">{{Index: Statistics summary}}</h1>
	<div class="l-w"><span class="loading">{{Loading}}…</span></div>
</section>

<script type="text/javascript">
	$(function() {
		$('#stats-summary').load('<?php echo Page()->adminHost . Page()->controller?>/get.stats');
		$.jsDate.regional.lv          = {
			monthNames     : [
				'Janvāris', 'Februāris', 'Marts', 'Aprīlis', 'Maijs', 'Jūnijs', 'Jūlijs', 'Augusts', 'Septembris',
				'Oktobris', 'Novembris', 'Decembris'
			],
			monthNamesShort: ['Jan', 'Feb', 'Mar', 'Apr', 'Mai', 'Jūn', 'Jūl', 'Aug', 'Sep', 'Okt', 'Nov', 'Dec'],
			dayNames       : [
				'Svētdiena', 'Pirmdiena', 'Otrdiena', 'Trešdiena', 'Ceturtdiena', 'Piektdiena', 'Sestdiena'
			],
			dayNamesShort  : ['Sv', 'Pr', 'Ot', 'Tr', 'Ce', 'Pk', 'Se'],
			formatString   : '%m.%d.%Y %H:%M:%S'
		};
		$.jsDate.config.defaultLocale = "lv";
	});
</script>
<?php
	Page()->footer();
?>
