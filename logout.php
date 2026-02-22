<?php
require_once 'lib/auth.php'; // Ensures valid session is started with correct params
session_destroy();
header("Location: login.php");
exit();
?>