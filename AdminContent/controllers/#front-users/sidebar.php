<?php

?>
<nav class="sidebar">

	<ul class="sections">
		<li>
			<a class="users<?php echo $this->action == "users" ? " active" : "" ?>" href="<?php echo $this->aHost . $this->controller ?>/users/">Lietotāji</a>
		</li>
	</ul>
</nav>
