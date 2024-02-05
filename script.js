jQuery(document).ready(function($) {

	var currentA = null;

	var mobile = () => {return $(window).width() > 600 ? false : true};

	function _f(s) {
		return blpwp_params['labels'][s]
	}

	jQuery("body").append( "<style>" +
			".mblpwp .blpwp_spot b {background:" + blpwp_params["options"]["color"] +"}" +
			".mblpwp .blpwp_spot b i {color:" + blpwp_params["options"]["fgcolor"] +"}" + 
						".mblpwp .blpwp_wrap {background:" + blpwp_params["options"]["color0"] +"}" +
						".mblpwp .blpwp_wrap {color:" + blpwp_params["options"]["fgcolor0"] +"}</style>");

				function checkPic() {
					span = $("#blpwp .blpwp_pics");
					if (typeof(span.data("images"))!== "undefined" && span.data("images")!="") {
						arPics = span.data("images").split("|");
						var img = new Image();
						img.onload = function(){
							  if(img.width * img.height > 2500) {
									span.replaceWith("<img src=\"" + arPics[0] + "\" onerror=\"jQuery(this).hide();\">");
									if (img.width > img.height) {
										$("#blpwp").addClass("horizontal").removeClass("vertical");
									} else {
										$("#blpwp").addClass("vertical").removeClass("horizontal");
									}
							  } else {
								let images = span.data("images").replace(arPics[0],"");
								images = images.replace(/^\|/, '');
								span.data("images", images);
								checkPic();	// recursive call
							  }
						}
						img.src = arPics[0];
					} else {
						$("#blpwp>div").addClass("nopic");
					}
				}

				var mTimer = false;
				var triggerClick = (a) => {

					$.ajax({
						url: blpwp_params['url'],
						type :'POST',
						data: {
							"url": convertToAbsoluteURL( a.attr("href") ),
							"idpost": blpwp_params['idpost'],
							"action" : 'blpwpclick',
							"rel" : a.attr("rel")
						},
						success: function(html){
							if( a.data("preventClick") == 0) {
								// normal links
								if(blpwp_params['options'].blank == 'on') {
									window.open( a.attr("href") );
								} else {
									document.location = a.attr("href");
								}
							} else {
								closePopup();
												
							}
						},
					});


				}

				function addOverlayer(){
					$("body").data("css-overflow", $("body").css("overflow"));
					$('body').append("<div id='underblpwp' class='" + blpwp_params['options'].style + "'></div>");
					$('body,html').css("overflow","hidden");
					// $('#blpwp').removeClass("fading");
					// console.log("rimosso")
					// console.log($('#blpwp').attr("class"));
					mTimer = setInterval(function(){
						// console.log("mTimer (opacity=" + $('#blpwp').css("opacity") + " " + ($('#blpwp').hasClass("fading")? "ha fading":"non ha fading")+")");
						if($('#blpwp').css("opacity") <= 0.05 && $('#blpwp').hasClass("fading")) {
							// console.log("remove");
							$('#underblpwp').remove();$('#blpwp').remove();
							clearInterval(mTimer);
						}
						// click on dark layer close pop up
						$('#underblpwp').on("click",function(e){
							e.preventDefault();
							e.stopPropagation();
							if($('#blpwp').css("opacity") >=.95  && !$('#blpwp').hasClass("fading")) {
								// console.log("add fading");
								$('#blpwp').addClass("fading");
								$('body,html').css("overflow",$("body").data("css-overflow"));
							}
						});
					},500);
				};

				function closePopup(){
					$('#blpwp').addClass("fading");
					if(mobile()) $('body,html').css("overflow",$("body").data("css-overflow"));
				}

				function showThisPop(a) {
					// console.log("showThisPop");
					// console.log($('#blpwp').attr("class"));
					$('#blpwp').html(a.data("html")).attr("class","mblpwp loaded " +  blpwp_params['options'].style + " " + (mobile() ? "mob" :"") );
					checkPic();
					if(mobile()){
						$('#underblpwp').addClass("loaded");
						$('#blpwp').on("click",function(e){
								e.preventDefault();
								e.stopPropagation();
							 // console.log("#blpwp.click");
							if(!$('#blpwp').hasClass("fading")) {
								// console.log("not fading... open");
								triggerClick(a)
							}
						});
					}
				}
			    
			    function convertToAbsoluteURL(relativeURL) {
			        // Check if a <base> tag already exists
			        const existingBase = document.querySelector('base');
			        let base;
			        if (existingBase) {
			            base = existingBase;
			        } else {
			            base = document.createElement('base');
			            document.head.appendChild(base);
			        }
			        base.href = window.location.href;
			        const absoluteURL = new URL(relativeURL, base.href);
			        return absoluteURL.href;
			    }
			    
				function setupPopUp(a, e) {
					currentA = convertToAbsoluteURL( e.target.getAttribute("href") );

					let h = convertToAbsoluteURL( a.attr("href") );

					if(a.data("html")==""){
						// search same link already retrieved
						let s = 'a[href^="' + h+'"]';
						$(s).each(function(){
							if($(this).data("html")!="" && typeof($(this).data("html"))!=="undefined") { 
								// console.log("already used");
								a.data("html", $(this).data("html")); a.data("status",2); return;
							}
						});
					}

					// link has status 0 and no html
					if(a.data("html")=="" && a.data("status")==0){
						// add div container for popup and fetch content
						$('#blpwp').html(_f("loading-preview")).attr("class","mblpwp loading a " +  blpwp_params['options'].style + " " + (mobile() ? "mob" :""));
						a.data("status",1);
						$.ajax({
							url: blpwp_params['url'],
							type :'POST',
							data: {
								"geturl" : h,
								"idpost" : blpwp_params['idpost'],
								"action" : 'blpwpgetinfo',
								"rel" : a.attr("rel")
							},
							success: function(html){
								a.data("html",html);
								a.data("status",2);	// status = 2 ==> OK
								// console.log(currentA + " == " +h + "(1)")

								if(currentA == h || !currentA) showThisPop(a);
							},
						});
					  
					} else {
						// there is html or status != 0


						if(a.data("status")==1) { // link is loading
							$('#blpwp').html(_f("loading-preview")).attr("class","mblpwp loading c " +  blpwp_params['options'].style + " " +  (mobile() ? "mob" :""));
						} else {

							// status is 2 ==> OK
							if(a.data("status") == 2 ) {
								// console.log(currentA + " == " +h + "(2)")
								if(currentA == h || !currentA) showThisPop(a);
							} else {
								// other
								$('#blpwp').remove();
							}
						}
					}

				}

				function prepareLayers(){
					
					// black layer on mobile
					if($('#underblpwp').length == 0 && mobile()) {
						addOverlayer();
					}

					if($('#blpwp').length == 0) {
						// add div container for popup if not exists
						$("body").append("<div id='blpwp' class='mblpwp loading b " +  blpwp_params['options'].style + " " + (mobile() ? "mob" :"") +"' style='font-size:" + blpwp_params['options'].size + "px'>" + _f("loading-preview") +"</div>");
					}
				}

				function followMouse(e){
					currentA = convertToAbsoluteURL( e.target.getAttribute("href") );
				
					$('#blpwp').removeClass("fading");
					
					let posX = e.pageX - $('#blpwp').width()/2;
					if($('#blpwp').width() +20 + posX > $(window).width()) {
						posX = $(window).width() - $('#blpwp').width() - 20;
					} else if(posX < 0) {
						posX = 0;
					}

					let posY = e.pageY +50;
					if($('#blpwp').height() +50 + posY > $(document).height()) {
						posY = $(document).height() - $('#blpwp').height() -50;
					}

					$('#blpwp').css({
						left:  posX,
						top:   posY
					});
				}

				let $selector = "";
				if (blpwp_params['options'].contentlinks == 'on') $selector +="a.blpwp";
				if (blpwp_params['options'].commentlinks == 'on') $selector += ($selector!="" ? ", " : "" ) + "a.blpwp_comment";
				if (blpwp_params['options'].selectorchk == 'on' && blpwp_params['options'].selector!="") 
						$selector += ($selector!="" ? ", " : "" ) +  blpwp_params['options'].selector;

				// alert($selector)

				$( $selector ).each(function(index){
					var a = $(this);
					let h = convertToAbsoluteURL( a.attr("href") );

					if( blpwp_params['options'].counter == 'yes' ) {
						setTimeout(function(){
							$.ajax({
								url: blpwp_params['url'],
								type :'POST',
								data: {
									"url" : h,
									"idpost" : blpwp_params['idpost'],
									"action" : 'blpwpgetclick'
								},
								success: function(html){
									if(html>"0") {
										// there was an error
										a.after("<sup class='blpwp_counter'>" + html+"<span> click</span></sup>");
									}
								},
							});

						}, index * 500);
					}
					


					if( blpwp_params['options'].blank == 'on' && h.indexOf("http") == 0 && h.indexOf(blpwp_params["internals"]) == -1) {
						a.attr("target","_blank");
					}

					let check = blpwp_params['options'].extint == '' || h.indexOf("mailto:") == 0;

					if (!check && blpwp_params['options'].extint == 'internal' && (h.indexOf("/") == 0 || h.indexOf(blpwp_params["internals"]) == 0 )) {
						check = true;
					}

					if (!check && blpwp_params['options'].extint == 'external' && h.indexOf("http") == 0 && h.indexOf(blpwp_params["internals"]) == -1 ) {
						check = true;
					}

					let preventClick = 0;
					if (check && blpwp_params['options'].imagelinks == '' && h.match(/(\.(jpe?g|gif|png|webp))$/, 'i') && $(this).find("img").length == 1 && $(this).children().length == 1) {
						check = false;
					}

					if (check) {

						// console.log("check")

						if ($(this).attr("href").indexOf("wp-admin")==-1 && $(this).attr("href").trim()!="") {
							a.data("html","");
							a.data("status",0);
							a.data("preventClick",preventClick);
							a.on("click",function(e){
								e.preventDefault();
								e.stopPropagation();
								e.stopImmediatePropagation();

								// console.log("click")

								if(mobile()) {
									// on mobile the click open the pop up

									prepareLayers();

									setupPopUp(a, e);

								} else {
									// on desktop the click is a click

									triggerClick(a);
								}
							});
							a.on("mouseenter",function(e){
								if(!mobile()) {

									prepareLayers();

									setupPopUp(a, e);

									// on desktop popup follows cursor
									a.on("mousemove",function(e){
										if(!mobile()) followMouse(e);
									});
								}
							});

							// on desktop remove pop up on exit
							a.on("mouseleave",function(e){
								currentA = null;
								if(!mobile()) $('#blpwp').addClass("fading");
							});

						}
					}
				});

				// on desktop when scroll remove pop up
				$(window).on("scroll",function(){
					closePopup();
				});
				$(window).on("resize",function(){
					closePopup();
				});


			});
