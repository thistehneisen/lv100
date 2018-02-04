<?php

	Page()->register_filter("content_for_display", function (&$str) {
		$str = phpQuery::newDocument($str);
		foreach (pq("[href]") as $href) {
			$url = pq($href)->attr("href");

			if (strpos($url, Page()->host) === 0) {
				pq($href)->attr("href", str_replace(Page()->host, "", $url))->attr("data-local", true);
			}
		}
		foreach (pq("[src]") as $src) {
			$url = pq($src)->attr("src");
			if (strpos($url, Page()->host) === 0) {
				pq($src)->attr("src", str_replace(Page()->host, "", $url))->attr("data-local", true);
			}
		}

		foreach (pq("blockquote") as $bq) {
			if (pq($bq)->find('p[style*="right"]')->length > 0) {
				$au = pq($bq)->find('p[style*="right"]:last');
				pq($au)->before('<div class="author">' . pq($au)->html() . '</div>');
				pq($au)->remove();
			}
		}

		pq("table")->addClass("table table-bordered");

		foreach (pq("iframe") as $iframe) {
			$parent = pq($iframe)->parent();
			pq($iframe)->insertBefore($parent);
			if (preg_match("#vimeo|youtube#", pq($iframe)->attr("src"))) {
				pq($iframe)->removeAttr("width")->removeAttr("height")->attr("style", "width: 100%;")
					->addClass("embed-responsive-item")
					->wrap('<div class="embed-responsive embed-responsive-16by9"></div>');
			} else if (preg_match("#soundcloud#", pq($iframe)->attr("src"))) {
				pq($iframe)->removeAttr("width")->removeAttr("height")->attr("style", "width: 100%;")
					->addClass("soundcloud");
			} else {
				pq($iframe)->removeAttr("width")->removeAttr("height")->attr("style", "width: 100%;")
					->addClass("embed-responsive-item")
					->wrap('<div class="embed-responsive embed-responsive-4by3"></div>');
			}
			if (pq($iframe)->parent()->next()->is(":empty")) pq($iframe)->parent()->next()->remove();
		}

		foreach (pq("img") as $img) {

			pq($img)->wrap('<span class="caption"></span>');
			if (pq($img)->attr("data-caption")) {
				pq($img)->after('<span>' . pq($img)->attr("data-caption") . '</span>');
			}
			pq($img)->parent()->attr("style", pq($img)->attr("style"));
			pq($img)->parent()->addClass(pq($img)->attr("class"));

			pq($img)->removeAttr("style")->removeAttr("data-caption")->attr("style","width: 100%;height: auto;");
			if (pq($img)->is("[data-local]")) {
				$s = getimagesize(Page()->path.pq($img)->attr("src"));
				if ($s[0]) {
					pq($img)->attr("width",$s[0])->attr("height",$s[1]);
				}
			}
		}

		$str = (string)$str;
	});
	Page()->register_filter("content_for_edit", function (&$str) {
		$str = phpQuery::newDocument($str);
		// Embeds
		foreach (pq(".embed-responsive") as $embed) {
			pq($embed)->after(pq($embed)->find(".embed-responsive-item"));
			pq($embed)->remove();
		}

		// Quotes
		foreach (pq("blockquote") as $bq) {
			if (pq($bq)->find("div.author")->length > 0) {
				foreach (pq($bq)->find("div.author") as $au) {
					pq($au)->before('<p style="text-align: right;">' . pq($au)->html() . '</p>');
					pq($au)->remove();
				}
			}
		}

		pq("table")->removeClass("table");
		pq("table")->removeClass("table-bordered");

		foreach (pq("[href]") as $href) {
			if (pq($href)->data("local")) {
				pq($href)->attr("href", Page()->host . pq($href)->attr("href"))->removeData("local");
			}
		}
		foreach (pq("[src]") as $src) {
			if (pq($src)->data("local")) {
				pq($src)->attr("src", Page()->host . pq($src)->attr("src"))->removeData("local");
			}
		}

		foreach (pq("span.caption") as $capt) {
			$img = pq($capt)->find("img");
			pq($capt)->after(pq($img));
			pq($img)->attr("style", pq($capt)->attr("style"));
			pq($img)->attr("data-caption", pq($capt)->find("span")->text());
			pq($capt)->remove();
		}

		$str = (string)$str;
	});
