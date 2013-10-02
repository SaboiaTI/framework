<?php
/**
 * Classe que permite manipular um objeto Authentication
 */

require_once($_SERVER['DOCUMENT_ROOT'].'/shared/model/Settings.php');
require_once($_SERVER['SYSTEM_COMMON'].'/shared/model/User.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/shared/model/UserAccount.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/shared/libraries/php/email.php');
require_once($_SERVER['SYSTEM_COMMON'].'/shared/data/multibond/MultiBond.php');

class Authentication {
	
	protected $appMultiBond;
	protected $authMultiBond;
	
	
	
	/**
	 * instancia um objeto Authentication
	 * @params $appMultiBond  MultiBond usado pela aplicação;
	 * @params $authMultiBond MultiBond usado pela autenticação (ME-SABOIA);
	 */
	public function __construct(MultiBond $appMultiBond=NULL, MultiBond $authMultiBond=NULL) {
		
		if (is_null($appMultiBond))  return NULL;
		if (is_null($authMultiBond)) return NULL;
		
		$this->appMultiBond = $appMultiBond;
		$this->authMultiBond = $authMultiBond;
	}
	
	
	
	/**
	 * realiza o login do usuário no sistema
	 * @return Boolean
	 */
	public function login($username, $password){
		
		$this->logout();
		
		$username = $username;
		$password = $this->hashedPassword($password);
		
		if (is_null($username) || is_null($password)) return false;
		
		$users = $this->authMultiBond->getObjects(
			array(
				'objectType'  => 'User',
				'filterParam' => "sLogon=".prepareSQL($username)." AND sPassword=".prepareSQL($password)." AND fActive=1 AND fBlocked=0"
			));
		
		// se não foi possível encontrar o usuário com o login e senha informados, 
		// adiciona 1 ao contador de tentativas de login com senha incorreta:
		if (count($users)!=1) {
			
			$users = $this->authMultiBond->getObjects(
				array(
					'objectType'  => 'User',
					'filterParam' => "sLogon=".prepareSQL($username)." AND fActive=1"
				)
			);
			
			if (count($users)!=1) return false;
			
			$user = new User($users[0], $this->authMultiBond);
			
			if ($user->iLoginAttempts < Settings::MAX_LOGIN_ATTEMPTS) {
				
				$user->iLoginAttempts += 1;
				$user->save();
			}
			
			// se o usuário exceder a quantidade máxima de tentativas de login, será bloqueado e notificado
			if ($user->iLoginAttempts >= Settings::MAX_LOGIN_ATTEMPTS) {
				
				$user->fBlocked = 1;
				$user->save();
				
				// TO DO: criar um novo tipo / layout de email para notificações de conta de acesso bloqueada
				
			}
			
			return false;
		}
		
		
		$user = new User($users[0], $this->authMultiBond);

		// se o usuário conseguir se logar, 
		// vamos zerar a contagem de tentativas de login...
		if ($user->iLoginAttempts > 0) {
			$user->iLoginAttempts = 0;
			$user->save();
		}
		
		
		// agora procura pela UserAccount do usuário na aplicação, para verificar se
		// o User existente no ME-SABOIA possui acesso ao sistema em que tentou se logar
		$accounts = $this->appMultiBond->getObjects(
			array(
				'objectType'  => 'UserAccount',
				'filterParam' => "referredUniqueUserId=".prepareSQL($users[0])
			)
		);
		
		if (count($accounts)<1) return false;
		$account = new UserAccount($accounts[0], $this->appMultiBond);
		
		$_SESSION["UserOn"] = true;
		$_SESSION["login"] = $username;
		$_SESSION["uniqueUserId"] = $users[0];
		$_SESSION["idUserAccount"] = $accounts[0];
		
		return $account;
	}
	
	
	
	/**
	 * realiza o login do usuário no sistema, através de verificação de cookies
	 * @return Boolean
	 
	 hash identificador do usuário, usado para login baseado em session cookies
	 
	 */
//	public function loginByCookie__________OLD() {
//		
//	//	echo "called loginByCookie\n";
//		
//		if (!isset($_COOKIE['authentication'])) return false;
//		
//	//	echo "cookie found!\n";
//		
//		// recupera sIdentifier e sAutoLoginKey do cookie
//		list($sIdentifier, $sAutoLoginKey) = explode(':', $_COOKIE['authentication']);
//		
//		if (!ctype_alnum($sIdentifier))   return false;
//		if (!ctype_alnum($sAutoLoginKey)) return false;
//		Authentication
//	//	echo "cookie data is allright!\n";
//		
//		// procura pelo usuário com esse sIdentifier e sAutoLoginKey
//		$users = $this->appMultiBond->getObjects(
//			array(
//				'objectType'  => 'User',
//				'filterParam' => "sIdentifier=".prepareSQL($sIdentifier)." AND sAutoLoginKey=".prepareSQL($sAutoLoginKey)." AND iAutoLoginTimeout>=".time()." AND fActive=1 AND fBlocked=0"
//			)
//		);
//		
//		if (count($users)!=1) return false;
//		
//		$user = new User($users[0], $this->appMultiBond);
//		
//	//	echo "user found!\n";
//		
//		// armazena/atualiza o cookie para login do usuário
//		// é necessário apenas gerar uma nova chave, o timeout continuará o mesmo 
//		$sAutoLoginKey       = md5(uniqid(rand(), true));
//		$user->sAutoLoginKey = $sAutoLoginKey;
//		
//		$success = $user->save();
//		
//		$cookie = setcookie('authentication', "{$user->sIdentifier}:{$sAutoLoginKey}", $user->iAutoLoginTimeout);
//	//	echo "setcookie? $cookie\n";
//		
//		
//		$_SESSION["UserOn"] = true;
//		$_SESSION["login"]  = $user->sLogon[0]['sValue'];
//		$_SESSION["uniqueUserId"] = $users[0];
//		$_SESSION["idUserAccount"] = NULL;
//		
//		return $user;
//	}
	
	
	
	
	
	
	
	
	
	/**
	 * realiza o logout do usuário no sistema
	 * @return Boolean
	 */
	public function logout() {
		
		// "destroys" the cookie, so the user will not be logged in automatically
//		if (isset($_COOKIE['authentication'])) {
//			$timeInThePast = time() - 60 * 60 * 24 * 365; // one year ago...
//			$cookie = setcookie('authentication', false, $timeInThePast);
//		}
		
		unset($_SESSION["UserOn"]);
		unset($_SESSION["login"]);
		unset($_SESSION["uniqueUserId"]);
		unset($_SESSION["idUserAccount"]);
		
		return true;
	}
	
	
	
	/**
	 * constrói o hash de um dado password, de acordo com o salt definido para o sistema
	 * @return String
	 */
	private function hashedPassword($password) {
		return hash("sha512", $password . Settings::SALT);
	}
	
	
	
	/**
	 * Verifica permissões do usuário logado para execução de métodos
	 * Cada método chamado pelos serviços (através da API) deve chamar esta função passando os tokens de permissão necessários para sua execução
	 * @return Boolean
	 */
	static public function validatePermission(MultiBond $appMultiBond=NULL, array $tokens=array()) {
		
		// verificar se existe a seção ativa e suas respectivas chaves
		if ( ! isset( $_SESSION["uniqueUserId"] ) && strlen( $_SESSION["uniqueUserId"] ) < 1 && ! isset( $_SESSION["idUserAccount"] ) && strlen( $_SESSION["idUserAccount"] ) < 1 && count( $tokens ) < 1 || is_null( $appMultiBond ) ){
			return false ;
		}
		
		// listando os perfis de acesso
		$args = array(
			 'id'          => $_SESSION["idUserAccount"] 
			,'thisTie'     => 'Member'
			,'bondType'    => 'Membership'
			,'thatTie'     => 'Entity'
			,'thatType'    => 'AccessProfile'
			,'filterParam' => '*'
		);
		
		$accessProfiles = $appMultiBond->getBondedObjects( $args ) ;
		
		if( !is_array( $accessProfiles ) || count( $accessProfiles ) < 1 ) {
			return false;
		}
		
		
		$filter = array() ;
		foreach( $tokens as $token ){
			//$filter[] = preg_replace('/"{1,20}/',"'", " sKey = " . prepareSQL( $token ) ) ;
			$filter[] = " sKey = " . prepareSQL( $token ) ;
		}
		
		// listando as permissões desse(s) perfil(s) de acesso
		$args = array(
			'id'          	=> $accessProfiles 
			, 'thisTie'     => 'Whole'
			, 'bondType'    => 'Composition'
			, 'thatTie'     => 'Part'
			, 'thatType'    => 'SystemPermission'
			, 'filterParam' => implode( " or " , $filter ) //" skey in ('" . implode("','" ,  $tokens ) . "')" 
		);
		
		$systemPermissions = $appMultiBond->getBondedObjects( $args ) ;
		
		//verifica se o $systemPermissions e tem o mesmo tamanho dos tokens informados
		return is_array( $systemPermissions ) && count( $systemPermissions ) == count( $tokens ) ;
		
	}
	
	
	
}
