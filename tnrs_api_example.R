###############################################
# TNRS API Example
###############################################

rm(list=ls())

#################################
# Parameters
#################################

# Base URL for TNRS api
url = "https://tnrsapi.xyz/tnrs_api.php"	# Production on paramo
#url = "http://vegbiendev.nceas.ucsb.edu:8975/tnrs_api.php"  # Dev on vegbiendev 

# Path and name of input file of taxon names 
# Comma-delimited CSV file, first column an integer ID, second column the name
# Test file from repo:
#names_file <- "tnrs_api_test_names.csv"	
# Test file from BIEN  website:
names_file <- "http://bien.nceas.ucsb.edu/bien/wp-content/uploads/2019/07/tnrs_testfile.csv"

#################################
# Import the raw data
#################################

# Load libraries
library(RCurl) # API requests
library(jsonlite) # JSON coding/decoding

# Read in example file of taxon names
data <- read.csv(names_file, header=FALSE)

# Inspect the input data
head(data,25)

# # Uncomment this to work with smaller sample of the data
# data <- head(data,10)

# Convert the data to JSON
data_json <- jsonlite::toJSON(unname(data))

#################################
# Example 1: Resolve mode, best match only
#################################

# Set the TNRS options
sources <- "tropicos,tpl,usda"	# Taxonomic sources
class <- "tropicos"										# Family classification
mode <- "resolve"										# Processing mode
matches <- "best"											# Return best match only

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
results_json <- postForm(url, .opts=list(postfields= input_json, httpheader=headers))

# Convert JSON results to a data frame
results <-  jsonlite::fromJSON(results_json)

# Inspect the results
head(results, 10)

# Display header plus one row vertically
# to better compare the output fields
results.t <- as.data.frame( t( results[,1:ncol(results)] ) )
results.t[,3,drop =FALSE]

# Display just the main results fields
results $match.score <- format(round(as.numeric(results $Overall_score),2), nsmall=2)
# results[ , c('Name_submitted', 'match.score', 'Name_matched', 'Taxonomic_status', 
	# 'Accepted_name')
	# ]
results[ 1:12, c('Name_submitted', 'match.score', 'Name_matched', 'Taxonomic_status', 
	'Accepted_name', 'Unmatched_terms')
	]

#################################
# Example 2: Resolve mode, all matches
#################################
rm( list = Filter( exists, c("results", "results_json") ) )

# Set the TNRS options
sources <- "tropicos,tpl,usda"					# Taxonomic sources
class <- "tropicos"										# Family classification
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
results_json <- postForm(url, .opts=list(postfields= input_json, httpheader=headers))

results <-  jsonlite::fromJSON(results_json)

# Inspect the results
head(results, 10)

# Just compare name submitted, name matched and final accepted name
results $match.score <- format(round(as.numeric(results $Overall_score),2), nsmall=2)
results[ , c('ID', 'Name_submitted', 'match.score', 'Name_matched', 
	'Taxonomic_status', 'Accepted_name')
	]

#################################
# Example 3: Parse mode
#################################
rm( list = Filter( exists, c("results", "results_json") ) )

# Let's just parse the names instead
# All we need to do is reset option mode:
mode <- "parse"		

# Reform the options json again
opts <- data.frame(c(sources),c(class), c(mode))
names(opts) <- c("sources", "class", "mode")
opts_json <- jsonlite::toJSON(opts)
opts_json <- gsub('\\[','',opts_json)
opts_json <- gsub('\\]','',opts_json)

# Make the options + data JSON
input_json <- paste0('{"opts":', opts_json, ',"data":', data_json, '}' )

# Send the request again
results_json <- postForm(url, .opts=list(postfields= input_json, httpheader=headers))

results <- jsonlite::fromJSON(results_json)

# Inspect the results
head(results, 10)

# Display header and two rows vertically
results.t <- as.data.frame( t( results[,1:ncol(results)] ) )
results.t[,2:3,drop =FALSE]

#################################
# Example 4: Get metadata for current 
# TNRS version
#################################
rm( list = Filter( exists, c("results", "results_json") ) )

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

# Send the request again
results_json <- postForm(url, .opts=list(postfields= input_json, httpheader=headers))

# Display the results
results <- jsonlite::fromJSON(results_json)
print( results )

#################################
# Example 5: Get metadata for all 
# taxonomic sources
#################################
rm( list = Filter( exists, c("results", "results_json") ) )

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

# Send the request again
results_json <- postForm(url, .opts=list(postfields= input_json, httpheader=headers))

# Display the results
results <- jsonlite::fromJSON(results_json)
print( results )

#################################
# Example 6: Get bibtex citations for taxonomic 
# sources and the TNRS
#################################
rm( list = Filter( exists, c("results", "results_json") ) )

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

# Send the request again
results_json <- postForm(url, .opts=list(postfields= input_json, httpheader=headers))

# Display the results
results <- jsonlite::fromJSON(results_json)
print( results )

#################################
# Example 7: Get all currently available 
# family classification sources
#################################
rm( list = Filter( exists, c("results", "results_json") ) )

# Set citations mode
mode <- "classifications"		

# Re-form the options json again
opts <- data.frame(c(mode))
names(opts) <- c("mode")
opts_json <- jsonlite::toJSON(opts)
opts_json <- gsub('\\[','',opts_json)
opts_json <- gsub('\\]','',opts_json)

# Make the options
input_json <- paste0('{"opts":', opts_json, '}' )

# Send the request again
results_json <- postForm(url, .opts=list(postfields= input_json, httpheader=headers))

# Display the results
results <- jsonlite::fromJSON(results_json)
print( results )

