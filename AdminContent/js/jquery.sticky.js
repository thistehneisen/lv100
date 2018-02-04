(function($){
	$.sticky = function(args){
		var module_url = Settings.adminHost+'stickies/edit/';
		
		function call(params,callback) {
			$.extend(true,params,{id:args.id});
			$.post(module_url,params,callback,"json");
		}
		
		function saveSettings () {
			call({
				inany: args.note.children("section").find("input[name=inany]").is(":checked") ? 1 : 0,
				ifany: args.note.children("section").find("input[name=ifany]").is(":checked") ? 1 : 0,
				uri: Settings.fullRequestUri
			},function(){
				args.note.children("section").find("input[type=checkbox]").each(function(){
					if ($(this).is(":checked")) $(this).attr({checked:"checked"});
					else $(this).removeAttr("checked");
				});
				args.note.children("section").children().appendTo(args.note.children("div.settings-form").empty());
				args.note.children("section").revertFlip();
				args.note.find("header > a.settings").removeClass("open");
			});
		}
		
		function saveContent() {
			var ct = args.note.find("section > div[contenteditable=true]");
			if (args.content != ct.html()) {
				call({
					content: ct.html()
				});
				args.content = ct.html();
			}
		}
		
		function create() {
			args.note = $($.parseHTML("<div/>")).css({position: "absolute"});
			args.note.addClass("sticky");
			args.note.append($($.parseHTML("<header/>")).css({position:"relative"}));
			if (args.user) args.note.children("header").append(
				$($.parseHTML("<div/>")).addClass("name").html(args.user)
			);
			if (args.owner) {
				args.note.children("header").append(
					$($.parseHTML("<a/>")).prop({
						href: "javascript:"
					}).addClass("settings").append(
						$($.parseHTML("<i/>")).addClass("icon-cog-alt")
					).on("click",function(e){
						e.preventDefault();
						if ($(this).is(".disabled")) return false;
						if ($(this).is(".open")) {
							args.note.children("section").children().appendTo(args.note.children("div.settings-form").empty());
							args.note.children("section").revertFlip();
							$(this).removeClass("open");
						} else {
							args.note.children("section").flip({
								direction: "lr",
								color: 'rgba(0,0,0,0)',
								content: $("div.settings-form",args.note),
								onBefore: function(){
									args.note.children("header").find("a.settings").addClass("disabled");
								},
								onEnd: function(){
									args.note.children("header").find("a.settings").removeClass("disabled");
								}
							});							
							$(this).addClass("open");
						}
					})
				);
				args.note.children("header").append(
					$($.parseHTML("<a/>")).prop({
						href: "javascript:"
					}).addClass("delete").append(
						$($.parseHTML("<i/>")).addClass("icon-cancel")
					).on("click",function(e){
						e.preventDefault();
						confirm(I81n.t("{{Are you sure want to delete sticky note?}}"),function(a){
							if (a) {
								call({"delete": 1},function(){
									args.note.fadeOut(function(){
										$(this).remove();
									});
								})
							}
						});
					})
				);
			} else {
				args.note.children("header").append(
					$($.parseHTML("<a/>")).prop({
						href: "javascript:"
					}).addClass("eye").append(
						$($.parseHTML("<i/>")).addClass("icon-eye-off")
					).on("click",function(e){
						e.preventDefault();
						confirm(I81n.t("{{Are you sure want to hide sticky note?}}"),function(a){
							if (a) {
								call({"eye-off": 1},function(){
									args.note.fadeOut(function(){
										$(this).remove();
									});
								})
							}
						});
					})

				);
			}
			args.note.append($($.parseHTML("<section/>")));
			args.note.children("section").append(
				$($.parseHTML("<div/>")).prop({
					contenteditable: args.owner ? true : false
				}).html(args.content)
			);
			args.note.append($($.parseHTML("<div/>")).addClass("settings-form").append(
				$($.parseHTML("<form/>")).on("submit",function(e){
					e.preventDefault();
					
				}).prop({
					action: "javascript:",
					method: "post"
				}).append(
					$($.parseHTML("<label/>")).append(
						$($.parseHTML("<input/>")).prop({
							type: "checkbox",
							name: "inany",
							value: 1,
							checked: (args.inany ? true : false)
						})
					).append(I81n.t("{{Sticky: show on any page}}"))
				).append("<br/>").append(
					$($.parseHTML("<label/>")).append(
						$($.parseHTML("<input/>")).prop({
							type: "checkbox",
							name: "ifany",
							value: 1,
							checked: (args.ifany ? true : false)
						})
					).append(I81n.t("{{Sticky: show to any user}}"))
				).append("<br/>").append(
					$($.parseHTML("<input/>")).prop({
						type: "button",
						value: I81n.t("{{Save}}")
					})
				)
			));
			args.note.on("click","section > form > input[type=button]",function(e){
				e.preventDefault();
				saveSettings ();
			});
			args.note.everyTime(5000,function(){
				if ($(this).find("section > div[contenteditable=true]").is(":focus"))
					saveContent ();
			}).on("blur","section > div[contenteditable=true]",function(){
				saveContent ();
			});
			args.note.css({
				width: args.dimensions.width,
				height: args.dimensions.height,
				top: args.position.top,
				left: args.position.left
			});
			if (args.owner) {
				args.note.resizable({
					stop: function() {
						call({
							dimensions: {
								width: args.note.width(),
								height: args.note.height()
							}
						});
					}
				});
			}
			args.note.draggable({
				cancel: "[contenteditable=true],a,input",
				containment: "parent",
				scroll: false,
				stop: function() {
					call({
						position: {
							top: args.note.offset().top,
							left: args.note.offset().left
						}
					});
				}
			});
			args.note.appendTo("body");
			if (args.ifany) args.note.children("div").find("input[name=ifany]").attr("checked","checked");
			if (args.inany) args.note.children("div").find("input[name=inany]").attr("checked","checked");
			if (args.focus) {
				args.note.find("section > div[contenteditable=true]").get(0).focus();
			}
		}
				
		create&&create();
	};
})(jQuery);