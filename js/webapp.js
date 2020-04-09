$(document).ready(function() {
	'use strict';

	// get all the url GET parameters and values
	let searchParams = new URLSearchParams(window.location.search);
	let page = searchParams.get('page');

	// set variables needed for maintable
	var pginfo, colsls, rowfmt;
	var lists;
	var pagetitle, table, showidcolumn, showrownum, showdeletecolumn, colorderby, rowlimit;
	var jsondtcolumns, jsonfiltercolumns, rwfmt;

	var request = getdata_ajax( 'page_info', {'page': page} );
	request.done(function(output) {
		if (output.result == 'success' && output.message == 'page_info') {
			// assign individual variables to their values
			for (let key in output.pginfo) {
				if ( output.pginfo[key] == null ) {
					var varname = key + ' = null';
				} else {
					var varname = key + ' = \"' + output.pginfo[key] + '\"';
				}
				eval(varname);
			}
			colsls = output.colsls;
			rowfmt = output.rowfmt;
		}
		// Set page title
		document.title = pagetitle;
		// Populate maintable header
		$("#table_records").html(dt_header( colsls, 'maintable', showrownum , showdeletecolumn ));

		// Set CSS for rowformat
		if ( rowfmt != null ) {
			$("head").append( rowformat( rowfmt, '#fff', '#ddd', '#ffd' ) );
			// calling page_lists here
			var request = getdata_ajax( 'page_lists', {'page': page} );
			request.done(function(output) {
				if (output.result == 'success' && output.message == 'page_lists') {
					lists = output.lists;
				}
			});
		}

		// set showdeletecolumn to yess if blank
		var ltCol, rtCol;
		ltCol = rtCol = 0;
		if ( showdeletecolumn == null || showdeletecolumn != 'no' ) {
			//showdeletecolumn = 'yes';
			rtCol = 1;
		}
		if ( showidcolumn != null || showidcolumn == 'yes' ) {
			ltCol++;
			console.log(showidcolumn);
		}
		if ( showrownum != null || showrownum == 'yes' ) {
			ltCol++;
			console.log(showrownum);
		}
		var issetdrilldown = 0;
		colsls.forEach(function(col) {
			if ( col["input_type"] === "drilldown" ) {
				issetdrilldown = 1;
				//fixedColumns does not work well with drilldown tables
				ltCol = rtCol = 0;
			}
		});

		// On page load: datatable
		var maintable = $('#table_records').DataTable({
			"bStateSave": true, // Save the state of the page at reload
			"scrollX": true, // Horizontal Scroll in window
			"scrollY": "80vh", // Vertical Height (72) in window
			"scrollCollapse": true, // Allows thead row to stay at top while scrolling
			"orderCellsTop": true, // Only allow sorting from top thead row
			"colReorder": {fixedColumnsRight: 1}, // Drap N Drop Columns
			"fixedColumns": {leftColumns: ltCol, rightColumns: rtCol}, // has problems with drilldown tables
			"dom": 'rt<"bottom"pil><"clear">',
			"ajax": {
				"url": 'data.php?job=get_records',
				"cache": false,
				"data": {'page': page, 'dt_table': 'maintable'},
				"dataType": 'json',
				"contentType": 'application/json; charset=utf-8',
				"type": 'get'
			},
			"createdRow": function( row, data ) {
				if ( rowfmt !== null ) {
					rwfmt = rw_fmt( lists, rowfmt );
					let i = 0;
					Object.keys(rwfmt).forEach(function(rowcol) {
						let rwln = rwfmt[rowcol];
						let colln = Object.keys(rwfmt)[i];
						Object.keys(rwfmt[rowcol]).forEach(function(rfm) {
							if ( data[colln] == rfm ) { $(row).addClass('color' + rwln[rfm] ); }
						});
						i++;
					});
				}
			},
			"columns": json_dtcolumns( colsls, showrownum, showdeletecolumn ),
			"order": function( ) { if ( showrownum == "yes" ) { return "[[ 1, 'asc' ]]"; } },
			"aoColumnDefs": [ { "bSortable": false, "aTargets": [-1] } ],
			"lengthMenu": [[50, -1], [50, "All"]],
			"oLanguage": {
				"oPaginate": { "sFirst": " ", "sPrevious": " ", "sNext": " ", "sLast": " ", },
				"sLengthMenu": "Records per page: _MENU_",
				"sInfo": "Displaying _START_ to _END_ / _TOTAL_ Total",
				"sInfoFiltered": "(filtered from _MAX_ total records)"
			}
		});
		yadcf.init(maintable, filter_columns( colsls, showrownum ),
			{ filters_tr_index: 1, cumulative_filtering: true }
		);
		if ( showrownum == "yes" ) {
			maintable.on( 'order.dt search.dt', function() {
				maintable.column(0, {search:'applied', order:'applied'}).nodes().each( function(cell, i) {
					cell.innerHTML = i+1;
					maintable.cell(cell).invalidate('dom');
				});
			}).draw();
		}

		// On page load: form validation
		jQuery.validator.setDefaults({
			success: 'valid',
			rules: {
				fiscal_year: { required: true, min: 2000, max: 2025 }
			},
			errorPlacement: function(error, element) {
				error.insertBefore(element);
			},
			highlight: function(element) {
				$(element).parent('.field_container').removeClass('valid').addClass('error');
			},
			unhighlight: function(element) {
				$(element).parent('.field_container').addClass('valid').removeClass('error');
			}
		});

		// Lightbox background
		$(document).on('click', '.lightbox_bg', function() {
			hide_lightbox();
		});
		// Lightbox close button
		$(document).on('click', '.lightbox_close', function() {
			hide_lightbox();
		});
		// Escape keyboard key
		$(document).keyup(function(e) {
			if (e.keyCode == 27) {
				hide_lightbox();
			}
		});
		// Reset Column Order
		$('#reset').click(function(e) {
			e.preventDefault();
			maintable.colReorder.reset();
		});
		$('#filter').click(function() {
			// create a list of filter able columns
			var filtercols = create_filtercols ( colsls );
			$('#filter_container').animate({height: 'toggle'});
			// calling page_lists here
			var request = getdata_ajax( 'page_lists', {'page': page} );
			request.done(function(output) {
				if (output.result == 'success' && output.message == 'page_lists') {
					lists = output.lists;
					//var filtertds = filter_form ( filtercols, lists, 'noneed' );
					$("#filter_container").html( '<div class=\"input_container\">' + filter_form ( filtercols, lists, 'noneed' ) + '</div>');
				}
			});
		});

		// Add Record button & submit form
		addeditdel_record( 'add' );

		// Edit Record button & submit form
		addeditdel_record( 'edit' );

		// Delete Record
		addeditdel_record( 'delete' );

		// Attach Record
		addeditdel_record( 'attach' );

		//For each child table
		if ( issetdrilldown === 1 ) {
			drilldowntable( maintable );
		}

		// Moment JS Date format sorting
		$.fn.dataTable.moment( 'MM/DD/YYYY' );
		$.fn.dataTable.moment( 'MM-DD-YYYY' );
	});
});
