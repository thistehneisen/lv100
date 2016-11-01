// Tabs

$(function(){
	$(".jstabs").each(function(){
		createTabs($(this));
	});
	
	lswitcher();
});

function createTabs(tabs) {
	tabs.children(":not(.tabs)").hide();
	tabs.find(".tabs > div > a").each(function(){
		$(this).on("click",function(e){
			e.preventDefault();
			tabs.children(":not(.tabs)").hide();
			tabs.find(this.getAttribute("href")).show();
		});
	});
	if (tabs.find(".tabs > div > a.selected").length) {
		tabs.find(tabs.find(".tabs > div > a.selected").attr("href")).show();
	} else {
		tabs.children(":not(.tabs):first").show();
		tabs.find(".tabs > div > a:first").addClass("selected");
	}
}

function lswitcher() {
	$('.languageswitcher:not(.switch-enabled)').each(function(){
		$(this).addClass('switch-enabled');
		$(this).find('div > a').each(function(i){
			$(this).on('click',function(e){
				e.preventDefault();
				$(this).parents('.languageswitcher').siblings('[lang]').hide();
				$(this).parents('.languageswitcher').siblings('[lang='+$(this).get(0).getAttribute('lang')+']').show();
			});
		}).eq(0).click();
	});
}