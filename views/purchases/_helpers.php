<?php
function ge_purchase_column_exists(PDO $pdo, string $table, string $column): bool {
    try {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?');
        $stmt->execute([$table, $column]);
        return (int)$stmt->fetchColumn() > 0;
    } catch (Throwable $e) { return false; }
}

function ge_purchase_add_column(PDO $pdo, string $table, string $column, string $definition): void {
    if (!ge_purchase_column_exists($pdo, $table, $column)) {
        try { $pdo->exec('ALTER TABLE '.ge_identifier($table).' ADD COLUMN '.ge_identifier($column).' '.$definition); } catch (Throwable $e) {}
    }
}

function ge_purchase_ensure_tables(PDO $pdo): void {
    if(function_exists('ge_erp_ensure_tables')) ge_erp_ensure_tables($pdo);
    db_install_enterprise_tables($pdo);
    ge_purchase_add_column($pdo, 'ge_purchase_orders', 'due_date', 'DATE NULL');
    ge_purchase_add_column($pdo, 'ge_purchase_orders', 'amount_tva', 'DECIMAL(15,3) NOT NULL DEFAULT 0');
    ge_purchase_add_column($pdo, 'ge_purchase_orders', 'amount_ttc', 'DECIMAL(15,3) NOT NULL DEFAULT 0');
    ge_purchase_add_column($pdo, 'ge_purchase_orders', 'supplier_invoice_id', 'INT NULL');
    ge_purchase_add_column($pdo, 'ge_purchase_orders', 'supplier_invoice_ref', 'VARCHAR(90) NULL');
    ge_purchase_add_column($pdo, 'ge_purchase_orders', 'template', 'VARCHAR(90) NULL');

    ge_purchase_add_column($pdo, 'ge_supplier_invoices', 'purchase_order_id', 'INT NULL');
    ge_purchase_add_column($pdo, 'ge_supplier_invoices', 'purchase_order_ref', 'VARCHAR(90) NULL');
    ge_purchase_add_column($pdo, 'ge_supplier_invoices', 'amount_tva', 'DECIMAL(15,3) NOT NULL DEFAULT 0');
    ge_purchase_add_column($pdo, 'ge_supplier_invoices', 'note', 'TEXT NULL');
    ge_purchase_add_column($pdo, 'ge_supplier_invoices', 'paid_at', 'DATE NULL');
    ge_purchase_add_column($pdo, 'ge_supplier_invoices', 'template', 'VARCHAR(90) NULL');

    $pdo->exec("CREATE TABLE IF NOT EXISTS ge_purchase_order_documents (
        record_id INT NOT NULL PRIMARY KEY,
        payload LONGTEXT NOT NULL,
        extra_json LONGTEXT NULL,
        ref VARCHAR(90) NULL,
        label VARCHAR(190) NULL,
        status VARCHAR(90) NULL,
        amount DECIMAL(15,3) NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_ref(ref), KEY idx_status(status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS ge_supplier_invoice_documents (
        record_id INT NOT NULL PRIMARY KEY,
        payload LONGTEXT NOT NULL,
        extra_json LONGTEXT NULL,
        ref VARCHAR(90) NULL,
        label VARCHAR(190) NULL,
        status VARCHAR(90) NULL,
        amount DECIMAL(15,3) NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_ref(ref), KEY idx_status(status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function ge_purchase_status_options(string $type = 'order'): array {
    return $type === 'invoice'
        ? ['Brouillon','À payer','Partiellement payée','Payée','En retard','Annulée']
        : ['Brouillon','Validée','Commandée','Reçue partiellement','Reçue','Facturée','Annulée'];
}
function ge_purchase_selected($a, $b): string { return (string)$a === (string)$b ? ' selected' : ''; }
function ge_purchase_ref_clean(string $ref): string { return preg_replace('/[^A-Za-z0-9_-]+/', '-', $ref); }
function ge_purchase_fmt($n): string { return number_format((float)$n, 2, ',', ' '); }
function ge_purchase_date($v): string { return $v ? date('Y-m-d', strtotime((string)$v)) : ''; }

function ge_purchase_product_select(array $products, $selected=''): string {
    $html='<select name="line_product_id[]" class="purchase-product-select" data-product-line-select data-placeholder="Choisir produit">';
    $html.='<option value="">Produit libre</option>';
    foreach($products as $p){
        $id=(int)($p['id'] ?? 0); if($id<=0) continue;
        $ref=(string)($p['ref'] ?? '');
        $label=(string)($p['label'] ?? $p['name'] ?? 'Produit');
        $text=trim(($ref !== '' ? $ref.' - ' : '').$label);
        $price=(float)($p['buy_price'] ?? $p['cost_price'] ?? $p['purchase_price'] ?? $p['sale_price'] ?? 0);
        $vat=(float)($p['tax_rate'] ?? $p['tva'] ?? $p['vat'] ?? 20);
        $unit=(string)($p['unit'] ?? 'u.');
        $sel=((string)$selected === (string)$id) ? ' selected' : '';
        $html.='<option value="'.$id.'"'.$sel.' data-ref="'.e($ref).'" data-label="'.e($label).'" data-price="'.e($price).'" data-cost="'.e($price).'" data-vat="'.e($vat).'" data-unit="'.e($unit).'">'.e($text).'</option>';
    }
    $html.='</select>';
    return $html;
}
function ge_purchase_warehouse_select(array $warehouses, $selected=''): string {
    $html='<select name="line_warehouse_id[]" class="purchase-warehouse-select" data-placeholder="Choisir entrepôt">';
    $html.='<option value="">Entrepôt par défaut</option>';
    foreach($warehouses as $w){
        $id=(int)($w['id'] ?? 0); if($id<=0) continue;
        $label=(string)($w['name'] ?? $w['label'] ?? ('Entrepôt #'.$id));
        $sel=((string)$selected === (string)$id) ? ' selected' : '';
        $html.='<option value="'.$id.'"'.$sel.'>'.e($label).'</option>';
    }
    $html.='</select>';
    return $html;
}

function ge_purchase_order_row(PDO $pdo, int $id) {
    ge_purchase_ensure_tables($pdo);
    $stmt = $pdo->prepare('SELECT * FROM ge_purchase_orders WHERE id=? AND tenant_id=? LIMIT 1');
    $stmt->execute([$id, ge_current_tenant_id()]);
    return $stmt->fetch();
}
function ge_supplier_invoice_row(PDO $pdo, int $id) {
    ge_purchase_ensure_tables($pdo);
    $stmt = $pdo->prepare('SELECT * FROM ge_supplier_invoices WHERE id=? AND tenant_id=? LIMIT 1');
    $stmt->execute([$id, ge_current_tenant_id()]);
    return $stmt->fetch();
}
function ge_purchase_docs(string $type, int $id): array {
    $collection = $type === 'invoice' ? 'supplier_invoice_documents' : 'purchase_order_documents';
    $key = $type === 'invoice' ? 'supplier_invoice_id' : 'purchase_order_id';
    return array_values(array_filter(data_read($collection, []), fn($d) => (int)($d[$key] ?? 0) === $id));
}
function ge_purchase_doc_add(string $type, int $id, array $doc): void {
    $collection = $type === 'invoice' ? 'supplier_invoice_documents' : 'purchase_order_documents';
    $key = $type === 'invoice' ? 'supplier_invoice_id' : 'purchase_order_id';
    $docs = data_read($collection, []);
    $docs = array_values(array_filter($docs, fn($d) => !((int)($d[$key] ?? 0) === $id && ($d['filename'] ?? '') === ($doc['filename'] ?? ''))));
    $doc['id'] = next_id($docs);
    $doc[$key] = $id;
    $docs[] = $doc;
    data_write($collection, $docs);
}


function ge_purchase_lines(PDO $pdo, string $type, int $id): array {
    if(function_exists('ge_erp_purchase_lines') && $type==='order') return ge_erp_purchase_lines($pdo,$id);
    if(function_exists('ge_erp_supplier_invoice_lines') && $type==='invoice') return ge_erp_supplier_invoice_lines($pdo,$id);
    return [];
}
function ge_purchase_sync_order_lines_from_post(PDO $pdo, int $orderId): void {
    if(function_exists('ge_erp_save_purchase_lines_from_post')) ge_erp_save_purchase_lines_from_post($pdo,$orderId);
}
function ge_purchase_lines_total(array $lines): array {
    $ht=0; $tva=0; $ttc=0;
    foreach($lines as $l){ $ht+=(float)($l['total_ht'] ?? 0); $tva+=(float)($l['total_tva'] ?? 0); $ttc+=(float)($l['total_ttc'] ?? 0); }
    return [$ht,$tva,$ttc];
}

function ge_purchase_default_line(array $row, string $type): array {
    $ht = (float)($row['amount_ht'] ?? 0);
    $ttc = (float)($row['amount_ttc'] ?? 0);
    if ($ttc <= 0) $ttc = $ht;
    $tva = max(0, $ttc - $ht);
    $rate = ($ht > 0 && $tva > 0) ? round(($tva / $ht) * 100, 2) : 0;
    return [
        'description' => $type === 'invoice' ? 'Facture fournisseur '.$row['ref'] : 'Bon de commande fournisseur '.$row['ref'],
        'tva' => $rate,
        'pu_ht' => $ht,
        'qty' => 1,
        'unit' => 'u.',
        'total_ht' => $ht,
    ];
}

function ge_purchase_generate_pdf_file(PDO $pdo, string $type, int $id): array {
    require_once __DIR__.'/../../app/pdf_docs.php';
    ge_purchase_ensure_tables($pdo);
    $row = $type === 'invoice' ? ge_supplier_invoice_row($pdo, $id) : ge_purchase_order_row($pdo, $id);
    if (!$row) return [];

    $isInvoice = $type === 'invoice';
    $title = $isInvoice ? 'Facture fournisseur' : 'Bon de commande fournisseur';
    $prefix = $isInvoice ? 'Facture-Fournisseur' : 'Commande-Fournisseur';
    $ref = (string)($row['ref'] ?? ('#'.$id));
    $ht = (float)($row['amount_ht'] ?? 0);
    $tva = (float)($row['amount_tva'] ?? 0);
    $ttc = (float)($row['amount_ttc'] ?? 0);
    if ($ttc <= 0) $ttc = $ht + $tva;
    if ($tva <= 0 && $ttc > $ht) $tva = $ttc - $ht;
    $dateLabel = $isInvoice ? 'Date facture' : 'Date commande';
    $dateValue = $isInvoice ? ($row['invoice_date'] ?? '') : ($row['order_date'] ?? '');
    $dueValue = $isInvoice ? ($row['due_date'] ?? '') : ($row['due_date'] ?? '');
    $supplier = trim((string)($row['supplier_name'] ?? 'Fournisseur'));

    $pdf = new SimplePdf();
    ge_pdf_company_header($pdf, $title.' '.$ref, [$dateLabel=>$dateValue, 'Statut'=>($row['status'] ?? '')]);
    ge_pdf_sender_box($pdf);
    $supplierData = ge_pdf_tier_data((int)($row['supplier_id'] ?? 0), $supplier);
    ge_pdf_client_box($pdf, $supplierData, 290, 125);

    $pdf->textRight(565, 262, 'Montants exprimés en Dirham', 8);
    $pdf->rect(28,270,540,210,false);
    $pdf->line(28,287,568,287);
    $pdf->line(330,270,330,480); $pdf->line(375,270,375,480); $pdf->line(442,270,442,480); $pdf->line(490,270,490,480);
    $pdf->text(32,282,'Désignation',9,true); $pdf->text(343,282,'TVA',9,true); $pdf->text(395,282,'P.U. HT',9,true); $pdf->text(460,282,'Qté',9,true); $pdf->text(518,282,'Total HT',9,true);

    $lines = ge_purchase_lines($pdo, $type, $id);
    if (!$lines) $lines = [ge_purchase_default_line($row, $type)];
    $yy = 305;
    foreach (array_slice($lines, 0, 8) as $line) {
        $desc = wordwrap((string)($line['product_label'] ?? $line['description'] ?? ''), 52, "\n", true);
        foreach (array_slice(explode("\n", $desc), 0, 2) as $i=>$d) $pdf->text(32, $yy + ($i*9), $d, 8, $i===0);
        $pdf->textRight(372, $yy, ge_purchase_fmt($line['tva_rate'] ?? $line['tva'] ?? 0).'%', 8, true);
        $pdf->textRight(438, $yy, ge_purchase_fmt($line['pu_ht'] ?? 0), 8, true);
        $pdf->textRight(486, $yy, ge_purchase_fmt($line['qty'] ?? 1), 8, true);
        $pdf->textRight(565, $yy, ge_purchase_fmt($line['total_ht'] ?? 0), 8, true);
        $pdf->line(28, $yy+22, 568, $yy+22, 0.78, 0.3);
        $yy += 25;
    }

    $baseY = 515;
    if (!empty($row['purchase_order_ref'])) $pdf->text(28, $baseY, 'Bon de commande source : '.$row['purchase_order_ref'], 9, true);
    if (!empty($row['supplier_invoice_ref'])) $pdf->text(28, $baseY, 'Facture fournisseur liée : '.$row['supplier_invoice_ref'], 9, true);
    if ($dueValue) $pdf->text(28, $baseY+16, 'Échéance : '.$dueValue, 9, true);
    if (!empty($row['note'])) $pdf->text(28, $baseY+38, 'Note : '.substr((string)$row['note'],0,150), 8);

    $sumY = 610;
    $pdf->text(340,$sumY,'Total HT',10,true); $pdf->textRight(555,$sumY,ge_purchase_fmt($ht),10,true);
    $pdf->text(340,$sumY+18,'Total TVA',10,true); $pdf->textRight(555,$sumY+18,ge_purchase_fmt($tva),10,true);
    $pdf->rect(338,$sumY+28,220,18,true,0.90); $pdf->text(340,$sumY+41,'Total TTC',10,true); $pdf->textRight(555,$sumY+41,ge_purchase_fmt($ttc),10,true);
    $pdf->text(340,$sumY+72,'Signature / validation',8); $pdf->rect(340,$sumY+82,228,50,false);
    ge_pdf_doc_footer($pdf, 1);

    $outDir = __DIR__.'/../../uploads/purchases';
    if (!is_dir($outDir)) mkdir($outDir, 0777, true);
    $filename = $prefix.'_'.ge_purchase_ref_clean($ref).'.pdf';
    $path = $outDir.'/'.$filename;
    $pdf->save($path);
    $doc = ['filename'=>$filename,'original_name'=>$filename,'mime_type'=>'application/pdf','size'=>@filesize($path) ?: 0,'size_bytes'=>@filesize($path) ?: 0,'created_at'=>date('d/m/Y H:i'),'url'=>'uploads/purchases/'.$filename,'path'=>$path,'ref'=>$ref,'status'=>'PDF'];
    ge_purchase_doc_add($type, $id, $doc);
    return $doc;
}

function ge_purchase_make_supplier_invoice(PDO $pdo, int $orderId): int {
    ge_purchase_ensure_tables($pdo);
    $order = ge_purchase_order_row($pdo, $orderId);
    if (!$order) return 0;
    if (!empty($order['supplier_invoice_id'])) return (int)$order['supplier_invoice_id'];

    $ref = 'FF-'.date('ymd-His');
    $date = date('Y-m-d');
    $due = date('Y-m-d', strtotime('+30 days'));
    $ht = (float)($order['amount_ht'] ?? 0);
    $tva = (float)($order['amount_tva'] ?? 0);
    $ttc = (float)($order['amount_ttc'] ?? 0);
    if ($ttc <= 0) $ttc = $ht + $tva;
    $stmt = $pdo->prepare('INSERT INTO ge_supplier_invoices(tenant_id,ref,supplier_id,supplier_name,invoice_date,due_date,status,amount_ht,amount_tva,amount_ttc,purchase_order_id,purchase_order_ref,note) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $stmt->execute([ge_current_tenant_id(), $ref, (int)($order['supplier_id'] ?? 0) ?: null, $order['supplier_name'] ?? 'Fournisseur', $date, $due, 'À payer', $ht, $tva, $ttc, $orderId, $order['ref'] ?? '', $order['note'] ?? '']);
    $invoiceId = (int)$pdo->lastInsertId();
    if(function_exists('ge_erp_copy_purchase_lines_to_supplier_invoice')) ge_erp_copy_purchase_lines_to_supplier_invoice($pdo, $orderId, $invoiceId);
    if(function_exists('ge_erp_post_supplier_invoice_accounting')) ge_erp_post_supplier_invoice_accounting($pdo, $invoiceId);
    $up = $pdo->prepare('UPDATE ge_purchase_orders SET supplier_invoice_id=?, supplier_invoice_ref=?, status=? WHERE id=? AND tenant_id=?');
    $up->execute([$invoiceId, $ref, 'Facturée', $orderId, ge_current_tenant_id()]);
    audit_log('supplier_invoice_created_from_purchase_order', 'Commande: '.($order['ref'] ?? $orderId).' | Facture: '.$ref);
    return $invoiceId;
}
