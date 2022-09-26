<?php
session_start();
require_once('includes'.DIRECTORY_SEPARATOR.'functions.php');
load_config_file();
?>
<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>DataCite Bulk DOI Creator - Reports</title>

   <link rel="stylesheet" href="css/bootstrap.min.css">
   <link rel="stylesheet" href="css/styles.css">

   <script src="js/jquery.slim.min.js"></script>
   <script src="js/bootstrap.bundle.min.js"></script>
</head>
<body>
   <?php print_header(basename(__FILE__)); ?>

   <main class="container my-3">
      <div class="row">
         <div class="col-lg-6 my-3">
            <h2><span>Reports</span></h2>

            <ul class="list-group">
               <?php list_files('reports', CONFIG['maxReportFiles']); ?>
            </ul>
         </div>

         <div class="col-lg-6 my-3">
            <h2><span>Uploads</span></h2>

            <ul class="list-group">
               <?php list_files('uploads', CONFIG['maxSubmittedFiles']); ?>
            </ul>
         </div>
      </div>
   </main>

   <?php print_footer(); ?>
</body>
</html>
