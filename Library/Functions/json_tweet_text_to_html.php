<?php
	function json_tweet_text_to_HTML($tweet, $links = true, $users = true, $hashtags = true) {
		$return = $tweet->text;

		$entities = array();

		if ($links && is_array($tweet->entities->urls)) {
			foreach ($tweet->entities->urls as $e) {
				$temp["start"] = $e->indices[0];
				$temp["end"] = $e->indices[1];
				$temp["replacement"] = '<a href="' . $e->expanded_url . '" target="_blank">' . $e->display_url . '</a>';
				$entities[] = $temp;
			}
		}
		if ($users && is_array($tweet->entities->user_mentions)) {
			foreach ($tweet->entities->user_mentions as $e) {
				$temp["start"] = $e->indices[0];
				$temp["end"] = $e->indices[1];
				$temp["replacement"] = '<a href="https://twitter.com/' . $e->screen_name . '" target="_blank">@' . $e->screen_name . '</a>';
				$entities[] = $temp;
			}
		}
		if ($hashtags && is_array($tweet->entities->hashtags)) {
			foreach ($tweet->entities->hashtags as $e) {
				$temp["start"] = $e->indices[0];
				$temp["end"] = $e->indices[1];
				$temp["replacement"] = '<a href="https://twitter.com/hashtag/' . $e->text . '?src=hash" target="_blank">#' . $e->text . '</a>';
				$entities[] = $temp;
			}
		}

		usort($entities, function ($a, $b) { return ($b["start"] - $a["start"]); });

		foreach ($entities as $item) {
			$return = mb_substr($return, 0, $item["start"]) . $item["replacement"] . mb_substr($return, $item["end"]);
		}

		return ($return);
	}
