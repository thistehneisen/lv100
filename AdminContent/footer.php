<?php
	if (ActiveUser()->canAccessPanel() && $this->notesEnabled)
		$this->addScript("jquery.sticky.js");
?>
	</div>
	<footer class="main">
		<div class="headwrap">
			<p>&copy; 2012<?php echo date("Y") != "2012" ? "-".date("Y") : ""?> <?php echo htmlspecialchars($Header['data']['title'])?> CMS
				<span>&bull;</span>
				Sesijas ID: <?php print(Page()->session_id); ?>
				<span>&bull;</span>
				IP: <?php print(Recipe::getClientIP(Page()->trustProxyHeaders)); ?>
			</p>
		</div>
	</footer>
	<script type="text/javascript">
		$(function(){
			$(document).on('click','[disabled]',function(e){e.preventDefault();});
			setTimeout(function(){$('.autohide').slideUp('slow');},2500);
		});
	</script>
</body>
</html>
