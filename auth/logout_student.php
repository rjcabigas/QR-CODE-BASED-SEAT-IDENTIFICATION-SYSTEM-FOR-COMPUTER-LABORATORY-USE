<?php
session_start();

session_unset();
session_destroy();

header("Location: ../auth/student_login.php");
exit();