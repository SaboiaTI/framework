<?php
/**
 * Classe que permite manipular um objeto MultiBondQuery
 * TO-DO: Documentar isso...
**/

require_once($_SERVER["DOCUMENT_ROOT"]."/shared/libraries/php/core.php");
require_once($_SERVER["SYSTEM_COMMON"]."/shared/data/multibond/MultiBond.php");
require_once($_SERVER["SYSTEM_COMMON"]."/shared/data/multibond/QueryFilter.php");

class MultiBondQuery {
	
	private $multiBond;		// instância de MultiBond utilizada por este Query
	public $params;			// lista de parâmetros recebidos através dos diversos métodos do Query
	
	
	
	public function __construct(MultiBond $mb=NULL){
		
		$this->multiBond = $mb;
		$this->params = array();
		
	}
	
	
	/**
	 * Limpa todos os parâmetros recebidos, reiniciando o Query
	**/
	public function resetParams(){
		$this->params = array();
		return true;
	}
	
	
	/**
	 * Função comum aos métodos bondedObjects(), unite(), intersect() e minus(),
	 * verifica se a estrutura de bonds e ties solicitada existe no SCHEMA do MultiBond, 
	 * e se todos os parâmetros necessários foram passados corretamente.
	 * Retorna a array de argumentos validada e convertida, pronta para uso pelas funções acima citadas.
	 * 
	 * @param int $args["id"]                id do Object a partir do qual buscamos os vínculos
	 * @param array|string $args["thisTie"]  tipo de laço (SCHEMATie) que vincula o Object de referência (opcional, usando *)
	 * @param array|string $args["bondType"] tipo de vínculo (SCHEMABond) entre os Objects (opcional, usando *)
	 * @param array|string $args["thatTie"]  tipo de laço (SCHEMATie) que vincula o Object buscado (opcional, usando *)
	 * @param array|string $args["thatType"] tipo de Object (SCHEMAObject) buscado (opcional, usando *)
	 * @param string $args["filterParam"]	 string de pesquisa, usando sintaxe SQL, aplicada nas propriedades (SCHEMAProperty) do Object buscado
	 * @return object|NULL
	**/
	private function _validadeBondArgs(array $args=array()){
		
		// Valores default para os parâmetros, que serão mesclados com os parâmetros recebidos
		$default_values = array(
			"id"          => NULL,
			"thisTie"     => "",
			"bondType"    => "",
			"thatTie"     => "",
			"thatType"    => "",
			"filterParam" => ""
		);
		
		$args = array_merge($default_values, $args);
		
		// o parâmetro args["id"] é obrigatório
		if (is_null($args["id"])) return NULL;
		
		
		// o parâmetro args["thisTie"] é obrigatório,
		// para listar todos os registros, exigimos o parâmetro como "*"
		// que então convertemos para "" (vazio)
		if (is_string($args["thisTie"]) && trim($args["thisTie"]) == "") return NULL;
		if (is_string($args["thisTie"]) && trim($args["thisTie"]) == "*") $args["thisTie"] = "";
		
		
		// o parâmetro args["bondType"] é obrigatório,
		// para listar todos os registros, exigimos o parâmetro como "*"
		// que então convertemos para "" (vazio)
		if (is_string($args["bondType"]) && trim($args["bondType"]) == "") return NULL;
		if (is_string($args["bondType"]) && trim($args["bondType"]) == "*") $args["bondType"] = "";
		
		
		// o parâmetro args["thatTie"] é obrigatório,
		// para listar todos os registros, exigimos o parâmetro como "*"
		// que então convertemos para "" (vazio)
		if (is_string($args["thatTie"]) && trim($args["thatTie"]) == "") return NULL;
		if (is_string($args["thatTie"]) && trim($args["thatTie"]) == "*") $args["thatTie"] = "";
		
		
		// o parâmetro args["thatType"] é obrigatório,
		// para listar todos os registros, exigimos o parâmetro como "*"
		// que então convertemos para "" (vazio)
		if (is_string($args["thatType"]) && trim($args["thatType"]) == "") return NULL;
		if (is_string($args["thatType"]) && trim($args["thatType"]) == "*") $args["thatType"] = "";
		
		
		// o parâmetro args["filterParam"] não pode ser passado em branco,
		// para listar todos os registros (sem filtro), exigimos o parâmetro como "*"
		// que então convertemos para "" (vazio)
		if (trim($args["filterParam"]) == "") return NULL;
		if (trim($args["filterParam"]) == "*") $args["filterParam"] = "";
		
		// aqui apenas é validado se filterParam existe como uma string, a validação de sintaxe e campos válidos é feita posteriormente
		// TO-DO: validar já aqui filterParam, e encerrar a execução em caso de erros
		
		
		//	o parâmetro args["thisTie"] pode ser uma string ou uma array de strings de SCHEMATie
		//	cada string é convertida para seu id equivalente
		if ($args["thisTie"] !== "") {
			$args["thisTie"] = $this->multiBond->getSCHEMATieId($args["thisTie"]);
			$temp_thisTie = array();
			foreach($args["thisTie"] as $roles) {
				foreach($roles as $r) {
					$temp_thisTie[] = $r;
				}
			}
			// se não encontrar o SCHEMA, retorna NULL, pois o vínculo pedido não existe!
			if (count($temp_thisTie) == 0) return NULL;
			$args["thisTie"] = $temp_thisTie;
			$args["thisTie"] = implode(",",$args["thisTie"]);
		}
		
		
		//	o parâmetro args["bondType"] pode ser uma string ou uma array de strings de SCHEMABond
		//	cada string é convertida para seu id equivalente
		if ($args["bondType"] !== "") {
			$args["bondType"] = $this->multiBond->getSCHEMABondId($args["bondType"]);
			$temp_bondType = array();
			foreach($args["bondType"] as $types) {
				foreach($types as $t) {
					$temp_bondType[] = $t;
				}
			}
			// se não encontrar o SCHEMA, retorna NULL, pois o vínculo pedido não existe!
			if (count($temp_bondType) == 0) return NULL;
			$args["bondType"] = $temp_bondType;
			$args["bondType"] = implode(",",$args["bondType"]);
		}
		
		
		//	o parâmetro args["thatTie"] pode ser uma string ou uma array de strings de SCHEMATie
		//	cada string é convertida para seu id equivalente
		if ($args["thatTie"] !== "") {
			$args["thatTie"] = $this->multiBond->getSCHEMATieId($args["thatTie"]);
			$temp_thatTie = array();
			foreach($args["thatTie"] as $roles) {
				foreach($roles as $r) {
					$temp_thatTie[] = $r;
				}
			}
			// se não encontrar o SCHEMA, retorna NULL, pois o vínculo pedido não existe!
			if (count($temp_thatTie) == 0) return NULL;
			$args["thatTie"] = $temp_thatTie;
			$args["thatTie"] = implode(",",$args["thatTie"]);
		}
		
		
		//	o parâmetro args["thatType"] pode ser uma string ou uma array de strings de SCHEMAObject
		//	cada string é convertida para seu id equivalente
		if ($args["thatType"] !== "") {
			$args["thatType"] = $this->multiBond->getSCHEMAObjectId($args["thatType"]);
			$temp_thatType = array();
			foreach($args["thatType"] as $roles) {
				foreach($roles as $r) {
					$temp_thatType[] = $r;
				}
			}
			// se não encontrar o SCHEMA, retorna NULL, pois o vínculo pedido não existe!
			if (count($temp_thatType) == 0) return NULL;
			$args["thatType"] = $temp_thatType;
			$args["thatType"] = implode(",",$args["thatType"]);
		}
		
		return $args;
	}
	
	/**
	 * Função comum aos métodos objects(), unite(), intersect() e minus(),
	 * verifica se a estrutura de objects solicitada existe no SCHEMA do MultiBond, 
	 * e se todos os parâmetros necessários foram passados corretamente.
	 * Retorna a array de argumentos validada e convertida, pronta para uso pelas funções acima citadas.
	 * 
	 * @param array|string $args["objectType"] tipo de Object (SCHEMAObject) buscado (opcional, usando *)
	 * @param string $args["filterParam"]	 string de pesquisa, usando sintaxe SQL, aplicada nas propriedades (SCHEMAProperty) do Object buscado
	 * @return object|NULL
	**/
	private function _validadeObjectArgs(array $args=array()){
		
		// Valores default para os parâmetros, que serão mesclados com os parâmetros recebidos
		$default_values = array(
			"objectType"  => "",
			"filterParam" => ""
		);
		
		$args = array_merge($default_values, $args);
		
		
		// o parâmetro args["objectType"] é obrigatório,
		// para listar todos os registros, exigimos o parâmetro como "*"
		// que então convertemos para "" (vazio)
		if (is_string($args["objectType"]) && trim($args["objectType"]) == "") return NULL;
		if (is_string($args["objectType"]) && trim($args["objectType"]) == "*") $args["objectType"] = "";
		
		
		//	o parâmetro args["objectType"] pode ser uma string ou uma array de strings de SCHEMAObject
		//	cada string é convertida para seu id equivalente
		if ($args["objectType"] !== "") {
			$args["objectType"] = $this->multiBond->getSCHEMAObjectId($args["objectType"]);
			$temp_objectType = array();
			foreach($args["objectType"] as $obj) {
				foreach($obj as $o) {
					$temp_objectType[] = $o;
				}
			}
			// se não encontrar o SCHEMA, retorna NULL, pois o vínculo pedido não existe!
			if (count($temp_objectType) == 0) return NULL;
			$args["objectType"] = $temp_objectType;
			$args["objectType"] = implode(",",$args["objectType"]);
		}
		
		
		// o parâmetro args["filterParam"] não pode ser passado em branco,
		// para listar todos os registros (sem filtro), exigimos o parâmetro como "*"
		// que então convertemos para "" (vazio)
		if (trim($args["filterParam"]) == "") return NULL;
		if (trim($args["filterParam"]) == "*") $args["filterParam"] = "";
		
		// aqui apenas é validado se filterParam existe como uma string, a validação de sintaxe e campos válidos é feita posteriormente
		// TO-DO: validar já aqui filterParam, e encerrar a execução em caso de erros
		
		return $args;
	}
	
	
	
	
	/**
	 * Função comum aos métodos get(), map() e count(),
	 * constrói uma string de query SQL, através de um set de parãmetros e um tipo de consulta
	 * Retorna a string com sintaxe SQL, pronta para uso pelas funções acima citadas.
	 * 
	 * @param $set int
	 * @return string
	**/
	private function _buildQuery($set=NULL) {
		
		if (!isset($this->params[$set])) return NULL;
		
		$type = isset($this->params[$set]["type"]) ? $this->params[$set]["type"] : NULL;
		$args = isset($this->params[$set]["args"]) ? $this->params[$set]["args"] : NULL;
		
		if (is_null($type)) return NULL;
		if (is_null($args)) return NULL;
		
		// se for um pedido para montagem de query de união ou intersecção de dados, tratamos de forma recursiva:
		if ( in_array($type, array("unite","intersect","minus")) ) {
			
			$query = "";
			$g = "";
			
			if ($type=="unite") 	$glue = " UNION ";
			if ($type=="intersect") $glue = " INNER JOIN ";
			
			
			foreach ($args as $set) {
				
				$tempQuery = $this->_buildQuery($set);
				if (is_null($tempQuery)) return NULL;
				
				if ( $type=="intersect" && (strpos($tempQuery, "SELECT `id` FROM")!==0) ) $tempQuery .= " AS `tb_".$set."` ";
				
				$query .= $g.$tempQuery;
				$g = $glue;
			}
			
			if ( $type=="intersect" && (strpos($query, "SELECT `id` FROM")!==0) ) {
				$query = "SELECT `id` FROM ".$query;
			}
			if ( $type=="intersect" ) {
				$query = $query." USING (`id`) ";
			}
				
//				$query = "SELECT `id`,`idSCHEMAObject`,`sSCHEMAObject`,`bond_id`,`bond_iType`,`bond_sType`,`this_id`,`this_iType`,`this_sType`,`that_id`,`that_iType`,`that_sType` FROM ".$query." USING (`id`,`idSCHEMAObject`,`sSCHEMAObject`,`bond_id`,`bond_iType`,`bond_sType`,`this_id`,`this_iType`,`this_sType`,`that_id`,`that_iType`,`that_sType`) ";
			
			return $query;
		}
		
		// se for um pedido para montagem de query de objetos vinculados:
		if ($type == "bondedObjects") return $this->_buildQueryBondedObjects($args);
		
		// se for um pedido para montagem de query de lista de objetos (sem vínculo):
		if ($type == "objects") return $this->_buildQueryObjects($args);
		
		return NULL;
	}
	
	/**
	 * Função específica para construir uma string de query de objetos vinculados
	 * usada por bondedObjects()
	 * @param $args array
	 * @return string
	**/
	private function _buildQueryBondedObjects(array $args=array()) {
		
		$id          = $args["id"];
		$thisTie     = $args["thisTie"];
		$bondType    = $args["bondType"];
		$thatTie     = $args["thatTie"];
		$thatType    = $args["thatType"];
		$filterParam = $args["filterParam"];
		
		
		$id = is_array($id) ? implode(',', $id) : $id;
		
		
		// montagem do WHERE da query, a partir dos tipos de vínculo e laço pesquisados
		$where  = "";
		$where .= $thisTie  == "" ? "" : ("AND `this`.`idSCHEMATie` IN (".$thisTie.")  ");
		$where .= $bondType == "" ? "" : ("AND `bond`.`idSCHEMABond`IN (".$bondType.") ");
		$where .= $thatTie  == "" ? "" : ("AND `that`.`idSCHEMATie` IN (".$thatTie.")  ");
		$where .= $thatType == "" ? "" : ("AND `o`.`idSCHEMAObject` IN (".$thatType.") ");
		
		$where .= " 
		AND `o`.`idGLOBALNamespace` = ".prepareSQL($this->multiBond->getNamespaceId($this->multiBond->getNamespace()))." 
		AND `o`.`fArchived` = 0 
		";
		
		
		// montagem dos critérios de pesquisa, através do objeto QueryFilter
		$filter = new QueryFilter();
		$queryJoinQueryFilter   = "";	// string com campos necessários para o bloco JOIN, devido à busca
		
		if ($filterParam != ""){
			
			$fields = array();
			$fi = $this->multiBond->getSCHEMAPropertyList( $this->multiBond->getSCHEMAObjectName($args["thatType"]) );
			foreach ($fi as $f) {
				$fields[$f["sKey"]] = array("sKey"=>$f["sKey"], "sDataType"=>$f["sDataType"]);
			}
			
			$filter->expression = $filterParam;
			$filter->acceptedFields = $fields;
			$parsedExpression = $filter->parseExpression();

			if ($filter->error) return NULL;
			
			foreach($filter->usedFields as $field) {
				
				$sKey      = $field["sKey"];
				$sDataType = $field["sDataType"];
				
				$queryJoinQueryFilter .= "
				LEFT OUTER JOIN `tbDATAProperty` AS `op_".$sKey."` 
				ON  `op_".$sKey."`.`idObject` = `o`.`id` 
				
				INNER JOIN `tbSCHEMAProperty` AS `p_".$sKey."` 
				ON  `p_".$sKey."`.`id` = `op_".$sKey."`.`idSCHEMAProperty` 
				AND `p_".$sKey."`.`sKey` = '".$sKey."' 
				";
			}
			
		} else {
			$filter->expression = "";
			$filter->acceptedFields = NULL;
			$parsedExpression = "";
		}
		
		
		// montagem da query
		$query = " (
		SELECT `o`.`id` AS `id`, 
		`o`.`idSCHEMAObject`,
		`thatType`.`sKey`     AS `sSCHEMAObject`, 
		`bond`.`id`           AS `bond_id`, 
		`bond`.`idSCHEMABond` AS `bond_iType`, 
		`schema_bond`.`sKey`  AS `bond_sType`, 
		`this`.`id`           AS `this_id`, 
		`this`.`idSCHEMATie`  AS `this_iType`, 
		`schema_this`.`sKey`  AS `this_sType`, 
		`that`.`id`           AS `that_id`, 
		`that`.`idSCHEMATie`  AS `that_iType`, 
		`schema_that`.`sKey`  AS `that_sType` 
		
		FROM `tbDATATie` `this` 
		
		INNER JOIN `tbSCHEMATie` `schema_this` 
		ON `this`.`idSCHEMATie` = `schema_this`.`id` 
		
		INNER JOIN `tbSCHEMATie` `this_schema` 
		ON `this`.`idSCHEMATie` = `this_schema`.`id` 
		
		INNER JOIN `tbDATABond` `bond` 
		ON `this`.`idBond` = `bond`.`id` 
		
		INNER JOIN `tbSCHEMABond` `schema_bond` 
		ON `bond`.`idSCHEMABond` = `schema_bond`.`id` 

		INNER JOIN `tbDATATie` `that` 
		ON `bond`.`id` = `that`.`idBond` 
		
		INNER JOIN `tbSCHEMATie` `schema_that` 
		ON `that`.`idSCHEMATie` = `schema_that`.`id` 
		
		INNER JOIN `tbSCHEMATie` `that_schema` 
		ON `that`.`idSCHEMATie` = `that_schema`.`id` 
		
		INNER JOIN `tbDATAObject` `o` 
		ON `that`.`idObject` = `o`.`id` 
		AND `o`.`id` <> `this`.`idObject` 
		
		INNER JOIN `tbSCHEMAObject` `thatType` 
		ON `o`.`idSCHEMAObject` = `thatType`.`id` 
		";
		$query .= $queryJoinQueryFilter;
		$query .= " 
		WHERE `this`.`idObject` IN (".$id.") ".$where." ";
		if ($parsedExpression!="") { $query .= "AND (".$parsedExpression.") "; }
		$query .= ") ";
		
		return $query;
		
	}
	
	/**
	 * Função específica para construir uma string de query de lista de objetos (sem vínculo)
	 * usada por objects()
	 * @param $args array
	 * @return string
	**/
	private function _buildQueryObjects(array $args=array()) {
		
		$objectType  = $args["objectType"];
		$filterParam = $args["filterParam"];
		
		
		
		// montagem dos critérios de pesquisa, através do objeto QueryFilter
		$filter = new QueryFilter();
		$queryJoinQueryFilter   = "";	// string com campos necessários para o bloco JOIN, devido à busca
		
		if ($filterParam != ""){
			
			$fields = array();
			$fi = $this->multiBond->getSCHEMAPropertyList( $this->multiBond->getSCHEMAObjectName($args["objectType"]) );
			foreach ($fi as $f) {
				$fields[$f["sKey"]] = array("sKey"=>$f["sKey"], "sDataType"=>$f["sDataType"]);
			}
			
			$filter->expression = $filterParam;
			$filter->acceptedFields = $fields;
			$parsedExpression = $filter->parseExpression();

			if ($filter->error) return NULL;
			
			foreach($filter->usedFields as $field) {
				
				$sKey      = $field["sKey"];
				$sDataType = $field["sDataType"];
				
				$queryJoinQueryFilter .= "
				LEFT OUTER JOIN `tbDATAProperty` AS `op_".$sKey."` 
				ON  `op_".$sKey."`.`idObject` = `o`.`id` 
				
				INNER JOIN `tbSCHEMAProperty` AS `p_".$sKey."` 
				ON  `p_".$sKey."`.`id` = `op_".$sKey."`.`idSCHEMAProperty` 
				AND `p_".$sKey."`.`sKey` = '".$sKey."' 
				";
			}
			
		} else {
			$filter->expression = "";
			$filter->acceptedFields = NULL;
			$parsedExpression = "";
		}
		
		
		// montagem da query
		$query = " (
		SELECT `o`.`id` AS `id` 
		FROM `tbDATAObject` AS `o` 
		";
		$query .= $queryJoinQueryFilter;
		
		$query .= "	WHERE `o`.`idSCHEMAObject` IN (".$objectType.") ";
		$query .= " AND `o`.`idGLOBALNamespace` = ".prepareSQL($this->multiBond->getNamespaceId($this->multiBond->getNamespace()))." ";
		if ($parsedExpression!="") { $query .= "AND (".$parsedExpression.") "; }
		$query .= " ) ";
		
		return $query;
		
	}
	
	
	
	/**
	 * @param array $args  uma array contendo a estrutura de vínculos buscados, 
	 * 					   ou um conjunto de índices existentes na array $this->params
	**/
	private function _addParam($type=NULL, array $args=array()){
		
		if (is_null($type)) return false;
		if (empty($args)) return false;
		
		if (!in_array($type, array("bondedObjects","objects","unite","intersect","minus"))) return false;
		
		
		// Se os argumentos recebidos não forem validados, não há como continuar a montagem das queries
		// algum dos tipos de SCHEMAObject, SCHEMABond ou SCHEMATie passados pode não existir
		if ($type == "bondedObjects") {
			$args = $this->_validadeBondArgs($args);
			if (is_null($args)) return false;
			
		}
		elseif ($type == "objects") {
			$args = $this->_validadeObjectArgs($args);
			if (is_null($args)) return false;
		}
		// se algum dos índices passados não existe, não há como continuar a montagem das queries
		elseif ( in_array($type, array("unite","intersect","minus")) ) {
			foreach ($args as $a){
				if (!isset($this->params[$a])) return false;
			}
		}
		
		$this->params[] = array("type"=>$type, "args"=>$args);
		
		$key = end(array_keys($this->params));
		return $key;
		
	}
	
	
	
	
	/**
	 * 
	**/
	public function bondedObjects(array $args=array()){
		return $this->_addParam("bondedObjects", $args);
	}
	
	
	/**
	 * 
	**/
	public function objects(array $args=array()){
		return $this->_addParam("objects", $args);
	}
	
	
	/**
	 * 
	**/
	public function unite(){
		if (func_num_args()==0) return false;
		return $this->_addParam("unite", func_get_args());
	}
	
	
	/**
	 * 
	**/
	public function intersect(){
		if (func_num_args()==0) return false;
		return $this->_addParam("intersect", func_get_args());
	}
	
	
	/**
	 * Em desenvolvimento
	**/
	public function minus(){
		// if (func_num_args()==0) return false;
		// return $this->_addParam("minus", $args);
	}
	
	
	
	
	
	/**
	 * Carrega uma lista de ids de Objects vinculados a um Object conhecido.
	 * Usa um ou mais elementos presentes na array $this->params; 
	 * Nesta array, estão organizados parâmetros de consulta recebidos pelos métodos bondedObjects(), objects(), unite(), intersect() e minus().
	 * @param int $set 			índice do parâmetro existente na array $this->params
	 * @param int $offset		offset de resultados (página a ser exibida)
	 * @param int $rowcount		quantidade de resultados (registros por página)
	 * @param string $order		ordem de exibição (em desenvolvimento)
	**/
	public function get($set=NULL, $offset=0, $rowcount=500, $order=NULL){
		
		// TO-DO: SELECT * está trazendo os campos: 
		// 		  se for objects(), traz apenas 'id'
		// 		  se for bondedObjects(), traz 'id','bond_iType','this_iType','that_iType'
		// por isso, o ORDER BY está apenas pelo 'id', é o único campo sempre presente...
		
		if (is_null($set)) return NULL;
		if (!isset($this->params[$set])) return NULL;
		
		$query  = "SELECT * "; //`id`, `bond_iType`, `this_iType`,  `that_iType` ";
		$query .= " FROM ( ";
		$query .= $this->_buildQuery($set);
		$query .= " ) `o` ";
		
		$query .= " ORDER BY `id` DESC "; //, `bond_iType`, `this_iType`, `that_iType` ";
		$query .= " LIMIT ".$offset.", ".$rowcount;
		
		//$GLOBALS["debug"]->add(NULL,$query);
		
		$result = $this->multiBond->db->query(utf8_decode($query));
		if (!$result) return NULL;
		
		
		$objects = array();
		while($row = $result->fetch_object()) {
			$objects[] = $row->id;
		}
		$result->free();
		return $objects;
		
	}
	
	public function map($set=NULL, $offset=0, $rowcount=500, $order=NULL){
		
		if (is_null($set)) return NULL;
		if (!isset($this->params[$set])) return NULL;
		
		$query  = "SELECT * ";
		$query .= " FROM ( ";
		$query .= $this->_buildQuery($set);
		$query .= " ) `o` ";
		
		$query .= " ORDER BY `id` DESC, `bond_iType`, `this_iType`, `that_iType` ";
		$query .= " LIMIT ".$offset.", ".$rowcount;
		
		
		$result = $this->multiBond->db->query(utf8_decode($query));
		if (!$result) return NULL;
		
		
		$associations = array();
		while($row = $result->fetch_object()) {
			
			$assoc = array();
			
			$assoc['thisTie']['id']        = $row->this_id;
			$assoc['thisTie']['schema']    = $row->this_sType;
			$assoc['bond']['id']           = $row->bond_id;
			$assoc['bond']['schema']       = $row->bond_sType;
			$assoc['thatTie']['id']        = $row->that_id;
			$assoc['thatTie']['schema']    = $row->that_sType;
			$assoc['thatObject']['id']     = $row->id;
			$assoc['thatObject']['schema'] = $row->sSCHEMAObject;
			
			$associations[] = $assoc;
		}
		$result->free();
		return $associations;
		
	}
	
	public function count($set=NULL){
		
		if (is_null($set)) return NULL;
		if (!isset($this->params[$set])) return NULL;
		
		$query = 'SELECT COUNT(DISTINCT `id`) AS `recordcount` ';
		$query .= " FROM ( ";
		$query .= $this->_buildQuery($set);
		$query .= " ) `o` ";
		
		
		$result = $this->multiBond->db->query(utf8_decode($query));
		if (!$result) return NULL;
		
		
		$recordcount = 0;
		while($row = $result->fetch_object()) {
			$recordcount = $row->recordcount;
		}
		$result->free();
		return $recordcount;
		
	}
	
}
