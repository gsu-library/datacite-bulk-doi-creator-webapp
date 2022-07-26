<?php
   session_start();
   $_SESSION['output'] = [];
   //TODO: Add CSV options to config
   //TODO: context_key should be called something different


   // Check if all needles exist in haystack.
   function in_array_all($needles, $haystack) {
      return empty(array_diff($needles, $haystack));
   }


   // Go back to the index page.
   function goBack() {
      header('location: .');
      exit;
   }


   // Check CSRF token.
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
   if($file = $_FILES['fileUpload']['name'] ?? null) {
      $fullFile = 'uploads/'.$file;

      if(file_exists($fullFile)) {
         $fullFile = 'uploads/'.pathinfo($file, PATHINFO_FILENAME).'.'.date('Ymd-His').'.'.pathinfo($file, PATHINFO_EXTENSION);
      }

      move_uploaded_file($_FILES['fileUpload']['tmp_name'], $fullFile);

      //TODO: Check max files and remove files if needed.
   }
   else {
      array_push($_SESSION['output'], 'There was an error saving the uploaded file.');
      goBack();
   }

   // Open the uploaded file.
   if(!$uploadFp = fopen($fullFile, 'r')) {
      array_push($_SESSION['output'], 'There was an error opening the uploaded file.');
      goBack();
   }


   $fileData = [];

   // Save file headers.
   if(($headers = fgetcsv($uploadFp)) === false) {
      array_push($_SESSION['output'], 'No data was found in the uploaded file.');
      goBack();
   }

   // Make sure CSV file has all required headers.
   $requiredHeaders = [
      'title',
      'year',
      'type',
      'description',
      'creator1',
      'creator1_type',
      'creator1_given',
      'creator1_family',
      'publisher',
      'source',
      'context_key'
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

   //TODO THESE MUST BE REMOVED
   curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
   curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

   // Open a file for the upload report.
   if(!$reportFp = fopen('reports/upload-report.'.date('Ymd-His').'.csv', 'w')) {
      array_push($_SESSION['output'], 'Cannot write to the reports folder');
      goBack();
   }

   // Add headers to upload report file.
   fputcsv($reportFp, ['id', 'doi', 'status', 'error']);

   // Process file data and create report.
   $proccessedCsv = [];
   foreach($fileData as $row) {
      $doi = $config['doiPrefix'].'/'.$row['context_key'];
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
               'url' => $row['source']
            ]
         ]
      ];

      // Submit data.
      $data = json_encode($submission, JSON_INVALID_UTF8_IGNORE);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
      $result = json_decode(curl_exec($ch), true);
      $error = $result['errors'][0]['title'] ?? '';
      fputcsv($reportFp, [$row['context_key'], 'https://doi.org/'.$doi, curl_getinfo($ch, CURLINFO_HTTP_CODE), $error]);
      array_push($_SESSION['output'], 'submitted key '.$row['context_key'].' with status of '.curl_getinfo($ch, CURLINFO_HTTP_CODE).', '.$error);

      if($error = curl_error($ch)) {
         array_push($_SESSION['output'], $error);
      }
   }

   fclose($reportFp);
   curl_close($ch);
   goBack();
