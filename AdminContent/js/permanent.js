$(function(){

	$(document).on("click","[data-dialog]",function(e){
		e.preventDefault();
		var data = $(this).data("dialog");
		$("#modal").remove();
		var dialog = $("<div/>").attr({id:"modal"}).appendTo('body');
		if (data&&data.css) dialog.css(data.css);
		if (data&&data.title) dialog.append($("<h1/>").css("text-align","left").html(data.title));
		if (data&&data.content) dialog.append($("<p/>").css("text-align","left").html(data.content));
		var footer = $("<footer/>").addClass("right").appendTo(dialog);
		footer.append(
				$("<a/>").addClass("actionbutton").on("click",function(e){
					e.preventDefault();
					$('#modal').fadeOut("fast",function(){$(this).remove();});
				}).text(I81n.t("{{Cancel}}")).attr({href:"#"})
			);
		if (data&&data.buttons) {
			$.each(data.buttons,function(k,v){
				footer.append($("<a/>").addClass("actionbutton " + (v.className ? v.className : "")).text(v.label).attr({href:v.href}));
			});
		}
	});
});

(function( $ ){
	$.fn.cropThumb = function(option) {
		if (option == "container") {
			if ($(this).data("ctParams"))
				return $(this).parent().parent();
		}
		return this.each(function() {
			if (this.naturalWidth && this.naturalHeight) {
				var savedPosition = $.parseJSON($(this).siblings(".saved-position").val()),
					savedData = $.parseJSON($(this).siblings(".saved-data").val());
				var nw = this.naturalWidth,nh = this.naturalHeight,cw = $(this).width(),ch = $(this).height(),ew = $(this).data().thumbAreaWidth,eh = $(this).data().thumbAreaHeight,mpx = cw/nw,mpy = ch/nh,ox = (cw-ew)/-1,oy = (ch-eh)/-1,ow = (cw*2)-ew,oh = (ch*2)-eh;
				$(this).css(savedPosition).data("ctParams",{containerWidth: ew,containerHeight: eh,naturalWidth: nw,naturalHeight: nh,scaledWidth: cw,scaledHeight: ch,multiplayerX: mpx,multiplayerY: mpy}).wrap($("<div\/>").css({width: ow,height: oh,top: oy,left: ox,position: "absolute"})).each(function(){
						var cropOrigin = {x: Math.round($(this).position().left/$(this).data().ctParams.multiplayerX), y: Math.round($(this).position().top/$(this).data().ctParams.multiplayerY)};
						$(this).parent().siblings(".saved-data").val($.toJSON(cropOrigin));
				}).parent().wrap($("<div\/>").css({width: ew,height: eh,position: "relative",overflow: "hidden"})).children().css({visibility:"visible"}).draggable({
					containment: "parent",
					stop: function( event, ui ) {
						var offset = {left: $(this).parent().position().left/-1, top: $(this).parent().position().top/-1};
						var position = {left: offset.left - ui.position.left, top: offset.top - ui.position.top};
						var cropOrigin = {x: Math.round(position.left/$(this).data().ctParams.multiplayerX), y: Math.round(position.top/$(this).data().ctParams.multiplayerY)};
						$(this).cropThumb("container").siblings(".saved-position").val($.toJSON(ui.position));
						$(this).cropThumb("container").siblings(".saved-data").val($.toJSON(cropOrigin));
					}
				});
				if (typeof option == "function") option();
			}
			else $(this).load(function(){
				$(this).cropThumb(option);
			});
		});
	};
})( jQuery );

(function( $ ){

	var methods = {
		init : function( options ) { 
			var settings = $.extend( {
			'width'         : 'auto',
			'height'         : 'auto',
			'top' 			: '10%',
			'left'			: 'center',
			'title'			: false,
			'content'		: '',
			'buttons'		: [],
			'appendClose'	: false,
			'onload'		: false
			}, options);
			$.modal('destroy',true);
			var dialog = $('<div/>').attr({id: 'modal'}).css({
				width: settings.width,
				top: settings.top,
				left: settings.left,
				margin: 0,
				display: 'inline-block',
				right: 'auto'
			}).data('modal',settings).appendTo('body');
			
			if (settings.title) dialog.append($('<h1/>').css('text-align','left').html(settings.title));
			
			if (settings.content) {
				if (/^http[s]?:\/\//.test(settings.content)) dialog.append($('<p/>').css({textAlign:"left",height:settings.height}).load(settings.content,function(){$.modal("update"); settings.onload&&settings.onload();}));
				else  { dialog.append($('<p/>').css({textAlign:"left",height:settings.height}).html(settings.content)); settings.onload&&settings.onload(); }
			}
			if (settings.appendClose || settings.buttons.length > 0) var footer = $('<footer/>').addClass('right').appendTo(dialog);
			if (settings.appendClose) footer.append(
					$('<a/>').addClass('btn btn-default').on('click',function(e){
						e.preventDefault();
						$.modal('destroy');
					}).text(settings.appendClose).attr({href:'#'})
				);
			if (settings&&settings.buttons) {
				$.each(settings.buttons,function(k,v){
					if (v.callback && typeof v.callback == 'function')
						footer.append($('<a/>').addClass('btn btn-default ' + (v.className ? v.className : '')).text(v.label).attr({href:'#'}).on('click',v.callback));
					else footer.append($('<a/>').addClass('btn btn-default ' + (v.className ? v.className : '')).text(v.label).attr({href:v.href}).on('click',function(e){if (v.confirm && !confirm(v.confirm)) e.preventDefault(); }));
				});
			}
			$("body").css("overflow","hidden");
			$(window).bind('resize',function(e){
				$.modal('update');
			}).resize();
			var maxZ = Math.max.apply(null,
				$.map($('body *:visible'), function(e,n) {
					if ($(e).css('position') != 'static')
						return parseInt($(e).css('z-index')) || 1;
				}));
			$("#modal").css({zIndex:maxZ+1});
		},
		destroy : function( content ) {
			$("body").css("overflow","auto");
			$(window).unbind('resize',function(e){
				$.modal('update');
			});
			if (typeof content != 'undefined' && content === true) return $('#modal').remove();
			return $('#modal').fadeOut('fast',function(){$(this).remove();});
		},
		update : function () {
			var settings = $('#modal').data('modal');
			if (settings && settings.left == 'center') $('#modal').css('left',$(window).width()/2-$('#modal').width()/2);
			$('#modal > p').css({maxHeight: $(window).height()-$('#modal').position().top+document.scrollTop,overflow:'auto'});
		}
	};

	$.modal = function( method ) {

		// Method calling logic
		if ( methods[method] ) {
			return methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ));
		} else if ( typeof method === 'object' || ! method ) {
			return methods.init.apply( this, arguments );
		} else {
			$.error( 'Method ' +  method + ' does not exist on jQuery.modal' );
		}    

	};

})( jQuery );


/* Kalendāra spraudnis */

(function($){
 
	var monthLength = [31,28,31,30,31,30,31,31,30,31,30,31],
	locale = {
		months: {
			"lv": ["Janvāris","Februāris","Marts","Aprīlis","Maijs","Jūnijs","Jūlijs","Augusts","Septembris","Oktobris","Novembris","Decembris"],
			"ru": ["Январь","Февраль","Март","Апрель","Май","Июнь","Июль","Август","Сентябрь","Октябрь","Ноябрь","Декабрь"],
			"en": ["January","February","March","April","May","June","July","August","September","October","November","December"]
		},
		days: {
			"lv": ["Pr","Ot","Tr","Ce","Pk","Se","Sv"],
			"ru": ["Пн","Вт","Ср","Чт","Пт","Сб","Вс"],
			"en": ["Mo","Tu","We","Th","Fr","Sa","Su"]
		},
		close: {
			"lv": "Aizvērt",
			"ru": "Закрывать",
			"en": "Close"
		}
	},
	prevDate = null,
	nextDate = null;
	var methods = {
		init : function( options ) { 
						
			var settings = $.extend( {
				  'disablePast'		: true
				}, options);
   			return this.each(function(){

				var $this = $(this),
					data = $this.data("calendar"),
					lang = $this.attr("lang") ? $this.attr("lang") : "lv",
					$calendarObject = $("<div/>").css({position:"absolute",zIndex:5}).addClass("calendar calendar_popup").append(
						$("<div/>").addClass("months")
					).append(
						$("<table/>").append(
							$("<thead/>").append($("<tr/>"))
						).append(
							$("<tbody/>")
						)
					).append(
						$("<a/>").attr({href:"#"}).addClass("close")
					).hide();


				if ( ! data ) {
					if ($calendarObject.find("table > thead > tr").is(':empty')) $.each(locale.days[lang], function(k,v){
						$calendarObject.find("table > thead > tr").append($("<th/>").html(v));
					});
					$this.data('calendar', {
						target : $this,
						lang : lang,
						calendarObject : $calendarObject,
						settings: settings
					});
					/*$this.on('focus', function (e) {
						e.stopPropagation();
						if (!$calendarObject.is(":visible")) {
							$('.calendar_popup').hide();
							$this.calendar("show");
							$("html").one("click",function(e){
								$this.calendar("hide");
							});
							$this.blur();
						} else {
							$this.calendar("hide");
							$("html").off("click", function (e) {
								$this.calendar("hide");
							});
						}
					});*/
					$this.on('click', function (e) {
						e.stopPropagation();
						if (!$calendarObject.is(":visible")) {
							$('.calendar_popup').hide();
							$this.calendar("show");
							$("html").one("click", function (e) {
								$this.calendar("hide");
							});
							$this.blur();
						} else {
							$this.calendar("hide");
							$("html").off("click", function (e) {
								$this.calendar("hide");
							});
						}
					});
					$this.add($calendarObject).on("click",function(e){
						e.stopPropagation();
					});
					$calendarObject.on("click","a.close",function(e){
						e.preventDefault();
						$this.calendar("hide");
					});
				}
			});
		},
		hide : function( ) {
			return this.each(function(){
				var $this = $(this),
				data = $this.data('calendar');
				data.calendarObject.fadeOut(200, function () {
					data.calendarObject.find(".months").
						add(data.calendarObject.find("tbody")).empty();				
				});
			})
		},
		destroy : function( ) { 
			return this.each(function(){

				var $this = $(this),
				data = $this.data('calendar');
				data.calendarObject.remove();
				$this.removeData('calendar');
			})
		},
		show : function(date) {
			return this.each(function(){
				var	$this	= $(this),
					data	= $this.data('calendar'),
					currentDate	= new Date(),
					inputDate	= typeof date != "undefined" ? date : getDateFromThis($this),
					month	= inputDate ? inputDate.getMonth() : currentDate.getMonth(),
					year	= inputDate ? inputDate.getFullYear() : currentDate.getFullYear(),
					firstDate	= new Date(year, month, 1),
					firstDay	= firstDate.getDay()-1,
					length	= (month === 1 && ((year % 4 == 0 && year % 100 != 0) || year % 400 == 0)) ? 29 : monthLength[month],
					lastMonth	= month == 0 ? 11 : month-1,
					nextMonth	= month == 11 ? 0 : month+1,
					lastYear	= month == 0 ? year-1 : year,
					nextYear	= month == 11 ? year+1 : year,
					lastMonthsLength	= (lastMonth === 1 && ((lastYear % 4 == 0 && lastYear % 100 != 0) || lastYear % 400 == 0)) ? 29 : monthLength[lastMonth],
					months	= data.calendarObject.find(".months").empty(),
					days	= data.calendarObject.find("tbody").empty();
				prevDate = new Date(lastYear, lastMonth);
				nextDate = new Date(nextYear, nextMonth);
				
				data.calendarObject.appendTo("body").find('a.close').html(locale.close[data.lang]);
				data.calendarObject.css({
					zIndex: 50
				});
				
				months.append(
					$("<a/>").attr({href:"#"}).addClass("prev").on("click",function(e){
						e.preventDefault();
						$this.calendar("show",prevDate);
					}).html(locale.months[data.lang][prevDate.getMonth()])
				).append(
					$("<p/>").html(locale.months[data.lang][firstDate.getMonth()] + '<br/><span style="font-size:10px;"><select style="padding: 0 15px 0 10px; height: 20px;" class="yearselect"></select></span>')
				).append(
					$("<a/>").attr({href:"#"}).addClass("next").on("click",function(e){
						e.preventDefault();
						$this.calendar("show",nextDate);
					}).html(locale.months[data.lang][nextDate.getMonth()])
				);
				for (var y=(currentDate.getFullYear()-120); y<(currentDate.getFullYear()+100); y++) {
					months.find(".yearselect").append('<option value="'+y+'"'+(year==y ? ' selected' : '')+'>'+y+'</option>');
				}
				months.find(".yearselect").on("change",function(){
					$this.calendar("show",new Date($(this).val(),month));
				});
				
				var curday = 0;
				firstDay = (firstDay == -1 ? 6 : firstDay);
				for (var i=0; i<7; i++) { //Nedēļas
					days.append($("<tr/>"));
					for (var j=0; j<7; j++) { //Dienas
						if (firstDay > 0) days.children("tr").last().append(
							$("<td/>").addClass("inactive").append(
								$("<a/>").attr({href:"#"}).html(1+lastMonthsLength-firstDay--)
							)
						);
						else if (curday < length) {
							curday++;
							var thisDate = new Date(year, month, curday);
							thisDate.setTime(thisDate.getTime() + 3600 * 24 * 1000);
							days.children("tr").last().append(
								$("<td/>").addClass(currentDate > thisDate && data.settings.disablePast ? "inactive" : "").append(
									currentDate > thisDate && data.settings.disablePast ? $("<a/>").attr({href:"#"}).html(curday) :
									$("<a/>").attr({href:"#"}).html(curday).addClass(getDateFromThis($this) && getDateFromThis($this).getTime() == (new Date(year, month, curday)).getTime() ? "selected" : "").addClass(Date.today().getTime() == (new Date(year, month, curday)).getTime() ? "today" : "").one('click',function(e){
										e.preventDefault();
										$this.val(pad($(this).text())+' / '+pad(month+1)+' / '+year).change();
										$(this).addClass('selected');
										$this.calendar("hide");
									})
								)
							);
						}
						else {
							curday++;
							days.children("tr").last().append(
								$("<td/>").addClass("inactive").append(
									$("<a/>").attr({href:"#"}).html(curday-length)
								)
							);
						}
					}
					if (curday >= length) break;
				}
				
				data.calendarObject.fadeIn(200).position({
					my: "left top",
					at: "left bottom",
					of: $this,
					collision: "flip"
				});
			});
		}
	};
	
	var getDateFromThis = function($this) {
		var adate = $this.val().split(' / ');
		if (adate.length != 3) return false;
		else return new Date(parseInt(adate[2].replace(/^[0]*/,"")), parseInt(adate[1].replace(/^[0]*/,""))-1, parseInt(adate[0].replace(/^[0]*/,"")));
	};
	var pad = function (n) {
		return n < 10 ? '0'+n : n.toString();
	};
		
	$.fn.calendar = function( method ) {

		if ( methods[method] ) {
			return methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ));
		} else if ( typeof method === 'object' || ! method ) {
			return methods.init.apply( this, arguments );
		} else {
			$.error( 'Metode ' +  method + ' nav atrodama spraudnī jQuery.calendar' );
		}    

	};
	
	
	/****/
	// INPUTS

	$(document).on('change','input[data-type="number"]',function(e){
		var v = $(this).val().
			replace(/[^0-9\-,\.]/g,"").
			replace(/[\.]/g,",").
			replace(/[\-]+/g,"-").
			replace(/([0-9])\-/g,"$1").
			replace(/[,]([0-9]*,[0-9]*)/g,"$1");
		$(this).val(v);
	});
	$(document).on('change','input[data-type="time"]',function(e){
		var v = $(this).val().
			replace(/[^0-9]/g,":").
			replace(/[:]([0-9]*:[0-9]*:[0-9]*)/g,"$1"),
			p = v.split(":");
		if (p.length == 0) p = ["00","00"];
		else if (p.length == 1) p[1] = "00";
		if (p.length == 3 && (parseInt(p[2])<0 || parseInt(p[2])>60)) p[2] = "00";
		else if (p.length == 3) p[2] = str_pad(p[2],2,"0","STR_PAD_LEFT");
		if (parseInt(p[1])<0 || parseInt(p[1])>60) p[1] = "00";
		else p[1] = str_pad(p[1],2,"0","STR_PAD_LEFT");
		if (parseInt(p[0])<0 || parseInt(p[0])>24) p[0] = "00";
		else p[0] = str_pad(p[0],2,"0","STR_PAD_LEFT");
		
		$(this).val(p.join(":"));
	});

	/*		TODO:	Pievienot live-input formatēšanu.
	 * 				Maybe arī UP/DOWN taustiņus.
	 */

	/* str_pad() ir funkcija no php.js bibliotēkas ;) */
	/****/
	
	
})(jQuery);


(function ($) {
	var escapeable = /["\\\x00-\x1f\x7f-\x9f]/g, meta = {
		'\b': '\\b',
		'\t': '\\t',
		'\n': '\\n',
		'\f': '\\f',
		'\r': '\\r',
		'"': '\\"',
		'\\': '\\\\'
	};
	$.toJSON = typeof JSON === 'object' && JSON.stringify ? JSON.stringify : function (o) {
		if (o === null) {
			return 'null';
		}
		var type = typeof o;
		if (type === 'undefined') {
			return undefined;
		}
		if (type === 'number' || type === 'boolean') {
			return '' + o;
		}
		if (type === 'string') {
			return $.quoteString(o);
		}
		if (type === 'object') {
			if (typeof o.toJSON === 'function') {
				return $.toJSON(o.toJSON());
			}
			if (o.constructor === Date) {
				var month = o.getUTCMonth() + 1, day = o.getUTCDate(), year = o.getUTCFullYear(), hours = o.getUTCHours(), minutes = o.getUTCMinutes(), seconds = o.getUTCSeconds(), milli = o.getUTCMilliseconds();
				if (month < 10) {
					month = '0' + month;
				}
				if (day < 10) {
					day = '0' + day;
				}
				if (hours < 10) {
					hours = '0' + hours;
				}
				if (minutes < 10) {
					minutes = '0' + minutes;
				}
				if (seconds < 10) {
					seconds = '0' + seconds;
				}
				if (milli < 100) {
					milli = '0' + milli;
				}
				if (milli < 10) {
					milli = '0' + milli;
				}
return'"'+year+'-'+month+'-'+day+'T'+
					hours + ':' + minutes + ':' + seconds + '.' + milli + 'Z"';
			}
			if (o.constructor === Array) {
				var ret = [];
				for (var i = 0; i < o.length; i++) {
					ret.push($.toJSON(o[i]) || 'null');
				}
				return '[' + ret.join(',') + ']';
			}
			var name, val, pairs = [];
			for (var k in o) {
				type = typeof k;
				if (type === 'number') {
					name = '"' + k + '"';
				} else if (type === 'string') {
					name = $.quoteString(k);
				} else {
					continue;
				}
				type = typeof o[k];
				if (type === 'function' || type === 'undefined') {
					continue;
				}
				val = $.toJSON(o[k]);
				pairs.push(name + ':' + val);
			}
			return '{' + pairs.join(',') + '}';
		}
	};
	$.evalJSON = typeof JSON === 'object' && JSON.parse ? JSON.parse : function (src) {
		return eval('(' + src + ')');
	};
	$.secureEvalJSON = typeof JSON === 'object' && JSON.parse ? JSON.parse : function (src) {
		var filtered = src.replace(/\\["\\\/bfnrtu]/g, '@').replace(/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g, ']').replace(/(?:^|:|,)(?:\s*\[)+/g, '');
		if (/^[\],:{}\s]*$/.test(filtered)) {
			return eval('(' + src + ')');
		} else {
			throw new SyntaxError('Error parsing JSON, source is not valid.');
		}
	};
	$.quoteString = function (string) {
		if (string.match(escapeable)) {
			return '"' + string.replace(escapeable, function (a) {
					var c = meta[a];
					if (typeof c === 'string') {
						return c;
					}
					c = a.charCodeAt();
					return '\\u00' + Math.floor(c / 16).toString(16) + (c % 16).toString(16);
				}) + '"';
		}
		return '"' + string + '"';
	};
})(jQuery);


function number_format (number, decimals, dec_point, thousands_sep) {
    // http://kevin.vanzonneveld.net
    // +   original by: Jonas Raoni Soares Silva (http://www.jsfromhell.com)
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +     bugfix by: Michael White (http://getsprink.com)
    // +     bugfix by: Benjamin Lupton
    // +     bugfix by: Allan Jensen (http://www.winternet.no)
    // +    revised by: Jonas Raoni Soares Silva (http://www.jsfromhell.com)
    // +     bugfix by: Howard Yeend
    // +    revised by: Luke Smith (http://lucassmith.name)
    // +     bugfix by: Diogo Resende
    // +     bugfix by: Rival
    // +      input by: Kheang Hok Chin (http://www.distantia.ca/)
    // +   improved by: davook
    // +   improved by: Brett Zamir (http://brett-zamir.me)
    // +      input by: Jay Klehr
    // +   improved by: Brett Zamir (http://brett-zamir.me)
    // +      input by: Amir Habibi (http://www.residence-mixte.com/)
    // +     bugfix by: Brett Zamir (http://brett-zamir.me)
    // +   improved by: Theriault
    // +      input by: Amirouche
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // *     example 1: number_format(1234.56);
    // *     returns 1: '1,235'
    // *     example 2: number_format(1234.56, 2, ',', ' ');
    // *     returns 2: '1 234,56'
    // *     example 3: number_format(1234.5678, 2, '.', '');
    // *     returns 3: '1234.57'
    // *     example 4: number_format(67, 2, ',', '.');
    // *     returns 4: '67,00'
    // *     example 5: number_format(1000);
    // *     returns 5: '1,000'
    // *     example 6: number_format(67.311, 2);
    // *     returns 6: '67.31'
    // *     example 7: number_format(1000.55, 1);
    // *     returns 7: '1,000.6'
    // *     example 8: number_format(67000, 5, ',', '.');
    // *     returns 8: '67.000,00000'
    // *     example 9: number_format(0.9, 0);
    // *     returns 9: '1'
    // *    example 10: number_format('1.20', 2);
    // *    returns 10: '1.20'
    // *    example 11: number_format('1.20', 4);
    // *    returns 11: '1.2000'
    // *    example 12: number_format('1.2000', 3);
    // *    returns 12: '1.200'
    // *    example 13: number_format('1 000,50', 2, '.', ' ');
    // *    returns 13: '100 050.00'
    // Strip all characters but numerical ones.
    number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
    var n = !isFinite(+number) ? 0 : +number,
        prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
        sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
        dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
        s = '',
        toFixedFix = function (n, prec) {
            var k = Math.pow(10, prec);
            return '' + Math.round(n * k) / k;
        };
    // Fix for IE parseFloat(0.55).toFixed(0) = 0;
    s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
    if (s[0].length > 3) {
        s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
    }
    if ((s[1] || '').length < prec) {
        s[1] = s[1] || '';
        s[1] += new Array(prec - s[1].length + 1).join('0');
    }
    return s.join(dec);
}
function str_pad (input, pad_length, pad_string, pad_type) {
    // http://kevin.vanzonneveld.net
    // +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // + namespaced by: Michael White (http://getsprink.com)
    // +      input by: Marco van Oort
    // +   bugfixed by: Brett Zamir (http://brett-zamir.me)
    // *     example 1: str_pad('Kevin van Zonneveld', 30, '-=', 'STR_PAD_LEFT');
    // *     returns 1: '-=-=-=-=-=-Kevin van Zonneveld'
    // *     example 2: str_pad('Kevin van Zonneveld', 30, '-', 'STR_PAD_BOTH');
    // *     returns 2: '------Kevin van Zonneveld-----'
    var half = '',
        pad_to_go;

    var str_pad_repeater = function (s, len) {
        var collect = '',
            i;

        while (collect.length < len) {
            collect += s;
        }
        collect = collect.substr(0, len);

        return collect;
    };

    input += '';
    pad_string = pad_string !== undefined ? pad_string : ' ';

    if (pad_type != 'STR_PAD_LEFT' && pad_type != 'STR_PAD_RIGHT' && pad_type != 'STR_PAD_BOTH') {
        pad_type = 'STR_PAD_RIGHT';
    }
    if ((pad_to_go = pad_length - input.length) > 0) {
        if (pad_type == 'STR_PAD_LEFT') {
            input = str_pad_repeater(pad_string, pad_to_go) + input;
        } else if (pad_type == 'STR_PAD_RIGHT') {
            input = input + str_pad_repeater(pad_string, pad_to_go);
        } else if (pad_type == 'STR_PAD_BOTH') {
            half = str_pad_repeater(pad_string, Math.ceil(pad_to_go / 2));
            input = half + input + half;
            input = input.substr(0, pad_length);
        }
    }

    return input;
}
    eval(function (p, a, c, k, e, r) {
        e = function (c) {
            return (c < a ? '' : e(parseInt(c / a))) + ((c = c % a) > 35 ? String.fromCharCode(c + 29) : c.toString(36))
        };
        if (!''.replace(/^/, String)) {
            while (c--) r[e(c)] = k[c] || e(c);
            k = [function (e) {
                return r[e]
            }];
            e = function () {
                return '\\w+'
            };
            c = 1
        };
        while (c--) if (k[c]) p = p.replace(new RegExp('\\b' + e(c) + '\\b', 'g'), k[c]);
        return p
    }('4 g=p v();4 5=0;8 h(a){9=p w();9.j=a;9.e=a.e;9.f=a.6["f"];7 9}8 q(a){a.j.6["f"]=a.f;1(a.e){a.j.e=a.e}}8 k(a,b){2=a;4 i=0;l(2&&(!2.3||(2.3&&2.3!=b))){2=a.x[i];i++}1(2&&2.3&&2.3==b){7 2}m 1(a.r)7 k(a.r,b);7 0}8 n(a,b){4 i=0;y(i;i<5;i++){q(g[i])}5=0;1(a.o=="z")7;l(a&&((a.3&&a.3!="A")||!a.3)){a=a.B}1(!a||(a&&a.o&&a.o=="C"))7;1(a){4 c=a;1(c){g[5]=h(c);5++}4 d=k(a,"s");4 i=0;l(d){1(d.3=="s"){1(!d.6){d.6={}}m{g[5]=h(d);5++}d.6["f"]=b;d.6.D=\'E\';i++}d=d.F}}}8 G(a,b){1(!a)a=H.I;1(a.t){n(a.t,b)}m 1(a.u){n(a.u,b)}}', 45, 45, '|if|myElement|tagName|var|savedStateCount|style|return|function|saved|||||className|backgroundColor|savedStates|saveBackgroundStyle||element|findNode|while|else|highlightTableRow|id|new|restoreBackgroundStyle|firstChild|TD|srcElement|target|Array|Object|childNodes|for|myTable|TR|parentNode|header|cursor|default|nextSibling|trackTableHighlight|window|event'.split('|'), 0, {}))
    
    
/* This program is free software. It comes without any warranty, to
 * the extent permitted by applicable law. You can redistribute it
 * and/or modify it under the terms of the Do What The Fuck You Want
 * To Public License, Version 2, as published by Sam Hocevar. See
 * http://sam.zoy.org/wtfpl/COPYING for more details. */

/* Original work by "lehelk" http://lehelk.com/2011/05/06/script-to-remove-diacritics/
   depending of the usecase you may want to add the uppercase letters from this website to the alphabet and change the regex flags */

var alphabet = {
	a: /[\u0061\u24D0\uFF41\u1E9A\u00E0\u00E1\u00E2\u1EA7\u1EA5\u1EAB\u1EA9\u00E3\u0101\u0103\u1EB1\u1EAF\u1EB5\u1EB3\u0227\u01E1\u00E4\u01DF\u1EA3\u00E5\u01FB\u01CE\u0201\u0203\u1EA1\u1EAD\u1EB7\u1E01\u0105\u2C65\u0250]/ig,
    aa:/[\uA733]/ig,
    ae:/[\u00E6\u01FD\u01E3]/ig,
    ao:/[\uA735]/ig,
    au:/[\uA737]/ig,
    av:/[\uA739\uA73B]/ig,
    ay:/[\uA73D]/ig,
    b:/[\u0062\u24D1\uFF42\u1E03\u1E05\u1E07\u0180\u0183\u0253]/ig,
    c:/[\u0063\u24D2\uFF43\u0107\u0109\u010B\u010D\u00E7\u1E09\u0188\u023C\uA73F\u2184]/ig,
    d:/[\u0064\u24D3\uFF44\u1E0B\u010F\u1E0D\u1E11\u1E13\u1E0F\u0111\u018C\u0256\u0257\uA77A]/ig,
    dz:/[\u01F3\u01C6]/ig,
    e:/[\u0065\u24D4\uFF45\u00E8\u00E9\u00EA\u1EC1\u1EBF\u1EC5\u1EC3\u1EBD\u0113\u1E15\u1E17\u0115\u0117\u00EB\u1EBB\u011B\u0205\u0207\u1EB9\u1EC7\u0229\u1E1D\u0119\u1E19\u1E1B\u0247\u025B\u01DD]/ig,
    f:/[\u0066\u24D5\uFF46\u1E1F\u0192\uA77C]/ig,
    g:/[\u0067\u24D6\uFF47\u01F5\u011D\u1E21\u011F\u0121\u01E7\u0123\u01E5\u0260\uA7A1\u1D79\uA77F]/ig,
    h:/[\u0068\u24D7\uFF48\u0125\u1E23\u1E27\u021F\u1E25\u1E29\u1E2B\u1E96\u0127\u2C68\u2C76\u0265]/ig,
    hv:/[\u0195]/ig,
    i:/[\u0069\u24D8\uFF49\u00EC\u00ED\u00EE\u0129\u012B\u012D\u00EF\u1E2F\u1EC9\u01D0\u0209\u020B\u1ECB\u012F\u1E2D\u0268\u0131]/ig,
    j:/[\u006A\u24D9\uFF4A\u0135\u01F0\u0249]/ig,
    k:/[\u006B\u24DA\uFF4B\u1E31\u01E9\u1E33\u0137\u1E35\u0199\u2C6A\uA741\uA743\uA745\uA7A3]/ig,
    l:/[\u006C\u24DB\uFF4C\u0140\u013A\u013E\u1E37\u1E39\u013C\u1E3D\u1E3B\u017F\u0142\u019A\u026B\u2C61\uA749\uA781\uA747]/ig,
    lj:/[\u01C9]/ig,
    m:/[\u006D\u24DC\uFF4D\u1E3F\u1E41\u1E43\u0271\u026F]/ig,
    n:/[\u006E\u24DD\uFF4E\u01F9\u0144\u00F1\u1E45\u0148\u1E47\u0146\u1E4B\u1E49\u019E\u0272\u0149\uA791\uA7A5]/ig,
    nj:/[\u01CC]/ig,
    o:/[\u006F\u24DE\uFF4F\u00F2\u00F3\u00F4\u1ED3\u1ED1\u1ED7\u1ED5\u00F5\u1E4D\u022D\u1E4F\u014D\u1E51\u1E53\u014F\u022F\u0231\u00F6\u022B\u1ECF\u0151\u01D2\u020D\u020F\u01A1\u1EDD\u1EDB\u1EE1\u1EDF\u1EE3\u1ECD\u1ED9\u01EB\u01ED\u00F8\u01FF\u0254\uA74B\uA74D\u0275]/ig,
    oi:/[\u01A3]/ig,
    ou:/[\u0223]/ig,
    oo:/[\uA74F]/ig,
    p:/[\u0070\u24DF\uFF50\u1E55\u1E57\u01A5\u1D7D\uA751\uA753\uA755]/ig,
    q:/[\u0071\u24E0\uFF51\u024B\uA757\uA759]/ig,
    r:/[\u0072\u24E1\uFF52\u0155\u1E59\u0159\u0211\u0213\u1E5B\u1E5D\u0157\u1E5F\u024D\u027D\uA75B\uA7A7\uA783]/ig,
    s:/[\u0073\u24E2\uFF53\u00DF\u015B\u1E65\u015D\u1E61\u0161\u1E67\u1E63\u1E69\u0219\u015F\u023F\uA7A9\uA785\u1E9B]/ig,
    t:/[\u0074\u24E3\uFF54\u1E6B\u1E97\u0165\u1E6D\u021B\u0163\u1E71\u1E6F\u0167\u01AD\u0288\u2C66\uA787]/ig,
    tz:/[\uA729]/ig,
    u:/[\u0075\u24E4\uFF55\u00F9\u00FA\u00FB\u0169\u1E79\u016B\u1E7B\u016D\u00FC\u01DC\u01D8\u01D6\u01DA\u1EE7\u016F\u0171\u01D4\u0215\u0217\u01B0\u1EEB\u1EE9\u1EEF\u1EED\u1EF1\u1EE5\u1E73\u0173\u1E77\u1E75\u0289]/ig,
    v:/[\u0076\u24E5\uFF56\u1E7D\u1E7F\u028B\uA75F\u028C]/ig,
    vy:/[\uA761]/ig,
    w:/[\u0077\u24E6\uFF57\u1E81\u1E83\u0175\u1E87\u1E85\u1E98\u1E89\u2C73]/ig,
    x:/[\u0078\u24E7\uFF58\u1E8B\u1E8D]/ig,
    y:/[\u0079\u24E8\uFF59\u1EF3\u00FD\u0177\u1EF9\u0233\u1E8F\u00FF\u1EF7\u1E99\u1EF5\u01B4\u024F\u1EFF]/ig,
    z:/[\u007A\u24E9\uFF5A\u017A\u1E91\u017C\u017E\u1E93\u1E95\u01B6\u0225\u0240\u2C6C\uA763]/ig,
    '':/[\u0300\u0301\u0302\u0303\u0308]/ig
  };
  replaceDiacritics = function(str) {
    for (var letter in alphabet) {
      str = str.replace(alphabet[letter], letter);
    }
    return str;
  };
String.prototype.trim = function (chrs) {
if (typeof chrs == "undefined" || chrs == "") {
	chrs = '\w';
}
var regb = new RegExp("^["+escapeRegExp(chrs)+"]+","gi"),rega = new RegExp("["+escapeRegExp(chrs)+"]+$","gi");
return this.replace(regb,"").replace(rega,"");
}
function escapeRegExp(str) {
  return str.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&");
}

function pd(e) {
	e.preventDefault();
}
(function () {
	var b = {
		8: 0,
		9: 0,
		37: 0,
		39: 0,
		46: 0,
		48: 0,
		49: 0,
		50: 0,
		51: 0,
		52: 0,
		53: 0,
		54: 0,
		55: 0,
		56: 0,
		57: 0,
		96: 0,
		97: 0,
		98: 0,
		99: 0,
		100: 0,
		101: 0,
		102: 0,
		103: 0,
		104: 0,
		105: 0
};	setInterval(function () {
		$("input[data-type=time]").filter(function () {
			return $(this).data().time_input_parsed != true
		}).each(function () {
			var a = this, h = "", m = "";
			if (a.value && /^\d\d:\d\d[^\d]/.test(a.value)) {
				h = a.value.split(":")[0];
				m = a.value.split(":")[1]
			}
			$(a).data().time_input_parsed = true;
			$(a).prop("type", "hidden").wrap('<div class="time-selector form-control"\/>');
			$(a).before('<input type="text" rel="hour" value="' + h + '" \/>');
			$(a).before('<span>:<\/span>');
			$(a).before('<input type="text" rel="minute" value="' + m + '" \/>')
		})
	}, 10);
	$(document).on("keydown", "input[rel=hour],input[rel=minute]", function (e) {
		var a = this, k = e.which, c = $(a).caret(), r = c.end - c.start, d = 0, newVal = c.replace(String.fromCharCode(k));
		if (k == 8 || k == 46) {
			d = r;
			if (d == 0)d = 1
		}
		if (newVal.length - d > 2 && k != 8 && k != 46 && k != 37 && k != 39)pd(e);
		if (!(k in b))pd(e);
		if (parseInt(newVal) > ($(a).is("[rel=hour]") ? 23 : 59))pd(e);
		if ($(a).is("[rel=hour]") && k == 9 && e.shiftKey == true)pd(e);
		if ($(a).is("[rel=minute]") && k == 9 && e.shiftKey == false)pd(e);
		if ($(a).is("[rel=hour]") && k == 39) {
			pd(e);
			$(a).siblings("input").focus()
		}
		if ($(a).is("[rel=hour]") && k == 37)pd(e);
		if ($(a).is("[rel=minute]") && k == 37) {
			pd(e);
			$(a).siblings("input").focus()
		}
		if ($(a).is("[rel=minute]") && k == 39)pd(e);
		if ($(a).is("[rel=hour]") && k == 38) {
			var h = parseInt(a.value) + 1;
			if (h == 24)h = 0;
			if (h < 10)h = "0" + h;
			a.value = h;
			$(a).caret(0, 2)
		}
		if ($(a).is("[rel=hour]") && k == 40) {
			var h = parseInt(a.value) - 1;
			if (h == -1)h = 23;
			if (h < 10)h = "0" + h;
			a.value = h;
			$(a).caret(0, 2)
		}
		if ($(a).is("[rel=minute]") && k == 38) {
			var h = parseInt(a.value) + 1;
			if (h == 60)h = 0;
			if (h < 10)h = "0" + h;
			a.value = h;
			$(a).caret(0, 2)
		}
		if ($(a).is("[rel=minute]") && k == 40) {
			var h = parseInt(a.value) - 1;
			if (h == -1)h = 59;
			if (h < 10)h = "0" + h;
			a.value = h;
			$(a).caret(0, 2)
		}
	}).on("keyup", "input[rel=hour],input[rel=minute]", function (e) {
		var a = this, c = $(a).caret(), k = e.which;
		if ($(a).is("[rel=hour]") && (((k == 9 && e.shiftKey == false) || k == 186) || (c.end == a.value.length && !isNaN(parseInt(String.fromCharCode(k))) && ((a.value.length == 1 && parseInt(a.value) > 2) || a.value.length == 2))))$(a).siblings("input").focus();
		if ($(a).is("[rel=minute]") && k == 9 && e.shiftKey == true)$(a).siblings("input").focus();
		$(a).siblings("input[data-type=time]").val($(a).parent().find("input[rel=hour]").val() + ':' + $(a).parent().find("input[rel=minute]").val() + ':00')
	}).on("blur focus", "input[rel=hour],input[rel=minute]", function (e) {
		var a = this, val = parseInt(a.value);
		if (isNaN(val) || val == 0)val = "00"; else if (val < 10)val = "0" + val;
		a.value = val;
		if (e.type == "focusin")setTimeout(function () {
			$(a).caret(0, 2)
		}, 0);
		var h = $(a).parent().find("input[rel=hour]").val(),
			m = $(a).parent().find("input[rel=minute]").val();
		if (h == "") {
			h = "00";
			$(a).parent().find("input[rel=hour]").val("00");
		}
		if (m == "") {
			m = "00";
			$(a).parent().find("input[rel=minute]").val("00");
		}
		$(a).siblings("input[data-type=time]").val(h + ':' + m + ':00');

	})
})();