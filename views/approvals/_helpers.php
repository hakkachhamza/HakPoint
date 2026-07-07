<?php
function ge_approval_column_exists(PDO $pdo, string $table, string $column): bool {
    try {
        $stmt=$pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?');
        $stmt->execute([$table,$column]);
        return (int)$stmt->fetchColumn()>0;
    } catch(Throwable $e){ return false; }
}
function ge_approval_add_column(PDO $pdo, string $table, string $column, string $definition): void {
    if(!ge_approval_column_exists($pdo,$table,$column)){
        try{ $pdo->exec('ALTER TABLE '.ge_identifier($table).' ADD COLUMN '.ge_identifier($column).' '.$definition); }catch(Throwable $e){}
    }
}
function ge_approval_ensure_tables(PDO $pdo): void {
    if(function_exists('ge_erp_ensure_tables')) ge_erp_ensure_tables($pdo);
    db_install_enterprise_tables($pdo);
    ge_approval_add_column($pdo,'ge_approval_requests','title','VARCHAR(190) NULL');
    ge_approval_add_column($pdo,'ge_approval_requests','priority','VARCHAR(40) NULL');
    ge_approval_add_column($pdo,'ge_approval_requests','decision_reason','TEXT NULL');
    ge_approval_add_column($pdo,'ge_approval_requests','decided_by','INT NULL');
    ge_approval_add_column($pdo,'ge_approval_requests','applied_at','DATETIME NULL');
    ge_approval_add_column($pdo,'ge_approval_requests','template','VARCHAR(90) NULL');
    db_install_collection_table($pdo, 'ge_approval_documents');
}
function ge_approval_status_options(): array { return ['En attente','Approuvé','Refusé','Annulé']; }
function ge_approval_type_options(): array { return ['devis'=>'Devis','commande'=>'Commande client','facture'=>'Facture client','achat'=>'Achat fournisseur','avoir'=>'Avoir client','stock'=>'Stock','autre'=>'Autre']; }
function ge_approval_selected($a,$b): string { return (string)$a === (string)$b ? ' selected' : ''; }
function ge_approval_fmt($n): string { return number_format((float)$n,2,',',' '); }
function ge_approval_row(PDO $pdo, int $id) {
    ge_approval_ensure_tables($pdo);
    $stmt=$pdo->prepare('SELECT * FROM ge_approval_requests WHERE id=? AND tenant_id=? LIMIT 1');
    $stmt->execute([$id, ge_current_tenant_id()]);
    return $stmt->fetch();
}
function ge_approval_docs(int $id): array {
    return array_values(array_filter(data_read('approval_documents', []), fn($d)=>(int)($d['approval_id'] ?? 0)===$id));
}
function ge_approval_doc_add(int $id, array $doc): void {
    $docs=data_read('approval_documents', []);
    $docs=array_values(array_filter($docs, fn($d)=>!((int)($d['approval_id'] ?? 0)===$id && ($d['filename'] ?? '')===($doc['filename'] ?? ''))));
    $doc['id']=next_id($docs);
    $doc['approval_id']=$id;
    $docs[]=$doc;
    data_write('approval_documents',$docs);
}
function ge_approval_user_label($id): string {
    $id=(int)$id; if($id<=0) return '-';
    $u=find_row_by_id(data_read('users',[]), $id);
    return $u ? ge_user_full_name($u) : '#'.$id;
}
function ge_approval_normalize_post(): array {
    $type=trim((string)($_POST['object_type'] ?? 'autre')) ?: 'autre';
    return [
        'title'=>trim((string)($_POST['title'] ?? '')),
        'object_type'=>$type,
        'object_id'=>(int)($_POST['object_id'] ?? 0),
        'object_ref'=>trim((string)($_POST['object_ref'] ?? '')),
        'requested_by'=>(int)($_POST['requested_by'] ?? 0) ?: ((int)(current_user()['id'] ?? 0) ?: null),
        'approver_id'=>(int)($_POST['approver_id'] ?? 0) ?: null,
        'status'=>$_POST['status'] ?? 'En attente',
        'amount'=>ge_decimal($_POST['amount'] ?? 0),
        'priority'=>trim((string)($_POST['priority'] ?? 'Normale')),
        'reason'=>trim((string)($_POST['reason'] ?? '')),
        'decision_reason'=>trim((string)($_POST['decision_reason'] ?? '')),
        'template'=>trim((string)($_POST['template'] ?? 'simple')),
    ];
}
function ge_approval_insert(PDO $pdo, array $d): int {
    ge_approval_ensure_tables($pdo);
    $stmt=$pdo->prepare('INSERT INTO ge_approval_requests(tenant_id,title,object_type,object_id,object_ref,requested_by,approver_id,status,amount,priority,reason,decision_reason,template) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $stmt->execute([ge_current_tenant_id(),$d['title'],$d['object_type'],$d['object_id'],$d['object_ref'],$d['requested_by'],$d['approver_id'],$d['status'],$d['amount'],$d['priority'],$d['reason'],$d['decision_reason'],$d['template']]);
    return (int)$pdo->lastInsertId();
}
function ge_approval_update(PDO $pdo, int $id, array $d): void {
    ge_approval_ensure_tables($pdo);
    $stmt=$pdo->prepare('UPDATE ge_approval_requests SET title=?, object_type=?, object_id=?, object_ref=?, requested_by=?, approver_id=?, status=?, amount=?, priority=?, reason=?, decision_reason=?, template=? WHERE id=? AND tenant_id=?');
    $stmt->execute([ge_current_tenant_id(),$d['title'],$d['object_type'],$d['object_id'],$d['object_ref'],$d['requested_by'],$d['approver_id'],$d['status'],$d['amount'],$d['priority'],$d['reason'],$d['decision_reason'],$d['template'],$id]);
}
function ge_approval_apply_decision(PDO $pdo, array $row, string $status): void {
    if($status !== 'Approuvé') return;
    $type=strtolower((string)($row['object_type'] ?? ''));
    $id=(int)($row['object_id'] ?? 0);
    if($id<=0) return;
    $map=[
        'devis'=>['quotes','Validé'],
        'commande'=>['orders','Validée'],
        'facture'=>['invoices','Validée'],
        'avoir'=>['credit_notes','Validé'],
    ];
    if(isset($map[$type])){
        [$collection,$targetStatus]=$map[$type];
        if($collection==='credit_notes'){
            try{ $stmt=$pdo->prepare('UPDATE ge_credit_notes SET status=? WHERE id=? AND tenant_id=?'); $stmt->execute([$targetStatus,$id,ge_current_tenant_id()]); if(function_exists('ge_erp_apply_credit_note')) ge_erp_apply_credit_note($pdo,$id); }catch(Throwable $e){}
        }else{
            $rows=data_read($collection,[]); $changed=false;
            foreach($rows as &$r){ if((int)($r['id'] ?? 0)===$id){ $r['status']=$targetStatus; $changed=true; break; } }
            unset($r); if($changed) data_write($collection,$rows);
        }
    } elseif($type==='achat') {
        try{ $stmt=$pdo->prepare('UPDATE ge_purchase_orders SET status=? WHERE id=? AND tenant_id=?'); $stmt->execute(['Validée',$id,ge_current_tenant_id()]); }catch(Throwable $e){}
    }
    $stmt=$pdo->prepare('UPDATE ge_approval_requests SET applied_at=NOW() WHERE id=? AND tenant_id=?');
    $stmt->execute([(int)$row['id'],ge_current_tenant_id()]);
}
function ge_approval_decide(PDO $pdo, int $id, string $status, string $reason='', bool $applyTarget=false): void {
    ge_approval_ensure_tables($pdo);
    $row=ge_approval_row($pdo,$id);
    if(!$row) return;
    $status=in_array($status, ge_approval_status_options(), true) ? $status : 'En attente';
    $decider=(int)(current_user()['id'] ?? 0) ?: null;
    $stmt=$pdo->prepare('UPDATE ge_approval_requests SET status=?, decision_reason=?, decided_by=?, decided_at=NOW() WHERE id=? AND tenant_id=?');
    $stmt->execute([$status,$reason,$decider,$id,ge_current_tenant_id()]);
    if($applyTarget) ge_approval_apply_decision($pdo, array_merge($row,['id'=>$id]), $status);
}
function ge_approval_generate_pdf_file(PDO $pdo, int $id): array {
    require_once __DIR__.'/../../app/pdf_docs.php';
    ge_approval_ensure_tables($pdo);
    $row=ge_approval_row($pdo,$id);
    if(!$row) return [];
    $ref=(string)($row['object_ref'] ?: ('VAL-'.$id));
    $pdf=new SimplePdf();
    ge_pdf_company_header($pdf, 'Fiche validation '.$ref, ['Statut'=>($row['status'] ?? ''), 'Date'=>($row['created_at'] ?? '')]);
    ge_pdf_sender_box($pdf);
    $pdf->text(290,117,'Demande',8);
    $pdf->rect(290,125,278,113,false);
    $pdf->text(300,145, strtoupper((string)($row['title'] ?: 'Demande de validation')), 11, true);
    $pdf->text(300,160, 'Objet : '.($row['object_type'] ?? '').' #'.($row['object_id'] ?? ''), 9);
    $pdf->text(300,175, 'Référence : '.$ref, 9);
    $pdf->text(300,190, 'Montant : '.ge_approval_fmt($row['amount'] ?? 0).' DH', 9, true);
    $pdf->text(300,205, 'Priorité : '.($row['priority'] ?? 'Normale'), 9);
    $pdf->text(28,262,'Détails de la demande',11,true);
    $pdf->rect(28,275,540,140,false);
    $y=298;
    $reason=trim((string)($row['reason'] ?? '')) ?: 'Aucune raison saisie.';
    foreach(array_slice(explode("\n", wordwrap($reason, 105, "\n", true)),0,8) as $line){ $pdf->text(38,$y,$line,9); $y+=13; }
    $pdf->text(28,445,'Décision',11,true);
    $pdf->rect(28,458,540,115,false);
    $pdf->text(38,482,'Statut : '.($row['status'] ?? ''),10,true);
    $pdf->text(38,500,'Demandé par : '.ge_approval_user_label((int)($row['requested_by'] ?? 0)),9);
    $pdf->text(38,516,'Valideur prévu : '.ge_approval_user_label((int)($row['approver_id'] ?? 0)),9);
    $pdf->text(38,532,'Décidé par : '.ge_approval_user_label((int)($row['decided_by'] ?? 0)),9);
    if(!empty($row['decision_reason'])) $pdf->text(38,552,'Commentaire : '.substr((string)$row['decision_reason'],0,95),8);
    $pdf->text(340,620,'Signature valideur',8); $pdf->rect(340,632,228,55,false);
    ge_pdf_doc_footer($pdf,1);
    $outDir=__DIR__.'/../../uploads/approvals';
    if(!is_dir($outDir)) mkdir($outDir,0777,true);
    $filename='Validation_'.preg_replace('/[^A-Za-z0-9_-]+/','-',$ref).'.pdf';
    $path=$outDir.'/'.$filename;
    $pdf->save($path);
    $doc=['filename'=>$filename,'original_name'=>$filename,'mime_type'=>'application/pdf','size'=>@filesize($path) ?: 0,'size_bytes'=>@filesize($path) ?: 0,'created_at'=>date('d/m/Y H:i'),'url'=>'uploads/approvals/'.$filename,'path'=>$path,'ref'=>$ref,'status'=>'PDF'];
    ge_approval_doc_add($id,$doc);
    return $doc;
}
