<?php
require __DIR__ . '/app/bootstrap.php';
require_installed();
$u = require_login();

$emp = null;
if (!empty($u['employee_id'])) {
    $st = db()->prepare(
        'SELECT e.*, d.name AS department_name
         FROM employees e JOIN departments d ON d.id = e.department_id
         WHERE e.id = ?'
    );
    $st->execute([(int) $u['employee_id']]);
    $emp = $st->fetch();
}

$pageTitle = 'حسابي';
require __DIR__ . '/app/layout_header.php';
?>
<div class="card">
    <h3><?= h($u['name']) ?></h3>
    <p>البريد: <span dir="ltr"><?= h($u['email']) ?></span></p>
    <p>الدور: <?= h($u['role_name']) ?></p>
    <?php if ($emp): ?>
        <p>الرقم الوظيفي: <?= h($emp['employee_no']) ?></p>
        <p>القسم: <?= h($emp['department_name']) ?></p>
        <p>المسمى: <?= h($emp['job_title']) ?></p>
        <p>رصيد الإجازة السنوية: <?= (int) $emp['annual_balance'] ?> يوم</p>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/app/layout_footer.php'; ?>
