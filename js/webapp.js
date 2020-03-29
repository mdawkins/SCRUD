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
				var varname = key + ' = \"' + output.pginfo[key] + '\"';
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

		// On page load: datatable
		var maintable = $('#table_records').DataTable({
			"bStateSave": true, // Save the state of the page at reload
			"scrollX": true, // Horizontal Scroll in window
			"scrollY": "72vh", // Vertical Height (72) in window
			"scrollCollapse": true, // Allows thead row to stay at top while scrolling
			"orderCellsTop": true, // Only allow sorting from top thead row
			"colReorder": {fixedColumnsRight: 1}, // Drap N Drop Columns
			//"fixedColumns": {leftColumns: 0, rightColumns: 1}, // Fix Column in place ie Freeze View
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
			"lengthMenu": [[15, 50, 100, -1], [15, 50, 100, "All"]],
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

		// Add Record button & submit form
		addeditdel_record( 'add' );

		// Edit Record button & submit form
		addeditdel_record( 'edit' );

		// Delete Record
		addeditdel_record( 'delete' );

		// Attach Record
		addeditdel_record( 'attach' );

		//For each child table
		for ( var i = 0; i < colsls.length; i++ ) {
			if ( colsls[i]["input_type"] === "drilldown" ) {
				var issetdrilldown = 1;
				//console.log(colsls[i]["input_type"]);
			}
		}
		if ( issetdrilldown === 1 ) {
			// Show Drill Down table
			var tablecount=1;
			$(document).on('click', '.function_drilldown a', function(e) {
				e.preventDefault();
				// set variables needed for childtable
				//var pginfo, ch_colsls, ch_lists, ch_rowfmt;
				var pginfo, ch_colsls;
				var pagetitle, table, showidcolumn, showrownum, showdeletecolumn, colorderby, rowlimit;
				var jsondtcolumns, jsonfiltercolumns, rwfmt;

				// Get Child Records linked to id
				let id = $(this).data('id');
				let subpage = $(this).data('name');
				var request = getdata_ajax( 'page_info', {'page': subpage} );

				var tr = $(this).closest('tr');
				var row = maintable.row( tr );
				console.log( tr );
				console.log( row );

				request.done(function(output) {
					if (output.result == 'success' && output.message == 'page_info') {
						// assign individual variables to their values
						for (let key in output.pginfo) {
							var varname = key + ' = \"' + output.pginfo[key] + '\"';
							eval(varname);
						}
						ch_colsls = output.colsls;
						//ch_lists = output.lists;
						//ch_rowfmt = output.rowfmt;
					}

					if ( row.child.isShown() ) {
						// This row is already open - close it
						row.child.hide();
						tr.removeClass('collapse');
					} else {
						// Open this row
						console.log( "here" + ' : ' + subpage + '_##ID##' + ' : ' + id + ' : ' + tablecount + ' : ' + showdeletecolumn);
						console.log( ch_colsls );
						console.log( format_header_id( dt_header( ch_colsls, subpage + '_##ID##', '', showdeletecolumn, id, subpage ), tablecount ) ); 
						row.child( format_header_id( dt_header( ch_colsls, subpage + '_##ID##', '', showdeletecolumn, id, subpage ), tablecount ) ).show();
						tr.addClass('collapse');
					}
					//show_loading_message();
					var childtable = $('#' + subpage + '_' + tablecount).DataTable({
						"bPaginate": false,
						"bSortable": false,
						"searching": false,
						"paging": false,
						"info": false,
						"ajax": {
							"url": 'data.php?job=get_records',
							"cache": true,
							"data": {'id': id ,'page': subpage, 'dt_table': subpage},
							"dataType": 'json',
							"contentType": 'application/json; charset=utf-8',
							"type": 'get'
							},
						"columns": json_dtcolumns( ch_colsls, 'no', showdeletecolumn ),
						"aoColumnDefs": [ { "bSortable": false, "aTargets": [-1] } ],
					});
				});
				tablecount++;
			});
		}
		// end of issetdrilldown
	});
});
