<?php

	require 'phpexcel.class.php';

	$arr = DataBase()->getRows("SELECT * FROM %s ORDER BY `language` ASC, `time` ASC", DataBase()->emails);


	$objPHPExcel = new PHPExcel();

	$objPHPExcel->getProperties()->setCreator("")
		->setLastModifiedBy("")
		->setTitle("")
		->setSubject("")
		->setDescription("")
		->setKeywords("")
		->setCategory("");
	$objPHPExcel->setActiveSheetIndex(0)->setTitle("Abonementi");
	$objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', 'E-pasta adrese')
			->setCellValue('B1', 'Valoda')
			->setCellValue('C1', 'Pierakstīšanās laiks')
			->setCellValue('D1', 'Jaunumiem')
			->setCellValue('E1', 'Aktualitātēm');

	$i=1;
	foreach($arr as $key => $value){
		$lang_count = count(Page()->languages);
		$nr = -1;
		$i++;
		$objPHPExcel->setActiveSheetIndex(0)->setCellValue('A'.$i, $value["email"])
				->setCellValue('B'.$i, $value["language"])
				->setCellValue('C'.$i, $value["time"])
				->setCellValue('D'.$i, $value["news"] ? 'x' : '')
				->setCellValue('E'.$i, $value["actualities"] ? 'x' : '');
	}
	$objPHPExcel->getActiveSheet()->getColumnDimension("A")->setAutoSize(true);
	$objPHPExcel->getActiveSheet()->getColumnDimension("B")->setAutoSize(true);
	$objPHPExcel->getActiveSheet()->getColumnDimension("C")->setAutoSize(true);
	$objPHPExcel->getActiveSheet()->getColumnDimension("D")->setAutoSize(true);
	$objPHPExcel->getActiveSheet()->getColumnDimension("E")->setAutoSize(true);
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

	$objPHPExcel->getActiveSheet()->getStyle('A1:E1')->applyFromArray($xheader);
	$objPHPExcel->getActiveSheet()->getStyle('A1:E1')->getFill()
		->setFillType(PHPExcel_Style_Fill::FILL_SOLID)
		->getStartColor()->setARGB('FFFFFF00');

	Page()->setType('application/vnd.ms-excel');
	header('Content-Disposition: attachment;filename="subscriptions_'.date("dmY",time()).'.xls"');
	header('Cache-Control: max-age=0');
	$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');

	$objWriter->save("php://output");
