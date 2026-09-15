<?php
require __DIR__ . '/app/bootstrap.php';
require_installed();
$u = require_login();

$scope = department_scope_id();
$own = own_employee_id();
$pdo = db();

if (can('employees.view_all')) {
    $empCount = (int) $pdo->query("SELECT COUNT(*) FROM employees WHERE status = 'active'")->fetchColumn();
} elseif ($scope && $scope > 0) {
    $st = $pdo->prepare("SELECT COUNT(*) FROM employees WHERE status = 'active' AND department_id = ?");
    $st->execute([$scope]);
    $empCount = (int) $st->fetchColumn();
} else {
    $empCount = $own ? 1 : 0;
}

if (can('employees.view_all')) {
    $pending = (int) $pdo->query("SELECT COUNT(*) FROM leaves WHERE status = 'pending'")->fetchColumn();
} elseif ($scope && $scope > 0) {
    $st = $pdo->prepare("SELECT COUNT(*) FROM leaves l JOIN employees e ON e.id = l.employee_id WHERE l.status = 'pending' AND e.department_id = ?");
    $st->execute([$scope]);
    $pending = (int) $st->fetchColumn();
} elseif ($own) {
    $st = $pdo->prepare("SELECT COUNT(*) FROM leaves WHERE status = 'pending' AND employee_id = ?");
    $st->execute([$own]);
    $pending = (int) $st->fetchColumn();
} else {
    $pending = 0;
}

$today = today();
if (can('employees.view_all')) {
    $st = $pdo->prepare('SELECT COUNT(*) FROM attendance WHERE work_date = ? AND check_in IS NOT NULL');
    $st->execute([$today]);
    $present = (int) $st->fetchColumn();
} elseif ($scope && $scope > 0) {
    $st = $pdo->prepare('SELECT COUNT(*) FROM attendance a JOIN employees e ON e.id = a.employee_id WHERE a.work_date = ? AND a.check_in IS NOT NULL AND e.department_id = ?');
    $st->execute([$today, $scope]);
    $present = (int) $st->fetchColumn();
} elseif ($own) {
    $st = $pdo->prepare('SELECT COUNT(*) FROM attendance WHERE work_date = ? AND employee_id = ? AND check_in IS NOT NULL');
    $st->execute([$today, $own]);
    $present = (int) $st->fetchColumn();
} else {
    $present = 0;
}

$pageTitle = 'لوحة التحكم';
require __DIR__ . '/app/layout_header.php';
?>
<div class="grid stats">
    <div class="card">
        <div class="stat-label">الموظفون</div>
        <div class="stat-value"><?= (int) $empCount ?></div>
    </div>
    <div class="card">
        <div class="stat-label">إجازات قيد الانتظار</div>
        <div class="stat-value"><?= (int) $pending ?></div>
    </div>
    <div class="card">
        <div class="stat-label">حضور اليوم</div>
        <div class="stat-value"><?= (int) $present ?></div>
    </div>
</div>
<div class="card" style="margin-top:16px">
    <h3>مرحباً <?= h($u['name']) ?></h3>
    <p class="hint">دورك الحالي: <?= h($u['role_name']) ?>. القائمة على اليمين تعرض فقط ما تسمح به صلاحياتك.</p>
</div>
<?php require __DIR__ . '/app/layout_footer.php'; ?>
