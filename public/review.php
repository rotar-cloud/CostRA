<?php
require dirname(__DIR__).'/bootstrap.php';
$id=parseExpenseId($_POST['id'] ?? null) ?? 0;
$status=field($_POST,'decision');
$note=field($_POST,'note');
if (!in_array($status,['approved','rejected'],true) || ($status==='rejected' && $note==='')) {
    http_response_code(422);
    $reviewError=$status==='rejected'?'Для отклонения укажите причину.':'Выберите допустимое решение.';
    require dirname(__DIR__).'/expense-detail.php';
    return;
}
if (!$repo->review($id,$user['id'],$status,$note)) { httpError(409,'Заявка уже рассмотрена или удалена.'); }
redirect('expense.php?id='.$id,'Решение сохранено.');