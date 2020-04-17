function create_filtercols ( colslist ) {
	var linepresent = 0;
	var filterline = "[ ";
	colslist.forEach(function(col) {
		if ( typeof col["filtersrv"] !== "undefined" && col["filtersrv"] == "yes" ) {
			if ( linepresent == 1 ) {
				// add comma to end of object
				filterline += ",";
			}
			var filtercol = '{ "column": "' + col["column"] + '", "title": "' + col["title"] + '", ';
			if ( col["input_type"] == "tableselect" || col["input_type"] == "tableselect" ) {
				// set input type to select mulitple
				filtercol += '"input_type": "' + col["input_type"] + '", "multiple": "yes" }'; 
			} else if ( col["input_type"] == "date" || col["input_type"] == "datetime" ) {
				// set input type to two date pickers for a range
				filtercol += '"input_type": "' + col["input_type"] + '", "range": "yes" }'; 
			}
			linepresent = 1;
			filterline += filtercol;
		} else {
			// no columns to use for server side filtering
		}
	});
	filterline += " ]";
	return JSON.parse(filterline);
}
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
			//} elseif ( !empty($col["filterbox"]) && $col["input_type"] == "checkbox" ) {
			//		filtercol = "{ column_number: " + ", data: ['Yes', 'No'], filter_default_label: 'Select Yes/No', select_type_options: {width: '200px'} }";
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
function cleanserial_mulsel( formdata, colslists) {
	colslists.forEach(function(col) {
		if ( col["multiple"] == "yes" ) {
			var colrplstr = '&' + col["column"] + '='; 
			var re = new RegExp( colrplstr, 'g');
			// add &amp; to the front of the string to match possible first column
			formdata = '&' + formdata;
			// the first replace changes the first match to a placeholder, the second replace matches all the rest, and the third changes back the placeholder to the original value
			formdata = formdata.replace(colrplstr, '##::##').replace(re, ';').replace('##::##', colrplstr);
			// remove possible empty select "=;"
			formdata = formdata.replace(/=;/g, '=');
			//remove added &amp; at the front
			formdata = formdata.substring(1);
			//console.log( formdata );
		}
	});;
	return formdata;
}
function json_dtcolumns ( colslist, showrownum, showdeletecolumn ) {
	var colsjson = "[\n";
	if ( showrownum == "yes" ) {
		colsjson += "		{ \"data\": \"rownum\", \"sClass\": \"rownum\", \"orderable\": false },\n";
	}
	colslist.forEach(function(col) {
		var sClassstring = '';
		if ( col["colview"] == "ellipsis" || col["colview"] == "fixedwidth-min" ) {
			sClassstring = ', "sClass": "truncate"';
		} else if ( col["colview"] == "fixedwidth" ) {
			sClassstring = ', "sClass": "truncatedbl"';
		} else if ( col["colview"] == "fixedwidth-max" ) {
			sClassstring = ', "sClass": "truncatetpl"';
		} else if ( col["input_type"] == "currency" ) {
				sClassstring = ', "sClass": "integer", "render": function( data ) { var spanopen = spanclose = ""; if ( data < 0 ) { spanopen = "<span class=\'negcurrency\'>"; spanclose = "</span>"; } return spanopen + accounting.formatMoney( data, { format: { pos: "%s%v", neg: "%s(%v)", zero: "--" } } ) + spanclose; }';
		} else if ( col["colview"] == "hide") {
			sClassstring =', "visible": false';
		} else if ( col["input_type"] == "drilldown" ) {
			sClassstring = ', "sClass": "functions", "orderable": false';
			var issetdrilldown = 1;
		}
		if ( col["input_type"] != "crosswalk" ) {
			colsjson += "		{ \"data\": \"" + col["column"] + "\"" + sClassstring + " },\n";
		}
		delete sClassstring;
	});
	if ( showdeletecolumn != "no" ) {
		colsjson += "		{ \"data\": \"functions\", \"sClass\": \"functions\" }\n";
	} else {
		colsjson = colsjson.replace(/,\n$/, "\n");
	}
	colsjson += "  ]\n"; 
	//return JSON.parse(colsjson);
	// have to use eval here instead of JSON.parse bc of js syntax being passed for the function
	return eval(colsjson);
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
function dt_header ( columnslist, tableid, showrownum, showdeletecolumn, id, page ) {
	var showfilter = "no";
	var showfiltermenu = "no";
	var showfooter = "no";
	var headerhtml = "<table class=\"datatable\" id=\"" + tableid + "\">\n<thead>\n\t<tr>\n";
	var filterhtml = "\t<tr>\n";
	var footerhtml = "<tfoot>\n\t<tr>\n";
	if ( showrownum == "yes" && tableid == "maintable" ) {
		headerhtml += "\t\t<th>No.</th>\n";
		filterhtml += "\t\t<th class=\"filter_content\"></th>\n";
		footerhtml += "\t\t<th></th>\n";
	}
	if ( id !== undefined ) {
		var dataid = "data-id=\"" + id + "\"";
	} else { var dataid = ""; }
	if ( page !== undefined ) {
		var dataname = "data-name=\"" + page + "\"";
	} else { var dataname = ""; }
	columnslist.forEach(function(col) {
		if ( col["filterbox"] != "" && col["filterbox"] !== undefined ) { showfilter = "yes"; }
		if ( col["filtersrv"] != "" && col["filtersrv"] !== undefined ) { showfiltermenu = "yes"; }
		if ( col["footer"] != "" && col["footer"] !== undefined ) { showfooter = "yes"; }
		if ( col["input_type"] != "crosswalk" ) {
			headerhtml += "\t\t<th>" + col["title"] + "</th>\n";
			filterhtml += "\t\t<th class=\"filter_content\"></th>\n";
			footerhtml += "\t\t<th>&nbsp;</th>\n";
		}
	});
	if ( showdeletecolumn != "no" ) {
		filterhtml += "\t\t<th>\n\t\t\t<div class=\"filter_button\"><ul>\n";
		headerhtml += "\t\t<th>\n\t\t\t<div class=\"topfunc_buttons\"><ul>\n";
		footerhtml += "\t\t<th></th>\n";
		if ( tableid == "maintable" ) {
			if ( showfiltermenu == "yes" ) {
				filterhtml += "\t\t\t\t<li id=\"filter_menu\" class=\"function_srvfilter\"><a><span title=\"Filter\">Filter</span></a></li>\n\n";
			}
			headerhtml += "\t\t\t\t<li id=\"reset\" class=\"function_reordercols\"><a><span title=\"Reorder Columns\">Reorder</span></a></li>\n\n";
			headerhtml += "\t\t\t\t<li id=\"add_record\" class=\"function_add\"><a " + dataid + " " + dataname + "><span title=\"Add Record\">Add</span></a></li>\n";
		}
		else if ( tableid != "maintable" ) {
			headerhtml += "\t\t\t\t<li id=\"add_record\" class=\"function_add\"><a " + dataid + " " + dataname + "><span title=\"Add Record\">Add</span></a></li>\n";
			//headerhtml += "\t\t\t\t<li id=\"attach_record\" class=\"function_attach\"><a " + dataid + " " + dataname + "><span title=\"Attach Record\">Attach</span></a></li>\n\n";
		}
		headerhtml += "\t\t\t</ul></div>\n\t\t</th>\n";
		filterhtml += "\t\t\t</ul></div>\n\t\t</th>\n";
	}
	if ( tableid == "maintable" && showfilter == "yes" ) {
		headerhtml += "\t</tr>\n";
		headerhtml += filterhtml;
	}
	headerhtml += "\t</tr></thead>\n";
	footerhtml += "\t</tr>\n</tfoot>\n";
	if ( showfooter != "no" ) {
		// combine the two
		headerhtml += footerhtml;
	}
	headerhtml += "</table>\n";
	//console.log(headerhtml);
	return headerhtml;
}
function format_header_id ( varheader, table_id ) {
	return varheader.replace("##ID##", table_id);
}
function filter_form ( columnslist, lists, tableid ) {
	var headerhtml = "<div class=\"input_container\">\n<form class=\"form filter\" id=\"form_filter\">\n<table class=\"datatable\" id=\"" + tableid + "\">\n<thead>\n\t<tr>\n";
	var formhtml = "<tbody>\n\t<tr>\n";
	columnslist.forEach(function(col) {
		headerhtml += "\t\t<th>" + col["title"] + "</th>\n";
		formhtml += "\t\t<td class=\"field_container\">\n";
		if ( col["input_type"] == "select" || col["input_type"] == "tableselect" ) {
			Object.keys(lists).forEach(function(list) {
				if ( list == col["column"] ) {
					formhtml += "\t\t<select class=\"text\" name=\"" + col["column"] + "\" id=\"" + col["column"] + "\"  multiple  >\n";
					formhtml += "\t\t\t<option value=\"\">Select a " + col["title"] + "</option>\n";
					//console.log( lists[list][0]["key"] );
					for ( var i = 0; i < lists[list].length; i++ ) {
						formhtml += "\t\t\t<option value=\"" + lists[list][i].key + "\" >" + lists[list][i].title + "</option>\n";
					}
					formhtml += "\t\t</select>\n";
				}
			});
		} else if ( col["input_type"] == "date" || col["input_type"] == "datetime" ) {
			// two date pickers
		}
		formhtml += "\t\t</td>\n";
	});
	headerhtml += "\t\t<th>&nbsp;</th>\n";
	headerhtml += "\t</tr></thead>\n";
	formhtml += "\t\t<td class=\"button_container\">\n\t\t<button type=\"submit\">Filter</button>\n\t\t</td>\n";
	formhtml += "\t</tr></tbody>\n</table>\n</form>\n</div>\n";
	//console.log( headerhtml + formhtml);
	return headerhtml + formhtml;

}
function addedit_form ( columnslist, lists, selslist ) {
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
				formhtml += "\t\t<textarea class=\"text textarea\" name=\"" + col["column"] + "\" id=\"" + col["column"] + "\"></textarea>\n";
			} else if ( col["input_type"] == "checkbox" ) {
				formhtml += "\t\t<input type=\"" + col["input_type"] + "\" class=\"text\" name=\"" + col["column"] + "\" id=\"" + col["column"] + "\" value=\"1\" " + errinput + ">\n";
			} else if ( col["input_type"] == "select" || col["input_type"] == "tableselect" ) {
				if ( col["multiple"] == "yes" ) {
					var multiple = " multiple";
				} else var multiple = "";
				Object.keys(lists).forEach(function(list) {
					if ( list == col["column"] ) {
						formhtml += "\t\t<select class=\"text\" name=\"" + col["column"] + "\" id=\"" + col["column"] + "\"" + multiple + ">\n";
						formhtml += "\t\t\t<option value=\"\">Select a " + col["title"] + "</option>\n";
						//console.log( lists[list][0]["key"] );
						for ( var i = 0; i < lists[list].length; i++ ) {
							if ( lists[list][0]["key"] != "selectparent" ) {
								formhtml += "\t\t\t<option value=\"" + lists[list][i].key + "\" >" + lists[list][i].title + "</option>\n";
							} else {
								var i = arrayColumn(selslist, "selcol").indexOf(list);
								var parenttitle = selslist[i]["partitle"];
								formhtml += "\t\t\t<option value=\"\">Select " + parenttitle + " first</option>\n";
							}
						}
						formhtml += "\t\t</select>\n";
	  				}
				});
			} else {
				formhtml += "\t\t<input type=\"" + col["input_type"] + "\" class=\"text\" name=\"" + col["column"] + "\" id=\"" + col["column"] + "\" value=\"\" " + errinput + ">\n";
			}
			formhtml += "\t</div>\n</div>\n";
		}
	});
	formhtml += "\t<div class=\"button_container\">\n\t\t<button type=\"submit\">##blank##</button>\n\t</div>\n</form>\n";
	//console.log(formhtml);
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
// Add, Delete, Edit Record
function addeditdel_record ( action ) {
	// get all the url GET parameters and values
	let searchParams = new URLSearchParams(window.location.search);
	let page = searchParams.get('page');
	var id, dt_table, configpage;
	
	$(document).on('click', '.function_' + action + ' a', function(e){
		e.preventDefault();
		if ( action == 'add' ) {
			show_loading_message();
			var job = 'page_lists';
			id = "";
			configpage = page;
			dt_table = 'table_records';
			if ( $(this).data('name') !== undefined ) {
				id = $(this).data('id');
				configpage = $(this).data('name');
				dt_table = $(this).closest('table').attr('id');
			}
		} else if ( action == 'attach' ) {
			// this is should always be a drilldown/childrecord/crosswalk etc function
			show_loading_message();
			var job = 'page_lists'; // don't know yet
			id = $(this).data('id'); // clientid
			configpage = $(this).data('name');
			dt_table = $(this).closest('table').attr('id');
		} else if ( action == 'edit' ) {
			show_loading_message();
			var job = 'get_record';
			id = $(this).data('id');
			configpage = page;
			if ( $(this).data('name') != 'maintable' ) {
				configpage = $(this).data('name');
			}
			dt_table = $(this).closest('table').attr('id');
		} else if ( action == 'delete' ) {
			if (confirm("Are you sure you want to delete this record?")) {
				var job = 'delete_record';
				show_loading_message();
				id = $(this).data('id');
				configpage = page; // un-hardcode this
				dt_table = 'table_records'; // un-hardcode this
				if ( $(this).data('name') != 'maintable' ) {
					configpage = $(this).data('name');
					dt_table = $(this).closest('table').attr('id');
				}
			} else {
				return;
			}
		}
		console.log( action + ' : ' + page + ' : ' + configpage + ' : ' + dt_table + ' : ' + id );

		// Get Record information from database
		var request = getdata_ajax( job, {'id': id,'page': configpage} );
		request.done(function(output){
			if (output.result == 'success') {
				if ( action != 'delete' ) {
	  				colsls = output.colsls;
	  				data = output.data;
					// some how the item id used to link to the child to the partent needs to be passed here
					$(".lightbox_content").html( addedit_form( colsls, output.lists, output.selslist ) );

					if ( action == 'add' ) { // only if dt_table is maintable or the parent record, child records will have an ID
						id, data = '';
					} else if ( action == 'delete' ) {
							return;
					}
					var ucaction = action.replace( /^./, action[0].toUpperCase() );
					$('.lightbox_content h2').text( ucaction + ' Record');
					$('#form_record button').text( ucaction + ' Record');
					$('#form_record').attr('class', 'form ' + action);
					$('#form_record').attr('data-id', id);
					$('#form_record .field_container label.error').hide();
					$('#form_record .field_container').removeClass('valid').removeClass('error');
					// iterate through columns and generate jquery syntax handle form inputs
					colsls.forEach(function(col) {
						var columnclass = "#form_record #" + col["column"];
						var column = col["column"];
						if ( action == 'edit' ) {
							if ( col["multiple"] == "yes" ) {
								$(columnclass).val( data[0][column].split(';') ) || [];
							} else if ( col["input_type"] == "checkbox" ) {
								$(columnclass).prop( 'checked', ( data[0][column] == 1 ) );
							} else if ( col["input_type"] != "drilldown" && col["input_type"] != "crosswalk" ) {
								$(columnclass).val( data[0][column] );
							}
						} else if ( action == 'add' ) {
							if ( col["multiple"] == "yes" ) {
								$(columnclass).val() || [];
							} else if ( col["input_type"] == "checkbox" ) {
								$(columnclass).val('1');
							} else if ( col["input_type"] != "drilldown" && col["input_type"] != "crosswalk" ) {
								$(columnclass).val('');
							}
						}
					});
					hide_loading_message();
					show_lightbox();
				} else {
					// Reload datatable on delete
					console.log( 'sql: ' + output.sql );
					$("#" + dt_table).DataTable().ajax.reload(function(){
						hide_loading_message();
						show_message("Record " + action + "d successfully.", 'success');
					}, true);
				}
			} else {
				hide_loading_message();
				show_message('Information request failed', 'error');
			}

			// Capture Parent Select change for cascade/nested selects
			ajaxselect ( '#form_record', page, output.lists, output.selslist );
		});
		request.fail(function(jqXHR, textStatus){
			hide_loading_message();
			show_message('Information request failed: ' + textStatus, 'error');
		});
	});

	$(document).on('submit', '#form_record.' + action, function(e){
		e.preventDefault();
		var ucaction = action.replace( /^./, action[0].toUpperCase() );
		recordform = $('#form_record');
		// Validate form
		recordform.validate();
		if (recordform.valid() == true){
			// Send Record information to database
			hide_ipad_keyboard();
			hide_lightbox();
			show_loading_message();
			var form_data = $('#form_record').serialize();
			console.log( form_data );
			form_data = cleanserial_mulsel( form_data, colsls );
			if ( action == 'add' || action == 'edit' ) {
				var request = getdata_ajax( action + '_record', form_data + '&id=' + id + '&page=' + configpage );
			}
			console.log( action + ' : ' + page + ' : ' + configpage + ' : ' + dt_table + ' : ' + id );
			request.done(function(output){
				console.log( 'sql: ' + output.sql + ' ; lastid: ' + output.lastid );
				if (output.result == 'success'){
					// Reload datatable
					$("#" + dt_table).DataTable().ajax.reload(function(){
						hide_loading_message();
						show_message("Record " + action + "ed successfully.", 'success');
					}, true);
				} else {
					hide_loading_message();
					show_message( action + ' request failed', 'error');
				}
			});
			request.fail(function(jqXHR, textStatus){
				hide_loading_message();
				show_message( ucaction + ' request failed: ' + textStatus, 'error');
			});
		}
	});
}
function getdata_ajax ( job, data ) {
	return $.ajax({
		url:		'data.php?job=' + job,
		cache:		false,
		data:		data,
		dataType:	'json',
		contentType:  'application/json; charset=utf-8',
		type:		'get'
	});
}
function splitHexColor ( hexColor ) {
	var hexcolor = hexColor.replace(/^#/, '');
	var hexlen = hexcolor.length;
	if ( hexlen == 3 ) {
		var hexsplit = hexcolor.split('');
		for ( var j = 0; j < hexsplit.length; j++ ) {
			hexsplit[j] = hexsplit[j] + hexsplit[j];
		}
	} else if ( hexlen == 6 ) {
		hexsplit = hexcolor.match(/.{2}/g);
	}
	return hexsplit;
}
function blendRowColors ( basecolor, rowcolor) {
	var hexsp = [];
	for ( var i = 0; i < arguments.length; i++ ) {
		hexsp[i] = splitHexColor( arguments[i] );
	}
	var hccmb = [];
	for ( var k = 0; k < hexsp[0].length; k++ ) {
		hccmb[k] = Math.round((parseInt(hexsp[0][k], 16) + parseInt(hexsp[1][k], 16)) / 2 ).toString(16);
	}
	var hccomb = '#' + hccmb.join('');
	return hccomb;
}
function rowformat ( rowfmt, bgcolorodd, bgcoloreven, bgcolorhover ) {
	var cssstyle = "<style>\n";
	rowfmt.forEach(function(rfm) {
		var rfmvalue = rfm["value"];
		var rfmbgcolor = rfm["background-color"];
		var rfmbgcoloreven = blendRowColors(bgcoloreven, rfmbgcolor);
		var rfmbgcolorhover = blendRowColors(bgcolorhover, rfmbgcolor);
		cssstyle += "table.datatable tbody tr.color" + rfmvalue + ".odd {\n";
		cssstyle += "  background-color: " + rfmbgcolor + ";\n}\n";
		cssstyle += "table.datatable tbody tr.color" + rfmvalue + ".even {\n";
		cssstyle += "  background-color: " + rfmbgcoloreven + ";\n}\n";
		cssstyle += "table.datatable tbody tr.color" + rfmvalue + ":hover {\n";
		cssstyle += "  background-color: " + rfmbgcolorhover + ";\n}\n";
	});
	cssstyle += "</style>\n";
	return cssstyle;
}
function ajaxselect ( attributeid, page, lists, selslist ) {
	$(document).on('change', 'select', attributeid, function() {
		var parentvalue = $(this).val();
		var parentcol = $(this).attr('id');
		Object.keys(lists).forEach(function(list) {
			var i = arrayColumn(selslist, "selcol").indexOf(list);
			if ( i > 0 && parentcol == selslist[i]["parselcol"]) { // not sure why first value returned is -1
				var nestedcolumn = list;
				var nestedname = selslist[i]["selname"];
				var nestedid = selslist[i]["selid"];
				var nestedtable = selslist[i]["seltable"];
				var wherekey = selslist[i]["wherekey"];
				var wherevalue = selslist[i]["whereval"];
				var parentcolumn = selslist[i]["parselcol"]; //parentcol
				var parenttitle = selslist[i]["partitle"];
				var datastring = 'nestedcolumn=' + encodeURI(nestedcolumn);
				datastring += '&nestedname=' + encodeURI(nestedname);
				datastring += '&nestedid=' + encodeURI(nestedid);
				datastring += '&nestedtable=' + encodeURI(nestedtable);
				datastring += '&wherekey=' + encodeURI(wherekey);
				datastring += '&wherevalue=' + encodeURI(wherevalue);
				datastring += '&parentcolumn=' + encodeURI(parentcolumn);
				datastring += '&parenttitle=' + encodeURI(parenttitle);
				datastring += '&parentid=' + encodeURI(parentvalue);
				//console.log( 'nc: ' + nestedcolumn + '; nn: ' + nestedname + '; nid: ' + nestedid + '; nt: ' + nestedtable );
				//console.log( 'wk: ' + wherekey + '; wv: ' + wherevalue + '; pc: ' + parentcolumn + '; pt: ' + parenttitle );
				//console.log( datastring );
				if ( parentvalue ) {
					$.ajax({
						url:	'data.php?page=' + page + '&job=ajax_select',
						data:	datastring,
						type:	'get',
						success: function(html) {
							$('#' + nestedcolumn).html(html);
						}
					});
				//} else {
				//	$('#' + nestedcolumn).html('<option value=\"\">Select ' + parenttitle + ' First</option>');
				}
			}
		});
	});
}
function drilldowntable ( parenttable ) {
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
		var row = parenttable.row( tr );
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
				"footerCallback": function ( row, data ) {
					for ( let k = 0; k < ch_colsls.length; k++ ) {
						if ( ch_colsls[k]["footer"] == "yes" ) {
							var api = this.api(), data;
							// Remove the formatting to get integer data for summation
							var intVal = function ( i ) { return typeof i === 'string' ?  i.replace(/[\$,]/g, '').replace('--', '0')*1 : typeof i === 'number' ?  i : 0; };
							// Total over all pages
							var total = api.column( k, { search: 'applied' } ).data().reduce( function (a, b) { return intVal(a) + intVal(b); }, 0 );
							// Update footer
							if ( total < 0 ) {
								$( api.column( k ).footer() ).addClass('negcurrency');
							}
							$( api.column( k ).footer() ).html( accounting.formatMoney(total, { format: { pos: "%s%v", neg: "%s(%v)", zero:"%s --" } } ) );
						}
					}
				},
				"aoColumnDefs": [ { "bSortable": false, "aTargets": [-1] } ],
			});
		});
		tablecount++;
	});
}
function filter_menu ( page, tableid, columnslist, rowfmt, showidcolumn, showrownum, showdeletecolumn ) {
	// create a list of filterable columns
	var filtercols = create_filtercols( columnslist );
	//console.log ( filtercols );

	$(document).on('click', '#filter_menu', function(e){
		e.preventDefault();

		// expand or collapse menu area
		$('#filter_container').animate({height: 'toggle'});

		// calling page_lists here
		var request = getdata_ajax( 'page_lists', {'page': page} );
		request.done(function(output) {
			if (output.result == 'success' && output.message == 'page_lists') {
				lists = output.lists;
				$("#filter_container").html( filter_form( filtercols, lists, 'filtertable' ) );
				filtercols.forEach(function(col) {
					var columnclass = "#form_filter #" + col["column"];
					if ( col["input_type"] == "select" || col["input_type"] == "tableselect" ) {
						$(columnclass).val() || [];
					}
				});
			}
		});

	});

	// server side filter submit button
	$(document).on('submit', '#form_filter.filter', function(e){
		e.preventDefault();

		var filterform = $('#form_filter');
		filterform.validate();
		if ( filterform.valid() == true ) {
			var form_data = $('#form_filter').serialize();
			//console.log( form_data );
			form_data = cleanserial_mulsel( form_data, filtercols );
			// prepare for filter API '::' splits key/value; ',' split multiple values; ';;' splits pairs
			form_data = form_data.replace(/=/g, '::').replace(/;/g, ',').replace(/&/g, ';;');
			console.log( form_data );
			show_loading_message();
			var dt_table = 'table_records';

			// Clear and Destroy previously initialized DataTables
			$("#" + dt_table).DataTable().clear().destroy();
			//$("#" + dt_table).find('[id^=yadcf]').hide();

			// load maintable with filterapi = form_data
			load_maintable( page, dt_table, columnslist, rowfmt, showidcolumn, showrownum, showdeletecolumn, form_data );

			hide_loading_message();
		}
	});

}
function load_maintable ( page, tableid, columnslist, rowfmt, showidcolumn, showrownum, showdeletecolumn, filterapi ) {
	// variables ( page, tableID, rowfmt, colsls, showrownum, showdeletecolumn, showfilterbutton )
	// if filterapi is not empty, null, etc -> showfilterbutton = yes
	var showfilterbutton;
	if ( filterapi != undefined || filterapi != "" ) {
		showfilterbutton = 'yes';
	}

	// Populate maintable header & footer
	$("#" + tableid).html( dt_header( columnslist, 'maintable', showrownum, showdeletecolumn ) );

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
	columnslist.forEach(function(col) {
		if ( col["input_type"] === "drilldown" ) {
			issetdrilldown = 1;
			//fixedColumns does not work well with drilldown tables
			ltCol = rtCol = 0;
		}
	});

	// On page load: datatable
	var maintable = $('#' + tableid).DataTable({
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
			"data": {'page': page, 'dt_table': 'maintable', 'filter': filterapi},
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
		"columns": json_dtcolumns( columnslist, showrownum, showdeletecolumn ),
		"footerCallback": function ( row, data ) {
			for ( let k = 0; k < columnslist.length; k++ ) {
				if ( columnslist[k]["footer"] == "yes" ) {
					var api = this.api(), data;
					// Remove the formatting to get integer data for summation
					var intVal = function ( i ) { return typeof i === 'string' ?  i.replace(/[\$,]/g, '').replace('--', '0')*1 : typeof i === 'number' ?  i : 0; };
					// Total over all pages
					var total = api.column( k, { search: 'applied' } ).data().reduce( function (a, b) { return intVal(a) + intVal(b); }, 0 );
					// Update footer
					if ( total < 0 ) {
						$( api.column( k ).footer() ).addClass('negcurrency');
					}
					$( api.column( k ).footer() ).html( accounting.formatMoney(total, { format: { pos: "%s%v", neg: "%s(%v)", zero: "--" } } ) );
				}
			}
		},
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

	// statr Yet Another Datatables Column Filter
	yadcf.init(maintable, filter_columns( columnslist, showrownum ),
		{ filters_tr_index: 1, cumulative_filtering: true }
	);
	// show row numbers 
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

	// Reset Column Order
	$('#reset').click(function(e) {
		e.preventDefault();
		maintable.colReorder.reset();
	});

	//For each child table
	if ( issetdrilldown === 1 ) {
		drilldowntable( maintable );
	}

	// Moment JS Date format sorting
	$.fn.dataTable.moment( 'MM/DD/YYYY' );
	$.fn.dataTable.moment( 'MM-DD-YYYY' );

}
