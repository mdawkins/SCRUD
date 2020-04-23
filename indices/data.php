<?php
$eta = -microtime(true);
if ( !empty($_GET["page"]) ) {
	require_once "pages/".$_GET["page"].".php";
	if( isset($colorderby) ) {
		$colorderby = "ORDER BY ".str_replace("::", " ", $colorderby);
	}
	$pageinfo = [ "pagetitle" => $pagetitle, "table" => $table, "showidcolumn" => $showidcolumn, "showrownum" => $showrownum, "showdeletecolumn" => $showdeletecolumn, "colorderby" => $colorderby, "rowlimit" => $rowlimit, "waittoload" => $waittoload ];

	// Database details
	require_once ".serv.conf";
	require_once "$funcroot/dbconnection.php";
	// performance time
	$timecheck = [ "P1" => $eta + microtime(true) ];

	// Get job (and id)
	$job = $id = "";
	if ( isset($_GET["job"]) ) {
		$job = $_GET["job"];
		if ( $job == "get_records" || $job == "get_record" || $job == "add_record" || $job == "edit_record" || $job == "delete_record" || $job == "page_info" || $job == "page_lists" || $job == "attach_record" || $job == "ajax_select" ) {
			// set name of table_pk or id
			if ( empty($table_pk) || !isset($table_pk) ) {
				$table_pk = "id";
			}
			// set value for id / table_pk
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
		$conn = db_connect($servername, $username, $password, $database, $datasource);
		if ( $result == "error" ) {
			//$result = "error";
			$message = "Failed to connect to database: ".mysqli_connect_error();
			$job = "";
		}
		// performance time
		$timecheck = array_merge($timecheck, [ "P2" => $eta + microtime(true) ]);

		// tableselect is a html select where its options are generated from
		// a DB table query. It requieres a valid array pair bewtween selslist & colslist
		// the following code, converts the selslist array into lists array with populated
		// "key" and "title" values.

		// Does input_type == tableselect? AND job == page_lists OR ajax_select OR get_record- this logic is super costly!
		if ( array_search( "tableselect", array_column( $colslist, "input_type" ) ) !== null && ($job == "page_lists" || $job == "ajax_select" || $job == "get_record") ) {
			// create array for creating select dropdown list
			foreach ( $selslist as $sel ) {
				// This identifies that the select is cascading/nested to a parent select
				if ( $job != "ajax_select" ) {
					if ( empty($sel["parselcol"]) && empty($sel["partitle"]) ) {
					// !!! CANNOT USE SINGLE OR DOUBLE QUOTES HERE, PLACE IN VAR 
						// if there is a wildcard use LIKE AND key & value are not empty
						if ( preg_match('/%/', $sel["wherekey"]) || preg_match('/%/', $sel["whereval"]) && (!empty($sel["wherekey"]) && !empty($sel["whereval"])) ) {
							$wherestring = ' WHERE '.$sel["wherekey"]." LIKE ".$sel["whereval"];
						} elseif ( !empty($sel["wherekey"]) && !empty($sel["whereval"]) ) {
							$wherestring = ' WHERE '.$sel["wherekey"]." = ".$sel["whereval"];
						} elseif ( !empty($sel["wherekey"]) ) {
							$wherestring = ' WHERE '.$sel["wherekey"];
						}
					} else {
						$wherestring = "";
					}
					$sqlsel_rows = "SELECT ".$sel["selid"].", ".$sel["selname"]." FROM ".$sel["seltable"].$wherestring." ORDER BY ".$sel["selname"];
					//echo $sqlsel_rows."<br>";
					$timecheck = array_merge($timecheck, [ "P3-pre".$sel["selcol"] => $eta + microtime(true) ]);
					$result = db_query($sqlsel_rows);
					if ( db_num_rows($result) > 0) {
						// output data of each row
						$i=0;
						while ( $row = db_fetch_assoc($result) ) {
							// this allows a non id ket to be the selid
							$name[$i] = [ [ "key" => $row[$sel["selid"]], "title" => $row[$sel["selname"]] ] ];
							// this hard codes the selid to be id.... why did I do this?
							//$name[$i] = [ [ "key" => $row["id"], "title" => $row[$sel["selname"]] ] ];
							//echo $row[$sel["selid"]].":".$row[$sel["selname"]].";";
							if ( $i != 0 ) { $name[0] = array_merge($name[0], $name[$i]); }
							$i++;
						}
						$timecheck = array_merge($timecheck, [ "P3-list".$sel["selcol"] => $eta + microtime(true) ]);
						$lists[$sel["selcol"]] = $name[0];
					}
					unset($wherestring);
					unset($result);
				} elseif ( !empty($sel["parselcol"]) && !empty($sel["partitle"]) ) {
					//echo "there: ".$sel["parselcol"]."<br>";
					$name[0] = [ [ "key" => "selectparent", "value" => $sel["parselcol"], "title" => "Select ".$sel["partitle"]." first" ] ];
					$lists[$sel["selcol"]] = $name[0];
				}
				$timecheck = array_merge($timecheck, [ "P3-".$sel["selcol"] => $eta + microtime(true), "sql-".$sel["selcol"] => $sqlsel_rows ]);
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

		// performance time
		$timecheck = array_merge($timecheck, [ "P4" => $eta + microtime(true) ]);

		// Execute job
		if ( $job == "get_records" ) {

			// filter API for wheres or additional filtering
			// any input type can be passed a variable to filter, but not all types can be filtered from the server side filter menu
			if ( !empty($_GET["filter"]) ) {

				if ( $_GET["filter"] == "0::0" ) {
					// this is to not run the query
					$wheres = "0::0";
				}
				// partner to use ;; splits filter pairs, :: splits keys from values
				$filterpairs = explode(";;", addslashes($_GET["filter"]));

				// this works like so: arrray_search "needle" inside array("haystack");
				foreach ( $filterpairs as $i => $filterpair ) {
					list($filtercolumn, $filtervalue) = explode("::", $filterpair);
					if ( !empty($filtervalue) ) {
						$filterlist[$i] = [ "column" => $filtercolumn, "value" => $filtervalue ];
					}
				}
				//print_r($filterlist);
			}
			// END filter API

			// BUILD SQL STATMENT BASED ON CONFIG FILE ARRAYS FOR PAGE
			$table = encap_mixedcase($table);

			// table_pk is used to populate other html elements
			$tbl_pk = encap_mixedcase($table_pk);

			// for oracle schema
			$dbtable = $table;
			if ( !empty($datasource) ) {
				$dbtable = "$database.$table";
			}

			foreach ( $colslist as $i => $col ) {
				// check to see if column name is mixed case and if so encapsulate with proper character
				// array elements to be encapsulated: selcol, selname, selid, seltable, wherekey, parselcol, column, table, table_pk, Anything from lists?
				$columnname = encap_mixedcase($col["column"]);

				// IF col input type is a tableselect OR input type is a crosswalk and selcol can be found in columns colslist
				if ( ( $col["input_type"] == "tableselect" || $col["input_type"] == "crosswalk" || $col["input_type"] == "columntotal" ) && array_search($col["column"], array_column($selslist, "selcol")) !== null ) {
					// SELCOL should == COLUMN
					//echo "{SELCOLINDEX: $selcolindex, SELCOL: ".$selslist[$selcolindex]["selcol"].", COLUMN: ".$col["column"]."},<br>";

					// this will be faster to access the selcol == column pair instead if looping thru the selslist array for each column array
					$selcolindex = array_search($col["column"], array_column($selslist, "selcol"));
					$selcol = encap_mixedcase($selslist[$selcolindex]["selcol"]); // not sure it is used
					$selname = encap_mixedcase($selslist[$selcolindex]["selname"]);
					$selid = encap_mixedcase($selslist[$selcolindex]["selid"]);
					$seltable = encap_mixedcase($selslist[$selcolindex]["seltable"]);
					$seljoin = $selslist[$selcolindex]["seljoin"]; // seljoin == yes means LEFT JOIN seltable as t$i
					$parselcol = encap_mixedcase($selslist[$selcolindex]["parselcol"]);
					$wherekey = encap_mixedcase($selslist[$selcolindex]["wherekey"]);

					// for oracle schema
					$dbseltable = $seltable;
					$dbasvar = "AS";
					if ( !empty($datasource) ) {
						$dbseltable = "$database.$seltable";
						$dbasvar = "";
					}

					// set default for table for addwheres
					$filtertable = "t$i";

					// don't join unless we need to only with crosswalk table not eq to childtable OR specify seljoin = yes
					if ( ($col["input_type"] == "crosswalk" && $seltable != $table) || $seljoin == "yes" ) {
						$ljointables .= "LEFT JOIN $dbseltable $dbasvar t$i ON ";
					} elseif ( $col["input_type"] == "columntotal" ) {
						$ljointables .= "LEFT JOIN ";
					}

					// columntotal create inner select statement
					if ( $col["input_type"] == "columntotal" ) {
						// set default type to SUM
						$totaltype = "SUM";
						if ( $col["total_type"] == "sum" ) {
							$totaltype = "SUM";
						} elseif ( $col["total_type"] == "avg" ) {
							$totaltype = "AVG";
						} elseif ( $col["total_type"] == "max" ) {
							$totaltype = "MAX";
						} elseif ( $col["total_type"] == "min" ) {
							$totaltype = "MIN";
						} elseif ( $col["total_type"] == "cnt" ) {
							$totaltype = "COUNT";
						}
						$innersqlstatement = "( SELECT $selid, $totaltype($selname) AS $columnname FROM $dbseltable GROUP BY $selid ) t$i ON";
					}

					// if crosswalk
					if ( $col["input_type"] == "crosswalk" ) {
					   	if ( $seltable != $table ) {
							$cwtable = "t$i";
							// honestly this is backwards, the selid should = id
							if ( empty($selname) ) {
								// table_pk maybe?
								$selname = "id";
							}
							if ( !empty($parselcol) ) {
								// left join to another table than the config file table
								$filtertable = $partable = "t".array_search( $parselcol, array_column($colslist, "column") );
							} else {
								$partable = $table;
							}
							$ljointables .= "$partable.$selname = t$i.$selid ";
						} else {
							// else use the config file table for crosswalk and no fields
							$cwtable = $table;
						}

						// set wheres for crosswalk in child config
						if ( $_GET["dt_table"] != "maintable" && !empty($_GET["dt_table"]) ) {
							$wheres .= "$cwtable.$wherekey = $id AND"; 
						} // else no wheres for crosswalk tables not used in child configs

					// if tableselect
					} elseif ( $seljoin == "yes" ) {
						// seltable used if seljoin == yes
						$fields .= "t$i.$selname AS $columnname, ";
						// filtertable already set
						$ljointables .= "$table.$columnname = t$i.$selid ";
					// why use left joins when the data is the same on the datatable but this is used to create tableselects in the forms
					// Default logic if col input type is tableselect
					} elseif ( $seljoin != "yes" && !empty($parselcol) ) { // Not intended for nested/cascading columns, expected empty(partitle)
						// use seltable from parent select column
						$fields .= "$partable.$columnname, ";
						$filtertable = "t".array_search( $parselcol, array_column($colslist, "column") );
						// no ljointables
					} elseif ( $col["input_type"] == "columntotal" ) {
						$fields .= "t$i.$columnname, ";
						// filtertable already set
						$ljointables .= "$innersqlstatement $table.$selid = t$i.$selid ";
					} else {
						$fields .= "$table.$columnname, ";
						// filtertable already set
						// no ljointables
					}
				// IF pivotjoin
				} elseif ( $col["input_type"] == "pivotjoin" ) {
					$fields .= "`".$columnname."`, ";
					$ljointables .= " LEFT JOIN (SELECT $pivkey, GROUP_CONCAT(DISTINCT(CASE WHEN $joinkey = '".$col["key"]."' THEN $keyname END) ORDER BY 1 SEPARATOR ', ') AS '$columnname' FROM $jointable WHERE $joinwherekey = $joinwhereval AND $joinkey = '".$col["key"]."' GROUP BY $pivkey) AS t".$i." ON $table.id=t$i.$pivkey";
					// table_pk maybe?
				//  Show in viewtable but not in the addedit form
				} elseif ( $col["input_type"] == "noform" ) {
					$fields .= str_replace("%T%", "$table", $col["colfunc"])." AS $columnname, ";
				//  Format number to currency or if zero leave --
				} elseif ( $col["input_type"] == "currency" ) {
					$tablecolname = "$table.$columnname";
					if ( $service == "mysql" ) {
						$fields .= "IF($tablecolname IS NULL, '0', $tablecolname) AS $columnname, ";
					} elseif ( $service == "oracle" ) {
						$fields .= "NVL($tablecolname, 0) AS $columnname, ";
					} elseif ( $service == "pgsql" ) {
						$fields .= "COALESCE($tablecolname, 0) AS $columnname, ";
					}
					//$fields .= "IF($tablecolname IS NULL OR $tablecolname = '0', '--', CONCAT(FORMAT($tablecolname, 2))) AS $columnname, ";
				//  Format YYYY/MM/DD to MM/DD/YYYY or leave --
				} elseif ( $col["input_type"] == "date" ) {
					$tablecolname = "$table.$columnname";
					if ( $service == "mysql" ) {
						$fields .= "IF($tablecolname IS NULL OR $tablecolname = '0000-00-00', '--', DATE_FORMAT($tablecolname, '%m/%d/%Y')) AS $columnname, ";
					} elseif ( $service == "oracle" ) {
						$fields .= "NVL(TO_CHAR($tablecolname, 'mm/dd/yyyy'), '--') AS $columnname, ";
					} elseif ( $service == "pgsql" ) {
						$fields .= "COALESCE(TO_CHAR($tablecolname, 'mm/dd/yyyy'), '--') AS $columnname, ";
					}
				//  Format YYYY/MM/DD 24H to MM/DD/YYYY 12a/p or leave --
				} elseif ( $col["input_type"] == "datetime" ) {
					$tablecolname = "$table.$columnname";
					if ( $service == "mysql" ) {
						$fields .= "IF($tablecolname IS NULL OR $tablecolname = '0000-00-00 00:00:00', '--', DATE_FORMAT($tablecolname, '%m/%d/%Y %r')) AS $columnname, ";
					} elseif ( $service == "oracle" ) {
						$fields .= "NVL(TO_CHAR($tablecolname, 'mm/dd/yyyy HH12:MI:SS'), '--') AS $columnname, ";
					} elseif ( $service == "pgsql" ) {
						$fields .= "COALESCE(TO_CHAR($tablecolname, 'mm/dd/yyyy HH12:MI:SS'), '--') AS $columnname, ";
					}
				// Produce a Arrow button and display childrecords in a subtable
				} elseif ( $col["input_type"] == "drilldown" || $col["input_type"] == "crosswalk" ) {
					continue;
				} else {
					$fields .= "$table.$columnname, ";
				}

				// filter API addwheres
				// input types that can have serverside filtering: tableselect, select, date, datetime
				// tableselect/select: select multiple
				// date/datetime: between
				$fpi = array_search($col["column"], array_column($filterlist, "column"));
				if ( $fpi !== false && !empty($_GET["filter"]) && !empty($filterlist) ) {
					// encapsulate filtertable
					//echo "$fpi : ".$filterlist[$fpi]["column"]." : ".$filterlist[$fpi]["value"]."<br>";
					if ( $col["multiple"] == "yes" && $col["input_type"] == "tableselect" ) {
						$addwheres .= " AND $filtertable.$selid REGEXP '".str_replace(",", "||", $filterlist[$fpi]["value"])."' ";
					} elseif ( $col["multiple"] == "yes" && $col["input_type"] == "select" ) { // just type select
						$addwheres .= " AND $table.$columnname REGEXP '".str_replace(",", "||", $filterlist[$fpi]["value"])."' ";
					} elseif ( $col["input_type"] == "tableselect" ) {
						$addwheres .= " AND $filtertable.$selid IN('".str_replace(",", "','", $filterlist[$fpi]["value"])."') ";
					} elseif ( $col["input_type"] == "date" || $col["input_type"] == "datetime" ) {
						$addwheres .= " AND $table.$columnname BETWEEN '".str_replace(",", "' AND '", $filterlist[$fpi]["value"])."') ";
					} elseif ( !empty($col["colfunc"]) ) { // concat val
						$addwheres .= " AND ".$col["colfunc"]." IN ('".str_replace(",", "','", $filterlist[$fpi]["value"])."') ";
					} else { // to match input type select and anything else thrown at the API
						$addwheres .= " AND $table.$columnname IN('".str_replace(",", "','", $filterlist[$fpi]["value"])."') ";
					}
				}
				// end filter API addwheres
			}

			// clean up of sql variables
			$fields = rtrim($fields,", ");
			if ( $wheres == "0::0" ) {
				// purposely load nothing
				$wheres = "WHERE 0";
			} elseif ( !empty($wheres) && empty($addwheres) ) {
				$wheres = "WHERE ".rtrim(trim($wheres),"AND");
			} elseif ( !empty($addwheres) && empty($wheres) ) {
				$wheres = "WHERE ".ltrim($addwheres, " AND ");
			} elseif ( !empty($wheres) && !empty($addwheres) )
				$wheres = "WHERE ".rtrim(trim($wheres),"AND").$addwheres;

			if ( $rowlimit > 0 ) {
				$limitrows = "LIMIT $rowlimit";
			} else unset($limitrows);

			$sqlstatement = "SELECT $table.$tbl_pk, $fields FROM $dbtable $ljointables $wheres $groupby $colorderby $limitrows";
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
					// would be nice to move this to the client side and instead produce pure JSON
					$functions = '<div class="function_buttons"><ul>';
					$functions .= '<li class="function_edit"><a data-id="'.$row["id"].'" data-name="'.$_GET["dt_table"].'"><span>Edit</span></a></li>';
					$functions .= '<li class="function_delete"><a data-id="'.$row["id"].'" data-name="'.$_GET["dt_table"].'"><span>Delete</span></a></li>';
					$functions .= '</ul></div>';

					// Set each column by row as it comes from the query
					$k=0;
					foreach ( $colslist as $i => $col ) {
						// modify data
						// handle lists key/value string replace for input type select
						if ( $col["input_type"] == "select" && ($col["keyreplace"] != "no" || $col["colview"] != "hide") ) {
							foreach ( $lists[$col["column"]] as $lst ) {
								$row[$col["column"]] = str_replace($lst["key"], $lst["title"], $row[$col["column"]]);
							}
						} elseif ( $col["multiple"] == "yes" && ($col["input_type"] == "select" || $col["input_type"] == "tableselect") ) {
								$row[$col["column"]] = str_replace(";", "/", $row[$col["column"]]);
						// handle input type checkbox
						} elseif ( $col["input_type"] == "checkbox" ) {
							if ( $row[$col["column"]] == 1 ) {
								$row[$col["column"]] = "<i class=\"fa fa-fw fa-check-square\">";
							} else {
								$row[$col["column"]] = "<i class=\"fa fa-fw fa-square\">";
							}
						// handle input drilldown
						} elseif ( $col["input_type"] == "drilldown" ) {
							if ( isset($col["columnid"]) ) {
								$rowid = $col["columnid"];
							} else {
								$rowid = $table_pk;
							}
							$row[$col["column"]] = '<div class="function_buttons"><ul>';
							$row[$col["column"]] .= '<li class="function_drilldown"><a data-id="'.$row[$rowid].'" data-name="'.$col["column"].'"><span>Show</span></a></li>';
							$row[$col["column"]] .= '</ul></div>';
						// Ignore crosswalk input type; this is only used in child records/nested tables
						} elseif ( $col["input_type"] == "crosswalk" ) {
							continue;
						// Unfinished - I'd like to find a way to have Datatables produce onhoover field data
						// this is possible with render and span title in DataTables
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
				$sqlstatement = "SELECT * FROM $table WHERE $table_pk = '".addslashes($id)."'";
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
				// decide how to handle crosswalk first is it Parent/Child or Many 2 Many (needs to be handled still if just a 3rd joined table)
				if ( $col["input_type"] == "crosswalk" ) {
					foreach ( $selslist as $k => $sel ) {
						// if parselcol is empty or !isset bc crosswalk + parselcol can be used to left join sibling columns that are keyed to other tables
						if ( $col["column"] == $sel["selcol"] && $sel["seltable"] != $table ) {
							$crosswalk = 1;
							$selid = $sel["selid"];
							$wherekey = $sel["wherekey"];
							$seltable = $sel["seltable"];
							$getid = addslashes($_GET["id"]);
						}
						elseif ( $col["column"] == $sel["selcol"] && $sel["seltable"] == $table ) {
							// add child column and id to the sql statement
							$sqlstatement .= $sel["wherekey"]." = '".addslashes($_GET["id"])."', ";
						}
					}
				}
				if ( isset($_GET[$col["column"]]) && empty($_GET[$col["column"]]) ) {
					//$sqlstatement .= $col["column"]." = NULL, ";
					// Nothing should happen here because if the column has a default value NULL or '' will overrwrite it.
					$sqlstatement .= "";
				} elseif ( isset($_GET[$col["column"]]) ) {
					$sqlstatement .= $col["column"]." = '".addslashes($_GET[$col["column"]])."', ";
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
				$sqlstatement .= " WHERE $table_pk = '".addslashes($id)."'";
				$query = db_query($sqlstatement);
				if ( !$query ) {
					$result = "error";
					$message = "query error";
				} else {
					$result = "success";
					$message = "query success";
				}
			}
			$query_data = [ "page" => $_GET["page"] ];
		// END job: edit_record
		} elseif ( $job == "delete_record" ) {
			// Delete Record: Needs to be verified to work with Oracle
			if ( $id == "" ) {
				$result = "error";
				$message = "id missing";
			} else {
				$sqlstatement = "DELETE FROM $table WHERE $table_pk = '".addslashes($id)."'";
				$query = db_query($sqlstatement);
				if ( !$query ) {
					$result = "error";
					$message = "query error";
				} else {
					$result = "success";
					$message = "query success";
				}
			}
			$query_data = [ "page" => $_GET["page"] ];
		// End Job: delete_record
		} elseif ( $job == "page_info" ) {
			$result = "success";
			$message = "page_info";
			$query_data = [ "page" => $_GET["page"] ];
		// End Job: page_info
		} elseif ( $job == "page_lists" ) {
			$result = "success";
			$message = "page_lists";
			$query_data = [ "page" => $_GET["page"] ];
		// End Job: page_lists
		} elseif ( $job == "attach_record" ) {
			// none of this works yet
			// but I'm putting it here as a placeholder and concept
			$i = array_search($col["column"], array_column($selslist, "selcol"));
			$colslist = array(
				[ "column" => $wherekey, "title" => "Record", "input_type" => "select", "disabled" => "disabled" ],
				[ "column" => $selid, "title" => "Case", "input_type" => "select" ],
			);

			$result = "success";
			$message = "attach_record";
		} elseif ( $job == "ajax_select" ) {
			$nestedcolumn=urldecode($_GET["nestedcolumn"]);	//nestedcolumn={{SELCOL}}
			$nestedname=urldecode($_GET["nestedname"]);		//nestedname={{SELNAME}}
			$nestedid=urldecode($_GET["nestedid"]);			//nestedid={{SELID}}
			$nestedtable=urldecode($_GET["nestedtable"]);	//nestedtable={{SELTABLE}}
			$wherekey=urldecode($_GET["wherekey"]);			//wherekey={{WHEREKEY}}
			$wherevalue=urldecode($_GET["wherevalue"]);		//whervalue={{WHEREVAL}}
			$parentcolumn=urldecode($_GET["parentcolumn"]);	//parentcolumn={{PARSELCOL}}
			$parenttitle=urldecode($_GET["parenttitle"]);	//parenttitle={{PARTITLE}}
			$parentid=urldecode($_GET["parentid"]);			//grabbed by jquery event this val

			if ( !empty($parentcolumn) ) {
				// finding the parent table and the parent selid
				foreach ( $selslist as $k => $sel ) {
					if ( $selslist[$k]["selcol"] == $parentcolumn ) {
						$parenttable = $selslist[$k]["seltable"];
						$parentnestedid = $selslist[$k]["selid"];
					}
				}
				$ljointables = "LEFT JOIN $parenttable AS t1 ON";
			}
			//Fetch all state data
			//this hard codes table as t0 and leftjoin table as t1
			// t1.nestedid should be the parent selid, also the ljointables should be the parent seltable
			$sqlstatement = "SELECT t0.$nestedid, t0.$nestedname AS $nestedcolumn FROM $nestedtable AS t0 $ljointables t1.$wherevalue=t0.$wherekey WHERE t1.$parentnestedid='$parentid' ORDER BY t0.$nestedid ASC";
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
				echo '<option value="'.$sqlstatement.'">No results available</option>'; // throw sql to option for debugging
			}
		}
		// End Job: ajax_select
		// Close database connection
		db_close($conn);
	}
	// performance time
	$timecheck = array_merge($timecheck, [ "P5" => $eta + microtime(true) ]);
}
if ( $job != "ajax_select" ) {	
	// Prepare data from info in config file arrays
	// depending on the page only send the data necessary
	// all pages need: result, message, data
	// get_records: needs data, rowfmt; helpful: pginfo, sql 
	// get_record: needs data, colsls, lists; helpful: sql 
	// page_info: needs pginfo, colsls
	// page_lists: needs colsls, lists; helpful selslist
	if ( $job == "get_records" ) {
		$data = array(
			"rowfmt"  => $rowformat, // allows for visual formatting of rows based on row data
			"sql"     => $sqlstatement, // generated sql statement for page
			"result"  => $result, // if $query true = success, else $query false = error
			"message" => $message, // message from qry execuction (select, insert, update, delete)
			"data"    => $query_data, // data returned from "get_records" and "get_record"
			"time"    => $timecheck,
		);
	} elseif ( $job == "get_record" || $job == "page_lists" ) { // page_list does not require the sql statement
		$data = array(
			//"pginfo"  => $pageinfo, // page directives. Ex: Title, table, pagination, etc
			"colsls"  => $colslist, // columns and col settings
			"lists"   => $lists, // support non-db data lists
			"selslist"  => $selslist, // list of lookup table related info for dropdowns and joins
			"sql"     => $sqlstatement, // generated sql statement for page
			"result"  => $result, // if $query true = success, else $query false = error
			"message" => $message, // message from qry execuction (select, insert, update, delete)
			"data"    => $query_data, // data returned from "get_records" and "get_record"
			"time"    => $timecheck,
		);
	} elseif ( $job == "add_record" ) {
		$data = array(
			"pginfo"  => $pageinfo, // page directives. Ex: Title, table, pagination, etc
			"sql"     => $sqlstatement, // generated sql statement for page
			"result"  => $result, // if $query true = success, else $query false = error
			"message" => $message, // message from qry execuction (select, insert, update, delete)
			"data"    => $query_data, // data returned from "get_records" and "get_record"
			"lastid"  => $lastid, // pk_id of inserted
			"time"    => $timecheck,
		);
	} elseif ( $job == "delete_record" || $job == "edit_record" || $job == "attach_record" ) {
		$data = array(
			"pginfo"  => $pageinfo, // page directives. Ex: Title, table, pagination, etc
			"sql"     => $sqlstatement, // generated sql statement for page
			"result"  => $result, // if $query true = success, else $query false = error
			"message" => $message, // message from qry execuction (select, insert, update, delete)
			"data"    => $query_data, // data returned used to double check that the correct request came back
			"time"    => $timecheck,
		);
	} elseif ( $job == "page_info" ) {
		$data = array(
			"pginfo"  => $pageinfo, // page directives. Ex: Title, table, pagination, etc
			"colsls"  => $colslist, // columns and col settings
			"rowfmt"  => $rowformat, // allows for visual formatting of rows based on row data
			"result"  => $result, // if $query true = success, else $query false = error
			"message" => $message, // message from qry execuction (select, insert, update, delete)
			"data"    => $query_data, // data returned used to double check that the correct request came back
			"time"    => $timecheck,
		);
	} else { // Show all objects/arrays
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
			"time"    => $timecheck,
		);
	}

	// Convert PHP array to JSON array
	$json_data = json_encode($data);
	//output the JSON array data for use by ajax call
	print $json_data;

}
?>
