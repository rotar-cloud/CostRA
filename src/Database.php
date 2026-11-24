<?php

declare(strict_types=1);

namespace App;

use InvalidArgumentException;
use PDO;
use PDOException;

/** Подключение приложения к PostgreSQL через PDO. */
final class Database
{
    private PDO $connection;

    /**
     * Открывает соединение с указанной базой PostgreSQL.
     *
     * @param array{host: string, port: string, dbname: string, user: string|false, password: string|false} $config Параметры подключения.
     * @throws InvalidArgumentException Если учётные данные не заданы.
     * @throws PDOException При ошибке подключения.
     */
    public function __construct(array $config)
    {
        if ($config['user'] === false || $config['user'] === '' || $config['password'] === false) {
            throw new InvalidArgumentException('Задайте DB_USER и DB_PASSWORD.');
        }

        if ($config['dbname'] !== 'awd_li') {
            throw new InvalidArgumentException('CostRA использует только awd_li.');
        }
        $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', $config['host'], $config['port'], $config['dbname']);
        $this->connection = new PDO($dsn, $config['user'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
        ]);
        // NOW() записывается в timestamp без зоны. Фиксируем зону на уровне соединения.
        $this->connection->exec("SET TIME ZONE 'UTC'");
        if ($this->connection->query('SELECT current_database()')->fetchColumn() !== 'awd_li') {
            throw new InvalidArgumentException('Неверная база.');
        }
    }

    /**
     * Возвращает соединение для выполнения запросов.
     *
     * @return PDO Открытое соединение с PostgreSQL.
     */
    public function getConnection(): PDO
    {
        return $this->connection;
    }
}