/**
 * BloopScrollbar.js
 * 
 * Classe do component 'BloopScrollbar', que adiciona uma barra de navegação alternativa à rolagem natural do browser,
 * fornecendo, além da rolagem, ferramentas para navegação entre páginas de uma lista de registros (recordList)
 * @author			Saboia Tecnologia da Informação <relacionamento@saboia.com.br>
 * @link			http://www.saboia.com.br
 * @version			1.0
 * @dependencies	jQuery
 * 
 */




var BloopScrollbar = (function() {

	var instance = function(htmlElement, list) {
		
		// PRIVATE PROPERTIES
		var that = this;
		
		var scrollableTarget = typeof htmlElement === 'undefined' ? null  : $(htmlElement);
																// HTMLElement: elemento controlado pela scrollbar
		var list			 = typeof list  === 'undefined' ? null : list;
		var scrollTime		 = 250;
		var scrollDistance	 = 250;
		
		var scrollableContainer = scrollableTarget.parent();	// HTMLElement: elemento onde será posicionada a scrollbar
		
		var scrollbar		 = null;
		var scrollUpButton	 = null;
		var scrollDownButton = null;
		var scrollButton	 = null;
		var navFirstButton	 = null;
		var navPrevButton	 = null;
		var navNextButton	 = null;
		var navLastButton	 = null;
		
		
		// PRIVATE METHODS
/**
 * Monta a marcação HTML do elemento scrollbar
 * @returns string 	marcação HTML
 */
		var _buildHTMLScrollbar = function(){
			
			var str =  '<div class="scrollbar">';
				
			if (list) {
				str += '	<div class="scrollbar-first"></div>';
				str += '	<div class="scrollbar-prev"></div>';
			}	
				str += '	<div class="scrollbar-up"></div>';
				str += '	<div class="scrollbar-button-container">';
				str += '		<div class="scrollbar-button"></div>';
				str += '	</div>';
				str += '	<div class="scrollbar-down"></div>';
				
			if (list) {
				str += '	<div class="scrollbar-next"></div>';
				str += '	<div class="scrollbar-last"></div>';
			}
				str += '</div>';
			
			return str;
		}
		
/**
 * Aplica o elemento scrollbar na interface
 * @returns string 	marcação HTML
 */
		var _createScrollbar = function(){
			
			scrollableTarget.css({'overflow':'hidden'});
			scrollableContainer.after(_buildHTMLScrollbar());
			
			scrollbar		 = scrollableContainer.siblings('div.scrollbar');
			scrollUpButton	 = scrollbar.find('div.scrollbar-up');
			scrollDownButton = scrollbar.find('div.scrollbar-down');
			scrollButton	 = scrollbar.find('div.scrollbar-button');
			navFirstButton	 = scrollbar.find('div.scrollbar-first');
			navPrevButton	 = scrollbar.find('div.scrollbar-prev');
			navNextButton	 = scrollbar.find('div.scrollbar-next');
			navLastButton	 = scrollbar.find('div.scrollbar-last');
		}
		
		var _dragScroll = function(controller){
			
			var targetOffset,
				porc;
			
			// procentagem da rolagem do conteúdo:
			porc = controller.position().top / ( controller.parent().innerHeight() - controller.outerHeight() ) * 100;
			porc = Math.round(porc);
			
			targetOffset = porc * ( scrollableTarget.get(0).scrollHeight - scrollableTarget.get(0).clientHeight + 20 ) / 100;
			
			// rolagem do conteúdo:
			scrollableTarget.scrollTop(targetOffset);
			//scrollButtonMini.animate({top:controller.position().top}, 0);
		}
		
		var _bindScrollBehavior = function(){
			
			scrollUpButton.unbind('click');
			scrollUpButton.bind('click', function(event){
				event.stopPropagation();
				that.scrollAmount(scrollDistance*-1);
			});
			
			scrollDownButton.unbind('click'); 
			scrollDownButton.bind('click', function(event){
				event.stopPropagation();
				that.scrollAmount(scrollDistance);
			});
			
			scrollButton.draggable({containment:'parent',axis:'y'});
			scrollButton.unbind('drag');
			scrollButton.bind('drag', function(event){
				_dragScroll($(this));
			});
			
			
			// mousewheel:
			var scrollableHTMLElement = scrollableTarget.get(0);
			var scrollbarHTMLElement  = scrollbar.get(0);
			
			var mousewheelevt=(/Firefox/i.test(navigator.userAgent))? "DOMMouseScroll" : "mousewheel";
			
			// for scrolling with the mouse over the scrollable element (IE-8 and some versions of Opera)
			if (scrollableHTMLElement.attachEvent) {
				
				scrollableHTMLElement.attachEvent("on"+mousewheelevt, function(event) {
					
					if (event.stopPropagation) { event.stopPropagation(); }
					
					if (event.detail < 0 || event.wheelDelta > 0) {
						scrollUpButton.click();
					}
					else if (event.detail > 0 || event.wheelDelta < 0) {
						scrollDownButton.click();
					}
				});
			}
			
			// for scrolling with the mouse over the scrollable element (W3C browsers)
			else if (scrollableHTMLElement.addEventListener) {
				
				scrollableHTMLElement.addEventListener(mousewheelevt, function(event) {
					
					if (event.stopPropagation) { event.stopPropagation(); }
					
					if (event.detail < 0 || event.wheelDelta > 0) {
						scrollUpButton.click();
					}
					else if (event.detail > 0 || event.wheelDelta < 0) {
						scrollDownButton.click();
					}
				}, false);
			}
			
			
			
			// for scrolling with the mouse over the scrollbar (IE-8 and some versions of Opera)
			if (scrollbarHTMLElement.attachEvent) {
				
				scrollbarHTMLElement.attachEvent("on"+mousewheelevt, function(event) {
					
					if (event.stopPropagation) { event.stopPropagation(); }
					
					if (event.detail < 0 || event.wheelDelta > 0) {
						scrollUpButton.click();
					}
					else if (event.detail > 0 || event.wheelDelta < 0) {
						scrollDownButton.click();
					}
				});
			}
			
			// for scrolling with the mouse over the scrollbar (W3C browsers)
			else if (scrollbarHTMLElement.addEventListener) {
				
				scrollbarHTMLElement.addEventListener(mousewheelevt, function(event) {
					
					if (event.stopPropagation) { event.stopPropagation(); }
					
					if (event.detail < 0 || event.wheelDelta > 0) {
						scrollUpButton.click();
					}
					else if (event.detail > 0 || event.wheelDelta < 0) {
						scrollDownButton.click();
					}
				}, false);
			}
			
			
			
			
			
		}
		
		var _bindNavBehavior = function(navFirstAction,navPrevAction,navNextAction,navLastAction){
			
			navFirstButton.unbind('click');
			navFirstButton.bind('click', navFirstAction);
			
			navPrevButton.unbind('click');
			navPrevButton.bind('click', navPrevAction);
			
			navNextButton.unbind('click');
			navNextButton.bind('click', navNextAction);
			
			navLastButton.unbind('click');
			navLastButton.bind('click', navLastAction);
		}
		
		
		// PUBLIC METHODS
/**
 * Expõe para o usuário a possibilidade de alterar o comportamento padrão para os botões de navegação e paginação da lista
 * 
 * @params int amount 	'tamanho' da rolagem a ser realizada, em relação à rolagem atual do elemento
 * @params int time 	duração da animação da rolagem do elemento
 * @returns void
 */
		this.scrollAmount = function(amount, time){
			
			var duration = !isNaN(parseInt(time,10)) ? time : scrollTime;
			
			var targetOffset, 
				newOffset,
				porc, h, min, max;
			
			targetOffset = Math.round(scrollableTarget.scrollTop());
			newOffset	 = amount != 0 ? targetOffset + amount : 0;
			
			// rolagem do conteúdo:
			scrollableTarget.stop(true,true).animate({"scrollTop":newOffset}, duration);
			
			// procentagem da rolagem do conteúdo:
			porc = newOffset / ( scrollableTarget.get(0).scrollHeight - scrollableTarget.get(0).clientHeight ) * 100;
			porc = Math.min(Math.round(porc), 100);
			
			// ajuste do botão da barra de rolagem:
			h = porc * (scrollButton.parent().innerHeight() - scrollButton.innerHeight()) / 100;
			var min = 0;
			var max = scrollButton.parent().innerHeight() - scrollButton.innerHeight();
			h = Math.min(Math.max(h, 0), max);
			
			scrollButton.stop(true,true).animate({top:h}, duration);
			//scrollButtonMini.stop(true,true).animate({top:h}, duration);
			
		}
		
/**
 * Expõe para o usuário a possibilidade de alterar o comportamento padrão para os botões de navegação e paginação da lista
 * Atenção: este método substitui completamente a navegação da lista, podendo causar comportamentos inesperados!
 * Será necessário sempre redefinir o comportamento completo, incluindo o carregamento das páginas da lista
 * 
 * @params function navFirstAction	função a ser chamada no evento de click do botão navFirstButton
 * @params function navPrevAction	função a ser chamada no evento de click do botão navPrevButton
 * @params function navNextAction	função a ser chamada no evento de click do botão navNextButton
 * @params function navLastAction	função a ser chamada no evento de click do botão navLastButton
 */
		this.bindNavBehavior = function(navFirstAction, navPrevAction, navNextAction, navLastAction){
			return _bindNavBehavior(navFirstAction, navPrevAction, navNextAction, navLastAction);
		}
		
		_createScrollbar();
		_bindScrollBehavior();
		if (list) _bindNavBehavior(
						function(data){list.load(1)}, 
						function(data){list.previousPage()}, 
						function(data){list.nextPage()}, 
						function(data){list.load(Number.MAX_VALUE)}
					);
	}
	
	return instance;
})();


/*
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
		
	// 	TO-DO: renomear classes dos elementos da  barra de rolagem para o padrão abaixo:
	//	strScroll += 	'<div class="scrollbar">';
	//	strScroll +=		'<div class="scrollbar-up"></div>';
	//	strScroll +=		'<div class="scrollbar-button-container">';
	//	strScroll +=			'<div class="scrollbar-button"></div>';
	//	strScroll +=		'</div>';
	//	strScroll +=		'<div class="scrollbar-down"></div>';
	//	strScroll +=	'</div>';
	//	
	//	strScroll +=	'<div class="scrollbar-mini">';
	//	strScroll +=		'<div class="scrollbar-button-container">';
	//	strScroll +=			'<div class="scrollbar-button"></div>';
	//	strScroll +=		'</div>';
	//	strScroll +=	'</div>';
		
		
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

//*/

