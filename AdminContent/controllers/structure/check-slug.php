<?php
	if (!ActiveUser()->canAccessPanel()) {
		Page()->accessDenied();
	}

	$_GET["s"] = trim($_GET["s"],"/");
	$x = $this->getNodeBy(array(
			"parent" => $_GET["p"],
			"slug" => $_GET["s"]
		));
	$available = $x === false || $x[0]->id == $_GET["c"];
	$slug = $_GET["s"];
	$uri = $this->host;
	$p = $this->getNodeBy(array("id"=>$_GET["p"]));
	if ($p) $uri .= $p[0]->address;
	$uri .= $slug . "/";
	if ($available) {
		if ($slug == ".." || $slug == ".") $available = false;
		else {
			$xh = get_headers($uri,true);
			if (preg_match("#^HTTP\/\d\.\d (200|403)#",$xh[0])) $available = false;
			if ($available == false && $xh["Node-Id"] == $_GET["p"]) $available = true;
		}
	}
	
	die(json_encode(array("available"=>$available,"uri"=>$uri)));
?>