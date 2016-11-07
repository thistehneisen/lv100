<section>
	<div class="container subscribe">
		<header>
			<h2>Pieraksties jaunumiem</h2>
			<?php /*<p class="lead">Donec rhoncus tincidunt sapien, in semper sapien luctus et. Sed pharetra aliquet ex, a hendrerit felis eget turpis facilisis, non maximus ex cursus.</p>*/ ?>
		</header>
		<div class="text" id="subscribe-container">
			<form action="<?php print(Page()->getURL()); ?>" method="post" id="subscribe-form">
				<input type="hidden" name="action" value="subscribe">
				<input type="email" id="f-1" name="email" placeholder="Ievadi savu e-pasta adresi" required="">
				
				<button>Pierakstīties</button>
			</form>
		</div>
	</div>
</section>
<script type="text/javascript" src="<?php print(Page()->bHost); ?>assets/js/jquery.forms.js"></script>
<script type="text/javascript">
	$(function() {
		$(document).on("submit", "#subscribe-form", function(e) {
			e.preventDefault();
			var options = {
				success: function(response){
					$('#subscribe-container').replaceWith(response);
					setTimeout(function(){
						$("#subscribe-alert").addClass("in");
					},50);
				}
			};
			$(this).ajaxSubmit(options);
		});
	});
</script>