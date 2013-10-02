<?php
/**
 * Classe que permite manipular um objeto SystemPermission
 */

require_once($_SERVER['SYSTEM_COMMON'].'/shared/data/multibond/MultiBond.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/shared/model/Settings.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/shared/model/BaseObject.php');


class SystemPermission extends BaseObject {
	
	const CONTEXT = 'SystemPermission';
	
	public function __construct($id=NULL, MultiBond $mb=NULL, $autoLoad=true) {
		
		parent::__construct($id, self::CONTEXT, $mb, $autoLoad);
		
		// secretProperties são propriedades deste objeto que não podem ser expostas à API pelo método expose() 
		// ou que não podem ser consultadas diretamente (por exemplo, a propriedade sPassword de um objeto User)
		// a classe MultiBondObject já possui uma lista de propriedades secretas. Devemos aqui adicionar as 
		// propriedades específicas deste objeto a essa lista
		
		$secretProperties = array('fStar','tagList','tsCreation','tsLastUpdate');
		$this->secretProperties = array_merge($this->secretProperties, $secretProperties);
		
	}
	
}
