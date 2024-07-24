<?php
// header("Access-Control-Allow-Origin: https://hostels.nitjalandhar.in");
// error_log("CORS headers set");
// header("Access-Control-Allow-Origin: https://guesthouseb.nitj.ac.in");
header("Access-Control-Allow-Origin: http://localhost:8080");
// header("Access-Control-Allow-Origin:http://localhost:4173");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");


if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Set session cookie parameters
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => 'v1.nitj.ac.in',  //backedn-domain name
    //  'domain' => 'localhost',//backedn-domain name --comment in production.
    'secure' => true,
    'httponly' => true,
    'samesite' => 'None'
]);
session_start();

?>