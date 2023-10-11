jQuery(document).ready(function($) {
	var setTabSel = (a) => {
		$('nav a').removeClass("sel").css("font-weight","normal");
		a.addClass("sel").css("font-weight","bold");
		$(".tabs>div").hide();
		$(".tabs>div").each(function(){
			if("#" + $(this).attr("id") == a.attr("href")) {
				$(this).show();
			}
		});
	}
	
	$('nav a').on("click",function(e){
		if($(this).attr("href").indexOf("#")==0) {
			e.preventDefault();
			setTabSel($(this));
		}	
	});
	setTabSel($("nav a.sel"));

    $('input[type=range]').on("change",function(){
        let divider = typeof($(this).data("divider"))=="undefined" ? 1 : parseInt($(this).data("divider"));
        $(this).next("span").text( parseInt($(this).val())/divider  );
    })

    $('input[type=color]').on("change",function(){
        $(this).next("span").text( $(this).val() );
    })
	
	
	//
	// SETTINGS PAGE JS
	//

	// CUSTOM SIZE
	let blpwp_size = function(s){$('.mblpwp').css("font-size", $(s).val() + "px");}
	blpwp_size('#blpwp_plugin_setting_size');
	$('#blpwp_plugin_setting_size').on("change",function(){
		blpwp_size(this);
	});

	// CUSTOM COLOR SPOT
	let blpwp_color = function(s){$('.mblpwp .blpwp_spot b').css("background-color", $(s).val());}
	blpwp_color('#blpwp_plugin_setting_color');
	$('#blpwp_plugin_setting_color').on("change",function(){
		blpwp_color(this);
	});
	let blpwp_fgcolor = function(s){$('.mblpwp .blpwp_spot b i').css("color", $(s).val());}
	blpwp_fgcolor('#blpwp_plugin_setting_fgcolor');
	$('#blpwp_plugin_setting_fgcolor').on("change",function(){
		blpwp_fgcolor(this);
	});

	// CUSTOM COLOR
	let blpwp_color0 = function(s){$('.mblpwp .blpwp_wrap').css("background-color", $(s).val());}
	blpwp_color0('#blpwp_plugin_setting_color0');
	$('#blpwp_plugin_setting_color0').on("change",function(){
		blpwp_color0(this);
	});
	let blpwp_fgcolor0 = function(s){$('.mblpwp .blpwp_wrap').css("color", $(s).val());}
	blpwp_fgcolor0('#blpwp_plugin_setting_fgcolor0');
	$('#blpwp_plugin_setting_fgcolor0').on("change",function(){
		blpwp_fgcolor0(this);
	});

	// CUSTOM SELECTOR CHECK
	let blpwp_check = function(){
		if($('#selectorchk').is(':checked')) {
			$('#blpwp_plugin_setting_selector').removeAttr('readonly');
		} else {
			$('#blpwp_plugin_setting_selector').attr('readonly','readonly');
		}
	};
	blpwp_check();
	$('#selectorchk').on('change',function(){
		blpwp_check();
	});
	

	// STYLE CHANGE
	$('.mblpwp').css("border-color", jQuery(".wp-core-ui .button-primary").css("background-color"));
	$('.mblpwp').on("click",function(e){
		$('#blpwp_plugin_setting_style').val( $(this).data("class"));
		$('.mblpwp').removeClass("selected");
		$(this).addClass("selected");


	});

	//Trigger sort when "Trigger Sort" button is clicked
	$("#nofollowShow").on("click", function(e){
		e.preventDefault();
		$("#allShow,#sponsoredShow,#brokenShow").removeClass("sel");	
		$(this).addClass("sel");
		table.setFilter(function(data){return data.de_rel.indexOf("nofollow")>-1},"error");
	});
	$("#sponsoredShow").on("click", function(e){
		e.preventDefault();
		$("#allShow,#brokenShow,#nofollowShow").removeClass("sel");	
		$(this).addClass("sel");
		table.setFilter(function(data){return data.de_rel.indexOf("sponsor")>-1},"error");
	});
	$("#brokenShow").on("click", function(e){
		e.preventDefault();
		$("#allShow,#sponsoredShow,#nofollowShow").removeClass("sel");	
		$(this).addClass("sel");
		table.setFilter(function(data){return data.error == false},"error");
	});        
	$("#allShow").on("click", function(e){
		e.preventDefault();
		$("#allShow").removeClass("sel");	
		$(this).addClass("sel");
		table.setFilter(function(data){return true},"error");
	});
} );