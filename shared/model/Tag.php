<?php
/**
 * Classe que permite manipular um objeto Tag
 */

require_once($_SERVER['SYSTEM_COMMON'].'/shared/data/multibond/MultiBond.php');
require_once($_SERVER['SYSTEM_COMMON'].'/shared/data/multibond/MultiBondBond.php');
require_once($_SERVER['SYSTEM_COMMON'].'/shared/data/multibond/MultiBondTie.php');
require_once($_SERVER['SYSTEM_COMMON'].'/shared/data/multibond/MultiBondObject.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/shared/model/Settings.php');



class Tag extends MultiBondObject {
	
	protected $context;	// Define o contexto para a Tag, ou seja, o tipo de objeto que a tag classificará.
						// Na estrutura de MultiBond existem objetos Tag diferentes para cada contexto,
						// por exemplo: Opportunity (para oportunidades - OpportunityTag), Client (para clientes - ClientTag), etc.
						// Usada pelo método construtor do MultiBondObject, para identificar o Schema correto.
	
	
	
	public function __construct($id=NULL, $context=NULL, MultiBond $mb=NULL) {
		
		// inicializamos as variáveis deste objeto que não são propriedades mapeadas pelo Schema do MultiBond
		// as propriedades do Schema serão inicializadas pelo load() da classe MultiBondObject
		$this->context = is_null($context) ? NULL : $context;
		
		parent::__construct($id, (is_null($context) ? NULL : $context."Tag"), $mb);
		
		// secretProperties são propriedades deste objeto que não podem ser expostas à API pelo método expose() 
		// ou que não podem ser consultadas diretamente (por exemplo, a propriedade sPassword de um objeto User)
		// a classe MultiBondObject já possui uma lista de propriedades secretas. Devemos aqui adicionar as 
		// propriedades específicas deste objeto a essa lista
		
		// $secretProperties = array('context');
		// $this->secretProperties = array_merge($this->secretProperties, $secretProperties);
		
	}
	
	
	
	/**
	 * controla a exibição de propriedades, impedindo que propriedades secretas sejam exibidas
	 * redireciona a chamada ao método __get da classe MultiBondObject, que controla a array de propriedades
	 * @return mixed property value if valid; NULL otherwise
	 */
	public function __get($propertyName) {
		
		if (!in_array($propertyName, $this->secretProperties)){
			
			if (!array_key_exists($propertyName, get_object_vars($this))) {
				return parent::__get($propertyName);
				
			} else {
				return $this->$propertyName;
			}
		}
		
		return NULL;
	}
	
	
	
	/**
	 * controla a gravação de propriedades, impedindo que propriedades inexistentes sejam armazenadas no objeto
	 * redireciona a chamada ao método __get da classe MultiBondObject, que controla a array de propriedades
	 * este método é necessário para controlar quais propriedades foram alteradas na hora de salvar o objeto
	 * funciona como um alias para parent::setProperty, para criação de um novo valor para uma propriedade existente
	 * para EDITAR UM VALOR EXISTENTE, utilize o método 'setProperty';
	 * @return Boolean
	 */
	public function __set($propertyName, $value) {
		
		if (!in_array($propertyName, $this->secretProperties)){
			
			if (!array_key_exists($propertyName, get_object_vars($this))) {
				return parent::__set($propertyName, $value);
				
			} else {
				return $this->$propertyName;
			}
		}
		
		return false;
    }
	
	
	
	public function save() {
		
		$idUserAccount = isset($_SESSION['idUserAccount']) ? $_SESSION['idUserAccount'] : NULL;
		if (is_null($idUserAccount)) return false;
		
		$idTag = parent::save();
		if (!$idTag) return false;
		
		
		
		// vínculo com o UserAccount que criou a tag, para mapear o Bond de Ownership
		$bond   = new MultiBondBond(NULL, 'Ownership', $this->multiBond);
		$idBond = $bond->save();
		if (!$idBond) return false;
		
		
		$tagTie = new MultiBondTie(array(
									'id'        => NULL,
									'SCHEMATie' => 'Owned',
									'idObject'  => $idTag,
									'idBond'    => $idBond,
									'mb'        => $this->multiBond
								));
		$idTagTie = $tagTie->save();
		if (!$idTagTie) return false;
		
		
		$userAccountTie = new MultiBondTie(array(
											'id'           => NULL,
											'SCHEMATie'    => 'Owner',
											'idObject'     => $idUserAccount,
											'idBond'       => $idBond,
											'mb'           => $this->multiBond
										));
		$idUserAccountTie = $userAccountTie->save();
		if (!$idUserAccountTie) return false;
		
		return true;
		
	}
	
	
}