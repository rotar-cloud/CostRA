<?php
declare(strict_types=1);
/**
 * Разбирает положительный идентификатор из запроса.
 *
 * @param mixed $value ID, переданный через форму или URL.
 * @return int|null Положительный ID в диапазоне PostgreSQL INTEGER либо null.
 */
function parseExpenseId(mixed $value): ?int
{
    if (!is_string($value) || !preg_match('/\A[0-9]+\z/', $value)) {
        return null;
    }
    $digits = ltrim($value, '0');
    if ($digits === '' || strlen($digits) > 10
        || (strlen($digits) === 10 && strcmp($digits, '2147483647') > 0)) {
        return null;
    }
    return (int) $digits;
}

/**
 * Выводит страницу или текст ошибки и завершает запрос.
 *
 * @param int $code Статус ошибки.
 * @param string $message Фиксированный текст для пользователя.
 * @return never
 */
function httpError(int $code, string $message): void
{
    http_response_code($code);
    if (in_array($code,[403,404,409,422],true)) {
        (new App\View())->render('error',['page'=>'error','code'=>$code,'message'=>$message,
            'title'=>[403=>'Доступ ограничен',404=>'Страница не найдена',409=>'Заявка изменилась',422=>'Проверьте данные'][$code]]);
        exit;
    }
    header('Content-Type: text/plain; charset=UTF-8');
    echo $message;
    exit;
}

/**
 * Проверяет допустимость HTTP-метода для обработчика.
 *
 * @param string $method Метод текущего запроса.
 * @param string[] $allowed Методы точки входа.
 * @return void
 */
function requireMethod(string $method, array $allowed): void
{
    if (!in_array($method, $allowed, true)) {
        header('Allow: ' . implode(', ', $allowed));
        httpError(405, 'Метод запроса не поддерживается.');
    }
}
/**
 * Перенаправляет клиента и при необходимости сохраняет уведомление.
 *
 * @param string $url Внутренний адрес приложения.
 * @param string $notice Однократное подтверждение операции.
 * @return never Завершает запрос перенаправлением.
 */
function redirect(string $url, string $notice = ''): never
{
    if ($notice !== '') { $_SESSION['notice'] = $notice; }
    header('Location: '.$url,true,303);
    exit;
}

/**
 * Проверяет строковое поле запроса и удаляет внешние пробелы.
 *
 * @param array $data Поля запроса.
 * @param string $name Имя поля.
 * @return string Значение в UTF-8 без внешних пробелов или пустая строка для отсутствующего поля.
 */
function field(array $data,string $name): string
{
    $value=$data[$name] ?? '';
    if (!is_string($value) || !mb_check_encoding($value,'UTF-8') || str_contains($value,"\0")) {
        httpError(422,'Недопустимое значение поля.');
    }
    return trim($value);
}