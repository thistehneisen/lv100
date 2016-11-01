<?php

	set_time_limit(0);
	Page()->cronLog("Race started.");

	Page()->countResults = true;
	$queue = DataBase()->getRows("SELECT q.*, c.`from_name`, c.`from_address`, c.`subject`, c.`content`, c.`language`, MD5(CONCAT(e.`email`,e.`id`,e.`language`)) as `hash` FROM %s q LEFT JOIN %s c ON (q.`campaign`=c.`id`) LEFT JOIN %s e ON (e.`email`=q.`to` AND c.`language`=e.`language`) WHERE `sent_status`=0 AND `locked`=0 AND `requeued`<5 ORDER BY `queued` ASC LIMIT 90", DataBase()->queue, DataBase()->campaigns, DataBase()->emails);
	$total = Page()->resultsFound;

	DataBase()->queryf("UPDATE %s SET `locked`=1 WHERE `id` IN ('" . join("','", array_map(function ($n) { return $n["id"]; }, $queue)) . "')", DataBase()->queue);

	$sentmails = 0;
	$started = time();

	$mail = new PHPMailer();

	$total = count($queue);
	foreach ($queue as $q) {
		$mail->CharSet = "utf-8";
		$mail->From = $q['from_address'];
		$mail->FromName = $q['from_name'];
		$mail->Subject = $q["subject"];
		$mail->MsgHTML(str_replace("%UNSUBSCRIBE_URL%", Page()->roots[ array_search($q["language"], array_map(function ($n) { return $n->language; }, Page()->roots)) ]->fullAddress . "?unsubscribe=" . urlencode($q["to"]) . "&hash=" . $q["hash"], $q["content"]));
		$mail->ClearAddresses();
		$mail->AddAddress($q["to"]);
		$i = $mail->Send();

		if ($i) {
			DataBase()->update("queue", array(
				"sent_status" => 1,
				"sent"        => strftime("%F %X")
			), array("id" => $q["id"]));
			$sentmails++;
		} else {
			DataBase()->update("queue", array(
				"sent_status" => 0,
				"queued"      => strftime("%F %X"),
				"requeued"    => $q["requeued"] + 1
			), array("id" => $q["id"]));
		}

		if ($started < (time() - 295)) break;
		sleep(3);
	}

	DataBase()->queryf("UPDATE %s SET `locked`=0 WHERE `id` IN ('" . join("','", array_map(function ($n) { return $n["id"]; }, $queue)) . "')", DataBase()->queue);

	Page()->cronLog("Race ended. Sent mails " . $sentmails . " of " . (int)$total . ".");
?>
