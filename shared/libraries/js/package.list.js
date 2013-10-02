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

function Item(id, data, context, itemElement) {
	// PRIVATE PROPERTIES
	var that = this;
	var htmlElement = typeof itemElement === 'undefined' ? null : $(itemElement)[0]; // HTMLElement: Objeto HTML deste item
	
	
	// PUBLIC PROPERTIES
	this.context	= context ;
	this.id         = typeof id   === 'undefined' ? null : parseInt(id);
	this.recordset  = typeof data === 'undefined' ? {}   : data;  // Object: 	dados do item, em formato JSON
	
	this.tagList	= new Array();	// Array: 	tags do item, instâncias da classe Tag
	//this.star		= false;		// Boolean:	indica se o item está marcado com estrela (favorito)
	this.selected	= false;		// Boolean:	indica se o item está selecionado
	this.open		= false;		// Boolean:	indica se o item está sendo exibido (aberto na interface)
	
	// grava todas as propriedades no objeto, recebidas como o parâmetro data
	for (key in this.recordset) { this[key] = this.recordset[key]; }
	
	// PRIVILEGED METHODS (CAN ACCESS PRIVATE METHODS AND PROPETIES)
	this.setHTMLElement = function(itemElement){
		htmlElement = $(itemElement)[0];
		return true;
	}

	this.getHTMLElement = function(){ return htmlElement; }
	
}
// Item PUBLIC METHODS
Item.prototype.toggleStar = function( callback ){ return this.changeStar( this.fStar ? false : true , callback ) ; }
Item.prototype.changeStar = function(flagStar, callback){
	
	var that = this;
	var params = {};
	var action;
	
	params.id = this.id;
	action = flagStar ? 'addStar' : 'removeStar';
	
	
	callOrbtal( this.context + 'Service', action, params, true , function(data){
		if( data.success == true ){
			that.fStar = action == 'addStar' ? true : false ;
		
			var evt = document.createEvent("Event");
			evt.initEvent("onItemUpdated",true,true);
			document.dispatchEvent(evt);

			if (callback && typeof callback === 'function') callback(data);
		}
	});
	
}
Item.prototype.addStar = function(callback){
	return this.changeStar(true, callback);
}
Item.prototype.removeStar = function(callback){
	return this.changeStar(false,callback);
}
Item.prototype.addTag = function(idTag, callback){
	
	var params = {};
	params.id = this.id;
	params.idTag = idTag;
	
	callOrbtal(this.context + 'Service', 'addTag', params, true, function(data){
		
		var evt = document.createEvent("Event");
		evt.initEvent("onItemUpdated",true,true);
		document.dispatchEvent(evt);
		
		if (callback && typeof callback === 'function') callback(data);
	});
}
Item.prototype.removeTag = function(idTag, callback){
	
//	var that = this;
	var params = {};
	params.id = this.id;
	params.idTag = idTag;
	
	callOrbtal(this.context + 'Service', 'removeTag', params, true, function(data){
		
	//	for (var i=0; i<that.tagList.length; i++) {
	//		if ( that.tagList[i]["id"] == idTag ) 
	//			that.tagList.splice(i,1);
	//	}
		
		var evt = document.createEvent("Event");
		evt.initEvent("onItemUpdated",true,true);
		document.dispatchEvent(evt);
		
		if (callback && typeof callback === 'function') callback(data);
	});
}
Item.prototype.clearTags = function(callback){
	
	var params = {};
	params.id = this.id;
	
	callOrbtal(this.context + 'Service', 'clearTags', params, true, function(data){
		
		var evt = document.createEvent("Event");
		evt.initEvent("onItemUpdated",true,true);
		document.dispatchEvent(evt);
		
		if (callback && typeof callback === 'function') callback(data);
	});
}

var List = (function(){

	var instance = function(listContainer, context){
		
		var that 				= this ;
		var listContainer 		= typeof listContainer === 'undefined' ? null : listContainer;
		
		var htmlElement  		= $(listContainer)[0]; // HTMLElement: Objeto HTML desta lista
		var object 		  		= context+'Service'; // String: Nome do objeto parâmetro para chamar a API orbtal
		
		var userCanRead			= false;		// Boolean: indica se o usuário pode ler a lista
		var userCanCreate		= false;		// Boolean: indica se o usuário pode criar um item na lista
		var userCanUpdate		= false;		// Boolean: indica se o usuário pode alterar um item na lista
		var userCanDelete		= false;		// Boolean: indica se o usuário pode deletar um item na lista
		var userCanReadReport	= false;		// Boolean: indica se o usuário pode gerar relatórios a partir da lista
		
		this.recordset 			= {};			// Object: 	dados da lista, em formato JSON
		this.pagination 		= {};			// Object: 	dados de paginação, em formato JSON
		this.items	 			= new Array();	// Array: 	ítens da lista, instâncias da classe Item
		
		this.tagFilters			= new Array();	// Array: 	com os ids das tags selecionadas para uso como filtro da lista
		this.statusFilters		= new Array();	// Array: 	com os ids dos status selecionadas para uso como filtro da lista
		this.priorityFilters	= new Array();	// Array: 	com os ids das prioridades selecionadas para uso como filtro da lista
		this.saleStageFilters	= new Array();	// Array: 	com os ids dos ciclos de venda selecionadas para uso como filtro da lista
		this.typeFilters		= new Array();	// Array: 	com os ids dos status selecionadas para uso como filtro da lista
		this.starFilter			= false;		// Boolean: define se a estrela está selecionada para uso como filtro da lista
		this.searchTerms		= "";			// String:	com os textos para uso como filtro da lista
		this.searchCriteria		= {};  			// Object:	com os valores de campos específicos para uso como filtro da lista
		
		this.openedRecord		= null;			// Int: 	id do Item selecionado (Painel aberto na interface)
		this.rowCount			= 25;			// Int: 	quantidade de registros por página, usada para limitar o recordset
		this.currentPage		= 1;			// Int:		número da página atual
		
		this.selectedItems		= new Array();	// Array: 	com os ids dos registros selecionados com checkbox (via interface)
	
	//	this.useSelection		= false;		// Boolean: ações serão aplicadas apenas na Array "selectedItems"
		
		
		this.getHTMLElement = function(){ return htmlElement; }
		
		this.load = function(moveToPage, callback) {
			
			if (typeof moveToPage === 'undefined') moveToPage = this.currentPage;
			
			var params = {};
			
			params.tagFilters       = this.tagFilters;
			params.statusFilters    = this.statusFilters;
			params.priorityFilters  = this.priorityFilters;
			params.saleStageFilters = this.saleStageFilters;
			params.typeFilters      = this.typeFilters;
			params.starFilter       = this.starFilter;
			params.searchTerms      = this.searchTerms;
			params.searchCriteria   = this.searchCriteria;
			params.moveTo           = moveToPage;
			params.rowCount         = this.rowCount;
			// params.expiredFilter  = 0;
			
			callOrbtal(object, 'loadList', params, true, function(data){
				
				that.currentPage = parseInt(data.pagination.currentPage,10);
				that.recordset   = data.recordset;
				that.pagination  = data.pagination;
				that.items  	 = new Array();
				var idItems  	 = new Array();
				
				for (r in data.recordset) {
					var item = new Item(data.recordset[r].id, data.recordset[r] , context );
						that.items.push(item);
						idItems.push(data.recordset[r].id);
				}
				
				// garante que a array selectedItems não tenha mais elementos que saíram da lista em função de algum filtro ou mudança de página
				for (si in that.selectedItems) {
					var selectedItem = that.selectedItems[si];
					if (idItems.indexOf(selectedItem)==-1) {
						that.selectedItems.splice(si,1);
					}
				}
				
				var evt = document.createEvent("Event");
				evt.initEvent("onListLoaded",true,true);
				document.dispatchEvent(evt);
				
				if (callback && typeof callback === 'function') callback(data);
			});
			//return true;
		}
		
		this.navigate = {
			firstPage : function (callback) { return that.load( 1 , callback); }
			, nextPage : function (callback) { return that.load(that.currentPage + 1 , callback); }
			, previousPage : function (callback) { return that.load(that.currentPage - 1 , callback); }
			, lastPage : function (callback) { return that.load(that.pagination.totalPages , callback); }
		}
		
		this.getItem = function(idItem) {
			
			if (typeof idItem === 'undefined') return null;
			
			for (i in this.items) {
				if (this.items[i].id == idItem) {
					return this.items[i];
				}
			}
			return null;
		}
		
		this.selectItem = function(idItem){
			
			if (typeof idItem === 'undefined') return false;
			
			var index = this.selectedItems.indexOf(idItem);
			if (index != -1) return true;
			
			this.selectedItems.push(idItem);
			
			return true;
		}
		
		this.deselectItem = function(idItem){
			
			if (typeof idItem === 'undefined') return false;
			
			var index = this.selectedItems.indexOf(idItem);
			if (index === -1) return true;
			
			this.selectedItems.splice(index,1);
			
			return true;
		}
		
	}
	
	return instance;
})();





function Panel(id, panelElement) {
	
	// PRIVATE PROPERTIES
	var that = this;
	var panelHTMLElement = typeof panelElement === 'undefined' ? null : $(panelElement)[0]; // HTMLElement: Objeto HTML deste item
	var panelMenuHTMLElement = panelHTMLElement==null ? null : $(panelHTMLElement).find('nav.panel-menu')[0]; // HTMLElement: Objeto HTML do menu de subPanels
	var subPanelContainerHTMLElement = panelHTMLElement==null ? null : $(panelHTMLElement).find('div.subpanel-container')[0]; // HTMLElement: Objeto HTML do container de subPanels;
	
	
	// PUBLIC PROPERTIES
	this.id = typeof id === 'undefined' ? null : parseInt(id); // Int: na verdade, o id do Item que gerou este Panel
	
	
	// PRIVILEGED METHODS (CAN ACCESS PRIVATE METHODS AND PROPETIES)
	this.setPanelElement = function(panelElement){
		if (typeof panelElement === 'undefined') return false;
		panelHTMLElement = $(panelElement)[0] || null;
		return panelHTMLElement!=null;
	}
	this.getPanelElement = function(){ return panelHTMLElement; }
	
	this.setPanelMenuElement = function(panelMenuElement){
		if (typeof panelMenuElement === 'undefined') return false;
		panelMenuHTMLElement = $(panelMenuElement)[0] || null;
		return panelMenuHTMLElement!=null;
	}
	this.getPanelMenuElement = function(){ return panelMenuHTMLElement; }
	
	this.setSubPanelContainerElement = function(subPanelContainerElement){
		if (typeof subPanelContainerElement === 'undefined') return false;
		subPanelContainerHTMLElement = $(subPanelContainerElement)[0] || null;
		return subPanelContainerHTMLElement!=null;
	}
	this.getSubPanelContainerElement = function(){ return subPanelContainerHTMLElement; }
	
	this.clearMenu();
	this.clearSubPanel();
}
// Panel PUBLIC METHODS
Panel.prototype.addMenuItem = function(title, idSubPanel){
	
	if (typeof title === 'undefined') return false;
	
	var that = this;
	var itemHTMLElement = '<li class="panel-menu-item"><a href="'+idSubPanel+'">'+title+'</a></li>';
	itemHTMLElement = $(itemHTMLElement);
	
	// ação de click para exibir o Panel correspondente
	if (typeof idSubPanel !== 'undefined') {
		
		itemHTMLElement.find('a').unbind('click').bind('click', function(event){
			event.stopPropagation();
			event.preventDefault();
			
			$(that.getPanelMenuElement()).find('li.active').removeClass('active');
			itemHTMLElement.addClass('active');
			
			$(that.getSubPanelContainerElement()).children().stop(true,true).fadeOut(150);
			$(that.getSubPanelContainerElement()).children('#'+$(event.target).attr('href')).stop(true,true).delay(150).fadeIn(150);
		});
	}
	
	// garante que exista um elemento 'ul' no panelMenuElement
	if ($(this.getPanelMenuElement()).children('ul').length == 0){
		$(this.getPanelMenuElement()).append('<ul class="panel-menu-list"></ul>');
	}
	
	$(this.getPanelMenuElement()).show();
	$(this.getPanelMenuElement()).children('ul').last().append(itemHTMLElement);
	
	return true;
}
Panel.prototype.clearMenu = function(){
	$(this.getPanelMenuElement()).empty();
	$(this.getPanelMenuElement()).hide();
	return true;
}
Panel.prototype.addSubPanel = function(title, htmlContent, focus){
	
	if (typeof title === 'undefined') return false;			// título exibido no item de menu
	if (typeof htmlContent === 'undefined') return false;	// markup HTML do Panel
	if (typeof focus === 'undefined') focus=false;			// exibir automaticamente o Panel após ser adicionado
	
	var idSubPanel = 'sp_'+guid();
	
	this.addMenuItem(title, idSubPanel);
	
	htmlContent = $('<div>'+htmlContent+'</div>').children(); // isso é pra corrigir bugs de jQuery...
	htmlContent.attr('id', idSubPanel);
	htmlContent.css({'display':'none'});
	
	$(this.getSubPanelContainerElement()).append(htmlContent);
	
	if (focus) $(this.getPanelMenuElement()).find('a[href="'+idSubPanel+'"]').click();
	
	return idSubPanel;
}
Panel.prototype.clearSubPanel = function(){
	$(this.getSubPanelContainerElement()).empty();
	return true;
}
	



function Panel_old(panelContainer, context, data) {
	
	// PRIVATE PROPERTIES
	var that = this;
	
	var panelContainer   = typeof panelContainer === 'undefined' ? null : panelContainer;
	this.recordset 	     = typeof data === 'undefined' ? null : data; 	// Object: dados do objeto mostrado no panel, em formato JSON
	var object 		     = context+'Service'; 							// String: Nome do objeto parâmetro para chamar a API orbtal
	
	
	
	var panelHTMLElement = $(panelContainer)[0];				// HTMLElement: Objeto HTML deste panel
	var panelMenu        = $(panelContainer).find('nav')[0]; 	// HTMLElement: Objeto HTML do menu deste panel, com botões de acesso aos sub-painéis
	var panelContent     = null; 								// HTMLElement: Objeto HTML do conteúdo deste panel
	var panelFooter      = null; 								// HTMLElement: Objeto HTML do rodapé deste panel, com informações de status
	
	
	
	
	// PRIVATE METHODS
	/**
	 * Cria o Panel, e o escreve o HTML markup do panel no elemento panelContainer, passado como parâmetro no construtor
	 * @returns boolean	true se o Panel foi criado corretamente
	 */
	var _createPanel = function(){
		
		panelContainer.append(_buildHTMLPanel());
		
		panelHTMLElement = panelContainer.children('div.panel.container').last()[0];
		panelHeader  = $(panelHTMLElement).find('header.panel-tools')[0];
		panelMenu    = $(panelHTMLElement).find('nav.panel-menu')[0];
		panelContent = $(panelHTMLElement).find('div.panel-content.container')[0];
		panelFooter  = $(panelHTMLElement).find('footer.panel-status')[0];
		
		// animação de exibição do Panel, para evidenciar ao usuário que a interface sofreu alteração
		$(panelHTMLElement).stop(true,true).fadeTo(0,0).fadeTo(250,1);
		
		// por padrão, um Panel não possui menu de subPanels.
		// ao chamar a função addMenuItem, se necessário, o panelMenu é exibido automaticamente
		$(panelMenu).hide();
		
		_resizePanels();
		
		return true;
	}
	
	/**
	 * Monta o HTML markup do elemento básico do Panel
	 * @returns string	o HTML Markup do Panel, sem conteúdo
	 */
	var _buildHTMLPanel = function(){
		var str  = '<div class="panel container">';
			str += 	'<div class="panel-container container">';
			str += 		'<header class="panel-tools"></header>';
			str += 		'<nav class="panel-menu"></nav>';
			str += 		'<div class="panel-content container"></div>';
			str += 		'<footer class="panel-status"></footer>';
			str += 	'</div>';
			str += '</div>';
		
		return str;
	}
	
	/**
	 * Redimensiona os Panels, distribuindo a largura do panelContainer entre os Panels
	 * @returns boolean	true
	 */
	var _resizePanels = function(){
		var len = panelContainer.find('div.panel.container').length;
		panelContainer.find('div.panel.container').each(function(index){
			$(this).css({'width':Math.floor(100/len)+'%'});
		});
		return true;
	}
	
	
	// PRIVILEGED METHODS (CAN ACCESS PRIVATE METHODS AND PROPETIES)
	/**
	 * Define qual é o HTMLElement para este objeto Panel
	 * @returns boolean	true
	 */
	this.setHTMLElement = function(panelElement){
		panelHTMLElement = $(panelElement)[0];
		return true;
	}

	/**
	 * Retorna o HTMLElement para este objeto Panel
	 * @returns HTMLElement	o elemento HTML deste objeto Panel
	 */
	this.getHTMLElement = function(){
		return panelHTMLElement;
	}
	
	this.getMenu    = function(){ return panelMenu;    }
	this.getContent = function(){ return panelContent; }
	this.getFooter  = function(){ return panelFooter;  }
	
	
	/**
	 * Manipula elementos do panelHeader, 
	 * usado para incluir botões, separadores, ícones, etc
	 * @params string htmlElement	HTMLMarkup do elemento a ser incluso no panelHeader
	 * @params function action		Função para ser executada no evento click do elemento
	 */
	this.addHeaderItem = function(htmlElement, action){
		
		if (typeof htmlElement === 'undefined') return false;
		
		var htmlElement = $(htmlElement);
		
		if (typeof action === 'function' ) htmlElement.bind('click', action);
		
		liElement = '<li></li>';
		liElement = $(liElement);
		liElement.append(htmlElement);
		
		if (
			$(panelHeader).children('ul').length == 0 
		|| ($(panelHeader).children('ul').last()[0] != $(panelHeader).children().last()[0])
		){
			$(panelHeader).append('<ul></ul>');
		}
		
		$(panelHeader).children('ul').last().append(liElement);
		
		return true;
	}
	this.addHeaderSeparator = function(){
		$(panelHeader).append('<hr>');
		return true;
	}
	this.clearHeader = function(){
		$(panelHeader).empty();
		return true;
	}
	
	/**
	 * Manipula elementos do panelMenu, 
	 * usado para incluir ítens no menu, para acessar subPanels
	 */
	this.addMenuItem = function(title, idSubPanel){
		
		if (typeof title === 'undefined') return false;
		
		var htmlElement = '<li class="panel-menu-item"><a href="'+idSubPanel+'">'+title+'</a></li>';
		htmlElement = $(htmlElement);
		
		// ação de click para exibir o Panel correspondente
		if (typeof idSubPanel !== 'undefined') {
			
			htmlElement.bind('click', function(){
				
				$(panelMenu).children('ul').children('li').removeClass('active');
				htmlElement.addClass('active');
				
				// var scrollDistance = Math.round($(panelContent).children('section.sub-panel#'+idSubPanel)[0].offsetLeft);
				// $(panelContent).animate({scrollLeft:scrollDistance}, 100);
			});
			
		}
		
		// garante que exista um elemento 'ul'
		if ($(panelMenu).children('ul').length == 0){
			$(panelMenu).append('<ul></ul>');
		}
		
		$(panelMenu).show();
		$(panelMenu).children('ul').last().append(htmlElement);
		
		return true;
	}
	this.clearMenu = function(){
		$(panelMenu).empty();
		$(panelMenu).hide();
		return true;
	}
	
	/**
	 * Manipula elementos do panelContent, 
	 * usado para incluir conteúdo no panelContent, não como subPanels
	 */
	this.addContent = function(htmlElement){
		$(panelContent).append(htmlElement);
		return true;
	}
	this.clearContent = function(){
		$(panelContent).empty();
		return true;
	}
	
	/**
	 * Manipula elementos do panelHTMLElement, 
	 * usado para incluir subPanels na área de conteúdo
	 * também criando ítens no panelMenu para acessar o subPanel
	 */
	this.addContentPanel = function(title, htmlContent){
		
		if (typeof title === 'undefined') return false;
		if (typeof htmlContent === 'undefined') htmlContent = '';
		
		var idSubPanel = 'subpanel_'+Math.floor(Math.random()*1001)+1;
		
		that.addMenuItem(title, idSubPanel);
		
	//	var str = $.parseHTML(htmlContent);
	//	var str = $($("'"+htmlContent+"'")[0]);//.attr('id', idSubPanel);
		
		$(panelHTMLElement).append(htmlContent);
		$(htmlContent).attr('id', idSubPanel);
		return true;
	}
	
	/**
	 * Fecha o Panel e remove o elemento HTML da interface
	 */
	this.close = function(){
		$(panelHTMLElement).remove();
		_resizePanels();
		return true;
	}
	
	
	_createPanel();
	
}
// Panel PUBLIC METHODS
// nothing...







function SimpleList(listContainer, context) {
	
	if (typeof listContainer === 'undefined') return null;
	if (typeof context === 'undefined') return null;
	
	// PRIVATE PROPERTIES
	var that = this;
	
	// PUBLIC PROPERTIES
	this.htmlElement = $(listContainer)[0];	// HTMLElement: Objeto HTML desta lista de tags
	this.context = context;					// String: tipo de tag buscada, por exemplo 'Channel', para 'ChannelTag'
	this.recordset = {};					// Object: 	dados das tags, em formato JSON
	
	
	// PRIVILEGED METHODS (CAN ACCESS PRIVATE METHODS AND PROPETIES)
	
	
}
// SimpleList PUBLIC METHODS
SimpleList.prototype.load = function(callback) {
	
	var that = this;
	var params = {};
	params.context = this.context;
	
	getData('MyUserAccountService', 'loadTagList', params, function(data){
		
		that.recordset = data.recordset;
		
		if (callback && typeof callback === 'function') callback(data);
		
		var evt = document.createEvent("Event");
		evt.initEvent("onTagListLoaded",true,true);
		document.dispatchEvent(evt);
		
	});
	return true;
}
SimpleList.prototype.writeList = function(listHTMLMarkup) {
	$(this.htmlElement).empty().append(listHTMLMarkup);
	return true;
}
SimpleList.prototype.setHTMLElement = function(element){
	this.htmlElement = $(element)[0];
	return true;
}
SimpleList.prototype.getHTMLElement = function(){
	return this.htmlElement;
}

TagList.inheritsFrom(SimpleList);
function TagList(listContainer){
	this.parent.constructor.call(this, listContainer, 'Channel');
}

var Item__OLDVERSION = (function(){
	
	var instance = function(id, data, itemElement){
		
		// PRIVATE PROPERTIES
		var that        = this;
		var htmlElement = typeof itemElement === 'undefined' ? null : $(itemElement)[0]; // HTMLElement: Objeto HTML deste item
		
		
		// PUBLIC PROPERTIES
		this.id         = typeof id   === 'undefined' ? null : parseInt(id);
		this.recordset  = typeof data === 'undefined' ? {}   : data;  // Object: 	dados do item, em formato JSON
		
		this.tagList	= new Array();	// Array: 	tags do item, instâncias da classe Tag
		this.star		= false;		// Boolean:	indica se o item está marcado com estrela (favorito)
		this.selected	= false;		// Boolean:	indica se o item está selecionado
		this.open		= false;		// Boolean:	indica se o item está sendo exibido (aberto na interface)
		
		
		// grava todas as propriedades no objeto, recebidas como o parâmetro data
		for (key in this.recordset) {
			this[key] = this.recordset[key];
		}
		
		
		this.setHTMLElement = function(itemElement){
			htmlElement = $(itemElement)[0];
			return true;
		}
		
		this.getHTMLElement = function(){
			return htmlElement;
		}
		
		this.changeStar = function(flagStar, callback){
			
			var params = {};
			var action;
			
			params.id = id;
			action = flagStar ? 'addStar' : 'removeStar';
			
			getData('ChannelService', action, params, function(data){
				
				that.star = flagStar;
				
				if (callback && typeof callback === 'function') callback(data);
				
				var evt = document.createEvent("Event");
				evt.initEvent("onItemUpdated",true,true);
				document.dispatchEvent(evt);
			});
			
		}
		
		this.addStar = function(callback){
			return this.changeStar(true, callback);
		}
		
		this.removeStar = function(callback){
			return this.changeStar(false,callback);
		}
		
		this.addTag = function(idTag, callback){
			
			var params = {};
			params.id = this.id;
			params.idTag = idTag;
			
			getData('ChannelService','addTag', params, function(data){
				
				if (callback && typeof callback === 'function') callback(data);
				
				var evt = document.createEvent("Event");
				evt.initEvent("onItemUpdated",true,true);
				document.dispatchEvent(evt);
			});
		}
		
		this.removeTag = function(idTag, callback){
			
			var params = {};
			params.id = this.id;
			params.idTag = idTag;
			
			getData('ChannelService','removeTag', params, function(data){
				
				if (callback && typeof callback === 'function') callback(data);
				
				var evt = document.createEvent("Event");
				evt.initEvent("onItemUpdated",true,true);
				document.dispatchEvent(evt);
			});
		}
		
		this.clearTags = function(callback){
			
			var params = {};
			params.id = this.id;
			
			getData('ChannelService', 'clearTags', params, function(data){
				
				if (callback && typeof callback === 'function') callback(data);
				
				var evt = document.createEvent("Event");
				evt.initEvent("onItemUpdated",true,true);
				document.dispatchEvent(evt);
			});
		}
		
	}
	
	return instance;
})();

var Panel__OLDVERSION = (function(){
	
	var instance = function(panelContainer, tags) {
		
		// PRIVATE PROPERTIES
		var that = this;
		
		var panelHTMLElement = null; 	// HTMLElement: Objeto HTML deste panel
		var panelHeader      = null; 	// HTMLElement: Objeto HTML do cabeçalho deste painel, com os botões de ações
		var panelMenu        = null; 	// HTMLElement: Objeto HTML do menu deste painel, com botões de acesso aos sub-painéis
		var panelContent     = null; 	// HTMLElement: Objeto HTML do conteúdo deste panel
		var panelFooter      = null; 	// HTMLElement: Objeto HTML do rodapé deste panel, com informações de status
		
		var panelContainer = typeof panelContainer === 'undefined' ? null : $(panelContainer);
		var tags           = typeof navigation     === 'undefined' ? null : tags;
		
		
		// PRIVATE METHODS
		/**
		 * Monta o HTML markup do elemento básico do Panel
		 * @returns string	o HTML Markup do Panel, sem conteúdo
		 */
		var _buildHTMLPanel = function(){
			var str  = '<div class="panel container">';
				str += 	'<div class="panel-container container">';
				str += 		'<header class="panel-tools"></header>';
				str += 		'<nav class="panel-menu"></nav>';
				str += 		'<div class="panel-content container"></div>';
				str += 		'<footer class="panel-status"></footer>';
				str += 	'</div>';
				str += '</div>';
			
			return str;
		}
		
		/**
		 * Cria o Panel, e o escreve o HTML markup do panel no elemento panelContainer, passado como parâmetro no construtor
		 * @returns boolean	true se o Panel foi criado corretamente
		 */
		var _createPanel = function(){
			
			panelContainer.append(_buildHTMLPanel());
			
			panelHTMLElement = panelContainer.children('div.panel.container').last()[0];
			panelHeader  = $(panelHTMLElement).find('header.panel-tools')[0];
			panelMenu    = $(panelHTMLElement).find('nav.panel-menu')[0];
			panelContent = $(panelHTMLElement).find('div.panel-content.container')[0];
			panelFooter  = $(panelHTMLElement).find('footer.panel-status')[0];
			
			// animação de exibição do Panel, para evidenciar ao usuário que a interface sofreu alteração
			$(panelHTMLElement).stop(true,true).fadeTo(0,0).fadeTo(250,1);
			
			// por padrão, um Panel não possui menu de subPanels.
			// ao chamar a função addMenuItem, se necessário, o panelMenu é exibido automaticamente
			$(panelMenu).hide();
			
			_resizePanels();
			
			return true;
		}
		
		/**
		 * Redimensiona os Panels, distribuindo a largura do panelContainer entre os Panels
		 * @returns boolean	true
		 */
		var _resizePanels = function(){
			var len = panelContainer.find('div.panel.container').length;
			panelContainer.find('div.panel.container').each(function(index){
				$(this).css({'width':Math.floor(100/len)+'%'});
			});
			return true;
		}
		
		
		// PUBLIC METHODS
		/**
		 * Define qual é o HTMLElement para este objeto Panel
		 * @returns boolean	true
		 */
		this.setHTMLElement = function(panelElement){
			panelHTMLElement = $(panelElement)[0];
			return true;
		}
		
		/**
		 * Retorna o HTMLElement para este objeto Panel
		 * @returns HTMLElement	o elemento HTML deste objeto Panel
		 */
		this.getHTMLElement = function(){ return panelHTMLElement;  }
		this.getHeader  = function(){ return panelHeader;  }
		this.getMenu    = function(){ return panelMenu;    }
		this.getContent = function(){ return panelContent; }
		this.getFooter  = function(){ return panelFooter;  }
		
		/**
		 * Manipula elementos do panelHeader, 
		 * usado para incluir botões, separadores, ícones, etc
		 * @params string htmlElement	HTMLMarkup do elemento a ser incluso no panelHeader
		 * @params function action		Função para ser executada no evento click do elemento
		 */
		this.addHeaderItem = function(htmlElement, action){
			
			if (typeof htmlElement === 'undefined') return false;
			
			var htmlElement = $(htmlElement);
			
			if (typeof action === 'function' ) htmlElement.bind('click', action);
			
			liElement = '<li></li>';
			liElement = $(liElement);
			liElement.append(htmlElement);
			
			if (
				$(panelHeader).children('ul').length == 0 
			|| ($(panelHeader).children('ul').last()[0] != $(panelHeader).children().last()[0])
			){
				$(panelHeader).append('<ul></ul>');
			}
			
			$(panelHeader).children('ul').last().append(liElement);
			
			return true;
		}
		this.addHeaderSeparator = function(){
			$(panelHeader).append('<hr>');
			return true;
		}
		this.clearHeader = function(){
			$(panelHeader).empty();
			return true;
		}
		
		/**
		 * Manipula elementos do panelMenu, 
		 * usado para incluir ítens no menu, para acessar subPanels
		 */
		this.addMenuItem = function(title, idSubPanel){
			
			if (typeof title === 'undefined') return false;
			
			var htmlElement = '<li>'+title+'</li>';
			htmlElement = $(htmlElement);
			
			// ação de click para exibir o Panel correspondente
			if (typeof idSubPanel !== 'undefined') {
				
				htmlElement.bind('click', function(){
					
					$(panelMenu).children('ul').children('li').removeClass('active');
					htmlElement.addClass('active');
					
					var scrollDistance = Math.round($(panelContent).children('section.sub-panel#'+idSubPanel)[0].offsetLeft);
					$(panelContent).animate({scrollLeft:scrollDistance}, 100);
				});
				
			}
			
			// garante que exista um elemento 'ul'
			if ($(panelMenu).children('ul').length == 0){
				$(panelMenu).append('<ul></ul>');
			}
			
			$(panelMenu).show();
			$(panelMenu).children('ul').last().append(htmlElement);
			
			return true;
		}
		this.clearMenu = function(){
			$(panelMenu).empty();
			$(panelMenu).hide();
			return true;
		}
		
		/**
		 * Manipula elementos do panelContent, 
		 * usado para incluir conteúdo no panelContent, não como subPanels
		 */
		this.addContent = function(htmlElement){
			$(panelContent).append(htmlElement);
			return true;
		}
		this.clearContent = function(){
			$(panelContent).empty();
			return true;
		}
		
		/**
		 * Manipula elementos do panelContent, 
		 * usado para incluir subPanels na área de conteúdo
		 * também criando ítens no panelMenu para acessar o subPanel
		 */
		this.addContentPanel = function(title, htmlContent){
			
			if (typeof title === 'undefined') return false;
			if (typeof htmlContent === 'undefined') htmlContent = '';
			
			var idSubPanel = Math.floor(Math.random()*1001)+1;
			
			that.addMenuItem(title, idSubPanel);
			
			var str  = '<section id="'+idSubPanel+'" class="sub-panel container">';
				str += 	'<div class="sub-panel-container container">';
				
				str += 		'<div class="sub-panel-content container">';
				str += 			htmlContent;
				str += 		'</div>';
				
				str += 	'</div>';
				str += '</section>';
			
			$(panelContent).append(str);
			return true;
		}
		
		/**
		 * Fecha o Panel e remove o elemento HTML da interface
		 */
		this.close = function(){
			$(panelHTMLElement).remove();
			_resizePanels();
			return true;
		}
		
		
		_createPanel();
	}
	
	return instance;
})();

var SimpleList__OLDVERSION = (function(){
	
	var instance = function(listContainer, context){
		
		if (typeof context === 'undefined') return null;
		
		// private properties
		var that    = this;
		var context = context;					// String: tipo de tag buscada, por exemplo 'Channel', para 'ChannelTag'
		var htmlElement = $(listContainer)[0];	// HTMLElement: Objeto HTML desta lista de tags
		
		// public properties
		this.recordset = {};					// Object: 	dados das tags, em formato JSON
		
		// public methods
		this.getHTMLElement = function(){
			return htmlElement;
		}
		
		this.load = function(callback) {
			
			var params = {};
			params.context = context;
			
			getData('MyUserAccountService', 'loadTagList', params, function(data){
				
				that.recordset = data.recordset;
				
				if (callback && typeof callback === 'function') callback(data);
				
				var evt = document.createEvent("Event");
				evt.initEvent("onTagListLoaded",true,true);
				document.dispatchEvent(evt);
				
			});
			return true;
		}
		
		this.writeList = function(listHTMLMarkup) {
			$(listContainer).empty().append(listHTMLMarkup);
			return true;
		}
	}
	
	return instance;
})();

var ChannelList = (function(){
	
	var instance = function(listContainer){
		List.call(this, listContainer, 'Channel');
	}
	return instance;
})();

var TagList__OLDVERSION = (function(){
	
	var instance = function(listContainer){
		SimpleList__OLDVERSION.call(this, listContainer, 'Channel');
	}
	return instance;
})();















