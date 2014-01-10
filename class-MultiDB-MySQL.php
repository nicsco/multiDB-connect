<?php

/*
	VERSION 0.2
*/

require_once( 'class-MultiDB.php' );

class MultiDB_MySQL extends MultiDB {

    function __construct( $name, $user, $pass, $host ){
		$this->db = new mysqli( $host, $user, $pass, $name );

		if ( $this->db->connect_error )
			$this->oops( 'Connect Error (' . $this->db->connect_errno . ') ' . $this->db->connect_error );
    }

    function __destruct(){
		if ( ! $this->db->close() )
			$this->oops( 'Connection close failed.' );
    }

	public function escape_string( $string ) {
		return $this->db->real_escape_string( $string );
	}

	public function query( $sql ) {
		$this->last_result		= $this->db->query( $sql );
		$this->affected_rows	= $this->last_result->num_rows;
		$this->last_error		= $this->db->error;

		return $this->last_result;
	}

	public function exec( $sql ) {
		$this->db->query( $sql );
	}

	public function list_tables() {
		$results	= array();
		foreach( $this->fetch_all( "SHOW TABLES", FALSE ) as $result )
			$results[]=$result['tbl_name'];
		return $results;
	}

	private function _filter_data( $string ){
		switch ( strtolower( $string ) ) {
			case 'null'		:	$string = 'NULL';							break;
			case 'now()'	:	$string = 'NOW()';							break;
			default			:	$string = "'" . $this->escape_string( $string ) . "'"; break;
		}
		return $string;
	}

	public function insert( $table, $data ){
		$sql = "INSERT INTO `{$table}` (" . implode( ",", array_map( array( $this, 'quote_field' ), array_keys( $data ) ) ) . ") VALUES (" . implode( ",", array_map( array( $this, '_filter_data' ), $data ) ) . ");";

		$this->last_insert_row_id = ( $this->query( $sql ) )
			?	$this->db->insert_id
			:	0;

		return $this->last_insert_row_id;
	}

	public function update( $table, $data, $where ){
		$sql = "UPDATE `{$table}` SET ";
		foreach( $data as $column_name => $expr ){
			switch ( strtolower( $expr ) ) {
				case 'null'		:	$sql .= "`{$column_name}` = NULL, ";		break;
				case "now()" 	:	$sql .= "`{$column_name}` = NOW(), ";		break;
				default			:	$sql .= "`{$column_name}` = '" . $this->escape_string( $expr ) . "', ";	break;
			}
		}
		$sql = rtrim( $sql, ', ' ) . " WHERE {$where};";
		return $this->query( $sql );
	}

#■ FETCH DATA ■

	public function fetch_row( $sql ){
		$this->_query( $sql );
		$data = $this->last_result->fetch_row();
		$this->free_result();
		return $data;
	}

	public function fetch_all( $sql ){
		$this->_query( $sql );
		$data = $this->last_result->fetch_all();
		$this->free_result();
		return $data;
	}

	protected function free_result(){
		$this->last_result->free_result();
	}



}