<?php
   //TODO: should output be cleared each run?
   session_start();

   // Print output session variable.
   function printOutput() {
      if(isset($_SESSION['output'])) {
         foreach($_SESSION['output'] as $message) {
            echo $message."\r\n";
         }
      }

      $_SESSION['output'] = [];
   }


   if(!($_SESSION['csrfToken'] ?? null)) {
      $_SESSION['csrfToken'] = bin2hex(random_bytes(32));
   }
?>
<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>DataCite Bulk DOI Creator</title>

   <link rel="stylesheet" href="css/bootstrap.min.css">

   <script src="js/jquery.slim.min.js"></script>
   <script src="js/bootstrap.bundle.min.js"></script>
</head>
<body>
   <div class="container">
      <h1 class="my-4">DataCite Bulk DOI Creator - REMOVE CURL SSL OPT</h1>

      <nav class="navbar navbar-expand-lg navbar-light bg-light my-4">
         <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNavDropdown" aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
         </button>

         <div class="collapse navbar-collapse" id="navbarNavDropdown">
            <ul class="navbar-nav">
               <li class="nav-item active">
                  <a class="nav-link" href="https://github.com/gsu-library/datacite-bulk-doi-creator-webapp/">Bulk DOI Creator Repository</a>
               </li>
               <li class="nav-item dropdown">
                  <a class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown" aria-expanded="false">
                     DataCite
                  </a>
                  <div class="dropdown-menu">
                     <a class="dropdown-item" href="https://datacite.org/">Homepage</a>
                     <a class="dropdown-item" href="https://doi.datacite.org/sign-in">Sign-In</a>
                  </div>
               </li>
               <li class="nav-item dropdown">
                  <a class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown" aria-expanded="false">
                     Help
                  </a>
                  <div class="dropdown-menu">
                     <a class="dropdown-item" href="https://github.com/gsu-library/datacite-bulk-doi-creator-webapp/blob/master/README.md">README</a>
                     <a class="dropdown-item" href="https://support.datacite.org/">DataCite Help</a>
                     <a class="dropdown-item" href="https://github.com/gsu-library/datacite-bulk-doi-creator-webapp/issues">Report an Issue/Request Enhancement</a>
                     <a class="dropdown-item" href="https://support.datacite.org/docs/api-error-codes">DataCite API Error/Status Codes</a>
                  </div>
               </li>
            </ul>
         </div>
      </nav>

      <div class="row">
         <div class="col-lg-7">
            <h2>Something</h2>
            <form action="submit.php" method="post" enctype="multipart/form-data">
               <div class="form-group">
                  <label for="fileUpload">Upload File</label>
                  <input type="file" id="fileUpload" class="form-control-file" name="fileUpload" accept=".csv">
               </div>

               <input type="hidden" name="csrfToken" value="<?= $_SESSION['csrfToken']; ?>">
               <button type="submit" class="btn btn-primary" name="submit">Submit</button>
            </form>

            <label class="mt-5" for="output">Output</label>
            <textarea class="form-control mb-5" id="output" rows="10" disabled><?php printOutput(); ?></textarea>
         </div>

         <div class="col-lg-5">
            <h2><span class="text-muted">Upload Reports</span></h2>

            <ul class="list-group mb-3">
               <?php
                  $files = glob('reports/*.csv');

                  // Sort by last modified descending.
                  usort($files, function($x, $y) {
                     return filemtime($x) < filemtime($y);
                  });

                  if(empty($files)) {
                     echo '<li class="list-group-item d-flex justify-content-between lh-condensed">no reports found</li>';
                  }

                  //TODO: sanitize filename
                  foreach($files as $file) {
                     echo '<li class="list-group-item d-flex justify-content-between lh-condensed">';
                     echo '<a class="stretched-link" href="'.$file.'">'.substr($file, 8).'</a>';
                     echo '</li>';
                  }
               ?>
            </ul>


            <h2><span class="text-muted">Submitted Files</span></h2>
            <ul class="list-group mb-3">
              <?php
                  $files = glob('uploads/*.csv');

                  // Sort by last modified descending.
                  usort($files, function($x, $y) {
                     return filemtime($x) < filemtime($y);
                  });

                  if(empty($files)) {
                     echo '<li class="list-group-item d-flex justify-content-between lh-condensed">no uploads found</li>';
                  }

                  //TODO: sanitize filename
                  foreach($files as $file) {
                     echo '<li class="list-group-item d-flex justify-content-between lh-condensed">';
                     echo '<a class="stretched-link" href="'.$file.'">'.substr($file, 8).'</a>';
                     echo '</li>';
                  }
               ?>
            </ul>
         </div>
      </div>
   </div>
</body>
</html>
