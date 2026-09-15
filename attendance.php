<?php
require __DIR__ . '/app/bootstrap.php';
require_installed();
$u = require_login();
if (!can('attendance.view') && !can('attendance.record')) {
    require_can('attendance.view');
}
$pdo = db();

$date = query('date') !== '' ? query('date') : today();
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $date = today();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    require_can('attendance.record');
    $employeeId = (int) post('employee_id');
    $workDate = post('work_date') ?: today();
    $checkIn = post('check_in') ?: null;
    $checkOut = post('check_out') ?: null;
    $note = post('note');

    $st = $pdo->prepare('SELECT * FROM employees WHERE id = ?');
    $st->execute([$employeeId]);
    $emp = $st->fetch();
    if (!$emp || !can_see_employee($emp)) {
        flash('error', 'ليست لديك صلاحية تسجيل حضور هذا الموظف.');
        redirect('attendance.php');
    }

    $existing = $pdo->prepare('SELECT id FROM attendance WHERE employee_id = ? AND work_date = ?');
    $existing->execute([$employeeId, $workDate]);
    $id = $existing->fetchColumn();
    if ($id) {
        $pdo->prepare('UPDATE attendance SET check_in=?, check_out=?, note=? WHERE id=?')
            ->execute([$checkIn, $checkOut, $note, $id]);
        flash('success', 'تم تحديث سجل الحضور.');
    } else {
        $pdo->prepare('INSERT INTO attendance (employee_id, work_date, check_in, check_out, note) VALUES (?,?,?,?,?)')
            ->execute([$employeeId, $workDate, $checkIn, $checkOut, $note]);
        flash('success', 'تم تسجيل الحضور.');
    }
    redirect('attendance.php?date=' . urlencode($workDate));
}

$scope = department_scope_id();
$own = own_employee_id();
$sql = 'SELECT a.*, e.full_name, e.department_id, d.name AS department_name
        FROM attendance a
        JOIN employees e ON e.id = a.employee_id
        JOIN departments d ON d.id = e.department_id
        WHERE a.work_date = ?';
$params = [$date];
if (can('employees.view_all')) {
    // all
} elseif ($scope && $scope > 0) {
    $sql .= ' AND e.department_id = ?';
    $params[] = $scope;
} elseif ($own) {
    $sql .= ' AND a.employee_id = ?';
    $params[] = $own;
} else {
    $sql .= ' AND 1=0';
}
$sql .= ' ORDER BY e.full_name';
$st = $pdo->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll();

$people = [];
if (can('attendance.record')) {
    if (can('employees.view_all')) {
        $people = $pdo->query("SELECT id, full_name FROM employees WHERE status = 'active' ORDER BY full_name")->fetchAll();
    } elseif ($scope && $scope > 0) {
        $pst = $pdo->prepare("SELECT id, full_name FROM employees WHERE status = 'active' AND department_id = ? ORDER BY full_name");
        $pst->execute([$scope]);
        $people = $pst->fetchAll();
    }
}

$pageTitle = 'الحضور';
require __DIR__ . '/app/layout_header.php';
?>
<div class="toolbar">
    <form method="get" class="actions">
        <label style="margin:0">اليوم
            <input type="date" name="date" value="<?= h($date) ?>" onchange="this.form.submit()">
        </label>
    </form>
</div>

<?php if (can('attendance.record') && $people): ?>
<div class="card" style="margin-bottom:16px">
    <h3>تسجيل حضور / انصراف</h3>
    <form method="post">
        <?= csrf_field() ?>
        <div class="row">
            <label>الموظف
                <select name="employee_id" required>
                    <?php foreach ($people as $p): ?>
                        <option value="<?= (int) $p['id'] ?>"><?= h($p['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>التاريخ
                <input type="date" name="work_date" value="<?= h($date) ?>" required>
            </label>
        </div>
        <div class="row">
            <label>الحضور
                <input type="time" name="check_in" value="08:00">
            </label>
            <label>الانصراف
                <input type="time" name="check_out">
            </label>
        </div>
        <label>ملاحظة
            <input name="note">
        </label>
        <button class="btn" type="submit">حفظ السجل</button>
    </form>
</div>
<?php endif; ?>

<div class="card table-wrap">
    <table>
        <thead>
        <tr>
            <th>الموظف</th>
            <th>القسم</th>
            <th>الحضور</th>
            <th>الانصراف</th>
            <th>ملاحظة</th>
        </tr>
        </thead>
        <tbody>
        <?php if (!$rows): ?>
            <tr><td colspan="5">لا توجد سجلات لهذا اليوم ضمن صلاحيتك.</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= h($row['full_name']) ?></td>
                <td><?= h($row['department_name']) ?></td>
                <td><?= h($row['check_in'] ?: '—') ?></td>
                <td><?= h($row['check_out'] ?: '—') ?></td>
                <td><?= h($row['note']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/app/layout_footer.php'; ?>
