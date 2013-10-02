<?php

require_once($_SERVER['DOCUMENT_ROOT'].'/settings/Start.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/shared/libraries/php/core.php');
require_once($_SERVER['SYSTEM_COMMON'].'/shared/data/multibond/MultiBond.php');
require_once($_SERVER['SYSTEM_COMMON'].'/shared/data/multibond/Filter.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/shared/libraries/php/debug.php');


$starttime = microtime(true);
$debug = new Debug();


// $response armazena a resposta no formato padrão da orbtalAPI
// sempre enviada à camada cliente em JSON UTF-8
$response = array();

$response['applicationControl']['authenticated'] = false;
$response['applicationControl']['authorized'] = false;
$response['success'] = false;



// ANÁLISE DO REQUEST
// quebra o request em variáveis, para identificar qual o objeto e o método sendo chamado
$object = isset($_REQUEST['object']) ? $_REQUEST['object'] : NULL;
$method = isset($_REQUEST['method']) ? $_REQUEST['method'] : NULL;
$data 	= isset($_REQUEST['data']) 	 ? $_REQUEST['data']   : NULL;

if (is_null($object)) {
	
	$response['applicationControl']['error']['number']	= 1001;
	$response['applicationControl']['error']['type']	= 'Invalid request call';
	$response['applicationControl']['error']['message'] = 'The request is not properly formatted. Please review your application.';
	
	echo json_encode(toUTF8($response));
	exit();
}
if (is_null($method)) {
	
	$response['applicationControl']['error']['number']	= 1002;
	$response['applicationControl']['error']['type']	= 'Invalid request call';
	$response['applicationControl']['error']['message'] = 'The request is not properly formatted. Please review your application.';
	
	echo json_encode(toUTF8($response));
	exit();
}
if (is_null($data)) {
	
	$response['applicationControl']['error']['number']	= 1003;
	$response['applicationControl']['error']['type']	= 'Invalid request call';
	$response['applicationControl']['error']['message'] = 'The request is not properly formatted. Please review your application.';
	
	echo json_encode(toUTF8($response));
	exit();
}

// TO-DO: verificar se $data é uma string em formato "JSON"
// atualmente, só checamos se trata-se de uma array
if ( !is_array($data) ) {
	
	$response['applicationControl']['error']['number']	= 2001;
	$response['applicationControl']['error']['type']	= 'Invalid request parameters';
	$response['applicationControl']['error']['message'] = 'The parameters for the method you are trying to access are not properly formatted. Please review your application.';
	
	echo json_encode(toUTF8($response));
	exit();
}





// ANÁLISE DE AUTENTICAÇÃO DO USUÁRIO
// verifica se o usuário está autenticado no sistema, para então decidir quais métodos da API estão disponíveis
$userIsAuthenticated = (isset($_SESSION["UserOn"]) && $_SESSION["UserOn"] == true);
$existsInPublic      = file_exists($_SERVER['DOCUMENT_ROOT']."/shared/services/public/$object.php");
$existsInPrivate     = file_exists($_SERVER['DOCUMENT_ROOT']."/shared/services/private/$object.php");



// O REQUEST SOLICITADO EXIGE AUTENTICAÇÃO?
$filePath;
if (!$userIsAuthenticated && $existsInPublic) {
	
	// usuário não autenticado, e método público: pode prosseguir
	$response['applicationControl']['authenticated'] 	= false;
	$response['applicationControl']['authorized'] 		= true;
	
	$filePath = $_SERVER['DOCUMENT_ROOT']."/shared/services/public/";
}
else if (!$userIsAuthenticated && !$existsInPublic && $existsInPrivate) {
	
	// usuário não autenticado, e método requer autenticação: termina com mensagem de erro
	$response['applicationControl']['authenticated'] 	= false;
	$response['applicationControl']['authorized'] 		= false;
	
	$response['applicationControl']['error']['number']	= 3001;
	$response['applicationControl']['error']['type']	= 'Login/Authorization failed';
	$response['applicationControl']['error']['message'] = 'Your session has expired. Please login again into the system.';
	
	echo json_encode(toUTF8($response));
	exit();
}
else if ($userIsAuthenticated && $existsInPrivate) {
	
	// usuário autenticado, e método requer autenticação: pode prosseguir
	$response['applicationControl']['authenticated'] 	= true;
	$response['applicationControl']['authorized'] 		= true;
	
	$filePath = $_SERVER['DOCUMENT_ROOT']."/shared/services/private/";
}
else if ($userIsAuthenticated && !$existsInPrivate && $existsInPublic) {
	
	// usuário autenticado, e método público: pode prosseguir
	$response['applicationControl']['authenticated'] 	= true;
	$response['applicationControl']['authorized'] 		= true;
	
	$filePath = $_SERVER['DOCUMENT_ROOT']."/shared/services/public/";
}
else if (!$existsInPublic && !$existsInPrivate) {
	
	// método não existe: termina com mensagem de erro
	$response['applicationControl']['error']['number']	= 1004;
	$response['applicationControl']['error']['type']	= 'Invalid request call';
	$response['applicationControl']['error']['message'] = 'The request you are trying to access is not available. Please review your application.';
	
	echo json_encode(toUTF8($response));
	exit();
}





// INÍCIO DO PROCESSAMENTO MULTIBOND E API
// Verifica o namespace a partir de variáveis do Apache
if (!isset($_SERVER['APPLICATION_NAMESPACE']) || !isset($_SERVER['AUTHENTICATION_NAMESPACE'])) {
	
	// namespace não existe; aplicação não existe: termina com mensagem de erro
	$response['applicationControl']['error']['number']	= 4001;
	$response['applicationControl']['error']['type']	= 'Invalid application';
	$response['applicationControl']['error']['message'] = 'The application you are trying to access is not available.';
	
	echo json_encode(toUTF8($response));
	exit();
}






// inclui o arquivo da classe e tenta instanciar o objeto
// se $obj não for um objeto (o arquivo pode não estar corretamente formatado como um objeto PHP válido),
// ou se o método chamado não existir dentro deste objeto, retorna uma mensagem de erro
require_once($filePath."$object.php");
$obj = new $object($application_mb);

//try {
//	$obj = new $object($application_mb);
//} catch (Exception $e) {
//	$response['applicationControl']['error']['number']	= 1007;
//	$response['applicationControl']['error']['type']	= 'Invalid request call';
//	$response['applicationControl']['error']['message'] = $e->getMessage();
//	echo json_encode(toUTF8($response));
//	exit();
//}


if (!is_object($obj)) {

	$response['applicationControl']['error']['number']	= 1005;
	$response['applicationControl']['error']['type']	= 'Invalid request call';
	$response['applicationControl']['error']['message'] = 'The request you are trying to access is not available. Please review your application.';
	
	echo json_encode(toUTF8($response));
	exit();
}

if (!method_exists($obj, $method)) {

	$response['applicationControl']['error']['number']	= 1006;
	$response['applicationControl']['error']['type']	= 'Invalid request call';
	$response['applicationControl']['error']['message'] = 'The request you are trying to access is not available. Please review your application.';
	
	echo json_encode(toUTF8($response));
	exit();
}






// antes de qualquer coisa, tenta realizar o login do usuário através de cookies!
// require_once($_SERVER['DOCUMENT_ROOT'].'/shared/model/private/Authentication.php');
// $auth = new Authentication($mb);
// $success = $auth->loginByCookie();





// se chegou até aqui, executa o método no objeto, e retorna o resultado
// através do $response, no formato padrão da orbtalAPI em JSON UTF-8
$success = $obj->$method($data);
foreach ((array) $success as $key => $value) { $response[$key] = $value; }

$endtime = microtime(true);
$totaltime = ($endtime - $starttime);
$response['applicationControl']['executiontime'] = $totaltime;

if (DEBUG_MODE) {
	$dump = $debug->dump();
	if (count($dump)>0) { $response['debug'] = $dump; }
}

echo json_encode(toUTF8($response));
exit();
