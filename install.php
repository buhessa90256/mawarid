<?php
require __DIR__ . '/app/bootstrap.php';

function run_install(): void
{
    $pdo = db();
    $mysql = db_driver() === 'mysql';
    $inc = $mysql ? 'INT AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    $bool = $mysql ? 'TINYINT(1) NOT NULL DEFAULT 1' : 'INTEGER NOT NULL DEFAULT 1';

    $pdo->exec("CREATE TABLE IF NOT EXISTS roles (
        id $inc,
        slug VARCHAR(50) NOT NULL UNIQUE,
        name VARCHAR(100) NOT NULL
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS permissions (
        id $inc,
        slug VARCHAR(80) NOT NULL UNIQUE,
        name VARCHAR(120) NOT NULL
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS role_permissions (
        role_id INTEGER NOT NULL,
        permission_id INTEGER NOT NULL,
        PRIMARY KEY (role_id, permission_id)
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id $inc,
        name VARCHAR(120) NOT NULL,
        email VARCHAR(190) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        role_id INTEGER NOT NULL,
        is_active $bool
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS departments (
        id $inc,
        name VARCHAR(120) NOT NULL,
        description TEXT,
        manager_employee_id INTEGER NULL
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS employees (
        id $inc,
        user_id INTEGER NULL UNIQUE,
        employee_no VARCHAR(40) NOT NULL UNIQUE,
        full_name VARCHAR(160) NOT NULL,
        department_id INTEGER NOT NULL,
        job_title VARCHAR(120) NOT NULL,
        email VARCHAR(190),
        phone VARCHAR(40),
        hired_on DATE NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'active',
        annual_balance INTEGER NOT NULL DEFAULT 21
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS leaves (
        id $inc,
        employee_id INTEGER NOT NULL,
        type VARCHAR(20) NOT NULL,
        date_from DATE NOT NULL,
        date_to DATE NOT NULL,
        days INTEGER NOT NULL,
        reason TEXT,
        status VARCHAR(20) NOT NULL DEFAULT 'pending',
        reviewed_by INTEGER NULL,
        created_at VARCHAR(30) NOT NULL
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS attendance (
        id $inc,
        employee_id INTEGER NOT NULL,
        work_date DATE NOT NULL,
        check_in VARCHAR(8),
        check_out VARCHAR(8),
        note VARCHAR(190),
        UNIQUE (employee_id, work_date)
    )");

    $roles = [
        ['admin', 'مدير النظام'],
        ['hr', 'مدير موارد بشرية'],
        ['dept_manager', 'مدير قسم'],
        ['employee', 'موظف'],
    ];
    $insRole = $pdo->prepare('INSERT INTO roles (slug, name) VALUES (?, ?)');
    foreach ($roles as $r) {
        $exists = $pdo->prepare('SELECT id FROM roles WHERE slug = ?');
        $exists->execute([$r[0]]);
        if (!$exists->fetch()) {
            $insRole->execute($r);
        }
    }

    $permissions = [
        ['employees.view', 'عرض الموظفين'],
        ['employees.view_all', 'عرض كل الموظفين'],
        ['employees.create', 'إضافة موظف'],
        ['employees.edit', 'تعديل موظف'],
        ['employees.delete', 'حذف موظف'],
        ['departments.manage', 'إدارة الأقسام'],
        ['leaves.view', 'عرض الإجازات'],
        ['leaves.create', 'طلب إجازة'],
        ['leaves.approve', 'اعتماد الإجازات'],
        ['attendance.view', 'عرض الحضور'],
        ['attendance.record', 'تسجيل الحضور'],
        ['users.manage', 'إدارة المستخدمين'],
        ['roles.manage', 'إدارة الأدوار'],
        ['reports.view', 'عرض التقارير'],
    ];
    $insPerm = $pdo->prepare('INSERT INTO permissions (slug, name) VALUES (?, ?)');
    foreach ($permissions as $p) {
        $exists = $pdo->prepare('SELECT id FROM permissions WHERE slug = ?');
        $exists->execute([$p[0]]);
        if (!$exists->fetch()) {
            $insPerm->execute($p);
        }
    }

    $roleId = [];
    foreach ($pdo->query('SELECT id, slug FROM roles') as $row) {
        $roleId[$row['slug']] = (int) $row['id'];
    }
    $permId = [];
    foreach ($pdo->query('SELECT id, slug FROM permissions') as $row) {
        $permId[$row['slug']] = (int) $row['id'];
    }

    $map = [
        'admin' => array_keys($permId),
        'hr' => [
            'employees.view', 'employees.view_all', 'employees.create', 'employees.edit', 'employees.delete',
            'departments.manage', 'leaves.view', 'leaves.create', 'leaves.approve',
            'attendance.view', 'attendance.record', 'reports.view',
        ],
        'dept_manager' => [
            'employees.view', 'leaves.view', 'leaves.approve',
            'attendance.view', 'attendance.record',
        ],
        'employee' => [
            'employees.view', 'leaves.view', 'leaves.create', 'attendance.view',
        ],
    ];
    $pdo->exec('DELETE FROM role_permissions');
    $link = $pdo->prepare('INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)');
    foreach ($map as $slug => $perms) {
        foreach ($perms as $ps) {
            if (isset($permId[$ps], $roleId[$slug])) {
                $link->execute([$roleId[$slug], $permId[$ps]]);
            }
        }
    }

    $hash = password_hash('Admin@123', PASSWORD_DEFAULT);
    $users = [
        ['مدير النظام', 'admin@mawarid.local', $hash, $roleId['admin']],
        ['منيرة الدوسري', 'hr@mawarid.local', $hash, $roleId['hr']],
        ['خالد العتيبي', 'manager@mawarid.local', $hash, $roleId['dept_manager']],
        ['نورة السالم', 'staff@mawarid.local', $hash, $roleId['employee']],
    ];
    $insUser = $pdo->prepare('INSERT INTO users (name, email, password_hash, role_id, is_active) VALUES (?,?,?,?,1)');
    foreach ($users as $u) {
        $exists = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $exists->execute([$u[1]]);
        if (!$exists->fetch()) {
            $insUser->execute($u);
        }
    }

    $userIds = [];
    foreach ($pdo->query('SELECT id, email FROM users') as $row) {
        $userIds[$row['email']] = (int) $row['id'];
    }

    if ((int) $pdo->query('SELECT COUNT(*) FROM departments')->fetchColumn() === 0) {
        $pdo->exec("INSERT INTO departments (name, description) VALUES
            ('تقنية المعلومات', 'تطوير الأنظمة والدعم الفني'),
            ('الموارد البشرية', 'شؤون الموظفين والإجازات'),
            ('المالية', 'الحسابات والمصروفات الداخلية')");
    }

    $deptIds = [];
    foreach ($pdo->query('SELECT id, name FROM departments') as $row) {
        $deptIds[$row['name']] = (int) $row['id'];
    }

    $employees = [
        ['EMP-1001', 'خالد العتيبي', $deptIds['تقنية المعلومات'], 'مدير تقنية المعلومات', 'manager@mawarid.local', '0501111001', '2020-03-12', 'active', 18, $userIds['manager@mawarid.local'] ?? null],
        ['EMP-1002', 'نورة السالم', $deptIds['تقنية المعلومات'], 'مطورة أنظمة', 'staff@mawarid.local', '0501111002', '2022-06-01', 'active', 21, $userIds['staff@mawarid.local'] ?? null],
        ['EMP-1003', 'سعد القحطاني', $deptIds['تقنية المعلومات'], 'مهندس شبكات', 'saad@mawarid.local', '0501111003', '2021-01-15', 'active', 16, null],
        ['EMP-1004', 'عبدالعزيز المطيري', $deptIds['تقنية المعلومات'], 'أخصائي دعم فني', 'aziz@mawarid.local', '0501111004', '2023-09-10', 'active', 21, null],
        ['EMP-2001', 'منيرة الدوسري', $deptIds['الموارد البشرية'], 'مديرة الموارد البشرية', 'hr@mawarid.local', '0502222001', '2019-11-03', 'active', 12, $userIds['hr@mawarid.local'] ?? null],
        ['EMP-2002', 'هند العلي', $deptIds['الموارد البشرية'], 'منسقة موارد بشرية', 'hind@mawarid.local', '0502222002', '2024-02-18', 'active', 21, null],
        ['EMP-3001', 'فهد الشمري', $deptIds['المالية'], 'محاسب', 'fahad@mawarid.local', '0503333001', '2020-08-20', 'active', 19, null],
        ['EMP-3002', 'ريم الحربي', $deptIds['المالية'], 'موظفة مالية', 'reem@mawarid.local', '0503333002', '2023-04-04', 'inactive', 21, null],
    ];
    $insEmp = $pdo->prepare('INSERT INTO employees (employee_no, full_name, department_id, job_title, email, phone, hired_on, status, annual_balance, user_id) VALUES (?,?,?,?,?,?,?,?,?,?)');
    foreach ($employees as $e) {
        $exists = $pdo->prepare('SELECT id FROM employees WHERE employee_no = ?');
        $exists->execute([$e[0]]);
        if (!$exists->fetch()) {
            $insEmp->execute($e);
        }
    }

    $empIds = [];
    foreach ($pdo->query('SELECT id, employee_no FROM employees') as $row) {
        $empIds[$row['employee_no']] = (int) $row['id'];
    }

    $pdo->prepare('UPDATE departments SET manager_employee_id = ? WHERE name = ?')
        ->execute([$empIds['EMP-1001'], 'تقنية المعلومات']);
    $pdo->prepare('UPDATE departments SET manager_employee_id = ? WHERE name = ?')
        ->execute([$empIds['EMP-2001'], 'الموارد البشرية']);
    $pdo->prepare('UPDATE departments SET manager_employee_id = ? WHERE name = ?')
        ->execute([$empIds['EMP-3001'], 'المالية']);

    if ((int) $pdo->query('SELECT COUNT(*) FROM leaves')->fetchColumn() === 0) {
        $pdo->prepare('INSERT INTO leaves (employee_id, type, date_from, date_to, days, reason, status, reviewed_by, created_at) VALUES (?,?,?,?,?,?,?,?,?)')
            ->execute([
                $empIds['EMP-1002'], 'annual', date('Y-m-d', strtotime('+3 days')), date('Y-m-d', strtotime('+5 days')),
                3, 'إجازة سنوية قصيرة', 'pending', null, date('c'),
            ]);
        $pdo->prepare('INSERT INTO leaves (employee_id, type, date_from, date_to, days, reason, status, reviewed_by, created_at) VALUES (?,?,?,?,?,?,?,?,?)')
            ->execute([
                $empIds['EMP-1003'], 'sick', date('Y-m-d', strtotime('-7 days')), date('Y-m-d', strtotime('-6 days')),
                2, 'مراجعة طبية', 'approved', $userIds['manager@mawarid.local'] ?? null, date('c'),
            ]);
    }

    if ((int) $pdo->query('SELECT COUNT(*) FROM attendance')->fetchColumn() === 0) {
        $today = date('Y-m-d');
        $insAtt = $pdo->prepare('INSERT INTO attendance (employee_id, work_date, check_in, check_out, note) VALUES (?,?,?,?,?)');
        $insAtt->execute([$empIds['EMP-1001'], $today, '08:01', null, null]);
        $insAtt->execute([$empIds['EMP-1002'], $today, '08:12', null, null]);
        $insAtt->execute([$empIds['EMP-2001'], $today, '07:55', '16:02', null]);
        $insAtt->execute([$empIds['EMP-1003'], date('Y-m-d', strtotime('-1 day')), '08:20', '16:10', null]);
    }
}

$done = false;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    try {
        run_install();
        $done = true;
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$already = false;
try {
    $already = table_exists('users') && (int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0;
} catch (Throwable $e) {
    $already = false;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>تثبيت موارد</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="auth-body">
<div class="card auth-card">
    <h1>تثبيت موارد</h1>
    <p class="hint">ينشئ الجداول، الأدوار، الصلاحيات، والحسابات التجريبية.</p>
    <?php if ($error): ?><div class="alert error"><?= h($error) ?></div><?php endif; ?>
    <?php if ($done || $already): ?>
        <div class="alert success">القاعدة جاهزة ويمكنك تسجيل الدخول.</div>
        <div class="accounts">
            <div>admin@mawarid.local / Admin@123 — مدير النظام</div>
            <div>hr@mawarid.local / Admin@123 — مدير موارد بشرية</div>
            <div>manager@mawarid.local / Admin@123 — مدير قسم التقنية</div>
            <div>staff@mawarid.local / Admin@123 — موظفة</div>
        </div>
        <p><a class="btn" href="login.php">الذهاب لتسجيل الدخول</a></p>
    <?php else: ?>
        <form method="post">
            <?= csrf_field() ?>
            <button class="btn" type="submit">تثبيت الآن</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
