<?php
require dirname(__DIR__).'/bootstrap.php';
if (!$repo->deleteRecord(parseExpenseId($_POST['id'] ?? null) ?? 0)) { httpError(404,'Расход не найден.'); }
redirect('expenses.php','Заявка удалена.');