<?php
	if ($_POST["action"] == "subscribe") {
		if (filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)) {
			DataBase()->insert("emails", array(
				"email"    => $_POST["email"],
				"time"     => strftime("%F %X"),
				"ip"       => Recipe::getClientIP(Page()->trustProxyHeaders),
				"language" => Page()->language
			), true);
			?>
			<div class="alert alert-success fade" id="subscribe-alert" role="alert">
				<div class="alert-content">
					<strong>Ļoti labi!</strong> Tava e-pasta adrese ir saglabāta mūsu sistēmā.
				</div>
			</div>
			<?php
			exit;
		}
	}
