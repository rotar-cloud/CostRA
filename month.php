<?php
$month=field($post?$_POST:$_GET,'month');
if ($month==='' && !$post) { $month=date('Y-m'); }
if (!$validator->validDate($month.'-01')) { httpError(422,'Выберите корректный месяц.'); }