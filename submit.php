<?php
session_start();
$_SESSION['output'] = [];
const DEBUG = false;
//TODO: Add CSV options to config
//TODO: check doi url in report for /
//TODO: send upload report to user
//TODO: add file name incrementer, use file_exists


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
function goBack() {
   header('location: .');
   exit;
}


// Check CSRF token.
if(!DEBUG) {
   if(!isset($_POST['csrfToken']) || !isset($_SESSION['csrfToken'])) {
      array_push($_SESSION['output'], 'CSRF token not found.');
      goBack();
   }
   else {
      if($_POST['csrfToken'] !== $_SESSION['csrfToken']) {
         array_push($_SESSION['output'], 'The CSRF token is invalid.');
         goBack();
      }
      else {
         unset($_SESSION['csrfToken']);
      }
   }
}

// Load configuration file.
if(!$config = parse_ini_file('config/config.ini')) {
   array_push($_SESSION['output'], 'Could not load the configuration file.');
   goBack();
}

// Check for PHP cURL.
if(!function_exists('curl_init')) {
   array_push($_SESSION['output'], 'Please install/enable the PHP cURL library.');
   goBack();
}

//TODO: Set max file size in configuration and check it here and on the form.
if($uploadFileName = $_FILES['fileUpload']['name'] ?? null) {
   $uploadFullFilePath = 'uploads/'.$uploadFileName;

   if(file_exists($uploadFullFilePath)) {
      $uploadFullFilePath = 'uploads/'.pathinfo($uploadFileName, PATHINFO_FILENAME).'.'.date('Ymd-His').'.'.pathinfo($uploadFileName, PATHINFO_EXTENSION);
   }

   move_uploaded_file($_FILES['fileUpload']['tmp_name'], $uploadFullFilePath);
   remove_old_files('uploads/*.csv', $config['maxSubmittedFiles']);
}
else {
   array_push($_SESSION['output'], 'There was an error saving the uploaded file.');
   goBack();
}

// Open the uploaded file.
if(!$uploadFp = fopen($uploadFullFilePath, 'r')) {
   array_push($_SESSION['output'], 'There was an error opening the uploaded file.');
   goBack();
}


// Save file headers.
if(($headers = fgetcsv($uploadFp)) === false) {
   array_push($_SESSION['output'], 'No data was found in the uploaded file.');
   goBack();
}

//TODO: move to configuration file?
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
   goBack();
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
if(!$reportFp = fopen('reports/report-'.basename($uploadFullFilePath), 'w')) {
   if(!$reportFp = fopen('reports/report-'.rtrim($uploadFileName, '.csv').'.'.date('Ymd-His').'.csv', 'w')) {
      array_push($_SESSION['output'], 'Cannot write to the reports folder.');
      goBack();
   }
}

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
   array_push($_SESSION['output'], 'submitted doi suffix '.$row['doi_suffix'].', with status of '.curl_getinfo($ch, CURLINFO_HTTP_CODE).', '.$error);

   if($error = curl_error($ch)) {
      array_push($_SESSION['output'], $error);
   }
}

fclose($reportFp);
remove_old_files('reports/*.csv', $config['maxReportFiles']);
curl_close($ch);
if(!DEBUG) {
   goBack();
}
