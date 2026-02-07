<?php
session_start();
session_unset();
session_destroy();
header("Location: ../index.php"); // ✅ correct path back to login page
exit();
?>
