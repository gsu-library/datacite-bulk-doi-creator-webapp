<?php
session_start();
require_once('includes'.DIRECTORY_SEPARATOR.'functions.php');

// Print output session variable.
function printOutput() {
   if(isset($_SESSION['output'])) {
      echo implode('<br>', $_SESSION['output']);
   }

   $_SESSION['output'] = [];
}


// Prints link to current report.
function printReportLink() {
   if(isset($_SESSION['reportPath']) && $_SESSION['reportPath']) {
      echo '<a href="'.htmlspecialchars($_SESSION['reportPath'], ENT_QUOTES).'">Download Report</a>';
   }

   unset($_SESSION['reportPath']);
}


if(!isset($_SESSION['csrfToken'])) {
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
   <link rel="stylesheet" href="css/styles.css">

   <script src="js/jquery.slim.min.js"></script>
   <script src="js/bootstrap.bundle.min.js"></script>
</head>
<body>
   <div class="container">
      <h1 class="my-4">DataCite Bulk DOI Creator</h1>

      <?php print_nav(basename(__FILE__)); ?>

      <div class="row">
         <div class="col-lg-7">
            <form action="submit.php" method="post" enctype="multipart/form-data">
               <div class="form-group">
                  <label for="fileUpload">Upload File</label>
                  <input type="file" id="fileUpload" class="form-control-file" name="fileUpload" accept=".csv">
               </div>

               <input type="hidden" name="csrfToken" value="<?= $_SESSION['csrfToken']; ?>">
               <button type="submit" class="btn btn-primary" name="submit">Submit</button>
            </form>

            <label class="mt-5" for="output">Output</label>
            <output class="output mb-5" id="output"><?php printOutput(); ?></output>

            <div class="report my-3">
               <?php printReportLink(); ?>
            </div>
         </div>

         <div class="col-lg-5">
            <h2><span class="text-muted">Recent Reports</span></h2>

            <ul class="list-group mb-3">
               <?php list_files('reports', 5); ?>
            </ul>


            <h2><span class="text-muted">Recent Uploads</span></h2>
            <ul class="list-group mb-3">
               <?php list_files('uploads', 5); ?>
            </ul>
         </div>
      </div>
   </div>
</body>
</html>
