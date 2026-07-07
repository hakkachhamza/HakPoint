<?php
require_once __DIR__.'/_helpers.php';
$id=(int)($_GET['id']??0); $status=$_GET['status']??''; $quotes=data_read('quotes',[]); $idx=quote_find_index($quotes,$id); if($idx>=0 && $status){ $quotes[$idx]['status']=$status; $quotes[$idx]['updated_at']=date('d/m/Y H:i'); data_write('quotes',$quotes); }
redirect_to('index.php?page=quote_show&id='.$id);
