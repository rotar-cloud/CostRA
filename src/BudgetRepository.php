<?php
declare(strict_types=1);
namespace App;
use PDO;

/** Лимиты и вычисляемые финансовые показатели подразделений. */
final class BudgetRepository
{
    /** @param PDO $db Подключение CostRA. */
    public function __construct(private PDO $db) {}

    /**
     * @param string $month Отчётный месяц YYYY-MM.
     * @param ?int $department Подразделение или null для всех отделов.
     * @param string $proposal Сумма заявки для прогноза.
     * @return array Сводка отдела или всего предприятия за месяц, включая прогноз заявки.
     */
    public function summary(string $month,?int $department,string $proposal='0'): array
    {
        $sql="WITH period AS (SELECT CAST(? AS date) first_day, CAST(? AS numeric) proposal), totals AS (
            SELECT u.department_id, SUM(e.amount) FILTER(WHERE status='approved') approved,
            SUM(e.amount) FILTER(WHERE status='pending') pending FROM expenses e JOIN users u ON u.id=e.user_id
            CROSS JOIN period WHERE expense_date >= first_day AND expense_date < first_day + interval '1 month'
            GROUP BY u.department_id)
            SELECT d.name,b.amount,coalesce(t.approved,0) approved,coalesce(t.pending,0) pending,
            b.amount-coalesce(t.approved,0) remaining,coalesce(t.approved,0)+proposal projected,
            coalesce(t.approved,0)+proposal-b.amount excess,
            round(100*(coalesce(t.approved,0)+proposal)/b.amount,2) projected_percent,
            round(100*coalesce(t.approved,0)/b.amount,2) percent
            FROM departments d CROSS JOIN period LEFT JOIN totals t ON t.department_id=d.id
            LEFT JOIN budgets b ON b.department_id=d.id AND b.month=first_day";
        $params=[$month.'-01',$proposal];
        if ($department!==null) { $sql.=' WHERE d.id=?'; $params[]=$department; }
        $stmt=$this->db->prepare($sql.' ORDER BY d.id');
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * @param int $dept Выбранное подразделение.
     * @param string $month Месяц лимита YYYY-MM.
     * @param string $amount Проверенная десятичная сумма.
     * @return void Сохраняет единственный лимит отдела на месяц.
     */
    public function save(int $dept,string $month,string $amount): void
    {
        $this->db->prepare('INSERT INTO budgets(department_id,month,amount) VALUES (?,?,?)
            ON CONFLICT(department_id,month) DO UPDATE SET amount=excluded.amount')->execute([$dept,$month.'-01',$amount]);
    }
}