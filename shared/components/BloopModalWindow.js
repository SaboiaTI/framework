/**
 * BloopModalWindow.js
 * 
 * classe do component 'BloopModalWindow'
 * @author			Saboia Tecnologia da Informação <relacionamento@saboia.com.br>
 * @link			http://www.saboia.com.br
 * @version			1.0
 * @dependencies	jQuery, Login
 * 
 * @properties
 * 					modalType:String		("login", "message", "prompt", "confirmation")
 * 					modalPriority:String	("default", "success", "attention", "critical", "neutral")
 * 					modalTitle:String		("")
 * 					modalMessage:String		("")
 * 					submitLabel:String		("")
 * 					resetLabel:String		("")
 * 					fieldLabel:String		("")
 * 					modalCallBack:Function	(null, function)
 * 
 * @example 
 *                  bmw = new BloopModalWindow();
 *                  bmw.modalType     = "message";
 *                  bmw.modalPriority = "attention";
 *                  bmw.modalTitle    = "Atenção";
 *                  bmw.modalMessage  = "Atenção ao alterar os valores dos campos desta tela.";
 *                  bmw.outputModal();
 */

var BloopModalWindow = function() {
	
	var modalId = "key" + (new Date).getTime();
	var that = this;
	
	
	// default values, can be modified through object properties:
	this.resetModal = function () {
	
		this.modalContainer = document.getElementsByTagName("body");
		this.modalType 		= "message";
		this.modalPriority 	= "default";
		this.modalTitle 	= "Alert";
		this.modalMessage 	= "";
		this.submitLabel 	= "ok";
		this.resetLabel 	= "cancelar";
		this.fieldLabel 	= "value";
		this.fieldType		= "text";
		this.modalCallBack 	= null;
	}
	
	this.outputModal = function() {
		
		// prevents more than one BloopModalWindow instance from being displayed at the same time:
		// only the first one will be shown in the screen.
		if ( $(this.modalContainer).find("div.modal").length != 0 ) {
			return;
		}
		
		// clear the page from other ModalWindows from the same instance:
		$(this.modalContainer).find("div#"+modalId+"").each(function() {
			that.removeModal(0);
		});
		
		var strModal;
		var fieldLabelIsArray;
		var i;
		
		
		// modalType: ajusta comportamento e elementos da janela modal
		if		(this.modalType == "message") {
			
			strModal  = '<div id="'+modalId+'" style="position:fixed;width:100%;height:100%;top:0;left:0;z-index:101;">';
			strModal += '	<div class="modal">';
			strModal += '		<form action="">';
			strModal += '		<fieldset>';
			strModal += '			<h2>'+this.modalTitle+'</h2>';
			strModal += '			<hr />';
			strModal += '			<p>'+this.modalMessage+'</p>';
			strModal += '		</fieldset>';
			strModal += '		<fieldset class="footer">';
			strModal += '			<input type="submit" class="button light" value="'+this.submitLabel+'"></li>';
			strModal += '		</fieldset>';
			strModal += '		</form>';
			strModal += '	</div>';
			strModal += '	<div class="modal-bg"></div>';
			strModal += '</div>';
			
			$(this.modalContainer).append(strModal);
			
			if ( $(this.modalContainer).css("opacity") == 0 || $(this.modalContainer).css("visibility") == "hidden" ) {
				$(this.modalContainer).css({"opacity":1,"visibility":"visible"});
			}
			
			$(this.modalContainer).find("div#"+modalId+"").find("form").get(0).onsubmit = function(event) {
				
				event.preventDefault();
				
				if (that.modalCallBack && typeof that.modalCallBack === 'function') that.modalCallBack();
				that.removeModal(250);
			};
		}
		else if	(this.modalType == "login") {
			
			strModal  = '<div id="'+modalId+'" style="position:fixed;width:100%;height:100%;top:0;left:0;z-index:101;">';
			strModal += '	<div class="modal modal-login">';
			strModal += '		<form>';
			strModal += '		<fieldset>';
			strModal += 			'<h1>Login</h1>';
			strModal += 			'<hr>';
			strModal += 			'<p>'+this.modalMessage+'</p>';
			strModal += 			'<input type="hidden" name="object" value="AuthenticationService">';
			strModal += 			'<input type="hidden" name="method" value="login">';
			strModal += 			'<p><label for="login">login</label><input type="text" id="login" required></p>';
			strModal += 			'<p><label for="password">senha</label><input type="password" id="password" required></p>';
			strModal += 			'<p id="message"></p>';
			strModal += '		</fieldset>';
			strModal += '		<fieldset class="footer">';
			strModal += 			'<input type="submit" class="button light" value="'+this.submitLabel+'">';
			strModal += '		</fieldset>';
			strModal += '		</form>';
			strModal += '	</div>';
			strModal += '	<div class="modal-bg"></div>';
			strModal += '</div>';
			
			$(this.modalContainer).append(strModal);
			
			if ( $(this.modalContainer).css("opacity") == 0 || $(this.modalContainer).css("visibility") == "hidden" ) {
				$(this.modalContainer).css({"opacity":1,"visibility":"visible"});
			}
			
			$(this.modalContainer).find("div#"+modalId+"").find("form").get(0).onsubmit = function(event) {
				
				event.preventDefault();
				
				if (that.modalCallBack && typeof that.modalCallBack === 'function') that.modalCallBack();
			//	that.removeModal(250);
				
			}
		}
		else if (this.modalType == "prompt") {
			
			// checa se o valor de 'fieldLabel' é uma array ou uma string
			// caso for uma array, serão criados mais campos de input
			fieldLabelIsArray = (this.fieldLabel.constructor.toString().indexOf("Array") != -1);
			
			strModal  = '<div id="'+modalId+'" style="position:fixed;width:100%;height:100%;top:0;left:0;z-index:101;">';
			strModal += '	<div class="modal modal-prompt" style="position:relative;margin:100px auto;width:440px;display:block;float:none;">';
			strModal += '		<form action="">';
			strModal += '			<div class="inner">';
			strModal += '				<h2>'+this.modalTitle+'</h2>';
			strModal += '				<hr />';
			strModal += '				<p>'+this.modalMessage+'</p>';
			
			if (fieldLabelIsArray == true) {
				for (i=0; i<this.fieldLabel.length; i++) {
					strModal += '<label for="value'+i+'">'+this.fieldLabel[i]+'</label><input id="value'+i+'" type="text" required><br><br>';
				}
			} else {
				if (this.fieldType == "textarea") {
					strModal += '<label for="value1">'+this.fieldLabel+'</label><textarea id="value1" rows="4" required autofocus style="outline:none;"></textarea><br>';
				} else {
					strModal += '<label for="value1">'+this.fieldLabel+'</label><input id="value1" type="text" required autofocus><br>';
				}
			}
			
			strModal += '			</div>';
			strModal += '			<div class="footer">';
			strModal += '			<ul>';
			strModal += '				<li><input type="submit" value="'+this.submitLabel+'" class="button"></li>';
			strModal += '				<li><input type="reset" value="'+this.resetLabel+'" class="button-cancel"></li>';
			strModal += '			</ul>';
			strModal += '			</div>';
			strModal += '		</form>';
			strModal += '	</div>';
			strModal += '	<div class="modal-bg"></div>';
			strModal += '</div>';
			
			$(this.modalContainer).append(strModal);
			
			if ( $(this.modalContainer).css("opacity") == 0 || $(this.modalContainer).css("visibility") == "hidden" ) {
				$(this.modalContainer).css({"opacity":1,"visibility":"visible"});
			}
			
			// usa eventos nativos do html para fazer a captura dos eventos de 
			// submit e reset <input type='submit'> e <input type='reset'>
			
			$(this.modalContainer).find("div#"+modalId+"").find("form").get(0).onsubmit = function(event) {
				
				event.preventDefault();
				
				if (that.modalCallBack && typeof that.modalCallBack === 'function') that.modalCallBack();
				that.removeModal(250);
			}
			
			$(this.modalContainer).find("div#"+modalId+"").find("form").get(0).onreset = function(event) {
				that.removeModal(250);
			}
			
		}
		else if (this.modalType == "confirmation") {
			
			// este bloco está mais correto que os anteriores, usando eventos nativos do html
			// para fazer a captura dos eventos de submit e reset, além de usar os elementos  
			// <input type='submit'> e <input type='reset'>
			
			strModal  = '<div id="'+modalId+'" style="position:fixed;width:100%;height:100%;top:0;left:0;z-index:101;">';
			strModal += '	<div class="modal modal-confirm" style="position:relative;margin:100px auto;width:440px;display:block;float:none;">';
			strModal += '		<form action="">';
			strModal += '			<div class="inner">';
			strModal += '				<h2>'+this.modalTitle+'</h2>';
			strModal += '				<hr />';
			strModal += '				<p>'+this.modalMessage+'</p>';
			strModal += '			</div>';
			strModal += '			<div class="footer">';
			strModal += '			<ul>';
			strModal += '				<li><input type="submit" value="'+this.submitLabel+'" class="button"></li>';
			strModal += '				<li><input type="reset" value="'+this.resetLabel+'" class="button-cancel"></li>';
			strModal += '			</ul>';
			strModal += '			</div>';
			strModal += '		</form>';
			strModal += '	</div>';
			strModal += '	<div class="modal-bg"></div>';
			strModal += '</div>';
			
			$(this.modalContainer).append(strModal);
			
			if ( $(this.modalContainer).css("opacity") == 0 || $(this.modalContainer).css("visibility") == "hidden" ) {
				$(this.modalContainer).css({"opacity":1,"visibility":"visible"});
			}
			
			// este bloco está mais correto que os anteriores, usando eventos nativos do html
			// para fazer a captura dos eventos de submit e reset, além de usar os elementos  
			// <input type='submit'> e <input type='reset'>
			
			$(this.modalContainer).find("div#"+modalId+"").find("form").get(0).onsubmit = function(event) {
				event.preventDefault();
				
				if (that.modalCallBack && typeof that.modalCallBack === 'function') that.modalCallBack();
				that.removeModal(250);
			}
			
			$(this.modalContainer).find("div#"+modalId+"").find("form").get(0).onreset = function(event) {
				that.removeModal(250);
			}
			
		}
		
		
		
		
		// modalPriority: ajusta cores e visual da janela modal
		if 		(this.modalPriority == "default") {
			$(this.modalContainer).find("div.modal").addClass("modal-default");
			$(this.modalContainer).find("div.modal-bg").addClass("modal-bg-medium");
		}
		else if (this.modalPriority == "success") {
			$(this.modalContainer).find("div.modal").addClass("modal-success");
			$(this.modalContainer).find("div.modal-bg").addClass("modal-bg-light");
		}
		else if (this.modalPriority == "attention") {
			$(this.modalContainer).find("div.modal").addClass("modal-attention");
			$(this.modalContainer).find("div.modal-bg").addClass("modal-bg-dark");
		}
		else if (this.modalPriority == "critical") {
			$(this.modalContainer).find("div.modal").addClass("modal-critical");
			$(this.modalContainer).find("div.modal-bg").addClass("modal-bg-dark");
		}
		else if (this.modalPriority == "neutral") {
			$(this.modalContainer).find("div.modal").addClass("modal-neutral");
			$(this.modalContainer).find("div.modal-bg").addClass("modal-bg-medium");
		}
		
		$(window).resize();
	
	}
	
	this.removeModal = function(duration) {
		
		if (!duration || duration == 0) {
			$("div#"+modalId+"").remove();
		} else {		
			$("div#"+modalId+"").fadeOut(duration, function() {
				$(this).remove();
			});
		}
	}
	
	// initializing Modal:
	this.resetModal();
}