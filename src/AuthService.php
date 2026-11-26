<?php
declare(strict_types=1);
namespace App;

/** Регистрация, вход и восстановление доступа по резервному коду. */
final class AuthService
{
    /**
     * Подключает репозиторий учётных записей.
     *
     * @param UserRepository $users Справочник учётных записей.
     */
    public function __construct(private UserRepository $users) {}

    /**
     * Проверяет пароль и начинает авторизованную сессию.
     *
     * @param string $login Логин из формы входа.
     * @param mixed $password Исходное значение пароля.
     * @return bool Пароль подтверждён и сессия обновлена.
     */
    public function login(string $login,mixed $password): bool
    {
        $account=$this->users->credentials(strtolower($login));
        if (!$account || !$this->matchesPassword($password,$account['password_hash'])) {
            return false;
        }
        session_regenerate_id(true);
        $_SESSION=['user_id'=>$account['id'],'csrf'=>bin2hex(random_bytes(32))];
        return true;
    }

    /**
     * Проверяет поля и создаёт пользователя с назначенной ролью.
     *
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
        if (!$this->validLogin($login)) { $errors[]='Логин: 3–32 латинские буквы, цифры или подчёркивания.'; }
        if (mb_strlen($name)<2 || mb_strlen($name)>100) { $errors[]='Имя должно содержать от 2 до 100 символов.'; }
        if (!in_array($dept,array_column($this->users->departments(),'id'),true)) { $errors[]='Выберите подразделение.'; }
        $errors=array_merge($errors,$this->passwordErrors($password,$input['confirm'] ?? null));
        if (!$errors && !$this->users->add($login,$name,$dept,password_hash($password,PASSWORD_BCRYPT),$role)) {
            $errors[]='Этот логин уже занят.';
        }
        return $errors;
    }

    /**
     * Выдаёт новый код после проверки пароля владельца сессии.
     * @param array $user Текущий пользователь из защищённого контекста.
     * @param mixed $password Текущий пароль из формы.
     * @return string|null Одноразово показываемый код или null при отказе.
     */
    public function issueRecoveryCode(array $user,mixed $password): ?string
    {
        $account=$this->users->credentials($user['username']);
        if (!$account || $account['id']!==$user['id'] || !$this->matchesPassword($password,$account['password_hash'])) {
            return null;
        }
        $code=bin2hex(random_bytes(16));
        return $this->users->replaceRecoveryCode($user['id'],$account['password_hash'],hash('sha256',$code))?$code:null;
    }

    /**
     * Проверяет данные публичной формы и погашает код при смене пароля.
     * @param array $input Логин, резервный код, новый пароль и его подтверждение.
     * @return string[] Сообщения для формы или пустой список при успехе.
     */
    public function recover(array $input): array
    {
        $login=is_string($input['username'] ?? null)?strtolower(trim($input['username'])):'';
        $code=$input['recovery_code'] ?? null;
        $invalid=['Неверный логин или резервный код.'];
        if (!$this->validLogin($login) || !is_string($code) || !preg_match('/\A[a-f0-9]{32}\z/',$code)) {
            return $invalid;
        }
        $password=$input['password'] ?? null;
        $errors=$this->passwordErrors($password,$input['confirm'] ?? null);
        if ($errors) { return $errors; }
        return $this->users->resetPassword($login,hash('sha256',$code),password_hash($password,PASSWORD_BCRYPT))?[]:$invalid;
    }

    /**
     * Проверяет формат нормализованного логина.
     * @param string $login Логин в нижнем регистре.
     * @return bool Логин соответствует формату учётной записи.
     */
    private function validLogin(string $login): bool
    {
        return (bool)preg_match('/^[a-z0-9_]{3,32}$/D',$login);
    }

    /**
     * Сверяет введённый пароль с сохранённым хешем bcrypt.
     * @param mixed $password Исходное значение из формы.
     * @param string $hash Хеш пароля пользователя.
     * @return bool Пароль допустим для проверки и совпал.
     */
    private function matchesPassword(mixed $password,string $hash): bool
    {
        return is_string($password) && !str_contains($password,"\0") && strlen($password)<=72 && password_verify($password,$hash);
    }

    /**
     * Проверяет требования к новому паролю и его подтверждение.
     * @param mixed $password Новый пароль из формы.
     * @param mixed $confirm Повторно введённый пароль.
     * @return string[] Ошибки полей пароля или пустой список.
     */
    private function passwordErrors(mixed $password,mixed $confirm): array
    {
        $errors=[];
        if (!is_string($password) || !mb_check_encoding($password,'UTF-8') || mb_strlen($password)<8 || strlen($password)>72 || str_contains($password,"\0")) {
            $errors[]='Пароль должен содержать от 8 символов до 72 байт.';
        }
        if ($password!==$confirm) { $errors[]='Пароли не совпадают.'; }
        return $errors;
    }
}
