<?php
$title='Projets';
$fields=['ref'=>['label'=>'Réf.'],'title'=>['label'=>'Projet'],'client_name'=>['label'=>'Client'],'start_date'=>['label'=>'Début','type'=>'date'],'end_date'=>['label'=>'Fin','type'=>'date'],'budget'=>['label'=>'Budget','type'=>'money'],'status'=>['label'=>'Statut','type'=>'select','options'=>ge_status_options('project')],'note'=>['label'=>'Note','type'=>'textarea']];
ge_simple_manager('Projets','projects',$fields,'projects','PRJ','fa-diagram-project');
