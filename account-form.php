<?php
$staff=$page==='users';
$errors=[];
if ($post) {
    $errors=$auth->register($_POST,$staff?'admin':'employee');
    if (!$errors) { redirect($staff?'users.php':'login.php',$staff?'Администратор создан.':'Регистрация завершена. Войдите в систему.'); }
    http_response_code(422);
}
$view->render('auth',$context+['title'=>$staff?'Пользователи':'Регистрация','errors'=>$errors,
    'departments'=>$users->departments(),'people'=>$staff?$users->all():[],
    'formData'=>array_intersect_key($_POST,array_flip(['username','full_name','department_id']))]);