$(document).ready(function(){
  'use strict';

  // get all the url GET parameters and values
  let searchParams = new URLSearchParams(window.location.search);
  let page = searchParams.get('page');
  let app = searchParams.get('app');

  // set variables needed for maintable
  var pginfo, colsls, lists, rowfmt;
  var pagetitle, table, showidcolumn, showrownum, showdeletecolumn, colorderby, rowlimit;
  var jsondtcolumns, jsonfiltercolumns, rwfmt;

  var request = getdata_ajax( 'page_lists', {'page': page ,'app': app} );
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
    // Populate maintable header
    $("#table_records").html(dt_header( colsls, lists, 'maintable', showrownum , showdeletecolumn ));

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
      "cache":        false,
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
  addrecord_button ( colsls, lists, app, page );
  // Add Record submit form
  record_submit ( 'add', app, page, maintable, colsls );

  // Edit Record button
  editrecord_button ( app, page );
  // Edit Record submit form
  record_submit ( 'edit', app, page, maintable, colsls );
  
  // Delete Record
  deleterecord_button ( app, page, maintable );
    
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
      // set variables needed for childtable
      var pginfo, ch_colsls, ch_lists, ch_rowfmt;
      var pagetitle, table, showidcolumn, showrownum, showdeletecolumn, colorderby, rowlimit;
      var jsondtcolumns, jsonfiltercolumns, rwfmt;

      let subpage = col["column"];
      var request = getdata_ajax( 'page_lists', {'page': subpage ,'app': app} );
      request.done(function(output){
        if (output.result == 'success' && output.message == 'page_lists') {
          // assign individual variables to their values
          for (let key in output.pginfo) {
            var varname = key + ' = \"' + output.pginfo[key] + '\"';
            eval(varname);
          }
          ch_colsls = output.colsls;
          ch_lists = output.lists;
          ch_rowfmt = output.rowfmt;
        }
	var varheader = [];
	varheader[subpage] = dt_header( ch_colsls, ch_lists, subpage + '_##ID##', '', showdeletecolumn );
        var tablecount=1;

        // Show Drill Down table
        $(document).on('click', '.function_drilldown a', function(e){
          e.preventDefault();
          // Get Child Records linked to id
          var id	= $(this).data('id');
          //var subpage	= $(this).data('name'); // duplicate 
    
          var tr = $(this).closest('tr');
          var row = maintable.row( tr );

          if ( row.child.isShown() ) {
            // This row is already open - close it
            row.child.hide();
            tr.removeClass('collapse');
          } else {
            // Open this row
            var childheader =  varheader[subpage];
            //var childcolumns = eval( subpage + '_columns');
            row.child( format_header_id ( childheader, tablecount ) ).show();
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
            "columns": json_dtcolumns( ch_colsls, 'no', showdeletecolumn ),
          });
          tablecount++;
        });
      });
    }
  });

}
// end of issetdrilldown

  });
});

