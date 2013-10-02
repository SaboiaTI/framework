<?php
/**
 * Classe que permite manipular um Object
 * No MultiBond, um Object é uma unidade de informação, geralmente representado por um objeto PHP na camada de regras de negócio.
 * É este objeto que, em conjunto com suas Properties, forma uma unidade de informação coerente (um registro)
 * a saber: Object <---> Tie <---> Bond <---> Tie <---> Object
 */

require_once($_SERVER['DOCUMENT_ROOT']."/shared/libraries/php/core.php");
require_once($_SERVER['SYSTEM_COMMON']."/shared/data/multibond/MultiBond.php");

class MultiBondObject {
	
	// status de carregamento dos dados do objeto
	const NOT_LOADED	= 0;
	const LOADED 		= 1;
	const EMPTY_LOADED 	= 2;
	
	
	protected $multiBond;
	protected $id;
	
	protected $idSCHEMAObject;
	protected $idGLOBALNamespace;
	
	protected $uniqueUserId;
	protected $tsCreation;
	protected $tsLastUpdate;
	protected $iTransaction;
	protected $fArchived;
	
	protected $properties;			// os valores e informações de todas as propriedades do objeto
	private   $templateProperties;	// um modelo das informações de todas as propriedades possíveis para o objeto
									// usada para salvar um novo valor para uma propriedade que aceite múltiplos valores
	
	protected $objectStatus;		// status de carregamento de dados do objeto
	protected $secretProperties;	// propriedades do Object que não podem ser acessadas de fora dele, através do método expose()
	
	
	
	
	public function __construct($id=NULL, $SCHEMAObject=NULL, MultiBond $mb, $autoLoad=true) {
		
		if ( is_null($id) && is_null($SCHEMAObject) ) throw new Exception('Could not finish constructing MultiBondObject');
		
		// primeiramente, é necessário que exista um MultiBond válido associado a este objeto 
		// para que ele tenha acesso a funções básicas de validação e consulta ao SCHEMA
		
		$this->setMultiBond($mb);
		
		// existindo um MultiBond, a consulta ao SCHEMA pode ser realizada, e os dados de SCHEMAObject e namespace
		// podem ser atribuídos corretamente; do contrário, os valores de SCHEMAObject e namespace serão NULL
		// procedimento: o parâmetro SCHEMAObject deve ser uma string;
		//               essa string é procurada na tabela de SCHEMA, e retornamos seu id
		//               se for encontrado o id, a string é salva na propriedade $this->SCHEMAObject
		//               e o id é salvo na propriedade $this->idSCHEMAObject
		
		$idSCHEMAObject = NULL;
		$idGLOBALNamespace = NULL;
		$autoLoad = filter_var($autoLoad, FILTER_VALIDATE_BOOLEAN);
		
		if (!is_null($this->multiBond) && !is_null($SCHEMAObject)) {
			
			$strSCHEMAObject = filter_var($SCHEMAObject, FILTER_SANITIZE_STRING);
			
			$idSCHEMAObject  = $this->multiBond->getSCHEMAObjectId($strSCHEMAObject);
			$idSCHEMAObject  = isset($idSCHEMAObject[0]) && count($idSCHEMAObject[0])===1 ? $idSCHEMAObject[0][0] : NULL;
			
			$idGLOBALNamespace  = $this->multiBond->getNamespaceId($this->multiBond->getNamespace());
			
			if ( is_null($idGLOBALNamespace) && is_null($idSCHEMAObject) ) throw new Exception('Could not finish constructing MultiBondObject');
		}
		
		$this->id = $id;
		$this->idSCHEMAObject = $idSCHEMAObject;
		$this->idGLOBALNamespace = $idGLOBALNamespace;
		
		$this->uniqueUserId = NULL;
		$this->tsCreation   = NULL;
		$this->tsLastUpdate = NULL;
		$this->iTransaction = NULL;
		$this->fArchived    = NULL;
		
		$this->properties         = array();
		$this->templateProperties = array();
		
		$this->objectStatus	= self::NOT_LOADED;
		
		$this->secretProperties = array(
			'multiBond', 
			'idSCHEMAObject', 
			'idGLOBALNamespace', 
			'uniqueUserId',
		//	'tsCreation',
		//	'tsLastUpdate',
			'iTransaction',
			'fArchived',
			'properties',
			'templateProperties',
			'objectStatus',
			'secretProperties', 
			);
			
			
		
		
		
		
		
		// se já houverem dados suficientes, tenta montar o Object básico 
		// trazendo mapeadas as Properties possíveis deste Object, ainda com valor NULL
		if ($autoLoad) {
			if ( !is_null($this->multiBond) && !is_null($this->id) && !is_null($this->idSCHEMAObject) ) {
				
				$success = $this->load();
				if (!$success) throw new Exception('Could not finish constructing MultiBondObject');
				
			} elseif ( !is_null($this->multiBond) && !is_null($this->id) && is_null($this->idSCHEMAObject) ) {
				
				$success = $this->load();
				if (!$success) throw new Exception('Could not finish constructing MultiBondObject');
				
			} elseif ( !is_null($this->multiBond) && is_null($this->id) && !is_null($this->idSCHEMAObject) ) {
				
				$success = $this->loadEmpty();
				if (!$success) throw new Exception('Could not finish constructing MultiBondObject');
			}
		}
		
	}
	
	
	
	
	
	/**
	 * expõe para leitura algumas propriedades desta classe definidas como protected ou private
	 * faz tratamentos e validação de alguns valores antes de retornar
	 * @return mixed property value if valid; NULL otherwise
	 */
	public function __get ($propertyName) {
		
		if (
			   $propertyName === 'id' 
			|| $propertyName === 'multiBond' 
			|| $propertyName === 'uniqueUserId' 
			|| $propertyName === 'tsCreation' 
			|| $propertyName === 'tsLastUpdate' 
			|| $propertyName === 'iTransaction' 
			|| $propertyName === 'fArchived' 
			|| $propertyName === 'objectStatus' 
		) {
			return $this->$propertyName;
		}
		elseif ($propertyName === 'idSCHEMAObject' || $propertyName === 'SCHEMAObject') {
			if (!is_null($this->multiBond)) {
			
				$schema = $this->multiBond->getSCHEMAObjectName($this->idSCHEMAObject);
				$schema = isset($schema[0]) ? $schema[0] : NULL;
				return $schema;
				
			} else {
				return false;
			}
		}
		elseif ($propertyName === 'idGLOBALNamespace' || $propertyName === 'GLOBALNamespace') {
			
			if (!is_null($this->multiBond)) {
			
				$nameSpace = $this->multiBond->getNamespaceName($this->idGLOBALNamespace);
				$nameSpace = isset($nameSpace[0]) ? $nameSpace[0] : NULL;
				return $nameSpace;
				
			} else {
				return false;
			}
		}
		elseif (array_key_exists($propertyName, $this->properties)) {
			
			if ($this->properties[$propertyName][0]['fMultiple']==0) {
				return $this->properties[$propertyName][0]['sValue'];
				
			} else {
				$props = array();
				
				foreach($this->properties[$propertyName] as $p) {
					$props[(string)$p['id']] = $p['sValue'];
				}
				
				return $props;
			}
		}
		
		else {
			return NULL;
		}
    }
	
	
	
	
	
	/**
	 * expõe para gravação algumas propriedades desta classe definidas como protected ou private
	 * faz tratamentos e validação dos valores antes de atribuir às propriedades
	 * @return boolean
	 */
	public function __set($propertyName, $value) {
		
		if ($propertyName === 'id') {
			
			$value = filter_var($value, FILTER_SANITIZE_NUMBER_INT);
			
			if (is_int($value)) {
				
				$this->$propertyName = $value;
				return true;
				
			} else {
				
				$this->$propertyName = NULL;
				return false;
			}
		}
		else if ($propertyName === 'multiBond') {
			return setMultiBond($value);
		}
		else if ($propertyName === 'idSCHEMAObject' || $propertyName === 'SCHEMAObject') {
			
			if (is_null($this->multiBond)) return false;
			
			if (is_string($value)) {
				
				$strSCHEMAObject = filter_var($value, FILTER_SANITIZE_STRING);
				$idSCHEMAObject  = $this->multiBond->getSCHEMAObjectId($strSCHEMAObject);
				$idSCHEMAObject  = isset($idSCHEMAObject[0]) && count($idSCHEMAObject[0])===1 ? $idSCHEMAObject[0][0] : NULL;
				
				$this->idSCHEMAObject = $idSCHEMAObject;
				return is_null($this->idSCHEMAObject);
				
			} else {
				
				$this->idSCHEMAObject = NULL;
				return false;
			}
		}
		else {
			
			if ($this->properties[$propertyName][0]['fMultiple']==0) {
				// atualiza o único valor, já que é uma propriedade não múltipla
				return $this->setProperty($propertyName, $value);
				
			} else {
				// inclui um novo valor, já que é uma propriedade múltipla e um id não foi especificado
				// para alterar um valor existente, é necessário especificar um id, usando diretamente o método setProperty()
				return $this->setProperty($propertyName, NULL, $value);
			}
			
			return false;
		}
	}
	
	
	
	
	
	/**
	 * Associa este Object a uma instância da classe MultiBond, que
	 * contém as informações do SCHEMA, necessárias para as funções 
	 * que buscam Bonds entre objetos
	 * @return boolean
	 */
	public function setMultiBond(MultiBond $mb=NULL) {
	
		if (!is_null($mb) && ($mb instanceof MultiBond) && !is_null($mb->db)) {
			
			$this->multiBond = $mb;
			
			$nameSpace = $this->multiBond->getNamespace();
			return $this->setNamespace($nameSpace);
			
		} else {
			return false;
		}
	}
	
	
	
	
	/**
	 * Associa este Object a um namespace
	 * função chamada automaticamente ao se definir um multiBond
	 * @return boolean
	 */
	protected function setNamespace($GLOBALNamespace) {
		
		if (is_null($this->multiBond)) return false;
		if (is_null($GLOBALNamespace)) return false;
		
		$valid = $this->multiBond->validateNamespace($GLOBALNamespace);
		
		if (!$valid) return false;
		
		$this->idGLOBALNamespace = $this->multiBond->getNamespaceId($GLOBALNamespace);
		
		return true;
	}
	
	
	
	
	
	/**
	 * retorna as propriedades não secretas deste objeto, para exposição de dados pela API, 
	 * ou mesmo para uso por outros controles que precisem de todos os dados das propriedades deste objeto
	 * @params expandMultiBondProperties Boolean indica se deve retornar a estrutura de Arrays completa do MultiBondObject
	 * @return Object
	 */
	public function expose($expandMultiBondProperties=false) {
		
		$obj = new stdClass();
		
		// propriedades deste objeto que não são propriedades mapeadas pelo Schema do MultiBond
		$vars = get_object_vars($this);
		foreach ($vars as $propertyName=>$propertyValue) {
			if ( in_array($propertyName, $this->secretProperties) || is_null($propertyName) ) continue;
			$obj->$propertyName	= $this->__get($propertyName);
		}
		
		
		// propriedades mapeadas pelo o Schema do MultiBond
		if ($expandMultiBondProperties) {
			
			$vars = $this->properties;
			foreach ($vars as $propertyName=>$propertyValue) {
				if ( in_array($propertyName, $this->secretProperties) || is_null($propertyName) ) continue;
				$obj->$propertyName = $this->getProperty($propertyName);
			}
			
		} else {
			
			$vars = $this->properties;
			foreach ($vars as $propertyName=>$propertyValue) {
				if ( in_array($propertyName, $this->secretProperties) || is_null($propertyName) ) continue;
				$obj->$propertyName = $this->__get($propertyName);
			}
			
		}
		
		return (array)$obj;
	}
	
	
	
	
	/**
	 * retorna as variáveis protegidas ou privadas deste objeto
	 * utilizado para exposição de dados pela API, ou mesmo para uso por outros 
	 * objetos que precisem consultar propriedades inacessíveis deste objeto
	 * @params asArray Boolean indica se deve retornar a estrutura de Arrays completa do MultiBondObject
	 * @return Object
	 */
	public function old_expose($asArray=false) {
		
		$obj = new stdClass();
		$obj->id = $this->id;
		
		if ($asArray) {
			foreach($this->properties as $key => $value) {
				$obj->$key = $value;
			}
			
		} else {
			foreach($this->properties as $key => $value) {
				$obj->$key = $value;
			}
		}
		
		return (array)$obj;
		
	}
	
	
	
	/**
	 * Carrega estrutura do Object, com suas propriedades, baseado em seu SCHEMAObject
	 * @return boolean true se os dados foram carregados corretamente; do contrário, false
	 */
	public function loadEmpty() {
		
		if (is_null($this->multiBond))         return false;
		if (is_null($this->idSCHEMAObject))    return false;
		if (is_null($this->idGLOBALNamespace)) return false;
		
		$properties     = array();
		$tempProperties = array();
		
		$SCHEMAObject = $this->multiBond->getSCHEMAObjectName($this->idSCHEMAObject);
		$SCHEMAObject = isset($SCHEMAObject[0]) ? $SCHEMAObject[0] : NULL;
		
		// array $tempProperties armazena todas as propriedades possíveis para este SCHEMAObject
		$tempProperties = $this->multiBond->getSCHEMAPropertyList($SCHEMAObject);
		
		// para cada propriedade possível para este SCHEMAObject, armazena um valor NULL na array $properties
		// assim, garantimos que todas as propriedades possíveis estão mapeadas no objeto (mesmo que ainda sem valores)
		foreach($tempProperties as $prop) {
			$properties[$prop['sKey']][] = array(
											'id'=>NULL, 
											'idSCHEMAProperty'=>$prop['id'], 
											'sKey'=>$prop['sKey'], 
											'sDataType'=>$prop['sDataType'], 
											'fMultiple'=>$prop['fMultiple'], 
											'sValue'=>NULL, 
											'iIndex'=>0, 
											'changed'=>false,
											'removed'=>false
											);
		}
		
		$this->properties         = $properties;
		$this->templateProperties = $properties;
		
		// neste momento, o objeto está pronto, com todas as propriedades passíveis de serem atribuídas
		// já mapeadas na array $this->properties, porém todas com valor NULL
		
		unset($tempProperties, $properties);
		
		$this->objectStatus	= self::EMPTY_LOADED;
		return true;
	}
	
	
	
	
	
	/**
	 * Carrega as informações do Object
	 * Usando o id do objeto, busca no banco de dados as informações do objeto e suas propriedades
	 * @return boolean true se os dados foram carregados corretamente; do contrário, false
	 */
	public function load() {
		
		if (is_null($this->id))        return false;
		if (is_null($this->multiBond)) return false;
		
		// variáveis temporárias, para armazenar os dados das propriedades carregadas do DB
		// antes de sobrescrever as propriedades do objeto
		$properties         = array();
		$templateProperties = array();
		
		$query = '
		SELECT 
			id, 
			idSCHEMAObject, 
			idGLOBALNamespace, 
			uniqueUserId, 
			tsCreation, 
			tsLastUpdate, 
			iTransaction, 
			fArchived 
		FROM tbDATAObject 
		WHERE id = '.prepareSQL($this->id).' ';
		
		$query .= !is_null($this->idSCHEMAObject) ? (' AND idSCHEMAObject = '.prepareSQL($this->idSCHEMAObject).';') : ';';
		
		$result = $this->multiBond->db->query(utf8_decode($query));
		if (!$result) return false;
		if ($result->num_rows <= 0) return false;
		
		while($row = $result->fetch_object()) {
			
			$this->id			      = toUTF8($row->id);
			$this->idSCHEMAObject     = toUTF8($row->idSCHEMAObject);
			$this->idGLOBALNamespace  = toUTF8($row->idGLOBALNamespace);
			$this->uniqueUserId       = toUTF8($row->uniqueUserId);
			$this->tsCreation         = toUTF8($row->tsCreation);
			$this->tsLastUpdate       = toUTF8($row->tsLastUpdate);
			$this->iTransaction       = toUTF8($row->iTransaction);
			$this->fArchived          = toUTF8($row->fArchived);
		}
		$result->free();
		
		
		//	leitura das propriedades do objeto
		//	todas as propriedades possíveis ao idSCHEMAObject deste objeto serão carregadas 
		//	propriedades sem um valor definido para este objeto serão trazidas como NULL
		
		
		$queryProperties = '
		SELECT 
			val.id, 
			pr.id AS idSCHEMAProperty, 
			pr.sKey, 
			pr.sDataType,
			pr.fMultiple,
			val.smallintValue, 
			val.bigintValue, 
			val.moneyValue, 
			val.floatValue, 
			val.datetimeValue, 
			val.stringValue, 
			val.textValue, 
			val.blobValue, 
			val.objectValue, 
			val.iIndex 
			
		FROM tbSCHEMAProperty pr 

		LEFT OUTER JOIN tbDATAObject ob 
		ON pr.idSCHEMAObject = ob.idSCHEMAObject 
		AND (pr.idGLOBALNamespace = ob.idGLOBALNamespace OR pr.idGLOBALNamespace IS NULL)

		LEFT OUTER JOIN tbDATAProperty val 
		ON pr.id = val.idSCHEMAProperty 
		AND val.idObject = ob.id 

		WHERE ob.id = '.prepareSQL($this->id).' 
		AND ob.idGLOBALNamespace = '.prepareSQL($this->idGLOBALNamespace).' 
		
		ORDER BY pr.sKey, val.iIndex, val.id;';
		
		
		$resultProperties = $this->multiBond->db->query(utf8_decode($queryProperties));
		if (!$resultProperties) return false;
		
		while($row = $resultProperties->fetch_object()) {
			$field = $row->sDataType.'Value';
			$properties[$row->sKey][] = array(
										'id'=>$row->id, 
										'idSCHEMAProperty'=>$row->idSCHEMAProperty, 
										'sKey'=>$row->sKey, 
										'sDataType'=>$row->sDataType, 
										'fMultiple'=>$row->fMultiple, 
										'sValue'=>toUTF8($row->$field), 
										'iIndex'=>toUTF8($row->iIndex), 
										'changed'=>false,
										'removed'=>false
										);
			
			$templateProperties[$row->sKey][0] = array(
										'id'=>NULL, 
										'idSCHEMAProperty'=>$row->idSCHEMAProperty, 
										'sKey'=>$row->sKey, 
										'sDataType'=>$row->sDataType, 
										'fMultiple'=>$row->fMultiple, 
										'sValue'=>NULL, 
										'iIndex'=>0, 
										'changed'=>false,
										'removed'=>false
										);
		}
		
		$resultProperties->free();
		
		
		//	após a leitura de todas as propriedades o objeto está pronto;
		//	a array $properties contém todas as informações do objeto atualmente gravadas no banco
		//	a array $templateProperties contém todas as informações das propriedades possíveis para o objeto
		
		$this->properties         = $properties;
		$this->templateProperties = $templateProperties;
		$this->objectStatus	      = self::LOADED;
		
		unset($properties, $templateProperties);
		
		return true;
	}
	
	
	
	
	
	/**
	 * salva as informações de um Object em banco de dados
	 * se for um novo Object (sem id definido), redireciona para _create()
	 * se for um Object já existente (com id definido), redireciona para _update()
	 * @return int / boolean
	 */
	public function save() {
		
		if (is_null($this->idGLOBALNamespace)) return false;
		
		// se o objeto ainda não foi carregado de alguma maneira, não é possível salvar suas propriedades
		if ($this->objectStatus	== self::NOT_LOADED) $this->loadEmpty();
		
		
		// inicia a gravação caso seja um novo objeto
		// neste caso, o idSCHEMAObject será obrigatório
		if ($this->objectStatus == self::EMPTY_LOADED) {
			
			return $this->_create();
		}
		
		// inicia a atualização caso seja um objeto já existente
		// neste caso, o id será obrigatório
		else if ($this->objectStatus == self::LOADED) {
			
			return $this->_update();
		}

	}
	
	protected function _create() {
		
		if (is_null($this->multiBond))         return false;
		if (is_null($this->idGLOBALNamespace)) return false;
		if (is_null($this->idSCHEMAObject))    return false;
		
		
		
		// dados da transação
		$tr = $this->multiBond->transaction();
		if (!$tr) return false;
		
		$iTransaction = $tr['iTransaction'];
		$tsNow = $tr['tsNow'];
		
		
		
		// criação do Object na tabela tbDATAObject
		$query = '
		INSERT INTO tbDATAObject 
		(
			id,
			idSCHEMAObject,
			idGLOBALNamespace,
			uniqueUserId,
			tsCreation,
			tsLastUpdate,
			iTransaction,
			fArchived,
			sAction
			
		) VALUES (
			
			NULL,
			'.prepareSQL($this->idSCHEMAObject).',
			'.prepareSQL($this->idGLOBALNamespace).',
			'.prepareSQL( isset($_SESSION['uniqueUserId']) ? $_SESSION['uniqueUserId'] : 1 ).',
			'.prepareSQL($tsNow).',
			'.prepareSQL($tsNow).',
			'.prepareSQL($iTransaction).',
			0,
			"I"
		); ';
		
		
		$result = $this->multiBond->db->query(utf8_decode($query));
		if (!$result) return false;
		$idObject = $this->multiBond->db->insert_id;
		
		
		
		// criação das Properties do Object na tabela tbDATAProperty
		foreach($this->properties as $prop) {
			foreach($prop as $p) {
				
				// como estamos criando o objeto agora, todas as propriedades devem ser gravadas
				//if (!$p['changed']) continue;
				
				$idSCHEMAProperty = $p['idSCHEMAProperty'];
				$sDataType        = $p['sDataType'];
				$value            = $p['sValue'];
				
				switch($sDataType) {
					
					case 'smallint' : 
						$value = filter_var($value, FILTER_SANITIZE_NUMBER_INT);
						$value = intval($value,10);
					break;
					
					case 'bigint' : 
						$value = filter_var($value, FILTER_SANITIZE_NUMBER_INT);
						$value = intval($value,10);
					break;
					
					case 'money' : 
						$value = filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
						$value = floatval($value);
					break;
					
					case 'float' : 
						$value = filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
						$value = floatval($value);
					break;
					
					case 'datetime' : 
						$value = filter_var($value, FILTER_SANITIZE_MAGIC_QUOTES);
						$validDate = DateTime::createFromFormat("Y-m-d H:i:s", $value);
						if ($validDate) $validDate = $validDate->format("Y-m-d H:i:s");
						$value = filter_var($validDate, FILTER_SANITIZE_MAGIC_QUOTES);
					break;
					
					case 'string' : 
						$value = filter_var($value, FILTER_SANITIZE_MAGIC_QUOTES);
						$value = strval($value);
					break;
					
					case 'text' : 
						$value = filter_var($value, FILTER_SANITIZE_MAGIC_QUOTES);
						$value = strval($value);
					break;
					
					case 'blob' : 
						$value = filter_var($value, FILTER_SANITIZE_MAGIC_QUOTES);
					break;
					
					case 'hid' : 
						$value = filter_var($value, FILTER_SANITIZE_MAGIC_QUOTES);
					break;
					
					case 'object' : 
						$value = filter_var($value, FILTER_SANITIZE_NUMBER_INT);
						$value = intval($value,10);
					break;
					
					default : 
						$value = filter_var($value, FILTER_SANITIZE_MAGIC_QUOTES);
						$value = strval($value);
					break;
				}
				
				
				$query = '
	INSERT INTO tbDATAProperty 
	(
		id,
		idObject,
		idSCHEMAProperty,
		idGLOBALNamespace,
		uniqueUserId,
		'.$sDataType.'Value,
		iIndex,
		tsCreation,
		tsLastUpdate,
		iTransaction,
		sAction
		
	) VALUES (
		
		NULL,
		'.$idObject.',
		'.prepareSQL($idSCHEMAProperty).',
		'.prepareSQL($this->idGLOBALNamespace).',
		'.prepareSQL( isset($_SESSION['uniqueUserId']) ? $_SESSION['uniqueUserId'] : 1 ).',
		'.prepareSQL($value).',
		'.prepareSQL($p['iIndex']).',
		'.prepareSQL($tsNow).',
		'.prepareSQL($tsNow).',
		'.prepareSQL($iTransaction).',
		"I"
	); ';
				
				$result = $this->multiBond->db->query(utf8_decode($query));
				// if (!$result) return false;
				
			}
		}
		
		$this->multiBond->log($iTransaction, $tsNow);
		
		// atualiza as propriedades do objeto
		$this->id = $idObject;
		$this->load();
		
		return $this->id;
	}
	
	protected function _update() {
		
		if (is_null($this->multiBond))         return false;
		if (is_null($this->idGLOBALNamespace)) return false;
		if (is_null($this->idSCHEMAObject))    return false;
		if (is_null($this->id)) 			   return false;
		
		
		// dados da transação
		$tr = $this->multiBond->transaction();
		if (!$tr) return false;
		
		$iTransaction = $tr['iTransaction'];
		$tsNow = $tr['tsNow'];
		
		
		
		// atualiza os dados do Object na tabela tbDATAObject
		$query = '
	UPDATE tbDATAObject SET 
		uniqueUserId = '.prepareSQL( isset($_SESSION['uniqueUserId']) ? $_SESSION['uniqueUserId'] : 1 ).',
		tsLastUpdate = '.prepareSQL($tsNow).',
		iTransaction = '.prepareSQL($iTransaction).', 
		sAction = "U" 
	WHERE id = '.prepareSQL($this->id).' 
	AND idSCHEMAObject = '.prepareSQL($this->idSCHEMAObject).' 
	AND idGLOBALNamespace = '.prepareSQL($this->idGLOBALNamespace).'; ';
		
		
		$result = $this->multiBond->db->query(utf8_decode($query));
		if (!$result) return false;
		
		
		
		// atualiza os dados das Properties na tabela tbDATAProperty
		// aqui faremos o controle das propriedades que existem, que foram alteradas e que foram excluídas
		foreach($this->properties as $prop) {
			foreach($prop as $p) {
				
				// para funcionamento correto do log, apenas vamos gravar as propriedades que foram alteradas/e
				// o campo 'changed' é marcado como 'true' pelo método setProperty e pelo método deleteProperty
				// o campo 'removed' é marcado como 'true' pelo método deleteProperty
				if (!$p['changed'] && !$p['removed']) continue;
				
				$idSCHEMAProperty = $p['idSCHEMAProperty'];
				$sDataType        = $p['sDataType'];
				$value            = $p['sValue'];
				
				switch($sDataType) {
					
					case 'smallint' : 
						$value = filter_var($value, FILTER_SANITIZE_NUMBER_INT);
						$value = intval($value,10);
					break;
					
					case 'bigint' : 
						$value = filter_var($value, FILTER_SANITIZE_NUMBER_INT);
						$value = intval($value,10);
					break;
					
					case 'money' : 
						$value = filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
						$value = floatval($value);
					break;
					
					case 'float' : 
						$value = filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
						$value = floatval($value);
					break;
					
					case 'datetime' : 
						$value = filter_var($value, FILTER_SANITIZE_MAGIC_QUOTES);
						$validDate = DateTime::createFromFormat("Y-m-d H:i:s", $value);
						if ($validDate) $validDate = $validDate->format("Y-m-d H:i:s");
						$value = filter_var($validDate, FILTER_SANITIZE_MAGIC_QUOTES);
					break;
					
					case 'string' : 
						$value = filter_var($value, FILTER_SANITIZE_MAGIC_QUOTES);
						$value = strval($value);
					break;
					
					case 'text' : 
						$value = filter_var($value, FILTER_SANITIZE_MAGIC_QUOTES);
						$value = strval($value);
					break;
					
					case 'blob' : 
						$value = filter_var($value, FILTER_SANITIZE_MAGIC_QUOTES);
					break;
					
					case 'hid' : 
						$value = filter_var($value, FILTER_SANITIZE_MAGIC_QUOTES);
					break;
					
					case 'object' : 
						$value = filter_var($value, FILTER_SANITIZE_NUMBER_INT);
						$value = intval($value,10);
					break;
					
					default : 
						$value = filter_var($value, FILTER_SANITIZE_MAGIC_QUOTES);
						$value = strval($value);
					break;
				}
				
				
				// se o id para a propriedade não for nulo, é um update ou exclusão de propriedade
				if ( !is_null($p['id']) && $p['id'] != 0 ) {
				
					$query = '
	UPDATE tbDATAProperty SET 
	
		'.$sDataType.'Value = '.prepareSQL($value).', 
		uniqueUserId        = '.prepareSQL( isset($_SESSION['uniqueUserId']) ? $_SESSION['uniqueUserId'] : 1 ).', 
		iIndex              = '.prepareSQL($p['iIndex']).', 
		tsLastUpdate        = '.prepareSQL($tsNow).', 
		iTransaction        = '.prepareSQL($iTransaction).', 
		sAction             = "'.( $p['removed'] ? "D" : "U" ).'" 
		
	WHERE idObject = '.prepareSQL($this->id).' 
	AND   id = '.prepareSQL($p['id']).' 
	AND   idSCHEMAProperty = '.prepareSQL($idSCHEMAProperty).' 
	AND   idGLOBALNamespace = '.prepareSQL($this->idGLOBALNamespace).'; ';
	
				}
				
				// se o id para a propriedade for nulo, é um novo valor para a propriedade
				// (usado para propriedades que permitem múltiplos valores)
				else {
					
					$query = '
	INSERT INTO tbDATAProperty (
	
		'.$sDataType.'Value, 
		uniqueUserId, 
		iIndex, 
		tsLastUpdate, 
		iTransaction, 
		idObject, 
		sAction, 
		idSCHEMAProperty, 
		idGLOBALNamespace
	
	) VALUES (
		'.prepareSQL($value).',
		'.prepareSQL( isset($_SESSION['uniqueUserId']) ? $_SESSION['uniqueUserId'] : 1 ).',
		'.prepareSQL($p['iIndex']).',
		'.prepareSQL($tsNow).',
		'.prepareSQL($iTransaction).',
		'.prepareSQL($this->id).', 
		"I", 
		'.prepareSQL($idSCHEMAProperty).', 
		'.prepareSQL($this->idGLOBALNamespace).' 
	); ';
				}
				
				$result = $this->multiBond->db->query(utf8_decode($query));
				//if (!$result) return false;
			}
		}
		
		// realiza o log de todos os registros envolvidos nesta atualização (Object e Properties)
		$this->multiBond->log($iTransaction, $tsNow);
		
		$this->load();
		
		return true;
	}
	
	
	
	
	
	/**
	 * Exclui um objeto
	 * @return boolean
	 */
	public function exclude() {
		
		if (is_null($this->id))                return false;
		if (is_null($this->multiBond))         return false;
		if (is_null($this->idGLOBALNamespace)) return false;
		
		
		// dados da transação
		$tr = $this->multiBond->transaction();
		if (!$tr) return false;
		
		$iTransaction = $tr['iTransaction'];
		$tsNow = $tr['tsNow'];
		
		
		// marca o Object para ser excluído na tabela tbDATAObject
		$query = '
		UPDATE tbDATAObject SET 
			uniqueUserId = '.prepareSQL( isset($_SESSION['uniqueUserId']) ? $_SESSION['uniqueUserId'] : 1 ).', 
			tsLastUpdate = '.prepareSQL($tsNow).', 
			iTransaction = '.prepareSQL($iTransaction).', 
			sAction      = "D" 
		WHERE id = '.prepareSQL($this->id).' 
		AND idSCHEMAObject = '.prepareSQL($this->idSCHEMAObject).' 
		AND idGLOBALNamespace = '.prepareSQL($this->idGLOBALNamespace).'; ';
		
		$result = $this->multiBond->db->query(utf8_decode($query));
		if (!$result) return false;
		
		
		// marca as Properties do Object para serem excluídas na tabela tbDATAProperty
		$query = '
		UPDATE tbDATAProperty SET 
			uniqueUserId = '.prepareSQL( isset($_SESSION['uniqueUserId']) ? $_SESSION['uniqueUserId'] : 1 ).', 
			tsLastUpdate = '.prepareSQL($tsNow).', 
			iTransaction = '.prepareSQL($iTransaction).', 
			sAction      = "D" 
		WHERE idObject = '.prepareSQL($this->id).'
		AND   idGLOBALNamespace = '.prepareSQL($this->idGLOBALNamespace).'; ';
		
		$result = $this->multiBond->db->query(utf8_decode($query));
		if (!$result) return false;
		
		
		// realiza o log e exclui todos os registros alterados
		$this->multiBond->log($iTransaction, $tsNow);
		
		return true;
	}
	
	
	
	
	
	/**
	 * Atualiza uma propriedade do objeto
	 * Usa algo parecido com method overloading, conceito existente em outras linguagens, porém não exatamente o que o PHP chama de overloading...
	 * Está mais próximo de uma função variádica/variária ('variadic function', não encontrei nenhuma tradução consistente para o português)
	 * Para mais esclarecimentos, ver Variadic function (http://en.wikipedia.org/wiki/Variadic_function#PHP), e
	 * PHP Overloading (http://www.php.net/manual/en/language.oop5.overloading.php) 
	 * 
	 * @example setProperty('sLogon', NULL, 'example@domain.com') -- insere novo valor para propriedade 'sLogon'
	 *          setProperty('sLogon', 1234, 'example@domain.com') -- altera o valor para propriedade 'sLogon' cujo id seja '1234'
	 *          setProperty('sFullName', 'John Doe')              -- altera o valor para propriedade 'sFullName'; 'sFullName' não aceita múltiplos valores
	 * @return Boolean
	 */
	public function setProperty($sKey, $id, $sValue=NULL) {
		
		// é necessário saber o namespace e o tipo do objeto para atribuir as propriedades
		if (is_null($this->multiBond))      return false;
		if (is_null($this->idSCHEMAObject)) return false;
		if (is_null($sKey))                 return false;
		
		
		// pelo NÚMERO DE ARGUMENTOS, validamos a intenção da chamada:
		// com dois argumentos, sendo $sKey e $sValue, atualizamos a única propriedade existente no objeto com o sKey solicitado;
		// com três argumentos, sendo $sKey, $id e $sValue, atualizamos a propriedade especificada pelo id com o sKey solicitado.
		$sValue = func_num_args()===2 ? func_get_arg(1) : func_get_arg(2);
		$id     = func_num_args()===3 ? func_get_arg(1) : NULL;
		
		
		// se o objeto ainda não foi carregado de alguma maneira, não é possível alterar suas propriedades
		if ($this->objectStatus	== self::NOT_LOADED) $this->loadEmpty();
		
		
		// se a chave existe na array, então é possível atribuir seu valor
		if (array_key_exists($sKey, $this->properties)) {
			
			// se não houver um id de propriedade válido para alterar, e foram passados TRÊS parâmetros, 
			// trata-se de INCLUSÃO de um novo valor para a propriedade -- numa propriedade que aceite múltiplos valores
			// vamos usar a array $templateProperties para 'copiar' o correto modelo de propriedade
			if ((is_null($id) || $id == 0) && func_num_args()===3) {
				
				if (array_key_exists($sKey, $this->templateProperties)) {
					$template = $this->templateProperties[$sKey][0];
				} else {
					return false;
				}
				
				$template['sValue']  = $sValue;
				$template['changed'] = true;
				
				$this->properties[$sKey][] = $template;
				
				return true;
			}
			
			// se não houver um id de propriedade válido para alterar, e foram passados APENAS DOIS parâmetros, 
			// trata-se de ALTERAÇÃO do único valor para a propriedade -- numa propriedade que aceite apenas um valor
			else if ((is_null($id) || $id == 0) && func_num_args()===2) {
				
				$this->properties[$sKey][0]['sValue']  = $sValue;
				$this->properties[$sKey][0]['changed'] = true;
				
				return true;
			}
			
			// se houver um id de propriedade para alterar, e foram passados TRÊS parâmetros, 
			// simplesmente altera o valor da propriedade 
			// e marca a flag 'changed', usada para executar a query de UPDATE do objeto
			else {
			
				foreach ($this->properties[$sKey] as $key => $value) {
					if ($value['id'] == $id) {
						$indexToChange = $key;
						break;
					}
				}
				
				if (isset($indexToChange)) {
					$this->properties[$sKey][$indexToChange]['sValue']  = $sValue;
					$this->properties[$sKey][$indexToChange]['changed'] = true;
					return true;
				}
			}
			
		} else {
			return false;
		}
	}
	
	
	
	
	
	/**
	 * Remove uma propriedade do objeto
	 * 
	 * @example deleteProperty('sLogon', 1234) -- remove completamente o valor da propriedade 'sLogon' cujo id seja '1234'
	 * @return Boolean
	 */
	public function deleteProperty($sKey, $id) {
		
		// é necessário saber o namespace e o tipo do objeto para remover a propriedade
		if (is_null($this->multiBond))      return false;
		if (is_null($this->idSCHEMAObject)) return false;
		if (is_null($sKey))                 return false;
		if (is_null($id))                   return false;
		
		
		// se o objeto ainda não foi carregado de alguma maneira, não é possível alterar suas propriedades
		if ($this->objectStatus	== self::NOT_LOADED) $this->loadEmpty();
		
		
		// se a chave existe na array, então é possível remover seu valor
		if (array_key_exists($sKey, $this->properties)) {
			
			foreach ($this->properties[$sKey] as $key => $value) {
				if ($value['id'] == $id) {
					$indexToRemove = $key;
					break;
				}
			}
			
			if (isset($indexToRemove)) {
				$this->properties[$sKey][$indexToRemove]['changed'] = false;
				$this->properties[$sKey][$indexToRemove]['removed'] = true;
				return true;
			}
			
		} else {
			return false;
		}
	}
	
	
	
	
	
	/**
	 * expõe o valor de uma propriedade do objeto para leitura
	 * @return array com todos os valores da propriedade, em estrutura de Arrays completa do MultiBondObject
	 */
	public function getProperty($sKey) {
		
		if (is_null($this->multiBond))          return NULL;
		if (is_null($this->idSCHEMAObject)) return NULL;
		
		// se o objeto ainda não foi carregado de alguma maneira, não é possível retornar suas propriedades
		if ($this->objectStatus	== self::NOT_LOADED) $this->loadEmpty();
		
		// se a chave existe na array, então é possível retornar seu valor
		if (array_key_exists($sKey, $this->properties)) {
			
			return $this->properties[$sKey];
			
		} else {
			return NULL;
		}
	}
	
	
	
	
	
	/**
	 * expõe a array de propriedades do objeto para leitura
	 * @return array com todas as propriedades, em estrutura de Arrays completa do MultiBondObject
	 */
	public function getProperties() {
		
		if (is_null($this->multiBond))      return array();
		if (is_null($this->idSCHEMAObject)) return array();
		
		// se o objeto ainda não foi carregado de alguma maneira, não é possível retornar suas propriedades
		if ($this->objectStatus	== self::NOT_LOADED) $this->loadEmpty();
		
		return $this->properties;
	}
	
}
