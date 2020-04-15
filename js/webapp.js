$(document).ready(function() {
	'use strict';

	// get all the url GET parameters and values
	let searchParams = new URLSearchParams(window.location.search);
	let page = searchParams.get('page');

	// set variables needed for maintable
	var pginfo, colsls, rowfmt;
	var lists;
	var pagetitle, table, showidcolumn, showrownum, showdeletecolumn, colorderby, rowlimit, waittoload;
	var jsondtcolumns, jsonfiltercolumns, rwfmt;
	var wheres = '';

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

		// Serverside Filter Menu
		filter_menu(  page, 'table_records', colsls, rowfmt, showidcolumn, showrownum, showdeletecolumn );

		// Set page title
		document.title = pagetitle;

		// load maintable function
		if ( waittoload == "yes" ) {
			// set where 0
			wheres = '0::0';
		}
		load_maintable( page, 'table_records', colsls, rowfmt, showidcolumn, showrownum, showdeletecolumn, wheres );
		if ( waittoload == "yes" ) {
			// open filter menu
			$('#filter_menu').trigger('click');
		}

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

		// Add Record button & submit form
		addeditdel_record( 'add' );

		// Edit Record button & submit form
		addeditdel_record( 'edit' );

		// Delete Record
		addeditdel_record( 'delete' );

		// Attach Record
		addeditdel_record( 'attach' );
	});
});
