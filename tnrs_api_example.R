###############################################
# TNRS API Example
###############################################

# Load libraries
library(RCurl) # API requests
library(jsonlite) # JSON coding/decoding

# URL for GNRS API
url = "https://tnrsapidev.xyz/tnrs_api.php"

# Read in example file of political division names
# See the BIEN website for details on how to organize input file:
# http://bien.nceas.ucsb.edu/bien/tools/gnrs/gnrs-input-format/
data <- read.csv("tnrs_testfile.csv", header=FALSE)

# Inspect the input
head(data,10)

# Uncomment this to work with smaller sample of the data
#data <- head(data,10)

# Convert the data to JSON
data_json <- jsonlite::toJSON(unname(data))

#################################
# Example 1: Resolve mode
#################################

# Set the TNRS options
sources <- "tpl,gcc,ildis,tropicos,usda"	# Taxonomic sources
class <- "tropicos"										# Family classification
mode <- "resolve"										# Processing mode

# Convert the options to data frame and then JSON
opts <- data.frame(c(sources),c(class), c(mode))
names(opts) <- c("sources", "class", "mode")
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

# Clean up results  
results<-gsub(pattern = '"',replacement = "",x = results)
results<-as.data.frame(results,stringsAsFactors = F) 
colnames(results) <- as.character(results[1,]) 
results <- results[-1,] 
rownames(results) <- NULL	  

# Inspect the results
head(results, 10)

# That's a lot of columns. Let's display header plus two rows vertically
# to get a better understanding of the output fields
results.t <- as.data.frame( t( results[,1:ncol(results)] ) )
results.t[,1:3,drop =FALSE]

# Make new data frame of best matches only
results.best <- results[results$Overall_score_order==1, ]
results.best$match.score <- format(round(as.numeric(results.best$Overall_score),2), nsmall=2)

# Compare name submitted, name matched and final accepted name
results.best[ , c('Name_submitted', 'match.score', 'Name_matched', 'Taxonomic_status', 'Accepted_name')]

#################################
# Example 2: Parse mode
#################################

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

# Clean up results  
results<-gsub(pattern = '"',replacement = "",x = results)
results<-as.data.frame(results,stringsAsFactors = F) 
colnames(results) <- as.character(results[1,]) 
results <- results[-1,] 
rownames(results) <- NULL	  

# Inspect the results
head(results, 10)

# Display header and three rows vertically
results.t <- as.data.frame( t( results[,1:ncol(results)] ) )
results.t[,9:11,drop =FALSE]
