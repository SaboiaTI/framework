<?php
/**
 * Classe que permite manipular um Bond
 * Um Bond é um vínculo entre dois objetos no MultiBond
 * O laço entre um Bond e um Objeto se dá por um Tie
 * a saber: Object <---> Tie <---> Bond <---> Tie <---> Object
 */
class MultiBondBond {
	
	const NOT_LOADED	= 0;
	const LOADED 		= 1;
	const EMPTY_LOADED 	= 2;
	
	protected $multiBond;
	protected $id;
	protected $idSCHEMABond;
	protected $idGLOBALNamespace;
	protected $objectStatus;
	
	
	
	/**
	 * Construtor
	 * 
	 * @param int $id             id do vínculo, caso já exista (NULL se for um novo vínculo, ainda não salvo)
	 * @param string $SCHEMABond  (SCHEMABond) tipo de vínculo
	 * @param MultiBond $mb
	 */
	public function __construct($id=NULL, $SCHEMABond=NULL, MultiBond $mb=NULL) {
		
		// primeiramente, é necessário que exista um MultiBond válido associado a este objeto 
		// para que ele tenha acesso a funções básicas de validação e consulta ao SCHEMA
		
		$this->multiBond = $mb;
		
		// existindo um MultiBond, a consulta ao SCHEMA pode ser realizada, e os dados de SCHEMABond e namespace
		// podem ser atribuídos corretamente; do contrário, os valores de SCHEMABond e namespace serão NULL
		// procedimento: o parâmetro SCHEMABond deve ser uma string;
		//               essa string é procurada na tabela de SCHEMA, e retornamos seu id
		//               se for encontrado, o id é salvo na propriedade $this->idSCHEMABond
		//               se não for encontrado, o SCHEMA será criado e o id resultante será salvo na propriedade $this->idSCHEMABond
		
		$idSCHEMABond      = NULL;
		$idGLOBALNamespace = NULL;
		
		if (!is_null($this->multiBond)) {
			
			$idGLOBALNamespace = $this->multiBond->getNamespaceId($this->multiBond->getNamespace());
			
			$strSCHEMABond = filter_var($SCHEMABond, FILTER_SANITIZE_STRING);
			
			$idSCHEMABond = $this->multiBond->getSCHEMABondId($strSCHEMABond);
			$idSCHEMABond = isset($idSCHEMABond[0]) && count($idSCHEMABond[0])===1 ? $idSCHEMABond[0][0] : NULL;
			
			if (is_null($idSCHEMABond)) {
				// não foi encontrado o SCHEMABond, então será criado um novo SCHEMABond on the fly
				$idSCHEMABond = $this->multiBond->createSCHEMABond($strSCHEMABond, 'Created automatically by the System', $this->multiBond->getNamespace());
			}
		}
		
		$this->id                = $id;
		$this->idSCHEMABond      = $idSCHEMABond;
		$this->idGLOBALNamespace = $idGLOBALNamespace;
		
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
		else if ($propertyName === 'idSCHEMABond' || $propertyName === 'SCHEMABond') {
			
			if (is_null($this->multiBond)) {
			
				$schema = $this->multiBond->getSCHEMABondName($this->idSCHEMABond);
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
		else if ($propertyName === 'idSCHEMABond' || $propertyName === 'SCHEMABond') {
			
			if (is_null($this->multiBond)) return false;
			
			if (is_string($value)) {
				
				$strSCHEMABond = filter_var($value, FILTER_SANITIZE_STRING);
				$idSCHEMABond  = $this->multiBond->getSCHEMABondId($strSCHEMABond);
				$idSCHEMABond  = isset($idSCHEMABond[0]) && count($idSCHEMABond[0])===1 ? $idSCHEMABond[0][0] : NULL;
				
				$this->idSCHEMABond = $idSCHEMABond;
				return is_null($this->idSCHEMABond);
				
			} else {
				
				$this->idSCHEMABond = NULL;
				return false;
			}
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
	 * Carrega as informações do Bond
	 * @return boolean
	 */
	public function load() {
		
		if (is_null($this->id))                return false;
		if (is_null($this->multiBond))         return false;
		if (is_null($this->idGLOBALNamespace)) return false;
		
		
		$query = '
		SELECT 
			id,
			idSCHEMABond
		FROM tbDATABond 
		WHERE id = '.prepareSQL($this->id).'
		AND idGLOBALNamespace = '.prepareSQL($this->idGLOBALNamespace).'; ';
		
		$result = $this->multiBond->db->query(utf8_decode($query));
		if (!$result) return false;
		if ($result->num_rows <= 0) return false;
		
		while($row = $result->fetch_object()) {
			$this->id = isset($row->id) ? toUTF8($row->id) : NULL;
			$this->idSCHEMABond = isset($row->idSCHEMABond) ? toUTF8($row->idSCHEMABond) : NULL;
		}
		$result->close();
		
		$this->objectStatus	= self::LOADED;
		return true;
	}
	
	
	
	
	
	/**
	 * salva as informações do Bond em banco de dados
	 * um Bond nunca é editado esta função sempre redireciona para create()
	 * @return int / boolean
	 */
	public function save() {
		
		if (is_null($this->multiBond))         return false;
		if (is_null($this->idGLOBALNamespace)) return false;
		
		// inicia a gravação caso seja um novo objeto
		// neste caso, o idSCHEMABond será obrigatório
		if ($this->objectStatus == self::NOT_LOADED) {
			
			return $this->create();
		}
		
		// caso seja um objeto já existente não há nada a fazer
		// retorna falso porque um Bond existente não pode ser salvo
		else if ($this->objectStatus == self::LOADED) {
			return false;
		}
		
	}
	
	private function create() {
		
		if (is_null($this->multiBond))         return false;
		if (is_null($this->idSCHEMABond))      return false;
		if (is_null($this->idGLOBALNamespace)) return false;

		
		// dados da transação
		$tr = $this->multiBond->transaction();
		if (!$tr) return false;
		
		$iTransaction = $tr['iTransaction'];
		$tsNow = $tr['tsNow'];
		
		
		$query = '
		INSERT INTO tbDATABond 
		(
			id, 
			idGLOBALNamespace, 
			idSCHEMABond, 
			uniqueUserId, 
			tsCreation, 
			tsLastUpdate, 
			iTransaction, 
			sAction 
		) VALUES (
			NULL, 
			'.prepareSQL($this->idGLOBALNamespace).', 
			'.prepareSQL($this->idSCHEMABond).', 
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
		
//echo "<pre>";
		
		// dados da transação
		$tr = $this->multiBond->transaction();
		if (!$tr) return false;
		
		$iTransaction = $tr['iTransaction'];
		$tsNow = $tr['tsNow'];
		
		
		// marca para exclusão o Bond
		$query = '
		UPDATE tbDATABond SET 
			uniqueUserId = '.prepareSQL($_SESSION['uniqueUserId']).',
			tsLastUpdate = '.prepareSQL($tsNow).',
			iTransaction = '.prepareSQL($iTransaction).', 
			sAction      = "D" 
		WHERE id = '.prepareSQL($this->id).' 
		AND idSCHEMABond = '.prepareSQL($this->idSCHEMABond).' 
		AND idGLOBALNamespace = '.prepareSQL($this->idGLOBALNamespace).'; ';
		
//print_r($query);
//echo "<hr>";
		
		$result = $this->multiBond->db->query(utf8_decode($query));
		if (!$result) return false;
		
//var_dump($result);
//echo "<hr>";
		
		// marca para exclusão os Ties vinculados a este Bond, para evitar que fiquem "soltos"
		$query = '
		UPDATE tbDATATie SET 
			uniqueUserId = '.prepareSQL($_SESSION['uniqueUserId']).',
			tsLastUpdate = '.prepareSQL($tsNow).',
			iTransaction = '.prepareSQL($iTransaction).', 
			sAction      = "D" 
		WHERE idBond = '.prepareSQL($this->id).' 
		AND idGLOBALNamespace = '.prepareSQL($this->idGLOBALNamespace).'; ';
		
//print_r($query);
//echo "<hr>";
		
		$result = $this->multiBond->db->query(utf8_decode($query));
		if (!$result) return false;

//var_dump($result);
//echo "<hr>";
		
		// log e exclusão de todos os registros envolvidos
		$success = $this->multiBond->log($iTransaction, $tsNow);
		
//var_dump($success);
//echo "</pre>";
		
		return $success;
	}
	
	
}