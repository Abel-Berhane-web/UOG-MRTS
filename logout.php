<?php
session_start();
$_SESSION = [];
session_destroy();

// Prevent caching after logout
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

header("Location: login.php");
exit;
?>
