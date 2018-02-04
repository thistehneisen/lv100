<?php

	session_write_close();
	set_time_limit(0);
	umask(0);

	$photos = DataBase()->getRows("SELECT `path`,`type` FROM %s ORDER BY `sort` ASC", DataBase()->gallery);

	$watermark = new Image(Page()->path."Library/Assets/logo_watermark.png");
	foreach ($photos as $photo) {
		if ($photo["type"] == "photo") {
			$img = new Image(Page()->path . $photo["path"]);
			$img->watermark($watermark);
			$img->save(Page()->path . $photo["path"]);
		}
	}