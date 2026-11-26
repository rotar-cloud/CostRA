<?php
require dirname(__DIR__).'/bootstrap.php';
$captchaError=$post?$captcha->verify($page,$_POST['captcha'] ?? null):null;
$login=field($_POST,'username');
if ($post && $captchaError===null && $auth->login($login,$_POST['password'] ?? null)) { redirect('dashboard.php'); }
if ($post) { http_response_code(422); }
$view->render('auth',$context+['title'=>'Вход','formData'=>['username'=>$login],
    'captchaQuestion'=>$captcha->issue($page),'captchaError'=>$captchaError,
    'errors'=>$post && $captchaError===null?['Неверный логин или пароль.']:[]]);