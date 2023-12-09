	#!/bin/bash

#############################################################
# Example of querying the TNRS API from bash
#
# Creates and saves a simple input CSV file of taxonomic names.
# Resolves or parses the names, using options set in Parameters section.
# Save the result to another CSV file.
#
# Requires:
#	jq - Command line json processor
#	csvkit - CSV utilities, including csvjson
#	curl - http requests
# 
# This demo can be run as a script. Usage:
#	./tnrs_api_example2.sh
#
# Author: Brad Boyle (bboyle@arizona.edu)
# Date: 7 Dec. 2023
#############################################################

##################################
# Parameters
#
# All can be over-ridden by 
# command-line options
##################################

# Automatically generate input file?
# prepare_f_in='t' (true): generate new input data file (see below in Main)
# prepare_f_in<>'t': import pre-existing data file
# Over-ridden by command line option -f
prepare_f_in="t"

# Data directory where input and output will be saved
# Omit trailing slash
# Set this to location of input and output files
# Over-ridden by file path and name supplied with -f option
datadir="/home/bien/tnrs/admin/bugs/partial_match"

# Input file basename (minus ".csv" extension)
# Content is created on the fly (see Main, below) and saved to this file
# Results file is name automatically (see Main)
# Over-ridden by file path and name supplied with -f option
f_in_base="partial_match_bug_test_with_id"

# TNRS API instance (base url)
# Over-ridden by command line option -f
URL="https://tnrsapi.xyz/tnrs_api.php" 
URL="http://vegbiendev.nceas.ucsb.edu:9975/tnrs_api.php" 

# API mode 
# parse|resolve
# Over-ridden by value supplied with command-line option -m
MODE="parse"

# Remaining API parameters apply to MODE="resolve" only
# Over-ridden by values supplied with command-line options -s, -c and -m
SOURCES="wfo"
CLASS="wfo"
MATCHES="best"

# Verbose mode (t|f)
# Use for debugging output of each step
# If verbose<>"t" suppresses all screen output
# Over-ridden by command line option -v
verbose="f"

##################################
# Get command line options
##################################






##################################
# Main
##################################

#
# Set remaining parameters automatically
#

if [ "$MODE" == "resolve" ]; then
	f_out_base=$f_in_base"_resolved"
elif [ "$MODE" == "parse" ]; then 
	f_out_base=$f_in_base"_parsed"
else 
	echo "ERROR: unknown \$MODE: $MODE"
	exit 1
fi

# Compose the final file names
f_in="${datadir}/${f_in_base}.csv"
f_out="${datadir}/${f_out_base}.csv"
f_out_json="${datadir}/${f_out_base}.json"

#
# Generate input file, if requested
#

# This step over-ridden if -f option supplied via command line
# Note: final heredoc EOT must be flush with left margin!
if [ "$prepare_f_in" == "t" ]; then
	cat << EOT > $f_in
	id,species
	1,"Connarus venezuelanus"
	2,"Connarus venezuelensis"
	3,"Croton antisyphiliticus"
	4,"Croton antisiphyllitius"
	5,"Connarus sp.1"
	6,"Connarus"
	7,"Connaraceae Connarus absurdus"
	8,"Connarus absurdus"
	9,"Connaraceae Badgenus badspecies"
	10,"Rosaceae Badgenus badspecies"
EOT
fi

#
# Compose and send the API request
#

# Compose json-formatted API options
if [ "$MODE" == "resolve" ]; then
	opts=$(jq -n \
	  --arg mode "$MODE" \
	  --arg sources "$SOURCES" \
	  --arg class "$CLASS" \
	  --arg matches "$MATCHES" \
	  '{"mode": $mode, "sources": $sources, "class": $class, "matches": $matches}')
else 
	opts=$(jq -n \
	  --arg mode "$MODE" \
	  '{"mode": $mode}')
fi
  
data=$(csvjson "$f_in")
req_json='{"opts":'$opts',"data":'$data'}'


if [ "$verbose" == "t" ]; then
	echo "Raw data (file '${f_in}'):"
	cat $f_in
	echo " "
	echo "Raw data as JSON (\$data):"
	echo "$data"
	echo " "
	echo "Request JSON (\$req_json):"
	echo "$req_json"
	echo " "
fi



# Send API request and save response
if [ "$verbose" == "t" ]; then
	opt_quiet=""
else
	opt_quiet="-s"
fi

resp_json=$(curl "${opt_quiet}" -X POST \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "charset: UTF-8" \
  -d "$req_json" \
  "$URL" \
  )

#
# Save response JSON to CSV file
#

# Cumbersome jq method
# echo "$resp_json" | jq -r '(map(keys) | add | unique) as $cols | map(. as $row | $cols | map($row[.])) as $rows | $cols, $rows[] | @csv' > $f_out
#partial_match_bug_test_with_id_parsed2.csv

# in2csv (csvkit): simple and preserves column order
echo "$resp_json" > $f_out_json
in2csv $f_out_json > $f_out

if [ "$verbose" == "t" ]; then
	echo " "
	echo "Response JSON (\$resp_json):"
	echo "$resp_json"
	echo " "
	echo "Response JSON (saved to temp file '${f_out_json}'):"
	cat $f_out_json
	echo " "
	echo "CSV results (saved to file '${f_out}'):"
	cat $f_out
	echo " "
fi

rm $f_out_json


