<?php

require_once( 'class-MultiDB.php' );

class MultiDB_SQLite3 extends MultiDB {

    function __construct( $database ){
		$this->db = new SQLite3( $database );
    }

    function __destruct(){
		if ( ! $this->db->close() )
			$this->oops( 'Connection close failed.' );
    }

	public function query( $sql ) {
		$this->last_result		= $this->db->query( $sql );
		$this->affected_rows	= $this->db->changes();
		$this->last_error		= $this->db->lastErrorMsg();

		return $this->last_result;
	}

	public function exec( $sql ) {
		$this->db->exec( $sql );
	}

	public function escape_string( $string ) {
		return $this->db->escapeString($string);
	}

	public function list_tables() {
		$data	= array();
		foreach( $this->fetch_all( "SELECT tbl_name FROM SQLITE_MASTER WHERE type = 'table'", FALSE ) as $result )
			$data[]=$result['tbl_name'];
		return $data;
	}

#■ INSERT / UPDATE / DELETE ■

	public function insert( $table, $data ){
		$query = "INSERT INTO `{$table}` " . $this->_insert_or_replace_data( $data ) . ";";

		$this->last_insert_row_id = ( $this->query( $sql ) )
			?	$this->db->lastInsertRowID()
			:	0;

		return $this->last_insert_row_id;

	}

	public function update( $table, $data, $where ){

		$query = "UPDATE `{$table}` SET ";
		foreach( $data as $column_name => $expr ){
			switch ( strtolower( $expr ) ) {
				case 'null'				:	$query .= "`{$column_name}` = NULL, ";				break;
				case "date('now')" 		:	$query .= "`{$column_name}` = date('now'), ";		break;
				case "datetime('now')"	:	$query .= "`{$column_name}` = datetime('now'), ";	break;
				default					:	$query .= "`{$column_name}` = '" . $expr . "', ";	break;
			}
		}
		$query = rtrim( $query, ', ' ) . " WHERE {$where};";
		return $this->exec( $query );
	}

	private function _insert_or_replace_data( $data ){

		foreach ( $data as $column_name => $expr ){
			switch ( strtolower( $expr ) ) {
				case 'null'				:	$data[$column_name] = "NULL";						break;
				case "date('now')" 		:	$data[$column_name] = "date('now')";				break;
				case "datetime('now')"	:	$data[$column_name] = "datetime('now')";			break;
				default					:	$data[$column_name] = "'" . $expr . "'";	break;
			}
		}
		return "( `" . implode( '`, `', array_keys( $data ) ) . "` ) VALUES ( " . implode( ", ", $data ) . " )";
	}


#■ FETCH DATA ■

	public function fetch_row( $sql ){
		$this->_query( $query );
		$data = $this->last_result->fetchArray( SQLITE3_ASSOC );
		$this->free_result();
		return $data;
	}

	public function fetch_all( $sql ){
		$this->_query( $query );
		$data = array();
		while ( $data[] = $this->last_result->fetchArray( SQLITE3_ASSOC ) );
		array_pop( $data );
		$this->free_result();
		return $data;
	}

	protected function free_result(){
		$this->last_result->finalize();
	}


#■ SQLITE SPECIALS ■

	/* The VACUUM command rebuilds the entire database. */
	public function vacuum(){
		$this->query( 'VACUUM' );
	}


}

