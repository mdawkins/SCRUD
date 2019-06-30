$(document).ready(function(){
  
  // On page load: datatable
  var maintable = $('#table_records').DataTable({
    "scrollX": true,
    "scrollY": "72vh",
    "scrollCollapse": true,
    "dom": '<"header"<"toolbar">f>rt<"bottom"pil><"clear">',
    "ajax": "data.php?job=get_records<?php echo $addgetvar; ?>",
    "columns": [
<?php
foreach ( $colslist as $i => $col ) {
	if ( $col["colwidth"] == "yes" ) {
		$sClassstring = ', "sClass": "truncate"';
	} elseif ( $col["input_type"] == "currency" ) {
		$sClassstring = ', "sClass": "integer"';
	} elseif ( $col["hidecol"] == "yes" ) {
		$sClassstring =', "visible": false,';
	}
	echo "\t{ \"data\": \"".$col["column"]."\"$sClassstring },\n";
	unset($sClassstring);
}
?>
      { "data": "functions",      "sClass": "functions" }
    ],
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
  $("div.toolbar").html('<button type="button" class="button" id="add_record">Add Record</button>'); 

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
		echo "\t$('#form_record #".$col["column"]."').val();\n";
	} else
		echo "\t$('#form_record #".$col["column"]."').val('');\n";
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
          // Reload datable
          maintable.api().ajax.reload(function(){
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
		// an array needs to be handled here
		echo "\t$('#form_record #".$col["column"]."').val(output.data[0].".$col["column"].");\n";
	} elseif ( $col["input_type"] == "checkbox" ) {
		echo "\t$('#form_record #".$col["column"]."').prop('checked', ( output.data[0].".$col["column"]." == 1 ) );\n";
	} else
		echo "\t$('#form_record #".$col["column"]."').val(output.data[0].".$col["column"].");\n";
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
          maintable.api().ajax.reload(function(){
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
	url:          'data.php?job=delete_record&<?php echo $addgetvar; ?>id=' + id,
        cache:        false,
        dataType:     'json',
        contentType:  'application/json; charset=utf-8',
        type:         'get'
      });
      request.done(function(output){
        if (output.result == 'success'){
          // Reload datable
          maintable.api().ajax.reload(function(){
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
