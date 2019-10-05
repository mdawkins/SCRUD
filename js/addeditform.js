function filter_columns ( colslist, showrownum ) {
  var linepresent, colnum = 0;
  var filterline = "[ ";
  if ( showrownum == "yes" ) { colnum++; }
  colslist.forEach(function(col) {
    if ( linepresent == 1 && typeof col["filterbox"] !== "undefined" ) {
      filterline += ",";
    }
    if ( typeof col["filterbox"] !== "undefined" ) {
      var filtercol = '{ "column_number": ' + colnum + ', "filter_type": ';
      if ( col["filterbox"] == "checkbox" ) {
        filtercol += '"multi_select", "select_type": "select2" }';
      } else if ( col["filterbox"] == "text" ) {
        filtercol += '"text" }';
      //  } elseif ( !empty($col["filterbox"]) && $col["input_type"] == "checkbox" ) {
      //    filtercol = "{ column_number: " + ", data: ['Yes', 'No'], filter_default_label: 'Select Yes/No', select_type_options: {width: '200px'} }";
      } else if ( col["filterbox"] !== "" && col["input_type"] == "date" ) {
        filtercol += '"range_date", "date_format": "mm/dd/yyyy", "filter_delay": 500 }';
      } //else filtercol = "";
      linepresent = 1;
      filterline += filtercol;
    }
    colnum++;
  });
  filterline += " ]";
  return JSON.parse(filterline);
}
function form_inputs( col, action ) {
  var forminputs = "$('#form_record #" + col["column"] + "').";
  if ( action == 'edit' ) {
    if ( col["multiple"] == "yes" ) {
      forminputs += "val(output.data[0]." + col["column"] + ".split(';')) || [];\n";
    } else if ( col["input_type"] == "checkbox" ) {
      forminputs += "prop('checked', ( output.data[0]." + col["column"] + " == 1 ) );\n";
    } else if ( col["input_type"] != "drilldown" && col["input_type"] != "crosswalk" ) {
      forminputs += "val(output.data[0]." + col["column"] + ");\n";
    }
  } else if ( action == 'add' ) {
    if ( col["multiple"] == "yes" ) {
      forminputs += "val() || [];\n";
    } else if ( col["input_type"] != "drilldown" && col["input_type"] != "crosswalk" ) {
      forminputs += "val('');\n";
    }
  }
  return forminputs;
}
function cleanserial_mulsel( formdata, colslists) {
  colslists.forEach(function(col) {
    if ( col["multiple"] == "yes" ) {
      var colrplstr = '&' + col["column"] + '='; 
      var re = new RegExp( colrplstr, 'g');
      // the first replace changes the first match to a placeholder, the second replace matches all the rest, and the third changes back the placeholder to the original value
      formdata = formdata.replace(colrplstr, '##::##').replace(re, ';').replace('##::##', colrplstr);
      //console.log( formdata );
    }
  });;
  return formdata;
}
function json_dtcolumns ( colslist, showrownum, showdeletecolumn ) {
  var colsjson = "[\n";
  if ( showrownum == "yes" ) {
    colsjson += "    { \"data\": \"rownum\", \"sClass\": \"rownum\", \"orderable\": false },\n";
  }
  colslist.forEach(function(col) {
    var sClassstring = '';
    if ( col["colwidth"] == "yes" ) {
      sClassstring = ', "sClass": "truncate"';
    } else if ( col["input_type"] == "currency" ) {
      sClassstring = ', "sClass": "integer"';
    } else if ( col["hidecol"] == "yes" ) {
      sClassstring =', "visible": false';
    } else if ( col["input_type"] == "drilldown" ) {
      sClassstring = ', "sClass": "functions", "orderable": false';
      var issetdrilldown = 1;
    }
    if ( col["input_type"] != "crosswalk" ) {
      colsjson += "    { \"data\": \"" + col["column"] + "\"" + sClassstring + " },\n";
    }
    delete sClassstring;
  });
  if ( showdeletecolumn != "no" ) {
    colsjson += "    { \"data\": \"functions\", \"sClass\": \"functions\" }\n";
  }
  colsjson += "  ]\n"; 
  return JSON.parse(colsjson);
  //return colsjson;
}
function arrayColumn(array, columnName) {
  return array.map(function(value,index) {
    return value[columnName];
  })
}
function rw_fmt ( lists, rowfmt ) {
  // first find all rowformat columns types
  let rowtype = '';
  rowfmt.forEach(function(colfmt) {
    if ( rowtype == '' ) {
      rowtype = [ colfmt["column"] ];
    } else if ( rowtype == colfmt["column"] ) {
      return;
    } else if ( rowtype != colfmt["column"] ) {
      rowtype.push( colfmt["column"] );
    }
  });
  // second for each type call lists.rwtype
  let coltype = {};
  rowtype.forEach(function(rwtype) {
    var tmptype = lists[rwtype].reduce(function(map, obj) {
      map[obj.title] = obj.key;
      return map;
    }, {});
      coltype[rwtype] = tmptype;
  });
  return coltype;
}
function addedit_form ( columnslist, lists ) {
  var formhtml = "<h2>##blank##</h2>\n";
  formhtml += "<form class=\"form add\" id=\"form_record\" data-id=\"\" novalidate>\n";
  columnslist.forEach(function(col) {
    if ( col["input_type"] != "noform" && col["input_type"] != "drilldown" && col["input_type"] != "crosswalk" ) {
      if ( col["required"] == "yes" ) {
        var errspan = "<span class='required'>*</span>"; 
        var errinput = "required";
      } else {
        var errspan = "";
        var errinput = "";
      }
      formhtml += "<div class=\"input_container\">\n";
      formhtml += "\t<label for=\"" + col["column"] + "\">" + col["title"] + ": " + errspan + "</label>\n";
      formhtml += "\t<div class=\"field_container\">\n";
      // input types
      if ( col["input_type"] == "textarea" ) {
      formhtml += "\t\t<textarea class=\"text textarea\" name=\"" + col["column"] + "\" id=\"" + col["column"] + "></textarea>\n";
      } else if ( col["input_type"] == "select" || col["input_type"] == "tableselect" ) {
        if ( col["mulitple"] == "yes" ) { var multiple = "multiple";
        } else var multiple = "";
        formhtml += "\t\t<select class=\"text\" name=\"" + col["column"] + "\" id=\"" + col["column"] + multiple + ">\n";
        formhtml += "\t\t\t<option value=\"\">Select a " + col["column"] + "</option>\n";
        var multisels = eval( col["column"].split(";") ); //explode(";", ${$col["column"]});
        lists.forEach(function(list) {
          if ( multisels.indexOf(list["key"]) !== false ) {
	    var selected = "selected";
          } else var selected = "";
	  if ( list["key"] != "selectparent" ) {
            formhtml += "\t\t\t<option value=\"" + list["key"] + " " + selected + "\">" + list["title"] + "</option>\n";
	  } else var selectnested = true; // this is used for cascading selects. needs to be set to false somewhere...
        });
        formhtml += "\t\t</select>\n";
      } else {
        formhtml += "\t\t<input type=\"" + col["input_type"] + "\" class=\"text\" name=\"" + col["column"] + "\" id=\"" + col["column"] + "\" value=\"\" " + errinput> + "\n";
      }
      formhtml += "\t</div>\n</div>\n";
    }
  });
  formhtml += "\t<div class=\"button_container\">\n\t\t<button type=\"submit\">##blank##</button>\n\t</div>\n</form>\n";
  return formhtml;
}
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
// Hide iPad keyboard
function hide_ipad_keyboard(){
  document.activeElement.blur();
  $('input').blur();
}
