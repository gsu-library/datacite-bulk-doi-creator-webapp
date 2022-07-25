<?php
   //TODO: csrf check
   //TODO: JSON output for errors?
   //TODO: Add CSV options to config

   // Check if all needles exist in haystack.
   function in_array_all($needles, $haystack) {
      return empty(array_diff($needles, $haystack));
   }

   // Load configuration file.
   if(!$config = parse_ini_file('config/config.ini')) {
      exit;
   }

   // Check for PHP cURL.
   if(!function_exists('curl_init')) {
      echo 'Please install/enable the PHP cURL library.';
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
      echo 'There was an error saving the uploaded file.';
      exit;
   }

   // Open the file.
   if(!$fp = fopen($fullFile, 'r')) {
      echo 'There was an error opening the uploaded file.';
      exit;
   }


   $fileData = [];

   // Save file headers.
   if(($headers = fgetcsv($fp)) === false) {
      echo 'No data was found in the uploaded file.';
      exit;
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
      'url',
      'context_key'
   ];

   if(!in_array_all($requiredHeaders, $headers)) {
      echo 'The uploaded CSV file is missing required headers.';
      exit;
   }

   // Find how many creator headers are present.
   $creatorHeaders = [];
   $i = 1;

   while(in_array('creator'.$i, $headers)) {
      array_push($creatorHeaders, 'creator'.$i++);
   }

   // Retrieve the rest of the file.
   while(($row = fgetcsv($fp)) !== false) {
      $fileData[] = array_combine($headers, $row);
   }

   fclose($fp);

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

   // Process file data.
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
               'url' => $row['url']
            ]
         ]
      ];

      // Submit data.
      $data = json_encode($submission, JSON_INVALID_UTF8_IGNORE);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
      $result = curl_exec($ch);

      // Print out message for each DOI.
      // Save/write status to file for download report.
      echo '<pre>'.print_r($result, true).'</pre>';

      if($error = curl_error($ch)) {
         echo '<pre>'.print_r($error, true).'</pre>';
      }
   }

   curl_close($ch);
