// - - - - - - - - - - -  Fastclick  - - - - - - - - - - - 

window.addEventListener('load', function() {
	FastClick.attach(document.body);
}, false);
document.addEventListener("touchstart", function(){}, true);


// - - - - - - - - - - -  Prevent empty anchors to anchor  - - - - - - - - - - - 
$(document).ready(function(){
	$("a[href='#']").click(function(e) {
		e.preventDefault();
	});


// - - - - - - - - - - -  Collapsable lists  - - - - - - - - - - - 
	//houdini.init();

// - - - - - - - - - - -  Mobile burger  - - - - - - - - - - - 
$(document).on('click', "a.nav-toggle", function(e) {
	e.preventDefault();
	$(this).toggleClass('cross');
	$('body').toggleClass('noscroll');
	$('header').toggleClass('show_nav');
	$('.mask').toggleClass('show-mask');
});

// - - - - - - - - - - -  Calendar month  - - - - - - - - - - - 
$(document).on('click', ".calendar-month .day", function(e) {
	var currdate = $(this).index();
	console.log(currdate);

	$('.calendar-month .day').removeClass('active');
	$('.calendar-month .events').removeClass('active');
	$('.calendar-month .events .elst').removeClass('active');


	$(this).addClass('active');
	$(this).parent().find('.events').addClass('active');
	$(this).parent().find('.events .elst:eq('+currdate+')').addClass('active');

});

$(document).on('click', ".calendar-month .day.active", function(e) {
	var currdate = $(this).index();
	console.log(currdate);

	$('.calendar-month .day').removeClass('active');
	$('.calendar-month .events').removeClass('active');
	$('.calendar-month .events .elst').removeClass('active');
});

$(document).on('click', ".elst .close", function(e) {
	$('.calendar-month .day').removeClass('active');
	$('.calendar-month .events').removeClass('active');
	$('.calendar-month .events .elst').removeClass('active');
});

// - - - - - - - - - - -  Filter fake toggle  - - - - - - - - - - - 

$(document).on('click', ".filter a", function(e) {
	$(this).toggleClass("active");
});


// - - - - - - - - - - -  Calendar month fake month switch  - - - - - - - - - - - 

$(document).on('click', ".calendar-month .header .right", function(e) {
	if ($('.month.new').next('.month').length == 0 ) {
		$(this).addClass('disabled');
	}
	else {
	$('.month.new').removeClass('new').next('.month').addClass('new');
	$('.calendar-month .header .disabled').removeClass('disabled');
	}
});


$(document).on('click', ".calendar-month .header .left", function(e) {
	if ($('.month.new').prev('.month').length == 0 ) {
		$(this).addClass('disabled');
	}
	else {
	$('.month.new').removeClass('new').prev('.month').addClass('new');
	$('.calendar-month .header .disabled').removeClass('disabled');
	}
});



$(".youtube-player").YouTubeModal({autoplay: 1});


});


// - - - - - - - - - - -  Detect if image is horizontal or vertical  - - - - - - - - - - - 

$(window).load(function(){
	$('.feed.instagram .content').find('img').each(function(){
		aspect = this.width/this.height;
			if (aspect > 1) {
				var imgClass = 'hori-img';
			}
			else {
				var imgClass = 'vert-img';
			}
			if ( aspect == 1) {
				var imgClass = 'sqr-img';
			}
		$(this).addClass(imgClass);
	});
});




/*!
 * Bootstrap v3.3.5 (http://getbootstrap.com)
 * Copyright 2011-2016 Twitter, Inc.
 * Licensed under MIT (https://github.com/twbs/bootstrap/blob/master/LICENSE)
 */

/*!
 * Generated using the Bootstrap Customizer (http://getbootstrap.com/customize/?id=36073fd8a2752942d3cf8e82c0b1c003)
 * Config saved to config.json and https://gist.github.com/36073fd8a2752942d3cf8e82c0b1c003
 */
 if ("undefined" == typeof jQuery) {
 	throw new Error("Bootstrap's JavaScript requires jQuery");
 }
 +function(t) {
 	"use strict";
 	var e = t.fn.jquery.split(" ")[0].split(".");
 	if (e[0] < 2 && e[1] < 9 || 1 == e[0] && 9 == e[1] && e[2] < 1 || e[0] > 2) {
 		throw new Error("Bootstrap's JavaScript requires jQuery version 1.9.1 or higher, but lower than version 3")
 	}
 }(jQuery), +function(t) {
 	"use strict";
 	function e(e, i) {
 		return this.each(function() {
 			var s = t(this), n = s.data("bs.modal"), r = t.extend({}, o.DEFAULTS, s.data(), "object" == typeof e && e);
 			n || s.data("bs.modal", n = new o(this, r)), "string" == typeof e ? n[e](i) : r.show && n.show(i)
 		})
 	}

 	var o = function(e, o) {
 		this.options = o, this.$body = t(document.body), this.$element = t(e), this.$dialog = this.$element.find(".modal-dialog"), this.$backdrop = null, this.isShown = null, this.originalBodyPad = null, this.scrollbarWidth = 0, this.ignoreBackdropClick = !1, this.options.remote && this.$element.find(".modal-content")
 		.load(this.options.remote, t.proxy(function() {this.$element.trigger("loaded.bs.modal")}, this))
 	};
 	o.VERSION = "3.3.6", o.TRANSITION_DURATION = 300, o.BACKDROP_TRANSITION_DURATION = 150, o.DEFAULTS = {
 		backdrop: !0,
 		keyboard: !0,
 		show    : !0
 	}, o.prototype.toggle = function(t) {
 		return this.isShown ? this.hide() : this.show(t)
 	}, o.prototype.show = function(e) {
 		var i = this, s = t.Event("show.bs.modal", {relatedTarget: e});
 		this.$element.trigger(s), this.isShown || s.isDefaultPrevented() || (this.isShown = !0, this.checkScrollbar(), this.setScrollbar(), this.$body.addClass("modal-open"), this.escape(), this.resize(), this.$element.on("click.dismiss.bs.modal", '[data-dismiss="modal"]', t.proxy(this.hide, this)), this.$dialog.on("mousedown.dismiss.bs.modal", function() {
 			i.$element.one("mouseup.dismiss.bs.modal", function(e) {
 				t(e.target).is(i.$element) && (i.ignoreBackdropClick = !0)
 			})
 		}), this.backdrop(function() {
 			var s = t.support.transition && i.$element.hasClass("fade");
 			i.$element.parent().length || i.$element.appendTo(i.$body), i.$element.show()
 			.scrollTop(0), i.adjustDialog(), s && i.$element[0].offsetWidth, i.$element.addClass("in"), i.enforceFocus();
 			var n = t.Event("shown.bs.modal", {relatedTarget: e});
 			s ? i.$dialog.one("bsTransitionEnd", function() {i.$element.trigger("focus").trigger(n)})
 			.emulateTransitionEnd(o.TRANSITION_DURATION) : i.$element.trigger("focus").trigger(n)
 		}))
 	}, o.prototype.hide = function(e) {
 		e && e.preventDefault(), e = t.Event("hide.bs.modal"), this.$element.trigger(e), this.isShown && !e.isDefaultPrevented() && (this.isShown = !1, this.escape(), this.resize(), t(document)
 			.off("focusin.bs.modal"), this.$element.removeClass("in").off("click.dismiss.bs.modal")
 			.off("mouseup.dismiss.bs.modal"), this.$dialog.off("mousedown.dismiss.bs.modal"), t.support.transition && this.$element.hasClass("fade") ?
 			this.$element.one("bsTransitionEnd", t.proxy(this.hideModal, this))
 			.emulateTransitionEnd(o.TRANSITION_DURATION) :
 			this.hideModal())
 	}, o.prototype.enforceFocus = function() {
 		t(document).off("focusin.bs.modal")
 		.on("focusin.bs.modal", t.proxy(function(t) {this.$element[0] === t.target || this.$element.has(t.target).length || this.$element.trigger("focus")}, this))
 	}, o.prototype.escape = function() {
 		this.isShown && this.options.keyboard ?
 		this.$element.on("keydown.dismiss.bs.modal", t.proxy(function(t) {27 == t.which && this.hide()}, this)) :
 		this.isShown || this.$element.off("keydown.dismiss.bs.modal")
 	}, o.prototype.resize = function() {
 		this.isShown ? t(window).on("resize.bs.modal", t.proxy(this.handleUpdate, this)) :
 		t(window).off("resize.bs.modal")
 	}, o.prototype.hideModal = function() {
 		var t = this;
 		this.$element.hide(), this.backdrop(function() {t.$body.removeClass("modal-open"), t.resetAdjustments(), t.resetScrollbar(), t.$element.trigger("hidden.bs.modal")})
 	}, o.prototype.removeBackdrop = function() {this.$backdrop && this.$backdrop.remove(), this.$backdrop = null}, o.prototype.backdrop = function(e) {
 		var i = this, s = this.$element.hasClass("fade") ? "fade" : "";
 		if (this.isShown && this.options.backdrop) {
 			var n = t.support.transition && s;
 			if (this.$backdrop = t(document.createElement("div")).addClass("modal-backdrop " + s)
 				.appendTo(this.$body), this.$element.on("click.dismiss.bs.modal", t.proxy(function(t) {
 					return this.ignoreBackdropClick ? void(this.ignoreBackdropClick = !1) :
 					void(t.target === t.currentTarget && ("static" == this.options.backdrop ?
 						this.$element[0].focus() : this.hide()))
 				}, this)), n && this.$backdrop[0].offsetWidth, this.$backdrop.addClass("in"), !e) {
 				return;
 		}
 		n ? this.$backdrop.one("bsTransitionEnd", e).emulateTransitionEnd(o.BACKDROP_TRANSITION_DURATION) : e()
 	}
 	else if (!this.isShown && this.$backdrop) {
 		this.$backdrop.removeClass("in");
 		var r = function() {i.removeBackdrop(), e && e()};
 		t.support.transition && this.$element.hasClass("fade") ?
 		this.$backdrop.one("bsTransitionEnd", r).emulateTransitionEnd(o.BACKDROP_TRANSITION_DURATION) : r()
 	}
 	else {
 		e && e()
 	}
 }, o.prototype.handleUpdate = function() {this.adjustDialog()}, o.prototype.adjustDialog = function() {
 	var t = this.$element[0].scrollHeight > document.documentElement.clientHeight;
 	this.$element.css({
 		paddingLeft : !this.bodyIsOverflowing && t ? this.scrollbarWidth : "",
 		paddingRight: this.bodyIsOverflowing && !t ? this.scrollbarWidth : ""
 	})
 }, o.prototype.resetAdjustments = function() {
 	this.$element.css({
 		paddingLeft : "",
 		paddingRight: ""
 	})
 }, o.prototype.checkScrollbar = function() {
 	var t = window.innerWidth;
 	if (!t) {
 		var e = document.documentElement.getBoundingClientRect();
 		t     = e.right - Math.abs(e.left)
 	}
 	this.bodyIsOverflowing = document.body.clientWidth < t, this.scrollbarWidth = this.measureScrollbar()
 }, o.prototype.setScrollbar = function() {
 	var t = parseInt(this.$body.css("padding-right") || 0, 10);
 	this.originalBodyPad = document.body.style.paddingRight || "", this.bodyIsOverflowing && this.$body.css("padding-right", t + this.scrollbarWidth)
 }, o.prototype.resetScrollbar = function() {this.$body.css("padding-right", this.originalBodyPad)}, o.prototype.measureScrollbar = function() {
 	var t = document.createElement("div");
 	t.className = "modal-scrollbar-measure", this.$body.append(t);
 	var e = t.offsetWidth - t.clientWidth;
 	return this.$body[0].removeChild(t), e
 };
 var i = t.fn.modal;
 t.fn.modal = e, t.fn.modal.Constructor = o, t.fn.modal.noConflict = function() {return t.fn.modal = i, this}, t(document)
 .on("click.bs.modal.data-api", '[data-toggle="modal"]', function(o) {
 	var i = t(this), s = i.attr("href"), n = t(i.attr("data-target") || s && s.replace(/.*(?=#[^\s]+$)/, "")), r = n.data("bs.modal") ?
 	"toggle" :
 	t.extend({remote: !/#/.test(s) && s}, n.data(), i.data());
 	i.is("a") && o.preventDefault(), n.one("show.bs.modal", function(t) {t.isDefaultPrevented() || n.one("hidden.bs.modal", function() {i.is(":visible") && i.trigger("focus")})}), e.call(n, r, this)
 })
}(jQuery);

/*!
 * Bootstrap YouTube Popup Player Plugin
 * http://lab.abhinayrathore.com/bootstrap-youtube/
 * https://github.com/abhinayrathore/Bootstrap-Youtube-Popup-Player-Plugin
 */
 !function(o) {
 	function t(t) {h.html(o.trim(t))}

 	function e(o) {b.html(o)}

 	function u() {t(""), e("")}

 	function a(o) {c.css({width: o + 2 * m})}

 	function i(o, t) {
 		return [
 		"//www.youtube.com/embed/", o, "?rel=0&showsearch=0&autohide=", t.autohide, "&autoplay=", t.autoplay,
 		"&controls=", t.controls, "&fs=", t.fs, "&loop=", t.loop, "&showinfo=", t.showinfo, "&color=", t.color,
 		"&theme=", t.theme, "&wmode=transparent"
 		].join("")
 	}

 	function l(o, t, e) {
 		return [
 		'<div class="embed-responsive embed-responsive-16by9"><iframe class="embed-responsive-item" title="YouTube video player" ',
 		'style="margin:0; padding:0; border:0;" ', 'src="', o,
 		'" frameborder="0" allowfullscreen seamless></iframe></div>'
 		].join("")
 	}

 	function d(e) {
 		o.ajax({
 			url     : window.location.protocol + "//query.yahooapis.com/v1/public/yql",
 			data    : {
 				q     : "select * from json where url ='http://www.youtube.com/oembed?url=http://www.youtube.com/watch?v=" + e + "&format=json'",
 				format: "json"
 			},
 			dataType: "jsonp",
 			success : function(o) {o && o.query && o.query.results && o.query.results.json && t(o.query.results.json.title)}
 		})
 	}

 	function r(o) {
 		var t = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=)([^#\&\?]*).*/, e = o.match(t);
 		return e && 11 == e[2].length ? e[2] : !1
 	}

 	var n, s = null, c = null, h = null, b = null, m = 5;
 	n = {
 		init      : function(n) {
 			if (n = o.extend({}, o.fn.YouTubeModal.defaults, n), null == s) {
 				s     = o('<div class="modal fade ' + n.cssClass + '" id="YouTubeModal" role="dialog" aria-hidden="true">');
 				var m = '<div class="modal-dialog modal-lg" id="YouTubeModalDialog"><div class="modal-content" id="YouTubeModalContent"><div class="modal-header"><a href="#" class="close" data-dismiss="modal">×</a><h4 class="modal-title" id="YouTubeModalTitle"></h4></div><div class="modal-body" id="YouTubeModalBody" style="padding:0;"></div></div></div>';
 				s.html(m).hide()
 				.appendTo("body"), c = o("#YouTubeModalDialog"), h = o("#YouTubeModalTitle"), b = o("#YouTubeModalBody"), s.modal({show: !1})
 				.on("hide.bs.modal", u)
 			}
 			return this.each(function() {
 				var u = o(this), c = u.data("YouTube");
 				c || (u.data("YouTube", {target: u}), o(u).bind("click.YouTubeModal", function(c) {
 					var h = n.youtubeId;
 					"" == o.trim(h) && u.is("a") && (h = r(u.attr("href"))), ("" == o.trim(h) || h === !1) && (h = u.attr(n.idAttribute));
 					var b = o.trim(n.title);
 					"" == b && (n.useYouTubeTitle ? d(h) : b = u.attr("title")), b && t(b), a(n.width);
 					var m = i(h, n), f = l(m, n.width, n.height);
 					e(f), s.modal("show"), c.preventDefault()
 				}))
 			})
 		}, destroy: function() {return this.each(function() {o(this).unbind(".YouTubeModal").removeData("YouTube")})}
 	}, o.fn.YouTubeModal = function(t) {
 		return n[t] ? n[t].apply(this, Array.prototype.slice.call(arguments, 1)) : "object" != typeof t && t ?
 		void o.error("Method " + t + " does not exist on Bootstrap.YouTubeModal") :
 		n.init.apply(this, arguments)
 	}, o.fn.YouTubeModal.defaults = {
 		youtubeId      : "",
 		title          : "",
 		useYouTubeTitle: !0,
 		idAttribute    : "rel",
 		cssClass       : "YouTubeModal",
 		autohide       : 2,
 		autoplay       : 1,
 		color          : "red",
 		controls       : 1,
 		fs             : 1,
 		loop           : 0,
 		showinfo       : 0,
 		theme          : "light"
 	}
 }(jQuery);


