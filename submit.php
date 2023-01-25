<?php
session_start();
require_once('includes'.DIRECTORY_SEPARATOR.'functions.php');
require_once('includes'.DIRECTORY_SEPARATOR.'submit_functions.php');
load_config_file();


$_SESSION['output'] = [];
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


check_capabilities();
validate_csrf_token();
$uploadFullPath = process_uploaded_file(); //TODO: var name


// Open the uploaded file.
if(!$uploadFp = fopen($uploadFullPath, 'r')) {
   array_push($_SESSION['output'], 'There was an error opening the uploaded file.');
   go_home();
}


//TODO: headers function?
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

// Find how many creator headers are present.
// TODO: does this go somewhere else?
$creatorHeaders = preg_grep('/^creator\d+$/', $headers);

/*************************************************************************************/

// Open file for reporting.
$reportFp = open_report_file($uploadFullPath);

// Add headers to upload report file.
fputcsv($reportFp, ['doi_suffix', 'doi_url', 'status', 'error']);


// Setup cURL.
$ch = curl_init();

curl_setopt_array($ch, [
   CURLOPT_SSL_VERIFYPEER => false, //TODO: for dev only
   CURLOPT_URL => CONFIG['url'],
   CURLOPT_POST => true,
   CURLOPT_RETURNTRANSFER => true,
   CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
   CURLOPT_USERPWD => CONFIG['username'].':'.CONFIG['password'],
   CURLOPT_HTTPHEADER => [
      'Content-Type: application/vnd.api+json'
   ]
]);


// Process the rest of the upload.
while(($row = fgetcsv($uploadFp)) !== false) {
   $row = array_combine($headers, $row);
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


curl_close($ch);
fclose($uploadFp);
fclose($reportFp);
remove_old_files('reports'.DIRECTORY_SEPARATOR.'*.csv', CONFIG['maxReportFiles']);
go_home();
