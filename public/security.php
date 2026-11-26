<?php
require dirname(__DIR__).'/bootstrap.php';
header('Cache-Control: no-store');
$code=null;
$errors=[];
if ($post) {
    $code=$auth->issueRecoveryCode($user,$_POST['password'] ?? null);
    if ($code===null) {
        $errors[]='Не удалось создать код. Проверьте текущий пароль.';
        http_response_code(422);
    }
}
$view->render('security',$context+['title'=>'Безопасность аккаунта','code'=>$code,'errors'=>$errors]);
