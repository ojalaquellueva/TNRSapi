<?php

//////////////////////////////////////////////
// Validate TNRS options passed to API
//////////////////////////////////////////////

// Testing
$sources_bak = $opt_arr['sources'];
$class_bak = $opt_arr['class'];
$mode_bak = $opt_arr['mode'];

// Taxonomic sources
if (array_key_exists('sources', $opt_arr)) {
	$sources = $opt_arr['sources'];
	
	if ( trim($sources) == "" ) {
		$sources = $TNRS_DEF_SOURCES;
	} else {
		$src_arr = explode(",",$sources);
		$valid = count(array_intersect($src_arr, $TNRS_SOURCES)) == count($src_arr);
		if ( $valid === false ) die("ERROR: Invalid option '$sources' for 'sources'\r\n");
	}
} else {
	$sources = $TNRS_DEF_SOURCES;
}

// Classification
if (array_key_exists('class', $opt_arr)) {
	$class = $opt_arr['class'];
	
	if ( trim($class) == "" ) {
		$class = $TNRS_DEF_CLASSIFICATION;
	} else {
		$valid = in_array($class, $TNRS_CLASSIFICATIONS);
		if ( $valid === false ) die("ERROR: Invalid option '$class' for 'class'\r\n");
	}
} else {
	$class = $TNRS_DEF_CLASSIFICATION;
}

// Processing mode
if (array_key_exists('mode', $opt_arr)) {
	$mode = $opt_arr['mode'];
	
	if ( trim($mode) == "" ) {
		$mode = $TNRS_DEF_MODE;
	} else {
		$valid = in_array($mode, $TNRS_MODES);
		if ( $valid === false ) die("ERROR: Invalid option '$mode' for 'mode'\r\n");
	}
} else {
	$mode = $TNRS_DEF_MODE;
}

// Constain matches by higher taxonomy
if (array_key_exists('constr_ht', $opt_arr)) {
	$constr_ht = $opt_arr['constr_ht'];
	
	if ( trim($constr_ht) == "" ) {
		$constr_ht = $TNRS_DEF_CONSTR_HT;
	} else {
		$valid = in_array($constr_ht, $TNRS_CONSTR_HT);
		if ( $valid === false ) die("ERROR: Invalid option '$constr_ht' for 'constr_ht'\r\n");
	}
} else {
	$constr_ht = $TNRS_DEF_CONSTR_HT;
}

// Constraint matches by taxonomic sources
if (array_key_exists('constr_ts', $opt_arr)) {
	$constr_ts = $opt_arr['constr_ts'];
	
	if ( trim($constr_ts) == "" ) {
		$constr_ts = $TNRS_DEF_CONSTR_TS;
	} else {
		$valid = in_array($constr_ts, $TNRS_CONSTR_TS);
		if ( $valid === false ) die("ERROR: Invalid option '$constr_ts' for 'constr_ts'\r\n");
	}
} else {
	$constr_ts = $TNRS_DEF_CONSTR_TS;
}

// Match accuracy
if (array_key_exists('acc', $opt_arr)) {
	$acc = $opt_arr['acc'];
	
	if ( trim($acc) == "" ) {
		$acc = $TNRS_DEF_ACC;
	} else {	
		$valid = false;
		if ( is_numeric($acc) ) {
			if ($acc>=$TNRS_ACC_MIN && $acc<=$TNRS_ACC_MAX ) $valid=true;
		} 	
		if ( $valid === false ) die("ERROR: Invalid option '$acc' for 'acc'\r\n");
	}
} else {
	$acc = $TNRS_DEF_ACC;
}

// Matches to return
if (array_key_exists('matches', $opt_arr)) {
	$matches = $opt_arr['matches'];
	
	if (trim($matches) == "" ) {
		$matches = $TNRS_DEF_MATCHES;
	} else {
		$valid = in_array($matches, $TNRS_MATCHES);
		if ( $valid === false ) die("ERROR: Invalid option '$matches' for 'matches'\r\n");
	}
} else {
	$matches = $TNRS_DEF_MATCHES;
}

?>