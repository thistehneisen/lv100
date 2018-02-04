"use strict";

function setTooltips() {
	$(document).tooltip({
		content : function() {
			return $(this).attr("info");
		},
		items   : "[info]",
		position: {my: "center bottom-6", at: "center top", collision: "flipfit"}
	});
}
function hideLonelyTabs() {
	$(".nav.nav-tabs:not(.donthide)").each(function() {
		if ($(this).children("li").length == 1) {
			$(this).hide();
		}
	});
}
function disableLabelSelecting() {
	$("label[for]").disableSelection();
}

$(document).on("ajaxcomplete", hideLonelyTabs);
$(document).on("ajaxcomplete", disableLabelSelecting);
(function($) {
	$(function() {
		if ($("form.addbody.new").length === 1) {
			(function() {
				var form        = $("form.addbody.new");
				var formActions = form.find(".form-actions").clone();
				if (form.find(".form-actions").length) {
					var formActionsWrapper = $($.parseHTML("<div/>")).addClass("form-actions-wrap");
					formActionsWrapper.append(formActions).appendTo(form);
					$(window).resize(function() {
						formActionsWrapper.css({left: form.offset().left + form.width() - formActionsWrapper.width() - parseInt(formActionsWrapper.css("padding-left")) - parseInt(formActionsWrapper.css("padding-right")) - parseInt(formActionsWrapper.css("border-left-width")) - parseInt(formActionsWrapper.css("border-right-width"))});
					}).resize();
					$(window, document).on("scroll", function() {
						var topPosition            = $(document).scrollTop();
						var rightbarBottomPosition = form.find(".rightbar .form-actions")
						                                 .offset().top + form.find(".rightbar .form-actions")
						                                                     .height() - 38;
						if (topPosition > rightbarBottomPosition) {
							formActionsWrapper.addClass("visible");
						}
						else {
							formActionsWrapper.removeClass("visible");
						}
					});
				}
			})();
		}
		$(document).on("click", "[data-confirm]", function(e) {
			e.preventDefault();
			var element = this;
			cmsConfirm($(element).data("confirm"), function(yes) {
				if (yes) {
					document.location.href = element.getAttribute("href");
				}
			});
			/*$("#modal").remove();
			 $("<div/>").attr({id:"modal"}).append(
			 $("<h1/>").css("text-align","left").html(I81n.t("{{Confirmation}}"))
			 ).append(
			 $("<p/>").css("text-align","left").html($(this).data("confirm"))
			 ).append(
			 $("<footer/>").addClass("right").append(
			 $("<a/>").addClass("actionbutton").on("click",function(e){
			 e.preventDefault();
			 $('#modal').fadeOut("fast",function(){$(this).remove();});
			 }).text(I81n.t("{{Cancel}}")).attr({href:"#"})
			 ).append($("<a/>").addClass("actionbutton "+($(this).data("confirmcolor") ? $(this).data("confirmcolor") : "green")).text(I81n.t("{{Continue}}")).attr({href:$(this).get(0).getAttribute("href")}))
			 ).appendTo("body");
			 */
		});
		hideLonelyTabs();
		disableLabelSelecting();

		$(document).on("click", ".pin-it[data-pindata]", function(e) {
			e.preventDefault();
			e.stopPropagation();
			$.post("", {add_to_carousel: $(this).data("pindata")}, function(response) {
				$("<div/>").html(response.text).dialog({
					modal  : true,
					close  : function() {
						$(this).dialog("destroy").remove();
					},
					buttons: [
						{
							text : "Aizvērt",
							click: function() {
								$(this).dialog("close");
							}
						}
					]
				});
			}, "json");
		});
	});
	setTooltips();
})(jQuery);

function str_replace(search, replace, subject, count) {
	var i    = 0,
	    j    = 0,
	    temp = '',
	    repl = '',
	    sl   = 0,
	    fl   = 0,
	    f    = [].concat(search),
	    r    = [].concat(replace),
	    s    = subject,
	    ra   = Object.prototype.toString.call(r) === '[object Array]',
	    sa   = Object.prototype.toString.call(s) === '[object Array]';
	s        = [].concat(s);

	if (typeof (search) === 'object' && typeof (replace) === 'string') {
		temp    = replace;
		replace = new Array();
		for (i = 0; i < search.length; i += 1) {
			replace[i] = temp;
		}
		temp = '';
		r    = [].concat(replace);
		ra   = Object.prototype.toString.call(r) === '[object Array]';
	}

	if (count) {
		this.window[count] = 0;
	}

	for (i = 0, sl = s.length; i < sl; i++) {
		if (s[i] === '') {
			continue;
		}
		for (j = 0, fl = f.length; j < fl; j++) {
			temp = s[i] + '';
			repl = ra ? (r[j] !== undefined ? r[j] : '') : r[0];
			s[i] = (temp)
				.split(f[j])
				.join(repl);
			if (count) {
				this.window[count] += ((temp.split(f[j]))
					.length - 1);
			}
		}
	}
	return sa ? s : s[0];
}

function removeAccents(str) {
	var a = [
		'À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Æ', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ð', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ',
		'Ö', 'Ø', 'Ù', 'Ú', 'Û', 'Ü', 'Ý', 'ß', 'à', 'á', 'â', 'ã', 'ä', 'å', 'æ', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í',
		'î', 'ï', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø', 'ù', 'ú', 'û', 'ü', 'ý', 'ÿ', 'Ā', 'ā', 'Ă', 'ă', 'Ą', 'ą', 'Ć',
		'ć', 'Ĉ', 'ĉ', 'Ċ', 'ċ', 'Č', 'č', 'Ď', 'ď', 'Đ', 'đ', 'Ē', 'ē', 'Ĕ', 'ĕ', 'Ė', 'ė', 'Ę', 'ę', 'Ě', 'ě', 'Ĝ',
		'ĝ', 'Ğ', 'ğ', 'Ġ', 'ġ', 'Ģ', 'ģ', 'Ĥ', 'ĥ', 'Ħ', 'ħ', 'Ĩ', 'ĩ', 'Ī', 'ī', 'Ĭ', 'ĭ', 'Į', 'į', 'İ', 'ı', 'Ĳ',
		'ĳ', 'Ĵ', 'ĵ', 'Ķ', 'ķ', 'Ĺ', 'ĺ', 'Ļ', 'ļ', 'Ľ', 'ľ', 'Ŀ', 'ŀ', 'Ł', 'ł', 'Ń', 'ń', 'Ņ', 'ņ', 'Ň', 'ň', 'ŉ',
		'Ō', 'ō', 'Ŏ', 'ŏ', 'Ő', 'ő', 'Œ', 'œ', 'Ŕ', 'ŕ', 'Ŗ', 'ŗ', 'Ř', 'ř', 'Ś', 'ś', 'Ŝ', 'ŝ', 'Ş', 'ş', 'Š', 'š',
		'Ţ', 'ţ', 'Ť', 'ť', 'Ŧ', 'ŧ', 'Ũ', 'ũ', 'Ū', 'ū', 'Ŭ', 'ŭ', 'Ů', 'ů', 'Ű', 'ű', 'Ų', 'ų', 'Ŵ', 'ŵ', 'Ŷ', 'ŷ',
		'Ÿ', 'Ź', 'ź', 'Ż', 'ż', 'Ž', 'ž', 'ſ', 'ƒ', 'Ơ', 'ơ', 'Ư', 'ư', 'Ǎ', 'ǎ', 'Ǐ', 'ǐ', 'Ǒ', 'ǒ', 'Ǔ', 'ǔ', 'Ǖ',
		'ǖ', 'Ǘ', 'ǘ', 'Ǚ', 'ǚ', 'Ǜ', 'ǜ', 'Ǻ', 'ǻ', 'Ǽ', 'ǽ', 'Ǿ', 'ǿ', 'А', 'а', 'К', 'к', 'М', 'м', 'о', 'o', 'Т',
		'т', 'В', 'в', 'Е', 'е', 'Н', 'н', 'Р', 'р', 'С', 'с', 'У', 'у', 'Х', 'х', 'Б', 'б', 'Г', 'г', 'Д', 'д', 'З',
		'з', 'И', 'и', 'Л', 'л', 'П', 'п', 'Ф', 'ф', 'Э', 'э', 'Ю', 'ю', 'Я', 'я', 'Ё', 'ё', 'Ж', 'ж', 'Ц', 'ц', 'Ч',
		'ч', 'Ш', 'ш', 'Щ', 'щ', 'Ы', 'ы', 'Й', 'й', 'Ъ', 'ъ', 'Ь', 'ь'
	];
	var b = [
		'A', 'A', 'A', 'A', 'A', 'A', 'AE', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'D', 'N', 'O', 'O', 'O', 'O',
		'O', 'O', 'U', 'U', 'U', 'U', 'Y', 's', 'a', 'a', 'a', 'a', 'a', 'a', 'ae', 'c', 'e', 'e', 'e', 'e', 'i', 'i',
		'i', 'i', 'n', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y', 'A', 'a', 'A', 'a', 'A', 'a', 'C',
		'c', 'C', 'c', 'C', 'c', 'C', 'c', 'D', 'd', 'D', 'd', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'G',
		'g', 'G', 'g', 'G', 'g', 'G', 'g', 'H', 'h', 'H', 'h', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'IJ',
		'ij', 'J', 'j', 'K', 'k', 'L', 'l', 'L', 'l', 'L', 'l', 'L', 'l', 'l', 'l', 'N', 'n', 'N', 'n', 'N', 'n', 'n',
		'O', 'o', 'O', 'o', 'O', 'o', 'OE', 'oe', 'R', 'r', 'R', 'r', 'R', 'r', 'S', 's', 'S', 's', 'S', 's', 'S', 's',
		'T', 't', 'T', 't', 'T', 't', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'W', 'w', 'Y', 'y',
		'Y', 'Z', 'z', 'Z', 'z', 'Z', 'z', 's', 'f', 'O', 'o', 'U', 'u', 'A', 'a', 'I', 'i', 'O', 'o', 'U', 'u', 'U',
		'u', 'U', 'u', 'U', 'u', 'U', 'u', 'A', 'a', 'AE', 'ae', 'O', 'o', 'A', 'a', 'K', 'k', 'M', 'm', 'O', 'o', 'T',
		't', 'V', 'v', 'Je', 'je', 'N', 'n', 'R', 'r', 'S', 's', 'U', 'u', 'H', 'h', 'B', 'b', 'G', 'g', 'D', 'd', 'Z',
		'z', 'I', 'i', 'L', 'l', 'P', 'p', 'F', 'f', 'E', 'e', 'Ju', 'ju', 'Ja', 'ja', 'Jo', 'jo', 'Z', 'z', 'C', 'c',
		'C', 'c', 'S', 's', 'Sca', 'sca', 'I', 'i', 'I', 'i', '', '', '', ''
	];

	return str_replace(a, b, str);
}

function selectFile(callback, type) {
	var req = {};
	if (typeof type != "undefined") {
		req.type = type;
	}
	var href = Settings.adminHost+"media/file.browser/?"+$.param(req);


	var fileBrowser = $($.parseHTML("<div/>")).attr({id:"fileBrowserDialog"}).html('<span class="loading">Notiek ielāde...</span>').dialog({
		width: 800,
		height: 440,
		modal: true,
		draggable: false,
		resizable: false,
		close: function(){
			$(this).dialog("destroy").remove();
		},
		open: function(){
			$(this).load(href);
		},
		buttons: [
			{
				text: I81n.t("Aizvērt"),
				click: function(){
					$(this).dialog("close");
				}
			},
			{
				text: I81n.t("Izvēlēties"),
				"class": "btn-success",
				click: function(){
					callback($(this).find(".thumbnail.active").data("file"),$(this).find(".thumbnail.active").data("filedata"));
					$(this).dialog("close");
				},
				disabled: true,
				id: "fileBrowserSelectButton"
			}
		]
	});
}


$(document).on("click", ".lng-switcher ul li a", function(e) {
	e.preventDefault();
	var that = this;
	$(this).parents("ul").prev().find(".lbl").text($(this).text());
	$(this).parents(".lng-switcher").siblings(".form-control").filter(function() {
		return $(this).data("lang") == $(that).data("lang");
	}).removeClass("hidden").siblings(".form-control").addClass("hidden");
});