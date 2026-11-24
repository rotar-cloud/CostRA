<?php
require dirname(__DIR__).'/bootstrap.php';
$login=field($_POST,'username');
if ($post && $auth->login($login,$_POST['password'] ?? null)) { redirect('dashboard.php'); }
if ($post) { http_response_code(422); }
$view->render('auth',$context+['title'=>'Вход','formData'=>['username'=>$login],
    'errors'=>$post?['Неверный логин или пароль.']:[]]);