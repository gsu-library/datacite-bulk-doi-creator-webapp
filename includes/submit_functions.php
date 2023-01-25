<?php
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
      'creator1_type', // TODO: will depend on orchid id
                     // don't require and assume personal?
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
