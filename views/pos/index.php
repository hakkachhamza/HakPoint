<?php
$title='Caisse / POS';
$fields=['ref'=>['label'=>'Réf.'],'client_name'=>['label'=>'Client'],'date'=>['label'=>'Date','type'=>'date','default'=>date('Y-m-d')],'amount_ht'=>['label'=>'HT','type'=>'money'],'amount_ttc'=>['label'=>'TTC','type'=>'money'],'payment_mode'=>['label'=>'Paiement','type'=>'select','options'=>['Espèces','Carte','Virement','Autre']],'status'=>['label'=>'Statut','type'=>'select','options'=>ge_status_options('pos_sale')],'note'=>['label'=>'Note','type'=>'textarea']];
ge_simple_manager('Caisse / POS','pos_sales',$fields,'pos','POS','fa-cash-register');
