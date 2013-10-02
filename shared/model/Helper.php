<?php
/**
 * A classe Helper fornece funções de apoio ao sistema
 */

class Helper {
	
	/**
	 * recupera da base de dados o valor de um campo em uma tabela
	 */
	public static function getField($field=NULL, $table=NULL, $key=NULL) {
		
		$value = NULL;
		
		if ( is_null($field) || is_null($table) || is_null($key) ) return $value;
		
		$field = filter_var($field, FILTER_SANITIZE_MAGIC_QUOTES);
		$table = filter_var($table, FILTER_SANITIZE_MAGIC_QUOTES);
		$key   = filter_var($key,   FILTER_SANITIZE_MAGIC_QUOTES);
		$key   = intval($key, 10);
		
		if (!is_int($key)) return $value;
		
		$query = "SELECT $field AS value FROM $table WHERE id = $key LIMIT 1; ";
		$resultQuery = mysql_query(utf8_decode($query));
		
		if (!$resultQuery) return $value;
		
		while($row = mysql_fetch_assoc($resultQuery)) { $value = $row['value']; }
		
		return toUTF8($value);
	}
	
	
}