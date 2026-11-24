<?php
declare(strict_types=1);

use App\Database;

if (PHP_SAPI !== 'cli') { exit(1); }
require dirname(__DIR__) . '/vendor/autoload.php';
try {
    $db = (new Database(require dirname(__DIR__) . '/config/database.php'))->getConnection();
    $db->beginTransaction();
    $db->exec('LOCK TABLE departments,categories,users,budgets,expenses IN EXCLUSIVE MODE');
    foreach (['departments','categories','users','budgets','expenses'] as $table) {
        if ($db->query("SELECT EXISTS(SELECT 1 FROM $table)")->fetchColumn()) {
            $db->rollBack();
            exit("База уже содержит данные. Наполнение пропущено.\n");
        }
    }
    $password = getenv('COSTRA_DEMO_PASSWORD');
    if (!$password || !mb_check_encoding($password,'UTF-8') || mb_strlen($password)<8 || strlen($password)>72 || str_contains($password,"\0")) {
        throw new InvalidArgumentException();
    }
    $dept = $db->prepare('INSERT INTO departments(name,description) VALUES (?,?) RETURNING id');
    $budget = $db->prepare("INSERT INTO budgets(department_id,month,amount) VALUES (?,'2026-11-01',?)");
    foreach ([['Administration','Административное управление',10000],['IT & Technology','Технологическая инфраструктура',12000],['Logistics','Доставка и транспорт',8000],['Marketing','Продвижение предприятия',8000]] as [$name,$description,$limit]) {
        $dept->execute([$name,$description]);
        $departments[] = $id = $dept->fetchColumn();
        $budget->execute([$id,$limit]);
    }
    $cat = $db->prepare('INSERT INTO categories(name,description) VALUES (?,?) RETURNING id');
    foreach (['Office'=>'Канцелярия и материалы для офиса','Transport'=>'Доставка, топливо и поездки','Food'=>'Питание и деловые встречи','Equipment'=>'Техника и оснащение рабочих мест','Services'=>'Подписки и услуги подрядчиков','Other'=>'Прочие хозяйственные расходы'] as $name=>$description) {
        $cat->execute([$name,$description]);
        $categories[] = $cat->fetchColumn();
    }
    $user = $db->prepare('INSERT INTO users(username,full_name,department_id,role,password_hash) VALUES (?,?,?,?,?) RETURNING id');
    foreach ([['admin','Alex Morgan',0],['diana','Diana Brooks',0],['daniel','Daniel Reed',1],['victor','Victor Hayes',2],['elena','Elena Parker',3]] as [$login,$name,$department]) {
        $user->execute([$login,$name,$departments[$department],$login==='admin'?'admin':'employee',password_hash($password,PASSWORD_BCRYPT)]);
        $users[$login]=$user->fetchColumn();
    }
    $expense=$db->prepare('INSERT INTO expenses(title,amount,created_at,expense_date,category_id,merchant,description,is_recurring,user_id,status,reviewed_by,reviewed_at,review_note) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)');
    foreach ([
        ['Office paper','245.50','2026-10-28 11:20:00',0,'OfficeMarket','Paper and folders for the office',0,'diana','approved'],
        ['Internet payment','650.00','2026-10-30 09:15:00',4,'NetProvider','Monthly internet payment for the office',1,'daniel','approved'],
        ['Delivery fuel','420.75','2026-11-01 16:40:00',1,'Petrol Station','Fuel for company delivery car',0,'victor','approved'],
        ['New keyboard','899.90','2026-11-02 14:10:00',3,'TechMarket','Keyboard for the accounting office',0,'daniel','pending'],
        ['Coffee for meeting','185.50','2026-11-08 14:58:22',2,'Coffee Point','Coffee and snacks for team meeting',0,'diana','approved'],
        ['Monthly software subscription','320.00','2026-11-08 15:19:24',4,'SoftCloud','Monthly subscription for company software',1,'daniel','approved'],
        ['Server maintenance','7200.00','2026-11-09 12:20',4,'DataLab','Scheduled server rack maintenance',0,'daniel','approved'],
        ['Delivery services','4600.00','2026-11-12 09:30',1,'Cargo Express','Delivery of equipment to branches',0,'victor','approved'],
        ['Office furniture','3800.00','2026-11-15 13:00',3,'Desk Studio','Desks for the administration office',0,'diana','approved'],
        ['Advertising campaign','6300.00','2026-11-18 10:00',4,'Media Group','November advertising placements',0,'elena','approved'],
        ['Promotional materials','2400.00','2026-11-21 14:00',0,'Print House','Printed brochures for the exhibition',0,'elena','pending'],
        ['Premium software license','1800.00','2026-11-23 11:45',4,'SoftCloud','Additional premium editing license',0,'daniel','rejected']
    ] as [$title,$amount,$time,$category,$merchant,$description,$regular,$owner,$status]) {
        $reviewed=$status!=='pending';
        $expense->execute([$title,$amount,$time,substr($time,0,10),$categories[$category],$merchant,$description,$regular,$users[$owner],$status,$reviewed?$users['admin']:null,$reviewed?$time:null,$status==='rejected'?'Предприятие уже использует лицензию с аналогичной функциональностью':null]);
    }
    $db->commit();
    echo "Демонстрационные данные добавлены.\n";
} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) { $db->rollBack(); }
    fwrite(STDERR,"Наполнение прервано. Проверьте подключение, схему и демопароль (от 8 символов до 72 байт).\n");
    exit(1);
}