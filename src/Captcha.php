<?php
declare(strict_types=1);
namespace App;

/** Одноразовые математические примеры для публичных форм. */
final class Captcha
{
    /**
     * Заменяет пример выбранной формы и сохраняет ответ на пять минут.
     * @param string $form Имя публичной формы входа или регистрации.
     * @return string Вопрос для отображения в шаблоне.
     */
    public function issue(string $form): string
    {
        $left=random_int(1,9);
        $right=random_int(1,9);
        $_SESSION['captcha'][$form]=['answer'=>$left+$right,'expires'=>time()+300];
        return "$left + $right = ?";
    }

    /**
     * Отзывает текущий пример формы, в том числе при отказе CSRF-проверки.
     * @param string $form Имя публичной формы.
     * @return void
     */
    public function discard(string $form): void
    {
        unset($_SESSION['captcha'][$form]);
    }

    /**
     * Погашает пример и сверяет ответ с учётом срока действия.
     * @param string $form Имя публичной формы.
     * @param mixed $answer Исходное значение поля ответа.
     * @return string|null Сообщение у поля при ошибке или null при верном ответе.
     */
    public function verify(string $form,mixed $answer): ?string
    {
        $challenge=$_SESSION['captcha'][$form] ?? null;
        $this->discard($form);
        if (!$challenge || time()>=$challenge['expires']) {
            return 'Срок действия примера истёк или он уже использован. Решите новый пример.';
        }
        if ($answer===null || $answer==='') { return 'Введите ответ на новый пример.'; }
        if (!is_string($answer) || !preg_match('/\A[0-9]+\z/',$answer)) {
            return 'Введите ответ на новый пример цифрами.';
        }
        return ltrim($answer,'0')===(string)$challenge['answer'] ? null : 'Неверный ответ. Решите новый пример.';
    }
}
