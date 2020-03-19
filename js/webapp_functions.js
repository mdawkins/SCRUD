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
		colsjson += "		{ \"data\": \"rownum\", \"sClass\": \"rownum\", \"orderable\": false },\n";
	}
	colslist.forEach(function(col) {
		var sClassstring = '';
		if ( col["colview"] == "ellipsis" ) {
			sClassstring = ', "sClass": "truncate"';
		} else if ( col["input_type"] == "currency" ) {
			sClassstring = ', "sClass": "integer"';
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
function dt_header ( columnslist, tableid, showrownum, showdeletecolumn, id, page ) {
	var showfilter = "no";
	var headerhtml = "<table class=\"datatable\" id=\"" + tableid + "\">\n<thead>\n\t<tr>\n";
	var filterhtml = "\t<tr>\n";
	if ( showrownum == "yes" && tableid == "maintable" ) {
		headerhtml += "\t\t<th>No.</th>\n";
		filterhtml += "\t\t<th class=\"filter_content\"></th>\n";
	}
	if ( id !== undefined ) {
		var dataid = "data-id=\"" + id + "\"";
	} else { var dataid = ""; }
	if ( page !== undefined ) {
		var dataname = "data-name=\"" + page + "\"";
	} else { var dataname = ""; }
	columnslist.forEach(function(col) {
		if ( col["filterbox"] != "" ) { showfilter = "yes"; }
		if ( col["input_type"] != "crosswalk" ) {
			headerhtml += "\t\t<th>" + col["title"] + "</th>\n";
			filterhtml += "\t\t<th class=\"filter_content\"></th>\n";
		}
	});
	if ( showdeletecolumn != "no" ) {
		filterhtml += "\t\t<th></th>\n";
		headerhtml += "\t\t<th>\n\t\t\t<div class=\"topfunc_buttons\"><ul>\n";
		if ( tableid == "maintable" ) {
			headerhtml += "\t\t\t\t<li id=\"reset\" class=\"function_reordercols\"><a><span title=\"Reorder Columns\">Reorder</span></a></li>\n\n";
		}
		headerhtml += "\t\t\t\t<li id=\"add_record\" class=\"function_add\"><a " + dataid + " " + dataname + "><span title=\"Add Record\">Add</span></a></li>\n";
		if ( tableid != "maintable" ) {
			headerhtml += "\t\t\t\t<li id=\"attach_record\" class=\"function_attach\"><a " + dataid + " " + dataname + "><span title=\"Attach Record\">Attach</span></a></li>\n\n";
		}
		headerhtml += "\t\t\t</ul></div>\n\t\t</th>\n";
	}
	if ( tableid == "maintable" ) {
		headerhtml += "\t</tr>\n";
		headerhtml += filterhtml;
	}
	headerhtml += "\t</tr></thead>\n</table>\n";
	//console.log(headerhtml);
	return headerhtml;
}
function format_header_id ( varheader, table_id ) {
	return varheader.replace("##ID##", table_id);
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
					$(".lightbox_content").html(addedit_form ( colsls, output.lists, output.selslist ));

					if ( action == 'add' ) {
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
				if ( nestedunion !== undefined ) {
					datastring += '&nestedunion=' + encodeURI(nestedunion);
				}
				datastring += '&parentcolumn=' + encodeURI(parentcolumn);
				datastring += '&parenttitle=' + encodeURI(parenttitle);
				datastring += '&parentid=' + encodeURI(parentvalue);
				datastring += '&parenttable=' + encodeURI(parenttable);
				//console.log( 'nc: ' + nestedcolumn + '; nn: ' + nestedname + '; nid: ' + nestedid + '; nt: ' + nestedtable + '; nu: ' + nestedunion );
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
