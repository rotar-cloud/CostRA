<?php

declare(strict_types=1);

namespace App;

use PDO;
use UnexpectedValueException;

/** Хранит расходы и читает справочник категорий через PostgreSQL. */
final class ExpenseRepository
{
    private const RECORDS = 'SELECT e.*, c.name category_name, u.full_name, d.id department_id,
        d.name department, r.full_name reviewer FROM expenses e JOIN categories c ON c.id=e.category_id
        JOIN users u ON u.id=e.user_id JOIN departments d ON d.id=u.department_id LEFT JOIN users r ON r.id=e.reviewed_by';

    /** @param PDO $pdo Открытое соединение приложения. */
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * Добавляет расход с ID и временем создания, назначенными БД.
     * @param Expense $expense Данные нового расхода.
     * @param int $owner Владелец, проверенный контроллером.
     * @return int Назначенный ID.
     */
    public function createRecord(Expense $expense, int $owner): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO expenses
            (user_id, title, amount, expense_date, category_id, merchant, description, is_recurring)
            VALUES (:owner, :title, :amount, :expense_date, :category_id, :merchant, :description, :is_recurring)
            RETURNING id');
        $stmt->bindValue(':owner', $owner, PDO::PARAM_INT);
        $stmt->bindValue(':title', $expense->getTitle(), PDO::PARAM_STR);
        $stmt->bindValue(':amount', $expense->getAmount(), PDO::PARAM_STR);
        $stmt->bindValue(':expense_date', $expense->getExpenseDate(), PDO::PARAM_STR);
        $stmt->bindValue(':category_id', $expense->getCategoryId(), PDO::PARAM_INT);
        $stmt->bindValue(':merchant', $expense->getMerchant(), PDO::PARAM_STR);
        $stmt->bindValue(':description', $expense->getDescription(), PDO::PARAM_STR);
        $stmt->bindValue(':is_recurring', $expense->isRecurring(), PDO::PARAM_BOOL);
        $stmt->execute();
        $id = $stmt->fetchColumn();
        if (!is_int($id)) {
            throw new UnexpectedValueException('База не вернула целый ID расхода.');
        }
        return $id;
    }


    /** @return array Категории для публичной страницы и форм. */
    public function getCategories(): array
    {
        return $this->pdo->query('SELECT * FROM categories ORDER BY id')->fetchAll();
    }

    /**
     * @param ?int $owner ID сотрудника или null для администратора.
     * @param array $filters Проверенные поля поиска.
     * @param bool $recent Ограничивает список пятью последними заявками.
     * @return array Расходы в пределах прав пользователя и выбранных фильтров.
     */
    public function search(?int $owner, array $filters, bool $recent = false): array
    {
        $where = ['true'];
        $params = [];
        if ($owner !== null) {
            $where[] = 'e.user_id=?';
            $params[] = $owner;
        }
        $columns = ['id'=>'e.id =', 'category_id'=>'e.category_id =', 'department_id'=>'d.id =',
            'status'=>'e.status =', 'from'=>'e.expense_date >=', 'to'=>'e.expense_date <='];
        foreach ($columns as $name=>$expression) {
            if (($filters[$name] ?? '') !== '') {
                $where[] = $expression . ' ?';
                $params[] = $filters[$name];
            }
        }
        if (($filters['q'] ?? '') !== '') {
            $where[] = "(e.title ILIKE ? OR e.merchant ILIKE ? OR e.description ILIKE ?)";
            array_push($params, ...array_fill(0,3,'%'.$filters['q'].'%'));
        }
        $sort = ($filters['sort'] ?? '') === 'amount' ? 'e.amount' : 'e.expense_date';
        $stmt = $this->pdo->prepare(self::RECORDS.' WHERE '.implode(' AND ', $where)." ORDER BY $sort DESC,e.id DESC".($recent ? ' LIMIT 5' : ''));
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * @param string $month Первый день отчётного месяца.
     * @param ?int $owner Владелец или null для обзора предприятия.
     * @return array Количество и сумма по каждому представленному статусу.
     */
    public function monthlyTotals(string $month, ?int $owner): array
    {
        $sql = "SELECT status, count(*) count, sum(amount) amount FROM expenses
            WHERE expense_date >= CAST(? AS date) AND expense_date < CAST(? AS date) + interval '1 month'";
        $params = [$month, $month];
        if ($owner !== null) {
            $sql .= ' AND user_id=?';
            $params[] = $owner;
        }
        $stmt = $this->pdo->prepare($sql.' GROUP BY status');
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * @param int $id ID расхода.
     * @param Expense $expense Проверенные поля формы.
     * @param ?int $owner ID сотрудника или null для администратора.
     * @return bool Запись доступна и сохранена с учётом текущего решения.
     */
    public function updateRecord(int $id, Expense $expense, ?int $owner): bool
    {
        // Блокировка не даёт согласовать заявку между проверкой статуса и обновлением.
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM expenses WHERE id=? FOR UPDATE');
            $stmt->execute([$id]);
            $old = $stmt->fetch();
            if (!$old || ($owner !== null && ($old['user_id'] !== $owner || $old['status'] !== 'pending'))) {
                $this->pdo->rollBack();
                return false;
            }
            $fields = ['title'=>$expense->getTitle(), 'amount'=>$expense->getAmount(),
                'expense_date'=>$expense->getExpenseDate(), 'category_id'=>$expense->getCategoryId(),
                'merchant'=>$expense->getMerchant(), 'description'=>$expense->getDescription(),
                'is_recurring'=>$expense->isRecurring()];
            // Сравнение по типу сохраняет различие, например, между названиями «001» и «0001».
            $changed = false;
            foreach ($fields as $name => $value) {
                if ($old[$name] !== $value) {
                    $changed = true;
                    break;
                }
            }
            if ($changed) {
                $fields['is_recurring'] = (int)$expense->isRecurring();
                $sql = "UPDATE expenses SET title=:title, amount=:amount, expense_date=:expense_date,
                    category_id=:category_id, merchant=:merchant, description=:description, is_recurring=:is_recurring,
                    status='pending', reviewed_by=NULL, reviewed_at=NULL, review_note=NULL WHERE id=:id";
                $this->pdo->prepare($sql)->execute($fields + ['id'=>$id]);
            }
            $this->pdo->commit();
            return true;
        } catch (\Throwable $error) {
            $this->pdo->rollBack();
            throw $error;
        }
    }

    /**
     * @param int $id ID заявки.
     * @param int $reviewer Администратор из сессии.
     * @param string $status Решение администратора.
     * @param string $note Комментарий к решению.
     * @return bool Решение принято, пока заявка ожидала рассмотрения.
     */
    public function review(int $id, int $reviewer, string $status, string $note): bool
    {
        $stmt = $this->pdo->prepare("UPDATE expenses SET status=?, reviewed_by=?, reviewed_at=greatest(now(),created_at),
            review_note=? WHERE id=? AND status='pending'");
        $stmt->execute([$status,$reviewer,$note,$id]);
        return $stmt->rowCount() === 1;
    }

    /**
     * @param int $id Удаляемая заявка.
     * @return bool Расход был удалён.
     */
    public function deleteRecord(int $id): bool
    {
        $stmt=$this->pdo->prepare('DELETE FROM expenses WHERE id=?');
        $stmt->execute([$id]);
        return $stmt->rowCount() === 1;
    }
}