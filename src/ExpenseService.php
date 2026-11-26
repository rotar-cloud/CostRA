<?php

declare(strict_types=1);

namespace App;

/** Проверяет данные формы и сохраняет допустимый расход. */
final class ExpenseService
{
    /**
     * Связывает проверку полей с хранилищем расходов.
     *
     * @param ExpenseRepository $repository Доступ к расходам и справочнику.
     * @param ExpenseValidator $validator Общие правила проверки полей.
     */
    public function __construct(
        private ExpenseRepository $repository,
        private ExpenseValidator $validator,
    ) {
    }

    /**
     * Проверяет и создаёт расход для выбранного владельца.
     *
     * @param array $input Исходные поля формы создания.
     * @param int $owner Проверенный ID владельца.
     * @return array При saved содержит id, при invalid — errors и formData.
     */
    public function create(array $input, int $owner): array
    {
        return $this->save($input, null, $owner);
    }

    /**
     * Проверяет поля и сохраняет изменения доступного расхода.
     *
     * @param int $id ID редактируемого расхода.
     * @param array $input Исходные поля формы редактирования.
     * @param ?int $owner ID сотрудника или null для администратора.
     * @return array saved с id, invalid с errors и formData либо только status = missing.
     */
    public function update(int $id, array $input, ?int $owner): array
    {
        return $this->save($input, $id, $owner);
    }

    /**
     * Готовит поля к повторному отображению и записывает расход после проверки.
     * @param array $input Исходные значения.
     * @param int|null $id ID для обновления или null для создания.
     * @param ?int $owner Владелец для создания или ограничения редактирования.
     * @return array Результат операции со статусом saved, invalid или missing.
     */
    private function save(array $input, ?int $id, ?int $owner): array
    {
        $formData = [];
        $errors = [];
        foreach (['title', 'amount', 'expense_date', 'category_id', 'merchant', 'description'] as $field) {
            $value = array_key_exists($field, $input) ? $input[$field] : '';
            if (!is_string($value) || !mb_check_encoding($value, 'UTF-8') || str_contains($value, "\0")) {
                $errors[$field] = 'Ожидается строка в UTF-8 без нулевого байта.';
                $value = '';
            }
            $formData[$field] = trim($value);
        }

        $data = $formData;
        if (array_key_exists('is_recurring', $input)) {
            $data['is_recurring'] = $input['is_recurring'];
        }
        $formData['is_recurring'] = ($input['is_recurring'] ?? null) === '1';
        $categoryIds = array_column($this->repository->getCategories(), 'id');
        $errors += $this->validator->validate($data, $categoryIds);
        if ($errors !== []) {
            return ['status' => 'invalid', 'errors' => $errors, 'formData' => $formData];
        }

        $parts = explode('.', $formData['amount']);
        $whole = ltrim($parts[0], '0');
        $amount = ($whole === '' ? '0' : $whole) . '.' . str_pad($parts[1] ?? '', 2, '0');
        $expense = new Expense(
            title: $formData['title'],
            amount: $amount,
            expenseDate: $formData['expense_date'],
            categoryId: (int) $formData['category_id'],
            merchant: $formData['merchant'],
            description: $formData['description'],
            isRecurring: $formData['is_recurring'],
        );
        if ($id === null) {
            $id = $this->repository->createRecord($expense, $owner);
        } elseif (!$this->repository->updateRecord($id, $expense, $owner)) {
            return ['status' => 'missing'];
        }
        return ['status' => 'saved', 'id' => $id];
    }
}