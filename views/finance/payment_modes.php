<?php
$title='Modes de paiement';
$fields=[
 'ref'=>['label'=>'Réf.'], 'name'=>['label'=>'Nom'], 'type'=>['label'=>'Type','type'=>'select','options'=>['Espèces','Chèque','Virement','Carte','Online','Autre']], 'status'=>['label'=>'Statut','type'=>'select','options'=>['Actif','Inactif']], 'note'=>['label'=>'Note','type'=>'textarea']
];
ge_simple_manager('Modes de paiement','payment_modes',$fields,'payment_modes','PM','fa-credit-card');
