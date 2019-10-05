$(document).ready(function(){
  'use strict';

  // get all the url GET parameters and values
  let searchParams = new URLSearchParams(window.location.search);
  let page = searchParams.get('page');
  let app = searchParams.get('app');

  var pginfo, colsls, lists, rowfmt;
  var pagetitle, table, showidcolumn, showrownum, showdeletecolumn, colorderby, rowlimit;
  var jsondtcolumns, jsonfiltercolumns, rwfmt;

  var request   = $.ajax({
    url:          'data.php?job=page_lists',
    cache:        false,
    data:         {'page': page ,'app': app},
    dataType:     'json',
    contentType:  'application/json; charset=utf-8',
    type:         'get'
  });
  request.done(function(output){
    if (output.result == 'success' && output.message == 'page_lists') {
      // assign individual variables to their values
      for (let key in output.pginfo) {
        var varname = key + ' = \"' + output.pginfo[key] + '\"';
        eval(varname);
      }
      colsls = output.colsls;
      lists = output.lists;
      rowfmt = output.rowfmt;
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
      "url":          'data.php?job=get_records',
      "cache":        true,
      "data":         {'app': app, 'page': page},
      "dataType":     'json',
      "contentType":  'application/json; charset=utf-8',
      "type":         'get'
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
    "order": function ( ) { if ( showrownum == "yes" ) { return "[[ 1, 'asc' ]]"; } },
    "aoColumnDefs": [
      { "bSortable": false, "aTargets": [-1] }
    ],
    "lengthMenu": [[15, 50, 100, -1], [15, 50, 100, "All"]],
    "oLanguage": { 
      "oPaginate": { "sFirst": " ", "sPrevious": " ", "sNext": " ", "sLast": " ", },
      "sLengthMenu":    "Records per page: _MENU_",
      "sInfo":          "Displaying _START_ to _END_ / _TOTAL_ Total",
      "sInfoFiltered":  "(filtered from _MAX_ total records)"
    }
  });
  yadcf.init(maintable, filter_columns( colsls, showrownum ),
    { filters_tr_index: 1, cumulative_filtering: true }
  );
  if ( showrownum == "yes" ) {
    maintable.on( 'order.dt search.dt', function () {
      maintable.column(0, {search:'applied', order:'applied'}).nodes().each( function (cell, i) {
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
    errorPlacement: function(error, element){
      error.insertBefore(element);
    },
    highlight: function(element){
      $(element).parent('.field_container').removeClass('valid').addClass('error');
    },
    unhighlight: function(element){
      $(element).parent('.field_container').addClass('valid').removeClass('error');
    }
  });
  var recordform = $('#form_record');
  recordform.validate();

  // Lightbox background
  $(document).on('click', '.lightbox_bg', function(){
    hide_lightbox();
  });
  // Lightbox close button
  $(document).on('click', '.lightbox_close', function(){
    hide_lightbox();
  });
  // Escape keyboard key
  $(document).keyup(function(e){
    if (e.keyCode == 27){
      hide_lightbox();
    }
  });
  // Reset Column Order
  $('#reset').click(function(e){
    e.preventDefault();
    maintable.colReorder.reset();
  });

  // Add Record button
  $(document).on('click', '#add_record', function(e){
    e.preventDefault();
    $('.lightbox_content h2').text('Add Record');
    $('#form_record button').text('Add Record');
    $('#form_record').attr('class', 'form add');
    $('#form_record').attr('data-id', '');
    $('#form_record .field_container label.error').hide();
    $('#form_record .field_container').removeClass('valid').removeClass('error');
    // iterate through columns and generate jquery to handle adds
    colsls.forEach(function(col) {
      eval( form_inputs(col, 'add') );
    });
    show_lightbox();
  });
  // Add Record submit form
  $(document).on('submit', '#form_record.add', function(e){
    e.preventDefault();
    // Validate form
    if (recordform.valid() == true){
      // Send Record information to database
      hide_ipad_keyboard();
      hide_lightbox();
      show_loading_message();
      var form_data = $('#form_record').serialize();
      form_data = cleanserial_mulsel( form_data, colsls );
      var request   = $.ajax({
        url:          'data.php?job=add_record',
	cache:        false,
	data:         form_data + '&app=' + app + '&page=' + page,
	dataType:     'json',
	contentType:  'application/json; charset=utf-8',
	type:         'get'
      });
      request.done(function(output){
	if (output.result == 'success'){
	  // Reload DataTable
	  maintable.ajax.reload(function(){
	    hide_loading_message();
	    var record_name = $('#blank').val();
	    show_message("Record '" + record_name + "' added successfully.", 'success');
	  }, true);
	} else {
	  hide_loading_message();
	  show_message('Add request failed', 'error');
	}
      });
      request.fail(function(jqXHR, textStatus){
	hide_loading_message();
	show_message('Add request failed: ' + textStatus, 'error');
      });
    }
  });

  // Edit Record button
  $(document).on('click', '.function_edit a', function(e){
    e.preventDefault();
    // Get Record information from database
    show_loading_message();
    var id      = $(this).data('id');
    var request = $.ajax({
      url:          'data.php?job=get_record',
      cache:        false,
      data:         {'id': id, 'app': app, 'page': page},
      dataType:     'json',
      contentType:  'application/json; charset=utf-8',
      type:         'get'
    });
    request.done(function(output){
      if (output.result == 'success'){
        $('.lightbox_content h2').text('Edit Record');
        $('#form_record button').text('Update Record');
        $('#form_record').attr('class', 'form edit');
        $('#form_record').attr('data-id', id);
        $('#form_record .field_container label.error').hide();
        $('#form_record .field_container').removeClass('valid').removeClass('error');
        // iterate through columns and generate jquery to handle edits
        colsls.forEach(function(col) {
          eval( form_inputs(col, 'edit') );
        });
        hide_loading_message();
        show_lightbox();
      } else {
        hide_loading_message();
        show_message('Information request failed', 'error');
      }
    });
    request.fail(function(jqXHR, textStatus){
      hide_loading_message();
      show_message('Information request failed: ' + textStatus, 'error');
    });
  });
  // Edit Record submit form
  $(document).on('submit', '#form_record.edit', function(e){
    e.preventDefault();
    // Validate form
    if (recordform.valid() == true){
      // Send Record information to database
      hide_ipad_keyboard();
      hide_lightbox();
      show_loading_message();
      var id        = $('#form_record').attr('data-id');
      var form_data = $('#form_record').serialize();
      form_data = cleanserial_mulsel( form_data, colsls );
      var request   = $.ajax({
	url:          'data.php?job=edit_record',
        cache:        false,
        data:         form_data + '&id=' + id + '&app=' + app + '&page=' + page,
        dataType:     'json',
        contentType:  'application/json; charset=utf-8',
        type:         'get'
      });
      request.done(function(output){
        if (output.result == 'success'){
          // Reload datable
          maintable.ajax.reload(function(){
            hide_loading_message();
            var record_name = $('#blank').val();
            show_message("Record '" + record_name + "' edited successfully.", 'success');
          }, true);
        } else {
          hide_loading_message();
          show_message('Edit request failed', 'error');
        }
      });
      request.fail(function(jqXHR, textStatus){
        hide_loading_message();
        show_message('Edit request failed: ' + textStatus, 'error');
      });
    }
  });
  
  // Delete Record
  $(document).on('click', '.function_delete a', function(e){
    e.preventDefault();
    var record_name = $(this).data('name');
    if (confirm("Are you sure you want to delete '" + record_name + "'?")){
      show_loading_message();
      var id      = $(this).data('id');
      var request = $.ajax({
	url:          'data.php?job=delete_record',
        cache:        false,
        data:         {'id': id, 'page': page ,'app': app},
        dataType:     'json',
        contentType:  'application/json; charset=utf-8',
        type:         'get'
      });
      request.done(function(output){
        if (output.result == 'success'){
          // Reload datable
          maintable.ajax.reload(function(){
            hide_loading_message();
            show_message("Record '" + record_name + "' deleted successfully.", 'success');
          }, true);
        } else {
          hide_loading_message();
          show_message('Delete request failed', 'error');
        }
      });
      request.fail(function(jqXHR, textStatus){
        hide_loading_message();
        show_message('Delete request failed: ' + textStatus, 'error');
      });
    }
  });
    
//For each child table
for ( var i = 0; i < colsls.length; i++ ) {
  if ( colsls[i]["input_type"] === "drilldown" ) {
    var issetdrilldown = 1;
    //console.log(colsls[i]["input_type"]);
  }
}
if ( issetdrilldown === 1 ) {
  colsls.forEach(function(col) {
    if ( col["input_type"] == "drilldown" ) {
//findme
    }
  });
}
<?php
if ( $issetdrilldown === 1 ) {

function loadchildpage ( $childpage ) {
	require_once "pages/$childpage.php";
	return [ $pagetitle, $table, $showidcolumn, $showrownum, $colorderby, $selslist, $lists, $colslist, $rowformat ];
}

foreach ( $colslist as $i => $col ) {
	if ( $col["input_type"] == "drilldown" ) {
		$childvars = loadchildpage( $col["column"] );
		//print_r($childvars);
		// statically linking variables to child prefix + variable name
		$childpagetitle = $childvars[0];
		$childtable = $childvars[1];
		$childshowidcolumn = $childvars[2];
		$childshowrownum = $childvars[3];
		$childcolorderby = $childvars[4];
		$childselslist = $childvars[5];
		$childlists = $childvars[6];
		$childcolslist = $childvars[7];
		$childrowformat = $childvars[8];
		//print_r($childvars);
		$child_header = "var ".$col["column"]."_header = `<table class=\"datatable\" id=\"".$col["column"]."_##ID##\">\n\t<thead><tr>\n";
		$child_columns = "var ".$col["column"]."_columns = `[\n"; 
		foreach ( $childcolslist as $i => $childcol ) {
			if ( $childcol["colwidth"] == "yes" ) {
				$sClassstring = ', "sClass": "truncate"';
			} elseif ( $childcol["input_type"] == "currency" ) {
				$sClassstring = ', "sClass": "integer"';
			} elseif ( $childcol["hidecol"] == "yes" ) {
				$sClassstring =', "visible": false';
			}
			if ( $childcol["input_type"] != "drilldown" && $childcol["input_type"] != "crosswalk" ) {
				$child_header .= "\t\t<th>".$childcol["title"]."</th>\n";
				$child_columns .= "    { \"data\": \"".$childcol["column"]."\"$sClassstring },\n";
			}
			unset($sClassstring);
		}
 		$child_header .= "\t\t<th>Functions</th>\n\t</tr></thead>\n</table>`;\n";
		$child_columns .= "    { \"data\": \"functions\", \"sClass\": \"functions\", \"orderable\": false }\n]`;\n";
		echo $child_header;
		echo $child_columns;
	}
}
?>
  function format_header ( varheader, table_id ) {
  	return varheader.replace("##ID##", table_id);
  }
  var tablecount=1;

  // Show Drill Down table
  $(document).on('click', '.function_drilldown a', function(e){
    e.preventDefault();
    // Get Child Records linked to id
    var id	= $(this).data('id');
    var subpage	= $(this).data('name');
    
    var tr = $(this).closest('tr');
    var row = maintable.row( tr );

    if ( row.child.isShown() ) {
      // This row is already open - close it
      row.child.hide();
      tr.removeClass('collapse');
    } else {
      // Open this row
      var childheader = eval( subpage + '_header');
      var childcolumns = eval( subpage + '_columns');
      row.child( format_header( childheader, tablecount ) ).show();
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
         "url":          'data.php?job=get_records',
         "cache":        true,
         "data":         {'id': id ,'subpage': subpage, 'app': app, 'page': page},
         "dataType":     'json',
         "contentType":  'application/json; charset=utf-8',
	 "type":         'get'
      },
      "columns":  eval( childcolumns ) 
    });
    tablecount++;
  });
<?php
}
?>
  });
});

