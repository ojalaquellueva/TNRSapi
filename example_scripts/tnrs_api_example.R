###############################################
# Example of calling the TNRS API from R
# 
# This example demonstrates the basic building blocks needed
# to call the TNRS API.  See also "tnrs_api_example2.R" for a 
# more function-based approach.
#
# Author: Brad Boyle (bboyle@arizona.edu)
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
# Input data (taxonomic names) 
##################

# Use external file or data frame (created below)? 
# Options: "file"|"df"
names_src<-"file"
names_src<-"df"

# External test file
# CSV file, first column an integer ID, second column the name
names_file <- 
	"http://bien.nceas.ucsb.edu/bien/wp-content/uploads/2019/07/tnrs_testfile.csv"

# Or...create your own data frame of names
# One name per line, preceded by an integer identifier
names_df <- data.frame(
"ID"=c(1,2,3,4,5), 
"Name_submitted"=c(
	"Andropogon gerardii", 
	"Andropogon gerardi",
	"Cephaelis elata",
	"Pinus pondersa Lawson",
	"Carnagia gigantea")
)

##################
# Misc parameters
##################

# Header for api call
headers <- list('Accept' = 'application/json', 'Content-Type' = 'application/json', 'charset' = 'UTF-8')

# API variables to clear before each API call
# Avoids spillover between calls
api_vars <- c("mode", "sources", "class", "matches", "acc", 
	"opts", "opts_json", "input_json")

# Response variables to clear
# Avoids spillover of previous results if API call fails
response_vars <- c("results_json", "results_raw", "results")

#################################
# Import the raw data
#################################

# Read in example file of taxon names
if ( names_src=="file" ) {
	data <- read.csv(names_file, header=FALSE)
} else if ( names_src=="df" ) {
	data <- names_df
} else {
	stop( paste0( "ERROR: invalid value '", names_src, "' for parameter names_src" ) )
}
colnames(data) <- c("ID", "Name_submitted")

# Inspect the input data
head(data,25)

# # Uncomment to work with smaller sample of the data
# data <- head(data,10)

# Convert the data to JSON
data_json <- jsonlite::toJSON(unname(data))

#################################
# Example 1: Resolve mode, best match only
#################################

# Clear existing variables
suppressWarnings( rm( list = Filter( exists, c(response_vars, api_vars ) ) ) )

# Set the TNRS options
sources <- "wcvp,wfo"	# Taxonomic sources
class <- "wfo"			# Family classification. Only current option: "wfo"
mode <- "resolve"			# Processing mode
matches <- "best"			# Return best match only

# Convert the options to data frame and then JSON
opts <- data.frame(c(sources),c(class), c(mode), c(matches))
names(opts) <- c("sources", "class", "mode", "matches")
opts_json <-  jsonlite::toJSON(opts)
opts_json <- gsub('\\[','',opts_json)
opts_json <- gsub('\\]','',opts_json)

# Combine the options and data into single JSON object
input_json <- paste0('{"opts":', opts_json, ',"data":', data_json, '}' )

# Send the API request
results_json <- POST(url = url,
                  add_headers('Content-Type' = 'application/json'),
                  add_headers('Accept' = 'application/json'),
                  add_headers('charset' = 'UTF-8'),
                  body = input_json,
                  encode = "json")

# Convert JSON results to a data frame
results_raw <- fromJSON(rawToChar(results_json$content)) 
results <- as.data.frame(results_raw)

# Inspect the results
head(results, 10)

# Display header plus one row vertically
# to better compare the output fields
results.t <- as.data.frame( t( results[,1:ncol(results)] ) )
results.t[,3,drop =FALSE]

# Display just the main results fields
results $match.score <- format(round(as.numeric(results $Overall_score),2), nsmall=2)
results[ 1:10, c('Name_submitted', 'match.score', 'Name_matched', 'Taxonomic_status', 
	'Accepted_name', 'Unmatched_terms')
	]

#################################
# Example 2: Resolve mode, all matches
#################################

# Clear existing variables
suppressWarnings( rm( list = Filter( exists, c(response_vars, api_vars ) ) ) )

# Set the TNRS options
sources <- "wfo,wcvp"					# Taxonomic sources
class <- "wfo"										# Family classification
mode <- "resolve"										# Processing mode
matches <- "all"											# Return all matches

# Convert the options to data frame and then JSON
opts <- data.frame(c(sources),c(class), c(mode), c(matches))
names(opts) <- c("sources", "class", "mode", "matches")
opts_json <-  jsonlite::toJSON(opts)
opts_json <- gsub('\\[','',opts_json)
opts_json <- gsub('\\]','',opts_json)

# Combine the options and data into single JSON object
input_json <- paste0('{"opts":', opts_json, ',"data":', data_json, '}' )

# Construct the request
headers <- list('Accept' = 'application/json', 'Content-Type' = 'application/json', 'charset' = 'UTF-8')

# Send the API request
results_json <- POST(url = url,
                  add_headers('Content-Type' = 'application/json'),
                  add_headers('Accept' = 'application/json'),
                  add_headers('charset' = 'UTF-8'),
                  body = input_json,
                  encode = "json")

# Convert JSON results to a data frame
results_raw <- fromJSON(rawToChar(results_json$content)) 
results <- as.data.frame(results_raw)

# Inspect the results
head(results, 10)

# Just compare name submitted, name matched and final accepted name
results $match.score <- format(round(as.numeric(results $Overall_score),2), nsmall=2)
results[ , c('ID', 'Name_submitted', 'match.score', 'Name_matched', 
	'Taxonomic_status', 'Accepted_name')
	]

results $match.score <- format(round(as.numeric(results $Overall_score),2), nsmall=2)
results[1:10, c('Name_submitted', 'match.score', 'Name_matched', 'Taxonomic_status', 
	'Accepted_name', 'Unmatched_terms')
	]
	
#################################
# Example 3: Resolve mode, all matches, 
# with custom match threshold
#################################

# Clear existing variables
suppressWarnings( rm( list = Filter( exists, c(response_vars, api_vars ) ) ) )

# Set the TNRS options
#sources <- "wfo,wcvp,tropicos"			# Taxonomic sources
sources <- "wfo,wcvp"					# Taxonomic sources
class <- "wfo"								# Family classification
mode <- "resolve"								# Processing mode
matches <- "all"									# Return all matches
acc <- 0.7											# Custom match accuracy threshold

# Convert the options to data frame and then JSON
opts <- data.frame(c(sources),c(class), c(mode), c(matches), c(acc))
names(opts) <- c("sources", "class", "mode", "matches", "acc")
opts_json <-  jsonlite::toJSON(opts)
opts_json <- gsub('\\[','',opts_json)
opts_json <- gsub('\\]','',opts_json)

# Combine the options and data into single JSON object
input_json <- paste0('{"opts":', opts_json, ',"data":', data_json, '}' )

# Construct the request
headers <- list('Accept' = 'application/json', 'Content-Type' = 'application/json', 'charset' = 'UTF-8')

# Send the API request
results_json <- POST(url = url,
                  add_headers('Content-Type' = 'application/json'),
                  add_headers('Accept' = 'application/json'),
                  add_headers('charset' = 'UTF-8'),
                  body = input_json,
                  encode = "json")

# Convert JSON results to a data frame
results_raw <- fromJSON(rawToChar(results_json$content)) 
results <- as.data.frame(results_raw)

# Inspect the results
head(results, 10)

# Just compare name submitted, name matched and final accepted name
results $match.score <- format(round(as.numeric(results $Overall_score),2), nsmall=2)
results[ , c('ID', 'Name_submitted', 'match.score', 'Name_matched', 
	'Taxonomic_status', 'Accepted_name','Accepted_name_author','Source')
	]

#################################
# Example 4: Parse mode
#################################

# Clear response variables only
suppressWarnings( rm( list = Filter( exists, c(response_vars ) ) ) )

# Let's just parse the names instead
# All we need to do is reset option mode:
mode <- "parse"		

# Re-form the options json again
# Note that only option "mode" is needed
opts <- data.frame(c(mode))
names(opts) <- c("mode")

opts_json <- jsonlite::toJSON(opts)
opts_json <- gsub('\\[','',opts_json)
opts_json <- gsub('\\]','',opts_json)

# Make the options + data JSON
input_json <- paste0('{"opts":', opts_json, ',"data":', data_json, '}' )

# Send the API request
results_json <- POST(url = url,
                  add_headers('Content-Type' = 'application/json'),
                  add_headers('Accept' = 'application/json'),
                  add_headers('charset' = 'UTF-8'),
                  body = input_json,
                  encode = "json")

# Convert JSON results to a data frame
results_raw <- fromJSON(rawToChar(results_json$content)) 
results <- as.data.frame(results_raw)

# Inspect the results
head(results, 10)

# Display header and two rows vertically
results.t <- as.data.frame( t( results[,1:ncol(results)] ) )
results.t[,2:3,drop =FALSE]

#################################
# Example 5: Get metadata for current 
# TNRS version
#################################

# Clear response variables
suppressWarnings( rm( list = Filter( exists, c(response_vars ) ) ) )

# All we need to do is reset option mode.
# all other options will be ignored
mode <- "meta"		

# Re-form the options json again
# Note that only 'mode' is needed
opts <- data.frame(c(mode))
names(opts) <- c("mode")
opts_json <- jsonlite::toJSON(opts)
opts_json <- gsub('\\[','',opts_json)
opts_json <- gsub('\\]','',opts_json)

# Make the options
# No data needed
input_json <- paste0('{"opts":', opts_json, '}' )

# Send the API request
results_json <- POST(url = url,
                  add_headers('Content-Type' = 'application/json'),
                  add_headers('Accept' = 'application/json'),
                  add_headers('charset' = 'UTF-8'),
                  body = input_json,
                  encode = "json")

# Convert JSON results to a data frame
results_raw <- fromJSON(rawToChar(results_json$content)) 
results <- as.data.frame(results_raw)
print( results )

#################################
# Example 6: Get metadata for all 
# taxonomic sources
#################################

# Clear response variables
suppressWarnings( rm( list = Filter( exists, c(response_vars ) ) ) )

# Set sources mode
mode <- "sources"		

# Re-form the options json again
opts <- data.frame(c(mode))
names(opts) <- c("mode")
opts_json <- jsonlite::toJSON(opts)
opts_json <- gsub('\\[','',opts_json)
opts_json <- gsub('\\]','',opts_json)

# Make the options
input_json <- paste0('{"opts":', opts_json, '}' )

# Send the API request
results_json <- POST(url = url,
                  add_headers('Content-Type' = 'application/json'),
                  add_headers('Accept' = 'application/json'),
                  add_headers('charset' = 'UTF-8'),
                  body = input_json,
                  encode = "json")

# Convert JSON results to a data frame
results_raw <- fromJSON(rawToChar(results_json$content)) 
results <- as.data.frame(results_raw)
print( results )

#################################
# Example 7: Get bibtex citations for taxonomic 
# sources and the TNRS
#################################

# Clear response variables
suppressWarnings( rm( list = Filter( exists, c(response_vars ) ) ) )

# Set citations mode
mode <- "citations"		

# Re-form the options json again
opts <- data.frame(c(mode))
names(opts) <- c("mode")
opts_json <- jsonlite::toJSON(opts)
opts_json <- gsub('\\[','',opts_json)
opts_json <- gsub('\\]','',opts_json)

# Make the options
input_json <- paste0('{"opts":', opts_json, '}' )

# Send the API request
results_json <- POST(url = url,
                  add_headers('Content-Type' = 'application/json'),
                  add_headers('Accept' = 'application/json'),
                  add_headers('charset' = 'UTF-8'),
                  body = input_json,
                  encode = "json")

# Convert JSON results to a data frame
results_raw <- fromJSON(rawToChar(results_json$content)) 
results <- as.data.frame(results_raw)
print( results )

#################################
# Example 8: Get all currently available 
# family classification sources
#################################

# Clear response variables
suppressWarnings( rm( list = Filter( exists, c(response_vars ) ) ) )

# Set mode
mode <- "classifications"		

# Re-form the options json again
opts <- data.frame(c(mode))
names(opts) <- c("mode")
opts_json <- jsonlite::toJSON(opts)
opts_json <- gsub('\\[','',opts_json)
opts_json <- gsub('\\]','',opts_json)

# Make the options
input_json <- paste0('{"opts":', opts_json, '}' )

# Send the API request
results_json <- POST(url = url,
                  add_headers('Content-Type' = 'application/json'),
                  add_headers('Accept' = 'application/json'),
                  add_headers('charset' = 'UTF-8'),
                  body = input_json,
                  encode = "json")

# Convert JSON results to a data frame
results_raw <- fromJSON(rawToChar(results_json$content)) 
results <- as.data.frame(results_raw)
print( results )

#################################
# Example 8: TNRS contributors & acknowledgments
#################################

# Clear response variables
suppressWarnings( rm( list = Filter( exists, c(response_vars ) ) ) )

# Set mode
mode <- "collaborators"		

# Re-form the options json again
opts <- data.frame(c(mode))
names(opts) <- c("mode")
opts_json <- jsonlite::toJSON(opts)
opts_json <- gsub('\\[','',opts_json)
opts_json <- gsub('\\]','',opts_json)

# Make the options
input_json <- paste0('{"opts":', opts_json, '}' )

# Send the API request
results_json <- POST(url = url,
                  add_headers('Content-Type' = 'application/json'),
                  add_headers('Accept' = 'application/json'),
                  add_headers('charset' = 'UTF-8'),
                  body = input_json,
                  encode = "json")

# Convert JSON results to a data frame
results_raw <- fromJSON(rawToChar(results_json$content)) 
results <- as.data.frame(results_raw)
print( results )

