/**
 * core.js
 * Funções e comportamentos comuns ao framework Bloop
 * @version 		2.0
 * @dependencies	jQuery
 */



/**
 * inheritsFrom
 * Função usada para extender objetos em JavaScript, simulando OOP
 * @params function(object) parentClassOrObject Objeto que será herdado
 * @return function(object)
 * Gavin Kistner {http://phrogz.net/JS/classes/OOPinJS.html} e {http://phrogz.net/JS/classes/OOPinJS2.html}
 */
Function.prototype.inheritsFrom = function(parentClassOrObject){
	if (parentClassOrObject.constructor == Function){
		// normal inheritance
		this.prototype = new parentClassOrObject();
		this.prototype.constructor = this;
		this.prototype.parent = parentClassOrObject.prototype;
	} else { 
		// pure virtual inheritance
		this.prototype = parentClassOrObject;
		this.prototype.constructor = this;
		this.prototype.parent = parentClassOrObject;
	}
	return this;
}



/**
 * Extends the funcionality of Object, declaring a new function
 * Returns the "length" of an object
 *  
 * @params  object obj the Object we need to get the size
 * @returns int        the size of the object
 * 
 */
Object.size = function(obj) {
    var size = 0;
	var key;
    for (key in obj) {
        if (obj.hasOwnProperty(key)) size++;
    }
    return size;
}




/**
 * Get some data from the API
 * 
 * @params  string object     the name of the requested API Object
 * @params  string method     the name of the requested API Method 
 * @params  object data       the data to pass to the API to handle the request
 * @params  function callback a function to be executed when the data is successfully retrieved
 * @returns object            the data retrieved from the API, or the data error messages
 */
var DELETAR__getData = function(object, method, data, callback) {
	
	if (typeof object === 'undefined') return null;
	if (typeof method === 'undefined') return null;
	if (typeof data   === 'undefined' || data == null || !data) data = {"void":null};
	// the API expects some data, even if not really required by the method...
	
	callOrbtal(object, method, data, true, callback);
	return true;
}


/**
 * Makes a generic request to the API
 * and handles login requests, error messages and some other things...
 *  
 * @params  string object     the name of the requested API Object
 * @params  string method     the name of the requested API Method 
 * @params  object data       the data to pass to the API to handle the request
 * @params  function callback a function to be executed when the data is successfully retrieved
 * @returns void              (the data retrieved from the API, or the data error messages, is used in the callback function)
 * 
 */
var callOrbtal = function(object, method, params, requiresLogin, callback) {
	
	if (typeof object === 'undefined') return null;
	if (typeof method === 'undefined') return null;
	if (typeof params === 'undefined' || params == null || !params) params = {"void":null};
	
	requiresLogin = requiresLogin ? requiresLogin : false;
	
	var bmw = new BloopModalWindow();
	
	$.ajax({
		type:		"POST",
		url: 		"/shared/api/orbtal-api.php",
		contentType:"application/x-www-form-urlencoded;charset=UTF-8",
		dataType: 	"json",
		data: 		{
						"object":object,
						"method":method,
						"data":params
					},
		success:	function(data){
						
						if (data.debug) console.log(data.debug);
						
						// requires login, and the user is NOT logged in
						// prompts the user to log in and makes a new request attempt
						if (requiresLogin && data.applicationControl && !data.applicationControl.authenticated){
							
							bmw.resetModal();
							bmw.modalType 		= "login";
							bmw.modalPriority 	= "default";
							bmw.modalTitle 		= m('MSG_LOGIN');
							bmw.submitLabel 	= m('MSG_BUTTON_LOGIN');
							bmw.modalMessage 	= m('MSG_WARNING_LOGIN');
							bmw.modalCallBack	= function() {

								var auth = new Authentication();
								auth.login(
									$.trim($("#login").val()),
									$.trim($("#password").val()), 
									// success:
									function(){
										// removemos a janela modal de autenticação e realizamos nova chamada ao callObtal
										bmw.removeModal(250);
										callOrbtal(object, method, params, requiresLogin, callback);
									}, 

									// invalid login:
									function(){
										// indicamos ao usuário que o login não foi bem sucedido, para que tente novamente
										$("#login, #password").css({"border-color":"#F90"});
										$("p#message").text("LOGIN INVÁLIDO. POR FAVOR VERIFIQUE SEU USUÁRIO E SENHA.");
									}, 

									// system in maintenance:
									function(){
										// indicamos ao usuário que o sistema está em manutenção, e o redirecionamos para a home
										bmw.removeModal(0);
										bmw.resetModal();
										bmw.modalType 		= "message";
										bmw.modalPriority 	= "alert";
										bmw.modalTitle 		= "Sistema em Manutenção";
										bmw.submitLabel 	= "ok";
										bmw.modalMessage 	= "Sistema em manutenção. Por favor, tente novamente mais tarde.";
										bmw.modalCallBack	= function(){ if (!(/modules\/login/.test(window.location))) { window.location = '/'; } };
										bmw.outputModal();
									}
								);
							}
							bmw.outputModal();
							
						}
						
						// there is some type of error with the request
						// just alerts the user, our work here is done!
						else if (data.applicationControl && data.applicationControl.error) {
							
							bmw.resetModal();
							bmw.modalType 		= "message";
							bmw.modalPriority 	= "critical";
							bmw.modalTitle 		= "Erro";
							bmw.submitLabel 	= "ok";
							bmw.modalMessage 	= "Ocorreu um erro com a requisição solicitada pelo sistema. Por favor, tente novamente mais tarde.<br><br>";
							bmw.modalMessage   += "<span style=\"font-size:0.9em;font-style:italic;color:#999;\">Error information:<br>";
							bmw.modalMessage   += "error #"+data.applicationControl.error.number+" &mdash; "+data.applicationControl.error.type+"<br>";
							bmw.modalMessage   += data.applicationControl.error.message+"</span>";
							bmw.outputModal();
							
						}
						
						// the request was not successfull
						else if (!data.success) {
							
							bmw.resetModal();
							bmw.modalType 		= "message";
							bmw.modalPriority 	= "critical";
							bmw.modalTitle 		= "Erro";
							bmw.submitLabel 	= "ok";
							bmw.modalMessage 	= "Não foi possível concluir a requisição solicitada. Por favor, verifique as informações fornecidas e tente novamente.";
							bmw.outputModal();
							
						}
						
						// everything went okay, so...
						// answer the request, calling the callback method (if any)
						else if (data.success) {
							
							if (callback && typeof(callback) === "function") callback(data);
						}
						
						// something went wrong... and we were not able to figure out why!
						// answer the request with a default error message
						else {
							
							bmw.resetModal();
							bmw.modalType 		= "message";
							bmw.modalPriority 	= "critical";
							bmw.modalTitle 		= "Erro";
							bmw.submitLabel 	= "ok";
							bmw.modalMessage 	= "Ocorreu um erro com a requisição solicitada pelo sistema. Por favor, tente novamente mais tarde.";
							bmw.outputModal();
						}
				
					}
	});
	
}





/**
 * Translate a token from the dictionary
 * The dictionary is constructed by the PHP when the page is loaded, 
 * and stored as a javascript associative array "DICTIONARY"
 * 
 * @params  String tokenMSG a token from the dictionary
 * @params  String replace an array containing parameters to replace in the message
 * @returns String the corresponding message related to the tokenMSG in the dictionary
 */
this.m = function(tokenMSG, replace) {
	
	tokenMSG = (typeof tokenMSG === "undefined") ? null : tokenMSG;
	replace  = (typeof replace  === "undefined") ? null : replace;
	
	var message;
	var pattern;
	
	message = DICTIONARY[tokenMSG] ? DICTIONARY[tokenMSG] : tokenMSG;
	
	if ( replace!=null && (replace instanceof Array) ) {
		
		var pattern = message.match(/(%\d+%)/g);
		
		pattern = pattern != null ? pattern : [];
		
		for ( i=0; i<pattern.length; i++ ) {
			
			var index = parseInt(pattern[i].replace('%', ''),10)-1;
			
			if (replace[index]) {
				
				var sWhat = pattern[i];
				var sFor  = replace[index];
				message   = message.replace(sWhat, sFor);
				
			}
			
		}
		
	}
	
	return message;
}




/**
 * Checks for the sValue in a given object, assuming the sintax used in our orbtal API
 * for example, in 'data.recordset.thePropertyName[0].sValue'
 * This method is prepared to return the value in any object structure that may be returned from the orbtal API:
 * @params String what         with the name of the property we want to find
 * @params Object where        where we will search for the property
 * @params Mixed defaultValue  what will be returned case we can not find the property in the object
 * @params Boolean allValues   indicating if we will return an array with all the values, or just the first
 * @returns Mixed              the found value(s), if any, or the defaultValue passed as a parameter
 */
this.getParam = function(what, where, defaultValue, allValues) {
	
	if (typeof what  === 'undefined') return null;
	if (typeof where === 'undefined') return null;
	
	defaultValue = (typeof defaultValue === 'undefined') ? null  : defaultValue;
	allValues    = (typeof allValues    === 'undefined') ? false : allValues;
	
	
	// sintax like: 'data.recordset.thePropertyName[0].sValue'
	if (where[what] && where[what][0].sValue && where[what][0].sValue !== null) {
		
		return (allValues) ? where[what] : where[what][0].sValue;
		//if (allValues) return where[what];
		//else return where[what][0].sValue;
	
	// sintax like: 'data.recordset.thePropertyName[0]'	
	} else if (where[what] && (where[what] instanceof Array) && where[what][0] !== null) {
		return where[what][0];
	
	// sintax like: 'data.recordset.thePropertyName.sValue'	
	} else if (where[what] && where[what].sValue && where[what].sValue !== null) {
		return where[what].sValue;
	
	// sintax like: 'data.recordset.thePropertyName'
	} else if (where[what] && where[what] !== null) {
		return where[what];
		
	} else {
		return defaultValue;
	}
}




/**
 * Builds the HTML markup for a <select>
 * 
 * @returns String the HTML markup for the entire <select>
 */
this.buildSelectOptions = function(data, selectValue, required, extraValue) {
	
	if (typeof data === 'undefined') return false;
	
	selectValue = typeof selectValue === 'undefined' ? null  : selectValue;
	required    = typeof required    === 'undefined' ? false : required;
	extraValue  = typeof extraValue  === 'undefined' ? null  : extraValue;
	
	
	var str  = '<option value="" ';
		str += (!required) ? '' : 'disabled ';
		str += (selectValue != null) ? '' : 'selected ';
		str += '>selecione</option>';
	
	if (extraValue != null && extraValue.value) {
		str += '<option value="'+extraValue.id+'" data-key="'+extraValue.id+'" data-value="'+extraValue.value+'">'+extraValue.value+'</option>';
	}
	
	for (d in data) {
		str += '<option ';
		str += 	'value="'+data[d].id+'" ';
		str += 	'data-key="'+data[d].id+'" ';
		str += 	'data-value="'+data[d].value+'" ';
		
		str += (data[d].id == selectValue && selectValue != null) ? 'selected ' : '';
		
		str += '>';
		str += 	data[d].value;
		str += '</option>';
	}
	
	return str;
}




// -------------------------------------------------------------------------------------------------
// método:			gup()
// propósito:		use este método para ler informações da URL, mimetizando o recebimento de parâmetros via GET
// parâmetros:		paramName:String	indica o parâmetro a ser procurado na URL
// retorna:			String				valor do parâmetro procurado
// afeta:			
// dependências:	
// eventos:			
// exemplo:			gup("id");
// comentários:		
// -------------------------------------------------------------------------------------------------

this.gup = function(paramName) {

	paramName = paramName.replace(/[\[]/,"\\\[").replace(/[\]]/,"\\\]");
	var regexS = "[\\?&]"+paramName+"=([^&#]*)";
	var regex = new RegExp( regexS );
	var results = regex.exec( window.location.href );
	if( results == null )
		return "";
	else
		return results[1];
}





// -------------------------------------------------------------------------------------------------
// método:			xmlToJson()
// propósito:		use este método para transformar uma estrutura de XML em um objeto no formato JSON
// parâmetros:		xml:XMLDocument		estrutura de dados XML a ser convertida para JSON
// retorna:			Object				estrutura de dados em formato JSON
// afeta:			
// dependências:	
// eventos:			
// exemplo:			xmlToJson(xml);
// comentários:		baseado em: http://davidwalsh.name/convert-xml-json
// -------------------------------------------------------------------------------------------------

this.xmlToJson = function(xml) {
	
	var obj = {};

	if (xml.nodeType == 1) { // element
		// do attributes
		if (xml.attributes.length > 0) {
		obj["@attributes"] = {};
			for (var j = 0; j < xml.attributes.length; j++) {
				var attribute = xml.attributes.item(j);
				obj["@attributes"][attribute.nodeName] = attribute.nodeValue;
			}
		}
	} else if (xml.nodeType == 3) { // text
		obj = xml.nodeValue;
	}

	// do children
	if (xml.hasChildNodes()) {
		for(var i = 0; i < xml.childNodes.length; i++) {
			var item = xml.childNodes.item(i);
			var nodeName = item.nodeName;
			if (typeof(obj[nodeName]) == "undefined") {
				obj[nodeName] = xmlToJson(item);
			} else {
				if (typeof(obj[nodeName].length) == "undefined") {
					var old = obj[nodeName];
					obj[nodeName] = [];
					obj[nodeName].push(old);
				}
				obj[nodeName].push(xmlToJson(item));
			}
		}
	}
	
	return obj;
	
	
	/*
	// opção enviada por um segundo desenvolvedor
	// ( http://dl.dropbox.com/u/513327/xmlToJSON.html )
	var attr,
		child,
		attrs = xml.attributes,
		children = xml.childNodes,
		key = xml.nodeType,
		obj = {},
		i = -1;
		
	if (key == 1 && attrs.length) {
		obj[key = '@attributes'] = {};
		while (attr = attrs.item(++i)) {
			obj[key][attr.nodeName] = attr.nodeValue;
		}
		i = -1;
	} else if (key == 3) {
		obj = xml.nodeValue;
	}
	
	while (child = children.item(++i)) {
		key = child.nodeName;
		if (obj.hasOwnProperty(key)) {
			if (obj.toString.call(obj[key]) != '[object Array]') {
				obj[key] = [obj[key]];
			}
			obj[key].push(xmlToJson(child));
		}
		else {
			obj[key] = xmlToJson(child);
		}
	}
	return obj;
	*/
	
};





// -------------------------------------------------------------------------------------------------
// método:			JSON.stringify()
// propósito:		use este método para transformar um objeto no formato JSON em uma String
// parâmetros:		obj:Object		estrutura de Objeto JSON a ser convertida para String
// retorna:			String			contendo a estrutura de dados em formato String
// afeta:			
// dependências:	
// eventos:			
// exemplo:			JSON.stringify(data.recordset);
// comentários:		este método é útil para ser usado em conjunto com o 'BloopSuperField'; ao carregar dados do servidor, 
//					o programador pode usar esta função para transformar o objeto JSON resultante em uma String, e usá-la 
//					como parâmetro 'data-x-values' do método 'BloopSuperField.createSuperSelect()'
// -------------------------------------------------------------------------------------------------

JSON.stringify = function (obj) {
	
    var t = typeof(obj);
    
	if (t != "object" || obj === null) {
        // simple data type
        if (t == "string") obj = '"'+obj+'"';
        return String(obj);
    }
    else {
        // recurse array or object
        var n, v, json = [], arr = (obj && obj.constructor == Array);
        for (n in obj) {
            v = obj[n]; t = typeof(v);
            if (t == "string") v = '"'+v+'"';
            else if (t == "object" && v !== null) v = JSON.stringify(v);
            json.push((arr ? "" : '"' + n + '":') + String(v));
        }
        return (arr ? "[" : "{") + String(json) + (arr ? "]" : "}");
    }
	
}





// -------------------------------------------------------------------------------------------------
// método:			showCPF()
// propósito:		use este método para formatar o CPF para exibição na tela
// parâmetros:		theCPF:String			CPF no formato "xxxxxxxxxxx"
// retorna:			String					CPF no formato "xxx.xxx.xxx-xx"
// afeta:			
// dependências:	
// eventos:			
// exemplo:			showCPF("00000000000");
// comentários:		
// -------------------------------------------------------------------------------------------------

this.showCPF = function(theCPF) {
	
	var sqlCPFPattern 	= /\b[0-9]{11}\b/g;
	var CPFPattern 		= /\b[0-9]{3}.[0-9]{3}.[0-9]{3}\-[0-9]{2}\b/g;
	var newCPF			= '';
	
	if (theCPF) {
		
		if (sqlCPFPattern.test(theCPF) == true) {
			
			// verifica se é um CPF no formato "xxxxxxxxxxx"
			theCPF = theCPF.split("");
			
			newCPF = theCPF[0]+theCPF[1]+theCPF[2] + "." + theCPF[3]+theCPF[4]+theCPF[5] + "." + theCPF[6]+theCPF[7]+theCPF[8] + "-" + theCPF[9]+theCPF[10];
			
		}
		
		else if (CPFPattern.test(theCPF) == true) {
			
			// verifica se é um CPF no formato "xxx.xxx.xxx-xx"
			// já está formatado, retorna como está
			newCPF = theCPF;
		}
		
	}
	
	return newCPF;
}





// -------------------------------------------------------------------------------------------------
// método:			showCNPJ()
// propósito:		use este método para formatar o CNPJ para exibição na tela
// parâmetros:		theCNPJ:String			CNPJ no formato "xxxxxxxxxxxxxx"
// retorna:			String					CNPJ no formato "xx.xxx.xxx/xxxx-xx"
// afeta:			
// dependências:	
// eventos:			
// exemplo:			showCNPJ("00000000000000");
// comentários:		
// -------------------------------------------------------------------------------------------------

this.showCNPJ = function(theCNPJ) {
	
	var sqlCNPJPattern 	= /\b[0-9]{14}\b/g;
	var CNPJPattern 	= /\b[0-9]{2}.[0-9]{3}.[0-9]{3}\/[0-9]{4}\-[0-9]{2}\b/g;
	var newCNPJ			= '';
	
	if (theCNPJ) {
		
		if (sqlCNPJPattern.test(theCNPJ) == true) {
			
			// verifica se é um CNPJ no formato "xxxxxxxxxxxxxx"
			theCNPJ = theCNPJ.split("");
			
			newCNPJ = theCNPJ[0]+theCNPJ[1] + "." + theCNPJ[2]+theCNPJ[3]+theCNPJ[4] + "." + theCNPJ[5]+theCNPJ[6]+theCNPJ[7] + "/" + theCNPJ[8]+theCNPJ[9]+theCNPJ[10]+theCNPJ[11] + "-" + theCNPJ[12]+theCNPJ[13];
			
			// theCNPJ = newCNPJ;
		}
		
		else if (CNPJPattern.test(theCNPJ) == true) {
			
			// verifica se é um CNPJ no formato "xx.xxx.xxx/xxxx-xx"
			// já está formatado, retorna como está
			newCNPJ = theCNPJ;
		}
		
	}
	
	return newCNPJ;
}





// -------------------------------------------------------------------------------------------------
// método:			showDate()
// propósito:		use este método para formatar a data para exibição na tela
// parâmetros:		theDate:String			data no formato "YYYY-DD-MM"
//					showTimeStamp:Boolean	indica se será retornada a data com o timestamp
// retorna:			String					data no formato "DD/MM/YYYY"
// afeta:			
// dependências:	
// eventos:			
// exemplo:			showDate("2011-20-06", true);
// comentários:		é necessário ainda criar método para tratamento de TIMESTAMP, 
//					ou mesmo extrair neste método o DATE de um TIMESTAMP passado como parâmetro
// -------------------------------------------------------------------------------------------------

this.showDate = function(theDate, showTimeStamp) {
	
	var sqlDatePattern	 = /\b[0-9]{4}\-[0-9]{2}\-[0-9]{2}\b/g;
	var monthDatePattern = /\b[0-9]{4}\-[0-9]{2}\b/g;
	var timestampPattern = /\b[0-9]{4}\-[0-9]{2}\-[0-9]{2}\s[0-9]{2}\:[0-9]{2}\:[0-9]{2}\b/g;
	
	if (theDate) {
		
		if (timestampPattern.test(theDate) == true) {
			
			// verifica se é um timestamp no formato "YYYY-MM-DD HH:MM:SS"
			theDate = theDate.split("");
			newDate = theDate[8]+theDate[9] + "/" + theDate[5]+theDate[6] + "/" + theDate[0]+theDate[1]+theDate[2]+theDate[3];
			
			if (showTimeStamp && showTimeStamp == true) {
				
				newDate += " " + theDate[11]+theDate[12] +":"+ theDate[14]+theDate[15] +":"+ theDate[17]+theDate[18];
				
			}
			
			theDate = newDate;
			
		}
		
		else if (sqlDatePattern.test(theDate) == true) {
			
			// verifica se é uma data no formato "YYYY-MM-DD":
			theDate = theDate.split("-");
			theDate = theDate[2] + "/" + theDate[1] + "/" + theDate[0];
		}
		
		else if (monthDatePattern.test(theDate) == true) {
			
			// verifica se é uma data no formato "YYYY-MM":
			theDate = theDate.split("-");
			theDate = theDate[1] + "/" + theDate[0];
			
		}
		
		return theDate;
		
	} else {
		return "";
	}
}





// -------------------------------------------------------------------------------------------------
// método:			sqlDate()
// propósito:		use este método para formatar a data para o banco de dados
// parâmetros:		theDate:String		data no formato "DD/MM/YYYY"
// retorna:			String				data no formato "YYYY-DD-MM"
// afeta:			
// dependências:	
// eventos:			
// exemplo:			sqlDate("20/06/2011");
// comentários:		
// -------------------------------------------------------------------------------------------------

this.sqlDate = function(theDate) {
	
	var prbrDatePattern	 = /\b[0-9]{2}\/[0-9]{2}\/[0-9]{4}\b/g;
	var monthDatePattern = /\b[0-9]{2}\/[0-9]{4}\b/g;
	
	if (theDate) {
		
		if (prbrDatePattern.test(theDate) == true) {
			
			// verifica se é uma data no formato "DD/MM/YYYY"
			
			theDate = theDate.split("/");
			theDate = theDate[2] + "-" + theDate[1] + "-" + theDate[0];
		
		} else if (monthDatePattern.test(theDate) == true) {
			
			// verifica se é uma data no formato "MM/YYYY"
			
			theDate = theDate.split("/");
			theDate = theDate[1] + "-" + theDate[0] + "-01";
			
		}
		
		return theDate;
		
	} else {
		return "";
	}
}





// -------------------------------------------------------------------------------------------------
// Função:		formatField(field, event, format)
// Objetivo:	máscara de input do campo 
// Exemplo:		formatField(htmlElement, event, "date");
// Retorno:		
// -------------------------------------------------------------------------------------------------

var isCtrl = false;
document.onkeyup = function(e) {
	if (e.keyCode == 17) isCtrl = false;
}

document.onkeydown = function(e) {
	if (e.keyCode == 17) isCtrl = true;
}

this.formatField = function(field, event, format) {
	
	var key = event.keyCode;
	
	if (isCtrl != false) return;
	
	// não são tratadas pela função:
	// teclas delete, tab, shift, backspace
	// teclas left arrow, up arrow, right arrow, down arrow
	// teclas ctrl, alt, shift
	if (
	key!=8  && key!=9  && key!=16 && key!=46 &&
	key!=37 && key!=38 && key!=39 && key!=40 &&
	key!=16 && key!=17 && key!=18
	) {
		
		switch (format) {
			
			case "date" :
				// impede input de valores não numéricos no campo:
				// valores de key entre 48 - 57 (keyboard) ou 96 - 105 (numpad keyboard)
				if (!( (key >= 48 && key <= 57) || (key >= 96 && key <= 105) )) {
					event.preventDefault();
				}
				
				// formata input:
				if ($(field).val().length == 2 || $(field).val().length == 5) {
					$(field).val( field.val() + "/" );
				}
			break;
			
			case "month" :
				// impede input de valores não numéricos no campo:
				// valores de key entre 48 - 57 (keyboard) ou 96 - 105 (numpad keyboard)
				if (!( (key >= 48 && key <= 57) || (key >= 96 && key <= 105) )) {
					event.preventDefault();
				}
				
				// formata input:
				if ($(field).val().length == 2) {
					$(field).val( field.val() + "/" );
				}
			break;
			
			case "datemonth" :
				
				// impede input de valores não numéricos no campo:
				// valores de key entre 48 - 57 (keyboard) ou 96 - 105 (numpad keyboard)
				if (!( (key >= 48 && key <= 57) || (key >= 96 && key <= 105) )) {
					event.preventDefault();
				}
				
				// formata input:
				if ($(field).val().length == 2) {
					$(field).val( field.val() + "/" );
				}
				
				// check if the date appears to be in format DD/MM or MM/YYYY (still incomplete) until now...
				if ($(field).val().length == 5) {
					
					var dayMonthPattern  = /(0[1-9]|[12][0-9]|3[01])\/(0[1-9]|1[012])/;		// DD/MM
					var monthYearPattern = /(0[1-9]|1[012])\/(19|2[0-9])/;					// MM/YY..
					
					if (dayMonthPattern.test($(field).val())) {
						$(field).val( field.val() + "/" );
						
					} else if (monthYearPattern.test($(field).val())) {
						// nothing to do...
					}
				}
				
				// check if the date appears to be in format MM/YYYY (complete)
				if ($(field).val().length == 7) {
					
					var monthYearPattern = /(0[1-9]|1[012])\/(19|2[0-9])\d{2}/;					// MM/YYYY
					if (monthYearPattern.test($(field).val())) {
						event.preventDefault();
					}
				}
				
			break;
			
			
			/*
			case "money" :
				// impede input de valores não numéricos no campo:
				// valores de key entre 48 - 57 (keyboard) ou 96 - 105 (numpad keyboard)
				if (!( (key >= 48 && key <= 57) || (key >= 96 && key <= 105) )) {
					event.preventDefault();
				}
				
				// formata input:
				var moneyValue 		= "";
				var stringValue 	= "";
				var tempValue 		= "";
				var formattedValue  = "";
				
				moneyValue  = field.val();
				stringValue = moneyValue.replace(".","");
				stringValue = stringValue.replace(",","");
				
				// stringValue agora contém apenas os números, sem formatação
				arValue = stringValue.split("");
				
				for (i=(arValue.length - 1); i>=0; i--) {
					
					formattedValue = arValue[i] + formattedValue;
					
					if (formattedValue.length == 2) {
						formattedValue = "," + formattedValue;
					}
				}
				
				field.val(formattedValue);
				
			break;
			*/
			
			
			case "cpf" :
				// impede input de valores não numéricos no campo:
				// valores de key entre 48 - 57 (keyboard) ou 96 - 105 (numpad keyboard)
				if (!( (key >= 48 && key <= 57) || (key >= 96 && key <= 105) )) {
					event.preventDefault();
				}
				
				if ($(field).val().length >= 14) {
					event.preventDefault();
				}
				
				// formata input:
				if ($(field).val().length == 3 || $(field).val().length == 7) {
					$(field).val( field.val() + "." );
					
				} else if ($(field).val().length == 11) {
					$(field).val( field.val() + "-" );
				}
				
			break;
			
			case "cnpj" :
				// impede input de valores não numéricos no campo:
				// valores de key entre 48 - 57 (keyboard) ou 96 - 105 (numpad keyboard)
				if (!( (key >= 48 && key <= 57) || (key >= 96 && key <= 105) )) {
					event.preventDefault();
				}
				
				// formata input:
				if ($(field).val().length == 2 || $(field).val().length == 6) {
					$(field).val( field.val() + "." );
					
				} else if ($(field).val().length == 10) {
					$(field).val( field.val() + "/" );
					
				} else if ($(field).val().length == 15) {
					$(field).val( field.val() + "-" );
				}
				
			break;
			
			
			
			
			case "cpfcnpj" :
				
				// impede input de valores não numéricos no campo:
				// valores de key entre 48 - 57 (keyboard) ou 96 - 105 (numpad keyboard)
				if (!( (key >= 48 && key <= 57) || (key >= 96 && key <= 105) )) {
					event.preventDefault();
				}
				
				// formata input:
				// como o CPF tem comprimento menor, assumimos temporariamente que o número inserido é um CPF;
				// caso o usuário continue digitando, trocamos a máscara para CNPJ e reformatamos o input
				
				if ($(field).val().length == 3 || $(field).val().length == 7) {
					$(field).val( field.val() + "." );
					
				} else if ($(field).val().length == 11) {
					$(field).val( field.val() + "-" );
				}
				
				// CPF:  ^\d{3}.\d{3}.\d{3}-\d{2}$
				// 000.000.000-00
				// CNPJ: ^\d{2}.\d{3}.\d{3}/\d{4}-\d{2}$
				// 00.000.000/0000-00
				
				// verifica se o input aparentemente está no formato 000.000.000-00 (CPF com 14 caracteres) e o usuário está inserindo um 15 dígito
				// se estiver, limpamos a formatação e aplicamos a máscara de CNPJ:
			//	if ($(field).val().length == 15) {
					
					var wrongCpfPattern = /\d{3}.\d{3}.\d{3}-\d{3}/;
					
					if (wrongCpfPattern.test($(field).val())) {
						
						var i;
						var fieldValue = '';
						var value = $(field).val();
						value = value.replace('.','');
						value = value.replace('/','');
						value = value.replace('-','');
						
						value = value.split('');
						$(field).val(fieldValue);
						
						for (i=0; i<value.length; i++) {
							
							$(field).val( fieldValue + value[i] );
							
							if ($(field).val().length == 2 || $(field).val().length == 6) {
								$(field).val( field.val() + '.' );
								
							} else if ($(field).val().length == 10) {
								$(field).val( field.val() + '/' );
								
							} else if ($(field).val().length == 15) {
								$(field).val( field.val() + '-' );
							}
							
							fieldValue = $(field).val();
							
						}
						
					}
			//	}
				
			break;
			
			
			
			
			case "phone" :
				// impede input de valores não numéricos no campo:
				// valores de key entre 48 - 57 (keyboard) ou 96 - 105 (numpad keyboard)
				if ( !( (key >= 48 && key <= 57) || (key >= 96 && key <= 105) ) ) {
					// field.val( field.val().substring(0,field.val().length-1) );
					event.preventDefault();
				}
				
				// formata input:
				if ($(field).val().length == 1) {
					$(field).val( "+" + field.val() );
					
				} else if ($(field).val().length == 3) {
					$(field).val( field.val() + " (" );
					
				} else if ($(field).val().length == 7) {
					$(field).val( field.val() + ") " );
					
				} else if ($(field).val().length == 13) {
					$(field).val( field.val() + "-" );
				}
				
			break;
			
			case "cep" :
				// impede input de valores não numéricos no campo:
				// valores de key entre 48 - 57 (keyboard) ou 96 - 105 (numpad keyboard)
				if (!( (key >= 48 && key <= 57) || (key >= 96 && key <= 105) )) {
					// field.val( field.val().substring(0,field.val().length-1) );
					event.preventDefault();
				}
				
				// formata input:
				if ($(field).val().length == 5) {
					$(field).val( field.val() + "-" );
				}
				
			break;
			
		}
		
	}
	
}

this.formatMoney = function(field, event) {
	
	var key = event.keyCode;
	
	// não são tratadas pela função:
	// teclas delete, tab, shift, backspace
	// teclas left arrow, up arrow, right arrow, down arrow
	// teclas ctrl, alt, shift
	if (
	key!=8  && key!=9  && key!=16 && key!=46 &&
	key!=37 && key!=38 && key!=39 && key!=40 &&
	key!=16 && key!=17 && key!=18
	) {
		
		if (event.type == "keydown") {
			
			// impede input de valores não numéricos no campo:
			// valores de key entre 48 - 57 (keyboard) ou 96 - 105 (numpad keyboard)
			if (!( (key >= 48 && key <= 57) || (key >= 96 && key <= 105) )) {
				event.preventDefault();
			}
		
		} else if (event.type == "keyup") {
			
			// formata input:
			var moneyValue 		= "";
			var stringValue 	= "";
			var formattedValue  = "";
			var count			= 0;
			
			moneyValue  = field.val();
			stringValue = moneyValue.replace(/\./g,"");
			stringValue = stringValue.replace(/\,/g,"");
			
			stringValue = parseInt(stringValue,10);
			stringValue = stringValue.toString();
			
			// stringValue agora contém apenas os números, sem formatação
			arValue = stringValue.split("");
			
			for (i=(arValue.length-1); i>=0; i--) {
				
				formattedValue = arValue[i] + formattedValue;
				
				if (formattedValue.length == 2) {
					formattedValue = "," + formattedValue;
				}
				
				if (formattedValue.length > 3) {
					count++;
					if (count == 3) {
						count = 0;
						formattedValue = "." + formattedValue;
					}
				}
			}
			
			// corrige vírgulas e pontos no começo do valor:
			if (formattedValue.indexOf(".") == 0) { formattedValue = formattedValue.slice(1); }
			if (formattedValue.indexOf(",") == 0) { formattedValue = "0" + formattedValue; }
			
			field.val(formattedValue);
			
		}
	}
}



// -------------------------------------------------------------------------------------------------
// Função:		moneyToFloat(value)
// Objetivo:	transforma uma String no formato '12.350,25' (money) para '12350.25' (float)
// Exemplo:		moneyToFloat("12.350,25");
// Retorno:		Float
// -------------------------------------------------------------------------------------------------

this.moneyToFloat = function(value) {
	
	if (value === "") {
		value = 0;
		
	} else {
		
		value = value.replace(/\./g,'');
		value = value.replace(/,/g,'.');
		value = parseFloat(value);
		
	}
	
	return value;
}



// -------------------------------------------------------------------------------------------------
// Função:		floatToMoney(value)
// Objetivo:	transforma uma String no formato '12350.25' (float) para '12.350,25' (money)
// Exemplo:		floatToMoney(12350.25);
// Retorno:		String
// -------------------------------------------------------------------------------------------------

this.floatToMoney = function(value) {
	
	var integer = null;
	var decimal = null;
	var c = null;
	var j = null;
	
	var aux = new Array();
	
	value = value.toString();
	
	// caso haja pontos na string, separa as partes em inteiro e decimal:
	c = value.indexOf(".",0);
	
	if (c > 0) {
		integer = value.substring(0, c);
		decimal = value.substring(c+1, value.length);
	} else {
		integer = value;
	}
	
	
	// pega a parte inteiro de 3 em 3 partes
	for (j = integer.length, c = 0; j > 0; j-=3, c++) {
		aux[c]=integer.substring(j-3, j);
	}
	
	// percorre a string acrescentando os pontos
	integer = "";
	for (c = aux.length-1; c >= 0; c--) {
		integer += aux[c] + ".";
	}
	// retirando o ultimo ponto e finalizando a parte inteiro
	
	integer = integer.substring(0, integer.length-1);
	
	decimal = parseInt(decimal);
	
	if(isNaN(decimal)) {
		decimal = "00";
	} else {
		decimal = decimal.toString();
		if (decimal.length === 1) {
			decimal = decimal + "0";
		}
	}
	
	value = integer + "," + decimal.toString().substr(0,2);
	
	return value;
}





// -------------------------------------------------------------------------------------------------
// método:			generateValidPassword()
// propósito:		use este método para gerar uma senha válida para o sistema
// parâmetros:		
// retorna:			String		senha gerada aleatoriamente dentro dos grupos de caracteres válidos
// afeta:			
// dependências:	
// eventos:			
// exemplo:			generateValidPassword();
// comentários:		
// -------------------------------------------------------------------------------------------------

this.generateValidPassword = function() {
	
	var strNovaSenha;
	
	var arConsoantes;
	var arVogais;
	var arNumeros;
	var arConsoantesMaiusculas;
	var arVogaisMaiusculas;
	var arSimbolos;
	
	var i;
	var intNumber;
	
	arConsoantes			= ("b,c,d,f,g,h,j,k,l,m,n,p,q,r,s,t,v,w,x,y,z,b,c,d,f,g,h,j,k,l,m,n,p,q,r,s,t,v,w,x,y,z").split(",");
	arVogais				= ("a,e,i,o,u").split(",");
	arNumeros				= ("0,1,2,3,4,5,6,7,8,9").split(",");
	arConsoantesMaiusculas	= ("B,C,D,F,G,H,J,K,L,M,N,P,Q,R,S,T,V,W,X,Y,Z,B,C,D,F,G,H,J,K,L,M,N,P,Q,R,S,T,V,W,X,Y,Z").split(",");
	arVogaisMaiusculas		= ("A,E,I,O,U").split(",");
//	arSimbolos				= ("!,@,#,$,%,&,*,?,-").split(",");
	arSimbolos				= ("!,@,#,-").split(",");
	
	strNovaSenha = "";
	
	while (strNovaSenha.length < 10) {
		
		i=0;
		
		while (strNovaSenha.length < 2) {
			
			intNumber = Math.floor(Math.random() * arConsoantes.length);
			strNovaSenha += arConsoantes[intNumber];
			
			i++;
			if (i == 10) break;
		}
		
		while (strNovaSenha.length < 4) {
		
			intNumber = Math.floor(Math.random() * arVogais.length);
			strNovaSenha += arVogais[intNumber];
			
			i++;
			if (i == 10) break;
		}
		
		while (strNovaSenha.length < 6) {
		
			intNumber = Math.floor(Math.random() * arNumeros.length);
			strNovaSenha += arNumeros[intNumber];
			
			i++;
			if (i == 10) break;
		}
		
		while (strNovaSenha.length < 8) {
		
			intNumber = Math.floor(Math.random() * arConsoantesMaiusculas.length);
			strNovaSenha += arConsoantesMaiusculas[intNumber];
			
			i++;
			if (i == 10) break;
		}
		
		while (strNovaSenha.length < 9) {
		
			intNumber = Math.floor(Math.random() * arSimbolos.length);
			strNovaSenha += arSimbolos[intNumber];
			
			i++;
			if (i == 10) break;
		}
		
		while (strNovaSenha.length < 11) {
		
			intNumber = Math.floor(Math.random() * arVogaisMaiusculas.length);
			strNovaSenha += arVogaisMaiusculas[intNumber];
			
			i++;
			if (i == 10) break;
		}
		
	}
	
	return strNovaSenha;
	
}



// -------------------------------------------------------------------------------------------------
// método:			validatePassword()
// propósito:		use este método para checar se uma senha é válida para o sistema
// parâmetros:		strPassword:String	senha a ser checada
// retorna:			Boolean				
// afeta:			
// dependências:	
// eventos:			
// exemplo:			validatePassword("rosebud");
// comentários:		
// -------------------------------------------------------------------------------------------------

this.validatePassword = function(strPassword) {
	
	// matrizes com conjuntos válidos
	var	strCaracteres;

	// variáveis utilizadas para fazer a contagem mínima da senha
	// quanto maior a contagem mais segura a senha.
	var intPontoConsoante;
	var intPontoVogal;
	var intPontoNumero;
	var intPontoConsoantesMaiuscula;
	var intPontoVogalMaiuscula;
	var intPontoSimbolo;

	var i;
	var j;
	var intError;
	var intTotalPontos;

	var intPosicao;
	var strLetraSenha;

	var intTamanhoDaSenha = 6;			// define a quantidade mínima de caracteres da senha
	var intQuantidadeDePontos = 3;		// define a quantidade mínima de requesitos obrigatórios

	intPontoConsoante = 0;
	intPontoVogal = 0;
	intPontoNumero = 0;
	intPontoConsoantesMaiuscula = 0;
	intPontoVogalMaiuscula = 0;
	intPontoSimbolo = 0;
	intError = 0;

	// montando a lista com os caracteres válidos
	strCaracteres = "aeiouAEIOUbcdfghjklmnpqrstvwxyzBCDFGHJKLMNPQRSTVWXYZ1234567890!@#$%&*?-";

	// a senha deve ter um número mínimo de caracteres:
	if (strPassword.length < intTamanhoDaSenha) {
		
		intError = 1;
		
	} else {
		
		for (i=0;i<=strPassword.length;i++) {
			
			// pegando a letra da senha
			strLetraSenha = strPassword.substr(i,1);
			
			// recuperado a posição da letra no conjunto de caracteres
			intPosicao = strCaracteres.indexOf(strLetraSenha,0);
			
			
			// o usuário está tentando utilizar um caracter que está fora do conjunto
			if (intPosicao == -1) {
				intError = 2;
				break;
			}
			
			
			// vogais minúsculas
			if (intPosicao < 5) {
				intPontoVogal = 1;
			// vogais maiúsculas
			} else if (intPosicao < 10) {
				intPontoVogalMaiuscula = 1;
			// consoantes minúsculas
			} else if (intPosicao < 31) {
				intPontoConsoante = 1;
			// consoantes maiúsculas
			} else if (intPosicao < 52) {
				intPontoConsoantesMaiuscula = 1;
			// números
			} else if (intPosicao < 62) {
				intPontoNumero = 1;
			// símbolos
			} else {
				intPontoSimbolo = 1;
			}
			
		}
		
	}

	// se não tem erro, conta os pontos...
	if (intError == 0) {
	
		intTotalPontos = intPontoVogal + intPontoVogalMaiuscula + intPontoConsoante + intPontoConsoantesMaiuscula + intPontoNumero + intPontoSimbolo;

		if (intTotalPontos < intQuantidadeDePontos) {
			intError = 3;
		}
		
	}
	
	return intError == 0;

}






function VerifyCNPJ(strCNPJ) {
	
	// retorna os 12 primeiros caracteres
	var strCNPJ = "000000000000" + strCNPJ;
	var strCNPJ = strCNPJ.substr(-12, 12);
	
	// Calcula digito de CNPJ ...
	for(i = 1; i <= 2; i++) {
	
		// Calcula somatorio
		var intDigito = 0;
		var intLength = strCNPJ.length;

		for(j = 1; j <= intLength; j++) {
		
			if(j > 8)  {
				var intFator = j - 7;
			} else {
				var intFator = j + 1;
			}

			var intNum		= parseInt(strCNPJ.substr((intLength - j), 1));
			var intDigito	= intDigito + (intNum * intFator);
			
		}

		// Divide por 11, obtem modulo e calcula o digito ...
		intDigito = 11 - (intDigito % 11);
		
		if(intDigito > 9) {
			intDigito = 0;
		}

		// Inclui este digito na string e prossegue ...
		if(intDigito == "") {
			strCNPJ += "0";
		} else {
			intDigito = ""+intDigito+"";
			strCNPJ = strCNPJ + intDigito.substr(0,1);
			strCNPJ = ""+strCNPJ+"";
		}

	}

	// Retorna o CNPJ completo, nao formatado ...
	return strCNPJ;
	

}


function IsCNPJ(strCNPJ) {

	// retira as pontuações
	strCNPJ = strCNPJ.replace(".", "");
	strCNPJ = strCNPJ.replace(".", "");
	strCNPJ = strCNPJ.replace("-", "");
	strCNPJ = strCNPJ.replace("/", "");

	if(strCNPJ.length != 14) {
		return false;
	}

	if(VerifyCNPJ(strCNPJ.substr(0, 12)) == strCNPJ) {
		return true;
	} else {
		return false;
	}

}






function VerifyCPF(strCPF){
	// Define variaveis locais...
	
	var lngSoma    = 0;
	var intResto   = 0;
	var intDigito1 = 0;
	var intDigito2 = 0;

	strCPF = "000000000" + strCPF;
	
	strCPF = strCPF.substr(-9, 9);

	// Calcula o 1o. digito...
	lngSoma =           parseInt( strCPF.substr(0, 1) ) * 10 + parseInt( strCPF.substr(1, 1) ) * 9;
	lngSoma = lngSoma + parseInt( strCPF.substr(2, 1) ) *  8 + parseInt( strCPF.substr(3, 1) ) * 7;
	lngSoma = lngSoma + parseInt( strCPF.substr(4, 1) ) *  6 + parseInt( strCPF.substr(5, 1) ) * 5;
	lngSoma = lngSoma + parseInt( strCPF.substr(6, 1) ) *  4 + parseInt( strCPF.substr(7, 1) ) * 3;
	lngSoma = lngSoma + parseInt( strCPF.substr(8, 1) ) *  2;

	intResto = lngSoma - (parseInt(lngSoma/11) * 11);

	if(intResto < 2) {
		intDigito1 = 0;
	} else {
		intDigito1 = 11 - intResto;
	}

	// Calcula o 2o. digito...
	lngSoma =           parseInt( strCPF.substr(0, 1) ) * 11 + parseInt( strCPF.substr(1, 1) ) * 10;
	lngSoma = lngSoma + parseInt( strCPF.substr(2, 1) ) *  9 + parseInt( strCPF.substr(3, 1) ) *  8;
	lngSoma = lngSoma + parseInt( strCPF.substr(4, 1) ) *  7 + parseInt( strCPF.substr(5, 1) ) *  6;
	lngSoma = lngSoma + parseInt( strCPF.substr(6, 1) ) *  5 + parseInt( strCPF.substr(7, 1) ) *  4;
	lngSoma = lngSoma + parseInt( strCPF.substr(8, 1) ) *  3 + intDigito1 * 2;

	intResto = lngSoma - (parseInt(lngSoma/11) * 11);

	if(intResto < 2) {
		intDigito2 = 0;
	} else {
		intDigito2 = 11 - intResto;
	}
	
	intDigito1 = ""+intDigito1+"";
	intDigito2 = ""+intDigito2+"";
	
	strCPF = strCPF + intDigito1.substr(0, 1) 
	strCPF = strCPF + intDigito2.substr(0, 1);
	strCPF = ""+strCPF+"";
	
	return strCPF;

}

function IsCPF(strCPF) {

	strCPF = strCPF.replace(".", "");
	strCPF = strCPF.replace(".", "");
	strCPF = strCPF.replace("-", "");
	
	if(strCPF.length != 11) {
		return false;
	}

	if(VerifyCPF(strCPF.substr(0, 9)) == strCPF) {
		return true;
	} else {
		return false;
	}
}



/*
 * função para extender objetos
 * */
function extend(from, to){
	if (from == null || typeof from != "object") return from;
	if (from.constructor != Object && from.constructor != Array) return from;
	if (from.constructor == Date || from.constructor == RegExp || from.constructor == Function || from.constructor == String || from.constructor == Number || from.constructor == Boolean){
		return new from.constructor(from);
	}

	to = to || new from.constructor();
	
	for (var name in from){
		to[name] = typeof to[name] == "undefined" ? extend(from[name], null) : to[name];
	}
	return to;
} 


/*
 * funções para gerar GUID em javascript
 * */
function s4() { return Math.floor((1 + Math.random()) * 0x10000).toString(16).substring(1); }
function guid() { return s4() + s4() + '-' + s4() + '-' + s4() + '-' + s4() + '-' + s4() + s4() + s4(); }

