<?php

////////////////////////////////////////////////////////
// Queries database with supplied sql ($sql)
////////////////////////////////////////////////////////

include $CONFIG_DIR.'db_config.php';

// On error, display SQL if request (turn off for production!)
if ( $err_show_sql ) {
	$sql_disp = " SQL: " . $sql;
} else {
	$sql_disp = "";
}

// connect to the db
$link = mysqli_connect($HOST,$USER,$PWD,$DB);

// check connection
if (mysqli_connect_errno()) {
	echo "Connection failed: ". mysqli_connect_error();
	exit();
}

$qy = mysqli_query($link,$sql) or die('Query failed!'.$sql_disp);

// create one master array of the records
$results_array = array();
if(mysqli_num_rows($qy)) {
	while($result = mysqli_fetch_assoc($qy)) {
		$results_array[] = array($mode=>$result);
	}
}

/* disconnect from the db */
@mysqli_close($link);

?>