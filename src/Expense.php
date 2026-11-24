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

    /** @return string Название расхода. */
    public function getTitle(): string
    {
        return $this->title;
    }

    /** @return string Десятичная сумма с двумя знаками после точки. */
    public function getAmount(): string
    {
        return $this->amount;
    }

    /** @return string Дата расхода. */
    public function getExpenseDate(): string
    {
        return $this->expenseDate;
    }

    /** @return int ID категории. */
    public function getCategoryId(): int
    {
        return $this->categoryId;
    }

    /** @return string Получатель платежа. */
    public function getMerchant(): string
    {
        return $this->merchant;
    }

    /** @return string Описание расхода. */
    public function getDescription(): string
    {
        return $this->description;
    }

    /** @return bool Признак регулярного расхода. */
    public function isRecurring(): bool
    {
        return $this->isRecurring;
    }

    /** @return ?int ID сохранённой записи. */
    public function getId(): ?int
    {
        return $this->id;
    }

    /** @return ?string Название категории из справочника. */
    public function getCategoryName(): ?string
    {
        return $this->categoryName;
    }

    /** @return ?string Время создания записи в БД. */
    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }
}
