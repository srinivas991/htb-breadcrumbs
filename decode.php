<?php
$ret = "";
require "./vendor/autoload.php";
use \Firebase\JWT\JWT;
session_start();


$ret = false;
$jwt = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJuYW1lIjoicGF1bCJ9fQ.7pc5S1P76YsrWhi_gu23bzYLYWxqORkr0WtEz_IUtCU";

$secret_key = '6cb9c1a2786a483ca5e44571dcc5f3bfa298593a6376ad92185c3258acd5591e';
$ret = JWT::decode($jwt, $secret_key, array('HS256'));   

?>