/**
 * Package Basic
 * Objetos básicos compartilhados por diversos módulos do sistema
 */

/**
 * MENU FLUTUANTE
 * Controla o comportamento de exibição de um menu flutuante, semelhante a um context menu
 */
var FloatingMenu = (function(){
	
	var allFloatingMenus = [];	// Array: mantém referência de todos os menus existentes em tela
	var animationTime = 150;
	
	var instance = function(buttonHTMLElement, menuHTMLElement){
		
		var that = this;
		var buttonHTMLElement = typeof buttonHTMLElement === 'undefined' ? null : $(buttonHTMLElement)[0];	// HTMLElement: Objeto HTML do botão
		var menuHTMLElement   = typeof menuHTMLElement   === 'undefined' ? null : $(menuHTMLElement)[0];	// HTMLElement: Objeto HTML do menu
		
		var menuId = $(menuHTMLElement).attr('id') || ('mn_'+guid());
		$(menuHTMLElement).attr('id', menuId);
		
		// adiciona o menu à referência de todos os menus, e remove da array aqueles que não existem mais em tela:
		allFloatingMenus.push({"id":menuId,"instance":this});
		for (var i=0; i<allFloatingMenus.length; i++) { if ( $('#'+allFloatingMenus[i]["id"]).length==0 ) allFloatingMenus.splice(i,1); }
		
		this.setButtonElement = function(buttonElement){
			if (typeof buttonElement === 'undefined') return false;
			buttonHTMLElement = $(buttonElement)[0] || null;
			return buttonHTMLElement!=null;
		}
		this.getButtonElement = function(){ return buttonHTMLElement; }
		
		this.setMenuElement = function(menuElement){
			if (typeof menuElement === 'undefined') return false;
			menuHTMLElement = $(menuElement)[0] || null;
			return menuHTMLElement!=null;
		}
		this.getMenuElement = function(){ return menuHTMLElement; }
		
		this.activate = function(){
			if ( this.getButtonElement() == null ) return false;
			if ( this.getMenuElement() == null ) return false;
			// adiciona evento de click no botão que exibe/oculta o menu
			$(this.getButtonElement()).unbind('click').bind('click', function(event){
				event.stopPropagation();
				event.preventDefault();
				
				if ($(that.getMenuElement()).css('display')=='none'){
					// adiciona evento de click em toda a página (fora do menu), para ocultar o menu
					$('body').bind('click.floatingmenu', function(event){
						$('body').unbind('click.floatingmenu');
						$(that.getMenuElement()).hide(animationTime);
					});
					// adiciona evento de click nos itens do menu, apenas para impedir o click de se propagar para a página
					$(that.getMenuElement()).unbind('click.floatingmenu').bind('click.floatingmenu', function(event){
						event.stopPropagation();
					});
				} else {
					// remove evento de click em toda a página (fora do menu), para ocultar o menu
					$('body').unbind('click.floatingmenu');
				}
				// oculta outros menus flutuantes
				FloatingMenu.each(function(){
					if (this!=that) $(this.getMenuElement()).hide(animationTime);
				});
				// exibe/oculta o menu
				$(that.getMenuElement()).toggle(animationTime);
			});
			return true;
		}
		
		this.activate();
	}
	instance.each = function(fn){
        for (var i=0; i<allFloatingMenus.length; i++) {
            fn.call(allFloatingMenus[i]["instance"]);
        }
    }
	return instance;
})();

/**
 * AUTENTICAÇÃO
 * Controla processos de autenticação, login e logout no sistema
 */
var Authentication = (function() {
	
	var instance = function(){
		var that = this;
		this.login = function(login, password, callbackSuccess, callbackFailure, callbackMaintenance) {
			
			$.ajax({
				type: 		"POST",
				url: 		"/shared/api/orbtal-api.php",
				contentType:"application/x-www-form-urlencoded;charset=UTF-8",
				dataType: 	"json",
				data: 		{
								"object":"AuthenticationService",
								"method":"login",
								"data":{
									"username":login,
									"password":password
								}
							},
				success:	function(data) {
								
								// applicationControl && login successful:
								if (data.applicationControl && data.applicationControl.authenticated && data.success) {
									
									if (typeof callbackSuccess !== 'undefined') callbackSuccess();
									
	//									// o usuário não concordou com os termos de uso ou é seu primeiro login:
	//									if (data.tsLastLogon == null || data.tsAgreementAcceptance == null) {
	//										that.showEULA(callbackSuccess);
	//									} else {
	//										if (typeof callbackSuccess !== 'undefined') callbackSuccess();
	//									}
									
								}
								
								// applicationControl.error && sistema em manutenção:
								else if (data.applicationControl && data.applicationControl.error && data.applicationControl.error.number && data.applicationControl.error.number==2) {
									
									if (typeof callbackMaintenance  !== 'undefined') { callbackMaintenance(); }
									else if (typeof callbackFailure !== 'undefined') { callbackFailure(); }
									
								}
								
								// applicationControl.error && login inválido:									
								else if (data.applicationControl && data.applicationControl.error && data.applicationControl.error.number && data.applicationControl.error.number==1001) {
									
									if (typeof callbackFailure !== 'undefined') callbackFailure();
								}
								
								// outro erro desconhecido até o momento:
								else {
									
									if (typeof callbackFailure !== 'undefined') callbackFailure();
									
								}
								
							},
				error:		function(data) {
								// erro com a requisição em si:
								if (typeof callbackFailure !== 'undefined') callbackFailure();
							}
			});
		}
		this.logout = function(callback) {
			
			$.ajax({
				type: 		"POST",
				url: 		"/shared/api/orbtal-api.php",
				contentType:"application/x-www-form-urlencoded;charset=UTF-8",
				dataType: 	"json",
				data: 		{
								"object":"AuthenticationService",
								"method":"logout",
								"data":{"void":null}
							},
				success:	function(data) {
								
								if (typeof callback !== 'undefined') callback();
								
							}
			});
		}
		this.showEULA = function(callback) {
			
			// TO-DO: mostrar os termos de serviço para o usuário
			if (typeof callback !== 'undefined') callback();
			
		}
		this.profileInformation = function(obj, params) {
			if (typeof $(params.img , obj) === 'undefined') return false;
			if (typeof $(params.name , obj) === 'undefined') return false;
			callOrbtal("AuthenticationService", "showProfile", {"profile":""}, true, function( data ){
				$( params.img , obj).attr({src: data.recordset.sAvatarPath});
				$( params.name , obj).html( data.recordset.sFullName );
			});
		}
	}
	return instance;
})();


/**
 * FILE UPLOAD
 * Controla processos de upload de arquivo no sistema
 */
var FileUpload = (function() {
	
	var instance = function(callbackFunction,failureFunction){
		
		var that = this;
		
		var successCallbackFunction = typeof callbackFunction === 'undefined' ? null : callbackFunction; // Function: Função chamada após o upload com sucesso
		var failureCallbackFunction = typeof failureFunction === 'undefined' ? null : failureFunction; // Function: Função chamada após um erro de upload
		var uploadInterval;
		var timeInterval     = 1500;
		var uploadActionFile = "/shared/libraries/php/upload.php";
		var httpRequest;
		
		this.createRequestObject = function() {
			var browser = navigator.appName;
			if (browser == "Microsoft Internet Explorer") {
				return new ActiveXObject("Microsoft.XMLHTTP");
			} else {
				return new XMLHttpRequest();
			}   
		}
		
		this.uploadFile = function() {
			clearInterval(uploadInterval);
			uploadInterval = setInterval(this.traceUpload, timeInterval);
		}
		
		this.traceUpload = function() {
		
			httpRequest.onreadystatechange = function(){that.handleResponse()};
			httpRequest.open("GET", uploadActionFile+"?checkFile=1");
			httpRequest.send(null); 
		}

		this.handleResponse = function() {

			// httpRequest.readyState: (in a multipart submit, this event may be triggered many times)
			// 0 Uninitialized: The initial value.
			// 1 Open: The open() method has been successfully called.
			// 2 Sent: The UA successfully completed the request, but no data has yet been received.
			// 3 Receiving: Immediately before receiving the message body (if any). All HTTP headers have been received.
			// 4 Loaded: The data transfer has been completed.
			
			if (httpRequest.readyState && httpRequest.readyState == 4) {

				var objResponse = jQuery.parseJSON(httpRequest.responseText);
				//console.log(objResponse);
				
				if (!objResponse) return false;
				if (!objResponse.applicationControl) return false;
				if (!objResponse.applicationControl.status) return false;
				
				
				// ainda está no processo de upload:
				if (objResponse.applicationControl.status == "loading") {
					// does nothing...
					return false;
				}
				
				
				// fim do processo de upload, precisamos verificar se foi com sucesso
				if (objResponse.applicationControl.status == "complete") {
					
					clearInterval(uploadInterval);
					
					if (objResponse.success == true) {
						
						if (successCallbackFunction !== null) successCallbackFunction(objResponse.recordset);
						
					} else if (!objResponse.success && objResponse.applicationControl.error) {
						
						//console.log(objResponse.applicationControl.error.number);
						//console.log(objResponse.applicationControl.error.type);
						//console.log(objResponse.applicationControl.error.message);
						if (failureCallbackFunction !== null) failureCallbackFunction(objResponse.applicationControl.error);
						
					} else {
						
						//console.log("Unknown error");
						if (failureCallbackFunction !== null) failureCallbackFunction({"number":null,"type":null,"message":"Unknown error"});
					}
					
				}
				
			}
		}
		
		httpRequest = this.createRequestObject();
		
	}
	return instance;
})();

