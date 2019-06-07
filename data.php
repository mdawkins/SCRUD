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
// import viewtables query build here
//Pivot Table join variables
foreach ( $lists["pivcols"] as $key ) {
	$pivkey = $key["pivkey"];
	$joinkey = $key["joinkey"];
	$keyname = $key["keyname"];
	$jointable = $key["pivtable"];
	$joinwherekey = $key["wherekey"];
	$joinwhereval = $key["whereval"];
}

// table row header
foreach ( $colslist as $i => $col ) {
	if ( $col["input_type"] == "tableselect" && array_search($col["column"], array_column($selslist, "selcol")) !== null ) {
		foreach ( $selslist as $k => $sel ) {
			if ( $col["column"] == $sel["selcol"] ) {
				$searchcol = $sel["selname"];
				$ljointables .= "LEFT JOIN ".$sel["seltable"]." AS t$i ON ";
				if ( $col["multiple"] == "yes" ) {
					// replace %T% with table alias
					$fields .= str_replace("%T%", "t$i", "GROUP_CONCAT(".$sel["selname"]." ORDER BY 1 SEPARATOR ', ') AS ".$col["column"]).",";
					$ljointables .= $table.".".$col["column"]." LIKE CONCAT('%', t$i.".$sel["selid"].", '%') ";
					$groupby = "GROUP BY $table.".$col["column"];
				} elseif ( $col["concatfield"] == "yes" ) {
					$fields .= str_replace("%T%", "t$i", $sel["selname"]." AS ".$col["column"]).",";
					$ljointables .= $table.".".$col["column"]." = t$i.".$sel["selid"]." ";
				} else {
					$fields .= "t$i.".$sel["selname"]." AS ".$col["column"].",";
					$ljointables .= $table.".".$col["column"]." = t$i.".$sel["selid"]." ";
				}
			}
		} 
	} elseif ( $col["input_type"] == "pivotjoin" )  {
		$fields .= "`".$col["column"]."`, ";
		$ljointables .= "LEFT JOIN\n\t(SELECT $pivkey, GROUP_CONCAT(DISTINCT(CASE WHEN $joinkey = '".$col["key"]."' THEN $keyname END) ORDER BY 1 SEPARATOR ', ') AS '".$col["column"]."' FROM $jointable WHERE $joinwherekey = $joinwhereval AND $joinkey = '".$col["key"]."' GROUP BY $pivkey) AS t".$i." ON $table.id=t$i.$pivkey\n";

	} elseif ( $col["input_type"] == "noform" )  {
		$fields .= $col["colfunc"]." AS ".str_replace("%T%", "t$i", $col["column"]).",";
	} elseif ( !empty($col["concatval"]) ) {
		$fields .= $col["concatval"]." AS ".str_replace("%T%", "t$i", $col["column"]).",";
	} elseif ( $col["input_type"] == "currency" )  {
		$tablecolname = "$table.".$col["column"];
		$fields .= "IF($tablecolname IS NULL OR $tablecolname = '0', '--', concat('$',format($tablecolname, 2))) AS ".$col["column"].",";
	} elseif ( $col["input_type"] == "date" )  {
		$tablecolname = "$table.".$col["column"];
		$fields .= "IF($tablecolname IS NULL OR $tablecolname = '0000-00-00', '--', date_format($tablecolname, '%m/%d/%Y')) AS ".$col["column"].",";
	} elseif ( $col["input_type"] == "datetime" )  {
		$tablecolname = "$table.".$col["column"];
		$fields .= "IF($tablecolname IS NULL OR $tablecolname = '0000-00-00 00:00:00', '--', date_format($tablecolname, '%m/%d/%Y %r')) AS ".$col["column"].",";
	} else {
		$fields .= "$table.".$col["column"].", ";
       	}
	// add in ajax filters wheres here
	if ( !empty($_POST[$col["column"]]) ) {
		${$col["column"]} = implode("','", $_POST[$col["column"]]);
		if ( $col["filterbox"] == "checkbox" ) { 
			if ( $col["multiple"] == "yes" && $col["input_type"] == "tableselect")
				$addwheres .= " AND t$i.id REGEXP '".str_replace(",", "||", ${$col["column"]})."' ";
			elseif ( $col["multiple"] == "yes" ) // just type select
				$addwheres .= " AND ".$col["column"]." REGEXP '".str_replace(",", "||", ${$col["column"]})."' ";
			else
				$addwheres .= " AND ".$col["column"]." IN('".${$col["column"]}."') ";
		}
		elseif ( $col["filterbox"] == "text" && !empty(${$col["column"]}) ) {
			if ( $col["input_type"] == "tableselect" ) {
				$addwheres .= " AND $searchcol LIKE '%".${$col["column"]}."%' ";
			} elseif ( !empty($col["concatval"]) ) {
				$addwheres .= " AND ".$col["concatval"]." LIKE '%".${$col["column"]}."%' ";
			} else {
				$addwheres .= " AND ".$col["column"]." LIKE '%".${$col["column"]}."%' ";
			}
		}
	}
}

// clean up of sql variables
$fields = rtrim($fields,", ");

if ( !empty($wheres) && empty($addwheres) ) {
	$wheres = "WHERE ".rtrim(trim($wheres),"AND");
} elseif ( !empty($addwheres) && empty($wheres) ) {
	$wheres = "WHERE ".ltrim($addwheres, " AND ");
} elseif ( !empty($wheres) && !empty($addwheres) )
	$wheres = "WHERE ".rtrim(trim($wheres),"AND").$addwheres;

$sqlsel_rows = "SELECT $table.id, $fields FROM $table $ljointables $wheres $groupby $colorderby";
// end import 

    // Get companies
    $query = $sqlsel_rows;
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

	// import viewtable
	foreach ($colslist as $col) {
		if ( $col["multiple"] == "yes" ) {
			$row[$col["column"]] = str_replace(";", "/", $row[$col["column"]]);
			foreach ( $lists[$col["column"]] as $lst ) {
				$row[$col["column"]] = str_replace($lst["key"], $lst["title"], $row[$col["column"]]);
			}
		} else {
			if ( isset($col["colwidth"]) && $col["colwidth"] < strlen($colstring) ) {
				$titlestring = "title=\"$colstring\"";
			}
			unset($titlestring);
		}
	}
	// end import

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
		if (isset($_GET[$col["column"]])) {
			$query .= $col["column"]." = '". mysqli_real_escape_string($db_connection, $_GET[$col["column"]]). "', ";
		} elseif ( !isset($_GET[$col["column"]]) && $col["input_type"] == "checkbox" ) {

			$query .= $col["column"]." = '0', ";
		}
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
  "sql"     => $sqlsel_rows,
  "result"  => $result,
  "message" => $message,
  "data"    => $mysql_data,
);

// Convert PHP array to JSON array
$json_data = json_encode($data);
print $json_data;
?>
