<?php
require dirname(__DIR__).'/bootstrap.php';
$id=parseExpenseId($_GET['id'] ?? null) ?? 0;
require dirname(__DIR__).'/expense-detail.php';