<?php
   const DEBUG = true;

   if(DEBUG) {
      echo '<pre>'.print_r($_FILES, true).'</pre>';
   }

   // Load configuration file.
   if(!$config = parse_ini_file('config/config.ini')) {
      exit;
   }

   //TODO: csrf check

   //TODO: Check for file upload & type.
   //TODO: Save file to uploads directory.
   //TODO: Make sure the directory is not over max files.
   //TODO: Set max file size in configuration and check it here and on the form.
   if($file = $_FILES['fileUpload'] ?? null) {
      move_uploaded_file($_FILES['fileUpload']['tmp_name'], 'uploads/'.$_FILES['fileUpload']['name']);
   }

   //TODO: Process the file

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

   // $result = curl_exec($ch);
   // echo '<pre>'.print_r($result, true).'</pre>';

   if($error = curl_error($ch)) {
      echo '<pre>'.print_r($error, true).'</pre>';
   }

   curl_close($ch);

