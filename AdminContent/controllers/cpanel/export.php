<?php
	if (!ActiveUser()->can(Page()->controller, "valodas")) {
		Page()->accessDenied();
	}

	$file = $_POST['file'];
	require 'phpexcel.class.php';
	$tr = DataBase()->getRows("SELECT * FROM %1\$s WHERE `file`='%2\%s' ORDER BY `file`,`text`", DataBase()->translate, $file);
	foreach ($tr as $row) {
		$arr[ $row["text"] ][ $row["language"] ] = $row["translate"];
	}

	if ($_POST["ajax"]) {

		$objPHPExcel = new PHPExcel();

		$objPHPExcel->getProperties()->setCreator("")
			->setLastModifiedBy("")
			->setTitle("")
			->setSubject("")
			->setDescription("")
			->setKeywords("")
			->setCategory("");
		$objPHPExcel->setActiveSheetIndex(0)->setTitle("Data");
		$objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', 'ID')
			->setCellValue('B1', 'Text');
		$fr = reset($arr);
		$n = 0;
		$lll2 = array();
		foreach ($fr as $ln => $blabla) {
			$objPHPExcel->setActiveSheetIndex(0)->setCellValue(chr(ord("C") + $n) . '1', strtoupper($ln));
			$lll2[ $ln ] = $n;
			$n++;
		}

		$i = 1;
		foreach ($arr as $key => $value) {
			$lang_count = count(Page()->languages);
			$nr = -1;
			$i++;
			$objPHPExcel->setActiveSheetIndex(0)->setCellValue('A' . $i, $i)
				->setCellValue('B' . $i, $key);
			$n = 0;
			foreach ($value as $ln => $text) {
				$objPHPExcel->setActiveSheetIndex(0)->setCellValue(chr(ord("C") + $lll2[ $ln ]) . $i, $text);
				$n++;
			}
		}
		$xheader = array(
			'font'    => array(
				'bold' => true
			),
			'borders' => array(
				'bottom' => array(
					'style' => PHPExcel_Style_Border::BORDER_THIN
				)
			)
		);

		$objPHPExcel->getActiveSheet()->getStyle('A1:' . chr(ord("C") + count($fr)) . '1')->applyFromArray($xheader);
		$objPHPExcel->getActiveSheet()->getStyle('A1:' . chr(ord("C") + count($fr)) . '1')->getFill()
			->setFillType(PHPExcel_Style_Fill::FILL_SOLID)
			->getStartColor()->setARGB('FFFFFF00');

		Page()->setType('application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="Colab_' . date("dmY", time()) . '.xls"');
		header('Cache-Control: max-age=0');
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		$date = strtotime(str_replace('-', '/', date('Y-m-d H:i:s')));
		$filename = 'Colab_' . $date . "." . "xls";
		$path = Page()->path . "Uploads/Translates/";
		umask(0);
		if (!is_dir($path)) {
			@mkdir($path, 0777, true);
		}
		$data = array('filename' => $filename, 'type' => $file);
		$file == 'global' ? Page()->opts->colab_last_export_translate_global = $data : Page()->opts->colab_last_export_translate_cms = $data;

		$objWriter->save($path . $filename);
		die(json_encode(array(
			"jsonrpc" => "2.0",
			"file"    => $filename
		)));
	}
?>