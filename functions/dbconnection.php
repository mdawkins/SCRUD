<?php

// Connect to DB	
// need to be include from a siteinfo config file
// Create connection

// check if the variable is mixed case (bad for table and column names in RDBMS) and encapsulate the name depending on the DB's requirements
function encap_mixedcase($itemname) {
	global $service;
	// encapsulation character
	if ( $service == "mysql" ) {
		$encapchar = "`";
	} elseif ( $service == "oracle" ) {
		$encapchar = "\"";
	} elseif ( $service == "pgsql" ) {
		$encapchar = "\"";
	}
	if ( $itemname != strtolower($itemname) && $itemname != strtoupper($itemname) ) {
		$itemname = $encapchar.$itemname.$encapchar;
	}
	return $itemname;
}

// prefer to write in procedural bc of oracle examples
function db_connect($servername, $username, $password, $database, $datasource) {
	global $service;
	if ( $service == "mysql" ) {
		$port = "3306";
		if (!($conn = mysqli_connect($servername, $username, $password, $database, $port))) {
			die(sprintf("Connection failed: %d:%s\n", mysqli_errno($conn), mysqli_error($conn) )); 
			exit();
		}
	} elseif ( $service == "oracle" ) {
		$port = "1521";
		$tnsstring = "( DESCRIPTION = (ADDRESS_LIST = (ADDRESS = (PROTOCOL = TCP)(HOST = $servername)(PORT = $port))) (CONNECT_DATA = (SERVER=dedicated)(SERVICE_NAME = $datasource)) )";
		if (!($conn = oci_connect($username, $password, $tnsstring))) {
			$e = oci_error();
			die(sprintf("Connection failed: %d:%s\n", $e["code"], $e["message"] )); 
			exit();
		}
	} elseif ( $service == "pgsql" ) {
		$port = "5432";
		if (!($conn = pg_connect("host=$servername port=$port dbname=$database user=$username password=$password"))) {
			die(sprintf("Connection failed: %d:%s\n", pg_last_error($conn) ));
			exit();
		}
	}
	return $conn;
} 

function db_query($query) {
	global $conn;
	global $service;
	unset($result);
	if ( $service == "mysql" ) {
		if( !($result = mysqli_query($conn, $query)) ) {
			$result = "error";
		}
	} elseif ( $service == "oracle" ) {
		if( !($result = oci_parse($conn, $query)) ) {
			$result = "error";
		} else
			oci_execute($result);
	} elseif ( $service == "pgsql" ) {
		if( !($result = pg_query($conn, $query)) ) {
			$result = "error";
		}
	}
	return $result;
}

function db_insert_id() {
	global $conn;
	global $service;
	unset($result);
	if ( $service == "mysql" ) {
		if( !($lastid = mysqli_insert_id($conn)) ) {
			$result = "error";
		}
	} elseif ( $service == "oracle" ) {
		// http://hustatyova.blogspot.com/2012/06/last-insert-id-with-php-oracle.html
		// INSERT INTO myTable (...) VALUES ( ...)
			// RETURNING RowId INTO :p_val
		//oci_bind_by_name($statement, ":p_val", $val, 18);
	} elseif ( $service == "pgsql" ) {
		// https://stackoverflow.com/questions/55956/mysql-insert-id-alternative-for-postgresql
	}
	return $lastid;
}

function db_fetch_assoc($result) {
	global $service;
	unset($row);
	if ( $service == "mysql" ) {
		$row = mysqli_fetch_assoc($result);
	} elseif ( $service == "oracle" ) {
		$row = oci_fetch_assoc($result);
	} elseif ( $service == "pgsql" ) {
		$row = pg_fetch_assoc($result);
	}
	return $row;
}

function db_num_rows($result) {
	global $service;
	unset($rowcnt);
	if ( $service == "mysql" ) {
		$rowcnt = mysqli_num_rows($result);
	} elseif ( $service == "oracle" ) {
		$rowcnt = oci_num_rows($result);
	} elseif ( $service == "pgsql" ) {
		$rowcnt = pg_num_rows($result);
	}
	return $rowcnt;
}

function db_close($conn) {
	global $service;
	global $result;
	if ( $service == "mysql" ) {
		mysqli_close($conn);
	} elseif ( $service == "oracle" ) {
		oci_free_statement($result);
		oci_close($conn);
	} elseif ( $service == "pgsql" ) {
		pg_close($conn);
	}
}
?>

