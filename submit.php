<?php
session_start();
require_once('includes'.DIRECTORY_SEPARATOR.'functions.php');
load_config_file();


$_SESSION['output'] = [];
const DEBUG = false;


// Check if all needles exist in haystack.
function in_array_all($needles, $haystack) {
   return empty(array_diff($needles, $haystack));
}


// Removes oldest files from directory.
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


// Returns a valid file name.
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


// Check CSRF token.
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

// Check for PHP cURL.
if(!function_exists('curl_init')) {
   array_push($_SESSION['output'], 'Please install/enable the PHP cURL library.');
   go_home();
}

// Check upload and move it to folder.
if($uploadFileName = $_FILES['fileUpload']['name'] ?? null) {
   if($_FILES['fileUpload']['size'] > CONFIG['maxUploadSize']){
      array_push($_SESSION['output'], 'The uploaded file is too large.');
      go_home();
   }

   if(!($uploadFullFilePath = find_file_name('uploads'.DIRECTORY_SEPARATOR.$uploadFileName))) {
      array_push($_SESSION['output'], 'There was an error saving the uploaded file.');
      go_home();
   }

   echo '<pre>'.print_r($_FILES['fileUpload'], true).'</pre>';

   move_uploaded_file($_FILES['fileUpload']['tmp_name'], $uploadFullFilePath);
   remove_old_files('uploads'.DIRECTORY_SEPARATOR.'*.csv', CONFIG['maxSubmittedFiles']);
}
else {
   array_push($_SESSION['output'], 'Could not find uploaded file.');
   go_home();
}

// Open the uploaded file.
if(!$uploadFp = fopen($uploadFullFilePath, 'r')) {
   array_push($_SESSION['output'], 'There was an error opening the uploaded file.');
   go_home();
}


// Save file headers.
if(($headers = fgetcsv($uploadFp)) === false) {
   array_push($_SESSION['output'], 'No data was found in the uploaded file.');
   go_home();
}

// Trim and lowercase headers in CSV file.
$headers = array_map(function($header){
   return strtolower(trim($header));
}, $headers);

// Make sure CSV file has all required headers.
$requiredHeaders = [
   'doi_suffix',
   'title',
   'year',
   'type',
   'description',
   'publisher',
   'source_url',
   'creator1',
   'creator1_type',
   'creator1_given',
   'creator1_family',
];

if(!in_array_all($requiredHeaders, $headers)) {
   array_push($_SESSION['output'], 'The uploaded CSV file is missing required headers.');
   go_home();
}

// Find how many creator headers are present.
$creatorHeaders = [];
$i = 1;

while(in_array('creator'.$i, $headers)) {
   array_push($creatorHeaders, 'creator'.$i++);
}

$fileData = [];

// Retrieve the rest of the file.
while(($row = fgetcsv($uploadFp)) !== false) {
   $fileData[] = array_combine($headers, $row);
}

fclose($uploadFp);

// Setup cURL.
$ch = curl_init();

curl_setopt_array($ch, [
   CURLOPT_URL => CONFIG['url'],
   CURLOPT_POST => true,
   CURLOPT_RETURNTRANSFER => true,
   CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
   CURLOPT_USERPWD => CONFIG['username'].':'.CONFIG['password'],
   CURLOPT_HTTPHEADER => [
      'Content-Type: application/vnd.api+json'
   ]
]);


$fileParts = pathinfo($uploadFullFilePath);
$fileName = $fileParts['filename'];

// Open a file for the upload report.
if(!($reportFullFilePath = find_file_name('reports'.DIRECTORY_SEPARATOR.basename($fileName).' report.csv'))) {
   array_push($_SESSION['output'], 'There was an error saving the report file.');
   go_home();
}

if(!$reportFp = fopen($reportFullFilePath, 'w')) {
   array_push($_SESSION['output'], 'Cannot write to the reports folder.');
   go_home();
}

// Save report path to add link on index page.
$_SESSION['reportPath'] = $reportFullFilePath;

// Add headers to upload report file.
fputcsv($reportFp, ['doi_suffix', 'doi_url', 'status', 'error']);

// Process file data and create report.
$proccessedCsv = [];
foreach($fileData as $row) {
   $doi = CONFIG['doiPrefix'].'/'.$row['doi_suffix'];
   $creators = [];

   // Process multiple creators.
   foreach($creatorHeaders as $x) {
      if(!empty($row[$x])) {
         array_push($creators, [
            'name' => $row[$x],
            'nameType' => $row[$x.'_type'],
            'givenName' => $row[$x.'_given'],
            'familyName' => $row[$x.'_family']
         ]);
      }
   }

   $submission = [
      'data' => [
         'id' => $doi,
         'type' => 'dois',
         'attributes' => [
            'event' => 'publish',
            'doi' => $doi,
            'creators' => $creators,
            'titles' => [
               'title' => $row['title']
            ],
            'publisher' => $row['publisher'],
            'publicationYear' => $row['year'],
            'descriptions' => [
               'description' => $row['description'],
               'descriptionType' => 'Abstract'
            ],
            'types' => [
               'resourceTypeGeneral' => 'Text',
               'resourceType' => $row['type']
            ],
            'schemaVersion' => 'http://datacite.org/schema/kernel-4',
            'url' => $row['source_url']
         ]
      ]
   ];

   // Submit data.
   $data = json_encode($submission, JSON_INVALID_UTF8_IGNORE);
   curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
   $result = json_decode(curl_exec($ch), true);
   $error = $result['errors'][0]['title'] ?? '';
   fputcsv($reportFp, [$row['doi_suffix'], 'https://doi.org/'.$doi, curl_getinfo($ch, CURLINFO_HTTP_CODE), $error]);

   if($error) {
      $error = ', '.$error;
   }

   array_push($_SESSION['output'], '- submitted doi suffix '.$row['doi_suffix'].' with status of '.curl_getinfo($ch, CURLINFO_HTTP_CODE).$error);

   if($error = curl_error($ch)) {
      array_push($_SESSION['output'], $error);
   }
}

fclose($reportFp);
remove_old_files('reports'.DIRECTORY_SEPARATOR.'*.csv', CONFIG['maxReportFiles']);
curl_close($ch);

if(!DEBUG) {
   go_home();
}
