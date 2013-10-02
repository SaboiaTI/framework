<?php
/**
 * Classe que permite manipular um Tie
 * Um Tie é laço entre um Objeto e um Bond, informando nesse vínculo qual o "papel" de cada Objeto na relação
 * a saber: Object <---> Tie <---> Bond <---> Tie <---> Object
 */
class MultiBondTie {
	
	const NOT_LOADED	= 0;
	const LOADED 		= 1;
	const EMPTY_LOADED 	= 2;
	
	
	protected $multiBond;
	protected $id;
	protected $idSCHEMATie;
	protected $idGLOBALNamespace;
	
	protected $idObject;
	protected $idBond;
	
	protected $tsCreation;
	protected $tsLastUpdate;
	
	protected $objectStatus;
	
	
	
	
	/**
	 * Construtor
	 * 
	 * @param int $id              id do laço, caso já exista (NULL se for um novo laço, ainda não salvo)
	 * @param string $SCHEMATie    (SCHEMATie) tipo de laço
	 * @param string $idObject
	 * @param string $idBond
	 * @param MultiBond $mb
	 */
	public function __construct(array $args=array()) {
		
		// default parameters values, to be merged qith the received parameters
		$default_values = array(
			'id'           => NULL,
			'SCHEMATie'    => NULL,
			'idObject'     => NULL,
			'idBond'       => NULL,
			'mb'           => NULL
		);
		
		$args = array_merge($default_values, $args);
		
		$id           = $args['id'];
		$SCHEMATie    = $args['SCHEMATie'];
		$idObject     = $args['idObject'];
		$idBond       = $args['idBond'];
		$mb           = $args['mb'];
		
		// Agora os valores recebidos foram normatizados.
		// Prosseguimos com a execução do método
		
		
		// primeiramente, é necessário que exista um MultiBond válido associado a este objeto 
		// para que ele tenha acesso a funções básicas de validação e consulta ao SCHEMA
		
		$this->multiBond = $mb;
		
		// existindo um MultiBond, a consulta ao SCHEMA pode ser realizada, e o SCHEMATie e namespace
		// podem ser atribuídos corretamente; do contrário, os valores de SCHEMATie e namespace serão NULL
		// procedimento: o parâmetro SCHEMATie deve ser uma string;
		//               essa string é procurada na tabela de SCHEMA, e retornamos seu id
		//               se for encontrado o id, a string é salva na propriedade $this->SCHEMATie
		//               e o id é salvo na propriedade $this->idSCHEMATie
		
		$idSCHEMATie = NULL;
		$idGLOBALNamespace  = NULL;
		
		if (!is_null($this->multiBond)) {
			
			$idGLOBALNamespace = $this->multiBond->getNamespaceId($this->multiBond->getNamespace());
			
			$strSCHEMATie = filter_var($SCHEMATie, FILTER_SANITIZE_STRING);
			
			$idSCHEMATie = $this->multiBond->getSCHEMATieId($strSCHEMATie);
			$idSCHEMATie = isset($idSCHEMATie[0]) && count($idSCHEMATie[0])===1 ? $idSCHEMATie[0][0] : NULL;
			
			if (is_null($idSCHEMATie)) {
				// não foi encontrado o SCHEMATie, então será criado um novo SCHEMATie on the fly
				$idSCHEMATie = $this->multiBond->createSCHEMATie($strSCHEMATie, 'Created automatically by the System', $this->multiBond->getNamespace());
			}
		}
		
		$this->id                = $id;
		$this->idSCHEMATie       = $idSCHEMATie;
		$this->idGLOBALNamespace = $idGLOBALNamespace;
		
		$this->idObject = $idObject;
		$this->idBond   = $idBond;
		
		$this->objectStatus	= self::NOT_LOADED;
		
		if (!is_null($this->id)) $this->load();
		
	}
	
	
	
	
	
	/**
	 * expõe para leitura algumas propriedades desta classe definidas como protected ou private
	 * faz tratamentos e validação de alguns valores antes de retornar
	 * @return mixed property value if valid; NULL otherwise
	 */
	public function __get ($propertyName) {
		
		if ($propertyName === 'id' || $propertyName === 'multiBond' || $propertyName === 'objectStatus') {
			
			return $this->$propertyName;
			
		}
		else if ($propertyName === 'idSCHEMATie' || $propertyName === 'SCHEMATie') {
			
			if (is_null($this->multiBond)) {
			
				$schema = $this->multiBond->getSCHEMATieName($this->idSCHEMATie);
				$schema = isset($schema[0]) ? $schema[0] : NULL;
				return $schema;
				
			} else {
				return false;
			}
		}
		else if ($propertyName === 'idGLOBALNamespace' || $propertyName === 'GLOBALNamespace') {
			
			if (is_null($this->multiBond)) {
			
				$nameSpace = $this->multiBond->getNamespaceName($this->idGLOBALNamespace);
				$nameSpace = isset($nameSpace[0]) ? $nameSpace[0] : NULL;
				return $nameSpace;
				
			} else {
				return false;
			}
		}
		else {
			return null;
		}
    }
	
	
	
	
	/**
	 * expõe para gravação algumas propriedades desta classe definidas como protected ou private
	 * faz tratamentos e validação dos valores antes de atribuir às propriedades
	 * @return boolean
	 */
	public function __set($propertyName, $value) {
		
		if ($propertyName === 'id' || $propertyName === 'idObject' || $propertyName === 'idBond') {
			
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
		else {
			return false;
		}
	}
	
	
	
	
	
	/**
	 * Associa este objeto a uma instância da classe MultiBond, que
	 * contém as informações do SCHEMA, necessárias para as funções 
	 * que buscam Bonds entre objetos
	 * @return boolean
	 */
	public function setMultiBond(MultiBond $mb) {
	
		if (!is_null($mb)) {
			
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
	 * Carrega as informações do Tie
	 * @return boolean
	 */
	public function load() {
		
		if (is_null($this->id))                return false;
		if (is_null($this->multiBond))         return false;
		if (is_null($this->idGLOBALNamespace)) return false;
		
		
		$query = '
		SELECT 
			id, 
			idSCHEMATie, 
			idObject, 
			idBond
			
		FROM tbDATATie 
		WHERE id = '.prepareSQL($this->id).'
		AND idGLOBALNamespace = '.prepareSQL($this->idGLOBALNamespace).'; ';
		
		$result = $this->multiBond->db->query(utf8_decode($query));
		if (!$result) return false;
		if ($result->num_rows <= 0) return false;
		
		while($row = $result->fetch_object()) {
			$this->id          = isset($row->id)          ? toUTF8($row->id)          : NULL;
			$this->idSCHEMATie = isset($row->idSCHEMATie) ? toUTF8($row->idSCHEMATie) : NULL;
			$this->idObject    = isset($row->idObject)    ? toUTF8($row->idObject)    : NULL;
			$this->idBond      = isset($row->idBond)      ? toUTF8($row->idBond)      : NULL;
		}
		$result->close();
		
		$this->objectStatus	= self::LOADED;
		
		return true;
	}
	
	
	
	
	
	
	/**
	 * salva as informações do Tie em banco de dados
	 * um Tie nunca é editado esta função sempre redireciona para create()
	 * @return int / boolean
	 */
	public function save() {
	
		if (is_null($this->multiBond))         return false;
		if (is_null($this->idGLOBALNamespace)) return false;
		
		// inicia a gravação caso seja um novo objeto
		// neste caso, o idSCHEMATie será obrigatório
		if ($this->objectStatus == self::NOT_LOADED) {
			
			return $this->create();
		}
		
		// caso seja um objeto já existente não há nada a fazer
		// retornamos false, pois um Tie não pode ser salvo
		else if ($this->objectStatus == self::LOADED) {
		
			return false;
		}
		
	}
	
	private function create() {
		
		if (is_null($this->multiBond))         return false;
		if (is_null($this->idSCHEMATie))       return false;
		if (is_null($this->idGLOBALNamespace)) return false;
		if (is_null($this->idObject))          return false;
		if (is_null($this->idBond))            return false;
		
		
		// verifica se o Object e Bond existem, e são acessíveis por este namespace;
		// o namespace é verificado internamente pelo multibond passado ao Object e ao Bond;
		// se o namespace não for válido, ou for diferente, o Object e o Bond não são carregados;
		$validObject = false;
		$validBond   = false;
		
		$obj = new MultiBondObject($this->idObject, NULL, $this->multiBond);
		$obj->load();
		$validObject = $obj->objectStatus == MultiBondObject::LOADED;
		
		$bnd = new MultiBondBond($this->idBond, NULL, $this->multiBond);
		$bnd->load();
		$validBond = $bnd->objectStatus == MultiBondBond::LOADED;
		
		if (!$validObject || !$validBond) return false;		
		
		
		// dados da transação
		$tr = $this->multiBond->transaction();
		if (!$tr) return false;
		
		$iTransaction = $tr['iTransaction'];
		$tsNow = $tr['tsNow'];
		
		
		
		$query = '
		INSERT INTO tbDATATie 
		(
			id, 
			idGLOBALNamespace, 
			idSCHEMATie, 
			idObject, 
			idBond,
			uniqueUserId, 
			tsCreation, 
			tsLastUpdate, 
			iTransaction,
			sAction
			
		) VALUES (
			
			NULL, 
			'.prepareSQL($this->idGLOBALNamespace).', 
			'.prepareSQL($this->idSCHEMATie).', 
			'.prepareSQL($this->idObject).', 
			'.prepareSQL($this->idBond).', 
			'.prepareSQL($_SESSION['uniqueUserId']).', 
			'.prepareSQL($tsNow).', 
			'.prepareSQL($tsNow).', 
			'.prepareSQL($iTransaction).', 
			"I"
		); ';
		
		
		$result = $this->multiBond->db->query(utf8_decode($query));
		if (!$result) return false;
		
		$this->id = $this->multiBond->db->insert_id;
		
		$success = $this->multiBond->log($iTransaction, $tsNow);
		
		return $this->id;
	}
	
	public function exclude() {
		
		if (is_null($this->id))                return false;
		if (is_null($this->multiBond))         return false;
		if (is_null($this->idGLOBALNamespace)) return false;
		
		
		// dados da transação
		$tr = $this->multiBond->transaction();
		if (!$tr) return false;
		
		$iTransaction = $tr['iTransaction'];
		$tsNow = $tr['tsNow'];
		
		
		
		// marca para ser deletado o Tie na tabela tbDATATie
		$query = '
		UPDATE tbDATATie SET 
			uniqueUserId = '.prepareSQL($_SESSION['uniqueUserId']).',
			tsLastUpdate = '.prepareSQL($tsNow).',
			iTransaction = '.prepareSQL($iTransaction).',
			sAction      = "D" 
		WHERE id = '.prepareSQL($this->id).' 
		AND idSCHEMATie = '.prepareSQL($this->idSCHEMATie).' 
		AND idGLOBALNamespace = '.prepareSQL($this->idGLOBALNamespace).'; ';
		
		$result = $this->multiBond->db->query(utf8_decode($query));
		if (!$result) return false;
		
		
		// log e exclusão do registro envolvido
		$success = $this->multiBond->log($iTransaction, $tsNow);
		
		return $success;
	}
	
	
}