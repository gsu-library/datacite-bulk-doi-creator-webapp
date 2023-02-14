<?php
// TODO: should require config loaded?
/**
 * Check for PHP cURL and that both the reports and uploads directories are writable.
 *
 * @return void
 */
function check_capabilities() {
   $go_home = false;

   if(!function_exists('curl_init')) {
      array_push($_SESSION['output'], 'Please install/enable the PHP cURL library.');
      $go_home = true;
   }

   if(!is_writable('uploads')) {
      array_push($_SESSION['output'], 'The uploads directory is not writable.');
      $go_home = true;
   }

   if(!is_writable('reports')) {
      array_push($_SESSION['output'], 'The reports directory is not writable.');
      $go_home = true;
   }

   if($go_home) {
      go_home();
   }
}


/**
 * Validates CSRF token.
 *
 * @return void
 */
function validate_csrf_token() {
   if(!isset($_POST['csrfToken']) || !isset($_SESSION['csrfToken'])) {
      array_push($_SESSION['output'], 'CSRF token not found.');
      go_home();
   }
   else {
      if($_POST['csrfToken'] !== $_SESSION['csrfToken']) {
         array_push($_SESSION['output'], 'The CSRF token is invalid.');
         go_home();
      }
      else {
         unset($_SESSION['csrfToken']);
      }
   }
}


/**
 * Check if all needles exist in haystack.
 *
 * @param   array $needles    Array of values to search for.
 * @param   array $haystack   Array of values to search in.
 * @return  bool
 */
function in_array_all($needles, $haystack) {
   return empty(array_diff($needles, $haystack));
}


/**
 * Remove oldest files from directory.
 *
 * @param   string   $filePattern   File name pattern to search for.
 * @param   integer  $maxFileCount  Max number of files to keep.
 * @return  void
 */
function remove_old_files($filePattern, $maxFileCount) {
   if($maxFileCount < 1) {
      $maxFileCount = 1;
   }

   $files = glob($filePattern);
   $extraFiles = count($files) - $maxFileCount;

   if($extraFiles > 0) {
      // Sort by last modified ascending.
      usort($files, function($x, $y) {
         return filemtime($x) > filemtime($y);
      });

      for($i = 0; $i < $extraFiles; $i++) {
         unlink($files[$i]);
      }
   }
}


/**
 * Return a valid file name.
 *
 * @param   string      $fileName Desired file name.
 * @return  string|null A valid file name.
 */
function find_file_name($fileName) {
   if(!file_exists($fileName)) {
      return $fileName;
   }

   $fileParts = pathinfo($fileName);
   $fileCount = count(glob($fileParts['dirname'] . DIRECTORY_SEPARATOR . "*"));

   for($i = 1; $i <= $fileCount; $i++) {
      $tempName = $fileParts['dirname'] . DIRECTORY_SEPARATOR . $fileParts['filename'] . " ($i)." . $fileParts['extension'];

      if(!file_exists($tempName)) {
         return $tempName;
      }
   }

   return null;
}


/**
 * Process uploaded file. Checks for upload file, file size, and file name.
 *
 * @return string Full file path of the uploaded file.
 */
function process_uploaded_file() {
   // Check upload and move it to folder.
   if($fileName = $_FILES['fileUpload']['name'] ?? null) {
      if($_FILES['fileUpload']['size'] > CONFIG['maxUploadSize']){
         array_push($_SESSION['output'], 'The uploaded file is too large.');
         go_home();
      }

      if(!($filePath = find_file_name('uploads'.DIRECTORY_SEPARATOR.$fileName))) {
         array_push($_SESSION['output'], 'There was an error saving the uploaded file.');
         go_home();
      }

      move_uploaded_file($_FILES['fileUpload']['tmp_name'], $filePath);
      remove_old_files('uploads'.DIRECTORY_SEPARATOR.'*.csv', CONFIG['maxSubmittedFiles']);

      return $filePath;
   }
   else {
      array_push($_SESSION['output'], 'Could not find uploaded file.');
      go_home();
   }
}


/**
 * Processes the headers in the uploaded file. Checks to make sure all required headers are present.
 *
 * @param   resource $uploadFp File pointer to upload file.
 * @return  array    Array of normalized headers from the upload file.
 */
function process_upload_headers($uploadFp) {
   // Headers required to process the upload file.
   $requiredHeaders = [
      'doi_suffix',
      'title',
      'year',
      'type',
      'description',
      'publisher',
      'source_url',
      'creator1',
      'creator1_given',
      'creator1_family',
   ];

   // Save file headers.
   if(($headers = fgetcsv($uploadFp)) === false) {
      array_push($_SESSION['output'], 'No data was found in the uploaded file.');
      go_home();
   }

   // Trim and lowercase headers in CSV file.
   $headers = array_map(function($header) {
      return strtolower(trim($header));
   }, $headers);

   // Make sure CSV file has all required headers.
   // TODO: specify missing headers
   if(!in_array_all($requiredHeaders, $headers)) {
      array_push($_SESSION['output'], 'The uploaded CSV file is missing required headers.');
      go_home();
   }

   return $headers;
}


/**
 * Opens up a file to create a report. The file name is based on the submitted file.
 *
 * @param   string   $uploadFullPath Full path of the uploaded file.
 * @return  resource File pointer of opened report file.
 */
function open_report_file($uploadFullPath) {
   $fileParts = pathinfo($uploadFullPath);
   $fileName = $fileParts['filename'];

   // Open a file for the upload report.
   if(!($reportFullPath = find_file_name('reports'.DIRECTORY_SEPARATOR.basename($fileName).' report.csv'))) {
      array_push($_SESSION['output'], 'There was an error saving the report file.');
      go_home();
   }

   if(!$reportFp = fopen($reportFullPath, 'w')) {
      array_push($_SESSION['output'], 'Cannot write to the reports folder.');
      go_home();
   }

   // Save report path to add link on index page.
   $_SESSION['reportPath'] = $reportFullPath;

   return $reportFp;
}


/**
 * Creates and returns an array of creators based on the passed row.
 *
 * @param   array $creatorHeaders An array of the number of creator headers found in the submitted file.
 * @param   array $row The current row of data being processed.
 * @return  array A formatted array of creators for the row.
 */
function get_creators($creatorHeaders, $row) {
   // do we process multiple orcids?
   $creators = [];
   $tokenFile = 'config/orcid_token.json';

   // if $row orcid exists process that
   // find just the orcid? what if multiple authors?
   // orcid regex: (\d{4}-){3}\d{3}(\d|X)
   if(!empty($row['orcid'])) {
      if(!file_exists($tokenFile)) {
         if(!is_writable('config')) {
            array_push($_SESSION['output'], 'The config directory is not writable.');
            return [];
         }

         $tokenInfo = get_orcid_token($tokenFile);
      }
      else {
         $tokenInfo = json_decode(file_get_contents($tokenFile), true);
         // if token expired get_orcid_token($tokenFile);
      }

      $creators = get_orcid_name($row['orcid'], $tokenInfo['access_token']);

      // if token query oid

      // query_oid();

      // if token is bad, get token

      // if no token, get token

      // query oid

      // at this point if there are any problems push on output array oid has issues
   }
   else {
      // Process multiple creators.
      foreach($creatorHeaders as $x) {
         if(!empty($row[$x])) {
            // Make nameType optional.
            if(empty($row[$x.'_type']) || $row[$x.'_type'] !== 'Organizational') {
               $nameType = 'Personal';
            }
            else {
               $nameType = 'Organizational';
            }

            array_push($creators, [
               'name' => $row[$x],
               'nameType' => $nameType,
               'givenName' => $row[$x.'_given'],
               'familyName' => $row[$x.'_family']
            ]);
         }
      }
   }

   return $creators;
}


/**
 * Retrieves a public read token from Orcid, writes it to file, and returns an array of related data.
 *
 * @param   string   $tokenFile Location of the Orcid token file.
 * @return  array    The Orcid access token and related information.
 */
function get_orcid_token($tokenFile) {
   $ch = curl_init();
   $postFields = [
      'client_id' => CONFIG['orcidClientId'],
      'client_secret' => CONFIG['orcidSecret'],
      'grant_type' => 'client_credentials',
      'scope' => '/read-public'
   ];

   if(CONFIG['devMode']) {
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
   }

   curl_setopt_array($ch, [
      CURLOPT_URL => CONFIG['orcidTokenUrl'],
      CURLOPT_POST => true,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_HTTPHEADER => [
         'content-type: application/x-www-form-urlencoded'
      ],
      CURLOPT_POSTFIELDS => http_build_query($postFields)
   ]);

   $result = json_decode(curl_exec($ch), true);

   //TODO: check for error, if error push to output and return []

   unset($result['orcid']);
   $result['expires_on'] = $result['expires_in'] + time();
   curl_close($ch);

   // Save contents to file as JSON.
   file_put_contents($tokenFile, json_encode($result, JSON_PRETTY_PRINT));

   return $result;
}


function get_orcid_name($orcid, $token) {
   preg_match('/(\d{4}-){3}\d{3}(\d|X)/', $orcid, $matches);
   $apiUrl = CONFIG['orcidApiUrl'].'v3.0/'.$matches[0].'/personal-details';
   $creator = [];
   $ch = curl_init();

   if(CONFIG['devMode']) {
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
   }

   curl_setopt_array($ch, [
      CURLOPT_URL => $apiUrl,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_HTTPHEADER => [
         'content-type: application/orcid+json',
         'Authorization: Bearer '.$token,
      ]
   ]);

   $result = json_decode(curl_exec($ch), true);

   array_push($creator, [
      'name' => $result['name']['family-name']['value'].', '.$result['name']['given-names']['value'],
      'nameType' => 'Personal',
      'givenName' => $result['name']['given-names']['value'],
      'familyName' => $result['name']['family-name']['value']
   ]);

   return $creator;
}
