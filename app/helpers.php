<?php

function h(?string $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $to): void
{
    header('Location: ' . $to);
    exit;
}

function flash(string $key, ?string $message = null)
{
    if ($message === null) {
        $val = $_SESSION['flash'][$key] ?? null;
        unset($_SESSION['flash'][$key]);
        return $val;
    }
    $_SESSION['flash'][$key] = $message;
}

function old(string $key, $default = '')
{
    return $_SESSION['old'][$key] ?? $default;
}

function remember_old(array $data): void
{
    $_SESSION['old'] = $data;
}

function clear_old(): void
{
    unset($_SESSION['old']);
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . h(csrf_token()) . '">';
}

function csrf_verify(): void
{
    $ok = isset($_POST['_csrf'], $_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], (string) $_POST['_csrf']);
    if (!$ok) {
        http_response_code(419);
        exit('طلب غير صالح. أعد المحاولة.');
    }
}

function format_date(?string $ymd): string
{
    if (!$ymd) {
        return '—';
    }
    $dt = DateTime::createFromFormat('Y-m-d', substr($ymd, 0, 10));
    return $dt ? $dt->format('d/m/Y') : $ymd;
}

function today(): string
{
    return date('Y-m-d');
}

function post(string $key, $default = '')
{
    return isset($_POST[$key]) ? trim((string) $_POST[$key]) : $default;
}

function query(string $key, $default = '')
{
    return isset($_GET[$key]) ? trim((string) $_GET[$key]) : $default;
}

function role_label(string $slug): string
{
    return [
        'admin' => 'مدير النظام',
        'hr' => 'مدير موارد بشرية',
        'dept_manager' => 'مدير قسم',
        'employee' => 'موظف',
    ][$slug] ?? $slug;
}

function leave_type_label(string $type): string
{
    return [
        'annual' => 'سنوية',
        'sick' => 'مرضية',
        'emergency' => 'طارئة',
    ][$type] ?? $type;
}

function leave_status_label(string $status): string
{
    return [
        'pending' => 'قيد الانتظار',
        'approved' => 'موافق',
        'rejected' => 'مرفوض',
    ][$status] ?? $status;
}

function emp_status_label(string $status): string
{
    return $status === 'inactive' ? 'موقوف' : 'نشط';
}

function days_between(string $from, string $to): int
{
    $a = new DateTime($from);
    $b = new DateTime($to);
    return (int) $a->diff($b)->days + 1;
}

function nav_active(string $file): string
{
    $current = basename($_SERVER['SCRIPT_NAME'] ?? '');
    return $current === $file ? 'active' : '';
}
