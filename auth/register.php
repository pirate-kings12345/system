<?php
session_start();
require_once '../config/db_connect.php';
require_once '../models/Database.php';
require_once '../models/User.php';
require_once '../controllers/registerController.php';

// Redirect to index.php if not a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit();
}

// The registerController.php will handle the actual registration logic
// and set session variables for errors/success before redirecting.
// No further action needed here.
?>