<?php

$password = "Admin@25";

$hash = password_hash($password, PASSWORD_BCRYPT);

echo "<h3>Password:</h3>";
echo $password;

echo "<br><br>";

echo "<h3>Generated Hash:</h3>";
echo $hash;

echo "<br><br>";

echo "<h3>SQL to paste in phpMyAdmin:</h3>";
echo "UPDATE users SET password = '$hash' WHERE email='admin@gmail.com';";

?>