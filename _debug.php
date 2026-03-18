<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
$_SESSION['mia_client_id'] = 1;
$_SERVER['REQUEST_URI'] = '/dashboard';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['SERVER_NAME'] = 'mia-whatsapp.com';
chdir('/var/www/html/mia.ainitravel.com');
require '/var/www/html/mia.ainitravel.com/index.php';
