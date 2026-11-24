<?php
$editing=$page==='edit';
$owners=$admin && !$editing?$users->all():[];
$errors=[];
$formData=['expense_date'=>date('Y-m-d'),'user_id'=>$user['id']];
if ($editing) {
    $id=parseExpenseId($_GET['id'] ?? null) ?? 0;
    $formData=$repo->search($scope,['id'=>$id])[0] ?? null;
    if (!$formData) { httpError(404,'Расход не найден.'); }
    if (!$admin && $formData['status']!=='pending') { httpError(403,'Расход уже рассмотрен.'); }
}
if ($post) {
    $owner=$admin && !$editing?parseExpenseId($_POST['user_id'] ?? null):$user['id'];
    if (!$editing && $admin && !in_array($owner,array_column($owners,'id'),true)) {
        $errors['user_id']='Выберите существующего владельца.';
        $formData=array_filter($_POST,'is_string');
        $formData['is_recurring']=($_POST['is_recurring'] ?? null)==='1';
    } else {
        $result=$editing?$service->update($id,$_POST,$scope):$service->create($_POST,$owner);
        if ($result['status']==='saved') { redirect('expense.php?id='.$result['id'],$editing?'Изменения сохранены.':'Заявка создана.'); }
        if ($result['status']==='missing') { httpError(403,'Заявка больше недоступна для изменения.'); }
        $errors=$result['errors'];
        $formData=$result['formData']+['user_id'=>$owner];
    }
    http_response_code(422);
}
$view->render('form',$context+compact('owners','formData','errors')+['title'=>$editing?'Изменить расход':'Новый расход','categories'=>$repo->getCategories()]);