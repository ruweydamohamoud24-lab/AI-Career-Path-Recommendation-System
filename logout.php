<?php
// logout.php - Destroy session and logout

session_start();
session_destroy();
header('Location: login.html');
exit;
?>