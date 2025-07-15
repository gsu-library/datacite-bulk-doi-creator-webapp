<?php
session_start();
require_once('includes'.DIRECTORY_SEPARATOR.'functions.php');
require_once('includes'.DIRECTORY_SEPARATOR.'index_functions.php');
load_config_file();
set_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>DataCite Bulk DOI Creator</title>

   <link rel="stylesheet" href="css/bootstrap.min.css">
   <link rel="stylesheet" href="css/styles.css">

   <script src="js/jquery.slim.min.js"></script>
   <script src="js/bootstrap.bundle.min.js"></script>
</head>
<body>
   <?php print_header(basename(__FILE__)); ?>

   <main class="container my-3">
      <div class="row">
         <div class="col-lg-7 my-3">
            <form class="mb-3" action="submit.php" method="post" enctype="multipart/form-data">
               <div class="form-group">
                  <label for="fileUpload">Upload File</label>
                  <input type="file" id="fileUpload" class="form-control-file" name="fileUpload" accept=".csv">
               </div>

               <input type="hidden" name="csrfToken" value="<?= $_SESSION['csrfToken']; ?>">
               <button type="submit" class="btn btn-primary" name="submit">Submit</button>
            </form>

            <label class="mt-3" for="output">Output</label>
            <output class="output" id="output"><?php print_output(); ?></output>

            <div class="report mt-3">
               <?php print_report_link(); ?>
            </div>
         </div>

         <div class="col-lg-5 my-3">
            <h2><span>Configuration</span></h2>
            <ul class="list-group mb-3">
               <?php
                  if(CONFIG['devMode'] ?? false) {
                     echo '<li class="list-group-item text-danger"><strong>Dev Mode Enabled</strong></li>';
                  }
               ?>
               <li class="list-group-item">
                  API URL: <?= CONFIG['url']; ?>
               </li>
            </ul>

            <h2><span>Recent Reports</span></h2>
            <ul class="list-group mb-3">
               <?php list_files('reports', min(3, CONFIG['maxReportFiles'])); ?>
            </ul>

            <h2><span>Recent Uploads</span></h2>
            <ul class="list-group">
               <?php list_files('uploads', min(3, CONFIG['maxSubmittedFiles'])); ?>
            </ul>
         </div>
      </div>
   </main>


   <?php print_footer(); ?>
</body>
</html>
