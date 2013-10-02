/* ------------------------------------------------------------------------------------------------------
 * BloopScrollbar.js
 * 
 * classe do component 'BloopScrollbar'
 * @author			Saboia Tecnologia da Informação <relacionamento@saboia.com.br>
 * @link			http://www.saboia.com.br
 * @version			1.0
 * @dependencies	jQuery
 * ------------------------------------------------------------------------------------------------------
*/

var BloopScrollbar = function(htmlElement, list) {
	
	var that = this;
	var scrollableTarget = htmlElement;
	var scrollbar;
	var scrollbarMini;
	var scrollButton;
	var scrollButtonMini;
	
	// if a 'list' is passed as a parameter, the scrollbar will also act as a page navigator
	this.navigation = list ? true : false;
	
	
	
	this.createScrollbar = function() {
		
		var strScroll = '';
		
		// TO-DO: renomear classes dos elementos da  barra de rolagem para o padrão abaixo:
		
		/*
		strScroll += 	'<div class="scrollbar">';
		strScroll +=		'<div class="scrollbar-up"></div>';
		strScroll +=		'<div class="scrollbar-button-container">';
		strScroll +=			'<div class="scrollbar-button"></div>';
		strScroll +=		'</div>';
		strScroll +=		'<div class="scrollbar-down"></div>';
		strScroll +=	'</div>';
		
		strScroll +=	'<div class="scrollbar-mini">';
		strScroll +=		'<div class="scrollbar-button-container">';
		strScroll +=			'<div class="scrollbar-button"></div>';
		strScroll +=		'</div>';
		strScroll +=	'</div>';
		*/
		
		if (this.navigation == true) {
			
			strScroll += '<div class="scrollerbar scrollerbar-navigation">';
			
			strScroll += '<div id="first" class="scroll-first"></div>';
			strScroll += '<div id="prev" class="scroll-prev"></div>';
			
			strScroll += '<div id="up" class="scroll-up"></div>';
			strScroll += '<div id="bar-container" class="scroll-bar-container"><div id="bar" class="scroll-bar"></div></div>';
			strScroll += '<div id="down" class="scroll-down"></div>';
			
			strScroll += '<div id="next" class="scroll-next"></div>';
			strScroll += '<div id="last" class="scroll-last"></div>';
			
			strScroll += '</div>';
			strScroll += '<div class="scrollerbar-min">';
			strScroll += '<div id="bar-container" class="scroll-bar-container"><div id="bar" class="scroll-bar"></div></div>';
			strScroll += '</div>';
			
		} else {
			
			strScroll += '<div class="scrollerbar">';
			
			strScroll += '<div id="up" class="scroll-up"></div>';
			strScroll += '<div id="bar-container" class="scroll-bar-container"><div id="bar" class="scroll-bar"></div></div>';
			strScroll += '<div id="down" class="scroll-down"></div>';
			
			strScroll += '</div>';
			strScroll += '<div class="scrollerbar-min">';
			strScroll += '<div id="bar-container" class="scroll-bar-container"><div id="bar" class="scroll-bar"></div></div>';
			strScroll += '</div>';
			
		}
		
		scrollableTarget.after(strScroll);
		
		scrollbar		 = $(scrollableTarget.parent().find("div.scrollerbar"));
		scrollbarMini	 = $(scrollableTarget.parent().find("div.scrollerbar-min"));
		
		scrollButton	 = scrollbar.find("div.scroll-bar");
		scrollButtonMini = scrollbarMini.find("div.scroll-bar");
	};
	
	this.scrollAmount = function(amount,time) {
		
		var duration;
		var targetOffset;
		var newOffset;
		var porc;
		var h;
		
		duration	 = !isNaN(parseInt(time,10)) ? time : 100;
		
		targetOffset = Math.round(scrollableTarget.scrollTop());
		newOffset	 = amount != 0 ? targetOffset + amount : 0;
		
		// rolagem do conteúdo:
		scrollableTarget.stop(true,true).animate({"scrollTop":newOffset}, duration);
		
		// cálculo da procentagem da rolagem do conteúdo:
		porc = newOffset / ( scrollableTarget.get(0).scrollHeight - scrollableTarget.get(0).clientHeight ) * 100;
		porc = Math.round(porc);
		if (porc > 100) porc = 100;
		
		// ajuste da barra de rolagem azul:
		h = porc * (scrollButton.parent().innerHeight() - scrollButton.innerHeight()) / 100;
		if (h < 0) {
			h = 0;
		}
		else if ( h > scrollButton.parent().innerHeight() - scrollButton.innerHeight() ) {
			scrollButton.parent().innerHeight() - scrollButton.innerHeight();
		}
		
		scrollButton.stop(true,true).animate({top:h}, duration);
		scrollButtonMini.stop(true,true).animate({top:h}, duration);
	};
	
	this.dragScroll = function(controller) {
		
		var porc;
		var targetOffset;
		
		// -- porcentagem da posição do botão:
		porc = controller.position().top / ( controller.parent().innerHeight() - controller.outerHeight() ) * 100;
		porc = Math.round(porc);
		
		// -- ajusta a rolagem do conteúdo à porcentagem da posição do botão:
		targetOffset = porc * ( scrollableTarget.get(0).scrollHeight - scrollableTarget.get(0).clientHeight + 20 ) / 100;
		
		//alert(scrollableTarget.get(0).scrollHeight);
		
		scrollableTarget.scrollTop(targetOffset);
		scrollButtonMini.animate({top:controller.position().top}, 0);
	};
	
	this.bindScrollBehavior = function() {
		
		// visibilidade da barra completa no mouseover:
		scrollbar.bind("mouseover", function(){
			$(this).stop(true).animate({"opacity":1}, "fast");
			
		});
		scrollbar.bind("mouseout", function(){
			$(this).stop(true).animate({"opacity":0}, "fast");
		});
		
		// botões de rolagem:
		scrollbar.find("div.scroll-up").bind("click", function(event) { 
			event.stopPropagation();
			that.scrollAmount(-100);
		});
		scrollbar.find("div.scroll-down").bind("click", function(event) { 
			event.stopPropagation();
			that.scrollAmount(100);
		});
		
		// barra:
		scrollButton.draggable({containment:'parent', axis:'y'});
		scrollButton.bind("drag", function() {
			that.dragScroll( $(this) );
		});
		
		
		
		// mousewheel:
		var scrollableHTMLElement = scrollableTarget.get(0);
		var scrollbarHTMLElement  = scrollbar.get(0);
		var scrollerUp			  = scrollbar.find("div.scroll-up");
		var scrollerDown		  = scrollbar.find("div.scroll-down");
		
		var mousewheelevt=(/Firefox/i.test(navigator.userAgent))? "DOMMouseScroll" : "mousewheel";
		
		// for scrolling with the mouse over the scrollable element
		// -- for IE-8 and some versions of Opera
		if (scrollableHTMLElement.attachEvent) {
			
			scrollableHTMLElement.attachEvent("on"+mousewheelevt, function(event) {
				
				if (event.stopPropagation) { event.stopPropagation(); }
				
				if (event.detail < 0 || event.wheelDelta > 0) {
					scrollerUp.click();
				}
				else if (event.detail > 0 || event.wheelDelta < 0) {
					scrollerDown.click();
				}
			});
		}
		
		// -- for W3C browsers 
		else if (scrollableHTMLElement.addEventListener) {
			
			scrollableHTMLElement.addEventListener(mousewheelevt, function(event) {
				
				if (event.stopPropagation) { event.stopPropagation(); }
				
				if (event.detail < 0 || event.wheelDelta > 0) {
					scrollerUp.click();
				}
				else if (event.detail > 0 || event.wheelDelta < 0) {
					scrollerDown.click();
				}
			}, false);
		}
		
		
		
		// for scrolling with the mouse over the scrollbar
		// -- for IE-8 and some versions of Opera
		if (scrollbarHTMLElement.attachEvent) {
			
			scrollbarHTMLElement.attachEvent("on"+mousewheelevt, function(event) {
				
				if (event.stopPropagation) { event.stopPropagation(); }
				
				if (event.detail < 0 || event.wheelDelta > 0) {
					scrollerUp.click();
				}
				else if (event.detail > 0 || event.wheelDelta < 0) {
					scrollerDown.click();
				}
			});
		}
		
		// -- for W3C browsers 
		else if (scrollbarHTMLElement.addEventListener) {
			
			scrollbarHTMLElement.addEventListener(mousewheelevt, function(event) {
				
				if (event.stopPropagation) { event.stopPropagation(); }
				
				if (event.detail < 0 || event.wheelDelta > 0) {
					scrollerUp.click();
				}
				else if (event.detail > 0 || event.wheelDelta < 0) {
					scrollerDown.click();
				}
			}, false);
		}
		
		
		// keys - up arrow / down arrow:
		/*
		if (scrollableHTMLElement.addEventListener) {
			
			scrollableHTMLElement.addEventListener('keydown', function(event) {
			
				var key = event.keyCode;
				alert('event.keyCode:' + key);
				
				if (key==38) {
					// tecla up arrow
					alert('up we go!');
					scrollerUp.click();
					
				} else if (key==40) {
					// tecla down arrow
					alert('down the rabbit hole!');
					scrollerDown.click();
				}
			});
		}
		*/
	};
	
	this.bindNavigationBehavior = function() {
		
		// navegação para próxima página e página anterior da list:
		
		scrollbar.find("div.scroll-next").bind("click", function(event) { 
			
			event.stopImmediatePropagation();
			list.getList(true,"next");
			that.scrollAmount(0);
		});
		
		scrollbar.find("div.scroll-prev").bind("click", function(event) {
			
			event.stopImmediatePropagation();
			list.getList(true,"prev");
			that.scrollAmount(0);
		});
		
		
		// navegação para primeira página e última página da list:
		
		scrollbar.find("div.scroll-first").bind("click", function(event) { 
			
			event.stopImmediatePropagation();
			list.getList(true,"first");
			that.scrollAmount(0);
		});
		
		scrollbar.find("div.scroll-last").bind("click", function(event) { 
			
			event.stopImmediatePropagation();
			list.getList(true,"last");
			that.scrollAmount(0);
		});
		
	}
	
	
	
	
	
	this.createScrollbar();
	this.bindScrollBehavior();
	
	if (this.navigation == true) {
		this.bindNavigationBehavior();
	}
	
};