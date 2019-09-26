$(document).ready(function(){
  'use strict';

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
    "ajax": "data.php?job=get_records<?php echo $addgetvar; ?>",
<?php
if ( isset($rowformat) ) {
	$i = 0;
	echo "    \"createdRow\": function ( row, data ) {\n";
	foreach ( $rowformat as $rfm ) {
		if ( $i > 0 ) { echo "\telse"; }
		$rfmvalue = $rfm["value"];
		if ( $colslist[array_search($rfm["column"], array_column($colslist, "column"))]["input_type"] == "select" ) { // Not sure if tableselect should be included too
			$rfmvalue = $lists[$rfm["column"]][ array_search( $rfm["value"], array_column( $lists[$rfm["column"]], "key" ) ) ]["title"];
		}
		echo " if ( data.".$rfm["column"]." == '$rfmvalue' ) { $(row).addClass('color".$rfm["value"]."'); }";
		$i++;
	}
	echo "    },\n";
}
?>
    "columns": [
<?php
if ( $showrownum == "yes" ) { echo "\t{ \"data\": \"rownum\", \"sClass\": \"rownum\", \"orderable\": false },\n"; }

foreach ( $colslist as $i => $col ) {
	if ( $col["colwidth"] == "yes" ) {
		$sClassstring = ', "sClass": "truncate"';
	} elseif ( $col["input_type"] == "currency" ) {
		$sClassstring = ', "sClass": "integer"';
	} elseif ( $col["hidecol"] == "yes" ) {
		$sClassstring =', "visible": false';
	} elseif (  $col["input_type"] == "drilldown" ) {
		$sClassstring = ', "sClass": "functions", "orderable": false';
		$issetdrilldown = 1;
	}
	if ( $col["input_type"] != "crosswalk" ) {
		echo "\t{ \"data\": \"".$col["column"]."\"$sClassstring },\n";
	}
	unset($sClassstring);
}
?>
      { "data": "functions",      "sClass": "functions" }
    ],
<?php
if ( $showrownum == "yes" ) { echo "    \"order\": [[ 1, 'asc' ]],\n"; }
?>
    "aoColumnDefs": [
      { "bSortable": false, "aTargets": [-1] }
    ],
    "lengthMenu": [[15, 50, 100, -1], [15, 50, 100, "All"]],
    "oLanguage": {
      "oPaginate": {
        "sFirst":       " ",
        "sPrevious":    " ",
        "sNext":        " ",
        "sLast":        " ",
      },
      "sLengthMenu":    "Records per page: _MENU_",
      "sInfo":          "Displaying _START_ to _END_ / _TOTAL_ Total",
      "sInfoFiltered":  "(filtered from _MAX_ total records)"
    }

  });

  yadcf.init(maintable, [
<?php
$f = $linepresent = 0;
$rowcnt = count($colslist);
if ( $showrownum == "yes" ) { $rowcount++; $f++; }
foreach ( $colslist as $i => $col ) {
	if ( $linepresent == 1 && !empty($col["filterbox"]) ) {
		echo ",\n";
	}
	if ( $col["filterbox"] == "checkbox" ) {
		echo "    { column_number: $f, filter_type: \"multi_select\", select_type: \"select2\" }";
		$linepresent = 1;
	} elseif ( $col["filterbox"] == "text" ) {
		echo "    { column_number: $f, filter_type: \"text\" }";
		//		echo "    { column_number: $f, filter_type: \"auto_complete\", select_type_options: {width: '200px'} }";
		$linepresent = 1;
		//	} elseif ( !empty($col["filterbox"]) && $col["input_type"] == "checkbox" ) {
		//		echo "    { column_number: $f, data: ['Yes', 'No'], filter_default_label: 'Select Yes/No', select_type_options: {width: '200px'} }";
		//		$linepresent = 1;
	} elseif ( !empty($col["filterbox"]) && $col["input_type"] == "date" ) {
		echo "    { column_number: $f, filter_type: \"range_date\", date_format: \"mm/dd/yyyy\", filter_delay: 500 }";
		$linepresent = 1;
	}
	$f++;
}
?>
   ],
   { filters_tr_index: 1, cumulative_filtering: true }
  );

<?php if ( $showrownum == "yes" ) { ?>
  maintable.on( 'order.dt search.dt', function () {
    maintable.column(0, {search:'applied', order:'applied'}).nodes().each( function (cell, i) {
      cell.innerHTML = i+1;
      maintable.cell(cell).invalidate('dom');
    });
  }).draw();
<?php } ?>

  // On page load: form validation
  jQuery.validator.setDefaults({
    success: 'valid',
    rules: {
      fiscal_year: {
	required: true,
	min:      2000,
	max:      2025
      }
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

  // Show message
  function show_message(message_text, message_type){
    $('#message').html('<p>' + message_text + '</p>').attr('class', message_type);
    $('#message_container').show();
    if (typeof timeout_message !== 'undefined'){
      window.clearTimeout(timeout_message);
    }
    timeout_message = setTimeout(function(){
      hide_message();
    }, 8000);
  }
  // Hide message
  function hide_message(){
    $('#message').html('').attr('class', '');
    $('#message_container').hide();
  }

  // Show loading message
  function show_loading_message(){
    $('#loading_container').show();
  }
  // Hide loading message
  function hide_loading_message(){
    $('#loading_container').hide();
  }

  // Show lightbox
  function show_lightbox(){
    $('.lightbox_bg').show();
    $('.lightbox_container').show();
  }
  // Hide lightbox
  function hide_lightbox(){
    $('.lightbox_bg').hide();
    $('.lightbox_container').hide();
  }
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

  // Hide iPad keyboard
  function hide_ipad_keyboard(){
    document.activeElement.blur();
    $('input').blur();
  }

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
<?php
foreach ( $colslist as $i => $col ) {
	if ( $col["multiple"] == "yes" ) {
		// an array needs to be handled here
		echo "\t$('#form_record #".$col["column"]."').val() || [];\n";
	} elseif ( $col["input_type"] != "drilldown" && $col["input_type"] != "crosswalk" ) {
		echo "\t$('#form_record #".$col["column"]."').val('');\n";
	}
}
?>
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
<?php
foreach ( $colslist as $i => $col ) {
	if ( $col["multiple"] == "yes" ) {
		// the first replace changes the first match to a placeholder, the second replace matches all the rest, and the third changes back the placeholder to the original value
		echo "form_data = form_data.replace('&".$col["column"]."=', '##::##').replace(/&".$col["column"]."=/g, ';').replace(/##::##/g, '&".$col["column"]."=');\n";
	}
}
?>
      var request   = $.ajax({
      url:          'data.php?job=add_record<?php echo $addgetvar; ?>',
	cache:        false,
	data:         form_data,
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

<?php
if ( $issetdrilldown === 1 ) {
?>
  //For each child table
<?php

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
         "url":          'data.php?job=get_records<?php echo $addgetvar; ?>',
         "cache":        true,
         "data":         {'id': id ,'subpage': subpage},
         "dataType":     'json',
         "contentType":  'application/json; charset=utf-8',
	 "type":         'get'
      },
      "columns":  eval(  childcolumns  ) 
    });
    tablecount++;
  });
<?php
}
?>
  // Edit Record button
  $(document).on('click', '.function_edit a', function(e){
    e.preventDefault();
    // Get Record information from database
    show_loading_message();
    var id      = $(this).data('id');
    var request = $.ajax({
      url:          'data.php?job=get_record<?php echo $addgetvar; ?>',
      cache:        false,
      data:         'id=' + id,
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
<?php
foreach ( $colslist as $i => $col ) {
	if ( $col["multiple"] == "yes" ) {
		// an array needs to be handled here -- string.split(separator)
		echo "\t$('#form_record #".$col["column"]."').val(output.data[0].".$col["column"].".split(';')) || [];\n";
	} elseif ( $col["input_type"] == "checkbox" ) {
		echo "\t$('#form_record #".$col["column"]."').prop('checked', ( output.data[0].".$col["column"]." == 1 ) );\n";
	} elseif ( $col["input_type"] != "drilldown" && $col["input_type"] != "crosswalk" ) {
		echo "\t$('#form_record #".$col["column"]."').val(output.data[0].".$col["column"].");\n";
	}
}
?>
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
<?php
foreach ( $colslist as $i => $col ) {
	if ( $col["multiple"] == "yes" ) {
		echo "form_data = form_data.replace('&".$col["column"]."=', '##::##').replace(/&".$col["column"]."=/g, ';').replace(/##::##/g, '&".$col["column"]."=');\n";
	}
}
?>
      var request   = $.ajax({
	url:          'data.php?job=edit_record<?php echo $addgetvar; ?>&id=' + id,
        cache:        false,
        data:         form_data,
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
	url:          'data.php?job=delete_record<?php echo $addgetvar; ?>&id=' + id,
        cache:        false,
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

});

