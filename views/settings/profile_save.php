<?php
try{
    $action=$_POST['profile_action'] ?? 'upload';
    if($action==='remove'){
        remove_current_user_profile_picture();
    }else{
        save_current_user_profile_picture('profile_picture');
    }
    redirect_to('index.php?page=settings&ok=1#profile-section');
}catch(Throwable $e){
    redirect_to('index.php?page=settings&err='.urlencode($e->getMessage()).'#profile-section');
}
