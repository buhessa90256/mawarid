<?php
$pageTitle = $title ?? 'غير مصرح';
require __DIR__ . '/layout_header.php';
?>
<div class="card">
    <h2><?= h($title ?? 'غير مصرح') ?></h2>
    <p><?= h($message ?? 'ليست لديك صلاحية لتنفيذ هذا الإجراء.') ?></p>
    <a class="btn" href="index.php">العودة للوحة</a>
</div>
<?php require __DIR__ . '/layout_footer.php'; ?>
