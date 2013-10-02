<?php
/**
 * A classe Settings fornece um objeto para armazenar configurações e constantes utilizadas na aplicação
 */

class Settings {

	/**
	 * salt usado para hash de informações sensíveis no sistema
	 * ATENÇÃO: ao alterar o salt, todass as informações já armazenadas serão inutilizadas!
	 */
	const SALT = 'p53lbLdA8D3SRvld0wkvVw2OykrZswTgC4UhKvwN';


	/**
	 * máximo de tentativas de login incorretas, antes de bloquear o acesso do usuário
	 */
	const MAX_LOGIN_ATTEMPTS = 10000;


	/**
	 * códigos e mensagens de erro
	 */
	const ERROR_WRONG_LOGIN_NUMBER      = 1001;
	const ERROR_WRONG_LOGIN_TYPE        = 'Login/Authorization failed';
	const ERROR_WRONG_LOGIN_MESSAGE     = 'The login/password combination provided do not match.';

	const ERROR_EXPIRED_SESSION_NUMBER  = 1002;
	const ERROR_EXPIRED_SESSION_TYPE    = 'Session expired';
	const ERROR_EXPIRED_SESSION_MESSAGE = 'Your session has expired. Please log in again.';

	const ERROR_SYSTEM_OFFLINE_NUMBER   = 2001;
	const ERROR_SYSTEM_OFFLINE_TYPE     = 'System offline/in maintenance';
	const ERROR_SYSTEM_OFFLINE_MESSAGE  = 'The system is going through a maintenance process. Please try again later.';



	/**
	 * utilizado para traduzir as propiedades de lista de um determinado modulo
	 * module = chave para identificar a lista
	 */
	static public function load_staticlist( $module = "" ){
		switch( $module ){
			case "REQUEST_STATUS":
				return array(
					  "IDENTIFIED" 				=> 1 //IDENTIFICADO
					, "OPEN" 					=> 2 //EM ANDAMENTO
					, "PENDING_WITH_THE_CLIENT"	=> 3 //COM PENDÊNCIA DO CLIENTE
					, "CLOSED"					=> 4 //CONCLUÍDO
					, "CANCELLED"				=> 5 //CANCELADO
				) ;
			break;
			case "PARTNER_STATUS":
				return array(
					  "ACTIVE" 	=> 1 //ATIVO
					, "PENDING" => 2 //PENDENTE
					, "INACTIVE"=> 3 //INATIVO
				) ;
			break;
			default:
				return array() ;
			break;
		}
	}
}
