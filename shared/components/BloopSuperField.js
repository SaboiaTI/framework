/* ------------------------------------------------------------------------------------------------------
 * BloopSuperField.js
 * 
 * classe do component 'BloopSuperField'
 * comportamento de interface para campos de input dos tipos: "SuperField", "SuperSelect", "SimpleSuperSelect"
 * @author			Saboia Tecnologia da Informa��o <relacionamento@saboia.com.br>
 * @link			http://www.saboia.com.br
 * @version 		1.0
 * @dependencies	jQuery, basicLib
 * ------------------------------------------------------------------------------------------------------
*/

var BloopSuperField = function(htmlElement) {

	var that = this;
	
	this.createSuperDropDown = function(field, dataValues) {
		
		var strList = "";
		var ulWidth;
		var ulLeft;
		var ulMarginLeft;
		var ulMarginRight;
		var fieldRequired = false;
		
		// cria��o e inser��o da lista vazia no DOM
		// 'onSelectStart="return false"' evita que durante a anima��o do dropDown o texto seja selecionado por acidente
		$("ul#drop-down").remove();
		
		ulWidth 	  = field.outerWidth();
		ulLeft 		  = field.position().left;
		ulMarginLeft  = field.css("margin-left");
		ulMarginRight = field.css("margin-right");
		fieldRequired = field.attr('required');
		
		field.parent().append('<ul id="drop-down" onSelectStart=\"return false;\" style="width:'+ulWidth+'px;left:'+ulLeft+'px;margin-left:'+ulMarginLeft+';margin-right:'+ulMarginRight+'"></ul>');
		
		
		
		$("ul#drop-down").stop(true,true).slideUp(0);
		
		
		
		$("ul#drop-down").bind("click", function(event) { event.stopPropagation(); });
		
		// previne o scroll dentro da lista de ativar o scroll da p�gina,
		// dificultando a navega��o pelos �tens em uma lista longa
		var mouseWheelEvent = (/Firefox/i.test(navigator.userAgent)) ? "DOMMouseScroll" : "mousewheel";
		var target = document.getElementById("drop-down");
		
		// -- for IE and some versions of Opera
		if (target.attachEvent) {
			target.attachEvent("on"+mouseWheelEvent, function(event) {
				if (event.stopPropagation) { event.stopPropagation(); }
			});
		}
		// -- for W3C compliant browsers 
		else if (target.addEventListener) {
			target.addEventListener(mouseWheelEvent, function(event) {
				if (event.stopPropagation) { event.stopPropagation(); }
			}, false);
		}
		
		
		
		// se o campo n�o for obrigat�rio, adiciona um valor vazio como o primeiro elemento '<li>':
		if (!fieldRequired) {
			strList += '<li><a id="key0" data-key="0" data-label="" href="#"><i>nenhum / n�o se aplica</i></a></li>';
		}
		
		// transforma cada �tem dos dados em um elemento '<li>':
		var item = 0;
		for (item in dataValues) {
			
			strList += '<li>';
			strList += 	'<a id="key'+dataValues[item].value+'" data-key="'+dataValues[item].value+'" data-label="'+dataValues[item].label+'" href="#">';
			strList += 		dataValues[item].label;
			strList += 	'</a>';
			strList += '</li>';
		}
		
		// inser��o dos �tens na lista:
		$("ul#drop-down").empty();
		$("ul#drop-down").append(strList);
		
		
		
		// anima��o da lista:
		// durante a anima��o, a sele��o de texto do 'body' � cancelada apenas para efeitos visuais;
		// caso o usu�rio, durante a anima��o da lista, com a movimenta��o dos elementos pela tela acabe por selecionar 
		// acidentalmente um trecho de texto, visualmente isso n�o ser� mostrado, mantendo a integridade do layout da interface; 
		// ao fim da anima��o, a possibilidade de sele��o � reestabelecida.
		
		$("body").css({ "-webkit-user-select":"none",
						"-khtml-user-select":"none",
						"-moz-user-select":"none",
						"-o-user-select":"none",
						"user-select":"none"
						});
		$("ul#drop-down").stop(true,true).slideDown(250,"easeOutQuart",function(){
			$("body").css({ "-webkit-user-select":"auto",
							"-khtml-user-select":"text",
							"-moz-user-select":"text",
							"-o-user-select":"text",
							"user-select":"text"
							});
		});
		
		
		
		// listener para fechar a div helper ao clicar no body
		$("body").unbind("click");
		
		$("body").bind("click", function(event) {
	//		if (document.activeElement == "[object HTMLBodyElement]") {
				field.blur();
				
				$("ul#drop-down").stop(true,true).slideUp(250,"easeOutQuart", function() {$(this).remove();});
				$("body").unbind("click");
				
	//		}
		});
		
		
		
		// a��o de click em um elemento "<li>" (sele��o de �tem do dropDown):
		// atribui o valor ao campo, para visualiza��o na tela, e ao data-key, para ser usado no submit do formul�rio;
		$("ul#drop-down").find("li a").bind("click", function(event) {
			
			event.preventDefault();
			
			var id 		= $(this).attr("data-key");
			var label 	= $(this).attr("data-label");
			
			if (id == "") { label = ""; }
			
			field.attr("data-key",id);
			field.val(label);
			
			// dispara o evento "change" para eventuais listeners associados a este evento
			field.change();
			
			 /*
			// TO-DO:
			// encontra o �ndice do foco atual:
			var actualFocus;
			var nextFocus;
			var actualFound;
			var nextFound;
			
			for (f in document.forms[0].elements) {
				
				if (actualFound == true && 
					nextFound != true && 
					document.forms[0].elements[f] == "[object HTMLInputElement]" && 
					document.forms[0].elements[f].getAttribute("disabled") == null
					) {
					
					nextFound = true;
					nextFocus = f;
					//alert("nextFocus:"+nextFocus);
					break;
				}
				
				if (actualFound != true && document.forms[0].elements[f] == field.get(0)) {
					actualFound = true;
					actualFocus = f;
					//alert("actualFocus:"+actualFocus);
				}
			}
			// aplica o foco no pr�ximo elemento v�lido, se houver:
			if (document.forms[0].elements[nextFocus]) {
				document.forms[0].elements[nextFocus].focus();
			}
			// */
			
			$("body").click();
			
		});
	};



	this.createSuperField = function() {
		
		if (htmlElement) {
			
			htmlElement.addClass("super-field");
			
			if (htmlElement.attr("data-x-wrap") != "false") {
				htmlElement.wrap('<div class="row">');
				htmlElement.before('<label for="' + htmlElement.attr("id") + '">' + htmlElement.attr("data-x-label") + '</label>');
			}
			
			if (htmlElement.attr("data-x-helper")) {
				htmlElement.after('<span class="helper">'+htmlElement.attr("data-x-helper")+'</span>');
			}
			
			if (htmlElement.attr("data-x-clonable") == "true") {
				htmlElement.after('<a class="button add-field" href="#"><img src="/shared/style/images/ico-new.png">&nbsp;adicionar campo</a>');
			}
			
			if (htmlElement.attr("data-x-group-clonable") == "true") {
				htmlElement.after('<a class="button add-fieldset" href="#"><img src="/shared/style/images/ico-new.png">&nbsp;adicionar grupo</a>');
			}
			
			if (htmlElement.attr("data-x-readonly") == "true") {
				htmlElement.addClass("read-only");
				htmlElement.bind("keypress", function(event) { event.preventDefault(); });
				// previne teclas delete e backspace:
				htmlElement.bind("keydown",  function(event) { if (event.keyCode == 8 || event.keyCode == 46) { event.preventDefault(); } });
			}
			
			if (htmlElement.attr("data-x-type")) {
			
				// checks if the function 'formatField' is defined in 'core.js'
				if (typeof formatField == 'function') {
					htmlElement.bind("keydown", function(event) {
						formatField(htmlElement, event, htmlElement.attr("data-x-type"));
					});
				}
				
			}
			
		}
		
	};



	this.createSuperSelect = function() {
		
		if (htmlElement) {
			
			htmlElement.addClass("super-select");
			
			if (htmlElement.attr("data-x-wrap") != "false") {
				htmlElement.wrap('<div class="row">');
				htmlElement.before('<label for="' + htmlElement.attr("id") + '">' + htmlElement.attr("data-x-label") + '</label>');
			}
			
			if (htmlElement.attr("data-x-helper")) {
				htmlElement.after('<span class="helper">'+htmlElement.attr("data-x-helper")+'</span>');
			}
			
			if (htmlElement.attr("data-x-clonable") == "true") {
				htmlElement.after('<a class="a-button add-field" href="#"><img src="/shared/style/images/ico-new.png">&nbsp;adicionar campo</a>');
			}
			
			if (htmlElement.attr("data-x-readonly") == "true") {
				htmlElement.addClass("read-only");
				htmlElement.bind("keypress", function(event) { event.preventDefault(); });
				// previne teclas delete e backspace:
				htmlElement.bind("keydown",  function(event) { if (event.keyCode == 8 || event.keyCode == 46) { event.preventDefault(); } });
			}
			
			// lista de dados:
			if (htmlElement.attr("data-x-values")) {
				
				htmlElement.bind("click", function(event) { event.stopImmediatePropagation(); });
				htmlElement.bind("focus", function(event) {
					
					var dataValues;
					
					if ( typeof($(this).attr("data-x-values")) != "object") {
						dataValues = jQuery.parseJSON($(this).attr("data-x-values"));
					} else {
						dataValues = $(this).attr("data-x-values");
					}
					that.createSuperDropDown($(this), dataValues);
					
				});
				
			}
		
		}
		
	};

	this.updateSuperSelectData = function() {
		
		if (htmlElement) {
			
			// lista de dados:
			if (htmlElement.attr("data-x-values")) {
				
				htmlElement.unbind("click");
				htmlElement.unbind("focus");
				
				htmlElement.bind("click", function(event) { event.stopImmediatePropagation(); });
				htmlElement.bind("focus", function(event) {
					
					var dataValues;
					
					if ( typeof($(this).attr("data-x-values")) != "object") {
						dataValues = jQuery.parseJSON($(this).attr("data-x-values"));
					} else {
						dataValues = $(this).attr("data-x-values");
					}
					
					that.createSuperDropDown($(this), dataValues);
					
				});
				
			}
		
		}
		
	};

	this.createSimpleSuperSelect = function() {
		
		if (htmlElement) {
			
			htmlElement.addClass("super-select");
			htmlElement.wrap('<div style="position:relative;">');
			//$(this).wrap('<div class="row">');
			
			if (htmlElement.attr("data-x-readonly") == "true") {
				htmlElement.addClass("read-only");
				htmlElement.bind("keypress", function(event) { event.preventDefault(); });
				// previne teclas delete e backspace:
				htmlElement.bind("keydown",  function(event) { if (event.keyCode == 8 || event.keyCode == 46) { event.preventDefault(); } });
			}
			
			// lista de dados:
			if (htmlElement.attr("data-x-values")) {
				
				htmlElement.bind("click", function(event) { event.stopImmediatePropagation(); });
				htmlElement.bind("focus", function(event) {
					
					var dataValues = jQuery.parseJSON($(this).attr("data-x-values"));
					that.createSuperDropDown($(this), dataValues);
					
				});
			}
			
		}
		
	};
	
}
