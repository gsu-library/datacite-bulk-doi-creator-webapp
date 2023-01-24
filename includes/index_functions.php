<?php
/**
 * Prints the $output session variable.
 *
 * @return void
 */
function print_output() {
   if(isset($_SESSION['output'])) {
      echo implode('<br>', $_SESSION['output']);
   }

   $_SESSION['output'] = [];
}


/**
 * Prints a link to the current report found in session variable $reportPath.
 *
 * @return void
 */
function print_report_link() {
   if(isset($_SESSION['reportPath']) && $_SESSION['reportPath']) {
      echo '<a href="'.htmlspecialchars($_SESSION['reportPath'], ENT_QUOTES).'" download>Download Report</a>';
   }

   unset($_SESSION['reportPath']);
}


/**
 * Sets the CSRF token in session ($csrfToken).
 *
 * @return void
 */
function set_csrf_token() {
   if(!isset($_SESSION['csrfToken'])) {
      $_SESSION['csrfToken'] = bin2hex(random_bytes(32));
   }
}
