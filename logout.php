<?php
define('SECURE_ACCESS', true);
require_once 'auth.php';

// Use the logout function from auth.php which properly handles session cleanup
logout();
// Note: logout() function already redirects and exits, so code below won't execute
?>

