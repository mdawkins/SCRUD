function addedit_form ( columnslist, lists ) {
  var formhtml = "<h2>##blank##</h2>\n";
  formhtml += "<form class=\"form add\" id=\"form_record\" data-id=\"\" novalidate>"\n;
  columnslist.forEach(function(col) {
    if ( col["input_type"] != "noform" && col["input_type"] != "drilldown" col["input_type"] != "crosswalk" ) {
      if ( col["required"] == "yes" ) {
        var errspan = "<span class='required'>*</span>"; 
        var errinput = "required";
      } else {
        var errspan = "";
        var errinput = "";
      }
      formhtml += "<div class=\"input_container\">\n";
      formhtml += "\t<label for=\"" + col["column"] + "\">" + col["title"] + ": $errspan</label>\n";
      formhtml += "\t<div class=\"field_container\">\n";
      // input types
      if ( col["input_type"] == "textarea" ) {
      formhtml += "\t\t<textarea class=\"text textarea\" name=\"" + col["column"] + "\" id=\"" + col["column"] + "></textarea>\n";
      } else if ( col["input_type"] == "select" || col["input_type"] == "tableselect" ) {
        if ( col["mulitple"] == "yes" ) { var multiple = "multiple";
        } else var multiple = "";
        formhtml += "\t\t<select class=\"text\" name=\"" + col["column"] + "\" id=\"" + col["column"] + multiple + ">\n";
        formhtml += "\t\t\t<option value=\"\">Select a " + col["column"] + "</option>\n";
        var multisels = eval(col["column"].split(";"); //explode(";", ${$col["column"]});
        lists.forEach(function(list) {
          if ( multisels.indexOf(list["key"]) !== false ) {
	    var selected = "selected";
          } else var selected = "";
	  if ( list["key"] != "selectparent" ) {
            formhtml += "\t\t\t<option value=\"" + list["key"] + " " + selected + "\">" + list["title"] + "</option>\n";
	  } else var selectnested = true; // this is used for cascading selects. needs to be set to false somewhere...
        }
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
