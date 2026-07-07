<?php require __DIR__.'/_helpers.php'; $id=(int)($_GET['id']??0); expedition_delete_all($id); redirect_to('index.php?page=expeditions');
