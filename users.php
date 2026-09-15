<?php
require __DIR__ . '/app/bootstrap.php';
require_installed();
$u = require_can('users.manage');
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = post('action');
    if ($action === 'save') {
        $id = (int) post('id');
        $name = post('name');
        $email = mb_strtolower(post('email'));
        $roleId = (int) post('role_id');
        $active = post('is_active') === '0' ? 0 : 1;
        $password = (string) ($_POST['password'] ?? '');

        $role = $pdo->prepare('SELECT * FROM roles WHERE id = ?');
        $role->execute([$roleId]);
        $roleRow = $role->fetch();
        if ($name === '' || $email === '' || !$roleRow) {
            flash('error', 'أكمل بيانات المستخدم.');
            redirect('users.php');
        }

        if ($id) {
            $target = $pdo->prepare('SELECT u.*, r.slug FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = ?');
            $target->execute([$id]);
            $t = $target->fetch();
            if ($t && $t['slug'] === 'admin' && $roleRow['slug'] !== 'admin') {
                $admins = (int) $pdo->query("SELECT COUNT(*) FROM users u JOIN roles r ON r.id = u.role_id WHERE r.slug = 'admin' AND u.is_active = 1")->fetchColumn();
                if ($admins <= 1) {
                    flash('error', 'لا يمكن تغيير دور آخر مدير نظام نشط.');
                    redirect('users.php');
                }
            }
        }

        try {
            if ($id) {
                if ($password !== '') {
                    $pdo->prepare('UPDATE users SET name=?, email=?, role_id=?, is_active=?, password_hash=? WHERE id=?')
                        ->execute([$name, $email, $roleId, $active, password_hash($password, PASSWORD_DEFAULT), $id]);
                } else {
                    $pdo->prepare('UPDATE users SET name=?, email=?, role_id=?, is_active=? WHERE id=?')
                        ->execute([$name, $email, $roleId, $active, $id]);
                }
                flash('success', 'تم تحديث المستخدم.');
            } else {
                if (strlen($password) < 8) {
                    flash('error', 'كلمة المرور يجب ألا تقل عن 8 أحرف.');
                    redirect('users.php?new=1');
                }
                $pdo->prepare('INSERT INTO users (name, email, password_hash, role_id, is_active) VALUES (?,?,?,?,?)')
                    ->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $roleId, $active]);
                flash('success', 'تم إنشاء المستخدم.');
            }
        } catch (Throwable $e) {
            flash('error', 'البريد مستخدم مسبقاً أو حدث خطأ أثناء الحفظ.');
        }
        redirect('users.php');
    }
}

$edit = null;
if (query('edit') !== '') {
    $st = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $st->execute([(int) query('edit')]);
    $edit = $st->fetch();
}

$rows = $pdo->query(
    'SELECT u.*, r.name AS role_name, r.slug AS role_slug
     FROM users u JOIN roles r ON r.id = u.role_id
     ORDER BY u.id'
)->fetchAll();
$roles = $pdo->query('SELECT * FROM roles ORDER BY id')->fetchAll();

$pageTitle = 'المستخدمون والأدوار';
require __DIR__ . '/app/layout_header.php';
?>
<div class="toolbar">
    <div class="hint">إدارة الحسابات متاحة لمدير النظام فقط. الأدوار مربوطة بصلاحيات ثابتة.</div>
    <a class="btn" href="users.php?new=1">مستخدم جديد</a>
</div>

<?php if ($edit || query('new') === '1'): ?>
<div class="card" style="margin-bottom:16px">
    <h3><?= $edit ? 'تعديل مستخدم' : 'مستخدم جديد' ?></h3>
    <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">
        <div class="row">
            <label>الاسم
                <input name="name" required value="<?= h($edit['name'] ?? '') ?>">
            </label>
            <label>البريد
                <input type="email" name="email" required dir="ltr" value="<?= h($edit['email'] ?? '') ?>">
            </label>
        </div>
        <div class="row">
            <label>الدور
                <select name="role_id" required>
                    <?php foreach ($roles as $r): ?>
                        <option value="<?= (int) $r['id'] ?>" <?= (int) ($edit['role_id'] ?? 0) === (int) $r['id'] ? 'selected' : '' ?>>
                            <?= h($r['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>الحالة
                <select name="is_active">
                    <option value="1" <?= (int) ($edit['is_active'] ?? 1) === 1 ? 'selected' : '' ?>>نشط</option>
                    <option value="0" <?= isset($edit['is_active']) && (int) $edit['is_active'] === 0 ? 'selected' : '' ?>>موقوف</option>
                </select>
            </label>
        </div>
        <label>كلمة المرور <?= $edit ? '(اتركها فارغة للإبقاء عليها)' : '' ?>
            <input type="password" name="password" <?= $edit ? '' : 'required minlength="8"' ?>>
        </label>
        <button class="btn" type="submit">حفظ</button>
        <a class="btn secondary" href="users.php">إلغاء</a>
    </form>
</div>
<?php endif; ?>

<div class="card table-wrap">
    <table>
        <thead>
        <tr><th>الاسم</th><th>البريد</th><th>الدور</th><th>الحالة</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= h($row['name']) ?></td>
                <td dir="ltr"><?= h($row['email']) ?></td>
                <td><?= h($row['role_name']) ?></td>
                <td><span class="badge <?= ((int) $row['is_active']) ? 'active' : 'inactive' ?>"><?= ((int) $row['is_active']) ? 'نشط' : 'موقوف' ?></span></td>
                <td><a class="btn small secondary" href="users.php?edit=<?= (int) $row['id'] ?>">تعديل</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/app/layout_footer.php'; ?>
