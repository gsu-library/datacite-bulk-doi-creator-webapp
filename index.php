<?php
//TODO: update output language for key -> suffix
//TODO: datetime text for report and upload links?
//TODO: file list should word-break: break-all
//TODO: change textarea to something else maybe use output
session_start();
require_once('includes/functions.php');

// Print output session variable.
function printOutput() {
   if(isset($_SESSION['output'])) {
      foreach($_SESSION['output'] as $message) {
         echo $message."\r\n";
      }
   }

   $_SESSION['output'] = [];
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

      <?php printNav(basename(__FILE__)); ?>

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
            <textarea class="form-control mb-5" id="output" rows="10" disabled><?php printOutput(); ?></textarea>
         </div>

         <div class="col-lg-5">
            <h2><span class="text-muted">Recent Reports</span></h2>

            <ul class="list-group mb-3">
               <?php listFiles('reports', 5); ?>
            </ul>


            <h2><span class="text-muted">Recent Uploads</span></h2>
            <ul class="list-group mb-3">
               <?php listFiles('uploads', 5); ?>
            </ul>
         </div>
      </div>
   </div>
</body>
</html>
