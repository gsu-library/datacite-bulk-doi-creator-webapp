<?php
session_start();
$_SESSION['output'] = [];
const DEBUG = false;
//TODO: Add CSV options to config
//TODO: make sure to trim and lowercase headers in upload file


// Check if all needles exist in haystack.
function in_array_all($needles, $haystack) {
   return empty(array_diff($needles, $haystack));
}


// Removes oldest files from directory.
function remove_old_files($filePattern, $maxFileCount) {
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


// Go back to the index page.
function go_back() {
   header('location: .');
   exit;
}


// Returns a valid file name.
function find_file_name($fileName, $maxCount) {
   if(!file_exists($fileName)) {
      return $fileName;
   }

   $fileParts = pathinfo($fileName);

   for($i = 1; $i < $maxCount; $i++) {
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
      go_back();
   }
   else {
      if($_POST['csrfToken'] !== $_SESSION['csrfToken']) {
         array_push($_SESSION['output'], 'The CSRF token is invalid.');
         go_back();
      }
      else {
         unset($_SESSION['csrfToken']);
      }
   }
}

// Load configuration file.
if(!$config = parse_ini_file('config'.DIRECTORY_SEPARATOR.'config.ini')) {
   array_push($_SESSION['output'], 'Could not load the configuration file.');
   go_back();
}

// Check for PHP cURL.
if(!function_exists('curl_init')) {
   array_push($_SESSION['output'], 'Please install/enable the PHP cURL library.');
   go_back();
}

//TODO: Set max file size in configuration and check it here and on the form.
if($uploadFileName = $_FILES['fileUpload']['name'] ?? null) {
   if(!($uploadFullFilePath = find_file_name('uploads'.DIRECTORY_SEPARATOR.$uploadFileName, $config['maxSubmittedFiles']))) {
      array_push($_SESSION['output'], 'There was an error saving the uploaded file.');
      go_back();
   }

   move_uploaded_file($_FILES['fileUpload']['tmp_name'], $uploadFullFilePath);
   remove_old_files('uploads'.DIRECTORY_SEPARATOR.'*.csv', $config['maxSubmittedFiles']);
}
else {
   array_push($_SESSION['output'], 'There was an error saving the uploaded file.');
   go_back();
}

// Open the uploaded file.
if(!$uploadFp = fopen($uploadFullFilePath, 'r')) {
   array_push($_SESSION['output'], 'There was an error opening the uploaded file.');
   go_back();
}


// Save file headers.
if(($headers = fgetcsv($uploadFp)) === false) {
   array_push($_SESSION['output'], 'No data was found in the uploaded file.');
   go_back();
}

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
   go_back();
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
   CURLOPT_URL => $config['url'],
   CURLOPT_POST => true,
   CURLOPT_RETURNTRANSFER => true,
   CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
   CURLOPT_USERPWD => $config['username'].':'.$config['password'],
   CURLOPT_HTTPHEADER => [
      'Content-Type: application/vnd.api+json'
   ]
]);

// Open a file for the upload report.
if(!($reportFullFilePath = find_file_name('reports'.DIRECTORY_SEPARATOR.'report-'.basename($uploadFullFilePath), $config['maxReportFiles']))) {
   array_push($_SESSION['output'], 'There was an error saving the report file.');
   go_back();
}

if(!$reportFp = fopen($reportFullFilePath, 'w')) {
   array_push($_SESSION['output'], 'Cannot write to the reports folder.');
   go_back();
}

// Save report path to add link on index page.
$_SESSION['reportPath'] = $reportFullFilePath;

// Add headers to upload report file.
fputcsv($reportFp, ['doi_suffix', 'doi_url', 'status', 'error']);

// Process file data and create report.
$proccessedCsv = [];
foreach($fileData as $row) {
   $doi = $config['doiPrefix'].'/'.$row['doi_suffix'];
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
remove_old_files('reports'.DIRECTORY_SEPARATOR.'*.csv', $config['maxReportFiles']);
curl_close($ch);

if(!DEBUG) {
   go_back();
}
