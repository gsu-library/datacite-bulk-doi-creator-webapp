<?php
session_start();
require_once('includes'.DIRECTORY_SEPARATOR.'functions.php');
$config = load_config_file();
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
   <header class="container">
      <h1 class="my-4 text-center">DataCite Bulk DOI Creator</h1>

      <?php print_nav(basename(__FILE__)); ?>
   </header>

   <main class="container">
      <div class="row">
         <div class="col-lg-6">
            <h2><span class="text-muted">Reports</span></h2>

            <ul class="list-group mb-3">
               <?php list_files('reports', $config['maxReportFiles']); ?>
            </ul>
         </div>

         <div class="col-lg-6">
            <h2><span class="text-muted">Uploads</span></h2>

            <ul class="list-group mb-3">
               <?php list_files('uploads', $config['maxSubmittedFiles']); ?>
            </ul>
         </div>
      </div>
   </main>

   <footer class="container">
      <?php print_footer(); ?>
   </footer>
</body>
</html>
