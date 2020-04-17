<!doctype html>
<html lang="en" dir="ltr">
	<head>
		<title>Loading...</title>
		<meta charset="utf-8">
		<meta name="viewport" content="width=1000, initial-scale=1">
		<meta http-equiv="X-UA-Compatible" content="IE=Edge">

		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/themes/base/jquery-ui.min.css">
<!--<link rel="stylesheet" href="https://cdn.datatables.net/1.10.18/css/dataTables.jqueryui.min.css"> --!>
		<link rel="stylesheet" href="//cdn.datatables.net/colreorder/1.5.2/css/colReorder.dataTables.min.css">
		<link rel="stylesheet" href="https://cdn.datatables.net/fixedcolumns/3.3.0/css/fixedColumns.jqueryui.min.css">
		<link rel="stylesheet" href="https://cdn.datatables.net/fixedheader/3.1.6/css/fixedHeader.jqueryui.min.css">
<!-- <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.2/css/responsive.jqueryui.min.css"> --!>
<!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/yadcf/0.9.3/jquery.dataTables.yadcf.min.css"> --!>
		<link rel="stylesheet" href="/css/jquery.dataTables.yadcf.css">
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.12/css/select2.min.css">
		
		<link rel="stylesheet" href="/css/layout.css">
		<link rel="stylesheet" href="/css/topmenubar.css">
		<link rel="stylesheet" href="//fonts.googleapis.com/css?family=Oxygen:400,700">
		<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.11.2/css/all.css">

		<script charset="utf-8" src="/js/topnav.js"></script>
		<script charset="utf-8" src="/js/webapp_functions.js"></script>

		<script charset="utf-8" src="//code.jquery.com/jquery-3.4.1.min.js"></script>
		<script charset="utf-8" src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
		<script charset="utf-8" src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.1/jquery.validate.min.js"></script>
		<script charset="utf-8" src="//cdn.datatables.net/1.10.20/js/jquery.dataTables.min.js"></script>
		<script charset="utf-8" src="//cdn.datatables.net/colreorder/1.5.2/js/dataTables.colReorder.min.js"></script>
		<script charset="utf-8" src="//cdn.datatables.net/fixedcolumns/3.3.0/js/dataTables.fixedColumns.min.js"></script>
		<script charset="utf-8" src="//cdn.datatables.net/fixedheader/3.1.6/js/dataTables.fixedHeader.min.js"></script>
		<script charset="utf-8" src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.24.0/moment.min.js"></script>
		<script charset="utf-8" src="//cdn.datatables.net/plug-ins/1.10.20/sorting/datetime-moment.js"></script>
		<script charset="utf-8" src="http://openexchangerates.github.io/accounting.js/accounting.min.js"></script>
<!--<script charset="utf-8" src="https://cdn.datatables.net/responsive/2.2.2/js/dataTables.responsive.min.js"></script> --!>
<!--<script charset="utf-8" src="https://cdn.datatables.net/responsive/2.2.2/js/responsive.jqueryui.min.js"></script> --!>
<!--<script charset="utf-8" src="https://cdnjs.cloudflare.com/ajax/libs/yadcf/0.9.3/jquery.dataTables.yadcf.min.js"></script> --!>
		<script charset="utf-8" src="/js/jquery.dataTables.yadcf.js"></script>
		<script charset="utf-8" src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.12/js/select2.min.js"></script>
		<script charset="utf-8" src="/js/webapp.js"></script>
	</head>
	<body>

<!-- start top menu bar --!>
<?php
include_once "$approot/templates/topmenubar.php";
?>
<!-- end of top menu bar --!>
		<div id="filter_container">
			<table class="filtertable" id="filter_records">
				<tr>
					<td>&nbsp;</td>
				</tr>
			</table>
		</div>
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
