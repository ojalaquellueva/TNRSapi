<?php

//////////////////////////////////////////////
// Validate TNRS options passed to API
//
// Note that if multiple errors detected, only
// the last gets reported
//////////////////////////////////////////////

// For testing only
$sources_bak = $opt_arr['sources'];
$class_bak = $opt_arr['class'];
$mode_bak = $opt_arr['mode'];

/////////////////////////////////////////////
// TNRS options
/////////////////////////////////////////////

// Processing mode
if (array_key_exists('mode', $opt_arr)) {
	$mode = $opt_arr['mode'];
	
	if ( trim($mode) == "" ) {
		$mode = $TNRS_DEF_MODE;
	} else {
		$valid = in_array($mode, $TNRS_MODES);
		if ( $valid === false ) {
			$err_msg="ERROR: Invalid option '$mode' for 'mode'"; 
			$err_code=400; $err=true;
		}
	}
} else {
	$mode = $TNRS_DEF_MODE;
}

// Taxonomic sources
if (array_key_exists('sources', $opt_arr)) {
	$sources = $opt_arr['sources'];
	
	if ( trim($sources) == "" ) {
		$sources = $TNRS_DEF_SOURCES;
	} else {
		$src_arr = explode(",",$sources);
		$valid = count(array_intersect($src_arr, $TNRS_SOURCES)) == count($src_arr);
		if ( $valid === false ) {
			$err_msg="ERROR: one or more invalid values '$sources' for option 'sources'"; 
			$err_code=400; $err=true;
		}
	}
} else {
	if ( $mode=='syn' ) {
		$err_msg="ERROR: option 'sources' missing, required for mode='syn'"; 
		$err_code=400; $err=true;
	} else {
		$sources = $TNRS_DEF_SOURCES;
	}
}

// Classification
if (array_key_exists('class', $opt_arr)) {
	$class = $opt_arr['class'];
	
	if ( trim($class) == "" ) {
		$class = $TNRS_DEF_CLASSIFICATION;
	} else {
		$valid = in_array($class, $TNRS_CLASSIFICATIONS);
		if ( $valid === false ) {
			$err_msg="ERROR: Invalid option '$class' for 'class'"; $err_code=400; $err=true;
		}
	}
} else {
	$class = $TNRS_DEF_CLASSIFICATION;
}

// Constain matches by higher taxonomy
// CURRENTLY NOT IMPLEMENTED
if (array_key_exists('constr_ht', $opt_arr)) {
	$constr_ht = $opt_arr['constr_ht'];
	
	if ( trim($constr_ht) == "" ) {
		$constr_ht = $TNRS_DEF_CONSTR_HT;
	} else {
		$valid = in_array($constr_ht, $TNRS_CONSTR_HT);
		if ( $valid === false ) {
			$err_msg="ERROR: Invalid option '$constr_ht' for 'constr_ht'"; 
			$err_code=400; $err=true;
		}
	}
} else {
	$constr_ht = $TNRS_DEF_CONSTR_HT;
}

// Constraint matches by taxonomic sources
// CURRENTLY NOT IMPLEMENTED
if (array_key_exists('constr_ts', $opt_arr)) {
	$constr_ts = $opt_arr['constr_ts'];
	
	if ( trim($constr_ts) == "" ) {
		$constr_ts = $TNRS_DEF_CONSTR_TS;
	} else {
		$valid = in_array($constr_ts, $TNRS_CONSTR_TS);
		if ( $valid === false ) {
			$err_msg="ERROR: Invalid option '$constr_ts' for 'constr_ts'"; 
			$err_code=400; $err=true;
		}
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
		if ( $valid === false ) {
			$err_msg="ERROR: Invalid value '$acc' for option '$acc'"; 
			$err_code=400; $err=true;
		}
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
		if ( $valid === false ) {
			$err_msg="ERROR: Invalid option '$matches' for 'matches'"; 
			$err_code=400; $err=true;
		}
	}
} else {
	$matches = $TNRS_DEF_MATCHES;
}

/////////////////////////////////////////////
// Other options
/////////////////////////////////////////////

// Number of batches for makeflow threads
// If not set, uses default $NBATCH
if (array_key_exists('batches', $opt_arr)) {
	$batches = $opt_arr['batches'];
	
	if ( $batches==intval($batches) ) {
		$NBATCH = $batches;
	} else {
		$err_msg="ERROR: Invalid value '$batches' for option 'batches': must be an integer"; $err_code=400; $err=true;
	}
}

?>