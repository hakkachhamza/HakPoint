<?php $title='Vue hiérarchique'; include __DIR__.'/../layouts/header.php'; require_once __DIR__.'/_helpers.php'; $users=data_read('users',[]); ?>
<div class="panel"><h2>Vue hiérarchique</h2><div class="hierarchy-tree"><?php foreach($users as $u): ?><div class="hierarchy-node"><i class="fa-solid fa-user"></i> <b><?=e(user_display_name($u))?></b><span><?=e($u['role']??'')?></span></div><?php endforeach; ?></div></div>
<?php include __DIR__.'/../layouts/footer.php'; ?>
