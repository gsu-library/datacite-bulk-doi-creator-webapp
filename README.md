# DataCite Bulk DOI Creator - WebApp
Code Repository: https://github.com/gsu-library/datacite-bulk-doi-creator-webapp  
Author: Matt Brooks <mbrooks34@gsu.edu>  
Date Created: 2022-06-29  
License:  
Version: 0.1.0

## Description
A PHP WebApp that bulk creates DataCite DOIs from a provided CSV file. DOIs are created in the findable state. If you are looking for the python version of this WebApp see [DataCite Bulk DOI Creator](https://github.com/gsu-library/datacite-bulk-doi-creator).

For more information about DOIs please see DataCite's [support page](https://support.datacite.org/) and/or resources from their [homepage](https://doi.datacite.org/). Information on their [metadata schemas](https://schema.datacite.org/) is also available.

## Setup
Put the repository files in a folder that is within Apache's webroot.

### Configuration
Rename config/config.sample.ini to config/config.ini and fill in your DOI prefix, username (repository ID), and password. If wanting to test the script out with the test DataCite API replace the URL with the test API URL (https://api.test.datacite.org/dois) and credentials. There are other configuration options that can be adjusted if wanted. **It is important that the config folder and its contents is not readable through a web browser. If not using Apache, the config/.htaccess file should be replaced with something denying web access to the contents of the folder.**

PHP will also need read/write access to both the reports and uploads folders. Make sure owner/group permissions are set accordingly.

### Authentication
Currently this application uses basic authentication provided by Apache (see [Apache AuthType directive](https://httpd.apache.org/docs/2.4/mod/mod_authn_core.html#authtype)). To use basic authentication [create a .htpasswd file](https://httpd.apache.org/docs/2.4/programs/htpasswd.html) within the config directory, rename .htaccess.sample to .htaccess in the root folder, and set the AuthUserFile directive to the absolute path of the .htpasswd file. The .htpasswd file does not have to live in the config folder, but wherever it lives should not be accessible from the web.

## Usage
The 'Download CSV Template' menu item (template.csv) provides an example of valid headers this WebApp accepts (also see the fields below). Only one set of creator fields are required per record. Once a filled out CSV file is uploaded and submitted it will be processed. During proccessing, a copy of the uploaded file will be saved in the uploads folder and a report will be created in the reports folder.

### CSV Fields
doi_suffix - DOI suffix  
title - title of publication  
year - publication year  
type - resource type  
description - abstract description  
publisher - publisher  
source_url - URL reference to resource  
creator{n} - full creator name (header example: creator1, creator2, etc.)  
creator{n}_type - Personal or Organizational  
creator{n}_given - creator given name  
creator{n}_family - creator family name  

### Errors
If an error occurs a verbose message will be logged on the page output and in the generated report. For more information on error codes please see DataCite's [API error code page](https://support.datacite.org/docs/api-error-codes).

## Dependencies
- [Bootstrap](https://getbootstrap.com/) v4.6.1
- [jQuery](https://jquery.com/) slim v3.5.1
- [PHP](https://www.php.net/) v7.0
- [PHP cURL](https://www.php.net/manual/en/book.curl.php)
