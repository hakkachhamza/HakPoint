<?php
$title='Agenda';
$fields=['ref'=>['label'=>'Réf.'],'title'=>['label'=>'Événement'],'object_type'=>['label'=>'Objet lié'],'object_id'=>['label'=>'ID objet','type'=>'number'],'event_date'=>['label'=>'Date','type'=>'date'],'event_time'=>['label'=>'Heure'],'assigned_to'=>['label'=>'ID responsable','type'=>'number'],'status'=>['label'=>'Statut','type'=>'select','options'=>['À faire','Fait','Annulé']],'note'=>['label'=>'Note','type'=>'textarea']];
ge_simple_manager('Agenda','agenda_events',$fields,'agenda','EVT','fa-calendar-days');
