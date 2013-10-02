<?php
/**
 * Classe que fornece meios para manipular uma lista de objetos Vertex
 * Nesta classe já são resolvidos os métodos de leitura, paginação e ordem básicos, para serem extendidos às listas mais específicas
 */
require_once($_SERVER['SYSTEM_COMMON'].'/shared/data/multibond/MultiBond.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/shared/model/Settings.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/shared/model/BaseObject.php');

class BaseList {
	
	private $context;
	
	public  $recordList;			// Array: lista com os dados completos dos registros
	public  $totalPages;			// Int: número total de páginas de registros na lista
	public  $totalRecords;          // Int: número total de registros na lista
	public  $currentPage;           // Int: número da página atual, carregada (e exibida em tela)
	public  $currentPageRecords;    // Int: número total de registros na página atual
	
	protected $multiBond;
	
	public function __construct($SCHEMAObject=NULL, MultiBond $mb) {
		
		$this->context = filter_var($SCHEMAObject, FILTER_SANITIZE_STRING);
		
		$this->recordList         = array();
		$this->totalPages         = 0;
		$this->totalRecords       = 0;
		$this->currentPage        = 1;
		$this->currentPageRecords = 0;
		
		$this->multiBond = $mb;
	}
	
	
	
	/**
	 * @return Boolean
	 */
	public function loadBasicList(
						$tagFilters=NULL, 
						$statusFilters=NULL, 
						$typeFilters=NULL, 
						$starFilter=0, 
						$searchTerms=NULL, 
						$searchCriteria=NULL, 
						$moveTo=1, 
						$rowCount=100){
		
		$this->recordList = array();
		
		$starFilter = $starFilter==1;
		
		$idUserAccount = isset($_SESSION["idUserAccount"]) ? $_SESSION["idUserAccount"] : NULL;
		
		
		$filterParam = "";
		$temp_filterParam = array();
		
		// FILTROS: aplicados aos objetos
		// palavras-chave ("search terms")
		if (!is_null($searchTerms) && trim($searchTerms) != "") {
			$temp_searchTerms = array();
			$temp_searchTerms[] = " sCompanyName LIKE '%".trim($searchTerms)."%' ";
			$temp_searchTerms[] = " sTradeName LIKE '%".trim($searchTerms)."%' ";
			$temp_searchTerms[] = " sSite LIKE '%".trim($searchTerms)."%' ";
			$temp_searchTerms[] = " sCNPJ LIKE '%".trim($searchTerms)."%' ";
			
			$temp_searchTerms = implode(" OR ", $temp_searchTerms);
			$temp_filterParam[] = " (".$temp_searchTerms.") ";
		}
		
		// status
		if (!is_null($statusFilters)) {
			$temp_statusFilters = array();
			foreach ($statusFilters as $status){
				$temp_statusFilters[] = " fStatus = ".$status." ";
			}
			$temp_statusFilters = implode(" OR ", $temp_statusFilters);
			$temp_filterParam[] = " (".$temp_statusFilters.") ";
		}
		
		
		
		$filterParam = implode(" AND ", $temp_filterParam);
		if (trim($filterParam) === "") $filterParam = "*";
		
		
		
		// FILTROS: aplicados aos Bonds
		// tags
		if (!is_null($tagFilters)) {
			$taggedchannels = $this->multiBond->getBondedObjects(
				array(
					'id'          => $tagFilters,
					'thisTie'     => 'Tag',
					'bondType'    => 'Tagging',
					'thatTie'     => 'Tagged',
					'thatType'    => $context,
					'filterParam' => '*'
				));
		}
		
		// estrela
		if ($starFilter) {
			$starredchannels = $this->multiBond->getBondedObjects(
				array(
					'id'          => $idUserAccount,
					'thisTie'     => 'Star',
					'bondType'    => 'Starring',
					'thatTie'     => 'Starred',
					'thatType'    => $context,
					'filterParam' => '*'
				));
		}
		
		
		
		
		$intersect = NULL;
		if (isset($taggedchannels) && isset($starredchannels)) {
			$intersect = array_intersect($taggedchannels, $starredchannels);
		
		} elseif(isset($taggedchannels)) {
			$intersect = $taggedchannels;
			
		} elseif(isset($starredchannels)) {
			$intersect = $starredchannels;
		}
		
		
		
		// dados de paginação - primeira parte
		$this->totalRecords = $this->multiBond->countObjects(array(
													'objectType'  => $this->context,
													'filterParam' => $filterParam,
													'intersect'	  => $intersect
												));
		
		
		$this->totalPages  = ceil($this->totalRecords / $rowCount);
		$this->currentPage = max(1, min($moveTo, $this->totalPages));
		
		
		// recordlist
		$reqs = $this->multiBond->getObjects(array(
													'objectType'  => $this->context,
													'filterParam' => $filterParam,
													'page'        => $this->currentPage,
													'limit'       => $rowCount,
													'intersect'	  => $intersect
												));
												
		if (!$reqs || count($reqs) == 0) return $this->recordList;
		
		if (file_exists($_SERVER['DOCUMENT_ROOT']."/shared/model/{$this->context}.php")) {
			require_once($_SERVER['DOCUMENT_ROOT']."/shared/model/{$this->context}.php");
		}
		
		foreach ($reqs as $o) {
			
			$id = intval((int)$o);
			
			if (file_exists($_SERVER['DOCUMENT_ROOT']."/shared/model/{$this->context}.php")) {
				$obj = new $this->context($id, $this->multiBond);
			} else {
				$obj = new BaseObject($id, $this->context, $this->multiBond);
			}
			
			$success = $obj->load();
			$this->recordList[] = $obj->expose(false);
			
		}
		
		// dados de paginação - segunda parte
		$this->currentPageRecords = count($reqs);
		
		
		return $this->recordList;
		
	}
	
	
	
}
