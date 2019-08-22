<?php

// Connect to mysql DB	
// need to be include from a siteinfo config file
// Create connection

//default mysql, overwrite in config files for oracle
$service = "mysql";


// prefer to write in procedural bc of oracle examples
function db_connect($servername, $username, $password, $database) {
	global $service;
	if ( $service == "mysql" ) {
		if (!($conn = mysqli_connect($servername, $username, $password, $database))) {
			die(sprintf("Connection failed: %d:%s\n", mysqli_errno($conn), mysqli_error($conn) )); 
			exit();
		}
	} elseif ( $service == "oracle" ) {
		$port = "1521";
		$tnsstring = "( DESCRIPTION = (ADDRESS_LIST = (ADDRESS = (PROTOCOL = TCP)(HOST = $servername)(PORT = $port))) (CONNECT_DATA = (SERVICE_NAME = $database)) )";
		if (!($conn = oci_connect($username, $password, $tnsstring))) {
			$e = oci_error();
			die(sprintf("Connection failed: %d:%s\n", $e["code"], $e["message"] )); 
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
	}
	return $result;
}

function db_fetch_assoc($result) {
	global $service;
	unset($row);
	if ( $service == "mysql" ) {
		$row = mysqli_fetch_assoc($result);
	} elseif ( $service == "oracle" ) {
		$row = oci_fetch_assoc($result);
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
	}
}
?>

