<?php
require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__).'/month.php';
$departments=$admin?$users->departments():[];
if ($post) {
    $dept=parseExpenseId($_POST['department_id'] ?? null);
    $amount=field($_POST,'amount');
    if (!in_array($dept,array_column($departments,'id'),true) || !$validator->validAmount($amount)) {
        httpError(422,'Выберите отдел и введите положительный лимит с точностью до двух знаков.');
    }
    $budgets->save($dept,$month,$amount);
    redirect('budgets.php?month='.$month,'Месячный лимит сохранён.');
}
$view->render('budgets',$context+compact('month','departments')+['title'=>'Месячные бюджеты',
    'budgets'=>$budgets->summary($month,$admin?null:$user['department_id'])]);