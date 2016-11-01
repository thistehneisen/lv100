<?php //ā
	if (!ActiveUser()->can(Page()->controller, "manuāla izsūtīšana")) {
		Page()->accessDenied();
	}


	if (Page()->method == "POST") {
		$count =  DataBase()->getVar("SELECT COUNT(*) FROM %s WHERE `language`='%s'", DataBase()->emails, $_POST["language"]);

		Page()->generateCampaign($_POST["subject"], $_POST["content"], $_POST["language"], $_POST["more_url"], 0, ActiveUser()->id, $_POST["from_name"], $_POST["from_address"]);

		$_SESSION["post_response"] = "Jaunums izsūtīšanai ir izveidots un tiks izsūtīts ".Common::mf($count, "%d saņēmējam", "%d saņēmējiem", "lv")."!";
		header("Location: {$_SERVER["HTTP_REFERER"]}");
		exit;
	}

	Page()->addBreadcrumb("Abonementi", Page()->aHost . Page()->controller . "/");
	Page()->addBreadcrumb("Manuāla izsūtīšana", Page()->aHost . Page()->controller . "/manual/");

	Page()->header();

?>

<?php include(Page()->controllers[ Page()->controller ]->getPath() . "sidebar.php"); ?>

<?php if (Page()->newsLettersEnabled) { ?>
<section class="block" id="content">
	<form action="<?php echo Page()->fullRequestUri ?>" method="post" id="send">
		<div class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title">Manuālā izsūtīšana</h3>
			</div>
			<div class="panel-body">
				<?php if ($_SESSION['post_response']) { ?>
					<div class="alert alert-success alert-dismissable">
						<button type="button" class="close" data-dismiss="alert" aria-label="Close">
							<span aria-hidden="true">&times;</span></button>
						<p><?php echo $_SESSION['post_response'] ?></p>
					</div>
					<?php unset($_SESSION['post_response']);
				} ?>
				<div class="form-group">
					<label for="from_name">Sūtītāja nosaukums:</label>
					<input id="from_name" class="form-control" type="text" placeholder="" name="from_name" value="<?php Page()->e(Settings()->get("subscribtions:default_from_name", "lv"), 1); ?>" />
				</div>
				<div class="form-group">
					<label for="from_address">Sūtītāja adrese:</label>
					<input id="from_address" class="form-control" type="text" placeholder="" name="from_address" value="<?php Page()->e(Settings()->get("subscribtions:default_from_address", "lv"), 1); ?>" />
				</div>
				<div class="form-group">
					<label for="language">Saņēmēju saraksts:</label>
					<select id="language" name="language" class="form-control">
						<option value="0" selected></option>
					<?php foreach (Page()->languages as $language) {
						$count =  DataBase()->getVar("SELECT COUNT(*) FROM %s WHERE `language`='%s'", DataBase()->emails, $language);
					?>
						<option value="<?php print($language); ?>">Jaunumu saņēmēju saraksts (<?php print(Page()->language_labels[$language]); ?>) (<?php print(Common::mf($count, "%d saņēmējs", "%d saņēmēji", "lv")); ?>)</option>
					<?php } ?>
					</select>
				</div>
				<hr>
				<div class="form-group">
					<label for="subject">E-pasta nosaukums:</label>
					<input id="subject" class="form-control" type="text" placeholder="" name="subject" value="" />
				</div>
				<div class="form-group">
					<label for="more_link">"Uzzināt vairāk" pogas links:</label>
					<input id="more_link" class="form-control" type="text" placeholder="" name="more_link" value="" />
				</div>
				<div class="form-group">
					<label for="body-content">Saturs:</label>
					<textarea id="body-content" class="form-control tinymce_small" placeholder="" name="content"></textarea>
				</div>
			</div>
			<div class="panel-footer">
				<button type="submit" class="btn btn-small btn-primary">Izsūtīt</button>
			</div>
		</div>
	</form>
</section>
	<script type="text/javascript">
		$(function(){
			$("#send").on("submit",function(e){
				if ($("#language").val() == "0") {
					e.preventDefault();
					alert("Izvēlies saņēmēju sarakstu!");
				}
			});
		});
	</script>
<?php } else { ?>
	<div class="alert alert-danger">
		Jaunumu izsūtīšana uz laiku ir izslēgta.
	</div>
<?php } ?>

<?php Page()->footer(); ?>