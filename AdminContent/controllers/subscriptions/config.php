<?php
	if (Page()->isAdminInterface && ActiveUser()->can(Page()->currentController, "sarakstu skatīšana")) {
		Page()->addNav("Abonamenti", Page()->currentController . "/");
	}

	Page()->registerController()
		->setGroupPerms(array("sarakstu skatīšana", "moduļa uzstādījumi", "abonentu dzēšana", "manuāla izsūtīšana"))
		->setName("Abonamenti");




	Page()->setMethod("generateCampaign", function ($subject, $content, $language, $more_url, $sid, $user, $from_name, $from_address, $list) {
		if (empty($from_name)) $from_name = Settings()->get("site_name", $language);
		if (empty($from_address)) $from_address = Page()->email_from_address;
		$template = file_get_contents(Page()->controllers["subscriptions"]->getPath() . "template.html");
		$language_root = Page()->getNode(array(
			"filter"        => array(
				"original" => 0,
				"parent"   => 0,
				"language" => $language
			),
			"returnFields"  => "fullAddress",
			"returnResults" => "first"
		));
		$host = Page()->host;
		$more_text = Settings()->get("subscribtions:more_text", $language);
		$facebook_url = Settings()->get("contacts:fb", $language);
		$facebook_text = Settings()->get("subscribtions:facebook_text", $language);
		$unsubscribe_info = nl2br(Settings()->get("subscribtions:unsubscribe_info", $language));

		$content = str_replace(array("%SITENAME%", "%LANGUAGE_ROOT%", "%HOST%", "%SUBJECT%", "%CONTENT%", "%MORE_URL%", "%MORE_TEXT%", "%FACEBOOK_URL%", "%FACEBOOK_TEXT%", "%UNSUBSCRIBE_INFO%"), array(Settings()->get("site_name", $language), $language_root, $host, $subject, $content, $more_url, $more_text, $facebook_url, $facebook_text, $unsubscribe_info), $template);

		DataBase()->insert("campaigns", array(
			"subject"        => $subject,
			"content"        => $content,
			"user"           => $user,
			"sid"            => $sid,
			"campaign_added" => strftime("%F %X"),
			"from_name"      => $from_name,
			"from_address"   => $from_address,
			"language"       => $language
		), true);
		$campaign_id = DataBase()->insertid;

		$recipients = DataBase()->getRows("SELECT * FROM %s WHERE `language`='%s'".($list ? " AND `".DataBase()->escape($list)."`=1" : ''), DataBase()->emails, $language);
		foreach ($recipients as $recipient) {
			DataBase()->insert("queue", array(
				"to"       => $recipient["email"],
				"queued"   => strftime("%F %X"),
				"campaign" => $campaign_id,
			));
		}
	});

	Page()->setMethod("generateCampaignDisplay", function ($subject, $content, $language, $more_url, $sid, $user, $from_name, $from_address) {
		if (empty($from_name)) $from_name = Settings()->get("site_name", $language);
		if (empty($from_address)) $from_address = Page()->email_from_address;
		$template = file_get_contents(Page()->controllers["subscriptions"]->getPath() . "template.html");
		$language_root = Page()->getNode(array(
			"filter"        => array(
				"original" => 0,
				"parent"   => 0,
				"language" => $language
			),
			"returnFields"  => "fullAddress",
			"returnResults" => "first"
		));
		$host = Page()->host;
		$more_text = Settings()->get("subscribtions:more_text", $language);
		$facebook_url = Settings()->get("contacts:fb", $language);
		$facebook_text = Settings()->get("subscribtions:facebook_text", $language);
		$unsubscribe_info = nl2br(Settings()->get("subscribtions:unsubscribe_info", $language));

		$content = str_replace(array("%SITENAME%", "%LANGUAGE_ROOT%", "%HOST%", "%SUBJECT%", "%CONTENT%", "%MORE_URL%", "%MORE_TEXT%", "%FACEBOOK_URL%", "%FACEBOOK_TEXT%", "%UNSUBSCRIBE_INFO%"), array(Settings()->get("site_name", $language), $language_root, $host, $subject, $content, $more_url, $more_text, $facebook_url, $facebook_text, $unsubscribe_info), $template);

		print($content);
	});

	Page()->setMethod("mailScheduledPost", function ($post, $list) {
		if ($post->data->mail_to_queued) return;
		$subject = $post->title;
		$pq = phpQuery::newDocument($post->controller_content);
		$content = "<p>".($post->description ? nl2br($post->description) : pq("p:first")->html())."</p>";
		$language = $post->language;
		$more_url = $post->fullAddress;
		$sid = $post->id;
		Page()->generateCampaign($subject, $content, $language, $more_url, $sid, null, null, null, $list);
		$post->data->mail_to_queued = true;
		Page()->setNode(array(
			"data" => $post->data,
			"id" => $post->id
		));
	});