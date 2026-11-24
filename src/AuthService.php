<?php
declare(strict_types=1);
namespace App;

/** Регистрация и смена анонимной сессии на авторизованную. */
final class AuthService
{
    /** @param UserRepository $users Справочник учётных записей. */
    public function __construct(private UserRepository $users) {}

    /**
     * @param string $login Логин из формы входа.
     * @param mixed $password Исходное значение пароля.
     * @return bool Пароль подтверждён и сессия обновлена.
     */
    public function login(string $login,mixed $password): bool
    {
        $account=$this->users->credentials(strtolower($login));
        if (!$account || !is_string($password) || str_contains($password,"\0") || strlen($password)>72
            || !password_verify($password,$account['password_hash'])) {
            return false;
        }
        session_regenerate_id(true);
        $_SESSION=['user_id'=>$account['id'],'csrf'=>bin2hex(random_bytes(32))];
        return true;
    }

    /**
     * @param array $input Поля формы регистрации.
     * @param string $role Роль, назначенная контроллером.
     * @return array Ошибки регистрации. 
     */
    public function register(array $input,string $role): array
    {
        $login=strtolower(field($input,'username'));
        $name=field($input,'full_name');
        $dept=parseExpenseId($input['department_id'] ?? null);
        $password=$input['password'] ?? null;
        $errors=[];
        if (!preg_match('/^[a-z0-9_]{3,32}$/D',$login)) { $errors[]='Логин: 3–32 латинские буквы, цифры или подчёркивания.'; }
        if (mb_strlen($name)<2 || mb_strlen($name)>100) { $errors[]='Имя должно содержать от 2 до 100 символов.'; }
        if (!in_array($dept,array_column($this->users->departments(),'id'),true)) { $errors[]='Выберите подразделение.'; }
        if (!is_string($password) || !mb_check_encoding($password,'UTF-8') || mb_strlen($password)<8 || strlen($password)>72 || str_contains($password,"\0")) {
            $errors[]='Пароль должен содержать от 8 символов до 72 байт.';
        }
        if ($password!==($input['confirm'] ?? null)) { $errors[]='Пароли не совпадают.'; }
        if (!$errors && !$this->users->add($login,$name,$dept,password_hash($password,PASSWORD_BCRYPT),$role)) {
            $errors[]='Этот логин уже занят.';
        }
        return $errors;
    }
}