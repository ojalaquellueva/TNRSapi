###############################################
# Example use of TNRS API
###############################################

# Load libraries
library(RCurl) # API requests
#library(rjson) # JSON coding/decoding
library(jsonlite) # JSON coding/decoding

# URL for GNRS API
url = "https://tnrsapidev.xyz/tnrs_api.php"

# Read in example file of political division names
# See the BIEN website for details on how to organize a GNRS Batch Mode input file:
# http://bien.nceas.ucsb.edu/bien/tools/gnrs/gnrs-input-format/
fulldata <- read.csv("tnrs_testfile.csv", header=FALSE)

# Inspect the input
head(fulldata,10)

# Let's start with a small sample, say fives lines, of the original data
data <- head(fulldata,5)

# Convert the data to JSON
data_json <- toJSON(unname(data))

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
opts_json <- toJSON(opts)
opts_json <- gsub('\\[','',opts_json)
opts_json <- gsub('\\]','',opts_json)

# Combine the options and data into single JSON object
input_json <- paste0('{"opts":', opts_json, ',"data":', data_json, '}' )

# Construct the request
headers <- list('Accept' = 'application/json', 'Content-Type' = 'application/json', 'charset' = 'UTF-8')

# Send the API request
results_json <- postForm(url, .opts=list(postfields= input_json, httpheader=headers))

# Convert JSON file to a data frame
results <- fromJSON(results_json)
#results <- as.data.frame(do.call(rbind,as.list(results)))

# Inspect the results
head(results, 10)

# That's a lot of columns. Let's display header plus two rows vertically
# to get a better understanding of the output fields
results.t <- t(results[,1:ncol(results)])
results.t[,1:3,drop =FALSE]

#################################
# Example 1: Parse mode
#################################

# Let's just parse the names instead
# All we need to do is reset option mode:
mode <- "parse"		

# Reform the options json again
opts <- data.frame(c(sources),c(class), c(mode))
names(opts) <- c("sources", "class", "mode")
opts_json <- toJSON(opts)
opts_json <- gsub('\\[','',opts_json)
opts_json <- gsub('\\]','',opts_json)

# Make the options + data JSON
input_json <- paste0('{"opts":', opts_json, ',"data":', data_json, '}' )

# Send the request again
results_json <- postForm(url, .opts=list(postfields= input_json, httpheader=headers))

results <- fromJSON(results_json)
#results <- as.data.frame(do.call(rbind,as.list(results)))

# Inspect the results
head(results, 10)

# Display header plus two rows vertically
results.t <- t(results[,1:ncol(results)])
results.t[,1:3,drop =FALSE]