<?php
if ( isset($_GET["page"]) ) {
	require_once "pages/".$_GET["page"].".php";
	$addgetvar = "&page=".$_GET["page"];
}
?>

<!doctype html>
<html lang="en" dir="ltr">
  <head>
  <title><?php echo $pagetitle; ?></title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=1000, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <link rel="stylesheet" href="//fonts.googleapis.com/css?family=Oxygen:400,700">
    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="layout.css">
    <script charset="utf-8" src="//ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
    <script charset="utf-8" src="//cdn.datatables.net/1.10.0/js/jquery.dataTables.js"></script>
    <script charset="utf-8" src="//cdn.jsdelivr.net/jquery.validation/1.13.1/jquery.validate.min.js"></script>
<!--    <script charset="utf-8" src="webapp_js.php"></script> --!>
	<script><?php require_once "webapp_js.php"; ?></script>
  </head>
  <body>

    <div id="page_container">

      <h1><?php echo $pagetitle; ?></h1>

      <button type="button" class="button" id="add_company">Add Record</button>

      <table class="datatable" id="table_companies">
        <thead>
	  <tr>
<?php
foreach ( $colslist as $i => $col ) {
	echo "<th>".$col["title"]."</th>\n";
}
?>
	    <th>Functions</th>
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
	<form class="form add" id="form_company" data-id="" novalidate>
<?php
foreach ( $colslist as $i => $col ) {
	if ( $col["required"] == "yes" ){
		$errspan = "<span class='required'>*</span>";
		$errinput = "required";
	} else {
		unset($errspan);
		unset($errinput);
	}
	echo "<div class='input_container'>\n";
	echo "	<label for='".$col["column"]."'>".$col["title"].": $errspan</label>\n";
	echo "	<div class='field_container'>\n";
	echo "		<input type='".$col["input_type"]."' class='text' name='".$col["column"]."' id='".$col["column"]."' value='' $errinput>\n";
	echo "	</div>\n";
	echo "</div>\n";
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
