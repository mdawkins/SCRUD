<?php
if ( !empty($_GET["page"]) ) {
	if ( !empty($_GET["subpage"]) ) {
		require_once "pages/".$_GET["subpage"].".php";
	} else {
		require_once "pages/".$_GET["page"].".php";
	}
	if( isset($colorderby) ) {
		$colorderby = "ORDER BY ".str_replace("::", " ", $colorderby);
	}

// Database details
require_once ".serv.conf";
require_once "$funcroot/dbconnection.php";
#require_once ".dbconfig";

// Get job (and id)
$job = "";
$id  = "";
$subpage  = "";
if ( isset($_GET["job"]) ) {
  $job = $_GET["job"];
  if ($job == "get_records" ||
      $job == "get_record"   ||
      $job == "add_record"   ||
      $job == "edit_record"  ||
      $job == "delete_record") {
    if ( isset($_GET["id"]) ) {
      $id = $_GET["id"];
      if ( !is_numeric($id) ) {
        $id = "";
      }
    }
    if ( isset($_GET["subpage"]) ) {
      $subpage = $_GET["subpage"];
    }
  } else {
    $job = "";
  }
}
// Prepare array
$query_data = array();

// Valid job found
if ( $job != "" ) {
  
  // Connect to database
  $conn = db_connect($servername, $username, $password, $database);
  if ( $result == "error" ) {
    //$result  = "error";
    $message = "Failed to connect to database: ".mysqli_connect_error();
    $job     = "";
  }
  
  // Execute job
  if ( $job == "get_records" ){
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
	if ( ( $col["input_type"] == "tableselect" || ( $col["input_type"] == "crosswalk" && !empty($subpage) ) ) && array_search($col["column"], array_column($selslist, "selcol")) !== null ) {
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
				} elseif ( $col["input_type"] == "crosswalk" ) {
					$ljointables .= $table.".id = t$i.".$sel["selid"]." ";
					$wheres .= "t$i.".$sel["wherekey"]." = $id AND"; 
				} else {
					$fields .= "t$i.".$sel["selname"]." AS ".$col["column"].",";
					$ljointables .= $table.".".$col["column"]." = t$i.".$sel["selid"]." ";
				}
			}
		} 
	} elseif ( $col["input_type"] == "pivotjoin" ) {
		$fields .= "`".$col["column"]."`, ";
		$ljointables .= "LEFT JOIN\n\t(SELECT $pivkey, GROUP_CONCAT(DISTINCT(CASE WHEN $joinkey = '".$col["key"]."' THEN $keyname END) ORDER BY 1 SEPARATOR ', ') AS '".$col["column"]."' FROM $jointable WHERE $joinwherekey = $joinwhereval AND $joinkey = '".$col["key"]."' GROUP BY $pivkey) AS t".$i." ON $table.id=t$i.$pivkey\n";

	} elseif ( $col["input_type"] == "noform" )  {
		$fields .= $col["colfunc"]." AS ".str_replace("%T%", "t$i", $col["column"]).",";
	} elseif ( !empty($col["concatval"]) ) {
		$fields .= $col["concatval"]." AS ".str_replace("%T%", "t$i", $col["column"]).",";
	} elseif ( $col["input_type"] == "currency" ) {
		$tablecolname = "$table.".$col["column"];
		$fields .= "IF($tablecolname IS NULL OR $tablecolname = '0', '--', concat('$',format($tablecolname, 2))) AS ".$col["column"].",";
	} elseif ( $col["input_type"] == "date" ) {
		$tablecolname = "$table.".$col["column"];
		$fields .= "IF($tablecolname IS NULL OR $tablecolname = '0000-00-00', '--', date_format($tablecolname, '%m/%d/%Y')) AS ".$col["column"].",";
	} elseif ( $col["input_type"] == "datetime" ) {
		$tablecolname = "$table.".$col["column"];
		$fields .= "IF($tablecolname IS NULL OR $tablecolname = '0000-00-00 00:00:00', '--', date_format($tablecolname, '%m/%d/%Y %r')) AS ".$col["column"].",";
	} elseif ( $col["input_type"] == "drilldown" || $col["input_type"] == "crosswalk" ) {
		continue;
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

    // Get Records
    $query = $sqlsel_rows;
    $query = db_query($query);
    if (!$query) {
      $result  = "error";
      $message = "query error";
    } else {
      $result  = "success";
      $message = "query success";
	$j=0;
      while ( $row = db_fetch_assoc($query) ) {
        $functions  = '<div class="function_buttons"><ul>';
        $functions .= '<li class="function_edit"><a data-id="'.$row["id"].'" data-name="'.$row["blank"].'"><span>Edit</span></a></li>';
        $functions .= '<li class="function_delete"><a data-id="'.$row["id"].'" data-name="'.$row["blank"].'"><span>Delete</span></a></li>';
	$functions .= '</ul></div>';

	// Set each column by row as it comes from the query
	$k=0;
	foreach ( $colslist as $i => $col ) {
		// modify data
		if ( $col["input_type"] == "select" || $col["input_type"] == "tableselect" ) {
			foreach ( $lists[$col["column"]] as $lst ) {
				$row[$col["column"]] = str_replace($lst["key"], $lst["title"], $row[$col["column"]]);
			}
			if ( $col["multiple"] == "yes" ) {
				$row[$col["column"]] = str_replace(";", "/", $row[$col["column"]]);
			}
		} elseif ( $col["input_type"] == "checkbox" ) {
			if ( $row[$col["column"]] == 1 ) {
				$row[$col["column"]] = "<i class=\"fa fa-fw fa-check-square\">";
			} else {
				$row[$col["column"]] = "<i class=\"fa fa-fw fa-square\">";
			}
		} elseif ( $col["input_type"] == "drilldown" ) {
        		$row[$col["column"]]  = '<div class="function_buttons"><ul>';
        		$row[$col["column"]] .= '<li class="function_drilldown"><a data-id="'.$row["id"].'" data-name="'.$col["column"].'"><span>Show</span></a></li>';
        		$row[$col["column"]] .= '<li class="function_adddrilldown"><a data-id="'.$row["id"].'" data-name="'.$row["column"].'"><span>Add</span></a></li>';
			$row[$col["column"]] .= '</ul></div>';
		} elseif ( $col["input_type"] == "crosswalk" ) {
			continue;
		} else { // this is legacy td title from pajm, not going to work here
			if ( isset($col["colwidth"]) && $col["colwidth"] < strlen($colstring) ) {
				$titlestring = "title=\"$colstring\"";
			}
			unset($titlestring);
		}
		// set array
		if ( $k == 0 )
			$query_data[$j] = [ $col["column"] => $row[$col["column"]] ];
		else
			$query_data[$j] = array_merge($query_data[$j], [ $col["column"] => $row[$col["column"]] ]);
		$k++;
	}
	$query_data[$j] = array_merge($query_data[$j], [ "functions" => $functions ]);
	if ( $showrownum == "yes" && empty($subpage) ) { $query_data[$j] = array_merge( [ "rownum" => "rn" ], $query_data[$j] ); }
	$j++;
      }
    }
    
  } elseif ( $job == "get_record" ) {
    
    // Get Record
    if ( $id == "" ) {
      $result  = "error";
      $message = "id missing";
    } else {
      $query = "SELECT * FROM $table WHERE id = '".addslashes($id)."'";
      $query = db_query($query);
      if ( !$query ) {
        $result  = "error";
        $message = "query error";
      } else {
        $result  = "success";
        $message = "query success";
	$j=0;
	while ( $row = db_fetch_assoc($query) ) {
		$k=0;
		foreach ( $colslist as $i => $col ) {
			$query_data[] = [ $col["column"] => $row[$col["column"]] ];
			if ( $k == 0 )
				$query_data[$j] = [ $col["column"] => $row[$col["column"]] ];
			else
				$query_data[$j] = array_merge($query_data[$j], [ $col["column"] => $row[$col["column"]] ]);
			$k++;
		}
		$j++;
        }
      }
    }
  
  } elseif ( $job == "add_record" ) {
    
    // Add Record
    $query = "INSERT INTO $table SET ";
	foreach ( $colslist as $i => $col ) {
		if (isset($_GET[$col["column"]]))	{ $query .= $col["column"]." = '".addslashes($_GET[$col["column"]])."', "; }
	}
    	$query = rtrim($query, ', ');
    $query = db_query($query);
    if ( !$query ) {
      $result  = "error";
      $message = "query error";
    } else {
      $result  = "success";
      $message = "query success";
      $lastid = $query->insert_id;
    }
  
  } elseif ( $job == "edit_record" ){
    
    // Edit Record
    if ( $id == "" ) {
      $result  = "error";
      $message = "id missing";
    } else {
      $query = "UPDATE $table SET ";
	foreach ( $colslist as $i => $col ) {
		if ( isset($_GET[$col["column"]]) ) {
			$query .= $col["column"]." = '".addslashes($_GET[$col["column"]])."', ";
		} elseif ( !isset($_GET[$col["column"]]) && $col["input_type"] == "checkbox" ) {

			$query .= $col["column"]." = '0', ";
		}
	}
    	$query = rtrim($query, ', ');
      $query .= "WHERE id = '".addslashes($id)."'";
      $query  = db_query($query);
      if ( !$query ) {
        $result  = "error";
        $message = "query error";
      } else {
        $result  = "success";
        $message = "query success";
      }
    }
    
  } elseif ( $job == "delete_record" ) {
  
    // Delete Record
    if ( $id == "" ) {
      $result  = "error";
      $message = "id missing";
    } else {
      $query = "DELETE FROM $table WHERE id = '".addslashes($id)."'";
      $query = db_query($query);
      if ( !$query ) {
        $result  = "error";
        $message = "query error";
      } else {
        $result  = "success";
        $message = "query success";
      }
    }
  }
  
  // Close database connection
  db_close($conn);
}

// Prepare data
$data = array(
  "colsls"  => $colslist,
  "sql"     => $sqlsel_rows,
  "result"  => $result,
  "message" => $message,
  "data"    => $query_data,
  "lastid"  => $lastid,
);

// Convert PHP array to JSON array
$json_data = json_encode($data);
print $json_data;

}
?>
