<?php
session_start();
require_once('includes'.DIRECTORY_SEPARATOR.'functions.php');
require_once('includes'.DIRECTORY_SEPARATOR.'submit_functions.php');
load_config_file();


$_SESSION['output'] = [];


check_capabilities();
if(!(CONFIG['devMode']) ?? false) { validate_csrf_token(); }
$uploadFullPath = process_uploaded_file();

// Open the uploaded file.
if(!$uploadFp = fopen($uploadFullPath, 'r')) {
   array_push($_SESSION['output'], 'There was an error opening the uploaded file.');
   go_home();
}

$headers = process_upload_headers($uploadFp);

// Find how many creator headers are present.
$creatorHeaders = preg_grep('/^creator\d+$/', $headers);

// Open file for reporting.
$reportFp = open_report_file($uploadFullPath);

// Add headers to upload report file.
fputcsv($reportFp, ['doi_suffix', 'doi_url', 'status', 'error']);

// Setup cURL.
$ch = curl_init();

if(CONFIG['devMode'] ?? false) {
   curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
}

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


// Process the rest of the upload.
while(($row = fgetcsv($uploadFp)) !== false) {
   $row = array_combine($headers, $row);
   $doi = $row['doi_prefix'].'/'.$row['doi_suffix'];
   $creators = get_creators($creatorHeaders, $row);

   $submission = [
      'data' => [
         'id' => $doi,
         'type' => 'dois',
         'attributes' => [
            'prefix' => $row['doi_prefix'],
            'event' => 'publish',
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

   if(!empty($row['doi_suffix'])) {
      $submission['data']['attributes']['doi'] = $doi;
   }

   // Submit data.
   $data = json_encode($submission, JSON_INVALID_UTF8_IGNORE);
   curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
   $result = json_decode(curl_exec($ch), true);
   $error = $result['errors'][0]['title'] ?? '';
   $publishedDoi = $result['data']['attributes']['doi'] ?? $doi;
   fputcsv($reportFp, [$row['doi_suffix'], 'https://doi.org/'.$publishedDoi, curl_getinfo($ch, CURLINFO_HTTP_CODE), $error]);

   if($error) {
      $error = ', '.lcfirst($error);
   }

   array_push($_SESSION['output'], '- submitted DOI '.$publishedDoi.' with status of '.curl_getinfo($ch, CURLINFO_HTTP_CODE).$error);

   if($error = curl_error($ch)) {
      array_push($_SESSION['output'], $error);
   }
}

curl_close($ch);
fclose($uploadFp);
fclose($reportFp);
remove_old_files('reports'.DIRECTORY_SEPARATOR.'*.csv', CONFIG['maxReportFiles']);
if(!(CONFIG['devMode'] ?? false)) { go_home(); }
else { echo '<a href=".">Go Back</a>'; }
