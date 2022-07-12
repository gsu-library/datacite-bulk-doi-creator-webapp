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
      <h1 class="my-4">DataCite Bulk DOI Creator</h1>

      <div class="row">
         <div class="col-lg-8">
            <h2>Something</h2>
            <form action="submit.php" method="post" enctype="multipart/form-data">
               <div class="form-group">
                  <label for="fileUpload">Upload File</label>
                  <input type="file" id="fileUpload" class="form-control-file" name="fileUpload" accept=".csv">
               </div>

               <button type="submit" class="btn btn-primary" name="submit">Submit</button>
            </form>
         </div>

         <div class="col-lg-4">
            <h2><span class="text-muted">Upload Reports</span></h2>

            <ul class="list-group mb-3">
               <li class="list-group-item d-flex justify-content-between lh-condensed">
                  <a href="#">file a.csv</a>
               </li>
               <li class="list-group-item d-flex justify-content-between lh-condensed">
                  <a href="#">bio science.csv</a>
               </li>
               <li class="list-group-item d-flex justify-content-between lh-condensed">
                  <a href="#">third file.csv</a>
               </li>
            </ul>


            <h2><span class="text-muted">Last Uploaded CSVs</span></h2>
            <ul class="list-group mb-3">
               <li class="list-group-item d-flex justify-content-between lh-condensed">
                  <a href="#">upload_report.20220710-111111.csv</a>
               </li>
               <li class="list-group-item d-flex justify-content-between lh-condensed">
                  <a href="#">upload_report.20220621-091825.csv</a>
               </li>
               <li class="list-group-item d-flex justify-content-between lh-condensed">
                  <a href="#">upload_report.20220603-232246.csv</a>
               </li>
            </ul>
         </div>
      </div>
   </div>
</body>
</html>
