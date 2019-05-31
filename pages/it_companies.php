<?php
// define variables and set to empty values

// Specific table for page
$table = "it_companies";
$colorderby = "rank::asc";

// Columns list
$colslist = array(
	[ "column" => "rank", "title" => "Rank", "required" => "yes", "input_type" => "text"	],
	[ "column" => "company_name", "title" => "Park Name", "required" => "yes", "input_type" => "text" 	],
	[ "column" => "industries", "title" => "Industries", "required" => "yes", "input_type" => "text"	],
	[ "column" => "revenue", "title" => "Revenue", "input_type" => "text"	],
	[ "column" => "fiscal_year", "title" => "Fiscal Year", "required" => "yes", "input_type" => "text" ],
	[ "column" => "employees", "title" => "Employees", "input_type" => "text" 	],
	[ "column" => "market_cap", "title" => "Market Cap", "input_type" => "text"	],
	[ "column" => "headquarters", "title" => "Headquarters", "required" => "yes", "input_type" => "text"	],
);

?>
