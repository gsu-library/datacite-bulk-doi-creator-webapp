<?php
/**
 * Check if all needles exist in haystack.
 *
 * @param array   $needles    Array of values to search for.
 * @param array   $haystack   Array of values to search in.
 * @return bool
 */
function in_array_all($needles, $haystack) {
   return empty(array_diff($needles, $haystack));
}


/**
 * Remove oldest files from directory.
 *
 * @param string  $filePattern   File name pattern to search for.
 * @param integer $maxFileCount  Max number of files to keep.
 * @return void
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
 * @param string $fileName Desired file name.
 * @return string|null
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
 * Check for PHP cURL.
 *
 * //TODO: Add more checks here (file write, etc.)
 *
 * @return void
 */
function check_capabilities() {
   if(!function_exists('curl_init')) {
      array_push($_SESSION['output'], 'Please install/enable the PHP cURL library.');
      go_home();
   }
}


/**
 * Validates CSRF token.
 *
 * @return void
 */
function validate_csrf_token() {
   if(!DEBUG) {
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
}
