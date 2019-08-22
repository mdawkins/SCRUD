<?php
function split_hexcolor ( $hexColor ) {
	$hexcolor = str_replace('#', '', $hexColor);
	if ( strlen($hexcolor) == 6 ) {
		$hexsplit = str_split($hexcolor, 2);
	} elseif ( strlen($hexcolor) == 3 ) {
		$hexsplit = str_split($hexcolor, 1);
		for ( $i = 0; $i < 3; $i++ ) {
			$hexsplit[$i] = $hexsplit[$i].$hexsplit[$i];
		}
	}
	return $hexsplit;
}

function blend_rowcolors ( $basecolor, $rowcolor ) {
	$hexsp["bc"] = split_hexcolor($basecolor);
	if ( empty($rowcolor) ) { $rowcolor = $basecolor; } // blend with itself
	$hexsp["rc"] = split_hexcolor($rowcolor);
	for ( $i = 0; $i < 3; $i++ ) {
		$hccmb[$i] = dechex(round( (hexdec($hexsp["bc"][$i]) + hexdec($hexsp["rc"][$i])) / 2 ));
	}
	$hccomb = "#" . implode("", $hccmb);
	return $hccomb;
}

?>
