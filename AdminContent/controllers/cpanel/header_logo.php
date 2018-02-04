<?php

	if (!ActiveUser()->isDev()) {
		Page()->accessDenied();
	}

	global $Header;

	if ($_POST) {
		$colorizer = array(
			"data" => array(
				"picture"    => $_POST['sharepic'],
				"title"      => $_POST['title'],
				"bg_color"   => $_POST['bg_color'],
				"logo_color" => $_POST['logo_color'],
				"favicon"    => $_POST['favicon']
			),

		);

		Settings()->set("favicon", $_POST['favicon'], null, null, true);
		Settings()->set("cms_title", $_POST['title'], null, null, true);

		Settings()->set("CMS.global.style", $colorizer);
		header("Location: {$_SERVER['HTTP_REFERER']}");
		exit;
	}

	Page()->addBreadcrumb("{{Settings}}", Page()->adminHost . Page()->controller);
	Page()->addBreadcrumb("{{Logo}}", Page()->adminHost . Page()->controller . "/header_logo");
	include('side.php'); ?>
<section class="block" id="content">
	<form action="<?php echo Page()->fullRequestUri ?>" method="post">
		<div class="panel panel-default">
			<div class="panel-heading">
				<h4 class="panel-title"><?= $action; ?></h4>
			</div>
			<div class="panel-body">

				<div class="row form-group">
					<div class="col-xs-6">
						<label class="control-label">{{Page Title:}}</label>
						<input class="form-control" id="title" name="title" type="text" value="<?php echo htmlspecialchars($Header['data']['title']) ?>" />
					</div>
					<div class="col-xs-6">
						<label class="control-label">{{Background color:}}</label>
						<input class="form-control" id="hex" name="bg_color" type="text" value="<?php echo htmlspecialchars($Header['data']['bg_color']) ?>" />
					</div>
				</div>
				<div class="row form-group">
					<div class="col-xs-6">
						<label class="control-label">{{Logo color:}}</label>
						<input class="form-control" id="logo_color" name="logo_color" type="text" value="<?php echo htmlspecialchars($Header['data']['logo_color']) ?>" />
					</div>
					<div class="col-xs-6" id="fav-c">
						<label class="control-label">{{Favicon:}}</label>
						<div>
							<div class="thumbnail visible-xs-inline-block visible-md-inline-block visible-lg-inline-block visible-sm-inline-block">
								<img id="favicon" src="<?php echo Settings("favicon") ? Page()->host . Settings("favicon") : 'http://placehold.it/90x90&text=20x20' ?>" width="20" />
							</div>
							<div class="pull-right">
								<a href="<?php echo Page()->adminHost . Page()->controller . "/upload" ?>" class="btn btn-default btn-sm btn-upload" id="fav-upload">{{Upload favicon}}</a>
								<input id="fav-input" type="hidden" name="favicon" value="<?php Page()->e(Settings("favicon"), 1); ?>" />
								<a href="#" class="btn btn-danger btn-sm delete2">{{Delete}}</a>
							</div>
						</div>
					</div>
				</div>
				<div class="row form-group">
					<div class="col-xs-6" id="logo-c">
						<label>{{Logo image:}}</label>
						<div class="row">
							<div class="col-xs-4 thumbnail">
								<img id="current_image" src="<?php echo $Header['data']['picture'] ? Page()->host . $Header['data']['picture'] : 'http://placehold.it/90x90&text=200x200' ?>" style="vertical-align: text-top;" width="90" />
								<input id="logo-input" type="hidden" name="sharepic" value="<?php echo $Header['data']['picture'] ?>" />
							</div>
							<div class="col-xs-8">
								<div class="pull-right">
									<a href="<?php echo Page()->adminHost . Page()->controller . "/upload" ?>" class="btn btn-default btn-sm btn-upload" id="logo-upload">{{Upload logo}}</a>
									<a href="#" class="btn btn-danger btn-sm delete1">{{Delete}}</a>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="panel-footer clearfix">
				<div class="btn-group pull-right">
					<a href="" class="btn btn-primary" onclick="event.preventDefault(); $(this).parents('form').submit();">{{Save}}</a>
				</div>
			</div>
		</div>
	</form>
</section>
<script>
$(function(){
	$('#hex').ColorPicker({
		color   : $('#hex').val(),
		onShow  : function(colpkr) {
			$(colpkr).fadeIn(500);
			return false;
		},
		onHide  : function(colpkr) {
			$(colpkr).fadeOut(500);
			return false;
		},
		onChange: function(hsb, hex, rgb) {
			$('#hex').val('#' + hex);

			$('header.main').css('background-color', '#' + hex);

			$('#colorSelector div').css('backgroundColor', '#' + hex);
		}

	});
	$("#title").keyup(function() {
		var color = $('#logo_color').val();
		$('hgroup h1 a').text($('#title').val()).css('color', color);

		if (!$(this).val()) {
			var image = $('#current_image').attr('src');
			$('hgroup h1:first').remove('a').html('<a><img src="' + image + '" /></a>');

		}
	});
	$(".delete1").live("click", function() {
		$('input[name=sharepic]').val('');
		$('#current_image').attr('src', 'http://placehold.it/90x90&text=200x200');
	});
	$(".delete2").live("click", function() {
		$('input[name=favicon]').val('');
		$('#favicon').attr('src', 'http://placehold.it/90x90&text=20x20');
	});

	$('#logo_color').ColorPicker({
		color   : $('#logo_color').val(),
		onShow  : function(colpkr) {
			$(colpkr).fadeIn(500);
			return false;
		},
		onChange: function(hsb, hex, rgb) {
			$('header.main h1 a').css('color', '#' + hex);
			$('#logo_color').val('#' + hex);
		}
	});

	ImageUploaderSingle("#fav-upload", function(response) {
		$("#favicon").attr({src: Settings.Host + response.file});
		$("#fav-input").val(response.file);
	}, "resize", {"crop":"20x20"})
	ImageUploaderSingle("#logo-upload", function(response) {
		$("#current_image").attr({src: Settings.Host + response.file});
		$("#logo-input").val(response.file);
		$('hgroup h1:first').hide();
		$('hgroup h1:last').show().find('img').attr({src: Settings.Host + response.file});

		var image = $('#current_image').attr('src');
		$('hgroup h1:first').remove('a').html('<a><img src="' + image + '" /></a>');
	}, "resize", {resize: "10000x100"})
});
</script>
<style type="text/css">
	#logo-c .thumbnail, #fav-c .thumbnail {
		margin-bottom: 0;
	}
</style>
<?php
	Page()->footer();
?>
