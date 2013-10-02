/**
 * Package List
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

var Form = (function(){

	var instance = function(id, context){
		var that 		= this ;
		var object 		= context+'Service'; // String: Nome do objeto parâmetro para chamar a API orbtal
		this.id		 	= id;				 // Int:id do elemento a ser carregado
		this.recordset 	= {};				 // Object: dados da lista, em formato JSON
//		this.paramters	= {}				 // Object: lista de parametros utilizado no formulário, em formato JSON
		
		this.getHTMLElement = function(){ return htmlElement; }
		
		
		// função load do formulario onde carrega os dados
		this.load = function(callback) {
			var params = {};
			params.id = this.id;
			callOrbtal(object, 'load', params, true, function(data){
				that.recordset = data.recordset[0];
				var evt = document.createEvent("Event");
				evt.initEvent("onFormLoaded",true,true);
				document.dispatchEvent(evt);
				if (callback && typeof callback === 'function') callback(data);
			});
			return true;
		}
	}
	
	return instance;
})();


