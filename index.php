<?php
// -----------------------------------------------------------------------------
// index.php - Redirects all requests to the main HTML frontend
// -----------------------------------------------------------------------------

// Send an HTTP redirect header to the browser, pointing to index.html
header("Location: index.html");

// Terminate the script to ensure no further code is executed
exit;
?>