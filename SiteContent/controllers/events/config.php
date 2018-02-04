<?php


	Page()->setMethod("getEventsByDate",function($date){
			$dateStart = strtotime($date." 00:00:00");
			$dateEnd = strtotime($date." 23:59:59");
			$returnArray = array();

			foreach (Page()->validEvents as &$event) {
				$startTime = strtotime($event->start);
				$endTime = strtotime($event->end);
				if (($startTime < $dateStart && $endTime >= $dateStart) || ($startTime >= $dateStart && $startTime <= $dateEnd)) {
					$returnArray[]=&$event;
				}
			}
			return $returnArray;
	});