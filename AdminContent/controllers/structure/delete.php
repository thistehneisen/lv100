<?php
	$node = $this->getNode($this->reqParams[0]);
	if (!ActiveUser()->canWrite(Page()->controller, $node->id)) {
		Page()->accessDenied();
	}


	if (ActiveUser()->canWrite($node->id)) {
		if (!$node->builtin || ActiveUser()->isDev()) {
			$x = $this->remNode($node->id);
			$_SESSION["post_response"] = $x ? array("Ieraksts izdzēsts.","success","yes") :
			( $node === false ? array("Norādītais ieraksts neeksistē.","danger","no")
				: array("Notika nezināma kļūme.","danger","no")
			);

			if ($x) {
				xLog($node->controller.": Dzēsts ieraksts ".$node->title, "failed", $x);
			}
		} else {
			$_SESSION["post_response"] = array("Šis ieraksts nav paredzēts dzēšanai.","danger","no");
		}
	} else {
		$_SESSION["post_response"] = array("Tev nav nepieciešamo atļauju, lai izdzēstu šo ierakstu.","danger","no");
	}
	if (!$_GET["return-to"]) $_GET["return-to"] = $this->aHost.$this->controller."/";
	header("Location: {$_GET["return-to"]}");
	exit;
?>