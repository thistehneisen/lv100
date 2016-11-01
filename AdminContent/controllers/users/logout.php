<?php

	xLog("users: Lietotājs " . ActiveUser()->getName() . " izgājis.", "success");
	Users()->logout();
	header("Location: {$_SERVER['HTTP_REFERER']}");
	exit;
?>