<?php
require __DIR__ . '/app/bootstrap.php';
require_installed();

if (current_user()) {
    redirect('index.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $email = mb_strtolower(post('email'));
    $password = (string) ($_POST['password'] ?? '');
    $st = db()->prepare('SELECT * FROM users WHERE email = ? AND is_active = 1');
    $st->execute([$email]);
    $user = $st->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
        login_user($user);
        redirect('index.php');
    }
    $error = 'بيانات الدخول غير صحيحة.';
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>دخول | موارد</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="auth-body">
<div class="card auth-card">
    <h1>موارد</h1>
    <p class="hint">تسجيل الدخول إلى نظام الموارد البشرية</p>
    <?php if ($error): ?><div class="alert error"><?= h($error) ?></div><?php endif; ?>
    <form method="post">
        <?= csrf_field() ?>
        <label>البريد الإلكتروني
            <input type="email" name="email" required value="<?= h(post('email')) ?>" dir="ltr">
        </label>
        <label>كلمة المرور
            <input type="password" name="password" required>
        </label>
        <button class="btn" type="submit">دخول</button>
    </form>
    <div class="accounts" style="margin-top:16px">
        <div>admin@mawarid.local / Admin@123</div>
        <div>hr@mawarid.local / Admin@123</div>
        <div>manager@mawarid.local / Admin@123</div>
        <div>staff@mawarid.local / Admin@123</div>
    </div>
</div>
</body>
</html>
