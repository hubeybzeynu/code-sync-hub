<?php
require "includes/auth.php";
unset($_SESSION['parent_id']);
header("Location: login.php");
exit;
