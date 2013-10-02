<?php
/**
 * Classe que permite manipular um objeto UserAccount
 * UserAccount é um objeto mais simples do que o User, usado apenas para controle de um usuário dentro de um sistema específico.
 * Porém, este objeto deve ser usado em detrimento do objeto User. 
 * O Objeto User deve ser usado apenas na aplicação responsável pela autenticação!
 */

require_once($_SERVER['SYSTEM_COMMON'].'/shared/data/multibond/MultiBond.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/shared/model/Settings.php');
require_once($_SERVER['SYSTEM_COMMON'].'/shared/model/User.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/shared/model/Tag.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/shared/model/BaseObject.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/shared/model/SystemPermission.php');



class UserAccount extends BaseObject {
	
	protected $ownTagList;
	protected $permissionList;
	protected $userData;
	
	
	
	public function __construct($id=NULL, MultiBond $mb=NULL) {
		
		global $authentication_mb;
		
		$this->ownTagList = array();
		$this->permissionList = array();
		
		parent::__construct($id, 'UserAccount', $mb);
		
		$u = new User($this->referredUniqueUserId, $authentication_mb);
		$this->userData = $u->expose();
		
	}
	
	
	
	/**
	 * carrega do banco de dados as informações completas deste registro de oportunidade
	 * @return Boolean
	 */
	public function load() {
		
		$success = parent::load();
		if (!$success) return false;
		
		$success = $this->loadPermissionList();
		if (!$success) return false;
		
		$success = $this->loadOwnTagList();
		if (!$success) return false;
		
		return true;
		
	}
	
	
	
	/**
	 * carrega todas as permissões do usuário, de acordo com seus accessGroups
	 * Mapeamento de estrutura de Bonds:
	 * 
	 * UserAccount   <--> Member                <--> Membership    <--> Entity    <--> AccessProfile     // UserAccount é membro de um AccessProfile;
	 * UserAccount   <--> Member, Administrator <--> Membership    <--> Entity    <--> Partner           // UserAccount é membro de um Partner;
	 * Partner       <--> Qualified             <--> Qualification <--> Qualifier <--> PartnerRole       // Partner é qualificado por um PartnerRole;
	 * PartnerRole   <--> Whole                 <--> Composition   <--> Part      <--> AccessProfile     // PartnerRole é composto por AccessProfiles;
	 * AccessProfile <--> Whole                 <--> Composition   <--> Part      <--> SystemPermission  // AccessProfiles é comporto por SystemPermissions;
	 * 
	 * @return Boolean
	 */
	public function loadPermissionList() {
		
		$this->permissionList = array();
		
		$userAccessProfiles      = array();	// AccessProfiles ao qual este UserAccount está vinculado diretamente
		$partnerAccessProfiles   = array();	// AccessProfiles ao qual o Partner deste UserAccount está vinculado
		$intersectAccessProfiles = array();	// AccessProfiles ao qual este UserAccount está vinculado corretamente 
											// podem haver algum caso em que o UserAccount esteja vinculado ao AccessProfile, 
											// mas seu Partner foi desvinculado do PartnerRole que lhe permitia esse perfil;
											// portanto é sempre necessário validar se o AccessProfile está vinculado ao Partner;
		$permissions      		 = array();	// Permissões encontradas para este UserAccount
		
		
		// Passo 1: Listar todos os AccessProfiles ligados diretamente ao UserAccount
		// vínculo dos PartnerRoles aos seus AccessProfiles
		$userAccessProfiles = $this->multiBond->getBondedObjects(
			array(
				'id'          => $this->id,
				'thisTie'     => 'Member',
				'bondType'    => 'Membership',
				'thatTie'     => 'Entity',
				'thatType'    => 'AccessProfile',
				'filterParam' => '*'
			));

// $GLOBALS["debug"]->add($this->id,array('userAccessProfiles'=>$userAccessProfiles));

		if (!$userAccessProfiles || count($userAccessProfiles) != 1) return true;
		
		
		
		// Passo 2: Listar todos os AccessProfiles ligados indiretamente ao UserAccount (pelo Partner)
		// vínculo do UserAccount ao seu Partner (apenas 1)
		$partners = $this->multiBond->getBondedObjects(
			array(
				'id'          => $this->id,
				'thisTie'     => array('Member','Administrator'),
				'bondType'    => 'Membership',
				'thatTie'     => 'Entity',
				'thatType'    => 'Partner',
				'filterParam' => '*'
			));
		
// $GLOBALS["debug"]->add($this->id,array('partners'=>$partners));

		if (!$partners || count($partners) != 1) return true;
		$idPartner = $partners[0];
		
		
		// vínculo do Partner aos seus PartnerRoles
		$partnerRoles = $this->multiBond->getBondedObjects(
			array(
				'id'          => $idPartner,
				'thisTie'     => 'Qualified',
				'bondType'    => 'Qualification',
				'thatTie'     => 'Qualifier',
				'thatType'    => 'PartnerRole',
				'filterParam' => '*'
			));
			
// $GLOBALS["debug"]->add($this->id,array('partnerRoles'=>$partnerRoles));
		
		if (!$partnerRoles || count($partnerRoles) != 1) return true;
		
		
		// vínculo dos PartnerRoles aos seus AccessProfiles
		foreach($partnerRoles as $role) {
			$temp_partnerAccessProfiles = $this->multiBond->getBondedObjects(
				array(
					'id'          => $role,
					'thisTie'     => 'Whole',
					'bondType'    => 'Composition',
					'thatTie'     => 'Part',
					'thatType'    => 'AccessProfile',
					'filterParam' => '*'
				));
			
// $GLOBALS["debug"]->add(NULL,array('this->id'=>$this->id,'temp_partnerAccessProfiles'=>$temp_partnerAccessProfiles));

			if ($temp_partnerAccessProfiles)
				$partnerAccessProfiles = array_merge($partnerAccessProfiles, $temp_partnerAccessProfiles);
		}
		
// $GLOBALS["debug"]->add($this->id,array('partnerAccessProfiles'=>$partnerAccessProfiles));
		
		
		
		// Passo 3: Intersecção entre os AccessProfiles do UserAccount e AccessProfiles do Partner
		//          Listar todos os SystemPermissions ligados aos AccessProfiles resultantes
		$intersectAccessProfiles = array_intersect($partnerAccessProfiles, $userAccessProfiles);
		
		foreach($intersectAccessProfiles as $accessProfile) {
			$temp_permissions = $this->multiBond->getBondedObjects(
				array(
					'id'          => $accessProfile,
					'thisTie'     => 'Whole',
					'bondType'    => 'Composition',
					'thatTie'     => 'Part',
					'thatType'    => 'SystemPermission',
					'filterParam' => '*'
				));
				
			if ($temp_permissions)
				$permissions = array_merge($permissions, $temp_permissions);
		}
		if (!$permissions || count($permissions) == 0) return true;
		
		
		foreach ($permissions as $value) {
			
			$id = intval((int)$value);
			$permission = new SystemPermission($id, $this->multiBond);
			if ($permission) $this->permissionList[] = $permission->expose();
		}
		
		return true;
	}
	
	
	
	/**
	 * Verifica permissões do usuário para execução de métodos
	 * Cada método chamado pelos serviços (através da API) deve chamar esta função passando os tokens de permissão necessários para sua execução
	 * @return Boolean
	 */
	public function checkPermission(array $tokens=array()){
		
		if (count($tokens) < 1) return false;
		if (count($this->permissionList) < 1) return false;
		
		$temp_permissionList = array();
		foreach($this->permissionList as $key=>$value){ $temp_permissionList[] = $value['sKey']; }
		
		foreach($tokens as $t){
			if ( !in_array($t, $temp_permissionList) ) {
				return false;
			}
		}
		
		return true;
	}
	
	
	
	/**
	 * Verifica PartnerRoles do usuário, de acordo com o Partner ao qual está vinculado
	 * @return Boolean
	 */
	public function checkPartnerRole(array $roles=array()) {
		
		if (count($roles) < 1) return false;
		
		// vínculo do UserAccount ao seu Partner (apenas 1)
		$partners = $this->multiBond->getBondedObjects(
			array(
				'id'          => $this->id,
				'thisTie'     => array('Member','Administrator'),
				'bondType'    => 'Membership',
				'thatTie'     => 'Entity',
				'thatType'    => 'Partner',
				'filterParam' => '*'
			));
		if (!$partners || count($partners) != 1) return false;
		$idPartner = $partners[0];
		
		
		// vínculo do Partner aos seus PartnerRoles
		$partnerRoles = $this->multiBond->getBondedObjects(
			array(
				'id'          => $idPartner,
				'thisTie'     => 'Qualified',
				'bondType'    => 'Qualification',
				'thatTie'     => 'Qualifier',
				'thatType'    => 'PartnerRole',
				'filterParam' => '*'
			));
		if (!$partnerRoles || count($partnerRoles) != 1) return false;
		
		$temp_partnerRoles = array();
		foreach($partnerRoles as $id){
			$pr = new BaseObject($id, 'PartnerRole', $this->multiBond);
			$temp_partnerRoles[] = $pr->sKey;
		}
		
		foreach($roles as $r){
			if ( !in_array($r, $temp_partnerRoles) ) {
				return false;
			}
		}
		
		return true;
		
	}
	
	
	/**
	 * carrega a lista de tags
	 * @return Boolean
	 */
	public function loadOwnTagList() {
		
	//	$idUserAccount = isset($_SESSION['idUserAccount']) ? $_SESSION['idUserAccount'] : NULL;
	//	if (is_null($idUserAccount)) return false;
		
		$this->ownTagList = array();
		
		$tags = $this->multiBond->mapBondedObjects(array(
					'id'          => $this->id,
					'thisTie'     => 'Owner',
					'bondType'    => 'Ownership',
					'thatTie'     => 'Owned',
					'thatType'    => array('OpportunityTag', 'ChannelTag', 'ClientTag'),
					'filterParam' => '*'
				));
		if (!$tags || count($tags) == 0) return true;
		
		
		foreach ($tags as $t) {
			
			$id = $t['thatObject']['id'];
			$schema = $t['thatObject']['schema'];
			
			$tag = new Tag((int)$id, (string)$schema, $this->multiBond);
			if ($tag) $this->ownTagList[ $tag->context ][] = $tag->expose();
		}
		
		return true;
	}
	
	
	
}