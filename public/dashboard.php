<?php
require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__).'/month.php';

$firstDay = $month.'-01';
$counts = array_fill_keys(array_keys($context['statuses']), 0);
$totals = [];
foreach ($repo->monthlyTotals($firstDay, $scope) as $group) {
    $counts[$group['status']] = $group['count'];
    $totals[$group['status']] = $group['amount'];
}
$period = ['from'=>$firstDay, 'to'=>date('Y-m-t', strtotime($firstDay))];
$view->render('dashboard', $context + compact('counts','totals','month') + [
    'title'=>'Финансовый обзор',
    'total'=>array_sum($counts),
    'rows'=>$repo->search($scope,$period,true),
    'budgets'=>$budgets->summary($month,$admin ? null : $user['department_id']),
]);