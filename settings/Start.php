<?php

/**
 * Algumas constantes de identifica��o de ambiente e modo do sistema
 * Sistema em manuten��o: s� � poss�vel se logar como um usu�rio do tipo fSystemAdmin;
 * Sistema em debug: alertas de email s�o enviados apenas para a Saboia;
 */
define('MAINTENANCE_MODE', false);
define('DEBUG_MODE', false);




/**
 *
 */
require_once($_SERVER['SYSTEM_COMMON'].'/shared/data/multibond/MultiBond.php');
require_once($_SERVER['SYSTEM_COMMON'].'/shared/data/multibond/Filter.php');

// TO-DO: tentar solucionar erro de permiss�o de escrita no diret�rio tempor�rio de sess�o com isso:
// session_save_path('/path/to/writable/directory/inside/your/account');
ini_set('session.gc_maxlifetime', 3*60*60); // 3 hours
ini_set('session.gc_probability', 0);
ini_set('session.gc_divisor', 100);
ini_set('session.cookie_secure', FALSE);
ini_set('session.use_only_cookies', TRUE);
session_start('multibond_sample');


header('Cache-Control: no-store, private, no-cache, must-revalidate');
header('Cache-Control: pre-check=0, post-check=0, max-age=0, max-stale = 0', false);
header('Pragma: public');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
header('Expires: 0', false);
header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
header('Pragma: no-cache');



/**
 * Carregamento de idiomas
 * Array contendo os idiomas do sistema, identificados por:
 * ISO language code (http://en.wikipedia.org/wiki/List_of_ISO_639-1_codes) e
 * ISO country code (http://en.wikipedia.org/wiki/ISO_3166-1)
 */

$LANGUAGES 		  = array();
$DEFAULT_LANGUAGE = NULL;
$DICTIONARY 	  = array();

function getLanguages() {

	// vari�vel global que armazenar� os idiomas dispon�veis no sistema
	global $LANGUAGES;
	global $DEFAULT_LANGUAGE;

	$languagesFileName = $_SERVER['DOCUMENT_ROOT']."/settings/languages.lang";

	if (!file_exists($languagesFileName)) {
		echo '<p>no languages provided.</p>';
		exit();
	}

	$languagesFile 		= fopen($languagesFileName,"r");
	$languagesContents 	= fread($languagesFile, filesize($languagesFileName));
	$languagesLines 	= explode("\n", $languagesContents);
	$defaultFound		= false;

	foreach ($languagesLines as $ln) {

		$line = trim($ln);

		$separator 	= stripos($line," ");
		$key		= substr($line, 0, $separator);
		$value		= substr($line, ($separator+1));


		// o caractere "+" no in�cio do ISO language code indica o idioma padr�o
		// o caractere "-" no in�cio do ISO language code indica um idioma n�o habilitado para uso
		$isDefault = strpos($key,"+") === 0 && !$defaultFound ? true : false;
		$isHidden  = strpos($key,"-") === 0 ? true : false;


		// uma vez feita a verifica��o, retira o caractere "+" ou "-" do in�cio do ISO language code
		if (strpos($key,"+") === 0 || strpos($key,"-") === 0) {
			$key = substr($key, 1);
		}


		$DEFAULT_LANGUAGE = !$defaultFound && $isDefault ? $key : $DEFAULT_LANGUAGE;
		$defaultFound	  = !$defaultFound && $isDefault ? true : $defaultFound;


		$LANGUAGES[$key] = array(
								'code'		=> $key,
								'default'	=> $isDefault,
								'hidden'	=> $isHidden,
								'value'		=> $value
								);
	}

	// se n�o foi fornecido nenhum idioma como default (sinal de "+" antes do ISO language code),
	// torna o primeiro idioma fornecido como sendo o default
	if (!$defaultFound) {
		foreach ($LANGUAGES as &$l) {
			$l['default'] = true;
			$DEFAULT_LANGUAGE = $l['code'];
			break;
		}
	}

	fclose($languagesFile);

}

getLanguages();

define('DEFAULT_LANGUAGE', $DEFAULT_LANGUAGE);
unset($DEFAULT_LANGUAGE);



/**
 * Conex�o ao banco de dados e cria��o dos objetos de acesso ao MultiBond.
 * S�o definidos dois objetos MultiBond: um para acesso aos dados aplica��o,
 * e outro para acesso aos dados de autentica��o
 * Os par�metros de conex�o s�o obtidos do arquivo de configura��o do site no Apache
 */

$application_mb    = new MultiBond();
$authentication_mb = new MultiBond();


// MultiBond para a aplica��o
$application_mysqli = new mysqli($_SERVER['APPLICATION_DATABASEHOST'],
								 $_SERVER['APPLICATION_DATABASEUSER'],
								 $_SERVER['APPLICATION_DATABASEPASSWORD'],
								 $_SERVER['APPLICATION_DATABASENAME']);
if ($application_mysqli->connect_errno) die('<p>Could not connect to application database</p>');

$application_mb->setDatabase($application_mysqli);
$application_mb->setNamespace($_SERVER['APPLICATION_NAMESPACE']);



// MultiBond para autentica��o
$authentication_mysqli = new mysqli($_SERVER['AUTHENTICATION_DATABASEHOST'],
									$_SERVER['AUTHENTICATION_DATABASEUSER'],
									$_SERVER['AUTHENTICATION_DATABASEPASSWORD'],
									$_SERVER['AUTHENTICATION_DATABASENAME']);
if ($authentication_mysqli->connect_errno) die('<p>Could not connect to authentication database</p>');

$authentication_mb->setDatabase($authentication_mysqli);
$authentication_mb->setNamespace($_SERVER['AUTHENTICATION_NAMESPACE']);
