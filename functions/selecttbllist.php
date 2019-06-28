<?php
# server config
require_once "dbconnection.php";

// create array for creating select dropdown list
foreach ( $selslist as $sel ) {

	if ( !empty($sel["parselcol"]) && !empty($sel["partitle"]) ) {
		//echo "there: ".$sel["parselcol"]."<br>";
		$name[0] = [ [ "key" => "selectparent", "title" => "" ] ];
		$lists[$sel["selcol"]] = $name[0];
	} else {
		if ( !empty($sel["whereval"]) ) { $wherestring = ' where '.$sel["wherekey"]." like ".$sel["whereval"]; } // !!! CANNOT USE SINGLE OR DOUBLE QUOTES HERE, PLACE IN VAR
		elseif ( !empty($sel["wherekey"]) ) { $wherestring = ' where '.$sel["wherekey"]; }
		//$sqlsel_rows = "select * from ".$sel["seltable"].$wherestring;
		$sqlsel_rows = "select ".$sel["selid"].", ".$sel["selname"]." from ".$sel["seltable"].$wherestring." ORDER BY ".$sel["selname"];
		//echo $sqlsel_rows."<br>";
		$result = $conn->query($sqlsel_rows);
		if ($result->num_rows > 0) {
		// output data of each row
			$i=0;
			while($row = $result->fetch_assoc()) {
				//$name[$i] = [ [ "key" => $row[$sel["selid"]], "title" => $row[$sel["selname"]] ] ];
				$name[$i] = [ [ "key" => $row["id"], "title" => $row[$sel["selname"]] ] ];
				//echo $row[$sel["selid"]].":".$row[$sel["selname"]].";";
				if ( $i != 0 ) { $name[0] = array_merge($name[0], $name[$i]); }
				$i++;
			}
			$lists[$sel["selcol"]] = $name[0];
		}
		unset($wherestring);
	}
}
?>
