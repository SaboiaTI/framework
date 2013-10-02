<?php
/**
 * Classe que fornece meios para manipular um objeto MultiBondObject, sem a necessidade de instanciar a classe MultiBondObject diretamente
 * Nesta classe já são resolvidos os métodos de leitura e gravação básicos, para serem extendidos aos objetos mais específicos
 */

require_once($_SERVER['DOCUMENT_ROOT'].'/shared/data/multibond/MultiBond.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/shared/data/multibond/MultiBondBond.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/shared/data/multibond/MultiBondTie.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/shared/data/multibond/MultiBondObject.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/shared/model/Settings.php');


class BaseObject extends MultiBondObject {

	private $context;

	protected $fStar;
	protected $tagList;
	protected $logList;


	public function __construct($id=NULL, $SCHEMAObject=NULL, MultiBond $mb=NULL, $autoLoad=true) {

		$this->context = filter_var($SCHEMAObject, FILTER_SANITIZE_STRING);
		$this->fStar = NULL;
		$this->tagList = array();
		$this->logList = array();

		parent::__construct($id, $SCHEMAObject, $mb, $autoLoad);

		// secretProperties são propriedades deste objeto que não podem ser expostas à API pelo método expose()
		// ou que não podem ser consultadas diretamente (por exemplo, a propriedade sPassword de um objeto User)
		// a classe MultiBondObject já possui uma lista de propriedades secretas. Devemos aqui adicionar as
		// propriedades específicas deste objeto a essa lista

		$secretProperties = array('logList');
		$this->secretProperties = array_merge($this->secretProperties, $secretProperties);
	}



	/**
	 * controla a exibição de propriedades, impedindo que propriedades secretas sejam exibidas
	 * redireciona a chamada ao método __get da classe MultiBondObject, que controla a array de propriedades
	 * @return mixed property value if valid; NULL otherwise
	 */
	public function __get($propertyName) {

		if (!in_array($propertyName, $this->secretProperties)){

			if (!array_key_exists($propertyName, get_object_vars($this))) {
				return parent::__get($propertyName);

			} else {
				return $this->$propertyName;
			}
		}

		return NULL;
	}



	/**
	 * controla a gravação de propriedades, impedindo que propriedades inexistentes sejam armazenadas no objeto
	 * redireciona a chamada ao método __get da classe MultiBondObject, que controla a array de propriedades
	 * este método é necessário para controlar quais propriedades foram alteradas na hora de salvar o objeto
	 * funciona como um alias para parent::setProperty, para criação de um novo valor para uma propriedade existente
	 * para EDITAR UM VALOR EXISTENTE, utilize o método 'setProperty';
	 * @return Boolean
	 */
	public function __set($propertyName, $value) {

		if (!in_array($propertyName, $this->secretProperties)){

			if (!array_key_exists($propertyName, get_object_vars($this))) {
				return parent::__set($propertyName, $value);

			} else {
				return $this->$propertyName;
			}
		}

		return false;
    }


	/**
	 * carrega do banco de dados as informações básicas (apenas as necessárias para exibição de lista) deste objeto
	 * por informações básicas, entendemos as propriedades do objeto, marcação como estrela e tags relacionadas
	 * @return Boolean
	 */
	public function load() {

		$success = parent::load();
		if (!$success) return false;

		// estrela
		$idUserAccount = isset($_SESSION['idUserAccount']) ? $_SESSION['idUserAccount'] : NULL;
		$this->fStar = $this->multiBond->isBonded(array(
								'thisObject'  => $this->id,
								'thisTie'     => 'Starred',
								'bondType'    => 'Starring',
								'thatTie'     => 'Star',
								'thatObject'  => $idUserAccount
							));

		// tags
		$this->loadTagList();

		return true;
	}





	/**
	 * carrega a lista de tags
	 * @return Boolean
	 */
	public function loadTagList() {

		$idUserAccount = isset($_SESSION['idUserAccount']) ? $_SESSION['idUserAccount'] : NULL;
		if (is_null($idUserAccount)) return false;

		$this->tagList = array();

		$objecttags = $this->multiBond->getBondedObjects(
			array(
				'id'          => $this->id,
				'thisTie'     => 'Tagged',
				'bondType'    => 'Tagging',
				'thatTie'     => 'Tag',
				'thatType'    => $this->context.'Tag',
				'filterParam' => '*'
			));
		if (!$objecttags || count($objecttags) == 0) return true;

		$usertags = $this->multiBond->getBondedObjects(
			array(
				'id'          => $idUserAccount,
				'thisTie'     => 'Owner',
				'bondType'    => 'Ownership',
				'thatTie'     => 'Owned',
				'thatType'    => $this->context.'Tag',
				'filterParam' => '*'
			));
		if (!$usertags || count($usertags) == 0) return true;

		$tags = array_intersect($objecttags,$usertags);

		foreach ($tags as $value) {

			$id = intval((int)$value);
			$tag = new Tag($id, $this->context, $this->multiBond);
			if ($tag) $this->tagList[] = $tag->expose();
		}

		return true;
	}

	/**
	 * Cria vínculo entre uma Tag e o objeto
	 * @return Boolean
	 */
	public function addTag($idTag=NULL) {

		if (is_null($this->id))        return false;
		if (is_null($idTag))           return false;
		if (is_null($this->multiBond)) return false;

		$success = $this->addBondToObject(array(
			'thisTieType'  => 'Tagged',		// este Object é classificado pela tag
			'bondType'     => 'Tagging',	// o SCHEMA de Bond usado para classificar por tags
			'thatTieType'  => 'Tag',		// o SCHEMA de Tie para a Tag que classifica o Object
			'thatId'       => $idTag, 		// o id da Tag que classifica este Object
			'reuseThisTie' => false,		// não tentar reusar o Tie deste Object!
			'reuseBond'    => true,			// se possível, reusar o Bond do Object Tag
			'reuseThatTie' => true			// se possível, reusar o Tie do Object Tag
		));

		if (!$success) return false;

		return $this->loadTagList();
	}

	/**
	 * Destrói o vínculo entre uma Tag e o objeto
	 * @return Boolean
	 */
	public function removeTag($idTag=NULL) {

		if (is_null($this->id))        return false;
		if (is_null($idTag))           return false;
		if (is_null($this->multiBond)) return false;

		$success = $this->removeBondToObject(array(
			'thisTieType'     => 'Tagged',			// este Object é classificado pela tag
			'bondType'        => 'Tagging',			// o SCHEMA de Bond usado para classificar por tags
			'thatTieType'     => 'Tag',            	// o SCHEMA de Tie para a Tag que classifica o Object
			'thatId'          => $idTag,  			// o id da Tag que classifica este Object
			'preserveThisTie' => false,				// nunca preservar o Tie deste Object
			'preserveThatTie' => true				// se possível, preservar o Tie do Object Tag
		));

		if (!$success) return false;

		return $this->loadTagList();
	}

	/**
	 * Destrói o vínculo entre todas as Tags e o object
	 * @return Boolean
	 */
	public function clearTags(){

		$idUserAccount = isset($_SESSION['idUserAccount']) ? $_SESSION['idUserAccount'] : NULL;

		if (is_null($idUserAccount))   return false;
		if (is_null($this->id))        return false;
		if (is_null($this->multiBond)) return false;


		// lista todas as Tags do UserAccount
		$idTags = $this->multiBond->getBondedObjects(array(
											'id'          => $idUserAccount,
											'thisTie'     => 'Owner',
											'bondType'    => 'Ownership',
											'thatTie'     => 'Owned',
											'thatType'    => $this->context.'Tag',
											'filterParam' => '*'
											));


		// se não houverem tags, não há mais nada a fazer
		if (!$idTags || count($idTags) == 0) return true;


		// deleta todos os Bonds entre as Tags encontradas e este objeto
		$idBonds = $this->multiBond->getBonds(array(
											'id'         => $this->id,
											'thisTie'    => 'Tagged',
											'bondType'   => 'Tagging',
											'thatTie'    => 'Tag',
											'thatObject' => $idTags
											));


		if (!$idBonds || count($idBonds) == 0) return true;

		$success = $this->multiBond->deleteTies(array(
											'idObject' => $this->id,
											'idBond'   => $idBonds,
											'tieType'  => 'Tagged'
											));
		return $success;
	}





	/**
	 * Cria vínculo do tipo Star entre um UserAccount e o Opportunity
	 * @return Boolean
	 */
	public function addStar() {

		$idUserAccount = isset($_SESSION['idUserAccount']) ? $_SESSION['idUserAccount'] : NULL;

		if (is_null($idUserAccount))   return false;
		if (is_null($this->id))        return false;
		if (is_null($this->multiBond)) return false;

		$success = $this->addBondToObject(array(
			'thisTieType'  => 'Starred',      // este Object é marcado como favorito
			'bondType'     => 'Starring',     // o SCHEMA de Bond usado para identificar favoritos
			'thatTieType'  => 'Star',         // o SCHEMA de Tie para o UserAccount que marcou como favorito
			'thatId'       => $idUserAccount, // o id do Object UserAccount que marcou como favorito
			'reuseThisTie' => false,          // não reusar o Tie deste Object!
			'reuseBond'    => true,           // se possível, reusar o Bond do Object UserAccount
			'reuseThatTie' => true            // se possível, reusar o Tie do Object UserAccount
		));

		return $success;

	}

	/**
	 * Destrói o vínculo do tipo Star entre um UserAccount e o Opportunity
	 * @return Boolean
	 */
	public function removeStar() {

		$idUserAccount = isset($_SESSION['idUserAccount']) ? $_SESSION['idUserAccount'] : NULL;

		if (is_null($idUserAccount))   return false;
		if (is_null($this->id))        return false;
		if (is_null($this->multiBond)) return false;


		$success = $this->removeBondToObject(array(
			'thisTieType'     => 'Starred',			// este Object é marcado como favorito
			'bondType'        => 'Starring',		// o SCHEMA de Bond usado para identificar favoritos
			'thatTieType'     => 'Star',            // o SCHEMA de Tie para o UserAccount que marcou como favorito
			'thatId'          => $idUserAccount,    // o id do Object UserAccount que marcou como favorito
			'preserveThisTie' => false,				// nunca preservar o Tie deste Object
			'preserveThatTie' => true				// se possível, preservar o Tie do Object UserAccount
		));

		return $success;

	}




	/**
	 * Cria um vínculo do tipo especificado entre este objeto e um outro objeto conhecido.
	 * Este método é bastante genérico, para ser usado pelas demais classes ao manipular seus Bonds da maneira correta.
	 * É possível informar ao método se devem ser reaproveitados Bonds e Ties.
	 *
	 * @param $thisTieType String    tipo de Schema do Tie ligado a este Object
	 * @param $bondType String       tipo de Schema do Bond que vincula os Objects
	 * @param $thatTieType String    tipo de Schema do Tie ligado ao outro Object
	 * @param $thatId Int            id do outro Object
	 * @param $reuseThisTie Boolean  indica se deve tentar reaproveitar o Tie ligado a este Object (do mesmo tipo)
	 * @param $reuseBond Boolean     indica se deve tentar reaproveitar o Bond que vincula os Objects (do mesmo tipo)
	 * @param $reuseThatTie Boolean  indica se deve tentar reaproveitar o Tie ligado ao outro Object (do mesmo tipo)
	 * @return Boolean
	 */
	public function addBondToObject(array $args=array()) {

		if ( is_null($this->id)        ) return false;
		if ( is_null($this->multiBond) ) return false;

		// default parameters values, to be merged with the received parameters
		$default_values = array(
			'thisTieType'  => NULL,
			'bondType'     => NULL,
			'thatTieType'  => NULL,
			'thatId'       => NULL,
			'reuseThisTie' => false,
			'reuseBond'    => false,
			'reuseThatTie' => false
		);

		$args = array_merge($default_values, $args);

		$thisTieType = filter_var($args['thisTieType'],   FILTER_SANITIZE_STRING);
		$bondType    = filter_var($args['bondType'],      FILTER_SANITIZE_STRING);
		$thatTieType = filter_var($args['thatTieType'],   FILTER_SANITIZE_STRING);
		$thatId      = intval(filter_var($args['thatId'], FILTER_SANITIZE_NUMBER_INT),10);

		$reuseThisTie = filter_var($args['reuseThisTie'], FILTER_VALIDATE_BOOLEAN);
		$reuseBond    = filter_var($args['reuseBond'],    FILTER_VALIDATE_BOOLEAN);
		$reuseThatTie = filter_var($args['reuseThatTie'], FILTER_VALIDATE_BOOLEAN);


		// Agora os valores recebidos foram normatizados.
		// Prosseguimos com a execução do método

		if ( is_null($thisTieType) || !$thisTieType ) return false;
		if ( is_null($bondType)    || !$bondType    ) return false;
		if ( is_null($thatTieType) || !$thatTieType ) return false;
		if ( is_null($thatId)      || $thatId==0    ) return false;


		// se já houver Bond e Ties dos tipos especificados,
		// não há nada que precise ser feito e encerramos por aqui!
		if ($this->multiBond->isBonded(array(
			'thisObject'  => $this->id,
			'thisTie'     => $thisTieType,
			'bondType'    => $bondType,
			'thatTie'     => $thatTieType,
			'thatObject'  => $thatId
		))) return true;



		// se já houver um Bond do tipo especificado, e $reuseBond==true, vamos reaproveitá-lo.
		// do contrário, criaremos um novo Bond
		// entretanto, o Bond somente poderá ser reaproveitado se houver instruções para reutilizar também um de seus Ties
		if ( $reuseBond ) {
			$bondToThis = $reuseThisTie ? $this->multiBond->getBondsByObject($this->id, $bondType) : array();
			$bondToThat = $reuseThatTie ? $this->multiBond->getBondsByObject($thatId,   $bondType) : array();
			$bond = array_merge($bondToThis, $bondToThat);
		}

		if ( isset($bond) && count($bond)>0 ) {
			$idBond = $bond[0];
		} else {
			$bond = new MultiBondBond(NULL, $bondType, $this->multiBond);
			$idBond = $bond->save();
		}
		if (!$idBond) return false;



		// se já houver um thisTie do tipo especificado, e $reuseThisTie==true, vamos reaproveitá-lo.
		// do contrário, criaremos um novo Tie
		if ( $reuseThisTie ) $thisTie = $this->multiBond->getTies(array(
			'idObject' => $this->id,
			'tieType'  => $thisTieType,
			'idBond'   => $idBond
		));

		if ( isset($thisTie) && count($thisTie)>0 ) {
			$idThisTie = $thisTie[0];
		} else {
			$thisTie = new MultiBondTie(array(
										'id'           => NULL,
										'SCHEMATie'    => $thisTieType,
										'idObject'     => $this->id,
										'idBond'       => $idBond,
										'mb'           => $this->multiBond
									));
			$idThisTie = $thisTie->save();
		}
		if (!$idThisTie) return false;



		// se já houver um thatTie do tipo especificado, e $reuseThatTie==true, vamos reaproveitá-lo.
		// do contrário, criaremos um novo Tie
		if ( $reuseThatTie ) $thatTie = $this->multiBond->getTies(array(
			'idObject' => $thatId,
			'tieType'  => $thatTieType,
			'idBond'   => $idBond
		));

		if ( isset($thatTie) && count($thatTie)>0 ) {
			$idThatTie = $thatTie[0];
		} else {
			$thatTie = new MultiBondTie(array(
										'id'           => NULL,
										'SCHEMATie'    => $thatTieType,
										'idObject'     => $thatId,
										'idBond'       => $idBond,
										'mb'           => $this->multiBond
									));
			$idThatTie = $thatTie->save();
		}
		if (!$idThatTie) return false;

		return true;

	}


	/**
	 * Remove um vínculo do tipo especificado entre este objeto e um outro objeto conhecido.
	 * Assim como o addBondToObject, este método é bastante genérico para ser usado pelas
	 * demais classes ao manipular seus Bonds da maneira correta.
	 * É possível informar ao método se deve ser preservado o Bond e o thisTie.
	 *
	 * @param $thisTieType String    	tipo de Schema do Tie ligado a este Object
	 * @param $bondType String       	tipo de Schema do Bond que vincula os Objects
	 * @param $thatTieType String    	tipo de Schema do Tie ligado ao outro Object
	 * @param $thatId Int            	id do outro Object
	 * @param $preserveThisTie Boolean  indica se deve preservar o Tie ligado a este Object (do mesmo tipo)
	 * @param $preserveThatTie Boolean  indica se deve preservar o Tie ligado ao outro Object (do mesmo tipo)
	 * @return Boolean
	 */
	public function removeBondToObject(array $args=array()) {

		if ( is_null($this->id)        ) return false;
		if ( is_null($this->multiBond) ) return false;

		// default parameters values, to be merged with the received parameters
		$default_values = array(
			'thisTieType'     => NULL,
			'bondType'        => NULL,
			'thatTieType'     => NULL,
			'thatId'          => NULL,
			'preserveThisTie' => false,
			'preserveThatTie' => false
		);

		$args = array_merge($default_values, $args);

		$thisTieType     = filter_var($args['thisTieType'],     FILTER_SANITIZE_STRING);
		$bondType        = filter_var($args['bondType'],        FILTER_SANITIZE_STRING);
		$thatTieType     = filter_var($args['thatTieType'],     FILTER_SANITIZE_STRING);
		$thatId          = intval(filter_var($args['thatId'],   FILTER_SANITIZE_NUMBER_INT),10);
		$preserveThisTie = filter_var($args['preserveThisTie'], FILTER_VALIDATE_BOOLEAN);
		$preserveThatTie = filter_var($args['preserveThatTie'], FILTER_VALIDATE_BOOLEAN);



		// Agora os valores recebidos foram normatizados.
		// Prosseguimos com a execução do método

		if ( is_null($thisTieType) || !$thisTieType ) return false;
		if ( is_null($bondType)    || !$bondType    ) return false;
		if ( is_null($thatTieType) || !$thatTieType ) return false;
		if ( is_null($thatId)      || $thatId==0    ) return false;


		// se NÃO houver Bond e Ties dos tipos especificados entre os objetos,
		// não há nada que precise ser feito e encerramos por aqui!
		if (!$this->multiBond->isBonded(array(
			'thisObject'  => $this->id,
			'thisTie'     => $thisTieType,
			'bondType'    => $bondType,
			'thatTie'     => $thatTieType,
			'thatObject'  => $thatId
		))) return true;



		// Devemos encontrar o SCHEMAObject do $thatId, e passá-lo para o 'mapBondedObjects()'
		// como 'thatType', para ter certeza de quais Ties serão deletados
		$thatSchema = $this->multiBond->getObjectSCHEMA($thatId);
		if (is_null($thatSchema)) return false;


		// mapeia o vínculo entre os dois Objects, para encontrar as informações do Bond e dos Ties
		$map = $this->multiBond->mapBondedObjects(array(
							'id'          => $this->id,
							'thisTie'     => $thisTieType,
							'bondType'    => $bondType,
							'thatTie'     => $thatTieType,
							'thatType'    => $thatSchema,
							'filterParam' => '*',
							'order'       => '',
							'offset'      => 0,
							'rowcount'    => 1000,
							'intersect'   => $thatId
						));

		// se não encontrarmos um (E APENAS UM) vínculo com os critérios fornecidos,
		// há algo errado com os Bonds e Ties, e não podemos continuar...
		if (!$map || count($map)!=1) return false;


		$idThisTie = $map[0]['thisTie']['id'];
		$idThatTie = $map[0]['thatTie']['id'];


		// De acordo com os parâmetros $preserveThisTie e $preserveThatTie, deletamos apenas o thisTie, ou ambos
		// Ao deletar ambos os Ties, o próprio processo de exclusão dos Ties pelo MultiBond se encarregará de excluir o Bond órfão
		// Ao preservar algum dos Ties, o Bond não ficará órfão, e não será deletado (contrariando o que o nome deste método sugere...)
		// Por padrão, esta função não preserva nenhum dos Ties, e consequentemente, não preserva o Bond.

		$tiesToDelete = array();

		if (!$preserveThisTie) { $tiesToDelete = array_merge($tiesToDelete, array($idThisTie)); }
		if (!$preserveThatTie) { $tiesToDelete = array_merge($tiesToDelete, array($idThatTie)); }


		$success = $this->multiBond->deleteTiesById($tiesToDelete);

		return $success;

	}



}
