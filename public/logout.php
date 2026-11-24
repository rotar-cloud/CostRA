<?php
require dirname(__DIR__).'/bootstrap.php';
$_SESSION=[];
setcookie(session_name(),'',time()-3600,'/');
session_destroy();
redirect('index.php');