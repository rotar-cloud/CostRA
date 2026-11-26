<?php
declare(strict_types=1);

use App\{AuthService,BudgetRepository,Captcha,Database,ExpenseRepository,ExpenseService,ExpenseValidator,UserRepository,View};

ini_set('display_errors','0');
require_once __DIR__.'/src/functions.php';
/**
 * Скрывает технические детали сбоя от клиента.
 *
 * @param Throwable $error Исключение, возникшее при обработке запроса.
 * @return never Завершает запрос с кодом 500.
 */
function internalError(Throwable $error): never
{
    httpError(500,'Не удалось обработать запрос. Проверьте доступность CostRA.');
    exit;
}
set_exception_handler('internalError');
require_once __DIR__.'/vendor/autoload.php';
date_default_timezone_set('Europe/Chisinau');
session_start(['use_strict_mode'=>1,'cookie_httponly'=>true,'cookie_samesite'=>'Lax',
    'cookie_secure'=>($_SERVER['HTTPS'] ?? '')==='on']);
$_SESSION['csrf'] ??= bin2hex(random_bytes(32));
$page=basename($_SERVER['SCRIPT_FILENAME'],'.php');
$post=$_SERVER['REQUEST_METHOD']==='POST';
$actions=['delete','review','logout'];
$forms=['login','register','create','edit','users','budgets','recover','security'];
requireMethod($_SERVER['REQUEST_METHOD'],in_array($page,$actions,true)?['POST']:(in_array($page,$forms,true)?['GET','POST']:['GET']));
$captcha=new Captcha();
if ($post && (!is_string($_POST['csrf'] ?? null) || !hash_equals($_SESSION['csrf'],$_POST['csrf']))) {
    if (in_array($page,['login','register','recover'],true)) { $captcha->discard($page); }
    httpError(403,'Проверка формы не пройдена. Обновите страницу.');
}
$public=in_array($page,['index','register','login','recover'],true);
if (!$public && !isset($_SESSION['user_id'])) { redirect('login.php'); }
$db=(new Database(require __DIR__.'/config/database.php'))->getConnection();
$users=new UserRepository($db);
$user=isset($_SESSION['user_id'])?$users->current($_SESSION['user_id']):null;
if (!$public && !$user) { redirect('login.php'); }
$admin=($user['role'] ?? '')==='admin';
if ((in_array($page,['delete','review','users'],true) || ($page==='budgets' && $post)) && !$admin) {
    httpError(403,'Требуются права администратора.');
}
$scope=$admin?null:($user['id'] ?? null);
$repo=new ExpenseRepository($db);
$validator=new ExpenseValidator();
$service=new ExpenseService($repo,$validator);
$auth=new AuthService($users);
$budgets=new BudgetRepository($db);
$view=new View();
$context=['user'=>$user,'admin'=>$admin,'page'=>$page,'csrf'=>$_SESSION['csrf'],
    'statuses'=>['approved'=>'Одобрено','pending'=>'На согласовании','rejected'=>'Отклонено']];
$context['notice']=$_SESSION['notice'] ?? null;
unset($_SESSION['notice']);