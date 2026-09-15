<?php

function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    static $cached = false;
    static $user = null;
    if ($cached) {
        return $user;
    }
    $st = db()->prepare(
        'SELECT u.*, r.slug AS role_slug, r.name AS role_name,
                e.id AS employee_id, e.department_id AS employee_department_id,
                e.full_name AS employee_name
         FROM users u
         JOIN roles r ON r.id = u.role_id
         LEFT JOIN employees e ON e.user_id = u.id
         WHERE u.id = ? AND u.is_active = 1'
    );
    $st->execute([(int) $_SESSION['user_id']]);
    $user = $st->fetch() ?: null;
    $cached = true;
    if ($user) {
        $user['permissions'] = user_permissions((int) $user['role_id']);
    }
    return $user;
}

function user_permissions(int $roleId): array
{
    $st = db()->prepare(
        'SELECT p.slug
         FROM role_permissions rp
         JOIN permissions p ON p.id = rp.permission_id
         WHERE rp.role_id = ?'
    );
    $st->execute([$roleId]);
    return $st->fetchAll(PDO::FETCH_COLUMN) ?: [];
}

function can(string $permission): bool
{
    $u = current_user();
    if (!$u) {
        return false;
    }
    if (($u['role_slug'] ?? '') === 'admin') {
        return true;
    }
    return in_array($permission, $u['permissions'] ?? [], true);
}

function require_login(): array
{
    $u = current_user();
    if (!$u) {
        redirect('login.php');
    }
    return $u;
}

function require_can(string $permission): array
{
    $u = require_login();
    if (!can($permission)) {
        http_response_code(403);
        $title = 'غير مصرح';
        $message = 'ليست لديك صلاحية لتنفيذ هذا الإجراء.';
        include __DIR__ . '/layout_denied.php';
        exit;
    }
    return $u;
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function department_scope_id(): ?int
{
    $u = current_user();
    if (!$u) {
        return null;
    }
    if (can('employees.view_all')) {
        return null;
    }
    if (($u['role_slug'] ?? '') === 'dept_manager') {
        return isset($u['employee_department_id']) ? (int) $u['employee_department_id'] : -1;
    }
    return -1;
}

function own_employee_id(): ?int
{
    $u = current_user();
    if (!$u || empty($u['employee_id'])) {
        return null;
    }
    return (int) $u['employee_id'];
}

function can_see_employee(array $emp): bool
{
    if (can('employees.view_all')) {
        return true;
    }
    $u = current_user();
    if (!$u) {
        return false;
    }
    if (!empty($emp['user_id']) && (int) $emp['user_id'] === (int) $u['id']) {
        return true;
    }
    if (($u['role_slug'] ?? '') === 'dept_manager'
        && !empty($u['employee_department_id'])
        && (int) $emp['department_id'] === (int) $u['employee_department_id']) {
        return true;
    }
    return false;
}

function can_manage_leave_for(array $leave): bool
{
    if (!can('leaves.approve')) {
        return false;
    }
    if (can('employees.view_all')) {
        return true;
    }
    $u = current_user();
    return $u
        && ($u['role_slug'] ?? '') === 'dept_manager'
        && !empty($u['employee_department_id'])
        && (int) $leave['department_id'] === (int) $u['employee_department_id'];
}
