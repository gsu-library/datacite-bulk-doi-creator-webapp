<?php
   session_start();
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
                  <a class="nav-link" href="#">Home <span class="sr-only">(current)</span></a>
               </li>
               <li class="nav-item active">
                  <a class="nav-link" href="#">DataCite</a>
               </li>
               <li class="nav-item active">
                  <a class="nav-link" href="#">Github Repository</a>
               </li>
               <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown" aria-expanded="false">
                  Help
               </a>
               <div class="dropdown-menu">
                  <a class="dropdown-item" href="#">README</a>
                  <a class="dropdown-item" href="#">DataCite Help</a>
                  <a class="dropdown-item" href="#">etc...</a>
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

               <button type="submit" class="btn btn-primary" name="submit">Submit</button>
            </form>
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
