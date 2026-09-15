<?php
require __DIR__ . '/app/bootstrap.php';
require_installed();
$u = require_login();

$canManage = can('departments.manage');
$canView = $canManage || can('employees.view_all') || ($u['role_slug'] ?? '') === 'dept_manager';
if (!$canView) {
    require_can('departments.manage');
}

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    if (!$canManage) {
        require_can('departments.manage');
    }
    $action = post('action');
    if ($action === 'save') {
        $name = post('name');
        $desc = post('description');
        $manager = post('manager_employee_id') !== '' ? (int) post('manager_employee_id') : null;
        $id = (int) post('id');
        if ($name === '') {
            flash('error', 'اسم القسم مطلوب.');
        } elseif ($id) {
            $pdo->prepare('UPDATE departments SET name=?, description=?, manager_employee_id=? WHERE id=?')
                ->execute([$name, $desc, $manager, $id]);
            flash('success', 'تم تحديث القسم.');
        } else {
            $pdo->prepare('INSERT INTO departments (name, description, manager_employee_id) VALUES (?,?,?)')
                ->execute([$name, $desc, $manager]);
            flash('success', 'تمت إضافة القسم.');
        }
    } elseif ($action === 'delete') {
        $id = (int) post('id');
        $used = $pdo->prepare('SELECT COUNT(*) FROM employees WHERE department_id = ?');
        $used->execute([$id]);
        if ((int) $used->fetchColumn() > 0) {
            flash('error', 'لا يمكن حذف قسم مرتبط بموظفين.');
        } else {
            $pdo->prepare('DELETE FROM departments WHERE id = ?')->execute([$id]);
            flash('success', 'تم حذف القسم.');
        }
    }
    redirect('departments.php');
}

$edit = null;
if (query('edit') !== '' && $canManage) {
    $st = $pdo->prepare('SELECT * FROM departments WHERE id = ?');
    $st->execute([(int) query('edit')]);
    $edit = $st->fetch();
}

$rows = $pdo->query(
    'SELECT d.*, e.full_name AS manager_name,
            (SELECT COUNT(*) FROM employees emp WHERE emp.department_id = d.id) AS emp_count
     FROM departments d
     LEFT JOIN employees e ON e.id = d.manager_employee_id
     ORDER BY d.id'
)->fetchAll();

$managers = $pdo->query("SELECT id, full_name FROM employees WHERE status = 'active' ORDER BY full_name")->fetchAll();

$pageTitle = 'الأقسام';
require __DIR__ . '/app/layout_header.php';
?>
<?php if ($canManage): ?>
<div class="card" style="margin-bottom:16px">
    <h3><?= $edit ? 'تعديل قسم' : 'قسم جديد' ?></h3>
    <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">
        <div class="row">
            <label>اسم القسم
                <input name="name" required value="<?= h($edit['name'] ?? '') ?>">
            </label>
            <label>مدير القسم
                <select name="manager_employee_id">
                    <option value="">بدون</option>
                    <?php foreach ($managers as $m): ?>
                        <option value="<?= (int) $m['id'] ?>" <?= (int) ($edit['manager_employee_id'] ?? 0) === (int) $m['id'] ? 'selected' : '' ?>>
                            <?= h($m['full_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <label>وصف مختصر
            <textarea name="description"><?= h($edit['description'] ?? '') ?></textarea>
        </label>
        <button class="btn" type="submit">حفظ</button>
        <?php if ($edit): ?><a class="btn secondary" href="departments.php">إلغاء</a><?php endif; ?>
    </form>
</div>
<?php endif; ?>

<div class="card table-wrap">
    <table>
        <thead>
        <tr>
            <th>القسم</th>
            <th>الوصف</th>
            <th>المدير</th>
            <th>عدد الموظفين</th>
            <?php if ($canManage): ?><th></th><?php endif; ?>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= h($row['name']) ?></td>
                <td><?= h($row['description']) ?></td>
                <td><?= h($row['manager_name'] ?: '—') ?></td>
                <td><?= (int) $row['emp_count'] ?></td>
                <?php if ($canManage): ?>
                    <td class="actions">
                        <a class="btn small secondary" href="departments.php?edit=<?= (int) $row['id'] ?>">تعديل</a>
                        <form method="post" onsubmit="return confirm('حذف القسم؟');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                            <button class="btn small danger" type="submit">حذف</button>
                        </form>
                    </td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/app/layout_footer.php'; ?>
