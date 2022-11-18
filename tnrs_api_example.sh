#############################################################
# Example of querying the TNRS API from bash
#
# This demo is not intended to be run as a script. The 
# commands should be be run interactively from the bash shell.
#
# Requires:
#	jq - Command line json processor
#	csvkit - CSV utilities, including csvjson
#	curl - http requests
# 
# Author: Brad Boyle (bboyle@arizona.edu)
#############################################################

#
# Parameters
#

# Your working directory
WD="/home/bien/tnrs/admin/bugs/partial_match"

# TNRS API base url
APIBASE="https://tnrsapi.xyz/tnrs_api.php"	

# TNRS options
MODE="resolve"
SOURCES="tropicos,wfo,usda"
CLASS="tropicos"
MATCHES="best"

#
# Create test file
#

cd $WD
cat << EOT > partial_match_bug_test_with_id.csv
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

#
# Compose the JSON request
#

opts=$(jq -n \
  --arg mode "$MODE" \
  --arg sources "$SOURCES" \
  --arg class "$CLASS" \
  --arg matches "$MATCHES" \
  '{"mode": $mode, "sources": $sources, "class": $class, "matches": $matches}')
data=$(csvjson partial_match_bug_test_with_id.csv)
req_json='{"opts":'$opts',"data":'$data'}'

# Send the POST request and save response
resp_json=$(curl -X POST \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "charset: UTF-8" \
  -d "$req_json" \
  "$APIBASE" \
  )

# Display submitted and matched names
echo "$resp_json" | jq '.[] | .Name_submitted + ", " + .Name_matched' | tr -d '\"' | column -t -s","
