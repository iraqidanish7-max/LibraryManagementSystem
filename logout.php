<?php
session_start();

/* Unset all session variables */
$_SESSION = [];

/* Destroy the session */
session_destroy();

/* Prevent browser cache */
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

/* Redirect to student login */
header("Location: student/student_login.php");
exit;