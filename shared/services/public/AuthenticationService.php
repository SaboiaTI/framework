<?php
/**
 * Classe que permite realizar login de um usuário no sistema
 */

require_once($_SERVER['DOCUMENT_ROOT'].'/shared/model/Settings.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/shared/model/Authentication.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/shared/model/User.php');


class AuthenticationService {

	private   $response;
	protected $appMultiBond;
	protected $authMultiBond;


	/**
	 * instancia um AuthenticationService
	 * @params $appMultiBond MultiBond usado pela aplicação (usualmente informado pela API como o global $application_mb);
	 *                       É passado como parâmetro pois é único para cada sistema/aplicação;
	 *                       Um segundo MultiBond é utilizado por este objeto para se conectar à aplicação ME-SABOIA
	 *                       e consultar a base de dados de usuários da Saboia;
	 *                       Não é passado como parâmetro pois é sempre utilizado o mesmo ($authentication_mb);
	 */
	public function __construct(MultiBond $appMultiBond=NULL) {

		global $authentication_mb;

		$this->appMultiBond  = $appMultiBond;
		$this->authMultiBond = $authentication_mb;

		$this->response = array();
		$this->response['success'] = false;
		$this->response['recordset'] = NULL;
	}



	public function login(array $parameters) {

		$this->response = array();
		$this->response['success'] = false;
		$this->response['recordset'] = NULL;

		$username = getParameter('username', $parameters, NULL);
		$password = getParameter('password', $parameters, NULL);

		if (is_null($username) || is_null($password)) {

			$this->response['applicationControl']['error']['number']  = Settings::ERROR_WRONG_LOGIN_NUMBER;
			$this->response['applicationControl']['error']['type']    = Settings::ERROR_WRONG_LOGIN_TYPE;
			$this->response['applicationControl']['error']['message'] = Settings::ERROR_WRONG_LOGIN_MESSAGE;

			return $this->response;
		}

		$auth = new Authentication($this->appMultiBond, $this->authMultiBond);
		$user = $auth->login($username, $password);

		if (!$user) {

			$this->response['applicationControl']['error']['number']  = Settings::ERROR_WRONG_LOGIN_NUMBER;
			$this->response['applicationControl']['error']['type']    = Settings::ERROR_WRONG_LOGIN_TYPE;
			$this->response['applicationControl']['error']['message'] = Settings::ERROR_WRONG_LOGIN_MESSAGE;

			return $this->response;
		}

		$this->response['applicationControl']['authenticated'] = true;
		$this->response['recordset'] = $user->expose();
		$this->response['success'] = true;

		return $this->response;
	}



	public function logout(array $parameters) {

		$this->response = array();
		$this->response['success'] = false;
		$this->response['recordset'] = NULL;

		$auth = new Authentication($this->appMultiBond, $this->authMultiBond);
		$success = $auth->logout();

		$this->response['success'] = $success;

		return $this->response;
	}



	public function checkUser(array $parameters) {

		$this->response = array();
		$this->response['success'] = false;
		$this->response['recordset'] = NULL;

		if (!isset($_SESSION["UserOn"]) || !$_SESSION["UserOn"]) return $this->response;

		$idUser   = $_SESSION["idUser"];
		$username = $_SESSION["login"];

		$user = new User($idUser, $this->authMultiBond);

		if (!$user) return $this->response;

		$_SESSION["idUser"] = $idUser;
		$_SESSION["login"]	= $username;
		$_SESSION["UserOn"]	= true;

		$this->response['applicationControl']['authenticated'] = true;
		$this->response['success']   = true;
		$this->response['recordset'] = $user->expose();

		return $this->response;

	}



	public function showProfile(array $parameters) {

		$this->response = array();
		$this->response['success'] = false;
		$this->response['recordset'] = NULL;


		if (   !isset($_SESSION["uniqueUserId"])
			&& strlen($_SESSION["uniqueUserId"]) < 1
			&& !isset($_SESSION["idUserAccount"])
			&& strlen($_SESSION["idUserAccount"]) < 1
		){
			return $this->response;
		}

		$this->response['applicationControl']['authenticated'] = true;

		$profile = getParameter('profile', $parameters, $_SESSION["login"]);

		$user	 = new User(NULL, $this->authMultiBond);
		$success = $user->loadByLogin($profile);

		if (!$success) return $this->response;

		$this->response['success']   = true;
		$this->response['recordset'] = $user->expose();

		return $this->response;

	}


	public function loadPermissionList(array $parameters) {

		$this->response = array();
		$this->response['success'] = false;
		$this->response['recordset'] = NULL;

		if (!isset($_SESSION["UserOn"]) || !$_SESSION["UserOn"]) return $this->response;

		$ua = new UserAccount($_SESSION["idUserAccount"], $this->appMultiBond);
		$success = $ua->loadPermissionList();

		if (!$success) return $this->response;

		$this->response['success'] = true;
		$this->response['recordset'] = $ua->permissionList;

		return $this->response;

	}


}
