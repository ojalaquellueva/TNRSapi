# TNRS API

## I. Introduction

An API wrapper for the TNRSbatch command line application. TNRSbatch is a command line adaption of the original iPlant TNRS (Boyle 2013; `http://tnrs.iplantcollaborative.org/`). See `https://github.com/iPlantCollaborativeOpenSource/TNRS` for information on the original TNRS. See `https://github.com/ojalaquellueva/TNRSbatch` for information on TNRSbatch. Also see Mozzherin 2008 and Rees, T. 2014.

## II. Dependencies
* **TNRS MySQL database**
   * Version 4.0 (tnrs4)
   * See `tnrs3_db_scripts/` in `https://github.com/iPlantCollaborativeOpenSource/TNRS`
* **TNRSbatch**
   * Must use fork:  
    `https://github.com/ojalaquellueva/TNRSbatch`.
* **GN parser** 
   * Version: 'biodiversity'
   * Repo: `https://github.com/GlobalNamesArchitecture/biodiversity`
   * Run as socket server. See `https://github.com/ojalaquellueva/TNRSbatch` for details.

## III. Required OS and software

* Ubuntu 18.04.2 LTS
* Perl 5.26.1
* PHP 7.2.19
* MySQL 5.7.26
* Apache 2.4.29
* Makeflow 4.0.0-RELEASE (released 02/06/2018)
* Ruby 2.5.1p57

(Not tested on earlier versions)

PHP extensions:
  * php-cli
  * php-mbstring
  * php-curl
  * php-xml
  * php-json
  * php-services-json

### IV. API Setup

#### 1.Create the following directory structure under /var/www/:

tnrs
├── api
├── data
└── tnrs_batch

* Command line application tnrs_batch may be run from other locations. You will need to adjust API parameters and Virtual Host directives accordingly.

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

##### 5. Adjust permissions

```
sudo chown -R $USER /var/www/tnrs/api
sudo chgrp -R tnrs /var/www/tnrs/api
sudo chmod -R 774 /var/www/tnrs/api
sudo chmod -R g+s /var/www/tnrs/api
```

Repeat for directories tnrs_batch and data.

#### 6. Set up Apache Virtual Host with /var/www/tnrs/api as DocumentRoot
* Example: `https://www.digitalocean.com/community/tutorials/how-to-set-up-apache-virtual-hosts-on-ubuntu-16-04`

#### 7. Set up SSL if desired
* Example using Let's Encrypt: `https://www.digitalocean.com/community/tutorials/how-to-secure-apache-with-let-s-encrypt-on-ubuntu-16-04`

#### 8. Start GN parser as socket server

```
cd /var/www/tnrs/tnrs_batch
nohup parserver &
<ctrl>+C
```

## V. Usage

#### API test script

Example syntax for interacting with API using php_curl is given in tnrs_api_example.php. To run the test script:

```
php tnrs_api_example.php
```
* Adjust parameters as desired in file params.php
* Also see API parameters section at start of tnrs_api_example.php
* For TNRS options and defaults, see params.php
* Make sure that test file (testfile.csv) is available in $DATADIR (as set in params.php)

## VI. References
﻿Boyle, B., N. Hopkins, Z. Lu, J. A. Raygoza Garay, D. Mozzherin, T. Rees, N. Matasci, M. L. Narro, W. H. Piel, S. J. Mckay, S. Lowry, C. Freeland, R. K. Peet, and B. J. Enquist. 2013. The taxonomic name resolution service: An online tool for automated standardization of plant names. BMC Bioinformatics 14(1):16.

Mozzherin, D. Y. 2008. GlobalNamesArchitecture/biodiversity: Scientific Name Parser. https:// github.com/GlobalNamesArchitecture/biodiversity. Accessed 15 Sep 2017

Rees, T. 2014. Taxamatch, an Algorithm for Near ('Fuzzy’) Matching of Scientific Names in Taxonomic Databases. PloS one 9(9): e107510.
