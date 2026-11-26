<?php
require dirname(__DIR__).'/bootstrap.php';
$errors=[];
$captchaError=null;
if ($post) {
    $captchaError=$captcha->verify($page,$_POST['captcha'] ?? null);
    if ($captchaError===null) {
        $errors=$auth->recover($_POST);
        if (!$errors) { redirect('login.php','Пароль изменён. Войдите с новым паролем.'); }
    }
    http_response_code(422);
}
$view->render('recover',$context+['title'=>'Восстановление пароля','errors'=>$errors,
    'username'=>is_string($_POST['username'] ?? null)?$_POST['username']:'',
    'captchaQuestion'=>$captcha->issue($page),'captchaError'=>$captchaError]);
