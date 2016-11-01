<?php
	if (!ActiveUser()->can(Page()->controller, "valodas")) {
		Page()->accessDenied();
	}

	include('side.php');
?>
<section class="block" id="content">
	<h1>
		<?= $action; ?>
	</h1>
	<div id="modal">
		<span class="loading">{{Loading}}…</span>
	</div>

</section>
<?php if (Page()->action == "translate") {
	unset($_SESSION['last_translate_filter']); ?>
	<script type="text/javascript">
		$(function() {
			$('#content')
				.load("<?php echo Page()->adminHost . Page()->controller?>/list_translates/<?=Page()->reqParams[0]?>/?q=<?php echo $_GET['q']?>");
			$('#content').on("click", "a.ajaxify", function(e) {
				e.preventDefault();
				$('#content').load($(this).attr("href"));
			});
			$('#content').on("submit", "form.ajaxify", function(e) {
				e.preventDefault();
				$(this).ajaxSubmit({target: "#content"});
			});
			$(document).on('click', '#copy-from-language-button', function() {
				if ($('#copy-from-language-select').val()) {
					$.post('<?php echo Page()->adminHost . Page()->controller?>/edit_translate/' + $("#translate")
							.data("id") + '', {
						lang: $('#copy-from-language-select').val()
					}, function(data) {
						$("#translate").text(data.text).trigger('autosize');
					}, "json");
				}
			});
		});
	</script>
<?php }
	Page()->footer(); ?>
