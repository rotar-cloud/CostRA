<?php

declare(strict_types=1);

namespace App;

/** Проверяет поля расхода по единым правилам создания и редактирования. */
final class ExpenseValidator
{
    /**
     * @param array $data Строковые поля формы и необязательный checkbox со значением '1'.
     * @param int[] $categoryIds Допустимые ID из справочника.
     * @return array<string, string> Сообщения по полям или пустой массив.
     */
    public function validate(array $data, array $categoryIds): array
    {
        $errors = [];
        $lengths = ['title' => [3, 80], 'merchant' => [2, 80], 'description' => [5, 500]];
        foreach (['title', 'merchant', 'description', 'amount', 'expense_date', 'category_id'] as $field) {
            $value = $data[$field] ?? '';
            if (!is_string($value) || !mb_check_encoding($value, 'UTF-8') || str_contains($value, "\0")) {
                $errors[$field] = 'Ожидается строка в UTF-8 без нулевого байта.';
                continue;
            }

            $value = trim($value);
            if ($value === '') {
                $errors[$field] = 'Заполните поле.';
            } elseif (isset($lengths[$field])) {
                [$min, $max] = $lengths[$field];
                $length = mb_strlen($value, 'UTF-8');
                if ($length < $min || $length > $max) {
                    $errors[$field] = "Введите от $min до $max символов.";
                }
            } elseif ($field === 'amount' && !$this->validAmount($value)) {
                $errors[$field] = 'Введите сумму от 0.01 до 9999999999.99 с точностью до двух знаков.';
            } elseif ($field === 'expense_date' && !$this->validDate($value)) {
                $errors[$field] = 'Введите существующую дату в формате YYYY-MM-DD.';
            } elseif ($field === 'category_id' && !$this->validCategory($value, $categoryIds)) {
                $errors[$field] = 'Выберите допустимую категорию.';
            }
        }

        if (array_key_exists('is_recurring', $data) && $data['is_recurring'] !== '1') {
            $errors['is_recurring'] = 'Недопустимое значение отметки.';
        }
        return $errors;
    }

    /**
     * @param string $value Сумма без внешних пробелов.
     * @return bool Сумма положительна и помещается в NUMERIC(12,2) без округления.
     */
    public function validAmount(string $value): bool
    {
        if (!preg_match('/\A([0-9]+)(?:\.([0-9]{1,2}))?\z/', $value, $parts)) {
            return false;
        }
        $whole = ltrim($parts[1], '0');
        return strlen($whole) <= 10 && ($whole !== '' || trim($parts[2] ?? '', '0') !== '');
    }

    /**
     * @param string $value Дата формы.
     * @return bool Дата соответствует формату и календарю.
     */
    public function validDate(string $value): bool
    {
        return preg_match('/\A([0-9]{4})-([0-9]{2})-([0-9]{2})\z/', $value, $parts)
            && checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1]);
    }

    /**
     * @param string $value ID из формы.
     * @param int[] $categoryIds ID из базы.
     * @return bool Положительный ID входит в диапазон SERIAL и присутствует в справочнике.
     */
    private function validCategory(string $value, array $categoryIds): bool
    {
        $digits = ltrim($value, '0');
        if (!preg_match('/\A[0-9]+\z/', $value) || $digits === '' || strlen($digits) > 10
            || (strlen($digits) === 10 && strcmp($digits, '2147483647') > 0)) {
            return false;
        }
        return in_array((int) $digits, $categoryIds, true);
    }
}