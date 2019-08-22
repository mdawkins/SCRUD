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
    
<?php 
include_once "$approot/css/layout.css";

if ( isset($rowformat) ) {
	include_once "$approot/functions/cssstyle.php";
	$bgcolorodd = '#fff';
	$bgcoloreven = '#ddd';
	$bgcolorhover = '#ffd';
	echo "<style>\n";
	foreach ( $rowformat as $rfm ) {
		$rfmvalue = $rfm["value"];
		$rfmbgcolor = $rfm["background-color"];
		$rfmbgcoloreven = blend_rowcolors($bgcoloreven, $rfmbgcolor);
		$rfmbgcolorhover = blend_rowcolors($bgcolorhover, $rfmbgcolor);
		echo "table.datatable tbody tr.color$rfmvalue.odd {
  background-color: $rfmbgcolor;
}
table.datatable tbody tr.color$rfmvalue.even {
  background-color: $rfmbgcoloreven;
}
table.datatable tbody tr.color$rfmvalue:hover {
  background-color: $rfmbgcolorhover;
}\n";
	}
	echo "</style>\n";
}
?>
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
    <script>
/* Toggle between adding and removing the "responsive" class to topnav when the user clicks on the icon */
function myFunction() {
  var x = document.getElementById("myTopnav");
  if (x.className === "topnav") {
    x.className += " responsive";
  } else {
    x.className = "topnav";
  }
}

function openNav() {
	  document.getElementById("myNav").style.width = "250px";
	  }

function closeNav() {
  document.getElementById("myNav").style.width = "0%";
}
    </script>
    <script><?php if ( !empty($_GET["page"]) ) { require_once "$approot/js/webapp_js.php"; } ?></script>
  </head>
  <body>
    <!-- top menu bar --!>
    <div class="topnav" id="myTopnav">
     <a class="active" href="#home" onclick="openNav()"><i class="fa fa-fw fa-bars"></i>Menu</a>
     <a href="#news"><i class="fa fa-fw fa-check-square"></i>News</a>
     <a href="#contact"><i class="fa fa-fw fa-square"></i>Contact</a>
     <a href="#about"><i class="fa fa-fw fa-home"></i>About</a>
     <a href="javascript:void(0);" class="icon" onclick="myFunction()">
      <i class="fa fa-bars"></i>
     </a>
    </div>

    <div id="myNav" class="overlay">
     <a href="javascript:void(0)" class="closebtn" onclick="closeNav()">&times;</a>
     <div class="overlay-content">
<?php echo $menuhtml; ?>
     </div>
    </div>

    <div id="page_container">
      <table class="datatable" id="table_records">
        <thead>
	  <tr>
<?php
if ( !empty($_GET["page"]) ) {
	foreach ( $colslist as $i => $col ) {
		echo "<th>".$col["title"]."</th>\n";
	}
	echo "<th>Functions</th>";
	echo "</tr>\n<tr>\n";
	foreach ( $colslist as $i => $col ) {
		echo "<th class=\"filter_content\"></th>";
	}
	echo "<th>\n";
	echo "<div class=\"topfunc_buttons\"><ul>\n";
	echo "<li id=\"reset\" class=\"function_reordercols\"><a><span>Reorder</span></a></li>\n";
	echo "<li id=\"add_record\" class=\"function_addrecord\"><a><span>Add</span></a></li>\n";
	echo "</ul></div>\n";
	echo "</th>\n";
}
?>
          </tr>
        </thead>
        <tbody>
        </tbody>
      </table>

    </div>

    <div class="lightbox_bg"></div>

    <div class="lightbox_container">
      <div class="lightbox_close"></div>
      <div class="lightbox_content">
        
        <h2>##blank##</h2>
	<form class="form add" id="form_record" data-id="" novalidate>
<?php
foreach ( $colslist as $i => $col ) {
	if ( $col["input_type"] != "noform" ) {
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
			echo "\t\t<input type=\"textarea\" class=\"text\" name=\"".$col["column"]."\" id=\"".$col["column"]."></textarea>\n";
			break;

		case "checkbox":
			echo "\t\t<input type=\"checkbox\" class=\"text\" name=\"".$col["column"]."\" id=\"".$col["column"]."\" value=\"1\" >\n";
			break;

		case "tableselect":
			include_once "$funcroot/selecttbllist.php";
		case "select":
			if ( $col["multiple"] == "yes" ) { $multiple = "multiple"; $size = "size=\"3\""; 
			} else { unset($multiple); $size = "size=\"1\""; }
			//echo "\t\t<select class=\"text\" name=\"".$col["column"]."[]\" id=\"".$col["column"]."\" ".$size." ".$multiple.">
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
