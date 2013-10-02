<?php

/* -------------------------------------------------------------------------------------------------
 core.php
 @author Saboia Tecnologia da Informa��o <relacionamento@saboia.com.br>
 @version 1.0
 usu�rio da �ltima altera��o: rafael.henrique
 data da �ltima altera��o: 02/08/2011 09:49
-------------------------------------------------------------------------------------------------*/



/* -------------------------------------------------------------------------------------------------
 Fun��es:		begin(), commit(), rollback()
 Objetivo:	Usadas para transa��es em bancos de dados InnoDB
 Retorno:
-------------------------------------------------------------------------------------------------*/

function begin() 	{ mysql_query("BEGIN"); }
function commit() 	{ mysql_query("COMMIT"); }
function rollback() { mysql_query("ROLLBACK"); }

/* -------------------------------------------------------------------------------------------------
 Fun��o:		m($tokenMSG:String, $replace:Array)
 Objetivo:	Converte um token ($tokenMSG) em seu texto correspondente, seguindo o sistema de tradu��o
				de mensagens no sistema, podendo receber par�metros ($replace) para substitui��o no texto traduzido
 Retorno:		String traduzida, ou o pr�prio token
-------------------------------------------------------------------------------------------------*/

function m($tokenMSG, $replace=null) {

	global $DICTIONARY;

	$message;
	$pattern = array();

	if (array_key_exists($tokenMSG, $DICTIONARY)) {
		$message = $DICTIONARY[$tokenMSG];
	} else {
		$message = $tokenMSG;
	}

	// se forem passados par�metros de substitui��o ($replace) para a fun��o, procura
	// por padr�es de par�metro na string da mensagem e as armazena na array $pattern
	if (!is_null($replace) && is_array($replace)) {

		preg_match_all("/(%\d+%)/", $message, $matches, PREG_PATTERN_ORDER);

		foreach ($matches[0] as $val) {
			$pattern[] = $val;
		}

		for ( $i=0; $i<count($pattern); $i++ ) {

			$index 	= intval(str_replace('%', '', $pattern[$i]),10)-1;

			if (array_key_exists($index, $replace)) {

				$what 	= $pattern[$i];
				$for	= $replace[$index];
				$message = str_replace($what, $for, $message);

			}

		}

	}

	return $message;
}



/* ----------------------------------------------------------------------------------------------
 Fun��o:		loadLanguage($languageCode:String)
 Objetivo:	Carrega o arquivo de tradu��o identificado pelo $languageCode
 Retorno:		Boolean
-------------------------------------------------------------------------------------------------*/

function loadLanguage($languageCode) {

	// vari�vel global que armazenar� o dicion�rio completo carregado, em formato de Array
	global $DICTIONARY;

	$DICTIONARY = array();

	// dicion�rios tempor�rios
	$DEFAULT_DICTIONARY  = array();
	$SELECTED_DICTIONARY = array();
	$DEFAULT_MISSING_DICTIONARY  = array();
	$SELECTED_MISSING_DICTIONARY = array();

	// lista de entradas n�o encontradas em cada dicion�rio
	$DEFAULT_MISSING  = array();
	$SELECTED_MISSING = array();

	// nomes dos arquivos dos idiomas DEFAULT e SELECIONADO pelo usu�rio
	$defaultFileName  = "languages/".DEFAULT_LANGUAGE.".lang";
	$selectedFileName = "languages/".$languageCode.".lang";



	// carrega primeiramente o dicion�rio do idioma DEFAULT
	if (file_exists($defaultFileName)) {

		$defaultFile 		= fopen($defaultFileName,"r");
		$defaultContents 	= fread($defaultFile, max(filesize($defaultFileName),1));
		$defaultLines 		= explode("\n", $defaultContents);

		foreach ($defaultLines as $ln) {

			$line = trim($ln);

			$separator 	= stripos($line," ");
			$separator 	= $separator ? $separator : strlen($line);
			$key		= substr($line, 0, $separator);
			$value		= substr($line, ($separator+1));

			$key		= trim($key);
			$value		= trim($value);
			if ($key === "") continue;

			$DEFAULT_DICTIONARY[$key] = $value;
		}

		fclose($defaultFile);
	}

	// temos at� aqui um dos dois cen�rios abaixo:
	// 1) array "$DEFAULT_DICTIONARY" carregada com o idioma DEFAULT, para servir de fallback para algum token inexistente no idioma selecionado;
	// 2) array "$DEFAULT_DICTIONARY" vazia, pois o idioma DEFAULT n�o foi encontrado.
	// e array "$DICTIONARY" ainda vazia.


	// carrega agora o dicion�rio do idioma SELECIONADO (apenas se for diferente do idioma DEFAULT)
	if (file_exists($selectedFileName) && $defaultFileName !== $selectedFileName) {

		$selectedFile 		= fopen($selectedFileName,"r");
		$selectedContents 	= fread($selectedFile, max(filesize($selectedFileName),1));
		$selectedLines 		= explode("\n", $selectedContents);

		foreach ($selectedLines as $ln) {

			$line = trim($ln);

			$separator 	= stripos($line," ");
			$separator 	= $separator ? $separator : strlen($line);
			$key		= substr($line, 0, $separator);
			$value		= substr($line, ($separator+1));

			$key		= trim($key);
			$value		= trim($value);
			if ($key === "") continue;

			$SELECTED_DICTIONARY[$key] = $value;
		}

		fclose($selectedFile);
	}

	// temos at� aqui um dos dois cen�rios abaixo:
	// 1) array "$SELECTED_DICTIONARY" carregada com o idioma SELECIONADO;
	// 4) array "$SELECTED_DICTIONARY" vazia, pois o idioma SELECIONADO n�o foi encontrado.
	// e array "$DICTIONARY" ainda vazia.



	// compara as entradas presentes nos dois arquivos de idioma, enquanto monta a array "$DICTIONARY"
	// se houver diferen�a, ser� necess�rio escrever um arquivo .missing, apontando as entradas n�o listadas em cada arquivo.
	// para a compara��o, usa os dicion�rios tempor�rios DEFAULT_MISSING_DICTIONARY e SELECTED_MISSING_DICTIONARY

	// carrega primeiramente o dicion�rio DEFAULT_MISSING_DICTIONARY do idioma DEFAULT

	$fileName = "languages/".DEFAULT_LANGUAGE.".missing";
	if (file_exists($fileName)) {

		$file 		= fopen($fileName,"r");
		$contents 	= fread($file, filesize($fileName));
		$lines 		= explode("\n", $contents);

		foreach ($lines as $ln) {

			$line = trim($ln);

			$separator 	= stripos($line," ");
			$separator 	= $separator ? $separator : strlen($line);
			$key		= substr($line, 0, $separator);
			$value		= substr($line, ($separator+1));

			$key		= trim($key);
			$value		= trim($value);
			if ($key === "") continue;

			$DEFAULT_MISSING_DICTIONARY[$key] = $value;
		}

		fclose($file);
	}


	// carrega ent�o o dicion�rio SELECTED_MISSING_DICTIONARY do idioma SELECTED

	$fileName = "languages/".$languageCode.".missing";
	if (file_exists($fileName)) {

		$file 		= fopen($fileName,"r");
		$contents 	= fread($file, filesize($fileName));
		$lines 		= explode("\n", $contents);

		foreach ($lines as $ln) {

			$line = trim($ln);

			$separator 	= stripos($line," ");
			$separator 	= $separator ? $separator : strlen($line);
			$key		= substr($line, 0, $separator);
			$value		= substr($line, ($separator+1));

			$key		= trim($key);
			$value		= trim($value);
			if ($key === "") continue;

			$SELECTED_MISSING_DICTIONARY[$key] = $value;
		}

		fclose($file);
	}


	if ( count($DEFAULT_DICTIONARY) > 0 ) {

		foreach ($DEFAULT_DICTIONARY as $key => $value) {

			$DICTIONARY[$key] = $value;

			if ( !array_key_exists($key, $SELECTED_DICTIONARY) && !array_key_exists($key, $SELECTED_MISSING_DICTIONARY) ) {

				$SELECTED_MISSING[$key] = $value;

			}
		}
	}

	if ( count($SELECTED_DICTIONARY) > 0 ) {

		foreach ($SELECTED_DICTIONARY as $key => $value) {

			$DICTIONARY[$key] = $value;

			if ( !array_key_exists($key, $DEFAULT_DICTIONARY) && !array_key_exists($key, $DEFAULT_MISSING_DICTIONARY) ) {

				$DEFAULT_MISSING[$key] = $value;

			}
		}
	}

	// temos at� aqui um dos tr�s cen�rios abaixo:
	// 1) array "$DICTIONARY" carregada com o idioma SELECIONADO incompleto, com fallback para o idioma DEFAULT;
	// 2) array "$DICTIONARY" carregada com o idioma SELECIONADO completo, com mais entradas do que o idioma DEFAULT (que parece estar incompleto);
	// 3) array "$DICTIONARY" carregada com um mix do idioma SELECIONADO incompleto e do idioma DEFAULT incompleto.
	// com isso, o "$DICTIONARY" est� o mais pronto poss�vel neste momento.

	// al�m disso:
	// 1) array "$DEFAULT_MISSING" contendo as entradas n�o encontradas no idioma DEFAULT;
	// 2) array "$SELECTED_MISSING" contendo as entradas n�o encontradas no idioma SELECIONADO.


	// tenta gravar o arquivo .missing de cada idioma, com as entradas n�o encontradas

	if ( count($DEFAULT_MISSING) > 0 ) {

		$fileName = "languages/".DEFAULT_LANGUAGE.".missing";

		$file = @fopen($fileName,"a");
		if ($file) {
			foreach ($DEFAULT_MISSING as $key => $value) {
				writeToken(DEFAULT_LANGUAGE, $key, $value);
			}
			fclose($file);
		}
	}

	if ( count($SELECTED_MISSING) > 0 ) {

		$fileName = "languages/".$languageCode.".missing";

		$file = @fopen($fileName,"a");
		if ($file) {
			foreach ($SELECTED_MISSING as $key => $value) {
				writeToken($languageCode, $key, $value);
			}
			fclose($file);
		}
	}

	unset($DEFAULT_MISSING);
	unset($SELECTED_MISSING);
	unset($DEFAULT_DICTIONARY);
	unset($SELECTED_DICTIONARY);
	unset($DEFAULT_MISSING_DICTIONARY);
	unset($SELECTED_MISSING_DICTIONARY);
	unset($defaultFileName);
	unset($selectedFileName);

}



// -------------------------------------------------------------------------------------------------
// Fun��o:		writeToken($languageCode:String, $tokenMSG:String, $value:String)
// Objetivo:	Escreve no arquivo de tradu��o identificado pelo $languageCode uma entrada do $tokenMSG
// Retorno:		Boolean
// -------------------------------------------------------------------------------------------------

function writeToken($languageCode, $tokenMSG, $value='') {

	// verifica se o par�metro $tokenMSG est� num formato aceito de token
	// (string sem espa�os, composta apenas por caracteres alfanum�ricos mai�sculos e "_")

	if (preg_match("/^([A-Z0-9_]*)$/", $tokenMSG) === 0) return false;
	if ($languageCode === "") return false;

	$fileName = "languages/".$languageCode.".missing";
	$file = fopen($fileName,"a");
	if (is_writable($fileName)) fwrite($file, $tokenMSG." ".$value."\n\r");

	fclose($file);

	return true;

}



// -------------------------------------------------------------------------------------------------
// Fun��o:		to_utf8($input:String ou Array)
// Objetivo:	Usadas para trasformar strings ou arrays em formato UTF-8, para padronizar
// 				os textos do banco de dados com os textos enviados via jQuery/AJAX/JSON
// Retorno:		String ou Array convertida para UTF-8
// -------------------------------------------------------------------------------------------------
function to_utf8($input)
{
	$output;

	if (is_array($input)) {

		foreach ($input as $key => $value) {
			$output[to_utf8($key)] = to_utf8($value);
		}

	} elseif(is_string($input)) {

			return utf8_encode($input);

	} else {

		return $input;

	}
	return $output;
}

// nova vers�o da fun��o para convers�o de valores para UTF8:
// nesta vers�o: se a fun��o for aplicada em uma array, todos os valores ser�o convertidos
//				 se a fun��o for aplicada em uma string j� UTF8, n�o ser� convertida
function toUTF8($input) {

	$output;

	if ((is_array($input) && count($input)>0) || is_object($input)) {

		foreach ($input as $key => $value) {
			$output[toUTF8($key)] = toUTF8($value);
		}

	} elseif (is_string($input)) {

		if (!checkUTF8($input)) {
			$output = mb_convert_encoding($input,'UTF-8');
		}
	}

	return (isset($output) ? $output : $input);
}

// verifica se o parametro $input esta em UTF-8

function checkUTF8($input) {

	if ( mb_detect_encoding($input,'UTF-8',true) ) {
		return true;
	} else {
		return false;
	}
}



// valida se um valor � vazio
// entendendo como n�o-vazios os seguintes valores:
// 0 	(0 as an integer)
// 0.0 	(0 as a float)
// "0" 	(0 as a string)
// return Boolean
function isBlank($input) {
	$input 	= trim($input);
	$output = empty($input) && !is_numeric($input);
    return $output;
}

// sanitiza quotes de strings para prevenir SQL injection
// return String/Int/Float
function sanitizeSQL($input) {

	$output;

	if (is_array($input) && count($input)>0) {

		foreach ($input as $key => $value) {
			$output[sanitizeSQL($key)] = sanitizeSQL($value);
		}

	} elseif (is_string($input)) {

		$output = filter_var($input,  FILTER_SANITIZE_MAGIC_QUOTES);
		// n�o limpamos HTML tags! em princ�pio, devemos gravar EXATAMENTE o que o usu�rio enviou
		// a limpeza destes dados ser� feita apenas na hora de exibir em tela.
	//	$output = filter_var($output, FILTER_SANITIZE_SPECIAL_CHARS);

	} else {

		$output = $input;
	}

	return $output;

}

// retorna string formatada para ser usada em queries SQL
// assume que ser�o usadas " (aspas duplas) para delimitar valores de string no SQL
// usada apenas nas fun��es que escrevem as queries, e n�o antes disso, para n�o interferir nos c�lculos que podem ser realizados com as propriedades dos objetos
// return String/Int/Float
function prepareSQL($input) {

	$output;

	if (is_array($input) && count($input)>0) {

		foreach ($input as $key => $value) {
			$output[prepareSQL($key)] = prepareSQL($value);
		}

	} elseif (is_array($input) && count($input)==0) {

		$output = $input;

	} elseif (is_numeric($input)) {

		// check for float
		if ((string)(float)$input === (string)$input) $output = $input;

		// check for int
		if ((string)(int)$input === (string)$input) $output = $input;

		// check for string containing number (treat as string)
		else $output = '"'.sanitizeSQL($input).'"';

	} elseif (is_string($input)) {

		if (strtolower($input) == 'null') $output = 'NULL';
		else 							  $output = '"'.sanitizeSQL($input).'"';

	} else {

		$output = 'NULL';
	}

	return $output;
}

// check for a specific $param in an $parameters array
// and get its value, or a $defaultValue if not found
// used vastly in Object's methods which require a lot of parameters...
// return String/Int/Float
function getParameter($param, array $parameters, $defaultValue=NULL) {

	if (is_array($parameters) && count($parameters)>0) {

		if (array_key_exists($param, $parameters)) {

			if (!is_array($parameters[$param]) && !isBlank($parameters[$param])) {

				return $parameters[$param];

			} else if (!is_array($parameters[$param]) && isBlank($parameters[$param])) {

				return $defaultValue;

			} else {

				return $parameters[$param];
			}


		} else {

			return $defaultValue;
		}

	} else {

		return $defaultValue;
	}
}




// -------------------------------------------------------------------------------------------------
// Fun��o:		escapeInt($value:Int)
// Objetivo:	escape de textos e valores recebidos do PHP para prepar�-los para o banco de dados
// Retorno:		Int
// -------------------------------------------------------------------------------------------------
function escapeInt($value)
{
	if (is_null($value)) { return 'NULL'; }
	return is_array($value) ? array_map('escapeInt', $value) : mysql_real_escape_string($value);
}



// -------------------------------------------------------------------------------------------------
// Fun��o:		escapeStr($value:String)
// Objetivo:	escape de textos e valores recebidos do PHP para prepar�-los para o banco de dados
// Retorno:		String, entre aspas (")
// -------------------------------------------------------------------------------------------------

function escapeStr($value)
{
	if (is_null($value)) { return 'NULL'; }
	return is_array($value) ? array_map('escapeStr', $value) : '"'.mysql_real_escape_string( filter_var($value,FILTER_SANITIZE_SPECIAL_CHARS) ).'"';
}



// -------------------------------------------------------------------------------------------------
// Fun��o:		VerifyCNPJ($strCNPJ:String)
// Objetivo:	retorna o n�mero de CNPJ v�lido, calculando seus d�gitos de verifica��o
// Par�metros:	$strCNPJ:String contendo o CNPJ a ser verificado, apenas n�meros, sem formata��o
// Retorno:		String contendo CNPJ v�lido, com d�gitos de verifica��o
// -------------------------------------------------------------------------------------------------

function VerifyCNPJ($strCNPJ)
{
	// retorna os 12 primeiros caracteres, colocando zeros � esquerda se necess�rio
	// os 12 caracteres iniciais representam o n�mero do CNPJ sem os d�gitos de verifica��o
	$strCNPJ = str_repeat("0", 12) . $strCNPJ;
	$strCNPJ = substr($strCNPJ, -12, 12);

	// calcula o d�gito verificador de CNPJ
	for($i = 1; $i <= 2; $i++) {

		// calcula somat�rio...
		$intDigito = 0;
		$intLength = strlen($strCNPJ);

		for ($j = 1; $j <= $intLength; $j++) {

			if($j > 8)  {
				$intFator = $j - 7;
			} else {
				$intFator = $j + 1;
			}

			$intNum = intval(substr($strCNPJ, ($intLength - $j), 1));
			$intDigito = $intDigito + ($intNum * $intFator);
		}

		// Divide por 11, obtem modulo e calcula o digito ...
		$intDigito = 11 - ($intDigito % 11);

		if($intDigito > 9) {
			$intDigito = 0;
		}

		// Inclui este digito na string e prossegue ...
		if($intDigito == "") {
			$strCNPJ = $strCNPJ."0";

		} else {
			$strCNPJ = $strCNPJ.substr(strval($intDigito),0,1);
		}

	}

	// Retorna o CNPJ completo, nao formatado ...
	return $strCNPJ;
}



// -------------------------------------------------------------------------------------------------
// Fun��o:		IsCNPJ($strCNPJ:String)
// Objetivo:	verificar se um n�mero de CNPJ � v�lido
// Par�metros:	$strCNPJ:String contendo o CNPJ a ser verificado, (tanto apenas n�meros sem formata��o ou string formatada)
// Retorno:		Boolean
// -------------------------------------------------------------------------------------------------

function IsCNPJ($strCNPJ)
{
	// retira da string do CNPJ os caracteres de formata��o e pontua��o
	$strCNPJ = str_replace("/", "", str_replace("-", "", str_replace(".", "", $strCNPJ)));

	if(strlen($strCNPJ) != 14) {
		return false;
	}

	if (VerifyCNPJ(substr($strCNPJ, 0, 12)) == $strCNPJ) {
		return true;
	} else {
		return false;
	}

}



// -------------------------------------------------------------------------------------------------
// Fun��o:		VerifyCPF($strCPF:String)
// Objetivo:	retorna o n�mero de CNPJ v�lido, calculando seus d�gitos de verifica��o
// Par�metros:	$strCPF:String contendo o CPF a ser verificado, apenas n�meros, sem formata��o
// Retorno:		String contendo CPF v�lido, com d�gitos de verifica��o
// -------------------------------------------------------------------------------------------------

function VerifyCPF($strCPF)
{
	$lngSoma    = 0;
	$intResto   = 0;
	$intDigito1 = 0;
	$intDigito2 = 0;

	$strCPF = str_repeat("0", 9) . $strCPF;

	$strCPF = substr($strCPF, -9, 9);

	// Calcula o 1o. digito...
	$lngSoma =            intval( substr( $strCPF, 0, 1 ) ) * 10 + intval( substr( $strCPF, 1, 1 ) ) * 9;
	$lngSoma = $lngSoma + intval( substr( $strCPF, 2, 1 ) ) *  8 + intval( substr( $strCPF, 3, 1 ) ) * 7;
	$lngSoma = $lngSoma + intval( substr( $strCPF, 4, 1 ) ) *  6 + intval( substr( $strCPF, 5, 1 ) ) * 5;
	$lngSoma = $lngSoma + intval( substr( $strCPF, 6, 1 ) ) *  4 + intval( substr( $strCPF, 7, 1 ) ) * 3;
	$lngSoma = $lngSoma + intval( substr( $strCPF, 8, 1 ) ) *  2;

	$intResto = $lngSoma - (intval($lngSoma/11) * 11);

	if($intResto < 2) {
		$intDigito1 = 0;
	} else {
		$intDigito1 = 11 - $intResto;
	}

	// Calcula o 2o. digito...
	$lngSoma =            intval( substr( $strCPF, 0, 1 ) ) * 11 + intval( substr( $strCPF, 1, 1 ) ) * 10;
	$lngSoma = $lngSoma + intval( substr( $strCPF, 2, 1 ) ) *  9 + intval( substr( $strCPF, 3, 1 ) ) *  8;
	$lngSoma = $lngSoma + intval( substr( $strCPF, 4, 1 ) ) *  7 + intval( substr( $strCPF, 5, 1 ) ) *  6;
	$lngSoma = $lngSoma + intval( substr( $strCPF, 6, 1 ) ) *  5 + intval( substr( $strCPF, 7, 1 ) ) *  4;
	$lngSoma = $lngSoma + intval( substr( $strCPF, 8, 1 ) ) *  3 + $intDigito1 * 2;

	$intResto = $lngSoma - (intval($lngSoma/11) * 11);

	if($intResto < 2) {
		$intDigito2 = 0;
	} else {
		$intDigito2 = 11 - $intResto;
	}

	$strCPF = $strCPF . substr(strval($intDigito1), 0, 1) . substr(strval($intDigito2), 0, 1);

	return $strCPF;
}



// -------------------------------------------------------------------------------------------------
// Fun��o:		IsCPF($strCPF:String)
// Objetivo:	verificar se um n�mero de CPF � v�lido
// Par�metros:	$strCPF:String contendo o CPF a ser verificado, (tanto apenas n�meros sem formata��o ou string formatada)
// Retorno:		Boolean
// -------------------------------------------------------------------------------------------------

function IsCPF($strCPF)
{
	$strCPF = str_replace("-", "", str_replace(".", "", $strCPF));

	if(strlen($strCPF) != 11) {
		return false;
	}

	if(VerifyCPF(substr($strCPF, 0, 9)) == $strCPF) {
		return true;
	} else {
		return false;
	}
}







// -------------------------------------------------------------------------------------------------
// Fun��o:		floatToMoney($value)
// Objetivo:	transforma uma String no formato '12350.25' (float) para '12.350,25' (money)
// Exemplo:		floatToMoney(12350.25);
// Retorno:		String
// -------------------------------------------------------------------------------------------------

function floatToMoney($value)
{
	$integer = null;
	$decimal = null;
	$c		 = null;
	$j		 = null;
	$aux	 = array();

	$value = strval($value);


	// caso haja pontos na string, separa as partes em inteiro e decimal:
	$c = strpos($value, '.');

	if ($c > 0) {
		$integer = substr($value, 0, $c);
		$decimal = substr($value, $c+1, strlen($value));
	} else {
		$integer = $value;
	}


	// acrescenta separador de milhar no valor inteiro
	$aux = str_split($integer);
	$integer = '';
	$auxInteger = '';
	$count = 0;

	for ($j=(count($aux)-1); $j>=0; $j--) {

		$count++;
		$auxInteger = $aux[$j].$integer;
		$integer = $auxInteger;

		if($count == 3) {
			$auxInteger = '.'.$integer;
			$integer = $auxInteger;
			$count = 0;
		}

	}

	// tratamento do valor decimal, garantindo sempre duas casas decimais
	$decimal = intval($decimal, 10);

	if(is_nan($decimal)) {
		$decimal = '00';
	} else {
		$decimal = strval($decimal);
		if (strlen($decimal) === 1) {
			$decimal = $decimal.'0';
		}
	}

	$value = $integer.','.$decimal;

	return $value;
}



?>
