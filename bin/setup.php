<?php
declare(strict_types=1);

use App\Database;

if (PHP_SAPI !== 'cli') { exit(1); }
require dirname(__DIR__).'/vendor/autoload.php';
$config=require dirname(__DIR__).'/config/database.php';
try {
    if ($config['dbname']!=='awd_li') { throw new InvalidArgumentException(); }
    // Служебное подключение используется только для проверки каталога и CREATE DATABASE.
    $catalog=new PDO('pgsql:host=127.0.0.1;port=5432;dbname=postgres',$config['user'],$config['password'],
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    if (!$catalog->query("SELECT 1 FROM pg_database WHERE datname='awd_li'")->fetchColumn()) {
        $catalog->exec('CREATE DATABASE awd_li');
    }
    $db=(new Database($config))->getConnection();
    $db->exec(file_get_contents(dirname(__DIR__).'/schema.sql'));
    echo "Схема awd_li готова.\n";
} catch (Throwable $error) {
    fwrite(STDERR,"Не удалось подготовить awd_li. Проверьте подключение и права пользователя.\n");
    exit(1);
}