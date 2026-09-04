<?php
require "includes/auth.php";
unset($_SESSION['student_id']);
header("Location: login.php");
exit;
