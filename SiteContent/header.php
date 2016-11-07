<?php
	Class App_Util {
		static function is_old_browser() {
			$old = 0;
			if (!empty($_SERVER['HTTP_USER_AGENT'])) {
				// IE <= 7
				// User Agent: Opera/9.80 (Windows NT 6.1; U; en) Presto/2.10.229 Version/11.61
				if (preg_match('#msie\s+(\d+)\.(\d+)#si', $_SERVER['HTTP_USER_AGENT'], $matches)) {
					if ($matches[1] <= 8) {
						$old = 1;
					}
				}
				// Firefox <= 7
				// User Agent: Mozilla/5.0 (Windows NT 6.1; WOW64; rv:10.0.2) Gecko/20100101 Firefox/10.0.2
				elseif (preg_match('#Firefox/(\d+)\.(\d+)[\.\d]+#si', $_SERVER['HTTP_USER_AGENT'], $matches)) {
					if ($matches[1] <= 3) {
						$old = 1;
					}
				}
				// Safari < 5
				// User Agent: Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/534.52.7 (KHTML, like Gecko) Version/5.1.2 Safari/534.52.7
				elseif (preg_match('#Version/(\d+)[\.\d]+ Safari/[\.\d]+#si', $_SERVER['HTTP_USER_AGENT'], $matches)) {
					if ($matches[1] < 5) {
						$old = 1;
					}
				}
				// opera < 11
				// User Agent: Opera/9.80 (Windows NT 6.1; U; en) Presto/2.10.229 Version/11.61
				elseif (preg_match('#Opera/[\.\d]+.*?Version/(\d+)[\.\d]+#si', $_SERVER['HTTP_USER_AGENT'], $matches)) {
					if ($matches[1] < 11) {
						$old = 1;
					}
				}
			}

			return $old;
		}
	}


	if (App_Util::is_old_browser()) {
		Page()->incl(Page()->bPath . 'browsergate.php');
		exit;
	}
?>
	<!doctype html>
	<html lang="en">
	<head>
		<meta charset="UTF-8">
		<title>Latvija 100</title>
		<meta name="viewport" content="initial-scale=1.0, user-scalable=no"/>
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<link rel="stylesheet" type="text/css" href="<?php print(Page()->bHost); ?>assets/css/1st-phase.css?_=<?php print(filemtime(Page()->bPath."assets/css/1st-phase.css")); ?>"/>

		<script src="<?php print(Page()->bHost); ?>assets/js/jquery-2.1.4.min.js" type="text/javascript"></script>
		<script type='application/javascript' src="<?php print(Page()->bHost); ?>assets/js/fastclick.js"></script>
		<script src="<?php print(Page()->bHost); ?>assets/js/scripts.js?_=<?php print(filemtime(Page()->bPath."assets/js/scripts.js")); ?>" type="text/javascript"></script>
		<link rel="icon" type="image/png" href="<?php print(Page()->bHost); ?>assets/img/favicon.png"/>

		<script>
		  window.fbAsyncInit = function() {
		    FB.init({
		      appId      : '912881472177929',
		      xfbml      : true,
		      version    : 'v2.8'
		    });
		    FB.AppEvents.logPageView();
		  };

		  (function(d, s, id){
		     var js, fjs = d.getElementsByTagName(s)[0];
		     if (d.getElementById(id)) {return;}
		     js = d.createElement(s); js.id = id;
		     js.src = "//connect.facebook.net/en_US/sdk.js";
		     fjs.parentNode.insertBefore(js, fjs);
		   }(document, 'script', 'facebook-jssdk'));
		</script>

	</head>
	<body spellcheck="false">
	<canvas id="projector" width="1440" height="900"></canvas>
	<script type="text/javascript" src="<?php print(Page()->bHost); ?>assets/js/100gade_back.js"></script>

<?php Page()->incl(Page()->bPath . 'snippets/header.php'); ?>
