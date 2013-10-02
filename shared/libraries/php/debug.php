<?php
/**
 * @example $GLOBALS["debug"]->add('some_label_or_NULL',
					array(
						'caller'=>'function_name()',
						'result'=>$someObject,
						'file'=>__FILE__,
						'line'=>__LINE__
					));
 */
class Debug {
	
	private $content;
	
	public function __construct(){
		$this->content = array();
	}
	
	public function clear(){
		$this->content = array();
		return true;
	}
	
	public function add($arg1=NULL, $arg2=NULL){
		
		if (func_num_args()===2 && !is_null($arg1)) {
			$this->content[$arg1] = $arg2;
		} elseif (func_num_args()===2){
			$this->content[] = $arg2;
		} else {
			$this->content[] = $arg1;
		}
		
		return true;
	}
	
	public function dump(){
		return $this->content;
	}
}