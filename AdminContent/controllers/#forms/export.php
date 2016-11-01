<?php

	require 'phpexcel.class.php';

	$ojd = DataBase()->json_decode;
	DataBase()->json_decode = true;
	$form = DataBase()->getRow("SELECT * FROM %s WHERE `id`='%d'", DataBase()->forms, Page()->reqParams[0]);
	$data = DataBase()->getRows("SELECT * FROM %s WHERE `form_id`='%d' ORDER BY `time` DESC", DataBase()->forms_data, Page()->reqParams[0]);
	DataBase()->json_decode = $ojd;
	
	$ob = new PHPExcel();
	
	$ob->getProperties()->setCreator("")
							 ->setLastModifiedBy("")
							 ->setTitle("")
							 ->setSubject("")
							 ->setDescription("")
							 ->setKeywords("")
							 ->setCategory("");
	$ob->setActiveSheetIndex(0)->setTitle("Dati");

	$c = 1;
	$ob->setActiveSheetIndex(0)->setCellValue('A1', "Laiks");
	foreach ($form["fields"] as $field) { if (!$field["show"] || $field["deleted"]) continue;
		$ob->setActiveSheetIndex(0)->setCellValue(PHPExcel_Cell::stringFromColumnIndex($c).'1', $field["title"]);

		$c++;
	}

	$columnCount = $c;

	$i = 2;

function customjoin($sep, $arr) {
	$x = 0; $str = "";
	foreach ($arr as $val) {
		if (!empty($val)) {
			$str.=($x > 0 ? $sep : '').$val;
			$x++;
		}
	}
	return $str;
}

	foreach ($data as $datarow) {
		$ob->setActiveSheetIndex(0)->setCellValue('A'.$i, $datarow["time"]);
		$c = 1;
		foreach ($form["fields"] as $field) { if (!$field["show"] || $field["deleted"]) continue;
			if (is_array($field["values"]) && count($field["values"])) {
				if (!is_array($datarow["f_".$field["id"]])) $datarow["f_".$field["id"]] = array($datarow["f_".$field["id"]]);
				foreach ($datarow["f_".$field["id"]] as $key => $value) {
					$z=0;
					foreach ($field["values"] as $fvalue) {
						if ($fvalue["id"] == $value && $field["subtype"] != "custom1") {
							$datarow["f_".$field["id"]][$key] = $fvalue["text"];
							break;
						}
						else if ($fvalue["id"] == $key && $field["subtype"] == "custom1") {
							if ($value) $datarow["f_".$field["id"]][$key] = $value." - ".$fvalue["text"];
							break;
						}
						$z++;
					}
				}
				$datarow["f_".$field["id"]] = customjoin(",\n",$datarow["f_".$field["id"]]);
			}
			if ($field["type"] == "input" && $field["subtype"] == "file" && is_file(Page()->path.$datarow["f_".$field["id"]])) {
				$datarow["f_".$field["id"]] = Page()->host.$datarow["f_".$field["id"]];
			}
			$datarow["f_".$field["id"]] = str_replace("\r","",$datarow["f_".$field["id"]]);
			$ob->setActiveSheetIndex(0)->setCellValue(PHPExcel_Cell::stringFromColumnIndex($c).$i, '="'.str_replace("\"","\"\"",$datarow["f_".$field["id"]]).'"');
			$ob->getActiveSheet()->getStyle(PHPExcel_Cell::stringFromColumnIndex($c).$i)->getAlignment()->setWrapText(true);
			$c++;
		}
		$i++;
	}


	for ($c = 0; $c<=$columnCount; $c++) {
		$ob->getActiveSheet()->getColumnDimension(PHPExcel_Cell::stringFromColumnIndex($c))->setAutoSize(true);
	}
	$xheader = array(
		'font' => array(
			'bold' => true
		),
		'borders' => array(
			'bottom' => array(
				'style' => PHPExcel_Style_Border::BORDER_THIN
			)
		)
	);
	
	$ob->getActiveSheet()->getStyle('A1:'.PHPExcel_Cell::stringFromColumnIndex($columnCount-1).'1')->applyFromArray($xheader);
	$ob->setActiveSheetIndex(0);
	Page()->setType('application/vnd.ms-excel');
	header('Content-Disposition: attachment; filename="applications.xls"');
	header('Cache-Control: max-age=0');
	$objWriter = PHPExcel_IOFactory::createWriter($ob, 'Excel5');
	umask(0); $time = time();
	if (!is_dir(Page()->path."Uploads/accexports/")) mkdir(Page()->path."Uploads/accexports/",0777,true);
	$objWriter->save(Page()->path."Uploads/accexports/{$time}.xls");
	readfile(Page()->path."Uploads/accexports/{$time}.xls");
	exit;
?>