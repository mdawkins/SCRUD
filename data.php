<?php
if ( isset($_GET["page"]) ) {
	require_once "pages/".$_GET["page"].".php";
	if( isset($colorderby) ) {
		$colorderby = "ORDER BY ".str_replace("::", " ", $colorderby);
	}
}

// Database details
require_once ".dbconfig";


// Get job (and id)
$job = '';
$id  = '';
if (isset($_GET['job'])){
  $job = $_GET['job'];
  if ($job == 'get_companies' ||
      $job == 'get_company'   ||
      $job == 'add_company'   ||
      $job == 'edit_company'  ||
      $job == 'delete_company'){
    if (isset($_GET['id'])){
      $id = $_GET['id'];
      if (!is_numeric($id)){
        $id = '';
      }
    }
  } else {
    $job = '';
  }
}

// Prepare array
$mysql_data = array();

// Valid job found
if ($job != ''){
  
  // Connect to database
  $db_connection = mysqli_connect($db_server, $db_username, $db_password, $db_name);
  if (mysqli_connect_errno()){
    $result  = 'error';
    $message = 'Failed to connect to database: ' . mysqli_connect_error();
    $job     = '';
  }
  
  // Execute job
  if ($job == 'get_companies'){
    
    // Get companies
    $query = "SELECT * FROM $table $colorderby";
    $query = mysqli_query($db_connection, $query);
    if (!$query){
      $result  = 'error';
      $message = 'query error';
    } else {
      $result  = 'success';
      $message = 'query success';
	$j=0;
      while ($company = mysqli_fetch_array($query)){
        $functions  = '<div class="function_buttons"><ul>';
        $functions .= '<li class="function_edit"><a data-id="'   . $company['id'] . '" data-name="' . $company['blank'] . '"><span>Edit</span></a></li>';
        $functions .= '<li class="function_delete"><a data-id="' . $company['id'] . '" data-name="' . $company['blank'] . '"><span>Delete</span></a></li>';
	$functions .= '</ul></div>';
	$k=0;
	foreach ( $colslist as $i => $col ) {
		if ( $k == 0 )
			$mysql_data[$j] = [ $col["column"] => $company[$col["column"]] ];
		else
			$mysql_data[$j] = array_merge($mysql_data[$j], [ $col["column"] => $company[$col["column"]] ]);
		$k++;
	}
	$mysql_data[$j] = array_merge($mysql_data[$j], [ "functions" => $functions ]);
	$j++;
      }
    }
    
  } elseif ($job == 'get_company'){
    
    // Get company
    if ($id == ''){
      $result  = 'error';
      $message = 'id missing';
    } else {
      $query = "SELECT * FROM $table WHERE id = '" . mysqli_real_escape_string($db_connection, $id) . "'";
      $query = mysqli_query($db_connection, $query);
      if (!$query){
        $result  = 'error';
        $message = 'query error';
      } else {
        $result  = 'success';
        $message = 'query success';
	$j=0;
	while ($company = mysqli_fetch_array($query)){
		$k=0;
		foreach ( $colslist as $i => $col ) {
			$mysql_data[] = [ $col["column"] => $company[$col["column"]] ];
			if ( $k == 0 )
				$mysql_data[$j] = [ $col["column"] => $company[$col["column"]] ];
			else
				$mysql_data[$j] = array_merge($mysql_data[$j], [ $col["column"] => $company[$col["column"]] ]);
			$k++;
		}
		$j++;
        }
      }
    }
  
  } elseif ($job == 'add_company'){
    
    // Add company
    $query = "INSERT INTO $table SET ";
	foreach ( $colslist as $i => $col ) {
		if (isset($_GET[$col["column"]]))	{ $query .= $col["column"]." = '". mysqli_real_escape_string($db_connection, $_GET[$col["column"]]). "', "; }
	}
    	$query = rtrim($query, ', ');
    $query = mysqli_query($db_connection, $query);
    if (!$query){
      $result  = 'error';
      $message = 'query error';
    } else {
      $result  = 'success';
      $message = 'query success';
    }
  
  } elseif ($job == 'edit_company'){
    
    // Edit company
    if ($id == ''){
      $result  = 'error';
      $message = 'id missing';
    } else {
      $query = "UPDATE $table SET ";
	foreach ( $colslist as $i => $col ) {
		if (isset($_GET[$col["column"]]))	{ $query .= $col["column"]." = '". mysqli_real_escape_string($db_connection, $_GET[$col["column"]]). "', "; }
	}
    	$query = rtrim($query, ', ');
      $query .= "WHERE id = '" . mysqli_real_escape_string($db_connection, $id) . "'";
      $query  = mysqli_query($db_connection, $query);
      if (!$query){
        $result  = 'error';
        $message = 'query error';
      } else {
        $result  = 'success';
        $message = 'query success';
      }
    }
    
  } elseif ($job == 'delete_company'){
  
    // Delete company
    if ($id == ''){
      $result  = 'error';
      $message = 'id missing';
    } else {
      $query = "DELETE FROM $table WHERE id = '" . mysqli_real_escape_string($db_connection, $id) . "'";
      $query = mysqli_query($db_connection, $query);
      if (!$query){
        $result  = 'error';
        $message = 'query error';
      } else {
        $result  = 'success';
        $message = 'query success';
      }
    }
  
  }
  
  // Close database connection
  mysqli_close($db_connection);

}

// Prepare data
$data = array(
  "result"  => $result,
  "message" => $message,
  "data"    => $mysql_data
);

// Convert PHP array to JSON array
$json_data = json_encode($data);
print $json_data;
?>
