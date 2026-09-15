<?php
require __DIR__ . '/app/bootstrap.php';
require_installed();
$u = require_login();
if (!can('leaves.view') && !can('leaves.create') && !can('leaves.approve')) {
    require_can('leaves.view');
}
$pdo = db();

function leave_rows_sql(): string
{
    return 'SELECT l.*, e.full_name, e.department_id, e.annual_balance, d.name AS department_name
            FROM leaves l
            JOIN employees e ON e.id = l.employee_id
            JOIN departments d ON d.id = e.department_id';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = post('action');

    if ($action === 'create') {
        require_can('leaves.create');
        $own = own_employee_id();
        $employeeId = can('employees.view_all') ? (int) post('employee_id') : (int) $own;
        if (!$employeeId) {
            flash('error', 'لا يوجد ملف موظف مرتبط بحسابك.');
            redirect('leaves.php');
        }
        $from = post('date_from');
        $to = post('date_to');
        $type = post('type');
        if (!in_array($type, ['annual', 'sick', 'emergency'], true) || !$from || !$to || $to < $from) {
            flash('error', 'تحقق من نوع الإجازة والتواريخ.');
            redirect('leaves.php?new=1');
        }
        $days = days_between($from, $to);
        $pdo->prepare('INSERT INTO leaves (employee_id, type, date_from, date_to, days, reason, status, created_at) VALUES (?,?,?,?,?,?,?,?)')
            ->execute([$employeeId, $type, $from, $to, $days, post('reason'), 'pending', date('c')]);
        flash('success', 'تم إرسال طلب الإجازة.');
        redirect('leaves.php');
    }

    if ($action === 'review') {
        require_can('leaves.approve');
        $id = (int) post('id');
        $status = post('status') === 'rejected' ? 'rejected' : 'approved';
        $st = $pdo->prepare(leave_rows_sql() . ' WHERE l.id = ?');
        $st->execute([$id]);
        $leave = $st->fetch();
        if (!$leave || !can_manage_leave_for($leave)) {
            flash('error', 'ليست لديك صلاحية اعتماد هذا الطلب.');
            redirect('leaves.php');
        }
        if ($leave['status'] !== 'pending') {
            flash('error', 'تمت معالجة الطلب مسبقاً.');
            redirect('leaves.php');
        }
        $pdo->prepare('UPDATE leaves SET status = ?, reviewed_by = ? WHERE id = ?')
            ->execute([$status, (int) $u['id'], $id]);
        if ($status === 'approved' && $leave['type'] === 'annual') {
            $balance = max(0, (int) $leave['annual_balance'] - (int) $leave['days']);
            $pdo->prepare('UPDATE employees SET annual_balance = ? WHERE id = ?')
                ->execute([$balance, (int) $leave['employee_id']]);
        }
        flash('success', $status === 'approved' ? 'تم اعتماد الإجازة.' : 'تم رفض الإجازة.');
        redirect('leaves.php');
    }
}

$scope = department_scope_id();
$own = own_employee_id();
$sql = leave_rows_sql();
if (can('employees.view_all')) {
    $rows = $pdo->query($sql . ' ORDER BY l.id DESC')->fetchAll();
} elseif ($scope && $scope > 0) {
    $st = $pdo->prepare($sql . ' WHERE e.department_id = ? ORDER BY l.id DESC');
    $st->execute([$scope]);
    $rows = $st->fetchAll();
} elseif ($own) {
    $st = $pdo->prepare($sql . ' WHERE l.employee_id = ? ORDER BY l.id DESC');
    $st->execute([$own]);
    $rows = $st->fetchAll();
} else {
    $rows = [];
}

$employees = [];
if (can('employees.view_all') && can('leaves.create')) {
    $employees = $pdo->query("SELECT id, full_name FROM employees WHERE status = 'active' ORDER BY full_name")->fetchAll();
}

$pageTitle = 'الإجازات';
require __DIR__ . '/app/layout_header.php';
?>
<div class="toolbar">
    <div class="hint">الطلبات حسب صلاحيتك الحالية.</div>
    <?php if (can('leaves.create')): ?>
        <a class="btn" href="leaves.php?new=1">طلب إجازة</a>
    <?php endif; ?>
</div>

<?php if (query('new') === '1' && can('leaves.create')): ?>
<div class="card" style="margin-bottom:16px">
    <h3>طلب إجازة</h3>
    <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="create">
        <?php if ($employees): ?>
            <label>الموظف
                <select name="employee_id" required>
                    <?php foreach ($employees as $emp): ?>
                        <option value="<?= (int) $emp['id'] ?>"><?= h($emp['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        <?php endif; ?>
        <div class="row">
            <label>النوع
                <select name="type" required>
                    <option value="annual">سنوية</option>
                    <option value="sick">مرضية</option>
                    <option value="emergency">طارئة</option>
                </select>
            </label>
            <label>من
                <input type="date" name="date_from" required>
            </label>
        </div>
        <div class="row">
            <label>إلى
                <input type="date" name="date_to" required>
            </label>
            <label>السبب
                <input name="reason">
            </label>
        </div>
        <button class="btn" type="submit">إرسال الطلب</button>
        <a class="btn secondary" href="leaves.php">إلغاء</a>
    </form>
</div>
<?php endif; ?>

<div class="card table-wrap">
    <table>
        <thead>
        <tr>
            <th>الموظف</th>
            <th>القسم</th>
            <th>النوع</th>
            <th>من</th>
            <th>إلى</th>
            <th>الأيام</th>
            <th>الحالة</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= h($row['full_name']) ?></td>
                <td><?= h($row['department_name']) ?></td>
                <td><?= h(leave_type_label($row['type'])) ?></td>
                <td><?= h(format_date($row['date_from'])) ?></td>
                <td><?= h(format_date($row['date_to'])) ?></td>
                <td><?= (int) $row['days'] ?></td>
                <td><span class="badge <?= h($row['status']) ?>"><?= h(leave_status_label($row['status'])) ?></span></td>
                <td class="actions">
                    <?php if ($row['status'] === 'pending' && can_manage_leave_for($row)): ?>
                        <form method="post">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="review">
                            <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                            <input type="hidden" name="status" value="approved">
                            <button class="btn small" type="submit">اعتماد</button>
                        </form>
                        <form method="post">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="review">
                            <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                            <input type="hidden" name="status" value="rejected">
                            <button class="btn small danger" type="submit">رفض</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/app/layout_footer.php'; ?>
