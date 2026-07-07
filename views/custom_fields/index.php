<?php
$title='Champs personnalisés';
$fields=['ref'=>['label'=>'Réf.'],'object_type'=>['label'=>'Objet','type'=>'select','options'=>['product','client','supplier','quote','order','invoice','project']],'field_key'=>['label'=>'Clé technique'],'label'=>['label'=>'Libellé'],'field_type'=>['label'=>'Type','type'=>'select','options'=>['text','number','date','select','textarea','checkbox']],'required'=>['label'=>'Obligatoire','type'=>'select','options'=>['0','1']],'status'=>['label'=>'Statut','type'=>'select','options'=>['Actif','Inactif']],'options'=>['label'=>'Options select','type'=>'textarea']];
ge_simple_manager('Champs personnalisés','custom_fields',$fields,'custom_fields','CF','fa-list-check');
