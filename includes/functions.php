<?php
/**
 * Loads the configuration file.
 *
 * Configuration will be in a CONFIG variable.
 *
 * @return void
 */
function load_config_file() {
   require_once('config'.DIRECTORY_SEPARATOR.'config.php');
}


/**
 * Lists out files in either the reports or uploads directories.
 *
 * @param string  $type    Folder to parse.
 * @param integer $amount  Number of results to return.
 * @return void
 */
function list_files($type, $amount) {
   $files = glob($type.DIRECTORY_SEPARATOR.'*.csv');

   // Sort by last modified descending.
   usort($files, function($x, $y) {
      return filemtime($y) <=> filemtime($x);
   });

   if(empty($files) || $amount <= 0) {
      echo '<li class="list-group-item">no '.$type.' found</li>';
      return;
   }
   else {
      $files = array_slice($files, 0, $amount);
   }

   foreach($files as $file) {
      echo '<li class="list-group-item">';
      echo '<a class="stretched-link" href="'.htmlspecialchars($file, ENT_QUOTES).'" download>'.htmlspecialchars(substr($file, 8), ENT_QUOTES).'</a>';
      echo '<div class="text-muted small">'.date('F j, Y g:i a', filemtime($file)).'</div>';
      echo '</li>';
   }
}


/**
 * Prints the navigation, marking the current page as active.
 *
 * @param string $currentPage Filename of the current page.
 * @return void
 */
function print_header($currentPage) {
   echo '
   <header class="container my-3">
      <h1 class="text-center mb-3">DataCite Bulk DOI Creator</h1>
      <nav class="navbar rounded navbar-dark bg-dark navbar-expand-lg">
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
               <li class="nav-item dropdown">
                  <a class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown" aria-expanded="false">
                     DataCite
                  </a>
                  <div class="dropdown-menu">
                     <a target="_blank" class="dropdown-item" href="https://datacite.org/">Homepage</a>
                     <a target="_blank" class="dropdown-item" href="https://doi.datacite.org/sign-in">Sign-In</a>
                  </div>
               </li>
               <li class="nav-item dropdown">
                  <a class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown" aria-expanded="false">
                     Help
                  </a>
                  <div class="dropdown-menu">
                     <a target="_blank" class="dropdown-item" href="https://github.com/gsu-library/datacite-bulk-doi-creator-webapp/blob/master/README.md">README</a>
                     <a target="_blank" class="dropdown-item" href="https://support.datacite.org/">DataCite Support</a>
                     <a target="_blank" class="dropdown-item" href="https://support.datacite.org/docs/api-error-codes">DataCite API Error Codes</a>
                     <a target="_blank" class="dropdown-item" href="https://github.com/gsu-library/datacite-bulk-doi-creator-webapp/issues">Report an Issue/Request Enhancement</a>
                  </div>
               </li>
            </ul>
         </div>
      </nav>
   </header>';
}


/**
 * Prints the footer.
 *
 * @return void
 */
function print_footer() {
   echo '
   <footer class="container my-3">
      <div class="text-center text-light bg-dark rounded py-2">
         <p class="m-0">Visit our <a class="text-light" href="https://github.com/gsu-library/datacite-bulk-doi-creator-webapp/">GitHub Repository</a></p>
      </div>
   </footer>';
}
