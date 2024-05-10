###############################################
# Example of calling the TNRS API from R
# 
# This example shows how to build your own functions for
# more efficient API calls
#
# Authors: Brad Boyle (bboyle@arizona.edu)
###############################################

rm(list=ls())

#################################
# Parameters & libraries
#################################

##################
# Base URL
##################

url = "https://tnrsapi.xyz/tnrs_api.php"	

##################
# Libraries
##################

library(httr)		# API requests
library(jsonlite) # JSON coding/decoding

##################
# Test data
##################

# Use external file or data frame created in this script (see below)? 
# Options: "file"|"df"
names_src<-"file"
names_src<-"df"

#########################################
# Test names
# CSV file with 2 columns: ID {integer}, Name_submitted
# Column names don't matter, but number of columns does
# Also include header, or first name will be treated as the header and lost
#########################################

######################
# Files
# Requires names_src=="file"
######################

# Test names file at a stable URL that can be used any time
names_file <- 
	"http://bien.nceas.ucsb.edu/bien/wp-content/uploads/2019/07/tnrs_testfile.csv"

######################
# Roll your own df of test names
# Requires names_src=="df"
######################

names_df <- data.frame(
  "ID"=c(1,2,3), 
  "Name_submitted"=c("Carnegia gigantea", "Opuntia versicolor", "Pinus ponerosa Lawson")
)
names_df <- data.frame(
  "ID"=c(1), "Name_submitted"=c("Carnagia gigantea")
)
names_df <- data.frame(
  "ID"=c(1,2,3,4,5,6), 
  "Name_submitted"=c(
    "Andropogon gerardii", 
    "Andropogon gerardi",
    "Cephaelis elata",
    "Pinus pondersa Lawson",
    "Carnagia gigantea",
    "Echinocactus texensis"
  )
)

##################
# Misc parameters
##################

# API variables to clear before each API call
# Avoids spillover between calls
api_vars <- c("mode", "sources", "class", "matches", "acc")

# Response variables to clear before each API call
# Avoids confusion with previous results if API call fails
response_vars <- c("request_json", "response_json", "response")

#################################
# Functions
#################################

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
  if ( mode=="resolve" || mode=="parse" || mode=='syn' )  {
    # Convert raw data to JSON
    if (is.null(data) ) stop("ERROR: modes 'resolve' and 'parse' require data!\n")
    data_json <- jsonlite::toJSON(unname(data))
  } 
  
  opts <- data.frame(mode = mode)
  if ( mode=="resolve" || mode=='syn' ) {
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
  if ( mode=="resolve" || mode=="parse" || mode=='syn' ) {
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
                         data=NULL 			# Raw data required if mode %in% c('resolve','parse','syn')
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
  
  if ( mode=="syn" ) {
    request_json <- make_request_json(
      mode=mode,
      sources=sources,	
      data=data 
    )	
  } else {
    request_json <- make_request_json(
      mode=mode,
      sources= sources,	
      class= class,
      matches= matches,
      acc= acc,	
      data= data 
    )
  }
  response_json <- send_request_json( url, request_json )
  response_df <- decode_response_json( response_json )
  
  if ( ncol(response_df)==1 ) {
    colnames(response_df) <- "error"
  } else if ( nrow(response_df)==0 ) {
    response_df <- data.frame("error"=c("Response is empty") )
  }
  response_df$http_status <- response_json$status
  
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

########################################
# Main
########################################

#################################
# Load test data
#################################

if ( names_src=="file" ) {
  data <- read.csv(names_file, header=FALSE)
  colnames(data) <- c("ID", "Name_submitted")
} else if ( names_src=="df" ) {
  data <- names_df
} else {
  stop( paste0( "ERROR: invalid value '", names_src, "' for parameter names_src" ) )
}

data <- head(data, 10) # Just a sample
cat("Raw names:\n")
print(data)

#################################
# Example 1: Resolve mode, best match only
#################################

# Clear existing variables
suppressWarnings( rm( list = Filter( exists, c(response_vars, api_vars ) ) ) )

# Set options
sources <- "cact,wfo,wcvp"		# Taxonomic sources
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
    "Accepted_family", "Accepted_name", "Accepted_name_author",	"Taxonomic_status", "Source"
  )
  print( response[ , results_cols_basic ]	)
}

#################################
# Example 2: Resolve mode, all matches
#################################

# Clear existing variables
suppressWarnings( rm( list = Filter( exists, c(response_vars, api_vars ) ) ) )

# # Fewer rows of the shorter names
# data.small <- data[c(1:2, 4:7), ]

# Set options
mode <- "resolve"						
sources <- "wfo,wcvp"	
class <- "wfo"
matches <- "all"		# Return all matches

response <- tnrs_request(url=url, mode=mode, matches=matches, 
                         source=sources, class=class, data= data	)
if ( colnames(response)[1]=="error" ) {
  print( response )
} else {
  response$Overall_score <- as.numeric(lapply( 
    as.numeric(response$Overall_score), FUN=specify_decimal, k=2
  ))
  # results_cols <- c(results_cols_basic,
  # c("Taxonomic_status", "Overall_score_order", "Highertaxa_score_order", "Warnings", "WarningsEng")
  # )
  results_cols <- c(results_cols_basic,
                    c("Taxonomic_status", "Source")
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
sources <- "wfo"	
class <- "wfo"
matches <- "all"	
acc <- 0.9					# High match threshold

response <- tnrs_request(url=url, mode=mode, matches=matches, 
                         source=sources, class=class, acc=acc, data= data	)
if ( colnames(response)[1]=="error" ) {
  print( response )
} else {
  response$Overall_score <- as.numeric(lapply( 
    as.numeric(response$Overall_score), FUN=specify_decimal, k=2
  ))
  results_cols <- c(results_cols_basic,
                    c("Taxonomic_status", "Overall_score_order", "Highertaxa_score_order", "WarningsEng")
  )
  print( response[ , results_cols ]	)
}

#################################
# Example 4: Parse mode
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
# Example 4: TNRS data dictionary
# Lists all output fields, with definitions
#################################

mode <- "dd"		
rm( list = Filter( exists, response_vars ) )
response <- tnrs_request(url=url, mode=mode)
print(response)  

#################################
# Example 6: Get synonyms
#################################

suppressWarnings( rm( list = Filter( exists, c(response_vars, api_vars ) ) ) )

# Set the name and taxonomic source you want to check
# Name submitted doesn't need to be an accepted name as the
# TNRS will resolve it to an accepted name
nameSubmitted ="Palicourea elata"	# Name to check (just one)
sources <- "wcvp"	                # Taxonomic source (just one)

# # More test names
# # Submitted name is a synonym
# nameSubmitted ="Echinocactus texensis"
# sources <- "cact"
# 
# # No match found
# nameSubmitted ="Junkus unresolvicus"
# sources <- "cact"
# 
# # Matches, but no accepted name
# nameSubmitted='Solanaceae Solanum bipatens Dunal'
# sources='wfo'

# Set mode and data
# Data same as for mode 'resolve', but only one name allowed
mode <- "syn"		
data <- data.frame("ID"=c(1), "Name_submitted"=c(nameSubmitted))

# Only 3 elements required
response <- tnrs_request(url=url, mode=mode, sources=sources, data=data)

if ( colnames(response)[1]=="error" ) {
  # Display error message
  print("Synonym request to TNRS failed!")
  print(response[,c("error", "http_status")])
} else {
  # Display the list of synonyms
  print(response)
}

#################################
# Example 7: Metadata calls
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
cat("TNRS version: ", tnrs_version, "\n", sep="")

cat("TNRS URL: ", url, "\n", sep="")

cat("DB version: ", db_version, " (", db_date, ")\n", sep="")

cat("Available taxonomic sources: ", paste(sources, collapse=", "), "\n")

cat("Available family classifications: ", paste(classifications, collapse=", "), "\n")

cat("Taxonomic source details:\n", sep="")
print(source.details, row.names=FALSE)

cat("WFO citation:")
print(wfo_citation, row.names=FALSE)

cat("Cactaceae (cact) citation:")
print(wfo_citation, row.names=FALSE)

cat("TNRS collaborators:\n", paste0(collaborator.codes, "\n"))

