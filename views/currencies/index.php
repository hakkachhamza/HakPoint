<?php
$title='Devises';
$fields=[
 'ref'=>['label'=>'Réf.'], 'code'=>['label'=>'Code','default'=>'MAD'], 'name'=>['label'=>'Nom','default'=>'Dirham marocain'], 'symbol'=>['label'=>'Symbole','default'=>'DH'], 'rate'=>['label'=>'Taux vs devise principale','type'=>'number','default'=>'1'], 'status'=>['label'=>'Statut','type'=>'select','options'=>['Actif','Inactif']]
];
ge_simple_manager('Devises','currencies',$fields,'currencies','CUR','fa-coins');
