<?php
$staff=$page==='users';
$errors=[];
$captchaError=$post && !$staff?$captcha->verify($page,$_POST['captcha'] ?? null):null;
if ($post) {
    if ($captchaError===null) { $errors=$auth->register($_POST,$staff?'admin':'employee'); }
    if ($captchaError===null && !$errors) { redirect($staff?'users.php':'login.php',$staff?'Администратор создан.':'Регистрация завершена. Войдите в систему.'); }
    http_response_code(422);
}
$view->render('auth',$context+['title'=>$staff?'Пользователи':'Регистрация','errors'=>$errors,
    'captchaQuestion'=>$staff?null:$captcha->issue($page),'captchaError'=>$captchaError,
    'departments'=>$users->departments(),'people'=>$staff?$users->all():[],
    'formData'=>array_intersect_key(array_filter($_POST,'is_string'),array_flip(['username','full_name','department_id']))]);