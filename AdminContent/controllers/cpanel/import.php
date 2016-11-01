<?php
	if (!ActiveUser()->can(Page()->controller, "valodas")) {
		Page()->accessDenied();
	}

	$path = Page()->host . $_GET["f"];

	$tFile = Page()->reqParams[0] ? Page()->reqParams[0] : 'front';

	require 'phpexcel.class.php';
	require 'PHPExcel/IOFactory.php';

	$worksheet = $highestRow = $highestColumnIndex = null;
	$objPHPExcel = PHPExcel_IOFactory::load($path);
	foreach ($objPHPExcel->getWorksheetIterator() as $worksheet) {
		$worksheetTitle = $worksheet->getTitle();
		$highestRow = $worksheet->getHighestRow(); // e.g. 10
		$highestColumn = $worksheet->getHighestColumn(); // e.g 'F'
		$highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);
		$nrColumns = ord($highestColumn) - 64;
	}

	$val = array();
	foreach (Page()->languages as $lang) {
		$val[ $lang ] = 0;
	}
	for ($col = 2; $col <= $highestColumnIndex; $col++) {
		$clang = $worksheet->getCellByColumnAndRow($col, 1)->getValue();
		if (isset($val[ strtolower($clang) ])) {
			$val[ strtolower($clang) ] = $col;
		}
	}

	$v = array();
	for ($col = 2; $col <= $highestRow; ++$col) {
		$code = $worksheet->getCellByColumnAndRow(1, $col)->getValue();
		foreach ($val as $lng => $id) {
			$v[ $code ][ $lng ] = $worksheet->getCellByColumnAndRow($id, $col)->getValue();
		}
	}

	foreach ($v as $t => $p) {
		foreach (Page()->languages as $l) {
			DataBase()->insert("translate", array(
				"file"      => $tFile,
				"text"      => $t,
				"language"  => $l,
				"translate" => $p[ $l ]
			), true);
		}
	}
	die(json_encode(array(
		"jsonrpc"  => "2.0",
		"response" => Page()->t("{{CMS: import data message}}")
	)));