<?php
require_once 'classes/Auth.php';
Auth::logout();
header("Location: login.php?logged_out=1");
exit;
