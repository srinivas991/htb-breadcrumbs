<?php

$username = $argv[1];

$max = strlen($username) - 1;
$seed = rand(0, $max);
$key = "s4lTy_stR1nG_".$username[$seed]."(!528./9890";

$session_cookie = $username.md5($key);

echo $session_cookie;
echo "\n";

?>
