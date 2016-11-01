<?php
	$Header = Settings()->get("CMS.global.style");
	if (!$Header) {
		$Header = array("data" => array(
			"title"      => "Admin Panel",
			"bg_color"   => "#404040",
			"logo_color" => "#000",
			"favicon"    => $this->adminBaseHost . 'css/favicon.png'
		));
	}

	Page()->loadScript("bootstrap-combobox", array("bootstrap"));
	Page()->loadScript("bootstrap-datepicker", array("bootstrap"));
	Page()->loadScript("jquery", null, "head");
	Page()->loadScript("bootstrap", array("jquery"));
	Page()->loadScript("jquery.timers", array("jquery"));
	Page()->loadScript("jquery.migrate", array("jquery"));
	Page()->loadScript("jquery-ui", array("jquery"), "head");
	Page()->loadScript("jquery.flip", array("jquery"));
	Page()->loadScript("jquery.nestedSortable", array("jquery-ui"));
	Page()->loadScript("jquery.multiselect", array("jquery"));
	Page()->loadScript("jquery.caret", array("jquery"));
	Page()->loadScript("tag-it", array("jquery-ui"));
	Page()->loadScript("colorpicker", array("jquery"));
	Page()->loadScript("selectize", array("jquery"));
	Page()->loadScript("form", array("jquery"));
	Page()->loadScript("tiny_mce/jquery.tinymce", array("jquery"));
	Page()->loadScript("plupload/plupload.full", array("jquery"));
	Page()->loadScript("jquery.autosize-min", array("jquery"));
	Page()->loadScript("jquery.timers", array("jquery"));
	Page()->loadScript("permanent", array("jquery-ui"));
	Page()->loadScript("script", array("permanent", "tiny_mce/jquery.tinymce"));
	Page()->loadScript("script.v2", array("script", "tag-it"), "head");
	Page()->loadScript("date", array("script"));

	//session_write_close();

	//Page()->getScriptsForLoad("head");
	/*	Page()->getScriptsForLoad("body");
		Page()->debug(Page()->scripts);*/
	//Page()->debug(Page()->getScriptsForLoad("body"));
?>
<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
		<title><?php echo htmlspecialchars($Header['data']['title']) ?> CMS</title>
		<link rel="stylesheet" href="<?php echo $this->adminBaseHost ?>css/bootstrap.min.css">
		<link rel="stylesheet" href="<?php echo $this->adminBaseHost ?>css/bootstrap-combobox.css">
		<link rel="stylesheet" href="<?php echo $this->adminBaseHost ?>css/bootstrap-datepicker.min.css">

		<link rel="stylesheet" href="<?php echo $this->adminBaseHost ?>css/jui.css" type="text/css"/>
		<link rel="stylesheet" href="<?php echo $this->adminBaseHost ?>css/style.css" type="text/css"/>
		<link rel="stylesheet" href="<?php echo $this->adminBaseHost ?>css/permanent.css" type="text/css"/>
		<link rel="stylesheet" href="<?php echo $this->adminBaseHost ?>css/jquery.tagit.css" type="text/css"/>
		<link rel="stylesheet" href="<?php echo $this->adminBaseHost ?>css/style.v2.css" type="text/css"/>
		<link rel="stylesheet" href="<?php echo $this->adminBaseHost ?>css/colorpicker.css" type="text/css"/>
		<link rel="stylesheet" href="<?php echo $this->adminBaseHost ?>css/fontello-embedded.css" type="text/css"/>
		<link rel="stylesheet" href="<?php echo $this->adminBaseHost ?>css/selectize.bootstrap3.css" type="text/css"/>
		<link rel="icon" type="image/png" href="<?= $this->host . $Header['data']['favicon'] ?>"/>
		<!--[if lt IE 9]>
		<script src="//html5shiv.googlecode.com/svn/trunk/html5.js"></script><![endif]-->

		<script type="text/javascript">

			var Settings = <?php echo json_encode(array(
				"adminHost"      => $this->aHost,
				"adminBaseHost"  => $this->bHost,
				"Host"           => $this->host,
				"fullRequestUri" => $this->fullRequestUri,
				"session_id"     => session_id()
			))?>;

		</script>
		<script type="text/javascript">
			var adminHost = "<?php echo $this->aHost?>", adminBaseHost = "<?php echo $this->bHost?>", Host = "<?php echo $this->host?>";
		</script>
	</head>
	<body>

		<header style="background: <?php echo $Header['data']['bg_color']; ?> url('<?= $this->bHost ?>css/img/noise.png') repeat;" class="main bgrepeat">
			<div class="headwrap">
				<hgroup>
					<h1><?php
							if ($Header['data']['picture']) {
								echo '<a href="' . $this->adminHost . '"><img src="' . $this->host . $Header['data']['picture'] . '" alt="' . $this->title . '"></a>';
							} else {
								echo '<a href="' . $this->adminHost . '" style="color: ' . $Header['data']['logo_color'] . '">' . $Header['data']['title'] . '</a>';
							}
						?></h1>

					<h2><a href="<?php echo $this->adminHost ?>">Administrācijas panelis</a></h2>
				</hgroup>

				<?php if (ActiveUser()->canAccessPanel()) { ?>
					<aside>
					<a href="<?php echo $this->adminHost ?>users/edit/<?php echo ActiveUser()->id ?>/" class="user"><?php echo ActiveUser()->echoName() ?></a>
					<a href="<?php echo $this->adminHost ?>users/logout/" class="logout">Iziet</a>
					</aside><?php } ?>
			</div>
		</header>
		<nav class="sitenav">
			<div class="headwrap">
				<?php if (ActiveUser()->canAccessPanel()) { ?>
					<ul class="sitenavpart">
						<li>
							<a <?php echo $this->controller == "index" ? 'class="active" ' : '' ?>href="<?php echo $this->adminHost ?>" tabindex="0" title="Sākums"><span></span></a>
						</li>
					</ul>
					<aside class="sitenavpart">

					<?php if ($this->permTo("manage", "cpanel")) { ?>
					<a href="<?php echo $this->adminHost ?>cpanel/list/" class="settings">Uzstādījumi</a><?php } ?>
					</aside><?php } ?>
			</div>
		</nav>
		<?php if (ActiveUser()->canAccessPanel()) { ?>
			<nav class="sitecrumbs">
				<ol class="breadcrumb headwrap">
					<?php $c = count($this->breadcrumbs);
						$n = 0;
						foreach ($this->breadcrumbs as $__crumb) {
							$n++;
							$crumbs[] = '<li>' . ($n < $c ? '<a href="' . $__crumb["href"] . '" title="">' : '') . $__crumb["title"] . ($n < $c ? '</a>' : '') . '</li>';
						}
						echo join(PHP_EOL, (array)$crumbs);
					?>
				</ol>
			</nav>
		<?php } ?>
		<div class="body clear container">

			<?php if ($this->adminPanelDisabled && ActiveUser()->canAccessPanel() && !ActiveUser()->isDev()) { ?>
				<section class="alert alert-warning">
					<p>Administrācijas panelis uz laiku ir izslēgts.</p>
				</section>
				<?php $this->footer();
				exit;
			} ?>
