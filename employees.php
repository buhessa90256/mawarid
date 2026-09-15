<?php
require __DIR__ . '/app/bootstrap.php';
require_installed();
$u = require_can('employees.view');
$pdo = db();

function employee_query_sql(string $extra = ''): string
{
    return 'SELECT e.*, d.name AS department_name
            FROM employees e
            JOIN departments d ON d.id = e.department_id
            ' . $extra . '
            ORDER BY e.id';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = post('action');
    if ($action === 'delete') {
        require_can('employees.delete');
        $id = (int) post('id');
        $st = $pdo->prepare('SELECT * FROM employees WHERE id = ?');
        $st->execute([$id]);
        $emp = $st->fetch();
        if (!$emp || !can_see_employee($emp)) {
            flash('error', 'لا يمكن حذف هذا الموظف.');
        } else {
            $pdo->prepare('DELETE FROM leaves WHERE employee_id = ?')->execute([$id]);
            $pdo->prepare('DELETE FROM attendance WHERE employee_id = ?')->execute([$id]);
            $pdo->prepare('UPDATE departments SET manager_employee_id = NULL WHERE manager_employee_id = ?')->execute([$id]);
            $pdo->prepare('DELETE FROM employees WHERE id = ?')->execute([$id]);
            flash('success', 'تم حذف الموظف.');
        }
        redirect('employees.php');
    }

    require_can($action === 'save' && post('id') ? 'employees.edit' : 'employees.create');
    $id = (int) post('id');
    $data = [
        'employee_no' => post('employee_no'),
        'full_name' => post('full_name'),
        'department_id' => (int) post('department_id'),
        'job_title' => post('job_title'),
        'email' => post('email'),
        'phone' => post('phone'),
        'hired_on' => post('hired_on'),
        'status' => post('status') === 'inactive' ? 'inactive' : 'active',
        'annual_balance' => max(0, (int) post('annual_balance')),
        'user_id' => post('user_id') !== '' ? (int) post('user_id') : null,
    ];
    if ($data['employee_no'] === '' || $data['full_name'] === '' || !$data['department_id'] || $data['hired_on'] === '') {
        flash('error', 'أكمل الحقول المطلوبة.');
        remember_old($_POST);
        redirect('employees.php' . ($id ? '?edit=' . $id : '?new=1'));
    }
    try {
        if ($id) {
            $st = $pdo->prepare('SELECT * FROM employees WHERE id = ?');
            $st->execute([$id]);
            $emp = $st->fetch();
            if (!$emp || !can_see_employee($emp)) {
                flash('error', 'ليست لديك صلاحية تعديل هذا الموظف.');
                redirect('employees.php');
            }
            $pdo->prepare('UPDATE employees SET employee_no=?, full_name=?, department_id=?, job_title=?, email=?, phone=?, hired_on=?, status=?, annual_balance=?, user_id=? WHERE id=?')
                ->execute([
                    $data['employee_no'], $data['full_name'], $data['department_id'], $data['job_title'],
                    $data['email'], $data['phone'], $data['hired_on'], $data['status'], $data['annual_balance'],
                    $data['user_id'], $id,
                ]);
            flash('success', 'تم تحديث بيانات الموظف.');
        } else {
            $pdo->prepare('INSERT INTO employees (employee_no, full_name, department_id, job_title, email, phone, hired_on, status, annual_balance, user_id) VALUES (?,?,?,?,?,?,?,?,?,?)')
                ->execute([
                    $data['employee_no'], $data['full_name'], $data['department_id'], $data['job_title'],
                    $data['email'], $data['phone'], $data['hired_on'], $data['status'], $data['annual_balance'],
                    $data['user_id'],
                ]);
            flash('success', 'تمت إضافة الموظف.');
        }
    } catch (Throwable $e) {
        flash('error', 'تعذر الحفظ. تحقق من الرقم الوظيفي أو ربط الحساب.');
        remember_old($_POST);
        redirect('employees.php' . ($id ? '?edit=' . $id : '?new=1'));
    }
    clear_old();
    redirect('employees.php');
}

$scope = department_scope_id();
$own = own_employee_id();
if (can('employees.view_all')) {
    $rows = $pdo->query(employee_query_sql())->fetchAll();
} elseif ($scope && $scope > 0) {
    $st = $pdo->prepare(employee_query_sql('WHERE e.department_id = ?'));
    $st->execute([$scope]);
    $rows = $st->fetchAll();
} elseif ($own) {
    $st = $pdo->prepare(employee_query_sql('WHERE e.id = ?'));
    $st->execute([$own]);
    $rows = $st->fetchAll();
} else {
    $rows = [];
}

$edit = null;
$showForm = query('new') === '1' || query('edit') !== '';
if (query('edit') !== '') {
    $st = $pdo->prepare('SELECT * FROM employees WHERE id = ?');
    $st->execute([(int) query('edit')]);
    $edit = $st->fetch();
    if (!$edit || !can_see_employee($edit) || !can('employees.edit')) {
        $edit = null;
        $showForm = false;
        flash('error', 'لا يمكن فتح هذا الموظف للتعديل.');
        redirect('employees.php');
    }
}
if (query('new') === '1' && !can('employees.create')) {
    $showForm = false;
}

$departments = $pdo->query('SELECT id, name FROM departments ORDER BY name')->fetchAll();
$users = $pdo->query('SELECT id, name, email FROM users ORDER BY name')->fetchAll();

$pageTitle = 'الموظفون';
require __DIR__ . '/app/layout_header.php';
?>
<div class="toolbar">
    <div class="hint">عدد السجلات: <?= count($rows) ?></div>
    <?php if (can('employees.create')): ?>
        <a class="btn" href="employees.php?new=1">موظف جديد</a>
    <?php endif; ?>
</div>

<?php if ($showForm && (can('employees.create') || can('employees.edit'))): ?>
<div class="card" style="margin-bottom:16px">
    <h3><?= $edit ? 'تعديل موظف' : 'موظف جديد' ?></h3>
    <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">
        <div class="row">
            <label>الاسم
                <input name="full_name" required value="<?= h(old('full_name', $edit['full_name'] ?? '')) ?>">
            </label>
            <label>الرقم الوظيفي
                <input name="employee_no" required value="<?= h(old('employee_no', $edit['employee_no'] ?? '')) ?>">
            </label>
        </div>
        <div class="row">
            <label>القسم
                <select name="department_id" required>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= (int) $d['id'] ?>" <?= (int) old('department_id', $edit['department_id'] ?? 0) === (int) $d['id'] ? 'selected' : '' ?>>
                            <?= h($d['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>المسمى
                <input name="job_title" required value="<?= h(old('job_title', $edit['job_title'] ?? '')) ?>">
            </label>
        </div>
        <div class="row">
            <label>البريد
                <input type="email" name="email" dir="ltr" value="<?= h(old('email', $edit['email'] ?? '')) ?>">
            </label>
            <label>الجوال
                <input name="phone" dir="ltr" value="<?= h(old('phone', $edit['phone'] ?? '')) ?>">
            </label>
        </div>
        <div class="row">
            <label>تاريخ التعيين
                <input type="date" name="hired_on" required value="<?= h(old('hired_on', $edit['hired_on'] ?? today())) ?>">
            </label>
            <label>الحالة
                <select name="status">
                    <option value="active" <?= old('status', $edit['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>نشط</option>
                    <option value="inactive" <?= old('status', $edit['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>موقوف</option>
                </select>
            </label>
        </div>
        <div class="row">
            <label>رصيد الإجازة السنوية
                <input type="number" min="0" name="annual_balance" value="<?= h(old('annual_balance', $edit['annual_balance'] ?? 21)) ?>">
            </label>
            <label>حساب المستخدم المرتبط
                <select name="user_id">
                    <option value="">بدون</option>
                    <?php foreach ($users as $usr): ?>
                        <option value="<?= (int) $usr['id'] ?>" <?= (int) old('user_id', $edit['user_id'] ?? 0) === (int) $usr['id'] ? 'selected' : '' ?>>
                            <?= h($usr['name'] . ' — ' . $usr['email']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <button class="btn" type="submit">حفظ</button>
        <a class="btn secondary" href="employees.php">إلغاء</a>
    </form>
</div>
<?php endif; ?>

<div class="card table-wrap">
    <table>
        <thead>
        <tr>
            <th>الرقم</th>
            <th>الاسم</th>
            <th>القسم</th>
            <th>المسمى</th>
            <th>الجوال</th>
            <th>التعيين</th>
            <th>الحالة</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= h($row['employee_no']) ?></td>
                <td><?= h($row['full_name']) ?></td>
                <td><?= h($row['department_name']) ?></td>
                <td><?= h($row['job_title']) ?></td>
                <td dir="ltr"><?= h($row['phone']) ?></td>
                <td><?= h(format_date($row['hired_on'])) ?></td>
                <td><span class="badge <?= h($row['status']) ?>"><?= h(emp_status_label($row['status'])) ?></span></td>
                <td class="actions">
                    <?php if (can('employees.edit')): ?>
                        <a class="btn small secondary" href="employees.php?edit=<?= (int) $row['id'] ?>">تعديل</a>
                    <?php endif; ?>
                    <?php if (can('employees.delete')): ?>
                        <form method="post" onsubmit="return confirm('حذف الموظف؟');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                            <button class="btn small danger" type="submit">حذف</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/app/layout_footer.php'; ?>
