<?php
	if (!ActiveUser()->isAdmin()) {
		Page()->accessDenied();
	}

	$user = Users()->removeUser(Page()->reqParams[0]);

	if ($user->deleted) {
		$this->trigger("user_deleted", $user);
		xLog("users: Lietotājs " . ActiveUser()->getName() . " izdzēsa lietotāju " . $user->getName() . ".", "success", $user->id);
		$_SESSION['post_success'] = "{{Users: Deleted message}}";
		header("Location: {$this->adminHost}{$this->controller}");
	} else {
		$_SESSION['post_error'] = "{{Users: Error deleting message}}";
		header("Location: {$_SERVER['HTTP_REFERER']}");
	}
?>