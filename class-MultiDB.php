<?php

abstract class MultiDB {

	protected	$db;							#	Handle to Database connection
	protected	$last_result;					#	Results of the last query made

	public	$last_insert_row_id	= 0;
	public	$affected_rows		= 0;
	public	$last_error			= '';


	public function __construct(){}
	public function __destruct(){}


	public abstract function query( $sql );			/* Executes a query against a given database. */
	public abstract function exec( $sql );			/* Executes a result-less query against a given database. */

	public abstract function escape_string( $string );
	public abstract function list_tables();
	protected abstract function free_result();



#■ TABLES ■

	public function create_table( $table, $colums_definition, $table_constraints = array() ){
		$columns		= $this->distribute( ' ', ', ', $colums_definition );
		$constraints	= ( empty( $table_constraints ) )
			?	''
			:	', ' . implode( ', ', $table_constraints );

print_r( $constraints );
		$constraints = str_ireplace( 'AUTO_INCREMENT', 'AUTOINCREMENT', $constraints );
print_r( $constraints );
		$query = "CREATE TABLE IF NOT EXISTS {$table} ( {$columns} {$constraints} )";

		$this->query( $query );
		return $this->last_result;
	}

	public function drop_table( $table ){
		$query = "DROP TABLE IF EXISTS {$table}";
		$this->query( $query );
		return $this->last_result;
	}

#■ INSERT / UPDATE / DELETE ■

	public abstract function insert( $table, $data );
	public abstract function update( $table, $data, $where );
	public function delete( $table, $where ){

		$query = "DELETE FROM `{$table}` WHERE {$where};";
		return $this->exec( $query );
	}

#■ FETCH DATA ■

	public abstract function fetch_row( $query );
	public abstract function fetch_all( $query );


#■ OTHER ■

	public function quote_field( $field ) {
		return "`{$field}`";
	}

	public function quote_fields( $fields ) {
		return array_map( array( $this, 'quote_field' ), $fields );
	}

	private function distribute( $separator, $glue, $array ){
		$string = '';
		foreach( $array as $key => $value )
			$string .= "{$key}{$separator}{$value}{$glue}";
		return substr( $string, 0, - strlen( $glue ) );
	}

	public function count( $table, $where, $x = '*' ){
		$query = "SELECT COUNT({$x}) FROM `{$table}` WHERE {$where}";
		return $this->fetch_row( $query );
	}

	private function oops( $msg = NULL ){
		echo "\n\n<!-- DATABASE ERROR :: MESSAGE \n\n {$msg} \n\nDATABASE ERROR END -->\n\n";
	}


}
