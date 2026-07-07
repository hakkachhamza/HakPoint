<?php
$title='Tableau de bord';
include __DIR__.'/layouts/header.php';
include __DIR__.'/dashboard/data.php';
?>
<?php if(isset($_GET['registered']) && current_user()): ?>
    <div class="panel tenant-register-success" style="margin:12px 16px;padding:14px 16px;border-left:4px solid #84CC16">
        <b>Company account created successfully.</b><br>
        Your company code for future login is: <code><?= e(current_user()['tenant_slug'] ?? ge_current_tenant_slug()) ?></code>
    </div>
<?php endif; ?>
<?php include __DIR__.'/dashboard/metrics.php'; ?>
<?php include __DIR__.'/dashboard/charts.php'; ?>
<?php include __DIR__.'/dashboard/recent.php'; ?>
<?php include __DIR__.'/layouts/footer.php'; ?>
