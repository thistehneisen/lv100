<?php

	$access_token_data = Settings()->get("invite:access_token");
	if (!$access_token_data || $access_token_data["updated"] < time() - 3600) {
		$data = file_get_contents("https://graph.facebook.com/oauth/access_token?client_id=" . Page()->FacebookApp["id"] . "&client_secret=" . Page()->FacebookApp["secret"] . "&grant_type=client_credentials");
		parse_str($data, $token);
		$access_token_data["token"] = $token["access_token"];
		$access_token_data["updated"] = time();
		Settings()->set("invite:access_token", $access_token_data);
	}

	$attendingList = Settings()->get("event:attending:" . Page()->FacebookApp["eventId"]);
	$eventData = Settings()->get("event:data:" . Page()->FacebookApp["eventId"]);

	if (!$attendingList) {
		$attendingList = array();
	}
	if (!$eventData) {
		$eventData = array();
	}

	$event = json_decode(file_get_contents("https://graph.facebook.com/" . Page()->FacebookApp["eventId"] . "/?access_token={$access_token_data["token"]}&fields=attending_count,maybe_count,interested_count,noreply_count,attending{first_name,picture{url}}"));

	$eventData["attending_count"] = $event->attending_count;
	$eventData["maybe_count"] = $event->maybe_count;
	$eventData["interested_count"] = $event->interested_count;
	$eventData["noreply_count"] = $event->noreply_count;
	$eventData["attending"] = array();

	$attendingIds = array();
	$userData = array();

	$attending = $event->attending;
	if ($attending->data && count($attending->data) > 0) {
		foreach ($attending->data as $user) {
			$attendingIds[] = $user->id;
			if (!isset($attendingList[ $user->id ])) {
				$attendingList[ $user->id ] = time();
				$userData[ $user->id . "" ] = array($user->first_name, $user->picture->data->url);
			}
		}

		while ($attending->paging->next) {
			$attending = json_decode(file_get_contents($attending->paging->next));
			if ($attending->data && count($attending->data) > 0) {
				foreach ($attending->data as $user) {
					$attendingIds[] = $user->id;
					if (!isset($attendingList[ $user->id ])) {
						$attendingList[ $user->id . "" ] = time();
						$userData[ $user->id . "" ] = array($user->first_name, $user->picture->data->url);
					}
				}
			}
		}
	}

	foreach ($attendingList as $userId => $time) {
		if (!in_array($userId, $attendingIds)) {
			unset($attendingList[ $userId . "" ]);
		}
	}

	Settings()->set("event:list:" . Page()->FacebookApp["eventId"], $attendingList);

	arsort($attendingList);
	$lasteight = array_slice($attendingList, 0, 8, true);
	$lasteight = array_keys($lasteight);

	foreach ($lasteight as $userId) {
		$eventData["attending"][] = $userData[ $userId . "" ];
	}

	Settings()->set("event:data:" . Page()->FacebookApp["eventId"], $eventData);

	Page()->debug($eventData);
