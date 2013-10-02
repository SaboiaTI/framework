<?php
/**
 * 
 */

class Filter {

	private	$error;
	private	$errorDescription;
	private $usedFields;
	
	private $expression;
	private $acceptedFields;
	private $acceptedOperators;
	private $stop;



	public function __construct($expression='', $acceptedFields=NULL) {
		
		$this->error 				= false;
		$this->errorDescription 	= '';
		$this->usedFields 			= array();
		
		$this->expression 			= $expression;
		$this->acceptedFields 		= $acceptedFields;
		
		$this->acceptedOperators = array(
			
			// precedence 1:
			array(
				array(
					'key' 		=> 'UNARY_MINUS',
					'find' 		=> '-',
					'replace' 	=> '-',
					'type' 		=> 'PREFIXED_UNARY',
					)
			),
			// precedence 2:
			array(
				array(
					'key' 		=> 'MULTIPLICATION',
					'find' 		=> '*',
					'replace' 	=> '*',
					'type' 		=> 'BINARY',
					),
				array(
					'key' 		=> 'DIVISION',
					'find' 		=> '/',
					'replace' 	=> '/',
					'type' 		=> 'BINARY',
					),
				array(
					'key' 		=> 'INT_DIVISION',
					'find' 		=> 'DIV',
					'replace' 	=> 'DIV',
					'type' 		=> 'BINARY',
					),
				array(
					'key' 		=> 'MODULO',
					'find' 		=> 'MOD',
					'replace' 	=> 'MOD',
					'type' 		=> 'BINARY',
					)
			),
			// precedence 3:
			array(
				array(
					'key' 		=> 'MINUS',
					'find' 		=> '-',
					'replace' 	=> '-',
					'type' 		=> 'BINARY',
					),
				array(
					'key' 		=> 'ADDITION',
					'find' 		=> '+',
					'replace' 	=> '+',
					'type' 		=> 'BINARY',
					)
			),
			// precedence 4:
			array(
				array(
					'key' 		=> 'EQUAL',
					'find' 		=> '=',
					'replace' 	=> '=',
					'type' 		=> 'BINARY',
					),
				array(
					'key' 		=> 'NOT_EQUAL',
					'find' 		=> '!=',
					'replace'	=> '<>',
					'type'		=> 'BINARY',
					),
				array(
					'key'	 	=> 'GREATER_THAN_OR_EQUAL',
					'find' 		=> '>=',
					'replace'	=> '>=',
					'type'	 	=> 'BINARY',
					),
				array(
					'key' 		=> 'GREATER_THAN',
					'find' 		=> '>',
					'replace' 	=> '>',
					'type'		=> 'BINARY',
					),
				array(
					'key'		=> 'LESS_THAN_OR_EQUAL',
					'find' 		=> '<=',
					'replace' 	=> '<=',
					'type' 		=> 'BINARY',
					),
				array(
					'key' 		=> 'LESS_THAN',
					'find' 		=> '<',
					'replace' 	=> '<',
					'type'		=> 'BINARY',
					),
				array(
					'key' 		=> 'NOT_EQUAL',
					'find' 		=> '<>',
					'replace' 	=> '<>',
					'type'		=> 'BINARY',
					),
				array(
					'key'		=> 'LIKE',
					'find' 		=> 'LIKE',
					'replace' 	=> 'LIKE',
					'type'		=> 'BINARY',
					),
				array(
					'key'		=> 'REGEXP',
					'find' 		=> 'REGEXP',
					'replace' 	=> 'REGEXP',
					'type'		=> 'BINARY',
					),
				array(
					'key' 		=> 'NULL_SAFE_EQUAL',
					'find' 		=> '<=>',
					'replace' 	=> '<=>',
					'type'		=> 'BINARY',
					),
				array(
					'key'		=> 'IS',
					'find'		=> 'IS',
					'replace'	=> 'IS',
					'type'		=> 'BINARY',
					),
				array(
					'key'		=> 'IN',
					'find'		=> 'IN',
					'replace'	=> 'IN',
					'type'		=> 'BINARY',
					)
			),
			// precedence 5:
			array(
				array(
					'key' 		=> 'NOT',
					'find' 		=> 'NOT',
					'replace' 	=> 'NOT',
					'type'		=> 'PREFIXED_UNARY',
					)
			),
			// precedence 6:
			array(
				array(
					'key'		=> 'AND',
					'find'		=> 'AND',
					'replace'	=> 'AND',
					'type'		=> 'BINARY',
					)
			),
			// precedence 7:
			array(
				array(
					'key'		=> 'XOR',
					'find'		=> 'XOR',
					'replace'	=> 'XOR',
					'type'		=> 'BINARY',
					)
			),
			// precedence 8:
			array(
				array(
					'key'		=> 'OR',
					'find'		=> 'OR',
					'replace'	=> 'OR',
					'type'		=> 'BINARY',
					)
			)
		);
		
	}


	
	public function __get ($propertyName) {
		
		if (
			$propertyName == 'error' 			|| 
			$propertyName == 'errorDescription' || 
			$propertyName == 'usedFields' 		||
			$propertyName == 'expression' 		||
			$propertyName == 'acceptedFields'	
		) {
			return $this->$propertyName;
		}
    }
	
	public function __set($propertyName, $value) {
		
		if (
			($propertyName == 'expression'     && is_string($value)) || 
			($propertyName == 'acceptedFields' && is_array($value))
		) {
			$this->$propertyName = $value;
		}
	}

	
	// procura pelos operadores e suas propriedades
	private function getOperatorsByPrecedence(&$elements) {
		
		// Identifica se elementos são operadores
		for ($i=0; $i<count($this->acceptedOperators); $i++) {
			
			$precedence = $this->acceptedOperators[$i];
		
			foreach ($precedence as $op) {
				
				foreach($elements as $key => $el) {

					$found = array_keys($op, $el['value']);
					
					if ($found) {
						$elements[$key]['isOperator'] = true;
					}
				}
			}
		}

		// Separa operadores por precedência

		$operatorsByPrecedence = array();
		
		for ($i=0; $i<count($this->acceptedOperators); $i++) {
			
			$precedence = $this->acceptedOperators[$i];
			
			$tempOrder = array();

			foreach ($precedence as $op) {
			
				$count = 0;
				foreach($elements as $key => $el) {
				
					if (!$elements[$key]['operatorFound']) {
						
						$found = array_keys($op, $el['value']);

						if ($found && $op['type'] == "PREFIXED_UNARY" && $key != 0 && !$elements[$key - 1]['isOperator']) $found = false;
						
						if ($found) {

							$tempOrder[$count] = array(
													'key' 		=> $op['key'],
													'find' 		=> $op['find'],
													'replace' 	=> $op['replace'],
													'type' 		=> $op['type'],
													'stringPosition' => $el['position'],
													'precedence' => $i,
													'elementPosition' => $count
													);
						
							$elements[$key]['operatorFound'] = true;
						
						}

					}

					$count++;
				
				}
				
			}

			for($j = 0; $j < count($elements); $j++){
				if (array_key_exists($j,$tempOrder)){
					$operatorsByPrecedence[] = $tempOrder[$j];
				}
			}

			
		}
	
		return $operatorsByPrecedence;
		
	}


	// encontra o operador na expressão, retornando uma array com os parâmetros, organizados de acordo com o tipo de operador
	private function getParameters($operator, $elements) {
		
		switch($operator['type']) {
			
			case 'BINARY' :
				
				$left  = array();
				$right = array();
				
				for ($i=0; $i<=$operator['elementPosition']-1; $i++) {
					$left[] = $elements[$i];
				}
				
				for ($i=$operator['elementPosition']+1; $i<count($elements); $i++) {
					$right[] = $elements[$i];
				}
				
				$parameters = array(
									'left'  => $left,
									'right' => $right
								);
				
			break;
			
			case 'PREFIXED_UNARY' :
				
				$right = array();
				
				for ($i=$operator['elementPosition']+1; $i<count($elements); $i++) {
					$right[] = $elements[$i];
				}
				
				$parameters = array(
									'right' => $right
								);
				
			break;
			
		}
		
		return $parameters;
		
	}


	// reordena a array, colocando elementos mais longos no início
	private function sortArray($originalArray) {
		
		$sortedArray = array();
		
		for ($i=1; count($sortedArray) < count($originalArray); $i++) {
			foreach ($originalArray as $item) {
				if (strlen($item) == $i) array_unshift($sortedArray, $item);
			}
		}
		
		return $sortedArray;
	}


	// separa a string em elementos individuais de operadores, expressões, strings
	private function splitElements($subject) {

		if (!is_string($subject)) return array();

		$op_splitters		 = array('=', '<', '>', '.', '!', '+', '-', '*', '/', '\\', '%', '|', '&', '==', '<=', '>=', '!=', '<>', '||', '&&', '<<', '>>');
		$reg_splitters		 = array(' ', '::', chr(10), chr(13));
		$string_delimiters	 = array('"' => '"', "'" => "'", "[" => "]");
		$encloser_delimiters = array('(' => ')', '{' => '}');

		// reordena a array, colocando elementos mais longos no início
		$op_splitters  = $this->sortArray($op_splitters);
		$reg_splitters = $this->sortArray($reg_splitters);



		$elements 		= array();
		$element 		= '';
		$end_string 	= '';
		$stack_array	= array();
		$position		= 0;
		
		for ($position=0; $position<strlen($subject); $position++) {
			
			$loop = false;
			
			$current_char = substr($subject,$position,1);
			
			// --------------------------------------------------
			// verifica por aspas, para identificar as strings
			if ($current_char === $end_string) {
				
				$end_string = '';

				if ($element !== '' && count($stack_array) === 0) {
					
					$elements[] = array(
										'value' => $element.$current_char,
										'position' => $position - strlen($element),
										'isOperator' => false,
										'isString' => true,
										'isEnclosure' => false,
										'operatorFound' => false
										);
					$element = '';
					continue;
				}
				
			} elseif ($end_string === '' && array_key_exists ($current_char, $string_delimiters )) {
				
				if ($element !== '' && count($stack_array) === 0) {
				
					$elements[] = array(
										'value' => $element,
										'position' => $position - strlen($element),
										'isOperator' => false,
										'isString' => false,
										'isEnclosure' => false,
										'operatorFound' => false
										);
					$element = '';
				}
				$end_string = $string_delimiters[$current_char];
			
			} elseif ($end_string === '') {

				// --------------------------------------------------
				// verifica por (, para identificar enclosures
				if ($current_char === end($stack_array)) {
					
					array_pop($stack_array);

					if (count($stack_array) === 0) {
						
						if ($element !== '') {
						
							$elements[] = array(
										'value' => $element.$current_char,
										'position' => $position - strlen($element),
										'isOperator' => false,
										'isString' => false,
										'isEnclosure' => true,
										'operatorFound' => false									
										);
							$element = '';
							continue;
						}
					}

				} elseif (array_key_exists ($current_char, $encloser_delimiters )) {
				
					array_push($stack_array, $encloser_delimiters[$current_char]);
					
					if (count($stack_array) === 1) {
				
						if ($element !== '') {
							$elements[] = array(
										'value' => $element,
										'position' => $position - strlen($element),
										'isOperator' => false,
										'isString' => false,
										'isEnclosure' => false,
										'operatorFound' => false									
										);
						}
						$element = '';
					}
					
				} elseif (count($stack_array) === 0) {

					// --------------------------------------------------
					// não sendo um bloco de string entre aspas nem um bloco entre enclosures, 
					// verifica por operadores e delimitadores
					
					// compara o caractere com a lista de separadores padrão
					// caso seja um separador, guarda o elemento atual na array de elementos
					// e avança para o próximo caractere (position + 1)
					foreach ($reg_splitters as $splitter) {
						
						if ($splitter === substr($subject, $position, strlen($splitter))) {
							
							if ($element !== '') {
								$elements[] = array(
										'value' => $element,
										'position' => $position - strlen($element),
										'isOperator' => false,
										'isString' => false,
										'isEnclosure' => false,
										'operatorFound' => false									
										);
							}
							$element = '';
							$position += strlen($splitter) - 1;
							$loop = true;
							break;
						}
					}
					if ($loop) continue;
					
					// compara o caractere com a lista de operadores que agem como separadores
					// caso seja um separador, guarda o elemento atual na array de elementos
					// e avança para o caractere após o operador (position + tamanho da string do operador)
					foreach ($op_splitters as $splitter) {
						
						if ($splitter === substr($subject, $position, strlen($splitter))) {
							
							if ($element != '') {
								$elements[] = array(
										'value' => $element,
										'position' => $position - strlen($element),
										'isOperator' => false,
										'isString' => false,
										'isEnclosure' => false,
										'operatorFound' => false
										);
							}
							$element = '';
							
							$elements[] = array(
										'value' => $splitter,
										'position' => $position - strlen($element),
										'isOperator' => false,
										'isString' => false,
										'isEnclosure' => false,
										'operatorFound' => false
										);
							$position += strlen($splitter) - 1;
							$loop = true;
							break;
						}
					}
					if ($loop) continue;
				}
			}
			
			$element .= $current_char;
		}
		
		if ($element != '') {
			
			$elements[] = array(
						'value' => $element,
						'position' => $position - strlen($element),
						'isOperator' => false,
						'isString' => false,
						'isEnclosure' => false,
						'operatorFound' => false					
						);
		}
		
		return $elements;
	}

	

	private function _parseExpression($subject, $hint='') {
		
		if ($this->stop-- <= 0) return '';
		
		// verifica se o parâmetro é uma lista separada por vírgulas
		if ($hint == 'CSV') {
			
			$validation = '/^\(((\s*(\'[^\']*\'|[^\,])\s*,\s*){1,}(\s*(\'[^\']*\'|[^\,])\s*))\)$/';
			
			if (!preg_match($validation, $subject)) {
				$this->error = true;
				return "<<<<< ERROR: Invalid operand; enclosed CSV expected >>>>>";
			}
			
			return $subject;
		}
		
		
		// separa o parametro subject em elementos individuais de operadores, expressões e strings
		$elements = $this->splitElements($subject);

		//	echo 'stop: '.$this->stop.'<br>';
		//	echo 'subject:  '.nl2br(str_replace(' ','&nbsp;',$subject),false).'<br>';
		//	echo 'position: ';
		//	for ($i=0; $i<strlen($subject); $i++) { echo ($i%10); }
		//	echo '<br>-----<br>';

		if (count($elements) == 0) {
			$this->error = true;
			return "<<<<< ERROR: Missing elements >>>>>";
		}
		//	echo '$elements:<br>';
		//	print_r($elements);
		//	echo "<br>";
		
		$operatorsArray = $this->getOperatorsByPrecedence($elements);
		//echo 'operadores organizados por precedência:<br>';
		//print_r($operatorsArray);
		//echo '<br>';

	
		if (count($elements) == 1) {

			// retorna o elemento como string:
			if ($elements[0]['isString']) {
				return $elements[0]['value'];
			
			// retorna o elemento como enclosure:
			} elseif ($elements[0]['isEnclosure']) {
				return $this->_parseExpression(substr($elements[0]['value'], 1, strlen($elements[0]['value'])-2));
			
			// se o elemento NÃO for um operador:
			} elseif (!$elements[0]['isOperator']) {
				
				// identifica se o elemento é um nome de campo válido da tabela tbDATAObject:
				if (
					   $elements[0]['value'] == 'id'
					|| $elements[0]['value'] == 'idSCHEMAMultiBond'
					|| $elements[0]['value'] == 'idGLOBALNamespace'
					|| $elements[0]['value'] == 'idGLOBALUser'
					|| $elements[0]['value'] == 'tsCreation'
					|| $elements[0]['value'] == 'tsLastUpdate'
					|| $elements[0]['value'] == 'fArchived'
					|| $elements[0]['value'] == 'fDeleted'
					
				) {
					// não é necessário adicionar os campos da tabela tbDATAObject:
	//				if (!in_array($elements[0]['value'], $this->usedFields)) $this->usedFields[] = $elements[0]['value'];
					return '`o`.`'.$elements[0]['value'].'`';
				}
				
				// identifica se o elemento é um nome de campo válido da tabela tbSCHEMAProperty:
				// para isso, usa a propriedade 'acceptedFields'
				// também aqui é armazenado o campo utilizado em uma lista, para montagem posterior do JOIN necessário ao comando SQL, pelo MultiBond
	//			else if (!is_null($this->acceptedFields) && in_array($elements[0]['value'], $this->acceptedFields)) 
				else if (!is_null($this->acceptedFields) && array_key_exists($elements[0]['value'], $this->acceptedFields)) {
					
	//				if (!in_array($elements[0]['value'], $this->usedFields)) $this->usedFields[] = $elements[0]['value'];
					if (!array_key_exists($elements[0]['value'], $this->usedFields)) {
						$this->usedFields[$elements[0]['value']] = array(
																'sKey' => $elements[0]['value'], 
																'sDataType' => $this->acceptedFields[$elements[0]['value']]['sDataType']
																);
					}
					return '`o`.`'.$elements[0]['value'].'`';
				}
				
				// verifica se o elemento é um valor numérico
				else if (
					is_numeric($elements[0]['value']) || 
					is_float($elements[0]['value']) || 
					is_int($elements[0]['value'])
				) {
					return $elements[0]['value'];
				}
				
// não implementado ainda, o valor NULL não pode ser verificado, 
// pois o registro em tbDATAProperty não armazena o valor NULL, ele simplesmente é removido
//				// verifica se o elemento é o valor NULL
//				else if (
//					is_null($elements[0]['value']) || 
//					(is_string($elements[0]['value']) && strtoupper($elements[0]['value']) == 'NULL')
//				) {
//					return $elements[0]['value'];
//				}
				
				// caso contrário, não é nenhum argumento ou operador válido
				else {
					$this->error = true;
					return '<<<<< ERROR: Invalid argument >>>>>';
				}
				
			} else {
				$this->error = true;
				return '<<<<< ERROR: Missing operands >>>>>';
			}
			
		} elseif (count($operatorsArray) == 0) {
			
			$this->error = true;
			return '<<<<< ERROR: Missing operator >>>>>';
			
		} else {
		
			$operator = end($operatorsArray);
			$param = $this->getParameters($operator, $elements);
			
		//	echo 'operator:<br>'; print_r($operator); echo '<br>';
			
			if ($operator['type'] == 'PREFIXED_UNARY') {
				if ($operator['elementPosition'] != 0) {
					$this->error = true;
					return '<<<<< ERROR: Invalid use of unary operator >>>>>';
					
				} elseif (count($param['right']) == 0) {
					$this->error = true;
					return '<<<<< ERROR: Syntax error with prefixed unary operator >>>>>';
				}
				
			} elseif (count($param['left']) == 0 || count($param['right']) == 0) {
				$this->error = true;
				return '<<<<< ERROR: Missing operands >>>>>';	
			}
			
			if ($operator['type'] == 'PREFIXED_UNARY') {
				
				$param1 = '';
				foreach($param['right'] as $p) {
					$param1 .= $p['value'].' ';
				}
				$param1 = rtrim($param1);
				
				$r = '';
				$r .= $operator['replace'];
				$r .= count($param['right']) == 1 && !$param['right'][0]['isEnclosure'] ? ' ' : ' (';
				$r .= $this->_parseExpression($param1);
				$r .= count($param['right']) == 1 && !$param['right'][0]['isEnclosure'] ? '' : ') ';
				
			//	$r = $operator['replace'].'('.$this->_parseExpression($param1).')';
				
				return $r;
				
			}
			
			elseif ($operator['type'] == 'BINARY') {
				
				$hint = '';
				if ($operator['replace'] == 'IN') $hint = 'CSV';
				
				$param1 = '';
				
				foreach($param['left'] as $p) {
					$param1 .= $p['value'].' ';
				}
				$param1 = rtrim($param1);

				$param2 = '';
				foreach($param['right'] as $p) {
					$param2 .= $p['value'].' ';
				}
				$param2 = rtrim($param2);
				
				$r = '';
				$r .= count($param['left']) == 1 && !$param['left'][0]['isEnclosure'] ? '' : '(';
				$r .= $this->_parseExpression($param1);
				$r .= count($param['left']) == 1 && !$param['left'][0]['isEnclosure'] ? ' ' : ') ';
				
				$r .= $operator['replace'];
				
				$r .= count($param['right']) == 1 && !$param['right'][0]['isEnclosure'] || $hint == 'CSV' ? ' ' : ' (';
				$r .= $this->_parseExpression($param2, $hint);
				$r .= count($param['right']) == 1 && !$param['right'][0]['isEnclosure'] || $hint == 'CSV' ? '' : ')';
				
			//	$r .= $this->_parseExpression($param1).') '.$operator['replace'].' ('.$this->_parseExpression($param2).')';
				
				return $r;
			
			}

		}

	}



	public function parseExpression() {
		
		$this->stop 			= 50;
		$this->error 			= false;
		$this->errorDescription = '';
		$this->usedFields 		= array();
		
		$returnedExpression = $this->_parseExpression($this->expression);
		
		if (!$this->error) {
			return $returnedExpression;
		} else {
			$this->errorDescription = $returnedExpression;
			return '';
		}
		
	}
	
	
	
}