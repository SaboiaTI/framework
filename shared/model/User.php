<?php
/**
 * Classe que permite manipular um objeto User
 * O Objeto User deve ser usado apenas na aplicação responsável pela autenticação!
 * Nas demais aplicações, o objeto UserAccount deve ser usado em detrimento do objeto User.
 */

require_once($_SERVER['DOCUMENT_ROOT'].'/shared/data/multibond/MultiBond.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/shared/libraries/php/email.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/shared/model/Settings.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/shared/model/BaseObject.php');

class User extends BaseObject {

	const CONTEXT = 'User';
	const ROOT_ME_FRONTEND = "http://me.sandbox.saboia.pro";
	public $applications;

	public function __construct($id=NULL, MultiBond $mb=NULL, $autoLoad=true) {

		// inicializamos as variáveis deste objeto que não são propriedades mapeadas pelo Schema do MultiBond
		// as propriedades do Schema serão inicializadas pelo load() da classe MultiBondObject

		$this->applications = array();

		parent::__construct($id, self::CONTEXT, $mb, $autoLoad);

		// secretProperties são propriedades deste objeto que não podem ser expostas à API pelo método expose()
		// ou que não podem ser consultadas diretamente (por exemplo, a propriedade sPassword de um objeto User)
		// a classe MultiBondObject já possui uma lista de propriedades secretas. Devemos aqui adicionar as
		// propriedades específicas deste objeto a essa lista

		$secretProperties = array('sPassword');
		$this->secretProperties = array_merge($this->secretProperties, $secretProperties);

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

//		$this->sAvatarPath = User::ROOT_ME_FRONTEND.$this->sAvatarPath;

		// Além das informações básicas, também carrega o que for explicitamente solicitado:
		if (!is_array($whatToLoad) && $whatToLoad!=='*') return false;

		// condição especial: o parâmetro "*" carrega o objeto por completo:
		if (!is_array($whatToLoad) && $whatToLoad==='*') return $this->_loadCompleteObject();

		return true;
	}

	/**
	 * carrega do banco de dados as informações deste User
	 * através do login do usuário (qualquer login válido do usuário)
	 * @return Boolean
	 */
	public function loadByLogin() {

		//if (is_null($login)) return false;

		$users = $this->multiBond->getObjects(array(
													'objectType'  => 'User',
													'filterParam' => "sLogon=".prepareSQL($_SESSION["login"])." AND fActive=1  AND fBlocked=0"
													));

		if (!isset($users[0])) return false;
		if (count($users)>1)   return false;

		$this->id = $users[0];
		return $this->load();

	}

	public function expose($expandMultiBondProperties=false) {

		$obj = parent::expose($expandMultiBondProperties);

		if ($expandMultiBondProperties==false) {
			$obj['sAvatarPath'] = !empty($obj['sAvatarPath']) ? User::ROOT_ME_FRONTEND.$obj['sAvatarPath'] : $obj['sAvatarPath'];
		}

		return $obj;
	}



	/**
	 * carrega do banco de dados as informações completas deste objeto
	 * chamada pelo método load(), quando este receber o parâmetro '*'
	 * @return boolean
	 */
	private function _loadCompleteObject() {

		$this->loadApplications();
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
			return $success;

		} else {
			return parent::_update();
		}
	}



	public function loadApplications() {

		$this->applications = array();

		$apps = $this->multiBond->mapBondedObjects(array(
														"id"          => $this->id,
														"thisTie"     => "Member",
														"bondType"    => "Membership",
														"thatTie"     => "Entity",
														"thatType"    => "Application",
														"filterParam" => "*"
													));
		if (!$apps || count($apps) == 0) return true;

		foreach ($apps as $a) {

			$id = intval((int)$a["thatObject"]["id"]);
			$app = new BaseObject($id, "Application", $this->multiBond);
			if ($app) $this->applications[] = $app->expose();
		}

		return true;
	}
	public function addApplication($idApplication=NULL) {

		if (is_null($idApplication))   return false;
		if (is_null($this->id))        return false;
		if (is_null($this->multiBond)) return false;

		$success = $this->addBondToObject(array(
			"thisTieType"  => "Member",
			"bondType"     => "Membership",
			"thatTieType"  => "Entity",
			"thatId"       => $idApplication,
			"reuseThisTie" => true,
			"reuseBond"    => true,
			"reuseThatTie" => false
		));

		if (!$success) return false;

		return $this->loadApplications();
	}
	public function removeApplication($idApplication=NULL) {

		if (is_null($idApplication))   return false;
		if (is_null($this->id))        return false;
		if (is_null($this->multiBond)) return false;


		$success = $this->removeBondToObject(array(
			"thisTieType"     => "Member",
			"bondType"        => "Membership",
			"thatTieType"     => "Entity",
			"thatId"          => $idApplication,
			"preserveThisTie" => true,
			"preserveThatTie" => false
		));

		if (!$success) return false;

		return $this->loadApplications();

	}



	private function _generateValidPassword() {

		$arLowerCase = explode(",", "a,e,i,o,u,b,c,d,f,g,h,j,k,l,m,n,p,q,r,s,t,v,w,x,y,z");
		$arUpperCase = explode(",", "A,E,I,O,U,B,C,D,F,G,H,J,K,L,M,N,P,Q,R,S,T,V,W,X,Y,Z");
		$arNumeros   = explode(",", "0,1,2,3,4,5,6,7,8,9");
		$arSimbolos  = explode(",", "!,@,#,-");

		$strNovaSenha = "";

		while (strlen($strNovaSenha) < 10) {

			$i=0;

			while (strlen($strNovaSenha) < 3) {

				$intNumber     = mt_rand(0,count($arLowerCase)-1);
				$strNovaSenha .= $arLowerCase[$intNumber];

				$i++;
				if ($i >= 10) { break; }
			}

			while (strlen($strNovaSenha) < 6) {

				$intNumber     = mt_rand(0,count($arUpperCase)-1);
				$strNovaSenha .= $arUpperCase[$intNumber];

				$i++;
				if ($i >= 10) { break; }
			}

			while (strlen($strNovaSenha) < 9) {

				$intNumber     = mt_rand(0,count($arSimbolos)-1);
				$strNovaSenha .= $arSimbolos[$intNumber];

				$i++;
				if ($i >= 10) { break; }
			}

			while (strlen($strNovaSenha) < 11) {

				$intNumber     = mt_rand(0,count($arNumeros)-1);
				$strNovaSenha .= $arNumeros[$intNumber];

				$i++;
				if ($i >= 10) { break; }
			}

		}

		return $strNovaSenha;
	}
	private function _hashPassword($password) {
		return hash("sha512", $password . Settings::SALT);
	}
	public function changePassword($password=NULL) {

		if (is_null($this->id)) return false;

		$sPassword = $password ? $password : $this->_generateValidPassword();
		$sPassword = trim($sPassword);

		$this->setProperty('sPassword', $this->_hashPassword($sPassword));
		$this->tsLastPasswordUpdate = date("Y-m-d H:i:s");

		$success = $this->save();
		if (!$success) return false;


		// envia os dados de acesso para o usuário por email
		$logon = "";
		foreach($this->sLogon as $k=>$v){
			$logon = $v;break;
		}

		$email = new Email();
		$email->to			= $this->sLogon;
		$email->title		= "DADOS DE ACESSO - SABOIA";
		$email->message		= "Caro(a) <strong>".$this->sFullName."</strong>,<br>
Seguem abaixo os seus dados de acesso aos Sistemas da Saboia:<br><br>
<pre><strong>usuário:</strong>".$logon."<br>
<strong>senha:</strong>".$sPassword."</pre><br>
Acesse o endereço <a href='".User::ROOT_ME_FRONTEND."'>".User::ROOT_ME_FRONTEND."</a>";

		//print_pre($this->sLogon);

		$success = $email->send();
		return $success;
	}

}
