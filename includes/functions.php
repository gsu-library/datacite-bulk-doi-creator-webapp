<?php
// Go back to the index page.
function go_back() {
   header('location: .');
   exit;
}


// Loads the configuration file.
function load_config_file() {
   if(!$config = parse_ini_file('config'.DIRECTORY_SEPARATOR.'config.ini')) {
      array_push($_SESSION['output'], 'Could not load the configuration file.');
      go_back();
   }

   return $config;
}


// Lists out files in either the reports or uploads directories.
function list_files($type, $amount) {
   $files = glob($type.DIRECTORY_SEPARATOR.'*.csv');

   // Sort by last modified descending.
   usort($files, function($x, $y) {
      return filemtime($x) < filemtime($y);
   });

   if(empty($files)) {
      echo '<li class="list-group-item d-flex justify-content-between lh-condensed">no '.$type.' found</li>';
   }
   else {
      $files = array_slice($files, 0, $amount);
   }

   foreach($files as $file) {
      echo '<li class="list-group-item d-flex justify-content-between lh-condensed">';
      echo '<div>';
      echo '<a class="stretched-link" href="'.htmlspecialchars($file, ENT_QUOTES).'" download>'.htmlspecialchars(substr($file, 8), ENT_QUOTES).'</a>';
      echo '<div class="text-muted small">'.date('F j, Y g:i a', filemtime($file)).'</div>';
      echo '</div>';
      echo '</li>';
   }
}


// Prints the navigation, marking the current page as active.
function print_nav($currentPage) {
   echo '
   <nav class="navbar rounded navbar-dark bg-dark navbar-expand-lg my-4">
      <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNavDropdown" aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
         <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navbarNavDropdown">
         <ul class="navbar-nav">
            <li class="nav-item'. ($currentPage === 'index.php' ? ' active' : '').'">
               <a class="nav-link" href=".">Home</a>
            </li>
            <li class="nav-item'. ($currentPage === 'reports.php' ? ' active' : '').'">
               <a class="nav-link" href="reports.php">Reports and Uploads</a>
            </li>
            <li class="nav-item">
               <a class="nav-link" href="template.csv">Download CSV Template</a>
            </li>
         </ul>

         <ul class="navbar-nav ml-auto">
            <li class="nav-item">
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
   </nav>';
}
