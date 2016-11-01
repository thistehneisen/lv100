


function replaceSelectorInputs() {
	if ($('input.selector').filter(function(){return !$(this).prev().is("a.selector")}).length > 0) {
		$('input.selector').filter(function(){return !$(this).prev().is("a.selector")}).hide().addClass('parsed')
			.before('<a href="javascript:" class="selector"><span>'+I81n.t("{{Switch: No}}")+'</span><em></em></a>')
			.each(function() {
				if ($(this).is(':checked')) $(this).prev().addClass('enabled').children('span').html(I81n.t("{{Switch: Yes}}"));
			});
	}
}
$(function() {
	
	/*$('body').on('mousedown', '.dropdown:focus', function(e) {
		$(this).one('click', function() {
			$(this).blur();
		});
	});*/



	$("nav.sitenav li:has(ul)").each(function(){
		$(this).addClass("dropdown");
		$(this).children("a").on("click",function(e){
			e.preventDefault();
			var p = $(this).parent();
			p.siblings().removeClass("active");
			if (p.find("ul").is(":visible")) {
				p.removeClass("active");
			}
			else {
				p.addClass("active");
				$('.dropdown').removeClass('open');
			}
		});
	});

	$('body').on('click', function() {
		$('.dropdown').removeClass('open').blur();
	});

	$('body').on('click', function() {
		$('.dropdown.active').removeClass('active');
	});

	$('body').on('click', '.dropdown', function(e) {
		if ($(this).parents(".sitenavpart").length) e.stopPropagation();
		if ($(this).parents("nav.sitenav").length == 0) $("nav.sitenav li.active:has(ul)").removeClass("active");
		var opened = $(this).is(".open");
		$(".dropdown.open").removeClass("open");
		if (opened) $(this).removeClass('open').blur();
		else $(this).addClass('open');
	});

	$('body').on('click', 'a[href="#"]', function(e) {
		e.preventDefault();
	});
	
	$('section.block').on('click', 'div.fakeinput', function(e) {
		$(this).find('input').first().focus();
	});

	jQuery.fn.setprogress = function(percent) {
	    $(this).children('div').css('width',percent+'%');
	    return percent + '%';
	};

	$(document).on('click', 'div.groupbutton a.button', function(e) {
		var siblings = $(this).siblings('a.button');
		var select = ($(this).parent().parent().hasClass('select') || $(this).parent().parent().hasClass('tabs') || $(this).parent().parent().hasClass('multi'));
		var multi = $(this).parent().parent().hasClass('multi');
		var noempty = $(this).parent().parent().hasClass('noempty');
		var selected = $(this).hasClass('selected');

		if ($(this).attr('href') == "#") e.preventDefault();

		if (select) {
			if (multi) {
				if (selected && !(noempty && siblings.filter('.selected').length === 0)) {
					$(this).removeClass('selected');
					selected = false;
				}
				else {
					$(this).addClass('selected');
					selected = true;
				}
			} else {
				siblings.removeClass('selected');
				$(this).addClass('selected');
				selected = true;
			}
		}
	});

	$(document).ajaxComplete(replaceSelectorInputs).trigger('ajaxComplete');

	$(document).on('click', 'a.selector', function() {
		var selector = $(this);
		if (selector.hasClass('enabled')) {
			selector.removeClass('enabled').next().attr('checked',false).trigger("selector.change");
			setTimeout(function() { selector.children('span').html(I81n.t("{{Switch: No}}")); }, 100);
		} else {
			selector.addClass('enabled').next().attr('checked',true).trigger("selector.change");
			setTimeout(function() { selector.children('span').html(I81n.t("{{Switch: Yes}}")); }, 100);
		}
	});

	$(document).on('change', 'input.selector', function() {
		$(this).prev('a.selector').click();
	});

	$('body').on('change', 'select.predefined', function (e) {
		var text = $(this).val();
		var target = $('#'+$(this).attr('data-target'));

		target.focus();

		if (target.text().length === 0) target.text(text);
		else target.text(target.text() + " " + text);

		$(this).val(0); // noliekam dropdownu atpakaļ kā bija
	});
	
	if ($('table.activity-table').length > 0) {
		$('table.activity-table tbody').on('click', 'tr', function(e) {
			$(this).toggleClass('selected');
			//$(this).find('input[type="checkbox"]').attr("checked", !checkBoxes.attr("checked"));
			if ($(this).hasClass('selected')) {
				$(this).find('input[type="checkbox"]').attr('checked', true).change();
			} else {
				$(this).find('input[type="checkbox"]').removeAttr('checked').change();
			}
		});
		
		$('table.activity-table tbody tr').on('click', 'a', function(e) {
			e.stopPropagation();
		});
		
		$('table.activity-table tbody').on('change', 'input[type="checkbox"]', function(e) {
			if($(this).parents('tbody').find('input[type="checkbox"]').length === $(this).parents('tbody').find('input[type="checkbox"]:checked').length) { // all boxes checked
				$(this).parents('table.activity-table').find('thead input[type="checkbox"]').attr('checked', true);
			} else {
				$(this).parents('table.activity-table').find('thead input[type="checkbox"]').removeAttr('checked');
			}
		});
		
		$('table.activity-table thead').on('change', 'input[type="checkbox"].selectall', function(e) {
			if ($(this).is(':checked')) {
				$(this).parents('table.activity-table').find('tbody input[type="checkbox"]').attr('checked', true).parents('tr').addClass('selected');
			} else {
				$(this).parents('table.activity-table').find('tbody input[type="checkbox"]').removeAttr('checked').parents('tr').removeClass('selected');
			}
		});
	}	

/*
	if ($('nav.sidebar').length > 0) {
		var sidebar = $('nav.sidebar');
		var padding = 20;
		var startingPos = sidebar.offset().top - padding;		
		
		
		$(window).scroll(function() {
			var currentPos = $(window).scrollTop();
			var bottom = startingPos + sidebar.parent().height() - padding;
			var height = sidebar.height();
			
			if (currentPos >= startingPos && currentPos + height < bottom && !sidebar.hasClass('fixed')) {
				sidebar.addClass('fixed').css({'position':'fixed','top':padding, 'bottom':'auto'});
			}
			else if (currentPos + height >= bottom && sidebar.hasClass('fixed')) {
				sidebar.removeClass('fixed').css({'position':'absolute', 'top':'auto', 'bottom':padding});
			}
			else if (currentPos < startingPos && currentPos + height < bottom && sidebar.hasClass('fixed')) {
				sidebar.removeClass('fixed').css({'position':'absolute'});
			}
		});
	}
*/
	/*if ($('aside.rightbar').length == 1) {
		var sidebar = $('aside.rightbar');
		var padding = 20;
		var startingPos = sidebar.offset().top - 20;
		if (sidebar.hasClass('tabsontop')) startingPos += 10;
		var startingPosX = sidebar.offset().left - padding;
		
		
		$(window).scroll(function() {
			var currentPos = $(window).scrollTop();
			var bottom = startingPos + sidebar.parent().height() - padding;
			var height = sidebar.height();
			
			if (currentPos >= startingPos && currentPos + height < bottom && !sidebar.hasClass('fixed')) {
				var postop = padding;
				if (sidebar.hasClass('tabsontop')) postop = padding * 2 + 10;
				sidebar.addClass('fixed').css({'position':'fixed','top':postop, 'left':startingPosX, 'bottom':'auto'});
			}
			else if (currentPos + height >= bottom && sidebar.hasClass('fixed')) {
				sidebar.removeClass('fixed').css({'position':'absolute', 'top':'auto', 'left':'auto', 'bottom':padding});
			}
			else if (currentPos < startingPos && currentPos + height < bottom && sidebar.hasClass('fixed')) {
				sidebar.removeClass('fixed').css({'position':'absolute', 'left':'auto', 'top':'auto'});
			}
		});
		if ($('form.addbody').length == 1) {
			
		}
	}*/

	if ($('ul.tree').length > 0) { // lol @ 200KB smagām jQuery library, kas dara ± to pašu
		// arī bez augšejā if'a, ja ul.tree neeksistētu, kods nepalaistos, bet šādi vieglāk galvā nodalīt (+ code folding)
		// $('ul.tree').on('click', 'li a', function(e) { e.stopPropagation(); }); // WTF?
		$(document).on('click', 'ul.tree li:first-child', function(e) {
			if ($(e.target).is(".actionbutton") || $(e.target).parents(".actionbutton").length) return;
			//if($(this).siblings().length > 0) $(this).parent().toggleClass('expanded');
			if(!$(this).is(':only-child')) $(this).parent().toggleClass('expanded');
		});
	}
	
	if ($('form.addbody').length && $('aside.rightbar').length) {
		setInterval(function(){
			var max = Math.max($('form.addbody').last().offset().top+$('form.addbody').last().height()+35,$('aside.rightbar').last().offset().top+$('aside.rightbar').last().height()+10);
			if ($('body').height() != max) $('body').height(max);
		}, 10);
	}


	if ($('.tinymce_small:not(.tm-initialized)').length) SetupSmallTinyMCE(".tinymce_small",{},function(){
		$('.tinymce_small').addClass('tm-initialized');
	});
	if ($('.tinymce_big:not(.tm-initialized)').length) SetupBigTinyMCE(".tinymce_big",{},function(){
		$('.tinymce_big').addClass('tm-initialized');
	});
	
	/*if (history.pushState) {
		$(window).on("popstate",function(e){
			if (e.originalEvent.state&&e.originalEvent.state.ajaxify) {
				console.log(e.originalEvent.state);
				var h = document.location.href,
					c = e.originalEvent.state.ajaxify;
				//$.modal({content:'<span class="loading">Loading...</span>'});
				$.get(h,function(htmlText){
					$(c).replaceWith($(htmlText).find(c));
					//$.modal("destroy");
				});
			}
			else console.log(e.originalEvent);
		});
	}
	$(document).on("click","a[ajaxify]",function(e){
		if (history.pushState) {
			e.preventDefault();
			var h = this.getAttribute("href"),
				c = this.getAttribute("ajaxify");
			//$.modal({content:'<span class="loading">Loading...</span>'});
			history.pushState({ajaxify:c}, "Loaded", h);
			$.get(h,function(htmlText){
				$(c).replaceWith($(htmlText).find(c));
				//$.modal("destroy");
			});
		}
	});*/
});

function imgEditTool(imgOptions) {
	window.imgEditToolObject = this;
	var me = window.imgEditToolObject;
	$("#image-edit").dialog("close");
	me.dialog = $(document.createElement("div")).attr("id","image-edit").dialog({title:imgOptions.filename,height:$(window).height()*0.9,width:$(window).width()*0.9,dialogClass:"fixed-dialog",modal:true,resizable:false,draggable:false,buttons:[
		{
			text: "Atcelt",
			click: function(){
				$(this).dialog("close");
			}
		}
	],open:function(){
		$(this).css({overflow:"hidden"}).append(
			$(document.createElement("div")).addClass("img-container").append(
				$(document.createElement("div")).addClass("img-parent")
			)
		).append(
			$(document.createElement("div")).addClass("props").attr("id","img-props")
		);

		var propsDiv = $("#img-props"),
			imgDialog = $(this),
			imgContainer = imgDialog.find(".img-container"),
			imgParent = imgDialog.find(".img-parent");

		var that = this;
		imgContainer.css({width: $(that).width(), height: $(that).height()});
		var img = new Image();
		$(img).css({maxWidth:"100%",maxHeight:"100%"}).load(function(){
			var originalSize = {w: imgOptions.width, h: imgOptions.height};
			$(that).find(".img-parent").append(img);
			$(that).find(".img-parent img").each(function(){
				$(this).css({position: "absolute", top: (imgParent.height()-$(this).height())/2, left: (imgParent.width()-$(this).width())/2});
			});
			var position = {x: (imgParent.width()-$(img).width())/2, y: (imgParent.height()-$(img).height())/2},
				size = {w: $(img).width(), h: $(img).height()},
				bounds = {x1: position.x, y1: position.y, x2: position.x+size.w, y2: position.y+size.h},
				scale = {
					d: Math.sqrt(Math.pow(size.w,2)+Math.pow(size.h,2))/Math.sqrt(Math.pow(originalSize.w,2)+Math.pow(originalSize.h,2)),
					w: size.w/originalSize.w,
					h: size.h/originalSize.h
				};


			var imgTools = $(document.createElement("div")).attr("id","img-tools").appendTo(propsDiv);

			var imgToolsBackForward = $(document.createElement("div")).addClass("backforward").appendTo(imgTools);
			var backButton = $(document.createElement("a")).attr({disabled:"disabled",href:"#"}).on("click",function(e){
				e.preventDefault();
				if (!$(this).is("[disabled]")) {
					$.getJSON(Settings.adminHost+'media/back/'+imgOptions.id+'/',function(resp){
						me = new imgEditTool(resp).open();
					});
				}
			}).html("Soli atpakaļ").appendTo(imgToolsBackForward);
			var forwardButton = $(document.createElement("a")).attr({disabled:"disabled",href:"#"}).on("click",function(e){
				e.preventDefault();
				if (!$(this).is("[disabled]")) {
					$.getJSON(Settings.adminHost+'media/forward/'+imgOptions.id+'/',function(resp){
						me = new imgEditTool(resp).open();
					});
				}
			}).html("Soli uz priekšu").appendTo(imgToolsBackForward);

			var cropToolButton = $(document.createElement("a")).attr({href:"#"}).css({display: "block"}).addClass("actionbutton").on("click",function(e){ e.preventDefault();
				me.cropToolInit ();
			}).html("Izgriezt attēlu").appendTo(imgTools);
			var grayscaleButton = $(document.createElement("a")).attr({href:"#"}).css({display: "block"}).addClass("actionbutton").on("click",function(e){ e.preventDefault();
				me.grayscale ();
			}).html("Padarīt melnbaltu").appendTo(imgTools);
			var negateButton = $(document.createElement("a")).attr({href:"#"}).css({display: "block"}).addClass("actionbutton").on("click",function(e){ e.preventDefault();
				me.negate ();
			}).html("Apgriezt krāsas").appendTo(imgTools);
			var gblurButton = $(document.createElement("a")).attr({href:"#"}).css({display: "block"}).addClass("actionbutton").on("click",function(e){ e.preventDefault();
				me.gblur ();
			}).html("Gausian blur").appendTo(imgTools);
			var sblurButton = $(document.createElement("a")).attr({href:"#"}).css({display: "block"}).addClass("actionbutton").on("click",function(e){ e.preventDefault();
				me.sblur ();
			}).html("Selective blur").appendTo(imgTools);

			if (imgOptions.backEnabled) backButton.removeAttr("disabled");
			if (imgOptions.forwardEnabled) forwardButton.removeAttr("disabled");

			var imgToolsCancelSave = $(document.createElement("div")).addClass("cancelsave").css({
				position: "absolute", bottom: 0, right: 0, textAlign: "right"
			}).appendTo(imgTools);
			if (imgOptions.history > 0) var saveButton = $(document.createElement("a")).attr({href:"#"}).addClass("actionbutton green").on("click",function(e){
				e.preventDefault();


			}).html("Saglabāt").appendTo(imgToolsCancelSave);


			me.grayscale = function () {
				me.doWith(imgOptions.id, "filter", ["grayscale"], function(data){
					me = new imgEditTool(data).open();
				});
			};
			me.negate = function () {
				me.doWith(imgOptions.id, "filter", ["negate"], function(data){
					me = new imgEditTool(data).open();
				});
			};
			me.gblur = function () {
				me.doWith(imgOptions.id, "filter", ["gblur"], function(data){
					me = new imgEditTool(data).open();
				});
			};
			me.sblur = function () {
				me.doWith(imgOptions.id, "filter", ["sblur"], function(data){
					me = new imgEditTool(data).open();
				});
			};

			me.deleteRevisions = function(id) {
				$.get(Settings.adminHost+'media/delete-revisions/'+imgOptions.id+'/');
			};

			me.cropToolInit = function (options) {

				var defaults = {
					desiredSize: false,
					cancel: false,
					save: false,
					maxSize: {w: 0, h: 0}
				};
				var settings = $.extend( {}, defaults, options );


				var cropToolWrapper = $(document.createElement("div")).attr("id","crop-tool-parent").css({top: position.y, left: position.x, width: size.w, height: size.h});
				imgParent.append(cropToolWrapper);

				var cropToolBounds = {w:0,h:0,x:0,y:0,minW:0,minH:0};

				var keep_ratio = true;
				if (!settings.desiredSize) {
					keep_ratio = false;
					settings.desiredSize = {w:originalSize.w,h:originalSize.h};
				} else {
					cropToolBounds.minW = scale.w*settings.desiredSize.w;
					cropToolBounds.minH = scale.h*settings.desiredSize.h;
				}

				var orig_ratio = settings.desiredSize.w/settings.desiredSize.h,
					pnh = size.h,
					pnw = Math.floor(pnh*orig_ratio);
				if (pnw > size.w) {
					pnw = size.w;
					pnh = Math.floor(pnw/orig_ratio);
				}
				cropToolBounds.w = pnw;
				cropToolBounds.h = pnh;
				cropToolBounds.x = (pnw < size.w ? (size.w-pnw)/2 : 0);
				cropToolBounds.y = (pnh < size.h ? (size.h-pnh)/2 : 0);
				if (!keep_ratio) {
					cropToolBounds.w = size.w;
					cropToolBounds.h = size.h;
					cropToolBounds.x = 0;
					cropToolBounds.y = 0;
				}

				var cropTool = $(document.createElement("div")).attr("id","crop-tool").css({width: cropToolBounds.w, height: cropToolBounds.h, top: cropToolBounds.y, left: cropToolBounds.x})
					.append($(document.createElement("img")).attr("src", (/^http[s]?\:\/\//.test(imgOptions.filepath) ? "" : Settings.Host)+imgOptions.filepath).css({width: size.w, height: size.h, position: "absolute"}))
					.append($(document.createElement("div")).addClass("leftborder"))
					.append($(document.createElement("div")).addClass("rightborder"))
					.append($(document.createElement("div")).addClass("topborder"))
					.append($(document.createElement("div")).addClass("bottomborder"))
					.append($(document.createElement("div")).addClass("ui-resizable-handle ui-resizable-nw tl"))
					.append($(document.createElement("div")).addClass("ui-resizable-handle ui-resizable-n t"))
					.append($(document.createElement("div")).addClass("ui-resizable-handle ui-resizable-ne tr"))
					.append($(document.createElement("div")).addClass("ui-resizable-handle ui-resizable-e r"))
					.append($(document.createElement("div")).addClass("ui-resizable-handle ui-resizable-se br"))
					.append($(document.createElement("div")).addClass("ui-resizable-handle ui-resizable-s b"))
					.append($(document.createElement("div")).addClass("ui-resizable-handle ui-resizable-sw bl"))
					.append($(document.createElement("div")).addClass("ui-resizable-handle ui-resizable-w l"));

				cropToolWrapper.append(cropTool);

				var cropToolOverlay = $(document.createElement("div")).attr("id","crop-tool-overlay").css({top: 0, left: 0, width: size.w, height: size.h});
				cropTool.before(cropToolOverlay);

				imgTools.remove();
				cropTool.draggable({containment: "parent", drag: function(e, ui){
					$(this).find("img").css({top:(-1*ui.position.top),left:(-1*ui.position.left)});
				}, cancel: ".ui-resizable-handle"}).resizable({containment: "parent", handles: { nw: ".tl", n: ".t", ne: ".tr", e: ".r", se: ".br", s: ".b", sw: ".bl", w: ".l"}, aspectRatio: keep_ratio, resize: function(e, ui){
					if (ui.size.width < 0 || ui.size.height < 0) {
						$(this).css({width: ui.originalSize.width, height: ui.originalSize.height});
						return false;
					}
					$(this).find("img").css({top:(-1*$(this).position().top),left:(-1*$(this).position().left)});
					$("#crop-tool-info").text(Math.round($(this).width()/scale.w)+"x"+Math.round($(this).height()/scale.h));
				},minWidth:cropToolBounds.minW,minHeight:cropToolBounds.minH}).each(function(){
					$(this).find("img").css({top:(-1*$(this).position().top),left:(-1*$(this).position().left)});
					$("#crop-tool-info").text(Math.round($(this).width()/scale.w)+"x"+Math.round($(this).height()/scale.h));
				});

				var imgToolsCancelSave = $(document.createElement("div")).addClass("cancelsave").appendTo(imgTools);
				var cancelButton = $(document.createElement("a")).attr({href:"#"}).addClass("btn btn-default").on("click",function(e){
					e.preventDefault();

					if (typeof settings.cancel == "function") settings.cancel.call(me);
					else me = new imgEditTool(imgOptions).open();
				}).html("Atcelt").appendTo(imgToolsCancelSave);
				var btns = imgEditToolObject.dialog.dialog("option","buttons");
				btns.push({
					text: "Pielietot",
					"class": "btn btn-success",
					click: function(e){
						e.preventDefault();
						if (typeof settings.save == "function") settings.save.apply(me, [{
							i: imgOptions.filepath,
							x: Math.round(cropTool.position().left/scale.w), // x1
							y: Math.round(cropTool.position().top/scale.h), // x2
							w: Math.round(cropTool.width()/scale.w), // w
							h: Math.round(cropTool.height()/scale.h), // h
							r: settings.desiredSize,
							m: settings.maxSize
						}]);
					}
				});
				imgEditToolObject.dialog.dialog("option","buttons",btns);
			};
		});
		img.src = (/^http[s]?\:\/\//.test(imgOptions.filepath) ? "" : Settings.Host)+imgOptions.filepath;
	},close:function(){
		$(this).dialog("destroy").remove();
		//me.deleteRevisions(imgOptions.id);
	},autoOpen: false});

	me.doWith = function(id, action, params, callback) {
		$.getJSON(Settings.adminHost+"media/dowith/"+id+"/"+action+"/"+params.join("/"),function(resp){
			callback&&callback(resp);
		});
	};

	me.open = function(callback){
		$("#image-edit").dialog("open");
		if (typeof me.cropToolInit != "function") {
			setTimeout(function(){
				me.open(callback);
			},5);
			return me;
		}
		if (typeof callback == "function") callback.call(me);
		return me
	};
	me.close = function(){ $("#image-edit").dialog("close"); return me };

	return window.imgEditToolObject;
}





function SetupBigTinyMCE(selector, params, callback) {
	var options = {
		script_url : Settings.adminBaseHost+'js/tiny_mce/tinymce.min.js',
		content_css: Settings.adminBaseHost+'editor.styles.css?v3',
		theme : "modern",
		plugins : "pagebreak layer insertdatetime searchreplace contextmenu paste noneditable visualchars nonbreaking preview media image fullscreen link textcolor code table",
		toolbar1 : "bold italic underline strikethrough forecolorpicker | formatselect | cut copy paste pastetext pasteword | link unlink anchor cleanup removeformat more | xembed fileup image2 galleries table",
		toolbar2 : "alignleft aligncenter alignright alignjustify | undo redo | bullist numlist | outdent indent blockquote | forecolor backcolor | preview code fullscreen",
		menubar: false,
		statusbar : true,
		resize : true,
		object_resizing : true,
		plugin_preview_width: 810,
		block_formats: "Paragraph=p;Header=h2",
		document_base_url : Settings.Host,
		relative_urls : false,
		remove_script_host : false,
		language: I81n.language,
		image_advtab: true,
		valid_elements: "@[id|class|style|title|dir<ltr?rtl|lang|xml::lang|onclick|ondblclick|onmousedown|onmouseup|onmouseover|onmousemove|onmouseout|onkeypress|onkeydown|onkeyup],a[rel|rev|charset|hreflang|tabindex|accesskey|type|name|href|target|title|class|onfocus|onblur],strong/b,em/i,strike,u,#p,-ol[type|compact],-ul[type|compact],-li,br,img[longdesc|usemap|src|border|alt=|title|hspace|vspace|width|height|align|data-caption],-sub,-sup,-blockquote[cite],-table[border|cellspacing|cellpadding|width|frame|rules|height|align|summary|bgcolor|background|bordercolor],-tr[rowspan|width|height|align|valign|bgcolor|background|bordercolor],tbody,thead,tfoot,#td[colspan|rowspan|width|height|align|valign|bgcolor|background|bordercolor|scope],#th[colspan|rowspan|width|height|align|valign|scope],caption,-div,-span,-code,-pre,address,-h2,hr[size|noshade],-font[face|size|color],dd,dl,dt,cite,abbr,acronym,del[datetime|cite],ins[datetime|cite],object[classid|width|height|codebase|*],param[name|value],embed[type|width|height|src|*],script[src|type],map[name],area[shape|coords|href|alt|target],bdo,button,col[align|char|charoff|span|valign|width],colgroup[align|char|charoff|span|valign|width],dfn,fieldset,form[action|accept|accept-charset|enctype|method],input[accept|alt|checked|disabled|maxlength|name|readonly|size|src|type|value|tabindex|accesskey],kbd,label[for],legend,noscript,optgroup[label|disabled],option[disabled|label|selected|value],q[cite],samp,select[disabled|multiple|name|size],small,textarea[cols|rows|disabled|name|readonly],tt,var,big",
		extended_valid_elements: 'iframe[width|height|allowfullscreen|frameborder|src],object[width|height],embed[width|height|flashvars|allowfullscreen|src],audio,video,source,param[name|value]',
		setup: function(ed) {
			ed.on('ObjectResized', function(e) {
				var nw = e.width/$(e.target).parent().width()*100;
				if (nw > 100) nw=100;
				$(e.target).css({width:nw+"%",height:"auto"}).removeAttr("width").removeAttr("height");
			});
			ed.on('change', function(e) {
				if (e.originalEvent && e.originalEvent.command) {
					if (["JustifyLeft","JustifyCenter","JustifyRight","JustifyFull"].indexOf(e.originalEvent.command) >= 0) {
						if ($(ed.selection.getNode()).is("img")) {
							$(ed.selection.getNode()).removeClass("img-ct-none img-ct-left img-ct-right").addClass('img-ct-'+$(ed.selection.getNode()).css('float'));
						}
					}
				}
			});
			ed.addButton('xembed', {
				title: I81n.t("{{Insert Embed}}"),
				icon: 'othericons ic-embed2',
				onclick: function() {
					insertEmbedCode(ed);
				}
			});
			ed.addButton('fileup', {
				title: I81n.t("{{Insert file}}"),
				icon: 'othericons ic-upload',
				onclick: function() {
					//upload_file_modal(ed);
					//console.log(ed.selection.getContent());
					FileManagerUploadPopup.open("mce",function(fmu){
						var selection = ed.selection.getContent();
						if (!selection) selection = fmu.fileName;
						ed.selection.setContent('<a href="'+Settings.Host+fmu.filePath+'">'+selection+'</a>');
					});
				}
			});
			ed.addButton('image2', {
				title: I81n.t("{{Insert image}}"),
				icon: 'image',
				onclick: function() {
					OpenImageAdder(ed);
				},
				onPostRender: function() {
					var ctrl = this;

					ed.on('NodeChange', function(e) {
						ctrl.active(e.element.nodeName == 'IMG' && !$(e.element).is(".mce-object"));
					});
				}
			});
			if ($("form.addbody.new").attr("lang")) {
				ed.addButton('galleries', {
					title: 'Ievietot galeriju',
					icon: 'othericons ic-images',
					onclick: function () {
						insertGallery(ed,$("form.addbody.new").attr("lang"));
					},
					onPostRender: function () {
						var ctrl = this;

						ed.on('NodeChange', function(e) {
							ctrl.active(e.element.nodeName == 'IMG' && !$(e.element).is(".mce-object"));
						});
					}
				});

			}

		},
		mediaUploadUrl: Settings.adminHost + "media/upload_mce",
		uploaderScript: Settings.adminBaseHost + "js/plupload/plupload.full.js",
		flash_swf_url : Settings.adminBaseHost + "js/plupload/plupload.flash.swf",
		silverlight_xap_url : Settings.adminBaseHost + "plupload/plupload.silverlight.xap"
	};
	$.each(params, function (key, value) {
		options[key]=value;
	});
	if (typeof selector == "object") selector.each(function(){$(this).tinymce(options);});
	else $(selector).each(function(){$(this).tinymce(options);});
	callback&&callback();
}

function insertGallery(editor,lang) {
	var galleryDialog = $($.parseHTML("<div/>")).attr({id:"galleryAddDialog"}).html('<span class="loading">Ielādē...</span>').dialog({
		width: 800,
		height: 405,
		modal: true,
		draggable: false,
		resizable: false,
		close: function(){
			$(this).dialog("destroy").remove();
		},
		open: function(){
			$(this).load(Settings.adminHost+"galleries/form_mce/"+lang+"/");
		},
		buttons: [
			{
				text: I81n.t("{{Cancel}}"),
				click: function(){
					$(this).dialog("close");
				}
			},
			{
				text: I81n.t("{{Insert}}"),
				"class": "btn-success",
				click: function(){},
				disabled: true,
				id: "galleryPopupInsertButton"
			}
		]
	});
}

var OpenImageAdder = function(editor) {
	var imageDialog = $($.parseHTML("<div/>")).attr({id:"imageUploadDialog"}).html('<span class="loading">'+I81n.t("{{Loading}}"),+'</span>').dialog({
		width: 800,
		height: 489,
		modal: true,
		draggable: false,
		resizable: false,
		close: function(){
			$(this).dialog("destroy").remove();
		},
		open: function(){
			$(this).load(Settings.adminHost+"media/form_mce/");
		},
		buttons: [
			{
				text: I81n.t("{{Cancel}}"),
				click: function(){
					$(this).dialog("close");
				}
			},
			{
				text: I81n.t("{{Insert}}"),
				"class": "btn-success",
				click: function(){},
				disabled: true,
				id: "imagePopupInsertButton"
			}
		]
	});
};

var FileUploaderSingle = function(uplButtons, callback, func, params){
	if (typeof uplButtons == "string") uplButtons = $(uplButtons);
	if (typeof params == 'undefined') params = {};

	uplButtons.each(function(){
		var uplButton = $(this),
			uplButtonContainer = $(this).parent();

		if (!uplButton.attr("id"))
			uplButton.attr({"id":"uplb_"+(new Date()).getTime()});
		if (!uplButtonContainer.attr("id"))
			uplButtonContainer.attr({"id":"uplc_"+(new Date()).getTime()});


		var haveProgress = uplButton.find(".progress").length,
			progressBar = uplButton.find(".progress");

		uplButton.data().uploader = new plupload.Uploader({
			runtimes: 'html5,flash,silverlight',
			browse_button: uplButton.attr("id"),
			container: uplButtonContainer.attr("id"),
			max_file_size: '200mb',
			multi_selection: false,
			url: Settings.Host + 'media.upload/?type=file&'+$.param(params)+'&session='+Settings.session_id,
			flash_swf_url: Settings.adminBaseHost + 'js/plupload/plupload.flash.swf',
			silverlight_xap_url: Settings.adminBaseHost + 'js/plupload/plupload.silverlight.xap'
		});
		if (uplButton.length) uplButton.data().uploader.init();
		uplButton.data().uploader.bind('FilesAdded', function (up, files) {
			if (haveProgress) progressBar.show().find(".progress-bar").css({width:"0"}).attr("aria-valuenow",0).find("span").html("0 %");
			else $.modal({content: '<span class="loading">'+I81n.t("{{Uploading file}}")+' (<span id="upload-progress"><\/span>)...<\/span>'});
			up.refresh();
			uplButton.blur().data().uploader.start();
		});
		uplButton.data().uploader.bind('UploadProgress', function (up, file) {
			if (haveProgress) progressBar.show().find(".progress-bar").css({width:file.percent+"%"}).attr("aria-valuenow",file.percent).find("span").html(file.percent+" %");
			else $('#upload-progress').html(file.percent + '%');
		});
		uplButton.data().uploader.bind('FileUploaded', function (up, file, response) {
			var jsonrpc = $.parseJSON(response.response);
			if (jsonrpc.error) {
				$.modal({content: jsonrpc.error.message, appendClose: I81n.t("{{Ok}}")});
				return;
			}
			if (haveProgress) progressBar.hide();
			else $.modal("destroy");
			if (typeof callback == "function") callback.apply(uplButton.get(0),[jsonrpc, uplButton.data().uploader]);
		});
	});
};
var ImageUploaderSingle = function(uplButtons, callback, func, params){
	if (typeof uplButtons == "string") uplButtons = $(uplButtons);

	uplButtons.each(function(){
		var uplButton = $(this),
			uplButtonContainer = $(this).parent();

		if (!uplButton.attr("id"))
			uplButton.attr({"id":"uplb_"+(new Date()).getTime()});
		if (!uplButtonContainer.attr("id"))
			uplButtonContainer.attr({"id":"uplc_"+(new Date()).getTime()});


		var haveProgress = uplButton.find(".progress").length,
			progressBar = uplButton.find(".progress");

		uplButton.data().uploader = new plupload.Uploader({
			runtimes: 'html5,flash,silverlight',
			browse_button: uplButton.attr("id"),
			container: uplButtonContainer.attr("id"),
			max_file_size: '200mb',
			multi_selection: false,
			url: Settings.Host + 'media.upload/?type=photo&'+$.param(params)+'&session='+Settings.session_id,
			flash_swf_url: Settings.adminBaseHost + 'js/plupload/plupload.flash.swf',
			silverlight_xap_url: Settings.adminBaseHost + 'js/plupload/plupload.silverlight.xap',
			filters : [{title : "Images", extensions : "png,jpg,gif,jpeg"}]
		});
		if (uplButton.length) uplButton.data().uploader.init();
		uplButton.data().uploader.bind('FilesAdded', function (up, files) {
			if (haveProgress) progressBar.show().find(".progress-bar").css({width:"0"}).attr("aria-valuenow",0).find("span").html("0 %");
			else $.modal({content: '<span class="loading">'+I81n.t("{{Uploading file}}")+' (<span id="upload-progress"><\/span>)...<\/span>'});
			up.refresh();
			uplButton.blur().data().uploader.start();
		});
		uplButton.data().uploader.bind('UploadProgress', function (up, file) {
			if (haveProgress) progressBar.show().find(".progress-bar").css({width:file.percent+"%"}).attr("aria-valuenow",file.percent).find("span").html(file.percent+" %");
			else $('#upload-progress').html(file.percent + '%');
		});
		uplButton.data().uploader.bind('FileUploaded', function (up, file, response) {
			var jsonrpc = $.parseJSON(response.response);
			if (jsonrpc.error) {
				$.modal({content: jsonrpc.error.message, appendClose: I81n.t("{{Ok}}")});
				return;
			}
			if (haveProgress) progressBar.hide();
			else $.modal("destroy");
			if (typeof callback == "function") callback.apply(uplButton.get(0),[jsonrpc, uplButton.data().uploader]);
		});
	});
};
var FileManagerUploadPopup = new function FileManagerUploadPopup(){
	var self=this;
	self.callback = function(){};
	self.origin = "script";
	self.dialog = null;
	self.filePath = "";
	self.open = function(origin, callback){
		if (typeof origin == "function" && typeof callback == "undefined") {
			self.callback = origin;
			self.origin = "script";
		} else if (typeof callback == "function") {
			self.callback = callback;
		}
		if (typeof origin == "string") {
			self.origin = origin;
		}
		else {
			self.origin = "script";
		}
		self.openDialog();
	};
	self.close = function(){
		self.callback = function(){};
		self.origin = "script";
		if (self.dialog) {
			self.dialog.dialog("destroy").remove();
			self.dialog = null;
		}
		self.filePath = "";
		self.fileName = "";
	};
	self.openDialog = function(){
		self.dialog = $("<div\/>").dialog({
			draggable: false,
			resizable: false,
			minHeight: 0,
			modal: true,
			close: function(){
				self.close();
			},
			open: function(){
				var uplButton = $("<a\/>").addClass("btn btn-block btn-upload btn-lg btn-default").attr({"href":"#"}).text("Izvēlies failu datorā");
				var selButton = $("<a\/>").addClass("btn btn-block btn-upload btn-lg btn-default").attr({"href":"#"}).text("Izvēlies failu datubāzē");
				$(this).append(uplButton);
				$(this).append(selButton);
				FileUploaderSingle(uplButton, function(data){
					self.filePath = data.file;
					self.fileName = data.name;
					self.callback(self);
					self.close();
				},"file");
				selButton.on("click",function(e){
					e.preventDefault();
					selectFile(function(file){
						self.callback({filePath:file});
						self.close();
					});
				});
			}
		});
	};
};

function insertEmbedCode(ed) {
	$.modal({
		"appendClose"	:	I81n.t("{{Cancel}}"),
		"title"			:	I81n.t("{{Insert Embed}}"),
		"content"		:	'<textarea id="ed_tmce_htmlembed" style="width: 350px; height: 100px;"></textarea>',
		"buttons"		:	[
			{"label": I81n.t("{{Insert}}"), "className": "btn-success", "callback": function(){
				var code = $(this).parents("#modal").find("#ed_tmce_htmlembed").val();
				var embed = $('<div>'+code+'</div>');
				if (embed.find('object').length) embed.find('object').prepend('<param name="wmode" value="transparent"></param>');
				if (embed.find('embed').length) embed.find('embed').attr('wmode','transparent');
				if (embed.find('iframe').length) embed.find('iframe').attr('src',embed.find('iframe')[0].getAttribute("src")+(embed.find('iframe')[0].getAttribute("src").indexOf("?") > 0 ? '&wmode=transparent' : '?wmode=transparent'));
				ed.execCommand("mceReplaceContent",false,embed.html());
				$.modal("destroy");
			}}
		]
	});
	$('#modal').find('#ed_tmce_htmlembed').focus();
}

function initUploadAndReturnUrl() {
	tinyMCE.selectedInstance.popwin.document.image_form.src.value="";
	tinyMCE.selectedInstance.popwin.document.image_form.src.onchange();
}

function SetupSmallTinyMCE(selector, params, callback) {
	var options = {
		script_url : Settings.adminBaseHost+'js/tiny_mce/tinymce.min.js',
		content_css: Settings.adminBaseHost+'editor.styles.css',
		theme : "modern",
		plugins : "pagebreak layer insertdatetime searchreplace contextmenu paste noneditable visualchars nonbreaking preview media image fullscreen link textcolor code table",
		toolbar : "bold italic underline strikethrough forecolorpicker | bullist numlist | link unlink fileup | code",
		menubar : false,
		theme_advanced_toolbar_location : "top",
		theme_advanced_statusbar_location : "bottom",
		statusbar : true,
		resize : true,
		document_base_url : Settings.Host,
		relative_urls : false,
		remove_script_host : false,
		language: I81n.language,
		setup: function(ed){
			ed.addButton('fileup', {
				title: I81n.t("{{Insert file}}"),
				icon: 'othericons ic-upload',
				onclick: function() {
					//upload_file_modal(ed);
					//console.log(ed.selection.getContent());
					FileManagerUploadPopup.open("mce",function(fmu){
						var selection = ed.selection.getContent();
						if (!selection) selection = fmu.fileName;
						ed.selection.setContent('<a href="'+Settings.Host+fmu.filePath+'">'+selection+'</a>');
					});
				}
			});
		}
	};
	$.each(params, function (key, value) {
		options[key]=value;
	});
	if (typeof selector == "object") selector.each(function(){$(this).tinymce(options);});
	else $(selector).each(function(){$(this).tinymce(options);});
	callback&&callback();
}
function upload_file_modal(ed) {
	$.modal({
		title: I81n.t("{{Upload file}}"),
		content: '<div id="modal-up-c"><a href="javascript:" class="big upload actionbutton" id="modal-upload">Izvēlies failu</a></div>',
		appendClose: I81n.t("{{Cancel}}"),
		width: 300
	});
	var uploader = new plupload.Uploader({
		runtimes : 'html5,flash,silverlight',
		browse_button : 'modal-upload',
		container: 'modal',
		max_file_size : '20mb',
		multi_selection : false,
		url : Settings.adminHost+"media/upload_file/",
		flash_swf_url : Settings.adminBaseHost+'js/plupload/plupload.flash.swf',
		silverlight_xap_url : Settings.adminBaseHost+'js/plupload/plupload.silverlight.xap'
	});
	if ($("#modal-upload").length) uploader.init();
	uploader.bind('FilesAdded', function(up, files) {
		//$.modal({content:'<div class="loading">{{Uploading}} (<span id="upload-progress">0%<'+'/span>)...<'+'/div>'});
		
		$("#modal-up-c").css({position:"absolute",top:-10000});
		$("#modal-up-c").after('<div class="loading">'+I81n.t("{{Uploading}}")+' (<span id="upload-progress">0%<'+'/span>)...<'+'/div>');

		up.refresh(); // Reposition Flash/Silverlight
		uploader.start();
	});
	uploader.bind('UploadProgress', function(up, file) {
		$('#upload-progress').html(file.percent+'%');
	});
	uploader.bind('FileUploaded', function(up, file, response) {
		var jsonrpc = $.parseJSON(response.response);
		if (jsonrpc.error) {
			$.modal({content:jsonrpc.error.message,appendClose:"OK"});
			return;
		}
		$.modal({
			title: I81n.t("{{File uploaded}}"),
			content: "<div style=\"padding: 3px;\"><section class=\"infotip green icon yes\"><h1>Gatavs!</h1><p>Tagad atliek tikai nokopēt linku un ievietot to redaktorā!</p></section><input type=\"text\" value=\""+Settings.Host+jsonrpc.file+"\"></div>",
			appendClose: I81n.t("{{Ok}}"),
			width: 500
		});
		$("#modal input").focus().select();
		//$("#thumb-pic").attr({src:<?=json_encode($this->host)?>+jsonrpc.sizes["250x136"]});
		//$("[name=thumb]").val($.toJSON(jsonrpc.sizes));
	});
}

$(document).on("ajaxComplete ready",function(){
	$('div.col[width]').each(function(){
		var div = parseInt(this.getAttribute("width"));
		$(this).width(($(this).parent().width()-20*$(this).siblings().length)/div);
		this.removeAttribute("width");
	}).parent().each(function(){
		$(this).find("div.col:first").css({marginLeft:0});
	});
	$("[maxlength]").filter(function(){return !$(this).next().is("span.counter")}).each(function(){
		var i = $(this).attr({type:"text"}),
			l = i.attr("maxlength");
		i.after($($.parseHTML("<span/>")).addClass("counter").css({display: "inline-block","float": "right",position: "relative",top: -27,paddingRight: 10,fontSize: "16px",color: "#999",opacity: 0.8,marginBottom:-31}).on("click",function(e){$(this).prev().focus();}).html(l));
		i.on("keydown keyup paste",function(e){
			var element = this;
			var t = $(element).val().length, m = parseInt($(element).prop("maxlength"));
			if (t>=m && e.which!=8 && e.which!=46 && !(e.which == 65 && (e.metaKey == true || e.ctrKey == true)) && e.which != 37 && e.which != 38 && e.which != 39 && e.which != 40) e.preventDefault();
			setTimeout(function(){
				$(element).next().html(m-$(element).val().length).css({color:((m-$(element).val().length) > m*0.05 ? "#999" : "red")});
				$(element).css({paddingRight:$(element).next().width()+20});
			},1);
		}).trigger("keydown");
	});
	$("input[data-before][data-after]").filter(function(){
		return !$(this).parent().is(".finput");
	}).each(function(){
		$(this).prop({type:"hidden"}).wrap("<div></div>");
		var p = $(this).parent().addClass("finput form-control").css({cursor:"text",wordBreak:"break-word"});
		var i = $(this).data("prevBefore",$(this).data("before")).data("prevAfter",$(this).data("after"));
		var b = $($.parseHTML("<span/>")).addClass("noteditable").addClass("before").text(i.data("before"));
		var a = $($.parseHTML("<span/>")).addClass("noteditable").addClass("after").text(i.data("after"));
		var c = $($.parseHTML("<span/>")).prop({contenteditable:true,id:i.prop("id")}).text(i.val());
		i.removeProp("id").removeAttr("id").everyTime(10,function(){
			var i = $(this);
			if (i.data("before") != i.data("prevBefore")) {
				i.data("prevBefore",i.data("before"));
				i.parent().find("span.before").text(i.data("before"));
			}
			if (i.data("after") != i.data("prevAfter")) {
				i.data("prevAfter",i.data("after"));
				i.parent().find("span.after").text(i.data("after"));
			}
		});
		p.append(b).append(c).append(a);
		var g = [];
		if (c.prop("id") && $("label[for=#"+c.prop("id")+"]")) {
			g = $("label[for="+c.prop("id")+"]");
		}
		p.add(g).on("click",function(e){
			p.find("[contenteditable=true]").get(0).focus();
		}).on("dblclick",function(e){
			e.preventDefault();
			e.stopPropagation();
			$(this).oneTime(1,function(){
				var sel, range;
				if (window.getSelection && document.createRange) {
					range = document.createRange();
					range.selectNodeContents($(this).find("[contenteditable=true]").get(0));
					sel = window.getSelection();
					sel.removeAllRanges();
					sel.addRange(range);
				} else if (document.body.createTextRange) {
					range = document.body.createTextRange();
					range.moveToElementText($(this).find("[contenteditable=true]").get(0));
					range.select();
				}
			});
		});
		c.on("blur paste keydown keyup paste",function(e){
			if (e.type == "keydown" && e.which == 13) e.preventDefault();
			var element = this;
			$(this).oneTime(1,function(){
				$(this).parent().find("input").val($(element).text());
			});
			if (e.type == "blur") {
				if ($(this).data("pval") !== $(this).text()) {
					$(this).data("pval",$(this).text());
					$(this).text(replaceDiacritics($(this).text()).replace(/[^a-zA-Z0-9-]+/gi,"-").replace(/[-]+/gi,'-').trim("-"));
					$(element).siblings("input").trigger("change").data("changed",true);
				}
			}
		});
	});
	$(document).on("click",function(e){
		if (!$(e.target).parents(".multiselect-menu").length) {
			$(".multiselect-menu").remove();
			$(".multiselect-box a.opened").removeClass("opened");
		}
		$(".combo-menu").remove();
		$(".combobox").removeClass("open");
	});
	$(".combobox").filter(function(){return $(this).find("a.open").length == 0;}).each(function(){
		var cb = $(this),
			ib = cb.find("input").addClass("combo form-control"),
			sb = cb.find("ul"),
			d = new Date();
		if (sb.find("li.selected").length) {
			ib.val(sb.find("li.selected").attr("data-value"));
		}
		sb.find("li").each(function(){
			if ($(this).children("a").length) return true;
			else $(this).html("<a>"+$(this).html()+"<\/a>");
		});
		sb.hide(); ib.after($($.parseHTML('<a href="javascript:" class="open actionbutton"/>')));
		cb.prop({id:'cb-'+d.getTime()});
		cb.find("a.open").on("click",function(e){
			e.stopPropagation();
			var el = $(this).parent();
			$(".combo-menu").remove();
			$(".multiselect-menu").remove();
			$(".multiselect-box a.opened").removeClass("opened");
			if (!el.is(".open")) {
				el.addClass("open");
				var cbm = el.find("ul").clone().show().addClass("combo-menu")
				.data("cb",el.prop("id"))
				.css({top:el.offset().top+el.height()-17,left:el.offset().left,width:el.width()});
				$("body").append(cbm);
				cbm.find("a").prop({href:"javascript:"}).on("click",function(e){
					e.preventDefault();
					$('#'+$(this).parents("ul.combo-menu").data("cb")).find("input[type=text]").val($(this).parent().attr("data-value"))
						.trigger("change");
					$('#'+$(this).parents("ul.combo-menu").data("cb")).find("a.open").click();
				});
				cbm.find("li[data-deep]").each(function(){
					$(this).find("a").css({paddingLeft:12+(parseInt($(this).attr("data-deep"))-1)*15});
				});
				var x = cbm.find("li").filter(function(){
					if ($(this).attr("data-value") == $('#'+$(this).parents("ul.combo-menu").data("cb")).find("input[type=text]").val())
						return true;
					else return false;
				}).addClass("active");
				x.siblings().find("a i").remove();
				x.find("a").prepend($.parseHTML("<i class=\"icon-check\"><\/i>"));
			} else {
				el.removeClass("open");
			}
		});
		ib.width(ib.width()-21);
	});
	$("select[multiple]").filter(function(){
		return !$(this).parent().is(".multiselect-box");
	}).each(function(){
		var id = $(this).attr("id"); $(this).removeAttr("id");
		var openbtn = $($.parseHTML('<a href="javascript:" class="open actionbutton"></a>')).attr({id:id});
		if (id && $("label").filter(function(){return $(this).attr("for") == id}).length) {
			$("label").filter(function(){return $(this).attr("for") == id}).on("click",function(){
				var el = this;
				$(this).oneTime(1,function(){$("*").filter(function(){
					return $(this).attr("id") == $(el).attr("for");
				}).trigger("click");});
			});
		}
		$(this).hide().wrap($.parseHTML('<div class="multiselect-box"><\/div>'));
		$(this).after(openbtn.html(I81n.t("{{None selected}}")));
		openbtn.on("click",function(e){
			e.preventDefault();
			$(".combo-menu").remove();
			$(".combobox").removeClass("open");
			$(this).toggleClass("opened");
			var el = $(this).parent();
			var se = el.children("select[multiple]");
			$('ul.multiselect-menu').remove();
			if ($(this).is(".opened")) {
				var menu = $($.parseHTML("<ul class=\"multiselect-menu\"><\/ul>"));
				se.children("optgroup,option").each(function(){
					if ($(this).is("optgroup")) {
						menu.append(
							$($.parseHTML("<li\/>")).append(
								$($.parseHTML("<label\/>")).css({padding:"0 3px"}).html(this.getAttribute("label"))
							)
						);
						$(this).children("option").each(function(){
							menu.append(
								$($.parseHTML("<li\/>")).append(
									$($.parseHTML("<a\/>")).css({marginLeft:"16px"}).append(
										$($.parseHTML("<i\/>")).addClass($(this).is(":selected") ? 'icon-check-checked' : 'icon-check-empty').addClass("icon")
									).append(
										$($.parseHTML("<span\/>")).html($(this).html())
									).prop({href:"javascript:"}).data("idx",$(this).parents("select").find("option").index($(this)))
								)
							);
						});
					}
					else {
						menu.append(
							$($.parseHTML("<li\/>")).append(
								$($.parseHTML("<a\/>")).append(
									$($.parseHTML("<i\/>")).addClass($(this).is(":selected") ? 'icon-check-checked' : 'icon-check-empty').addClass("icon")
								).append(
									$($.parseHTML("<span\/>")).html($(this).html())
								).prop({href:"javascript:"}).data("idx",$(this).parents("select").find("option").index($(this)))
							)
						);
					}
				});
				menu.data("select",$(this).siblings("select")).find("li:has(i.icon-check-checked)").addClass("active");
				menu.find("li > a").on("click",function(e){
					e.preventDefault();
					var idx = $(this).data("idx");
					var el = menu.data("select");
					if ($(this).parent().is(".active")) {
						$(this).parent().removeClass("active");
						$(this).children("i").removeClass("icon-check-checked").addClass("icon-check-empty");
						el.find("option").eq(idx).prop({selected: false});
					}
					else {
						$(this).parent().addClass("active");
						$(this).children("i").addClass("icon-check-checked").removeClass("icon-check-empty");
						el.find("option").eq(idx).prop({selected: true});
					}
					el.trigger("msb.change");
					return false;
				});
				menu.appendTo("body").css({top:el.offset().top+el.height()-17,left:el.offset().left,width:el.width()});
				$(document).on("scroll",function(){
					$(menu).css({top:el.offset().top+el.height()-17,left:el.offset().left});
				});
			}
			return false;
		});
		$(this).on("msb.change change",function(e){
			if ($(this).find("option:selected").length == 1) {
				$(this).siblings("a.open").html($(this).find("option:selected:eq(0)").html());
			} else if ($(this).find("option:selected").length == 0) {
				$(this).siblings("a.open").html(I81n.t("{{None selected}}"))
			} else {
				$(this).siblings("a.open").html(I81n.t("{{# items selected}}").replace("#",$(this).find("option:selected").length))
			}
		})
		$(this).trigger("msb.change");
	});
});
$(document).on("dialogopen","*",function(e,u){
	if ($(this).is(".ui-dialog-content")) {
		$(this).find("input:first").focus();
		$(this).dialog("fixbuttons");
		var maxZ = Math.max.apply(null,
			$.map($('body *:visible'), function(e,n) {
				if ($(e).css('position') != 'static')
					return parseInt($(e).css('z-index')) || 1;
			}));
		$(this).parent().css({zIndex:maxZ+1}).prev(".ui-widget-overlay").css({zIndex:maxZ+1});
	}
});

$.ui.dialog.prototype.addbutton = function(button) {
	var buttons = this.element.dialog("option","buttons");
	buttons.push(button);
	this.element.dialog("option","buttons",buttons);
	this.element.dialog("fixbuttons");
}
$.ui.dialog.prototype.fixbuttons = function() {
	var buttons = this.element.dialog("option","buttons");
	for (i in buttons) {
		if (isNaN(parseInt(i))) continue;
		var classes = buttons[i]["class"] ? buttons[i]["class"].split(" ") : [];
		if ($.inArray("btn", classes) === -1) classes.push("btn btn-default");
		buttons[i]["class"]=classes.join(" ");
	}
	this.element.dialog("option","buttons",buttons)
}
//var defAlert = alert;
var cmsAlert = function(t,c) {
	$($.parseHTML("<div>"+t+"</div>")).dialog({
		resizable: false,
		draggable: false,
		callback: c,
		buttons: [
			{
				text: I81n.t("{{Ok}}"),
				"class": "btn-success",
				click: function(){$(this).dialog("close");}
			}
		],
		close: function(){typeof c == "function"&&c(); $(this).parent().remove();},
		open: function() {
			$(this).parent().find(".ui-dialog-buttonset button.green").focus();
		},
		modal: true
	});
}
//window.alert = cmsAlert;
//var defConfirm = confirm;
var cmsConfirm = function(t, c) {
	if (typeof c != "function") c = function(){};
	$($.parseHTML("<div>"+t+"</div>")).dialog({
		callback: c,
		state: false,
		resizable: false,
		draggable: false,
		buttons: [
			{
				text: I81n.t("{{Cancel}}"),
				click: function(){
					$(this).dialog("close");
				}
			},{
				text: I81n.t("{{Yes}}"),
				"class": "btn-success",
				click: function(){
					$(this).dialog("option","state",true);
					$(this).dialog("close");
				}
			}
		],
		close: function(){
			$(this).dialog("option","callback")($(this).dialog("option","state"));
			$(this).parent().remove();
		},
		open: function() {
			$(this).parent().find(".ui-dialog-buttonset button.green").focus();
		},
		modal: true
	});
}
//window.confirm = cmsConfirm;
//var defPrompt = prompt;
var cmsPrompt = function(t, d, c) {
	if (typeof d == "function") c = d;
	if (typeof d != "string") d = "";
	if (typeof c != "function") c = function(){};
	$($.parseHTML("<div><input type=\"text\" value=\""+d+"\" name=\"prompt\" tabindex=\"0\"/></div>")).dialog({
		title: t,
		callback: c,
		answer: null,
		resizable: false,
		draggable: false,
		buttons: [
			{
				text: I81n.t("{{Cancel}}"),
				click: function(){
					$(this).dialog("close");
				},
				tabindex: 2
			},{
				text: I81n.t("{{Ok}}"),
				"class": "btn-success",
				click: function(){
					$(this).dialog("option","answer",$(this).find("[name=prompt]").val());
					$(this).dialog("close");
				},
				tabindex: 1
			}
		],
		close: function(){
			$(this).dialog("option","callback")($(this).dialog("option","answer"));
			$(this).parent().remove();
		},
		open: function(){
			$(this).find("input[name=prompt]").on("keyup",function(e){
				if (e.which == 13) {
					$(this).parents(".ui-dialog-content:first").dialog("option","answer",$(this).val());
					$(this).parents(".ui-dialog-content:first").dialog("close");
				}
			});
		},
		modal: true
	});
}
//window.prompt = cmsPrompt;

$(document).on("focus","[contenteditable]",function(){
	setEndOfContenteditable(this);
});

$.extend($.ui.dialog.prototype.options, {
    modal: true,
    resizable: false,
    draggable: false,
    close: function(){$(this).dialog("destroy").remove();},
    minHeight: 30
});

function setEndOfContenteditable(contentEditableElement)
{
    var range,selection;
    if(document.createRange)//Firefox, Chrome, Opera, Safari, IE 9+
    {
        range = document.createRange();//Create a range (a range is a like the selection but invisible)
        range.selectNodeContents(contentEditableElement);//Select the entire contents of the element with the range
        range.collapse(false);//collapse the range to the end point. false means collapse to end rather than the start
        selection = window.getSelection();//get the selection object (allows you to change selection)
        selection.removeAllRanges();//remove any selections already made
        selection.addRange(range);//make the range you have just created the visible selection
    }
    else if(document.selection)//IE 8 and lower
    { 
        range = document.body.createTextRange();//Create a range (a range is a like the selection but invisible)
        range.moveToElementText(contentEditableElement);//Select the entire contents of the element with the range
        range.collapse(false);//collapse the range to the end point. false means collapse to end rather than the start
        range.select();//Select the range (make it the visible selection
    }
}
$.fn.serializeObject = function()
{
    var o = {};
    var a = this.serializeArray();
    $.each(a, function() {
        if (o[this.name] !== undefined) {
            if (!o[this.name].push) {
                o[this.name] = [o[this.name]];
            }
            o[this.name].push(this.value || '');
        } else {
            o[this.name] = this.value || '';
        }
    });
    return o;
};
/* Placeholders.js v3.0.2 */
(function(t){"use strict";function e(t,e,r){return t.addEventListener?t.addEventListener(e,r,!1):t.attachEvent?t.attachEvent("on"+e,r):void 0}function r(t,e){var r,n;for(r=0,n=t.length;n>r;r++)if(t[r]===e)return!0;return!1}function n(t,e){var r;t.createTextRange?(r=t.createTextRange(),r.move("character",e),r.select()):t.selectionStart&&(t.focus(),t.setSelectionRange(e,e))}function a(t,e){try{return t.type=e,!0}catch(r){return!1}}t.Placeholders={Utils:{addEventListener:e,inArray:r,moveCaret:n,changeType:a}}})(this),function(t){"use strict";function e(){}function r(){try{return document.activeElement}catch(t){}}function n(t,e){var r,n,a=!!e&&t.value!==e,u=t.value===t.getAttribute(V);return(a||u)&&"true"===t.getAttribute(D)?(t.removeAttribute(D),t.value=t.value.replace(t.getAttribute(V),""),t.className=t.className.replace(R,""),n=t.getAttribute(F),parseInt(n,10)>=0&&(t.setAttribute("maxLength",n),t.removeAttribute(F)),r=t.getAttribute(P),r&&(t.type=r),!0):!1}function a(t){var e,r,n=t.getAttribute(V);return""===t.value&&n?(t.setAttribute(D,"true"),t.value=n,t.className+=" "+I,r=t.getAttribute(F),r||(t.setAttribute(F,t.maxLength),t.removeAttribute("maxLength")),e=t.getAttribute(P),e?t.type="text":"password"===t.type&&M.changeType(t,"text")&&t.setAttribute(P,"password"),!0):!1}function u(t,e){var r,n,a,u,i,l,o;if(t&&t.getAttribute(V))e(t);else for(a=t?t.getElementsByTagName("input"):b,u=t?t.getElementsByTagName("textarea"):f,r=a?a.length:0,n=u?u.length:0,o=0,l=r+n;l>o;o++)i=r>o?a[o]:u[o-r],e(i)}function i(t){u(t,n)}function l(t){u(t,a)}function o(t){return function(){m&&t.value===t.getAttribute(V)&&"true"===t.getAttribute(D)?M.moveCaret(t,0):n(t)}}function c(t){return function(){a(t)}}function s(t){return function(e){return A=t.value,"true"===t.getAttribute(D)&&A===t.getAttribute(V)&&M.inArray(C,e.keyCode)?(e.preventDefault&&e.preventDefault(),!1):void 0}}function d(t){return function(){n(t,A),""===t.value&&(t.blur(),M.moveCaret(t,0))}}function g(t){return function(){t===r()&&t.value===t.getAttribute(V)&&"true"===t.getAttribute(D)&&M.moveCaret(t,0)}}function v(t){return function(){i(t)}}function p(t){t.form&&(T=t.form,"string"==typeof T&&(T=document.getElementById(T)),T.getAttribute(U)||(M.addEventListener(T,"submit",v(T)),T.setAttribute(U,"true"))),M.addEventListener(t,"focus",o(t)),M.addEventListener(t,"blur",c(t)),m&&(M.addEventListener(t,"keydown",s(t)),M.addEventListener(t,"keyup",d(t)),M.addEventListener(t,"click",g(t))),t.setAttribute(j,"true"),t.setAttribute(V,x),(m||t!==r())&&a(t)}var b,f,m,h,A,y,E,x,L,T,N,S,w,B=["text","search","url","tel","email","password","number","textarea"],C=[27,33,34,35,36,37,38,39,40,8,46],k="#ccc",I="placeholdersjs",R=RegExp("(?:^|\\s)"+I+"(?!\\S)"),V="data-placeholder-value",D="data-placeholder-active",P="data-placeholder-type",U="data-placeholder-submit",j="data-placeholder-bound",q="data-placeholder-focus",z="data-placeholder-live",F="data-placeholder-maxlength",G=document.createElement("input"),H=document.getElementsByTagName("head")[0],J=document.documentElement,K=t.Placeholders,M=K.Utils;if(K.nativeSupport=void 0!==G.placeholder,!K.nativeSupport){for(b=document.getElementsByTagName("input"),f=document.getElementsByTagName("textarea"),m="false"===J.getAttribute(q),h="false"!==J.getAttribute(z),y=document.createElement("style"),y.type="text/css",E=document.createTextNode("."+I+" { color:"+k+"; }"),y.styleSheet?y.styleSheet.cssText=E.nodeValue:y.appendChild(E),H.insertBefore(y,H.firstChild),w=0,S=b.length+f.length;S>w;w++)N=b.length>w?b[w]:f[w-b.length],x=N.attributes.placeholder,x&&(x=x.nodeValue,x&&M.inArray(B,N.type)&&p(N));L=setInterval(function(){for(w=0,S=b.length+f.length;S>w;w++)N=b.length>w?b[w]:f[w-b.length],x=N.attributes.placeholder,x?(x=x.nodeValue,x&&M.inArray(B,N.type)&&(N.getAttribute(j)||p(N),(x!==N.getAttribute(V)||"password"===N.type&&!N.getAttribute(P))&&("password"===N.type&&!N.getAttribute(P)&&M.changeType(N,"text")&&N.setAttribute(P,"password"),N.value===N.getAttribute(V)&&(N.value=x),N.setAttribute(V,x)))):N.getAttribute(D)&&(n(N),N.removeAttribute(V));h||clearInterval(L)},100)}M.addEventListener(t,"beforeunload",function(){K.disable()}),K.disable=K.nativeSupport?e:i,K.enable=K.nativeSupport?e:l}(this);