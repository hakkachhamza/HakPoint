<?php
require_csrf();
ge_save_modules_state($_POST['modules'] ?? []);
redirect_to('index.php?page=modules&ok=1');
