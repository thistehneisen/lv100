<?php
	if (!ActiveUser()->can(Page()->controller, "analytics")) {
		Page()->accessDenied();
	}

	if (Page()->method == "POST") {
		foreach ($_POST as $key => $value) {
			Settings()->set($key, $value, null, null, true);
		}
		$_SESSION["post_response"] = "{{Settings saved}}.";
		header("Location: {$_SERVER["HTTP_REFERER"]}");
		exit;
	}
	include('side.php');
?>
	<section class="block" id="content">
		<?php if ($_SESSION['post_response']) { ?>
			<div class="alert alert-success alert-dismissable">
				<button type="button" class="close" data-dismiss="alert" aria-label="Close">
					<span aria-hidden="true">&times;</span></button>
				<p><?php echo $_SESSION['post_response'] ?></p>
			</div>
			<?php unset($_SESSION['post_response']);
		} ?>
		<div class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title"><?php print($action); ?></h3>
			</div>
			<div class="panel-body">
				<form action="<?php echo Page()->fullRequestUri ?>" method="post">
					<div class="form-group row">
						<div class="col-xs-6">
							<label for="ga_code">Google Analyics Īpašuma ID:</label>
							<input id="ga_code" class="form-control" type="text" placeholder="UA-00000000-1" name="ga_code" value="<?php Page()->e(Settings("ga_code"), 1); ?>"/>
						</div>
						<div class="col-xs-6">
							<label for="ga_email">Google API servisa e-pasta adrese:</label>
							<input id="ga_email" class="form-control" autocomplete="off" type="text" name="ga_service_email" value="<?php Page()->e(Settings("ga_service_email"), 1); ?>"/>
						</div>
					</div>
					<div class="form-group row">
						<div class="col-xs-6">
							<label for="ga_profile_id">Google Analytics Skata ID:</label>
							<input id="ga_profile_id" class="form-control" autocomplete="off" type="text" placeholder="00000000" name="ga_profile_id" value="<?php Page()->e(Settings("ga_profile_id"), 1); ?>"/>
						</div>
						<div class="col-xs-6">
							<label for="ga_service_p12">Google API servisa konta atslēga:</label>
							<span class="input-group" id="ga_service_p12_upload">
								<input type="text" class="form-control" id="ga_service_p12" name="ga_service_p12" value="<?php Page()->e(Settings("ga_service_p12"), 1); ?>">
								<span class="input-group-btn">
									<button class="btn-default btn"><span class="glyphicon glyphicon-cloud-upload"></span>
									</button>
								</span>
							</span>
						</div>
					</div>
					<div class="row">
						<div class="col-xs-12">
							<button class="btn btn-success" type="submit">{{Save}}</button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</section>
	<script type="text/javascript">
		$(function(){
			FileUploaderSingle($("#ga_service_p12_upload"),function(res){
				$(this).find("input").val(res.file);
			},"file");
		});
	</script>
<?php Page()->footer(); ?>