<?php

if ( isset($_GET["page"]) ) {
	require_once "pages/".$_GET["page"].".php";
	$addgetvar = "&page=".$_GET["page"];
	if ( !empty($_GET["app"]) ) {
		$addgetvar .= "&app=".$_GET["app"];
	}	
	if ( empty($pagetitle) ) { $pagetitle = "Missing Title"; }
}
?>

<!doctype html>
<html lang="en" dir="ltr">
  <head>
  <title><?php echo $pagetitle; ?></title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=1000, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/themes/base/jquery-ui.min.css">
<!--    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.18/css/dataTables.jqueryui.min.css"> --!>
    <link rel="stylesheet" href="//cdn.datatables.net/colreorder/1.5.1/css/colReorder.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/fixedcolumns/3.2.5/css/fixedColumns.jqueryui.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/fixedheader/3.1.4/css/fixedHeader.jqueryui.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.2/css/responsive.jqueryui.min.css">
<!--    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/yadcf/0.9.3/jquery.dataTables.yadcf.min.css"> --!>
    <link rel="stylesheet" href="/css/jquery.dataTables.yadcf.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.8/css/select2.min.css">
    
    <link rel="stylesheet" href="/css/layout.css">
<?php 
if ( isset($rowformat) ) {
	include_once "$approot/functions/cssstyle.php";
	echo rowformat( $rowformat, '#fff', '#ddd', '#ffd' );
}
?>
    <link rel="stylesheet" href="/css/topmenubar.css">
    <link rel="stylesheet" href="//fonts.googleapis.com/css?family=Oxygen:400,700">
    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">

    <script charset="utf-8" src="//code.jquery.com/jquery-3.4.1.min.js"></script>
    <script charset="utf-8" src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
    <script charset="utf-8" src="//cdn.jsdelivr.net/jquery.validation/1.13.1/jquery.validate.min.js"></script>
    <script charset="utf-8" src="//cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>
    <script charset="utf-8" src="//cdn.datatables.net/colreorder/1.5.1/js/dataTables.colReorder.min.js"></script>
    <script charset="utf-8" src="//cdn.datatables.net/fixedcolumns/3.2.6/js/dataTables.fixedColumns.min.js"></script>
    <script charset="utf-8" src="//cdn.datatables.net/fixedheader/3.1.5/js/dataTables.fixedHeader.min.js"></script>
<!--<script charset="utf-8" src="https://cdn.datatables.net/responsive/2.2.2/js/dataTables.responsive.min.js"></script> --!>
<!--<script charset="utf-8" src="https://cdn.datatables.net/responsive/2.2.2/js/responsive.jqueryui.min.js"></script> --!>
<!--    <script charset="utf-8" src="https://cdnjs.cloudflare.com/ajax/libs/yadcf/0.9.3/jquery.dataTables.yadcf.min.js"></script> --!>
    <script charset="utf-8" src="/js/jquery.dataTables.yadcf.js"></script>
    <script charset="utf-8" src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.8/js/select2.min.js"></script>
    <script charset="utf-8" src="/js/topnav.js"></script>
    <script charset="utf-8" src="/js/addeditform.js"></script>
    <script><?php if ( !empty($_GET["page"]) ) { require_once "$approot/js/webapp_js.php"; } ?></script>
  </head>
  <body>

<!-- top menu bar --!>
<?php
include_once "$approot/templates/topmenubar.php";
?>

    <div id="page_container">
<?php
if ( !empty($_GET["page"]) ) {
	$showfilter = "no";
	$tablehtml = "<table class=\"datatable\" id=\"table_records\">\n";
	$tableheader = "<thead>\n\t<tr>\n";
	$tablefilter = "<tr>\n";
	if ( $showrownum == "yes" ) {
		$tableheader .= "<th>No.</th>\n";
		$tablefilter .= "<th class=\"filter_content\"></th>\n";
	}
	foreach ( $colslist as $i => $col ) {
		if ( !empty($col["filterbox"]) ) { $showfilter = "yes"; }
		if ( $col["input_type"] != "crosswalk" ) {
			$tableheader .= "<th>".$col["title"]."</th>\n";
			$tablefilter .= "<th class=\"filter_content\"></th>\n";
		}
	}
	$tableheader .= "<th>Functions</th>\n";
	$tablefilter .= "<th>\n";
	$tablefilter .= "<div class=\"topfunc_buttons\"><ul>\n";
	$tablefilter .= "<li id=\"reset\" class=\"function_reordercols\"><a><span title=\"Reorder Columns\">Reorder</span></a></li>\n";
	$tablefilter .= "<li id=\"add_record\" class=\"function_addrecord\"><a><span title=\"Add Record\">Add</span></a></li>\n";
	$tablefilter .= "</ul></div>\n";
	$tablefilter .= "</th>\n";
	$tableheader .= "</tr>\n";
	$tablefilter .= "</tr>\n";

	$tablehtml .= $tableheader;
	if ( $showfilter == "yes" ) {
		$tablehtml .= $tablefilter;
	}
	$tablehtml .= "</thead>\n";

	echo $tablehtml;
}
?>
        <tbody>
        </tbody>
      </table>

    </div>

    <div class="lightbox_bg"></div>

    <div class="lightbox_container">
      <div class="lightbox_close"></div>
      <div class="lightbox_content">

<!-- start of form --!>
        <h2>##blank##</h2>
	<form class="form add" id="form_record" data-id="" novalidate>
<?php
foreach ( $colslist as $i => $col ) {
	if ( $col["input_type"] != "noform" && $col["input_type"] != "drilldown" && $col["input_type"] != "crosswalk") {
		if ( $col["required"] == "yes" ) {
			$errspan = "<span class='required'>*</span>";
			$errinput = "required";
		} else {
			unset($errspan);
			unset($errinput);
		}
		echo "<div class='input_container'>\n";
		echo "\t<label for='".$col["column"]."'>".$col["title"].": $errspan</label>\n";
		echo "\t<div class='field_container'>\n";
		switch ( $col["input_type"] ) {

		case "text":
			echo "\t\t<input type=\"text\" class=\"text\" name=\"".$col["column"]."\" id=\"".$col["column"]."\" value=\"\" $errinput>\n";
			break;

		case "number":
		case "currency":
			echo "\t\t<input type=\"number\" class=\"text\" name=\"".$col["column"]."\" id=\"".$col["column"]."\" value=\"\" $errinput>\n";
			break;

		case "date":
			echo "\t\t<input type=\"date\" class=\"text\" name=\"".$col["column"]."\" id=\"".$col["column"]."\" value=\"\" $errinput>\n";
			break;

		case "datetime":
			echo "\t\t<input type=\"datetime-local\" class=\"text\" name=\"".$col["column"]."\" id=\"".$col["column"]."\" value=\"\" $errinput>\n";
			break;

		case "textarea":
			echo "\t\t<textarea class=\"text textarea\" name=\"".$col["column"]."\" id=\"".$col["column"]."></textarea>\n";
			break;

		case "checkbox":
			echo "\t\t<input type=\"checkbox\" class=\"text\" name=\"".$col["column"]."\" id=\"".$col["column"]."\" value=\"1\" >\n";
			break;

		case "tableselect":
			include_once "$funcroot/selecttbllist.php";
		case "select":
			if ( $col["multiple"] == "yes" ) { $multiple = "multiple";
			} else { unset($multiple); }
			echo "\t\t<select class=\"text\" name=\"".$col["column"]."\" id=\"".$col["column"]."\" ".$size." ".$multiple.">
			<option value=\"\">Select a ".$col["title"]."</option>\n";
			$multisels = explode(";", ${$col["column"]});
			foreach ( $lists[$col["column"]] as $list ) {
				if ( array_search($list["key"], $multisels) !== false ) { $selected="selected"; }
				if ( $list["key"] != "selectparent" ) {
					echo "\t\t\t<option value='".$list["key"]."' $selected >".$list["title"]."</option>\n";
				} else { $selectnested = "true"; }
				unset($selected);
			}
			echo "\t\t</select>\n";
		break;
		}
		echo "\t</div>\n";
		echo "</div>\n";
	}
}
?>
          <div class="button_container">
            <button type="submit">##blank##</button>
          </div>
        </form>
<!-- end of form --!>
        
      </div>
    </div>

    <noscript id="noscript_container">
      <div id="noscript" class="error">
        <p>JavaScript support is needed to use this page.</p>
      </div>
    </noscript>

    <div id="message_container">
      <div id="message" class="success">
        <p>This is a success message.</p>
      </div>
    </div>

    <div id="loading_container">
      <div id="loading_container2">
        <div id="loading_container3">
          <div id="loading_container4">
            Loading, please wait...
          </div>
        </div>
      </div>
    </div>

  </body>
</html>
