#!/bin/bash

#############################################################
# Example of querying the TNRS API from Linux Bash shell
#
# Takes as input a simple CSV file of taxonomic names preceded by integer IDs
# Resolves or parses the names, using options set in Parameters section.
# Save the result to another CSV file. Can also create the input file
# inside this script (see below).
#
# Requires:
#	jq - Command line json processor
#	csvkit - CSV utilities, including csvjson
#	curl - http requests
# 
# This demo can be run as a script. Usage (*=default value; do not include in command):
#
#	./tnrs_api_example2.sh [-q|--quiet] [-v|--verbose] 
#		[-f /absolute/path/and/inputfilename.csv]
#		[-o /absolute/path/and/outputfilename.csv]
#		[-u tnrs_api_url]
#		[-m|--mode {resolve|parse}]
#		[-s|--sources taxonomic,sources,comma,delimited]
#		[-c|--class familyclassificationsourcename]
#		[-a|--allmatches]
#
# Defaults:
#	* Minimal screen echo (input, output only)
#	* API options mode, sources, class & matches: see below
#	* output file(s) saved to same directory as input file
#
# Author: Brad Boyle (bboyle@arizona.edu)
# Date: 7 Dec. 2023
#############################################################

##################################
# Default parameters
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
# "." sets uses same directory as this script
datadir="."

# Input file basename (minus ".csv" extension)
# Content is created on the fly (see Main, below) and saved to this file
# Results file is name automatically (see Main)
# Over-ridden by file path and name supplied with -f option
f_in_base="partial_match_bug_test_with_id"

# TNRS API instance (base url)
# Over-ridden by command line option -f
URL="https://tnrsapi.xyz/tnrs_api.php"

# API mode 
# parse|resolve
# Over-ridden by value supplied with command-line option -m
MODE="resolve"

# Remaining API parameters apply to MODE="resolve" only
# Over-ridden by values supplied with command-line options -s, -c and -m
SOURCES="wfo,wcvp"
CLASS="wfo"
MATCHES="best"

# Verbose mode (t|f)
# Use for debugging output of each step
# If verbose<>"t" defaults to minimal output
# of input names and selected results columns
# Over-ridden by command line option -v
verbose="f"

# Suppress all screen output? (t|f)
# Over-ridden by command line option -q
# Over-rides option -v
quiet="f"

##################################
# Get command line options
##################################

while [ "$1" != "" ]; do
    case $1 in
        -q | --quiet )         	quiet="t"
                            	;;
        -v | --verbose )        verbose="t"
                            	;;
        -f | --infile )     	shift
        						f_in=$1
                            	;;
        -o | --outfile )        shift
                                f_out=$1
                                ;;
        -u | --url )    	    shift
                                URL=$1
                                ;;
        -m | --mode )   	     shift
                                MODE=$1
                                ;;
        -s | --sources )   	    shift
                                SOURCES=$1
                                ;;
        -c | --class )          shift
                                CLASS=$1
                                ;;
        -a | --all )       		MATCHES="all"
                            	;;
         * )                     echo "invalid option: $1 ($local)"; exit 1
    esac
    shift
done

if [ "$quiet" == "t" ]; then
	verbose="f"
fi

##################################
# Main
##################################

#
# Set remaining parameters automatically
#

# Compose the final file names
if [ -z "${f_in+x}" ]; then
	#echo "\$f_in NOT set!"
	f_in="${datadir}/${f_in_base}.csv"
else
	#echo "\$f_in is set!"
	prepare_f_in="f"
	datadir="${f_in%/*}"
	f_in_fullname=$(basename -- "$f_in")
	f_in_base="${f_in_fullname%.*}"
fi

if [ -z "${f_out+x}" ]; then
	#echo "\$f_out NOT set!"
	f_out="${datadir}/${f_out_base}.csv"
	f_out_json="${datadir}/${f_out_base}.json"
else
	#echo "\$f_out is set!"
	datadirout="${f_out%/*}"
	f_out_fullname=$(basename -- "$f_out")
	f_out_base="${f_out_fullname%.*}"
	f_out_json="${datadirout}/${f_out_base}.json"
fi

if [ "$MODE" == "resolve" ]; then
	f_out_base=$f_in_base"_resolved"
elif [ "$MODE" == "parse" ]; then 
	f_out_base=$f_in_base"_parsed"
else 
	echo "ERROR: unknown \$MODE: $MODE"
	exit 1
fi

# Set filenames manually
f_in="${datadir}/${f_in_base}.csv"
f_out="${datadir}/${f_out_base}.csv"
f_out_json="${datadir}/${f_out_base}.json"


#
# Generate input file, if requested
#

# Load example set of test names
# This step over-ridden if -f option supplied via command line
# Note: final EOT *must* be flush with left margin!
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


if [ "$quiet" != "t" ]; then
	echo "Names submitted:"
	#cat $f_in
	csvcut "$f_in" | csvlook
	echo " "
fi

if [ "$verbose" == "t" ]; then
	echo "Raw data file: '${f_in}'):"
	echo "Raw data as JSON (\$data):"
	echo "$data"
	echo " "
	echo "Request JSON (\$req_json):"
	echo "$req_json"
	echo " "
fi

if [ "$verbose" == "t" ]; then
	opt_quiet=""
else
	opt_quiet="-s"
fi

if [ "$quiet" != "t" ]; then
	echo "Processing with TNRS API @ '${URL}'"
	echo " "
fi

# Send API request and save response
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

# in2csv (csvkit): simple and preserves column order
echo "$resp_json" > $f_out_json
in2csv $f_out_json > $f_out

if [ "$quiet" != "t" ]; then
	if [ "$MODE" == "resolve" ]; then
		echo "Name resolution results:"
		csvcut -c Name_submitted,Name_matched,Overall_score,Taxonomic_status,Accepted_name 		"$f_out" | csvlook 
	elif [ "$MODE" == "parse" ]; then
		echo "Name parsing results:"
		csvsql --query "select Name_submitted, Family, Genus, Specific_epithet as Species, Infraspecific_rank as rank, Infraspecific_epithet as infraspecific, Author, Annotations, Unmatched_terms as unmatched from '${f_out_base}'" "$f_out" | csvlook
	fi
fi

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


