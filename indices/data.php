<?php
if ( !empty($_GET["page"]) ) {
	require_once "pages/".$_GET["page"].".php";
	if( isset($colorderby) ) {
		$colorderby = "ORDER BY ".str_replace("::", " ", $colorderby);
	}
	$pageinfo = [ "pagetitle" => $pagetitle, "table" => $table, "showidcolumn" => $showidcolumn, "showrownum" => $showrownum, "showdeletecolumn" => $showdeletecolumn, "colorderby" => $colorderby, "rowlimit" => $rowlimit ];

	// Database details
	require_once ".serv.conf";
	require_once "$funcroot/dbconnection.php";

	// Get job (and id)
	$job = $id = "";
	if ( isset($_GET["job"]) ) {
		$job = $_GET["job"];
		if ( $job == "get_records" || $job == "get_record" || $job == "add_record"
			|| $job == "edit_record" || $job == "delete_record" || $job == "page_lists"
			|| $job == "attach_record" || $job == "ajax_select" ) {
			if ( isset($_GET["id"]) ) {
				$id = $_GET["id"];
				if ( !is_numeric($id) ) {
					$id = "";
				}
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
			//$result = "error";
			$message = "Failed to connect to database: ".mysqli_connect_error();
			$job = "";
		}

		// BN - Is tableselect, essentially a file of non-db 'table' data?
		// or just the opposite? Go get data from a table for select drop downs?
		// MD - tableselect is a html select where its options are generated from
		// a DB table query. It requieres a valid array pair bewtween selslist & colslist
		// eg: casetracker>clientcases> "selcol" => "attorneyid" pairs to "column" => "attorneyid"
		// the following code, converts the selslist array into lists array with populated
		// "key" and "title" values.

		// Does input_type == tableselect?
		if ( array_search( "tableselect", array_column( $colslist, "input_type" ) ) !== null ) {
			// create array for creating select dropdown list
			foreach ( $selslist as $sel ) {
				// This identifies that the select is cascading/nested to a parent select
				if ( !empty($sel["parselcol"]) && !empty($sel["partitle"]) ) {
					//echo "there: ".$sel["parselcol"]."<br>";
					$name[0] = [ [ "key" => "selectparent", "value" => $sel["parselcol"], "title" => "Select ".$sel["partitle"]." first" ] ];
					$lists[$sel["selcol"]] = $name[0];
				} else {
					if ( !empty($sel["whereval"]) ) { $wherestring = ' where '.$sel["wherekey"]." like ".$sel["whereval"]; } // !!! CANNOT USE SINGLE OR DOUBLE QUOTES HERE, PLACE IN VAR
					elseif ( !empty($sel["wherekey"]) ) { $wherestring = ' where '.$sel["wherekey"]; }
					//$sqlsel_rows = "select * from ".$sel["seltable"].$wherestring;
					$sqlsel_rows = "select ".$sel["selid"].", ".$sel["selname"]." from ".$sel["seltable"].$wherestring." ORDER BY ".$sel["selname"];
					//echo $sqlsel_rows."<br>";
					$result = db_query($sqlsel_rows);
					if ( db_num_rows($result) > 0) {
						// output data of each row
						$i=0;
						while ( $row = db_fetch_assoc($result) ) {
							//$name[$i] = [ [ "key" => $row[$sel["selid"]], "title" => $row[$sel["selname"]] ] ];
							$name[$i] = [ [ "key" => $row["id"], "title" => $row[$sel["selname"]] ] ];
							//echo $row[$sel["selid"]].":".$row[$sel["selname"]].";";
							if ( $i != 0 ) { $name[0] = array_merge($name[0], $name[$i]); }
							$i++;
						}
						$lists[$sel["selcol"]] = $name[0];
					}
					unset($wherestring);
					unset($result);
				}
			}
		}

		//Pivot Table join variables
		if ( $tabletype == "pivottable" ) {
			// Add Column names and keys to colslist
			$sqlsel_pivcols = "SELECT $pivcolskey, $pivcolsname FROM $pivcolstable";
			if ( $pivcolswherekey != "" && $pivcolswhereval != "" ) {
				$sqlsel_pivcols .= " WHERE $pivcolswherekey = '$pivcolswhereval'";
			}
			$sqlsel_pivcols .= " ORDER BY $pivcolsname";
			$result = db_query($sqlsel_pivcols);
			if ( db_num_rows($result) > 0) {
				while ( $row = db_fetch_assoc($result) ) {
					$colslist[] = array( "column" => $row[$pivcolsname], "title" => $row[$pivcolsname], "key" => $row[$pivcolskey], "input_type" => "pivotjoin" );
				}
			}

			foreach ( $lists["pivcols"] as $key ) {
				$pivkey = $key["pivkey"];
				$joinkey = $key["joinkey"];
				$keyname = $key["keyname"];
				$jointable = $key["pivtable"];
				$joinwherekey = $key["wherekey"];
				$joinwhereval = $key["whereval"];
			}
		}

		// Does input_type == dropedit?
		// insert logic from labtests/spec700 & specs703actual
		// Does input_type == tableselect && is nested?
		// insert logic from labtests/transmittal
		// Attach child record to parent record in crosswalk table

		// Execute job
		if ( $job == "get_records" ) {

			// table row header
			// BUILD SQL STATMENT BASED ON CONFIG FILE ARRAYS FOR PAGE
			// BN - Sniff db, then branch to MySQLLib php file, with this code
			// or point it at OCI8 oraLib php file
			// MD - The .serv.conf addresses which service (ie mysql/oracle) to use.
			// Once the service variable is established, functions/dbconnection.php
			// implements the appropriate DB function calls inside SCRUD's DB functions
			// Eg. db_connect: implements either mysqli_connect or oci_connect depending on service
			foreach ( $colslist as $i => $col ) {
				// IF col input type is a tableselect OR input type is a crosswalk and the main datatables table variable is not maintable
				if ( ( $col["input_type"] == "tableselect" || ( $col["input_type"] == "crosswalk" && $_GET["dt_table"] != "maintable") )
					// AND selslist "selcol" == colslist "column"
					// This basic array mapping
					&& array_search($col["column"], array_column($selslist, "selcol")) !== null ) {
					foreach ( $selslist as $k => $sel ) {
						if ( $col["column"] == $sel["selcol"] ) {
							$searchcol = $sel["selname"];
							$ljointables .= "LEFT JOIN ".$sel["seltable"]." AS t$i ON ";
							// IF col input type is select/tableselect with multiple options to be selected
							if ( $col["multiple"] == "yes" ) {
								// replace %T% with table alias
								$fields .= str_replace("%T%", "t$i", "GROUP_CONCAT(".$sel["selname"]." ORDER BY 1 SEPARATOR ', ') AS ".$col["column"]).",";
								$ljointables .= $table.".".$col["column"]." LIKE CONCAT('%', t$i.".$sel["selid"].", '%') ";
								$groupby = "GROUP BY $table.".$col["column"];
							// IF tableselect value needs to concatenate multiple columns into one field
							// Eg Labtests: testqualification:
							// [ "column" => "testid", "title" => "Test: AASHTO / ASTM", "required" => "yes", "input_type" => "tableselect", "concatfield" => "yes" ],
							// [ "selcol" => "testid", "selname" => "concat(IF(aashtodesignation = '', '---', aashtodesignation), ' / ', IF(astmdesignation = '', '---', astmdesignation), ' ', testmethod)", "selid" => "id", "seltable" => "accreditedtests", "wherekey" => "", "whereval" => "" ],
							} elseif ( $col["concatfield"] == "yes" ) {
								$fields .= str_replace("%T%", "t$i", $sel["selname"]." AS ".$col["column"]).",";
								$ljointables .= $table.".".$col["column"]." = t$i.".$sel["selid"]." ";
							// IF col input type is crosswalk and we are attaching a child drilldown table to the parent record
							// Eg Casetracker: caseinfo: via embedded/child config file
							// [ "column" => "crosswalkclientscases", "title" => "Crosswalk", "input_type" => "crosswalk" ],
							// [ "selcol" => "crosswalkclientscases", "selname" => "", "selid" => "caseid", "seltable" => "crosswalkclientscases", "wherekey" => "clientid", "whereval" => "" ],
							} elseif ( $col["input_type"] == "crosswalk" ) {
								$ljointables .= $table.".id = t$i.".$sel["selid"]." ";
								$wheres .= "t$i.".$sel["wherekey"]." = $id AND"; 
							// Default logic if col input type is tableselect
							} else {
								$fields .= "t$i.".$sel["selname"]." AS ".$col["column"].",";
								$ljointables .= $table.".".$col["column"]." = t$i.".$sel["selid"]." ";
							}
						}
					} 
				// IF col input type is pivotjoin
				// Eg labtests: testroster:
				// See page config file for TODO integration of logic into data.php
				} elseif ( $col["input_type"] == "pivotjoin" ) {
					$fields .= "`".$col["column"]."`, ";
					$ljointables .= " LEFT JOIN (SELECT $pivkey, GROUP_CONCAT(DISTINCT(CASE WHEN $joinkey = '".$col["key"]."' THEN $keyname END) ORDER BY 1 SEPARATOR ', ') AS '".$col["column"]."' FROM $jointable WHERE $joinwherekey = $joinwhereval AND $joinkey = '".$col["key"]."' GROUP BY $pivkey) AS t".$i." ON $table.id=t$i.$pivkey";
				// Eg casetracker: clientinfo
				//  Show in viewtable but not in the addedit form
				} elseif ( $col["input_type"] == "noform" ) {
					$fields .= $col["colfunc"]." AS ".str_replace("%T%", "t$i", $col["column"]).",";
				// Eg labtest: testroster no addedit form
				//  [ "column" => "testname", "concatval" => "concat(IF(aashtodesignation = '', '---', aashtodesignation), ' / ', IF(astmdesignation = '', '---', astmdesignation), ' ', testmethod)", "title" => "Test: AASHTO / ASTM", "input_type" => "text", "filterbox" => "text" ],
				} elseif ( !empty($col["concatval"]) ) {
					$fields .= $col["concatval"]." AS ".str_replace("%T%", "t$i", $col["column"]).",";
				// Eg fundsmap: historicalprojects
				//  Format number to currency or if zero leave --
				} elseif ( $col["input_type"] == "currency" ) {
					$tablecolname = "$table.".$col["column"];
					$fields .= "IF($tablecolname IS NULL OR $tablecolname = '0', '--', concat('$',format($tablecolname, 2))) AS ".$col["column"].",";
				// Eg labtests: testqualification
				//  Format YYYY/MM/DD to MM/DD/YYYY or leave --
				} elseif ( $col["input_type"] == "date" ) {
					$tablecolname = "$table.".$col["column"];
					$fields .= "IF($tablecolname IS NULL OR $tablecolname = '0000-00-00', '--', date_format($tablecolname, '%m/%d/%Y')) AS ".$col["column"].",";
				// Eg casetracker: clientcases
				//  Format YYYY/MM/DD 24H to MM/DD/YYYY 12a/p or leave --
				} elseif ( $col["input_type"] == "datetime" ) {
					$tablecolname = "$table.".$col["column"];
					$fields .= "IF($tablecolname IS NULL OR $tablecolname = '0000-00-00 00:00:00', '--', date_format($tablecolname, '%m/%d/%Y %r')) AS ".$col["column"].",";
				// Eg casetracker: clientinfo
				// Produce a Arrow button and display childrecords in a subtable
				} elseif ( $col["input_type"] == "drilldown" || $col["input_type"] == "crosswalk" ) {
					continue;
				} else {
					$fields .= "$table.".$col["column"].", ";
				}
				// add in ajax filters wheres here
				// this was implemented in pajm and could be reused here if we do serverside filtering
				// where the SQl statement is updated using ajax
				if ( !empty($_POST[$col["column"]]) ) {
					// BN - ex: In " t$i.id REGEXP ", what is "t" for ?
					// MD - {t}ableN.id
					${$col["column"]} = implode("','", $_POST[$col["column"]]);
					if ( $col["filterbox"] == "checkbox" ) { 
						if ( $col["multiple"] == "yes" && $col["input_type"] == "tableselect")
							$addwheres .= " AND t$i.id REGEXP '".str_replace(",", "||", ${$col["column"]})."' ";
						elseif ( $col["multiple"] == "yes" ) // just type select
							$addwheres .= " AND ".$col["column"]." REGEXP '".str_replace(",", "||", ${$col["column"]})."' ";
						else
							$addwheres .= " AND ".$col["column"]." IN('".${$col["column"]}."') ";
					} elseif ( $col["filterbox"] == "text" && !empty(${$col["column"]}) ) {
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

			$sqlstatement = "SELECT $table.id, $fields FROM $table $ljointables $wheres $groupby $colorderby";
			//
			// END BUILD SQL STATMENT BASED ON CONFIG FILE ARRAYS FOR PAGE
			//
			// Get Records
			// Evaluate result of query
			$query = db_query($sqlstatement);
			if (!$query) {
				$result = "error";
				$message = "query error";
			} else {
				$result = "success";
				$message = "query success";
				$j=0;
				while ( $row = db_fetch_assoc($query) ) {
					$functions = '<div class="function_buttons"><ul>';
					$functions .= '<li class="function_edit"><a data-id="'.$row["id"].'" data-name="'.$_GET["dt_table"].'"><span>Edit</span></a></li>';
					$functions .= '<li class="function_delete"><a data-id="'.$row["id"].'" data-name="'.$_GET["dt_table"].'"><span>Delete</span></a></li>';
					$functions .= '</ul></div>';

					// Set each column by row as it comes from the query
					$k=0;
					foreach ( $colslist as $i => $col ) {
						// modify data
						// handle lists data eg iinput type select or tableselect
						if ( $col["input_type"] == "select" || $col["input_type"] == "tableselect" ) {
							foreach ( $lists[$col["column"]] as $lst ) {
								$row[$col["column"]] = str_replace($lst["key"], $lst["title"], $row[$col["column"]]);
							}
							if ( $col["multiple"] == "yes" ) {
								$row[$col["column"]] = str_replace(";", "/", $row[$col["column"]]);
							}
						// handle input type checkbox
						} elseif ( $col["input_type"] == "checkbox" ) {
							if ( $row[$col["column"]] == 1 ) {
								$row[$col["column"]] = "<i class=\"fa fa-fw fa-check-square\">";
							} else {
								$row[$col["column"]] = "<i class=\"fa fa-fw fa-square\">";
							}
						// handle input drilldown
						} elseif ( $col["input_type"] == "drilldown" ) {
							$row[$col["column"]] = '<div class="function_buttons"><ul>';
							$row[$col["column"]] .= '<li class="function_drilldown"><a data-id="'.$row["id"].'" data-name="'.$col["column"].'"><span>Show</span></a></li>';
							$row[$col["column"]] .= '</ul></div>';
						// Ignore crosswalk input type; this is only used in child records/nested tables
						} elseif ( $col["input_type"] == "crosswalk" ) {
							continue;
						// Unfinished - I'd like to find a way to have Datatables produce onhoover field data
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
					if ( $showrownum == "yes" && $_GET["dt_table"] == "maintable" ) {
						$query_data[$j] = array_merge( [ "rownum" => "rn" ], $query_data[$j] );
					}
					$j++;
				}
			}
		// END job: get records

		} elseif ( $job == "get_record" ) {
			// Get Record
			if ( $id == "" ) {
				$result = "error";
				$message = "id missing";
			} else {
				$sqlstatement = "SELECT * FROM $table WHERE id = '".addslashes($id)."'";
				$query = db_query($sqlstatement);
				if ( !$query ) {
					$result = "error";
					$message = "query error";
				} else {
					$result = "success";
					$message = "query success";
					$j=0;
					while ( $row = db_fetch_assoc($query) ) {
						$k=0;
						foreach ( $colslist as $i => $col ) {
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
			// Add Record: Needs to be verified to work with Oracle
			$sqlstatement = "INSERT INTO $table SET ";
			foreach ( $colslist as $i => $col ) {
				if ( isset($_GET[$col["column"]]) && empty($_GET[$col["column"]]) ) {
					$sqlstatement .= $col["column"]." = NULL, ";
				} elseif ( isset($_GET[$col["column"]]) ) {
					$sqlstatement .= $col["column"]." = '".addslashes($_GET[$col["column"]])."', ";
				}
				if ( $col["input_type"] == "crosswalk" ) {
					$crosswalk = 1;
					foreach ( $selslist as $k => $sel ) {
						if ( $col["column"] == $sel["selcol"] ) {
							$selid = $sel["selid"];
							$wherekey = $sel["wherekey"];
							$seltable = $sel["seltable"];
							$getid = addslashes($_GET["id"]);
						}
					}
				}
			}
			$sqlstatement = rtrim($sqlstatement, ', ');
			$query = db_query($sqlstatement);
			if ( !$query ) {
				$result = "error";
				$message = "query error";
			} else {
				$result = "success";
				$message = "query success";
				$lastid = mysqli_insert_id($conn);
				if ( is_numeric($getid) && $crosswalk == 1 ) {
					// selid == lastid; wherekey == _GET["id"]; seltable == table; input_type == "crosswalk"
					$sqlstatement = "INSERT INTO $seltable SET $selid = '$lastid', $wherekey = '$getid'";
					$query = db_query($sqlstatement);
				}
			}
		// END job: add_record
		} elseif ( $job == "edit_record" ) {
			// Edit Record
			if ( $id == "" ) {
				$result = "error";
				$message = "id missing";
			} else {
				$sqlstatement = "UPDATE $table SET ";
				foreach ( $colslist as $i => $col ) {
					if ( isset($_GET[$col["column"]]) && empty($_GET[$col["column"]]) ) {
						$sqlstatement .= $col["column"]." = NULL, ";
					} elseif ( isset($_GET[$col["column"]]) ) {
						$sqlstatement .= $col["column"]." = '".addslashes($_GET[$col["column"]])."', ";
					} elseif ( !isset($_GET[$col["column"]]) && $col["input_type"] == "checkbox" ) {
						$sqlstatement .= $col["column"]." = '0', ";
					}
				}
				$sqlstatement = rtrim($sqlstatement, ', ');
				$sqlstatement .= "WHERE id = '".addslashes($id)."'";
				$query = db_query($sqlstatement);
				if ( !$query ) {
					$result = "error";
					$message = "query error";
				} else {
					$result = "success";
					$message = "query success";
				}
			}
		// END job: edit_record
		} elseif ( $job == "delete_record" ) {
			// Delete Record: Needs to be verified to work with Oracle
			if ( $id == "" ) {
				$result = "error";
				$message = "id missing";
			} else {
				$sqlstatement = "DELETE FROM $table WHERE id = '".addslashes($id)."'";
				$query = db_query($sqlstatement);
				if ( !$query ) {
					$result = "error";
					$message = "query error";
				} else {
					$result = "success";
					$message = "query success";
				}
			}
		// End Job: delete_record
		} elseif ( $job == "page_lists" ) {
			$result = "success";
			$message = "page_lists";
			$query_data = [ "page" => $_GET["page"], "app" => $_GET["app"] ];
		// End Job: page_lists
		} elseif ( $job == "ajax_select" ) {
			$nestedcolumn=urldecode($_GET["nestedcolumn"]);	//nestedcolumn={{SELCOL}}
			$nestedname=urldecode($_GET["nestedname"]);		//nestedname={{SELNAME}}
			$nestedid=urldecode($_GET["nestedid"]);			//nestedid={{SELID}}
			$nestedtable=urldecode($_GET["nestedtable"]);	//nestedtable={{SELTABLE}}
			$wherekey=urldecode($_GET["wherekey"]);			//wherekey={{WHEREKEY}}
			$wherevalue=urldecode($_GET["wherevalue"]);		//whervalue={{WHEREVAL}}
			$nestedunion=urldecode($_GET["nestedunion"]);	//wherval={{WHEREVAL}}
			$parentcolumn=urldecode($_GET["parentcolumn"]);	//parentcolumn={{PARSELCOL}}
			$parenttitle=urldecode($_GET["parenttitle"]);	//parenttitle={{PARTITLE}}
			$parentid=urldecode($_GET["parentid"]);			//grabbed by jquery event this val

			if ( !empty($nestedunion) ) {
				$unionstring = " UNION ( SELECT ".$nestedid.", ".$nestedname." as ".$nestedcolumn." FROM ".$nestedunion."=".$parentid." )";
			}
			if ( !empty($wherevalue) ) {
				$wherestring = ' WHERE '.$wherekey." LIKE ".$wherevalue; // !!! CANNOT USE SINGLE OR DOUBLE QUOTES HERE, PLACE IN VAR
			} elseif ( !empty($wherekey) ) {
				$wherestring = ' WHERE '.$wherekey;
			}
			//Fetch all state data
			$sqlstatement = "SELECT t1.".$nestedid.", ".$nestedname." as ".$nestedcolumn." FROM ".$nestedtable.$wherestring." and t2.id = ".$parentid.$unionstring." ORDER BY ".$nestedid." ASC";
			$query = db_query($sqlstatement);
			//Count total number of rows
			$rowCount = db_num_rows($query);

			//Project option list
			if ( $rowCount > 1 ) { //only print if there are multiple rows
				echo '<option value="">Select a '.$parenttitle.'</option>';
			} 
			if ( $rowCount > 0 ) { 
				while ( $row = db_fetch_assoc($query) ) {
					echo '<option value="'.$row[$nestedid].'">'.$row[$nestedcolumn].'</option>';
				}
			} else {
				echo '<option value="">'.$sqlstatement.' not available</option>'; // throw sql to option for debugging
			}
		}
		// End Job: ajax_select
		// Close database connection
		db_close($conn);
	}
}
if ( $job != "ajax_select" ) {	
	// Prepare dat from info in config file arrays
	$data = array(
		"pginfo"  => $pageinfo, // page directives. Ex: Title, table, pagination, etc
		"colsls"  => $colslist, // columns and col settings
		"lists"   => $lists, // support non-db data lists
		"selslist"  => $selslist, // list of lookup table related info for dropdowns and joins
		"rowfmt"  => $rowformat, // allows for visual formatting of rows based on row data
		"sql"     => $sqlstatement, // generated sql statement for page
		"result"  => $result, // if $query true = success, else $query false = error
		"message" => $message, // message from qry execuction (select, insert, update, delete)
		"data"    => $query_data, // data returned from "get_records" and "get_record"
		"lastid"  => $lastid, // pk_id of inserted
	);

	// Convert PHP array to JSON array
	$json_data = json_encode($data);
	//output the JSON array data for use by ajax call
	print $json_data;

}
?>
