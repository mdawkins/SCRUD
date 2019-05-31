<?php
// define variables and set to empty values

// Specific table for page
$pagetitle = "Largest IT companies by revenue";
$table = "it_companies";
$colorderby = "rank::asc";

// Columns list
$colslist = array(
	[ "column" => "rank", "title" => "Rank", "required" => "yes", "input_type" => "text"	],
	[ "column" => "company_name", "title" => "Park Name", "required" => "yes", "input_type" => "text" 	],
	[ "column" => "industries", "title" => "Industries", "required" => "yes", "input_type" => "text"	],
	[ "column" => "revenue", "title" => "Revenue", "input_type" => "number"	],
	[ "column" => "fiscal_year", "title" => "Fiscal Year", "required" => "yes", "input_type" => "number" ],
	[ "column" => "employees", "title" => "Employees", "input_type" => "number" 	],
	[ "column" => "market_cap", "title" => "Market Cap", "input_type" => "number"	],
	[ "column" => "headquarters", "title" => "Headquarters", "required" => "yes", "input_type" => "text"	],
);

?>
