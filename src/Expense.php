<?php

declare(strict_types=1);

namespace App;

/** Один расход предприятия. */
final class Expense
{
    /**
     * Создаёт модель из проверенных полей формы или строки выборки.
     *
     * @param string $title Название расхода.
     * @param string $amount Десятичная сумма с двумя знаками после точки.
     * @param string $expenseDate Дата расхода.
     * @param int $categoryId ID категории.
     * @param string $merchant Получатель платежа.
     * @param string $description Описание расхода.
     * @param bool $isRecurring Признак регулярного расхода.
     * @param ?int $id ID сохранённой записи.
     * @param ?string $categoryName Название категории из справочника.
     * @param ?string $createdAt Время создания записи в БД.
     */
    public function __construct(
        private string $title,
        private string $amount,
        private string $expenseDate,
        private int $categoryId,
        private string $merchant,
        private string $description,
        private bool $isRecurring,
        private ?int $id = null,
        private ?string $categoryName = null,
        private ?string $createdAt = null,
    ) {
    }

    /**
     * Возвращает название расхода.
     *
     * @return string Название расхода.
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Возвращает сумму без преобразования в число с плавающей точкой.
     *
     * @return string Десятичная сумма с двумя знаками после точки.
     */
    public function getAmount(): string
    {
        return $this->amount;
    }

    /**
     * Возвращает календарную дату расхода.
     *
     * @return string Дата расхода.
     */
    public function getExpenseDate(): string
    {
        return $this->expenseDate;
    }

    /**
     * Возвращает идентификатор выбранной категории.
     *
     * @return int ID категории.
     */
    public function getCategoryId(): int
    {
        return $this->categoryId;
    }

    /**
     * Возвращает наименование получателя платежа.
     *
     * @return string Получатель платежа.
     */
    public function getMerchant(): string
    {
        return $this->merchant;
    }

    /**
     * Возвращает пояснение к расходу.
     *
     * @return string Описание расхода.
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Показывает, отмечен ли расход как регулярный.
     *
     * @return bool Признак регулярного расхода.
     */
    public function isRecurring(): bool
    {
        return $this->isRecurring;
    }

    /**
     * Возвращает идентификатор записи, если он задан.
     *
     * @return ?int ID сохранённой записи.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Возвращает название категории, если оно передано из справочника.
     *
     * @return ?string Название категории из справочника.
     */
    public function getCategoryName(): ?string
    {
        return $this->categoryName;
    }

    /**
     * Возвращает время создания, если оно получено из БД.
     *
     * @return ?string Время создания записи в БД.
     */
    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }
}
