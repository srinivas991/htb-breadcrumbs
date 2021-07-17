<?php

require __DIR__ . '/vendor/autoload.php';
use \Firebase\JWT\JWT;

$secret_key = '6cb9c1a2786a483ca5e44571dcc5f3bfa298593a6376ad92185c3258acd5591e';
            $data = array();
            $username = $argv[1];

            $payload = array(
                "data" => array(
                    "username" => $username
            ));

            $jwt = JWT::encode($payload, $secret_key, 'HS256');
            
            echo $jwt;
            echo "\n";

?>
