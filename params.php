<?php

/////////////////////////////////////////////////
// API parameters
/////////////////////////////////////////////////

// Return offending SQL on error? (true|false)
// TURN OFF FOR PRODUCTION! ($err_show_sql=false)
$err_show_sql=false;

// Maximum permitted input rows per request
// For no limit, set to 0
$MAX_ROWS=5000;	
					
// Number of batches
$NBATCH=10000;	
$NBATCH=25;				

//////////////////////////////////////////////////
// All TNRS options
// Use to test if submitted options allowed
//////////////////////////////////////////////////

# Options "resolve" & "parse" go to TNRSbatch, but other options
# query database directly
$TNRS_MODES = array("resolve","parse","meta","sources","citations","classifications");

# Sources are now only "tropicos","tpl","usda" but leaving as-is for
# backward compatibility. TNRSbatch will ignore the now non-extistent
# sources and process will still run without error
$TNRS_SOURCES = array("tropicos","tpl","gcc","ildis","usda","ncbi");

#$TNRS_CLASSIFICATIONS = array("tropicos","ncbi");	// Family classification
$TNRS_CLASSIFICATIONS = array("tropicos");	// Family classification

$TNRS_CONSTR_HT = array("true","false"); 	// Constrain by higher taxa
$TNRS_CONSTR_TS = array("true","false"); 	// Constrain by taxonomic sources
$TNRS_MATCHES =  array("best","all");		// Matches to return
$TNRS_ACC_MIN = 0.05;		// Min match accuracy
$TNRS_ACC_MAX = 1;			// Max match accuracy

//////////////////////////////////////////////////
// TNRS default options
//////////////////////////////////////////////////

$TNRS_DEF_SOURCES = "tpl,gcc,ildis,tropicos,usda";	// Taxonomic sources
$TNRS_DEF_CLASSIFICATION = "tropicos"; 				// Family classification
$TNRS_DEF_MODE = "resolve";		// Processing mode
$TNRS_DEF_CONSTR_HT = "false"; 	// Constrain by higher taxa
$TNRS_DEF_CONSTR_TS = "false"; 	// Constrain by taxonomic sources
$TNRS_DEF_MATCHES =  "all";		// Matches to return
$TNRS_DEF_ACC = 0.05;			// Match accuracy

?>
