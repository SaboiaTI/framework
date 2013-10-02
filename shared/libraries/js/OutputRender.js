/**
 * OutputRender.js
 * Namespace para ser utilizado em tratamento de template para ser escrito em javascript
 * @version 		1.0
 * @dependencies	jQuery
*/

var OutputRender = function(){
	var instance = this ;
	
	var output = "" ;
	
	this.queue = 0;
	
	instance.template = {};
	
	instance.load = function( url , type , callback ){
		$.ajax({ url: url , dataType: type , cache: false , success: function(data){ 
			instance.builder( data ) ; 
			if (callback && typeof callback === 'function') callback(); 
		}});
	}
	
	instance.mapposition = function( d ){
		// objeto responsavel por demarcar onde esta o codigo que sera parseado
		var _mapposition = { 
			_start		: ( d ).search( /\<!--(\s+)?{{/ )	// posição inicial da tag de abertura com a chave a ser parseado
			, _end		: ( d ).search( /--\>/ ) + 3		// posicao final da tag de abertura com a chave a ser parseado
			, _close	: -1								// posição inicial da tag de fechamento com a chave a ser parseado
			, key 		: ""								// chave que sera criada na matriz de template
			, value 	: "" 								// valor que sera criada na matriz de templas
			, key_clean	: "" 								// valor que sera criada na matriz de templas
		} ;
		
		// definir a chave retirando o padrão de marcação de cometário
		_mapposition.key = ( d ).substring( _mapposition._start , _mapposition._end ).replace(/\<!--(\s+)?{{(\s+)?(.+?)(\s+)?--\>/,"$3" ) ;
		
		//limpeza da chave do objeto para atribuir somente uma chave limpa na matriz sem espaços,' ou "	
		_mapposition.key_clean = String(_mapposition.key).replace(/"|'|\s{2,99}/igm,"") ;		
		
		// procura posção da tag de fechamento
		_mapposition._close = ( d ).search( eval( '/\\<!--([\\s|"|\\\']+)?}}([\\s|"\\\']+)?' + _mapposition.key + '([\\s|"|\\\']+)?--\\>/' ) ) ;
		
		return _mapposition ;
	}
	
	instance.builder = function( d ){
		/** 
		 * tratamento do parametro passado para retirar quebras de linhas
		 * e espaços para poder facilitar o parseamento do texto e atribuir
		 * esse resultado para a variavel _d que é a que sera tratada */
		var _d = String( d ).replace(/\n+|\r+|\s{2,99}/gim," ") ;
		while( ( _d ).search( /\<!--(\s+)?{{/ ) > -1 ){
			this.queue++;
			// objeto responsavel por demarcar onde esta o codigo que sera parseado
			var container = instance.mapposition(_d);
					
			// caso encontre parsea caso não gera log de erro
			if( container._close > - 1 ){
				container.value = ( _d ).substring( container._end , container._close ) ;
				v = String( container.value ).replace( eval( '/\\<!--([\\s|"|\\\']+)?..([\\s|"|\\\']+)?' + container.key + '([\\s|"|\\\']+)?--\\>/gim' ) , "" ) ;
				
				instance.template[ container.key_clean ] = v ;
				
				_d = ( _d ).replace( ( _d ).substring( container._start , container._close ) , "{{" +  container.key_clean + "}}" ).replace( eval( '/\\<!--([\\s|"|\\\']+)?..([\\s|"|\\\']+)?' + container.key + '([\\s|"|\\\']+)?--\\>/gim' ) , "" ) ;
				
				//verifica existencia de padrão de comentario interno
				v1 = extend( v ) ;
				while( ( v1 ).search( /\<!--(\s+)?{{/ ) > -1 ){
					var container1 = instance.mapposition( v1 );
					container1.value = ( v1 ).substring( container1._end , container1._close ) ;
					v1 = (v1).replace( String( container1.value ) , "{{" +  container1.key_clean + "}}" ).replace( eval( '/\\<!--([\\s|"|\\\']+)?..([\\s|"|\\\']+)?' + container1.key + '([\\s|"|\\\']+)?--\\>/gim' ) , "" ) ;
					instance.template[ container.key_clean ] = v1 ;
				}
				
				//recursividade para o resto do template
				if( ( v ).search( /\<!--(\s+)?{{/ ) > -1 ){ 
					instance.builder( v ) ; 
				}
				else{
					this.queue--;
				}		
			}
			else{
				var bmw = new BloopModalWindow();
				bmw.resetModal();
				bmw.modalType 		= "message";
				bmw.modalPriority 	= "critical";
				bmw.modalTitle 		= "Erro";
				bmw.submitLabel 	= "ok";
				bmw.modalMessage 	= "Ocorreu um problema no processamento do TEMPLATE na chave " + container.key + "<br><br>";
				bmw.outputModal();
				//console.log("ERRO de sintaxe na chave " + container.key );
				_d = "" ;
			}
		}
		
		if( this.queue == 0 ){
			var evt = document.createEvent("Event");
			evt.initEvent("onTemplateLoaded",true,true);
			document.dispatchEvent(evt);
		}
		
	}
	
	instance.renderHTML = function( template_key, params ){		
		if( instance.template[template_key] == undefined ) return "" ;
		tmp = instance.template[template_key] ;
		$.each( params , function( i , o ){
			tmp = tmp.replace(new RegExp( "{{"+i+"}}" , "gi") , o );
		});
		tmp = tmp.replace(/{{.+?}}/gim, '' );
		return tmp;
	}
} ;
 
