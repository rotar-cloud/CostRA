<?php
declare(strict_types=1);
namespace App;
use PDO;

/** Справочники и учётные записи. */
final class UserRepository
{
    /**
     * Подключает репозиторий к базе пользователей.
     *
     * @param PDO $db Подключение CostRA.
     */
    public function __construct(private PDO $db) {}

    /**
     * Загружает список подразделений в порядке идентификаторов.
     *
     * @return array Подразделения для выбора.
     */
    public function departments(): array
    {
        return $this->db->query('SELECT id,name FROM departments ORDER BY id')->fetchAll();
    }

    /**
     * Выбирает пользователей с ролями и названиями подразделений.
     *
     * @return array Каталог пользователей без хешей.
     */
    public function all(): array
    {
        return $this->db->query('SELECT u.id,u.username,u.full_name,u.role,d.name department FROM users u
            JOIN departments d ON d.id=u.department_id ORDER BY u.full_name')->fetchAll();
    }

    /**
     * Загружает профиль пользователя по идентификатору сессии.
     *
     * @param int $id Идентификатор из сессии.
     * @return array|null Только публичные поля текущего пользователя.
     */
    public function current(int $id): ?array
    {
        $stmt=$this->db->prepare('SELECT id,username,full_name,department_id,role FROM users WHERE id=?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Находит учётные данные для проверки пароля.
     *
     * @param string $login Нормализованный логин.
     * @return array|null Данные для проверки пароля.
     */
    public function credentials(string $login): ?array
    {
        $stmt=$this->db->prepare('SELECT id,password_hash FROM users WHERE username=?');
        $stmt->execute([$login]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Добавляет пользователя при свободном логине.
     *
     * @param string $login Уникальный логин.
     * @param string $name Полное имя.
     * @param int $dept ID подразделения.
     * @param string $hash Хеш пароля.
     * @param string $role Серверная роль.
     * @return bool Уникальный логин удалось зарегистрировать.
     */
    public function add(string $login,string $name,int $dept,string $hash,string $role): bool
    {
        $stmt=$this->db->prepare('INSERT INTO users(username,full_name,department_id,password_hash,role)
            VALUES (?,?,?,?,?) ON CONFLICT(username) DO NOTHING');
        $stmt->execute([$login,$name,$dept,$hash,$role]);
        return $stmt->rowCount()===1;
    }
    /**
     * Заменяет хеш резервного кода, если проверенный пароль всё ещё актуален.
     * @param int $id ID владельца из авторизованной сессии.
     * @param string $passwordHash Хеш проверенного текущего пароля.
     * @param string $codeHash SHA-256 нового резервного кода.
     * @return bool Код сохранён для той же версии пароля.
     */
    public function replaceRecoveryCode(int $id,string $passwordHash,string $codeHash): bool
    {
        $stmt=$this->db->prepare('UPDATE users SET recovery_code_hash=? WHERE id=? AND password_hash=?');
        $stmt->execute([$codeHash,$id,$passwordHash]);
        return $stmt->rowCount()===1;
    }

    /**
     * Одним обновлением меняет пароль и погашает совпавший код.
     * @param string $login Нормализованный логин.
     * @param string $codeHash SHA-256 предъявленного кода.
     * @param string $passwordHash Хеш нового пароля.
     * @return bool Код принят и пароль изменён.
     */
    public function resetPassword(string $login,string $codeHash,string $passwordHash): bool
    {
        $stmt=$this->db->prepare('UPDATE users SET password_hash=?, recovery_code_hash=NULL
            WHERE username=? AND recovery_code_hash=?');
        $stmt->execute([$passwordHash,$login,$codeHash]);
        return $stmt->rowCount()===1;
    }
}
