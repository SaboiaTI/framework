<?php
/**
 * Classe que permite manipular um objeto File
 */

require_once($_SERVER['SYSTEM_COMMON'].'/shared/data/multibond/MultiBond.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/shared/model/Settings.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/shared/model/BaseObject.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/shared/model/UserAccount.php');


class File extends BaseObject {
	
	const CONTEXT = "File";
	
	protected $users;
	
	
	
	public function __construct($id=NULL, MultiBond $mb=NULL, $autoLoad=true) {
		
		// inicializamos as variáveis deste objeto que não são propriedades mapeadas pelo Schema do MultiBond
		// as propriedades do Schema serão inicializadas pelo load() da classe MultiBondObject
		
		$this->users = array();
		
		parent::__construct($id, self::CONTEXT, $mb, $autoLoad);
		
	}
	
	
	
	/**
	 * carrega do banco de dados as informações requisitadas deste objeto
	 * @param $whatToLoad array|'*'	lista de informações adicionais que devem ser carregadas junto com o objeto (opcional)
	 * 								aceita a propriedade '*' para carregar o objeto completo, com todas as informações adicionais
	 * @return boolean
	 */
	public function load($whatToLoad=array()) {
		
		$success = parent::load();
		if (!$success) return false;
		
		
		// Além das informações básicas, também carrega o que for explicitamente solicitado:
		if (!is_array($whatToLoad) && $whatToLoad!=='*') return false;
		
		// condição especial: o parâmetro "*" carrega o objeto por completo:
		if (!is_array($whatToLoad) && $whatToLoad==='*') return $this->_loadCompleteObject();
		
		// ou verifica cada parâmetro solicitado:
		if (in_array('users', 		  $whatToLoad)) $this->loadUsers();
		
		return true;
	}
	
	
	
	/**
	 * carrega do banco de dados as informações completas deste objeto
	 * chamada pelo método load(), quando este receber o parâmetro '*'
	 * @return boolean
	 */
	private function _loadCompleteObject() {
	
		$this->loadUsers();
		
		return true;
	}
	
	
	
	/**
	 * salva as informações deste objeto no banco de dados
	 * também, ao criar, um vínculo com o usuário criador já é criado automaticamente
	 * @return int / boolean
	 */
	public function save() {
		
		if (is_null($this->id)) {
			
			$success = parent::_create();
			if (!$success) return false;
			
			$success = $this->addUser($_SESSION["idUserAccount"], "Creator");
			return $success;
			
		} else {
			return parent::_update();
		}
	}
	
	
	
	/**
	 * carrega a lista de usuários vinculados a este File
	 * @return Boolean
	 */
	public function loadUsers() {
		
		$this->users = array();
		
		$users = $this->multiBond->mapBondedObjects(
			array(
				'id'          => $this->id,
				'thisTie'     => 'Managed',
				'bondType'    => 'Management',
				'thatTie'     => array('Creator','Manager','Responsible'),
				'thatType'    => 'UserAccount',
				'filterParam' => '*'
			));
		if (!$users || count($users) == 0) return true;
		
		
		foreach ($users as $u) {
			
			$id = intval((int)$u['thatObject']['id']);
			$user = new UserAccount($id, $this->multiBond);
			if ($user) $this->users[ $u['thatTie']['schema'] ][] = $user->expose();
		}
		
		return true;
	}
	public function addUser($idUserAccount=NULL, $role="Responsible") {
		
		if (is_null($idUserAccount))   return false;
		if (is_null($this->id))        return false;
		if (is_null($this->multiBond)) return false;
		
		// o id passado deve ser de um UserAccount válido
		if( $this->multiBond->getObjectSCHEMA($idUserAccount) !== "UserAccount" ) return false;
		
		if (!in_array($role, array("Creator","Manager","Responsible"))) {
			return false;
		}
		
		$success = $this->addBondToObject(array(
			'thisTieType'  => 'Managed',
			'bondType'     => 'Management',
			'thatTieType'  => $role,
			'thatId'       => $idUserAccount,
			'reuseThisTie' => true,
			'reuseBond'    => true,
			'reuseThatTie' => false
		));
		
		if (!$success) return false;
		
		return $this->loadUsers();
	}
	public function removeUser($idUserAccount=NULL, $role=NULL) {
		
		if (is_null($idUserAccount))   return false;
		if (is_null($this->id))        return false;
		if (is_null($this->multiBond)) return false;
		
		
		if (!in_array($role, array("Creator","Manager","Responsible","*"))) {
			return false;
		}
		
		
		$success = $this->removeBondToObject(array(
			'thisTieType'     => 'Managed',
			'bondType'        => 'Management',
			'thatTieType'     => $role,
			'thatId'          => $idUserAccount,
			'preserveThisTie' => true,
			'preserveThatTie' => false
		));
		
		if (!$success) return false;
		
		return $this->loadUsers();
		
	}
	
	
}
