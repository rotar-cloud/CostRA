<?php
$row=$repo->search($scope,['id'=>$id])[0] ?? null;
if (!$row) { httpError(404,'Расход не найден.'); }
if (isset($reviewError) && $row['status']!=='pending') { httpError(409,'Заявка уже рассмотрена.'); }
$budget=$budgets->summary(substr($row['expense_date'],0,7),$row['department_id'],$row['amount'])[0];
$view->render('expense',$context+compact('row','budget')+['title'=>$row['title'], 'reviewError'=>$reviewError ?? null,
    'reviewNote'=>isset($reviewError)?($_POST['note'] ?? ''):'', 'decision'=>$status ?? '']);