# TNRSbatch API

## Contents

[Introduction](#introduction)  
[Dependencies](#dependencies)  
[Required OS and software](#software)  
[Setup & configuration](#setup)  
[Usage](#usage)  
[Example scripts](#examples)  
[Related applications](#related)  
[References](#references)  

<a name="introduction"></a>
## Introduction

TNRSbatch API is an API wrapper for TNRSbatch, a command line adaption of the  [Taxonomic Name Resolution Service (TNRS) web interface](http://tnrs.iplantcollaborative.org/) (Boyle 2013). For information on TNRSbatch, see the [TNRSbatch repo](https://github.com/ojalaquellueva/TNRSbatch). For information on the original TNRS code base, see <https://github.com/iPlantCollaborativeOpenSource/TNRS>. Also see [References](#references) for information on component applications GN Parser (Mozzherin 2008) and Taxamatch (Rees 2014). 

R users may prefer to use the [RTNRS R package](https://github.com/EnquistLab/RTNRS), which queries the [BIEN instance of TNRSbatch](https://bien.nceas.ucsb.edu/bien/tools/tnrs/) via the TNRS API.

<a name="dependencies"></a>
## Dependencies
* **TNRS MySQL database**
   * Repo `https://github.com/ojalaquellueva/tnrs_db`
* **TNRSbatch**
   * Must use fork:  
    [https://github.com/ojalaquellueva/TNRSbatch](ttps://github.com/ojalaquellueva/TNRSbatch)
* **GN parser** 
   * Version: 'biodiversity'
   * Repo: [https://github.com/GlobalNamesArchitecture/biodiversity](https://github.com/GlobalNamesArchitecture/biodiversity)
   * Run as socket server. See [https://github.com/ojalaquellueva/TNRSbatch](https://github.com/ojalaquellueva/TNRSbatch) for details.

<a name="software"></a>
## Required OS and software
* Ubuntu 18.04.2 LTS
* Perl 5.26.1
* PHP 7.2.19
* MySQL 5.7.26
* Apache 2.4.29
* Makeflow 4.0.0 (released 02/06/2018)
* Ruby 2.5.1p57
(Not tested on earlier versions)

PHP extensions:
  * php-cli
  * php-mbstring
  * php-curl
  * php-xml
  * php-json
  * php-services-json


<a name="setup"></a>
## Setup & configuration

#### 1. Create the following directory structure under /var/www/:

```
tnrs  
|__api  
|__data  
|__tnrs_batch  
```

* Command line application `tnrs_batch` may be run from other locations. Adjust API parameters and Virtual Host directives accordingly.

#### 2. Download contents of this respository to api:

```
git clone --depth=1 --branch=master https://github.com/ojalaquellueva/tnrsapi.git /var/www/tnrs/api
cd /var/www/tnrs/tnrs_batch
rm -rf .git
```

#### 3. Download contents of TNRSbatch repository to tnrs_batch:

```
git clone --depth=1 --branch=master https://github.com/ojalaquellueva/TNRSbatch.git /var/www/tnrs/tnrs_batch
cd /var/www/tnrs/tnrs_batch
rm -rf .git
```

#### 4. Copy test file to data directory

```
cp /var/www/tnrs/api/example_data/testfile.csv /var/www/tnrs/data/
```

#### 5. Adjust permissions

```
sudo chown -R $USER /var/www/tnrs/api
sudo chgrp -R www-data /var/www/tnrs/api
sudo chmod -R 774 /var/www/tnrs/api
sudo chmod -R g+s /var/www/tnrs/api
```

Repeat for directories `tnrs_batch` and `data`.

#### 6. Set up Apache Virtual Host with /var/www/tnrs/api as DocumentRoot
* Tutorial:   
   [https://www.digitalocean.com/community/tutorials/how-to-set-up-apache-virtual-hosts-on-ubuntu-16-04](https://www.digitalocean.com/community/tutorials/how-to-set-up-apache-virtual-hosts-on-ubuntu-16-04)

#### 7. Set up SSL
* Tutorial (using Let's Encrypt):  
  [https://www.digitalocean.com/community/tutorials/how-to-secure-apache-with-let-s-encrypt-on-ubuntu-16-04](https://www.digitalocean.com/community/tutorials/how-to-secure-apache-with-let-s-encrypt-on-ubuntu-16-04).

#### 8. Build tnrs database or install from mysqldump
* Create and populate the database
* Create MySQL user tnrs with read-only access on the TNRS database
* Use repo [`https://github.com/ojalaquellueva/tnrs_db`](https://github.com/ojalaquellueva/tnrs_db)
* Do not use [`https://github.com/iPlantCollaborativeOpenSource/TNRS/tree/master/tnrs3_db_scripts`](https://github.com/iPlantCollaborativeOpenSource/TNRS/tree/master/tnrs3_db_script) (deprecated).

#### 9. Update TNRS database, user and password parameters in TNRSbatch config file
* See [https://github.com/ojalaquellueva/TNRSbatch](https://github.com/ojalaquellueva/TNRSbatch)

#### 10. Start parser as socket server

```
cd /var/www/tnrs/tnrs_batch
nohup parserver &
<ctrl>+C
```

<a name="usage"></a>
## Usage

#### Input data

Input data should be organized as a UTF-8 CSV file with two columns. The first column must be an integer ID that uniquely identifies each row. The second column is the taxon name, with or without authorities. Optionally, the family name may be prepended to the taxon name (separated by a whitespace, NOT a comma) to help distinguish between homonyms (identical names applied to different taxa) in different families. Family name, if included, MUST end in -aceae or it will prevent matching. The first letter of the genus name must be capitalized for the parser to recognize it as a genus. Below is an example of a properly formated input file:

    1,Arecaceae Mauritia
    2,Solanaceae Solanum bipatens Dunal
    3,Arecaceae Leopoldinia pulchra Mart.
    4,Melastomataceae Leandra schenckii
    5,Piper arboreum Aubl.
    6,Poaceae Pseudochaetochloa australiensis Hitchc.
    7,Juglandaceae Engelhardia spicata var. colebrookeana (Lindl. ex Wall. ) Koord. & Valeton
    8,Melastomataceae Miconia sp.1
    
Input data must be converted to JSON and combined as element "data" with the TNRS options (element "opts"; see Options, below). The combined JSON object is sent to the API as POST data in the request body. The scripts below provide examples of how to do this in PHP and R. 

#### Options

The API accepts the following TNRS options, which must be converted to JSON and combined as element "opts" along with the data (element "data") in the request body POST data.


| Option | Meaning | Value(s) | Default | Notes |
| ------ | ------- | -------- | ------ | -----|
| sources | Taxonomic sources | tpl,gcc,ildis,tropicos,usda,ncbi | tpl,gcc,ildis,tropicos,usda | Can be combined, with comma delimiters
| class | Family classification | tropicos,ncbi | tropicos | tropicos is euqivalent to APG III
| mode | Processing mode | resolve,parse | resolve | Parse-only mode separates name components. Resolve mode parses, matches to a published name and resolves synonyms to accepted name.
| matches | Matches to return | best,all | best | Return either the single best match to a name, or all matches above the minimum match threshold


<a name="examples"></a>
## Example scripts

All example scripts are in the `example_scripts/` subdirectory.

#### R

* `tnrs_api_example.R` demonstrates the individual components needed to build calls to the TNRS API from scratch
* `tnrs_api_example2.R` demonstrates a more efficient function-based approach to calling the TNRS API

#### Linux shell (bash)

* `tnrsapi.sh` is a standalone bash executable for calling the TNRS API. 
* Used without options, it will submit a small set of example names (provided internally by the script) using the current production TNRS API at tnrsapi.xyz. Results are display in the terminal without saving to the filesystem.
* It also accepts command line options that can be used to set the input and output files (i.e., save to filesystem), the API endpoint, and most standard TNRS API options, such as taxonomic sources, best match vs. all matches, etc.
* Currently only modes "resolve" and "parse" are supported

##### Usage:

```
$ tnrsapi.sh [-q|--quiet] [-v|--verbose] [-f </absolute/path/and/inputfilename.csv>] [-o </absolute/path/and/outputfilename.csv>] [-u <tnrs_api_url>] [-m|--mode {resolve|parse}] [-s|--sources <taxonomic,sources,comma,delimited>] [-c|--class <familyclassificationsourcename>] [-a|--allmatches] [-k|--keep_files]
```

##### Options:
* All arguments are optional

Option | Purpose | Followed by value | Default | Notes
------ | ------- | -------------- | ------- | -----
-q  | quiet | [none] | abbreviated input/output echo | 
-v  | verbose | [none] | abbreviated input/output echo |
-f  | input file | file path and name | nothing saved | 
-o  | output file | file path and name | nothing saved | 
-u  | endpoint url | TNRS API endpoint url |  https://tnrsapi.xyz | Omit "/tnrs_api.php" 
-m  | api mode | resolve|parse | resolve | other API options not currently supported
-s  | taxonomic sources | One or more of: wfo,wcvp,cact | wfo | Available sources may change
-c  | family classification source | One of: wfo,wcvp | wfo | Available source may change
-a  | all matches | [none] | Best match only if omitted | 
-k  | keep files | [none] | nothing saved if omitted | Writes both input and output as CSV files

##### Example:
* The example below specific input and output files, taxonomic sources wcvp and cact, all matches and runs in verbose mode

```
$ tnrsapi.sh -f /home/boyle/tnrs/data/example_names.csv -o /home/boyle/tnrs/data/example_names_resolved.csv -a -s wcvp,cact -v
```
##### Installation:
* Run from same directory as script only: `./tnrsapi.sh`
* Place in executable directory of your choice and include path in environment to run from anywhere


<a name="related"></a>
## Related applications
* **TNRS batch:**  
  [https://github.com/ojalaquellueva/TNRSbatch](https://github.com/ojalaquellueva/TNRSbatch)
* **TNRS database:**   
  [https://github.com/ojalaquellueva/tnrs_db](https://github.com/ojalaquellueva/tnrs_db)
* **RTNRS R package:**  
﻿ [https://github.com/EnquistLab/RTNRS](https://github.com/EnquistLab/RTNRS)
* **GN parser:**   
   [https://github.com/GlobalNamesArchitecture/biodiversity](https://github.com/GlobalNamesArchitecture/biodiversity)

<a name="references"></a>
## References
﻿Boyle, B., N. Hopkins, Z. Lu, J. A. Raygoza Garay, D. Mozzherin, T. Rees, N. Matasci, M. L. Narro, W. H. Piel, S. J. Mckay, S. Lowry, C. Freeland, R. K. Peet, and B. J. Enquist. 2013. The taxonomic name resolution service: An online tool for automated standardization of plant names. BMC Bioinformatics 14(1):16.

Mozzherin, D. Y. 2008. GlobalNamesArchitecture/biodiversity: Scientific Name Parser. https:// github.com/GlobalNamesArchitecture/biodiversity. Accessed 15 Sep 2017

Rees, T. 2014. Taxamatch, an Algorithm for Near ('Fuzzy’) Matching of Scientific Names in Taxonomic Databases. PloS one 9(9): e107510.
