<?php
require dirname(__DIR__).'/bootstrap.php';
$view->render('home',$context+['title'=>'Главная','categories'=>$repo->getCategories()]);