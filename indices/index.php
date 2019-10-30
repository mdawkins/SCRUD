<!doctype html>
<html lang="en" dir="ltr">
	<head>
	<title>Loading...</title>
		<meta charset="utf-8">
		<meta name="viewport" content="width=1000, initial-scale=1">
		<meta http-equiv="X-UA-Compatible" content="IE=Edge">

		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/themes/base/jquery-ui.min.css">
<!--<link rel="stylesheet" href="https://cdn.datatables.net/1.10.18/css/dataTables.jqueryui.min.css"> --!>
		<link rel="stylesheet" href="//cdn.datatables.net/colreorder/1.5.1/css/colReorder.dataTables.min.css">
		<link rel="stylesheet" href="https://cdn.datatables.net/fixedcolumns/3.2.5/css/fixedColumns.jqueryui.min.css">
		<link rel="stylesheet" href="https://cdn.datatables.net/fixedheader/3.1.4/css/fixedHeader.jqueryui.min.css">
<!-- <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.2/css/responsive.jqueryui.min.css"> --!>
<!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/yadcf/0.9.3/jquery.dataTables.yadcf.min.css"> --!>
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
	<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.11.2/css/all.css">

		<script charset="utf-8" src="/js/topnav.js"></script>
		<script charset="utf-8" src="/js/webapp_functions.js"></script>

		<script charset="utf-8" src="//code.jquery.com/jquery-3.4.1.min.js"></script>
		<script charset="utf-8" src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
		<script charset="utf-8" src="//cdn.jsdelivr.net/jquery.validation/1.13.1/jquery.validate.min.js"></script>
		<script charset="utf-8" src="//cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>
		<script charset="utf-8" src="//cdn.datatables.net/colreorder/1.5.1/js/dataTables.colReorder.min.js"></script>
		<script charset="utf-8" src="//cdn.datatables.net/fixedcolumns/3.2.6/js/dataTables.fixedColumns.min.js"></script>
		<script charset="utf-8" src="//cdn.datatables.net/fixedheader/3.1.5/js/dataTables.fixedHeader.min.js"></script>
<!--<script charset="utf-8" src="https://cdn.datatables.net/responsive/2.2.2/js/dataTables.responsive.min.js"></script> --!>
<!--<script charset="utf-8" src="https://cdn.datatables.net/responsive/2.2.2/js/responsive.jqueryui.min.js"></script> --!>
<!--<script charset="utf-8" src="https://cdnjs.cloudflare.com/ajax/libs/yadcf/0.9.3/jquery.dataTables.yadcf.min.js"></script> --!>
		<script charset="utf-8" src="/js/jquery.dataTables.yadcf.js"></script>
		<script charset="utf-8" src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.8/js/select2.min.js"></script>
		<script charset="utf-8" src="/js/webapp.js"></script>
	</head>
	<body>

<!-- start top menu bar --!>
<?php
include_once "$approot/templates/topmenubar.php";
?>
<!-- end of top menu bar --!>

		<div id="page_container">
			<table class="datatable" id="table_records">
				<tbody>
				</tbody>
			</table>

		</div>

		<div class="lightbox_bg"></div>

		<div class="lightbox_container">
			<div class="lightbox_close"></div>
			<div class="lightbox_content">
			<!-- addeditform recordform --!>
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
