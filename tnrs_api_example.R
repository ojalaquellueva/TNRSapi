###############################################
# TNRS API Example
###############################################

rm(list=ls())

#################################################
# Parameters & libraries
#################################################

##################
# Base URLs - choose one
##################

# Production
url = "https://tnrsapi.xyz/tnrs_api.php"	

# Public development on vegbiendev (for testing development versions)
url = "http://vegbiendev.nceas.ucsb.edu:9975/tnrs_api.php" 

##################
# Libraries
##################

library(httr)		# API requests
library(jsonlite) # JSON coding/decoding

##################
# Input data
##################

# Example names file 
example_file <- "http://bien.nceas.ucsb.edu/bien/wp-content/uploads/2019/07/tnrs_testfile.csv"

# File of names to resolve, change  as needed. 
# If you use your own file, it must use the same format as the example file: 
# * CSV UTF8
# * first column an integer ID
# * second column the taxon name
names_file <- example_file
	
##################
# Misc parameters
##################

# API variables to clear before each API call
# Avoids spillover between calls
api_vars <- c("mode", "sources", "class", "matches", "acc")

# Response variables to clear before each API call
# Avoids confusion with previous results if API call fails
response_vars <- c("request_json", "response_json", "response")

#################################################
# Functions
#################################################

make_request_json <- function( mode,	# API mode; required
	sources=NULL,		# Taxonomic sources
	class=NULL,			# Family-level classification source
	matches=NULL,	# Matches returned: best|all
	acc=NULL,				# Match accuracy. Float 0-1, default=0.53
	data=NULL 		# Raw data; required if mode %in% c('resolve','parse')
	) {
	######################################
	# Accepts: options parameters and (optionally) data
	# Returns: formatted JSON api request
	######################################
	
	# Set defaults if applicable
	if ( mode=="resolve" || mode=="parse" )  {
		# Convert raw data to JSON
		if (is.null(data) ) stop("ERROR: modes 'resolve' and 'parse' require data!\n")
		data_json <- jsonlite::toJSON(unname(data))
	} 
	
	opts <- data.frame(mode = mode)
	if ( mode=="resolve" ) {
		# Add remaining options if provided
		# If not provided, TNRS will use defaults
		if (!is.null(sources)) opts <- cbind(opts, data.frame(sources = sources))
		if (!is.null(class)) opts <- cbind(opts, data.frame(class = class))
		if (!is.null(matches)) opts <- cbind(opts, data.frame(matches = matches))
		if (!is.null(acc)) opts <- cbind(opts, data.frame(acc = acc))
	}
	opts_json <-  jsonlite::toJSON(opts)
	opts_json <- gsub('\\[','',opts_json)
	opts_json <- gsub('\\]','',opts_json)
	
	# Combine the options and data into single JSON object
	if ( mode=="resolve" || mode=="parse" ) {
		input_json <- paste0('{"opts":', opts_json, ',"data":', data_json, '}' )
	} else {
		input_json <- paste0('{"opts":', opts_json, '}' )
	}
	
	return(input_json)
}

send_request_json <- function( url, request_json ) {
	###################################
	# Accepts: API url + JSON options & data
	# Sends: POST request to url, with JSON
	#		attached to body
	# Returns: JSON response
	###################################
	if ( is.null(url) || is.na(url) ) {
		stop("ERROR: parameter 'url' missing (function send_request_json()'\n")
	}
	if ( is.null(request_json) || is.na(request_json) ) {
		stop("ERROR: parameter 'json_body' missing (function send_request_json()'\n")
	}
	
	response_json <- POST(url = url,
	                  add_headers('Content-Type' = 'application/json'),
	                  add_headers('Accept' = 'application/json'),
	                  add_headers('charset' = 'UTF-8'),
	                  body = request_json,
	                  encode = "json"
	                  )

	return(response_json)
}

decode_response_json <- function( response_json ) {
	###################################
	# Converts resonse json to data frame
	###################################
	
	response_raw <- fromJSON( rawToChar( response_json$content ) ) 
	response <- as.data.frame(response_raw)
	return( response )
}

tnrs_request <- function(url, mode,	# Required
	sources=NULL,		# Taxonomic sources
	class=NULL,			# Family-level classification source
	matches=NULL,	# Matches returned: best|all
	acc=NULL,				# Match accuracy. Float 0-1, default=0.53
	data=NULL 			# Raw data required if mode %in% c('resolve','parse')
	) {
	######################################
	# Accepts: options parameters and (optionally) data
	#		required for TNRS request
	# Sends: POST request to TNRS API
	# Returns: response formatted as data frame
	# 
	# Meta-function which combine functions 
	# make_request_json, send_request_json & 
	# decode_response_json. See component 
	# functions for details
	######################################
	if ( is.null(url) || is.na(url) ) {
		stop("ERROR: parameter 'url' missing (function tnrs_request()'\n")
	}
	if ( is.null(mode) || is.na(mode) ) {
		stop("ERROR: parameter 'mode' missing (function tnrs_request()'\n")
	}

	request_json <- make_request_json(
		mode=mode,
		sources= sources,	
		class= class,
		matches= matches,
		acc= acc,	
		data= data 
		)
	response_json <- send_request_json( url, request_json )
	response_df <- decode_response_json( response_json )
	
	if ( ncol(response_df)==1 ) {
		colnames(response_df) <- "error"
		response_df$http_status <- response_json$status
	}

	return( response_df )
}

specify_decimal <- function(x, k) {
	# Set fixed number of decimals
	if ( is.na(x) || is.null(x) ) {
		x.formatted <- x
	} else {
		x.formatted <- format(round(x, k), nsmall=k)
	}
	return( x.formatted )
}

#################################################
# Main
#################################################

#################################
# Example 1: Metadata
#
# Available metadata calls: 
# "meta", "sources", "class", "citations", "collaborators"
# print(response) to see the complete results of each call
#################################

# TNRS application version and database version
mode <- "meta"		
rm( list = Filter( exists, response_vars ) )
response <- tnrs_request(url=url, mode=mode)
db_version  <- response$db_version
db_date   <- response$build_date
if( "app_version" %in% colnames(response) ) {
	tnrs_version  <- response$app_version
} else {
	tnrs_version  <- response$code_version
}

# Available sources
mode <- "sources"		
rm( list = Filter( exists, response_vars ) )
response <- tnrs_request(url=url, mode=mode)
sources  <- as.vector(t(response$sourceName))
source.details <- response[ , c(
	"sourceID", "sourceName", "sourceNameFull", "version", 
	"sourceReleaseDate", "tnrsDateAccessed"
	)]

# Available classifications
mode <- "classifications"		
rm( list = Filter( exists, response_vars ) )
response <- tnrs_request(url=url, mode=mode)
classifications  <- as.vector(t(response$sourceName))

# TNRS and source citations
mode <- "citations"		
rm( list = Filter( exists, response_vars ) )
citations <- tnrs_request(url=url, mode=mode)
wfo_citation <- citations$citation[ citations$source=="wfo" ]
cact_citation <- citations$citation[ citations$source=="cact" ]

# TNRS collaborators
mode <- "collaborators"		
rm( list = Filter( exists, response_vars ) )
collaborators <- tnrs_request(url=url, mode=mode)
collaborator.codes  <- as.vector(t(collaborators$collaboratorName))

# Display results
cat("TNRS version: ", tnrs_version, "\n")
cat("DB version: ", db_version, " (", db_date, ")\n")
cat("Available taxonomic sources: ", sources, "\n")
cat("Available family classifications: ", classifications, "\n")
cat(" \r\n")

cat("Taxonomic source details:\n")
print(source.details, row.names=FALSE)
cat(" \r\n")

cat("\r\nCitations:")
for (i in 1:nrow(citations)) { 
	cat(citations$source[ i ], ":\r\n", sep="")
	cat("  ", citations$citation[ i ], ":\r\n", sep="")
}
	cat(" \r\n")

cat("\r\nTNRS collaborators:\r\n")
cat(collaborator.codes, sep = "\r\n")
cat(" \r\n")

#################################
# Example 2: Resolve mode, best match only
#################################

# Load test data
data <- read.csv(names_file, header=FALSE)
colnames(data) <- c("ID", "Name_submitted")
data <- head(data, 10) # Just a sample
cat("Raw names:\n")
print(data)

# Clear existing variables
suppressWarnings( rm( list = Filter( exists, c(response_vars, api_vars ) ) ) )

# Set options
sources <- "wcvp,wfo"		# Taxonomic sources
class <- "wfo"					# Family classification. Only current option: "wfo"
mode <- "resolve"			# Processing mode
matches <- "best"			# Return best match only

response <- tnrs_request(url=url, mode=mode, matches=matches, 
	source=sources, class=class, data=data	)
if ( colnames(response)[1]=="error" ) {
	print( response )
} else {
	# Format overall_score to 2 decimals
	response$Overall_score <- as.numeric(lapply( 
		as.numeric(response$Overall_score), FUN=specify_decimal, k=2
		))
	results_cols_basic <- c(
		"ID", "Name_submitted","Overall_score", 	"Name_matched", 
		"Accepted_name", "Accepted_name_author",	"Source"
	)
	print( response[ , results_cols_basic ]	)
}

#################################
# Example 3: Resolve mode, all matches
#################################

# Clear existing variables
suppressWarnings( rm( list = Filter( exists, c(response_vars, api_vars ) ) ) )

# Fewer rows of the shorter names
data.small <- data[c(1:2, 4:7), ]

# Set options
mode <- "resolve"						
sources <- "wfo,wcvp"	
class <- "wfo"
matches <- "all"		# Return all matches

response <- tnrs_request(url=url, mode=mode, matches=matches, 
	source=sources, class=class, data= data.small	)
if ( colnames(response)[1]=="error" ) {
	print( response )
} else {
	response$Overall_score <- as.numeric(lapply( 
		as.numeric(response$Overall_score), FUN=specify_decimal, k=2
		))
	results_cols <- c(results_cols_basic,
		 c("Overall_score_order", "Highertaxa_score_order", "Warnings")
	)
	print( response[ , results_cols ]	)
}

#################################
# Example 3: Resolve mode, all matches, 
# Custom match threshold sets minimum
# value of Overall_score
#################################

# Clear existing variables
suppressWarnings( rm( list = Filter( exists, c(response_vars, api_vars ) ) ) )

# Set options
mode <- "resolve"						
sources <- "wfo,wcvp"	
class <- "wfo"
matches <- "all"	
acc <- 0.9					# High match threshold

response <- tnrs_request(url=url, mode=mode, matches=matches, 
	source=sources, class=class, acc=acc, data= data.small	)
if ( colnames(response)[1]=="error" ) {
	print( response )
} else {
	response$Overall_score <- as.numeric(lapply( 
		as.numeric(response$Overall_score), FUN=specify_decimal, k=2
		))
	results_cols <- c(results_cols_basic,
		 c("Overall_score_order", "Highertaxa_score_order", "Warnings")
	)
	print( response[ , results_cols ]	)
}

#################################
# Example 5: Parse mode
#################################

# Clear existing variables
suppressWarnings( rm( list = Filter( exists, c(response_vars, api_vars ) ) ) )

# Set options
mode <- "parse"			# Processing mode

response <- tnrs_request(url=url, mode=mode, matches=matches, 
	source=sources, class=class, data=data	)
if ( colnames(response)[1]=="error" ) {
	print( response )
} else {
	results_cols <- c(
		"ID", "Name_submitted", "Family", "Genus", "Specific_epithet", "Author"
	)
	print( response[ , results_cols ]	)
}

#################################
# Example 5: TNRS data dictionary
#################################

# Available classifications
mode <- "dd"		
rm( list = Filter( exists, response_vars ) )
dd <- tnrs_request(url=url, mode=mode)
dd
