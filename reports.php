<?php
require_once('includes/functions.php');

//TODO: add config load to functions along with goback and think about session messages.
// Load configuration file.
if(!$config = parse_ini_file('config/config.ini')) {
   // array_push($_SESSION['output'], 'Could not load the configuration file.');
   // goBack();
   header('location: .');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>DataCite Bulk DOI Creator - Reports</title>

   <link rel="stylesheet" href="css/bootstrap.min.css">

   <script src="js/jquery.slim.min.js"></script>
   <script src="js/bootstrap.bundle.min.js"></script>
</head>
<body>
   <div class="container">
      <h1 class="my-4">DataCite Bulk DOI Creator - Reports</h1>

      <?php printNav(basename(__FILE__)); ?>

      <div class="row">
         <div class="col-lg-6">
            <h2><span class="text-muted">Reports</span></h2>

            <ul class="list-group mb-3">
               <?php listFiles('reports', $config['maxReportFiles']); ?>
            </ul>
         </div>

         <div class="col-lg-6">
            <h2><span class="text-muted">Uploads</span></h2>

            <ul class="list-group mb-3">
               <?php listFiles('uploads', $config['maxSubmittedFiles']); ?>
            </ul>
         </div>
      </div>
   </div>
</body>
</html>
