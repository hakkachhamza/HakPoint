<?php
function ge_credit_column_exists(PDO $pdo, string $table, string $column): bool {
    try {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?');
        $stmt->execute([$table, $column]);
        return (int)$stmt->fetchColumn() > 0;
    } catch (Throwable $e) { return false; }
}
function ge_credit_add_column(PDO $pdo, string $table, string $column, string $definition): void {
    if (!ge_credit_column_exists($pdo, $table, $column)) {
        try { $pdo->exec('ALTER TABLE '.ge_identifier($table).' ADD COLUMN '.ge_identifier($column).' '.$definition); } catch (Throwable $e) {}
    }
}
function ge_credit_ensure_tables(PDO $pdo): void {
    if(function_exists('ge_erp_ensure_tables')) ge_erp_ensure_tables($pdo);
    db_install_enterprise_tables($pdo);
    ge_credit_add_column($pdo, 'ge_credit_notes', 'client_id', 'INT NULL');
    ge_credit_add_column($pdo, 'ge_credit_notes', 'invoice_ref', 'VARCHAR(90) NULL');
    ge_credit_add_column($pdo, 'ge_credit_notes', 'amount_tva', 'DECIMAL(15,3) NOT NULL DEFAULT 0');
    ge_credit_add_column($pdo, 'ge_credit_notes', 'note', 'TEXT NULL');
    ge_credit_add_column($pdo, 'ge_credit_notes', 'refunded_at', 'DATE NULL');
    ge_credit_add_column($pdo, 'ge_credit_notes', 'template', 'VARCHAR(90) NULL');
    db_install_collection_table($pdo, 'ge_credit_note_documents');
}
function ge_credit_status_options(): array { return ['Brouillon','Validé','Appliqué','Remboursé','Utilisé','Annulé']; }
function ge_credit_selected($a, $b): string { return (string)$a === (string)$b ? ' selected' : ''; }
function ge_credit_fmt($n): string { return number_format((float)$n, 2, ',', ' '); }
function ge_credit_date($v): string { return $v ? date('Y-m-d', strtotime((string)$v)) : ''; }
function ge_credit_ref_clean(string $ref): string { return preg_replace('/[^A-Za-z0-9_-]+/', '-', $ref); }
function ge_credit_row(PDO $pdo, int $id) {
    ge_credit_ensure_tables($pdo);
    $stmt = $pdo->prepare('SELECT * FROM ge_credit_notes WHERE id=? AND tenant_id=? LIMIT 1');
    $stmt->execute([$id, ge_current_tenant_id()]);
    return $stmt->fetch();
}
function ge_credit_docs(int $id): array {
    return array_values(array_filter(data_read('credit_note_documents', []), fn($d) => (int)($d['credit_note_id'] ?? 0) === $id));
}
function ge_credit_doc_add(int $id, array $doc): void {
    $docs = data_read('credit_note_documents', []);
    $docs = array_values(array_filter($docs, fn($d) => !((int)($d['credit_note_id'] ?? 0) === $id && ($d['filename'] ?? '') === ($doc['filename'] ?? ''))));
    $doc['id'] = next_id($docs);
    $doc['credit_note_id'] = $id;
    $docs[] = $doc;
    data_write('credit_note_documents', $docs);
}
function ge_credit_invoice_options(): array {
    $items = [];
    foreach (data_read('invoices', []) as $inv) {
        $id = (int)($inv['id'] ?? 0);
        if ($id <= 0) continue;
        $items[] = [
            'id' => $id,
            'ref' => (string)($inv['ref'] ?? ('#'.$id)),
            'client' => (string)($inv['client'] ?? $inv['client_name'] ?? $inv['tier_name'] ?? ''),
            'amount_ht' => (float)($inv['total_ht'] ?? $inv['amount_ht'] ?? 0),
            'amount_ttc' => (float)($inv['total_ttc'] ?? $inv['amount_ttc'] ?? 0),
        ];
    }
    return $items;
}
function ge_credit_normalize_post(PDO $pdo, array $old=[]): array {
    $invoiceId = (int)($_POST['invoice_id'] ?? 0);
    $invoiceRef = trim((string)($_POST['invoice_ref'] ?? ''));
    $clientName = trim((string)($_POST['client_name'] ?? ''));
    if ($invoiceId > 0) {
        $inv = find_row_by_id(data_read('invoices', []), $invoiceId) ?: [];
        if ($invoiceRef === '') $invoiceRef = (string)($inv['ref'] ?? '');
        if ($clientName === '') $clientName = (string)($inv['client'] ?? $inv['client_name'] ?? $inv['tier_name'] ?? '');
    }
    $ht = ge_decimal($_POST['amount_ht'] ?? 0);
    $tva = ge_decimal($_POST['amount_tva'] ?? 0);
    $ttc = ge_decimal($_POST['amount_ttc'] ?? 0);
    if ($ttc <= 0) $ttc = $ht + $tva;
    if ($tva <= 0 && $ttc > $ht) $tva = $ttc - $ht;
    return [
        'ref' => trim((string)($_POST['ref'] ?? '')) ?: ($old['ref'] ?? ('AV-'.date('ymd-His'))),
        'invoice_id' => $invoiceId ?: null,
        'invoice_ref' => $invoiceRef,
        'client_id' => (int)($_POST['client_id'] ?? 0) ?: null,
        'client_name' => $clientName,
        'credit_date' => ge_date_or_null($_POST['credit_date'] ?? '') ?: date('Y-m-d'),
        'status' => $_POST['status'] ?? ($old['status'] ?? 'Brouillon'),
        'amount_ht' => $ht,
        'amount_tva' => $tva,
        'amount_ttc' => $ttc,
        'reason' => trim((string)($_POST['reason'] ?? '')),
        'note' => trim((string)($_POST['note'] ?? '')),
        'template' => trim((string)($_POST['template'] ?? 'simple')),
    ];
}
function ge_credit_insert(PDO $pdo, array $d): int {
    ge_credit_ensure_tables($pdo);
    $stmt = $pdo->prepare('INSERT INTO ge_credit_notes(tenant_id,ref,invoice_id,invoice_ref,client_id,client_name,credit_date,status,amount_ht,amount_tva,amount_ttc,reason,note,template,refunded_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $stmt->execute([ge_current_tenant_id(),$d['ref'],$d['invoice_id'],$d['invoice_ref'],$d['client_id'],$d['client_name'],$d['credit_date'],$d['status'],$d['amount_ht'],$d['amount_tva'],$d['amount_ttc'],$d['reason'],$d['note'],$d['template'],($d['status']==='Remboursé'?date('Y-m-d'):null)]);
    $id=(int)$pdo->lastInsertId();
    if(function_exists('ge_erp_apply_credit_note')) ge_erp_apply_credit_note($pdo, $id);
    return $id;
}
function ge_credit_update(PDO $pdo, int $id, array $d): void {
    ge_credit_ensure_tables($pdo);
    $refunded = $d['status'] === 'Remboursé' ? date('Y-m-d') : null;
    $stmt = $pdo->prepare('UPDATE ge_credit_notes SET ref=?, invoice_id=?, invoice_ref=?, client_id=?, client_name=?, credit_date=?, status=?, amount_ht=?, amount_tva=?, amount_ttc=?, reason=?, note=?, template=?, refunded_at=? WHERE id=? AND tenant_id=?');
    $stmt->execute([ge_current_tenant_id(),$d['ref'],$d['invoice_id'],$d['invoice_ref'],$d['client_id'],$d['client_name'],$d['credit_date'],$d['status'],$d['amount_ht'],$d['amount_tva'],$d['amount_ttc'],$d['reason'],$d['note'],$d['template'],$refunded,$id]);
    if(function_exists('ge_erp_apply_credit_note')) ge_erp_apply_credit_note($pdo, $id);
}
function ge_credit_generate_pdf_file(PDO $pdo, int $id): array {
    require_once __DIR__.'/../../app/pdf_docs.php';
    ge_credit_ensure_tables($pdo);
    $row = ge_credit_row($pdo, $id);
    if (!$row) return [];
    $ref = (string)($row['ref'] ?? ('#'.$id));
    $ht = (float)($row['amount_ht'] ?? 0);
    $tva = (float)($row['amount_tva'] ?? 0);
    $ttc = (float)($row['amount_ttc'] ?? 0);
    if ($ttc <= 0) $ttc = $ht + $tva;
    if ($tva <= 0 && $ttc > $ht) $tva = $ttc - $ht;
    $rate = ($ht > 0 && $tva > 0) ? round(($tva / $ht) * 100, 2) : 0;
    $client = ge_pdf_tier_data((int)($row['client_id'] ?? 0), (string)($row['client_name'] ?? 'Client'));
    $pdf = new SimplePdf();
    ge_pdf_company_header($pdf, 'Avoir client '.$ref, ['Date avoir'=>($row['credit_date'] ?? ''), 'Statut'=>($row['status'] ?? '')]);
    ge_pdf_sender_box($pdf);
    ge_pdf_client_box($pdf, $client, 290, 125);
    $pdf->textRight(565, 262, 'Montants exprimés en Dirham', 8);
    $pdf->rect(28,270,540,210,false);
    $pdf->line(28,287,568,287);
    $pdf->line(330,270,330,480); $pdf->line(375,270,375,480); $pdf->line(442,270,442,480); $pdf->line(490,270,490,480);
    $pdf->text(32,282,'Désignation',9,true); $pdf->text(343,282,'TVA',9,true); $pdf->text(395,282,'P.U. HT',9,true); $pdf->text(460,282,'Qté',9,true); $pdf->text(518,282,'Total HT',9,true);
    $desc = trim((string)($row['reason'] ?? '')) ?: 'Avoir client '.$ref;
    if (!empty($row['invoice_ref'])) $desc .= ' - Facture source '.$row['invoice_ref'];
    $parts = explode("\n", wordwrap($desc, 52, "\n", true));
    foreach (array_slice($parts,0,3) as $i=>$d) $pdf->text(32, 305 + ($i*10), $d, 8, $i===0);
    $pdf->textRight(372,305,ge_credit_fmt($rate).'%',8,true);
    $pdf->textRight(438,305,ge_credit_fmt($ht),8,true);
    $pdf->textRight(486,305,'1',8,true);
    $pdf->textRight(565,305,ge_credit_fmt($ht),8,true);
    $pdf->line(28,329,568,329,0.78,0.3);
    $baseY=515;
    if (!empty($row['invoice_ref'])) $pdf->text(28,$baseY,'Facture source : '.$row['invoice_ref'],9,true);
    if (!empty($row['note'])) $pdf->text(28,$baseY+18,'Note : '.substr((string)$row['note'],0,160),8);
    $sumY=610;
    $pdf->text(340,$sumY,'Total HT',10,true); $pdf->textRight(555,$sumY,ge_credit_fmt($ht),10,true);
    $pdf->text(340,$sumY+18,'Total TVA',10,true); $pdf->textRight(555,$sumY+18,ge_credit_fmt($tva),10,true);
    $pdf->rect(338,$sumY+28,220,18,true,0.90); $pdf->text(340,$sumY+41,'Total avoir TTC',10,true); $pdf->textRight(555,$sumY+41,ge_credit_fmt($ttc),10,true);
    $pdf->text(340,$sumY+72,'Signature / validation',8); $pdf->rect(340,$sumY+82,228,50,false);
    ge_pdf_doc_footer($pdf,1);
    $outDir = __DIR__.'/../../uploads/credit_notes';
    if (!is_dir($outDir)) mkdir($outDir,0777,true);
    $filename = 'Avoir-Client_'.ge_credit_ref_clean($ref).'.pdf';
    $path = $outDir.'/'.$filename;
    $pdf->save($path);
    $doc = ['filename'=>$filename,'original_name'=>$filename,'mime_type'=>'application/pdf','size'=>@filesize($path) ?: 0,'size_bytes'=>@filesize($path) ?: 0,'created_at'=>date('d/m/Y H:i'),'url'=>'uploads/credit_notes/'.$filename,'path'=>$path,'ref'=>$ref,'status'=>'PDF'];
    ge_credit_doc_add($id, $doc);
    return $doc;
}
