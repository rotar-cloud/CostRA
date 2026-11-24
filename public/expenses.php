<?php
require dirname(__DIR__).'/bootstrap.php';
$filters=[];
foreach (['q','category_id','from','to','sort','status','department_id'] as $key) {
    $filters[$key]=field($_GET,$key);
}
if (!$admin) { unset($filters['department_id']); }
foreach (['category_id','department_id'] as $key) {
    if (($filters[$key] ?? '')!=='' && !parseExpenseId($filters[$key])) { httpError(422,'Некорректный идентификатор.'); }
}
foreach (['from','to'] as $key) {
    $date=$filters[$key];
    if ($date!=='' && !$validator->validDate($date)) { httpError(422,'Некорректная дата.'); }
}
if ($filters['from']!=='' && $filters['to']!=='' && $filters['from']>$filters['to']) {
    httpError(422,'Конец периода должен быть не раньше начала.');
}
if ($filters['status']!=='' && !isset($context['statuses'][$filters['status']])) {
    httpError(422,'Выберите доступный статус.');
}
$view->render('expenses',$context+['title'=>'Расходы','rows'=>$repo->search($scope,$filters),'filters'=>$filters,
    'categories'=>$repo->getCategories(),'departments'=>$admin?$users->departments():[]]);