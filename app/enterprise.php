<?php
/**
 * Enterprise helpers: simple Dolibarr-like building blocks without installer/migrations.
 * These helpers keep the UI simple and reusable across modules.
 */

function ge_default_modules(){
    return [
        'tiers'=>'Tiers / CRM',
        'products'=>'Produits & services',
        'sales'=>'Ventes: devis, commandes, factures',
        'purchases'=>'Achats fournisseurs',
        'stock'=>'Stock & entrepôts',
        'finance'=>'Banque, caisse, paiements',
        'accounting'=>'Comptabilité simple',
        'documents'=>'GED / documents',
        'signatures'=>'Signatures électroniques',
        'projects'=>'Projets & tâches',
        'agenda'=>'Agenda & relances',
        'api'=>'API',
        'reports'=>'Rapports',
        'analytics'=>'Analyse / BI',
        'pos'=>'Caisse / POS',
        'manufacturing'=>'BOM / fabrication',
        'settings'=>'Paramètres'
    ];
}
function ge_modules_state(){
    $modules=app_setting('modules', []);
    if(!is_array($modules)) $modules=[];
    foreach(ge_default_modules() as $key=>$label){ if(!array_key_exists($key,$modules)) $modules[$key]=true; }
    return $modules;
}
function ge_module_enabled($key){
    $modules=ge_modules_state();
    return !array_key_exists($key,$modules) || !empty($modules[$key]);
}
function ge_save_modules_state($posted){
    $state=[];
    foreach(ge_default_modules() as $key=>$label){ $state[$key]=in_array($key,(array)$posted,true); }
    save_app_settings(['modules'=>$state]);
    audit_log('modules_updated','Modules updated');
}
function ge_workflows(){
    return [
        'quote'=>['Brouillon','Ouvert','Envoyé','Signée (à facturer)','Non signée (fermée)','Facturée','Annulée'],
        'order'=>['Brouillon','Validée','En préparation','Expédiée partiellement','Livrée','Facturée','Annulée'],
        'invoice'=>['Brouillon','Validée','Envoyée','Impayée','Payée partiellement','Payée','En retard','Abandonnée','Annulée'],
        'purchase_order'=>['Brouillon','À valider','Validé','Commandé','Reçu partiellement','Reçu','Facturé','Payé','Annulé'],
        'supplier_invoice'=>['Brouillon','À payer','Payée partiellement','Payée','En retard','Annulée'],
        'credit_note'=>['Brouillon','Validé','Appliqué','Remboursé','Annulé'],
        'approval'=>['En attente','Approuvée','Refusée','Annulée'],
        'project'=>['Nouveau','En cours','En pause','Terminé','Annulé'],
        'task'=>['À faire','En cours','Terminée','Annulée'],
        'payment'=>['Brouillon','Confirmé','Rapproché','Annulé'],
        'inventory'=>['Brouillon','Validé','Appliqué','Annulé'],
        'pos_sale'=>['Brouillon','Payée','Annulée'],
        'manufacturing'=>['Brouillon','Lancée','Terminée','Annulée']
    ];
}
function ge_status_options($type){ $w=ge_workflows(); return $w[$type] ?? ['Brouillon','Validé','Annulé']; }
function ge_new_ref($prefix, $collection){
    return $prefix.date('ymd').'-'.str_pad((string)next_id(data_read($collection,[])),5,'0',STR_PAD_LEFT);
}
function ge_post_value($name,$type,$old=null){
    if($type==='checkbox') return !empty($_POST[$name]);
    $v=$_POST[$name] ?? $old;
    if($type==='number' || $type==='money' || $type==='qty') return ge_parse_number($v);
    return is_string($v) ? trim($v) : $v;
}
function ge_simple_fields_from_post($fields,$old=[]){
    $row=$old;
    foreach($fields as $name=>$meta){
        $type=$meta['type'] ?? 'text';
        $row[$name]=ge_post_value($name,$type,$old[$name] ?? null);
    }
    $row['updated_at']=date('Y-m-d H:i:s');
    return $row;
}
function ge_simple_handle_crud($collection, $fields, $redirectPage, $refPrefix=''){
    if(($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') return;
    require_csrf();
    $action=$_POST['action'] ?? 'save';
    $rows=data_read($collection,[]);
    if($action==='delete'){
        $id=(int)($_POST['id'] ?? 0);
        $deletedRow=null;
        foreach($rows as $r){ if((int)($r['id']??0)===$id){ $deletedRow=$r; break; } }
        if(in_array($collection, ['payments','supplier_payments'], true) && $deletedRow && function_exists('ge_finance_payment_is_active') && ge_finance_payment_is_active((string)($deletedRow['status'] ?? ''))){
            // Enterprise rule: confirmed/reconciled payments are not deleted; they are cancelled with reversing side effects.
            ge_finance_set_payment_status($collection, $id, 'Annulé');
            audit_log('enterprise_cancel_payment',$collection.' #'.$id.' annulé au lieu de suppression');
            redirect_to('index.php?page='.$redirectPage.'&cancelled=1');
        }
        $rows=array_values(array_filter($rows, fn($r)=>(int)($r['id']??0)!==$id));
        data_write($collection,$rows);
        $oldInvoiceId=(int)($deletedRow['invoice_id'] ?? $_POST['invoice_id'] ?? 0);
        $oldSupplierInvoiceId=(int)($deletedRow['supplier_invoice_id'] ?? $_POST['supplier_invoice_id'] ?? 0);
        if($collection==='payments' && $oldInvoiceId>0) ge_finance_update_invoice_payment($oldInvoiceId);
        if($collection==='supplier_payments' && function_exists('ge_finance_update_supplier_invoice_payment') && $oldSupplierInvoiceId>0) ge_finance_update_supplier_invoice_payment($oldSupplierInvoiceId);
        if(function_exists('ge_erp_remove_bank_movement') && $collection==='payments') ge_erp_remove_bank_movement('payment',$id);
        if(function_exists('ge_erp_remove_bank_movement') && $collection==='supplier_payments') ge_erp_remove_bank_movement('supplier_payment',$id);
        if(function_exists('ge_accounting_post_lines') && $collection==='payments') ge_accounting_post_lines('payment',$id,'BQ','',[]);
        if(function_exists('ge_accounting_post_lines') && $collection==='supplier_payments') ge_accounting_post_lines('supplier_payment',$id,'BQ','',[]);
        if(function_exists('ge_erp_update_bank_balances') && in_array($collection, ['payments','supplier_payments'], true)) ge_erp_update_bank_balances();
        audit_log('enterprise_delete',$collection.' #'.$id);
        redirect_to('index.php?page='.$redirectPage.'&deleted=1');
    }
    $id=(int)($_POST['id'] ?? 0);
    $old=[]; $idx=-1;
    foreach($rows as $i=>$r){ if((int)($r['id']??0)===$id){ $old=$r; $idx=$i; break; } }
    if($idx>=0 && in_array($collection, ['payments','supplier_payments'], true) && function_exists('ge_finance_payment_is_active') && ge_finance_payment_is_active((string)($old['status'] ?? ''))){
        audit_log('enterprise_locked_payment_edit',$collection.' #'.$id.' modification bloquée');
        $showPage = $collection==='payments' ? 'payment_show' : 'supplier_payment_show';
        redirect_to('index.php?page='.$showPage.'&id='.$id.'&locked=1');
    }
    if($id<=0){ $id=next_id($rows); $old=['id'=>$id,'created_at'=>date('Y-m-d H:i:s')]; if($refPrefix && empty($old['ref'])) $old['ref']=ge_new_ref($refPrefix,$collection); }
    $row=ge_simple_fields_from_post($fields,$old);
    $row['id']=$id;
    if($refPrefix && trim((string)($row['ref'] ?? ''))==='') $row['ref']=ge_new_ref($refPrefix,$collection);
    if($idx>=0) $rows[$idx]=$row; else $rows[]=$row;
    data_write($collection,$rows);
    if($collection==='payments' && !empty($row['invoice_id'])) ge_finance_update_invoice_payment((int)$row['invoice_id']);
    if($collection==='supplier_payments' && function_exists('ge_finance_update_supplier_invoice_payment') && !empty($row['supplier_invoice_id'])) ge_finance_update_supplier_invoice_payment((int)$row['supplier_invoice_id']);
    if(function_exists('ge_erp_sync_finance_payment_side_effects') && in_array($collection, ['payments','supplier_payments'], true)) ge_erp_sync_finance_payment_side_effects($collection, $row);
    if($collection==='payments' && in_array((string)($row['status'] ?? ''), ['Confirmé','Rapproché'], true)) ge_accounting_post_lines('payment',$id,'BQ','Paiement client '.($row['ref'] ?? ''),[
        ['account'=>'512000','amount'=>($row['amount'] ?? 0),'debit'=>($row['amount'] ?? 0),'credit'=>0],
        ['account'=>'411000','amount'=>($row['amount'] ?? 0),'debit'=>0,'credit'=>($row['amount'] ?? 0)],
    ]);
    if($collection==='supplier_payments' && in_array((string)($row['status'] ?? ''), ['Confirmé','Rapproché'], true)) ge_accounting_post_lines('supplier_payment',$id,'BQ','Paiement fournisseur '.($row['ref'] ?? ''),[
        ['account'=>'401000','amount'=>($row['amount'] ?? 0),'debit'=>($row['amount'] ?? 0),'credit'=>0],
        ['account'=>'512000','amount'=>($row['amount'] ?? 0),'debit'=>0,'credit'=>($row['amount'] ?? 0)],
    ]);
    if($collection==='payments' && !in_array((string)($row['status'] ?? ''), ['Confirmé','Rapproché'], true)) ge_accounting_post_lines('payment',$id,'BQ','',[]);
    if($collection==='supplier_payments' && !in_array((string)($row['status'] ?? ''), ['Confirmé','Rapproché'], true)) ge_accounting_post_lines('supplier_payment',$id,'BQ','',[]);
    audit_log('enterprise_save',$collection.' #'.$id);
    redirect_to('index.php?page='.$redirectPage.'&ok=1');
}
function ge_field_input($name,$meta,$value=''){
    $label=e($meta['label'] ?? $name); $type=$meta['type'] ?? 'text'; $value=(string)$value;
    echo '<label>'.$label.'</label>';
    if($type==='textarea'){
        echo '<textarea name="'.e($name).'">'.e($value).'</textarea>';
    }elseif($type==='select'){
        $placeholder = $meta['placeholder'] ?? '';
        echo '<select name="'.e($name).'" class="smart-select">';
        if($placeholder !== '') echo '<option value="">'.e($placeholder).'</option>';
        foreach(($meta['options'] ?? []) as $optValue=>$optLabel){
            if(is_int($optValue)) $optValue=$optLabel;
            $sel=((string)$optValue===$value)?'selected':'';
            echo '<option '.$sel.' value="'.e($optValue).'">'.e($optLabel).'</option>';
        }
        echo '</select>';
    }elseif($type==='date'){
        echo '<input type="date" name="'.e($name).'" value="'.e(substr($value,0,10)).'">';
    }elseif(in_array($type,['number','money','qty'],true)){
        echo '<input type="number" step="0.001" name="'.e($name).'" value="'.e($value).'">';
    }else{
        echo '<input type="text" name="'.e($name).'" value="'.e($value).'">';
    }
}
function ge_simple_manager($title,$collection,$fields,$redirectPage,$refPrefix='',$icon='fa-table'){
    ge_simple_handle_crud($collection,$fields,$redirectPage,$refPrefix);
    $rows=array_reverse(data_read($collection,[]));
    $editId=(int)($_GET['edit'] ?? 0); $edit=$editId ? find_row_by_id($rows,$editId) : [];
    include __DIR__.'/../views/layouts/header.php';
    echo '<div class="erp-page">';
    if(isset($_GET['ok'])) echo '<div class="email-status ok">Enregistré avec succès.</div>';
    if(isset($_GET['deleted'])) echo '<div class="email-status ok">Élément supprimé.</div>';
    echo '<div class="erp-head"><div><h2><i class="fa-solid '.e($icon).'"></i> '.e($title).'</h2><p>Interface simple, mêmes boutons et même style sur toutes les sections.</p></div></div>';
    echo '<section class="panel erp-card"><h3>'.($edit?'Modifier':'Nouveau').'</h3><form method="post" class="erp-form">'.csrf_field().'<input type="hidden" name="action" value="save"><input type="hidden" name="id" value="'.e($edit['id'] ?? 0).'">';
    foreach($fields as $name=>$meta){ ge_field_input($name,$meta,$edit[$name] ?? ($meta['default'] ?? '')); }
    echo '<div class="erp-actions"><button class="btn primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Enregistrer</button>'; if($edit) echo '<a class="btn light" href="index.php?page='.e($redirectPage).'">Annuler</a>'; echo '</div></form></section>';
    echo '<section class="panel erp-card"><h3>Liste</h3><div class="table-wrap"><table class="clean-table erp-table"><thead><tr>';
    foreach($fields as $name=>$meta){ echo '<th>'.e($meta['label'] ?? $name).'</th>'; }
    echo '<th>Actions</th></tr></thead><tbody>';
    foreach($rows as $r){ echo '<tr>'; foreach($fields as $name=>$meta){ $val=$r[$name] ?? ''; if(($meta['type'] ?? '')==='money') $val=money((float)$val); echo '<td>'.e($val).'</td>'; } echo '<td class="nowrap"><a class="mini-action" href="index.php?page='.e($redirectPage).'&edit='.(int)($r['id']??0).'">Modifier</a> <form method="post" style="display:inline" onsubmit="return confirm(\'Supprimer ?\')">'.csrf_field().'<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="'.(int)($r['id']??0).'"><button class="mini-action danger" type="submit">Supprimer</button></form></td></tr>'; }
    if(!$rows) echo '<tr><td colspan="'.(count($fields)+1).'">Aucune donnée pour le moment.</td></tr>';
    echo '</tbody></table></div></section></div>';
    include __DIR__.'/../views/layouts/footer.php';
}
function ge_finance_update_invoice_payment($invoiceId){
    $invoiceId=(int)$invoiceId; if($invoiceId<=0) return;
    $payments=data_read('payments',[]); $paid=0;
    foreach($payments as $p){ if((int)($p['invoice_id']??0)===$invoiceId && !in_array(($p['status']??''),['Annulé','Brouillon'],true)) $paid += (float)($p['amount']??0); }
    $invoices=data_read('invoices',[]);
    foreach($invoices as &$inv){ if((int)($inv['id']??0)===$invoiceId){ $total=(float)($inv['total_ttc']??$inv['amount_ttc']??0); $inv['paid_amount']=$paid; $inv['remaining_amount']=max(0,$total-$paid); if($total>0 && $paid>=$total) $inv['status']='Payée'; elseif($paid>0) $inv['status']='Payée partiellement'; elseif(!in_array((string)($inv['status'] ?? ''), ['Brouillon','Annulée','Abandonnée'], true)) $inv['status']='Impayée'; break; } }
    unset($inv); data_write('invoices',$invoices);
}

function ge_finance_update_supplier_invoice_payment($invoiceId){
    $invoiceId=(int)$invoiceId; if($invoiceId<=0) return;
    $payments=data_read('supplier_payments',[]); $paid=0;
    foreach($payments as $p){ if((int)($p['supplier_invoice_id']??0)===$invoiceId && !in_array(($p['status']??''),['Annulé','Brouillon'],true)) $paid += (float)($p['amount']??0); }
    try{
        $pdo=db(); db_install_enterprise_tables($pdo);
        $stmt=$pdo->prepare('SELECT amount_ttc FROM ge_supplier_invoices WHERE id=? AND tenant_id=?');
        $stmt->execute([$invoiceId, ge_current_tenant_id()]);
        $total=(float)($stmt->fetchColumn() ?: 0);
        $status=$total>0 && $paid>=$total ? 'Payée' : ($paid>0 ? 'Payée partiellement' : 'À payer');
        $paidAt=$status==='Payée' ? date('Y-m-d') : null;
        $up=$pdo->prepare('UPDATE ge_supplier_invoices SET status=?, paid_at=? WHERE id=? AND tenant_id=?');
        $up->execute([$status,$paidAt,$invoiceId, ge_current_tenant_id()]);
    }catch(Throwable $e){ try{ audit_log('supplier_payment_sync_error',$e->getMessage()); }catch(Throwable $ignored){} }
}



function ge_accounting_periods(){
    return data_read('accounting_periods', []);
}
function ge_accounting_period_status_for_date($date){
    $d=substr((string)$date,0,10);
    if($d==='') $d=date('Y-m-d');
    foreach(ge_accounting_periods() as $p){
        $start=substr((string)($p['start_date'] ?? ''),0,10);
        $end=substr((string)($p['end_date'] ?? ''),0,10);
        if($start!=='' && $end!=='' && $d >= $start && $d <= $end){
            return (string)($p['status'] ?? 'Ouverte');
        }
    }
    return 'Ouverte';
}
function ge_accounting_period_is_locked($date=null){
    return in_array(ge_accounting_period_status_for_date($date ?: date('Y-m-d')), ['Clôturée','Verrouillée','Fermée'], true);
}
function ge_accounting_save_period_from_post(){
    $rows=data_read('accounting_periods', []);
    $id=(int)($_POST['id'] ?? 0); $idx=-1;
    foreach($rows as $i=>$r){ if((int)($r['id'] ?? 0)===$id){ $idx=$i; break; } }
    if($id<=0) $id=next_id($rows);
    $status=(string)($_POST['status'] ?? 'Ouverte');
    $row=[
        'id'=>$id,
        'ref'=>trim((string)($_POST['ref'] ?? '')) ?: 'PER'.date('ymd').'-'.str_pad((string)$id,5,'0',STR_PAD_LEFT),
        'name'=>trim((string)($_POST['name'] ?? 'Période comptable')),
        'start_date'=>substr((string)($_POST['start_date'] ?? date('Y-01-01')),0,10),
        'end_date'=>substr((string)($_POST['end_date'] ?? date('Y-12-31')),0,10),
        'status'=>$status,
        'closed_by'=>in_array($status,['Clôturée','Verrouillée','Fermée'],true)?(int)(current_user()['id'] ?? 0):null,
        'closed_at'=>in_array($status,['Clôturée','Verrouillée','Fermée'],true)?date('Y-m-d H:i:s'):'',
        'note'=>trim((string)($_POST['note'] ?? '')),
        'created_at'=>date('Y-m-d H:i:s'),
        'updated_at'=>date('Y-m-d H:i:s'),
    ];
    if($idx>=0) $rows[$idx]=array_merge($rows[$idx], $row); else $rows[]=$row;
    data_write('accounting_periods',$rows,false);
    audit_log('accounting_period_save',$row['ref'].' '.$row['status']);
}

function ge_accounting_add_entry($journal,$label,$debitAccount,$creditAccount,$amount,$sourceType='',$sourceId=0){
    $amount=ge_parse_number($amount); if($amount<=0) return;
    $entryDate=date('Y-m-d');
    if(function_exists('ge_accounting_period_is_locked') && ge_accounting_period_is_locked($entryDate)){ audit_log('accounting_locked_block','Manual entry blocked: '.$entryDate); return; }
    $rows=data_read('accounting_entries',[]); $id=next_id($rows);
    $base=['date'=>$entryDate,'journal'=>$journal,'label'=>$label,'source_type'=>$sourceType,'source_id'=>$sourceId,'status'=>'Brouillon','created_at'=>date('Y-m-d H:i:s')];
    $rows[]=$base+['id'=>$id,'ref'=>'ACC'.date('ymd').'-'.str_pad((string)$id,5,'0',STR_PAD_LEFT),'account'=>$debitAccount,'debit'=>$amount,'credit'=>0];
    $rows[]=$base+['id'=>$id+1,'ref'=>'ACC'.date('ymd').'-'.str_pad((string)($id+1),5,'0',STR_PAD_LEFT),'account'=>$creditAccount,'debit'=>0,'credit'=>$amount];
    data_write('accounting_entries',$rows);
}

/* -------------------------------------------------------------------------
   Connected ERP logic (Dolibarr-like, no installer/migrations)
   ------------------------------------------------------------------------- */
if (!function_exists('ge_table_column_exists')) {
    function ge_table_column_exists(PDO $pdo, string $table, string $column): bool {
        try {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?');
            $stmt->execute([$table, $column]);
            return (int)$stmt->fetchColumn() > 0;
        } catch (Throwable $e) { return false; }
    }
}
if (!function_exists('ge_table_add_column')) {
    function ge_table_add_column(PDO $pdo, string $table, string $column, string $definition): void {
        if (!ge_table_column_exists($pdo, $table, $column)) {
            try { $pdo->exec('ALTER TABLE '.ge_identifier($table).' ADD COLUMN '.ge_identifier($column).' '.$definition); } catch (Throwable $e) {}
        }
    }
}

function ge_erp_ensure_tables(?PDO $pdo=null): void {
    static $doneThisRequest = false;
    if($doneThisRequest) return;
    $pdo = $pdo ?: db();
    db_install_enterprise_tables($pdo);
    ge_table_add_column($pdo, 'ge_purchase_orders', 'due_date', 'DATE NULL');
    ge_table_add_column($pdo, 'ge_purchase_orders', 'amount_tva', 'DECIMAL(15,3) NOT NULL DEFAULT 0');
    ge_table_add_column($pdo, 'ge_purchase_orders', 'amount_ttc', 'DECIMAL(15,3) NOT NULL DEFAULT 0');
    ge_table_add_column($pdo, 'ge_purchase_orders', 'supplier_invoice_id', 'INT NULL');
    ge_table_add_column($pdo, 'ge_purchase_orders', 'supplier_invoice_ref', 'VARCHAR(90) NULL');
    ge_table_add_column($pdo, 'ge_purchase_orders', 'receipt_applied_at', 'DATETIME NULL');
    ge_table_add_column($pdo, 'ge_purchase_orders', 'template', 'VARCHAR(90) NULL');
    ge_table_add_column($pdo, 'ge_supplier_invoices', 'purchase_order_id', 'INT NULL');
    ge_table_add_column($pdo, 'ge_supplier_invoices', 'purchase_order_ref', 'VARCHAR(90) NULL');
    ge_table_add_column($pdo, 'ge_supplier_invoices', 'amount_tva', 'DECIMAL(15,3) NOT NULL DEFAULT 0');
    ge_table_add_column($pdo, 'ge_supplier_invoices', 'note', 'TEXT NULL');
    ge_table_add_column($pdo, 'ge_supplier_invoices', 'paid_at', 'DATE NULL');
    ge_table_add_column($pdo, 'ge_supplier_invoices', 'accounting_posted_at', 'DATETIME NULL');
    ge_table_add_column($pdo, 'ge_supplier_invoices', 'template', 'VARCHAR(90) NULL');
    ge_table_add_column($pdo, 'ge_credit_notes', 'client_id', 'INT NULL');
    ge_table_add_column($pdo, 'ge_credit_notes', 'invoice_ref', 'VARCHAR(90) NULL');
    ge_table_add_column($pdo, 'ge_credit_notes', 'amount_tva', 'DECIMAL(15,3) NOT NULL DEFAULT 0');
    ge_table_add_column($pdo, 'ge_credit_notes', 'note', 'TEXT NULL');
    ge_table_add_column($pdo, 'ge_credit_notes', 'refunded_at', 'DATE NULL');
    ge_table_add_column($pdo, 'ge_credit_notes', 'applied_at', 'DATETIME NULL');
    ge_table_add_column($pdo, 'ge_credit_notes', 'accounting_posted_at', 'DATETIME NULL');
    ge_table_add_column($pdo, 'ge_credit_notes', 'template', 'VARCHAR(90) NULL');

    // Keep approval table compatible with old databases created before approval UI fields existed.
    ge_table_add_column($pdo, 'ge_approval_requests', 'title', 'VARCHAR(190) NULL');
    ge_table_add_column($pdo, 'ge_approval_requests', 'priority', "VARCHAR(40) NOT NULL DEFAULT 'Normale'");
    ge_table_add_column($pdo, 'ge_approval_requests', 'decision_reason', 'TEXT NULL');
    ge_table_add_column($pdo, 'ge_approval_requests', 'decided_by', 'INT NULL');
    ge_table_add_column($pdo, 'ge_approval_requests', 'applied_at', 'DATETIME NULL');
    ge_table_add_column($pdo, 'ge_approval_requests', 'template', 'VARCHAR(90) NULL');

    $pdo->exec("CREATE TABLE IF NOT EXISTS ge_purchase_order_lines (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT NOT NULL DEFAULT 1,
        purchase_order_id INT NOT NULL,
        line_no INT NOT NULL DEFAULT 1,
        product_id INT NULL,
        product_ref VARCHAR(90) NULL,
        product_label VARCHAR(190) NOT NULL,
        warehouse_id INT NULL,
        qty DECIMAL(15,3) NOT NULL DEFAULT 0,
        received_qty DECIMAL(15,3) NOT NULL DEFAULT 0,
        unit VARCHAR(40) NULL,
        pu_ht DECIMAL(15,3) NOT NULL DEFAULT 0,
        tva_rate DECIMAL(8,3) NOT NULL DEFAULT 0,
        total_ht DECIMAL(15,3) NOT NULL DEFAULT 0,
        total_tva DECIMAL(15,3) NOT NULL DEFAULT 0,
        total_ttc DECIMAL(15,3) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_po_line_tenant(tenant_id), KEY idx_po_line_order(purchase_order_id), KEY idx_po_line_product(product_id), KEY idx_po_line_warehouse(warehouse_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS ge_supplier_invoice_lines (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT NOT NULL DEFAULT 1,
        supplier_invoice_id INT NOT NULL,
        purchase_order_line_id INT NULL,
        line_no INT NOT NULL DEFAULT 1,
        product_id INT NULL,
        product_ref VARCHAR(90) NULL,
        product_label VARCHAR(190) NOT NULL,
        qty DECIMAL(15,3) NOT NULL DEFAULT 0,
        unit VARCHAR(40) NULL,
        pu_ht DECIMAL(15,3) NOT NULL DEFAULT 0,
        tva_rate DECIMAL(8,3) NOT NULL DEFAULT 0,
        total_ht DECIMAL(15,3) NOT NULL DEFAULT 0,
        total_tva DECIMAL(15,3) NOT NULL DEFAULT 0,
        total_ttc DECIMAL(15,3) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_si_line_tenant(tenant_id), KEY idx_si_line_invoice(supplier_invoice_id), KEY idx_si_line_product(product_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS ge_approval_rules (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT NOT NULL DEFAULT 1,
        object_type VARCHAR(80) NOT NULL,
        min_amount DECIMAL(15,3) NOT NULL DEFAULT 0,
        active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_rule_tenant(tenant_id), UNIQUE KEY uniq_rule_tenant_type(tenant_id, object_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS ge_bank_movements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT NOT NULL DEFAULT 1,
        bank_account_id INT NULL,
        movement_date DATE NULL,
        label VARCHAR(190) NOT NULL,
        source_type VARCHAR(80) NULL,
        source_id INT NULL,
        debit DECIMAL(15,3) NOT NULL DEFAULT 0,
        credit DECIMAL(15,3) NOT NULL DEFAULT 0,
        reconciled TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_bank_tenant_source(tenant_id, source_type, source_id),
        KEY idx_bank_tenant(tenant_id), KEY idx_bank_account(bank_account_id), KEY idx_bank_date(movement_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    foreach(['ge_purchase_orders','ge_supplier_invoices','ge_credit_notes','ge_approval_requests','ge_purchase_order_lines','ge_supplier_invoice_lines','ge_approval_rules','ge_bank_movements'] as $geTenantTable){
        if(function_exists('ge_ensure_tenant_column')) ge_ensure_tenant_column($pdo, $geTenantTable, false);
    }
    if(function_exists('ge_tenancy_fix_unique_indexes')) ge_tenancy_fix_unique_indexes($pdo);
    $doneThisRequest = true;
}

function ge_erp_product_label_from_id(int $id): array {
    foreach (data_read('products', []) as $p) {
        if ((int)($p['id'] ?? 0) === $id) {
            return [
                'id'=>$id,
                'ref'=>(string)($p['ref'] ?? ''),
                'label'=>(string)($p['label'] ?? $p['name'] ?? ('Produit #'.$id)),
                'buy_price'=>ge_decimal($p['buy_price'] ?? $p['purchase_price'] ?? $p['default_buy_price'] ?? 0),
            ];
        }
    }
    return ['id'=>$id, 'ref'=>'', 'label'=>$id>0 ? 'Produit #'.$id : '', 'buy_price'=>0];
}

function ge_erp_purchase_lines(PDO $pdo, int $orderId): array {
    ge_erp_ensure_tables($pdo);
    $stmt=$pdo->prepare('SELECT * FROM ge_purchase_order_lines WHERE purchase_order_id=? AND tenant_id=? ORDER BY line_no ASC, id ASC');
    $stmt->execute([$orderId, ge_current_tenant_id()]);
    return $stmt->fetchAll() ?: [];
}
function ge_erp_supplier_invoice_lines(PDO $pdo, int $invoiceId): array {
    ge_erp_ensure_tables($pdo);
    $stmt=$pdo->prepare('SELECT * FROM ge_supplier_invoice_lines WHERE supplier_invoice_id=? AND tenant_id=? ORDER BY line_no ASC, id ASC');
    $stmt->execute([$invoiceId, ge_current_tenant_id()]);
    return $stmt->fetchAll() ?: [];
}
function ge_erp_calculate_line(float $qty, float $puHt, float $tvaRate): array {
    $totalHt = round($qty * $puHt, 3);
    $totalTva = round($totalHt * ($tvaRate / 100), 3);
    return [$totalHt, $totalTva, round($totalHt + $totalTva, 3)];
}
function ge_erp_sync_purchase_totals(PDO $pdo, int $orderId): void {
    $stmt=$pdo->prepare('SELECT COALESCE(SUM(total_ht),0), COALESCE(SUM(total_tva),0), COALESCE(SUM(total_ttc),0) FROM ge_purchase_order_lines WHERE purchase_order_id=? AND tenant_id=?');
    $stmt->execute([$orderId, ge_current_tenant_id()]);
    [$ht,$tva,$ttc]=$stmt->fetch(PDO::FETCH_NUM) ?: [0,0,0];
    $up=$pdo->prepare('UPDATE ge_purchase_orders SET amount_ht=?, amount_tva=?, amount_ttc=? WHERE id=? AND tenant_id=?');
    $up->execute([(float)$ht,(float)$tva,(float)$ttc,$orderId, ge_current_tenant_id()]);
}
function ge_erp_sync_supplier_invoice_totals(PDO $pdo, int $invoiceId): void {
    $stmt=$pdo->prepare('SELECT COALESCE(SUM(total_ht),0), COALESCE(SUM(total_tva),0), COALESCE(SUM(total_ttc),0) FROM ge_supplier_invoice_lines WHERE supplier_invoice_id=? AND tenant_id=?');
    $stmt->execute([$invoiceId, ge_current_tenant_id()]);
    [$ht,$tva,$ttc]=$stmt->fetch(PDO::FETCH_NUM) ?: [0,0,0];
    if ((float)$ttc > 0) {
        $up=$pdo->prepare('UPDATE ge_supplier_invoices SET amount_ht=?, amount_tva=?, amount_ttc=? WHERE id=? AND tenant_id=?');
        $up->execute([(float)$ht,(float)$tva,(float)$ttc,$invoiceId, ge_current_tenant_id()]);
    }
}
function ge_erp_save_purchase_lines_from_post(PDO $pdo, int $orderId): void {
    ge_erp_ensure_tables($pdo);
    $ids = (array)($_POST['line_product_id'] ?? []);
    $labels = (array)($_POST['line_label'] ?? []);
    $warehouses = (array)($_POST['line_warehouse_id'] ?? []);
    $qtys = (array)($_POST['line_qty'] ?? []);
    $prices = (array)($_POST['line_pu_ht'] ?? []);
    $tvas = (array)($_POST['line_tva'] ?? []);
    $units = (array)($_POST['line_unit'] ?? []);
    $pdo->prepare('DELETE FROM ge_purchase_order_lines WHERE purchase_order_id=? AND tenant_id=?')->execute([$orderId, ge_current_tenant_id()]);
    $ins=$pdo->prepare('INSERT INTO ge_purchase_order_lines(tenant_id,purchase_order_id,line_no,product_id,product_ref,product_label,warehouse_id,qty,unit,pu_ht,tva_rate,total_ht,total_tva,total_ttc) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $lineNo=1;
    $max=max(count($ids), count($labels), count($qtys));
    for($i=0;$i<$max;$i++){
        $productId=(int)($ids[$i] ?? 0);
        $p=ge_erp_product_label_from_id($productId);
        $label=trim((string)($labels[$i] ?? '')) ?: ($p['label'] ?: 'Article');
        $qty=ge_decimal($qtys[$i] ?? 0); $pu=ge_decimal($prices[$i] ?? 0); $tva=ge_decimal($tvas[$i] ?? 20);
        if($qty<=0 || ($productId<=0 && $label==='')) continue;
        if($pu<=0 && $p['buy_price']>0) $pu=$p['buy_price'];
        [$totalHt,$totalTva,$totalTtc]=ge_erp_calculate_line($qty,$pu,$tva);
        $ins->execute([ge_current_tenant_id(),$orderId,$lineNo++,$productId ?: null,$p['ref'],$label,(int)($warehouses[$i] ?? 0) ?: null,$qty,trim((string)($units[$i] ?? 'u.')) ?: 'u.',$pu,$tva,$totalHt,$totalTva,$totalTtc]);
    }
    ge_erp_sync_purchase_totals($pdo,$orderId);
}
function ge_erp_copy_purchase_lines_to_supplier_invoice(PDO $pdo, int $orderId, int $invoiceId): void {
    ge_erp_ensure_tables($pdo);
    $pdo->prepare('DELETE FROM ge_supplier_invoice_lines WHERE supplier_invoice_id=? AND tenant_id=?')->execute([$invoiceId, ge_current_tenant_id()]);
    $ins=$pdo->prepare('INSERT INTO ge_supplier_invoice_lines(tenant_id,supplier_invoice_id,purchase_order_line_id,line_no,product_id,product_ref,product_label,qty,unit,pu_ht,tva_rate,total_ht,total_tva,total_ttc) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    foreach(ge_erp_purchase_lines($pdo,$orderId) as $l){
        $ins->execute([ge_current_tenant_id(),$invoiceId,(int)$l['id'],(int)$l['line_no'],(int)$l['product_id'] ?: null,$l['product_ref'],$l['product_label'],(float)$l['qty'],$l['unit'],(float)$l['pu_ht'],(float)$l['tva_rate'],(float)$l['total_ht'],(float)$l['total_tva'],(float)$l['total_ttc']]);
    }
    ge_erp_sync_supplier_invoice_totals($pdo,$invoiceId);
}
function ge_erp_normalize_warehouse_stock_map($value): array {
    if (is_string($value) && $value !== '') {
        $decoded = json_decode($value, true);
        if (is_array($decoded)) $value = $decoded;
    }
    $out = [];
    if (is_array($value)) {
        foreach ($value as $k => $v) {
            $wid = (int)$k;
            if ($wid > 0) $out[$wid] = ge_decimal($v);
        }
    }
    return $out;
}

function ge_erp_default_warehouse_id(array $product = [], ?int $preferred = null): int {
    if ($preferred && $preferred > 0) return $preferred;
    $wid = (int)($product['warehouse_id'] ?? 0);
    if ($wid > 0) return $wid;
    $warehouses = data_read('warehouses', []);
    foreach ($warehouses as $w) {
        $id = (int)($w['id'] ?? 0);
        if ($id > 0) return $id;
    }
    return 0;
}

function ge_erp_apply_purchase_receipt(PDO $pdo, int $orderId): void {
    ge_erp_ensure_tables($pdo);
    $orderStmt = $pdo->prepare('SELECT * FROM ge_purchase_orders WHERE id=? AND tenant_id=?');
    $orderStmt->execute([$orderId, ge_current_tenant_id()]);
    $order = $orderStmt->fetch();

    // Very important: a purchase receipt must affect stock only once.
    if (!$order || !empty($order['receipt_applied_at'])) return;

    $lines = ge_erp_purchase_lines($pdo, $orderId);
    if (!$lines) return;

    $products = data_read('products', []);
    $movements = data_read('warehouse_movements', []);
    $nextMove = next_id($movements);
    $now = date('Y-m-d H:i:s');
    $appliedLines = 0;

    foreach ($lines as $l) {
        $qty = ge_decimal($l['qty'] ?? 0);
        if ($qty <= 0) continue;

        $pid = (int)($l['product_id'] ?? 0);
        if ($pid <= 0) continue; // Services/free text lines do not change stock.

        $lineWarehouseId = (int)($l['warehouse_id'] ?? 0);
        $productFound = false;
        $warehouseId = $lineWarehouseId;

        foreach ($products as &$p) {
            if ((int)($p['id'] ?? 0) !== $pid) continue;

            $productFound = true;
            $warehouseId = ge_erp_default_warehouse_id($p, $warehouseId);

            $oldPhysical = ge_decimal($p['physical_stock'] ?? $p['stock'] ?? 0);
            $oldVirtual = ge_decimal($p['virtual_stock'] ?? $oldPhysical);

            $p['physical_stock'] = $oldPhysical + $qty;
            $p['stock'] = $p['physical_stock'];
            $p['virtual_stock'] = $oldVirtual + $qty;

            if ($warehouseId > 0) {
                $stockMap = ge_erp_normalize_warehouse_stock_map($p['warehouse_stock'] ?? []);
                if (!$stockMap && !empty($p['warehouse_id'])) {
                    $stockMap[(int)$p['warehouse_id']] = $oldPhysical;
                }
                $stockMap[$warehouseId] = ge_decimal($stockMap[$warehouseId] ?? 0) + $qty;
                $p['warehouse_stock'] = $stockMap;
                if (empty($p['warehouse_id'])) $p['warehouse_id'] = $warehouseId;
            }
            break;
        }
        unset($p);

        if (!$productFound) continue;

        $movements[] = [
            'id' => $nextMove++,
            'product_id' => $pid,
            'product_ref' => $l['product_ref'] ?? '',
            'product_label' => $l['product_label'] ?? '',
            'warehouse_id' => $warehouseId ?: null,
            'qty' => $qty,
            'type' => 'Entrée fournisseur',
            'movement_type' => 'purchase_receipt',
            'source_type' => 'purchase_order',
            'source_id' => $orderId,
            'date' => $now,
            'created_at' => $now,
            'note' => 'Réception automatique depuis '.($order['ref'] ?? ('#'.$orderId))
        ];
        $appliedLines++;
    }

    if ($appliedLines <= 0) return;

    data_write_batch(['products' => $products, 'warehouse_movements' => $movements], false);

    // Keep order lines coherent: the "Reçue" quantity shown in the order detail must match what was applied to stock.
    $pdo->prepare('UPDATE ge_purchase_order_lines SET received_qty=qty WHERE purchase_order_id=? AND tenant_id=?')->execute([$orderId, ge_current_tenant_id()]);

    $pdo->prepare('UPDATE ge_purchase_orders SET receipt_applied_at=NOW(), status=? WHERE id=? AND tenant_id=?')->execute(['Reçue', $orderId, ge_current_tenant_id()]);
    audit_log('purchase_receipt_applied', 'Order '.$orderId.' -> stock updated: '.$appliedLines.' line(s)');
}
function ge_accounting_post_lines(string $sourceType, int $sourceId, string $journal, string $label, array $lines): void {
    $entryDate=date('Y-m-d');
    if(function_exists('ge_accounting_period_is_locked') && ge_accounting_period_is_locked($entryDate)){ audit_log('accounting_locked_block', $sourceType.' #'.$sourceId.' blocked for '.$entryDate); return; }
    $rows=data_read('accounting_entries', []);
    $rows=array_values(array_filter($rows, fn($r)=>!((string)($r['source_type'] ?? '')===$sourceType && (int)($r['source_id'] ?? 0)===$sourceId)));
    $next=next_id($rows);
    foreach($lines as $line){
        $amount=ge_decimal($line['amount'] ?? 0); if($amount<=0) continue;
        $rows[]=[
            'id'=>$next,
            'ref'=>'ACC'.date('ymd').'-'.str_pad((string)$next,5,'0',STR_PAD_LEFT),
            'date'=>$entryDate, 'journal'=>$journal, 'account'=>(string)($line['account'] ?? ''), 'label'=>$label,
            'debit'=>ge_decimal($line['debit'] ?? 0), 'credit'=>ge_decimal($line['credit'] ?? 0),
            'source_type'=>$sourceType, 'source_id'=>$sourceId, 'status'=>'Validée', 'created_at'=>date('Y-m-d H:i:s')
        ];
        $next++;
    }
    data_write('accounting_entries', $rows, false);
}
function ge_erp_post_supplier_invoice_accounting(PDO $pdo, int $invoiceId): void {
    $stmt=$pdo->prepare('SELECT * FROM ge_supplier_invoices WHERE id=? AND tenant_id=?'); $stmt->execute([$invoiceId, ge_current_tenant_id()]); $inv=$stmt->fetch();
    if(!$inv || in_array((string)($inv['status'] ?? ''), ['Brouillon','Annulée'], true)) return;
    $ht=(float)($inv['amount_ht'] ?? 0); $tva=(float)($inv['amount_tva'] ?? 0); $ttc=(float)($inv['amount_ttc'] ?? 0); if($ttc<=0) $ttc=$ht+$tva;
    ge_accounting_post_lines('supplier_invoice',$invoiceId,'AC','Facture fournisseur '.($inv['ref'] ?? $invoiceId),[
        ['account'=>'611000','amount'=>$ht,'debit'=>$ht,'credit'=>0],
        ['account'=>'345520','amount'=>$tva,'debit'=>$tva,'credit'=>0],
        ['account'=>'401000','amount'=>$ttc,'debit'=>0,'credit'=>$ttc],
    ]);
    $pdo->prepare('UPDATE ge_supplier_invoices SET accounting_posted_at=NOW() WHERE id=? AND tenant_id=?')->execute([$invoiceId, ge_current_tenant_id()]);
}
function ge_erp_post_credit_note_accounting(PDO $pdo, int $creditId): void {
    $stmt=$pdo->prepare('SELECT * FROM ge_credit_notes WHERE id=? AND tenant_id=?'); $stmt->execute([$creditId, ge_current_tenant_id()]); $cn=$stmt->fetch();
    if(!$cn || in_array((string)($cn['status'] ?? ''), ['Brouillon','Annulé'], true)) return;
    $ht=(float)($cn['amount_ht'] ?? 0); $tva=(float)($cn['amount_tva'] ?? 0); $ttc=(float)($cn['amount_ttc'] ?? 0); if($ttc<=0) $ttc=$ht+$tva;
    ge_accounting_post_lines('credit_note',$creditId,'VT','Avoir client '.($cn['ref'] ?? $creditId),[
        ['account'=>'709000','amount'=>$ht,'debit'=>$ht,'credit'=>0],
        ['account'=>'445500','amount'=>$tva,'debit'=>$tva,'credit'=>0],
        ['account'=>'411000','amount'=>$ttc,'debit'=>0,'credit'=>$ttc],
    ]);
    $pdo->prepare('UPDATE ge_credit_notes SET accounting_posted_at=NOW() WHERE id=? AND tenant_id=?')->execute([$creditId, ge_current_tenant_id()]);
}
function ge_erp_recalculate_invoice_balance(int $invoiceId): void {
    $invoiceId=(int)$invoiceId; if($invoiceId<=0) return;
    $invoices=data_read('invoices', []); $changed=false;
    foreach($invoices as &$inv){
        if((int)($inv['id'] ?? 0)!==$invoiceId) continue;
        $total=ge_decimal($inv['total_ttc'] ?? $inv['amount_ttc'] ?? 0);
        $paid=0; foreach(data_read('payments', []) as $p){ if((int)($p['invoice_id'] ?? 0)===$invoiceId && !in_array((string)($p['status'] ?? ''), ['Annulé','Brouillon'], true)) $paid+=ge_decimal($p['amount'] ?? 0); }
        $credit=0; try{ $pdo=db(); ge_erp_ensure_tables($pdo); $stmt=$pdo->prepare("SELECT COALESCE(SUM(amount_ttc),0) FROM ge_credit_notes WHERE invoice_id=? AND tenant_id=? AND status IN ('Validé','Utilisé','Remboursé','Appliqué')"); $stmt->execute([$invoiceId, ge_current_tenant_id()]); $credit=(float)$stmt->fetchColumn(); }catch(Throwable $e){}
        $inv['paid_amount']=$paid; $inv['credit_amount']=$credit; $inv['remaining_amount']=max(0,$total-$paid-$credit);
        if($total>0 && ($paid+$credit)>=$total) $inv['status']='Payée'; elseif($paid>0 || $credit>0) $inv['status']='Payée partiellement';
        $changed=true; break;
    }
    unset($inv); if($changed) data_write('invoices',$invoices,false);
}
function ge_erp_apply_credit_note(PDO $pdo, int $creditId): void {
    $stmt=$pdo->prepare('SELECT * FROM ge_credit_notes WHERE id=? AND tenant_id=?'); $stmt->execute([$creditId, ge_current_tenant_id()]); $cn=$stmt->fetch();
    if(!$cn) return;
    if(in_array((string)($cn['status'] ?? ''), ['Validé','Utilisé','Remboursé','Appliqué'], true)){
        ge_erp_recalculate_invoice_balance((int)($cn['invoice_id'] ?? 0));
        ge_erp_post_credit_note_accounting($pdo,$creditId);
        if(empty($cn['applied_at'])) $pdo->prepare('UPDATE ge_credit_notes SET applied_at=NOW() WHERE id=? AND tenant_id=?')->execute([$creditId, ge_current_tenant_id()]);
    }
}
function ge_erp_make_bank_movement(PDO $pdo, string $sourceType, int $sourceId, ?int $bankAccountId, string $label, float $debit, float $credit): void {
    ge_erp_ensure_tables($pdo);
    try{
        $stmt=$pdo->prepare('INSERT INTO ge_bank_movements(tenant_id,bank_account_id,movement_date,label,source_type,source_id,debit,credit) VALUES(?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE bank_account_id=VALUES(bank_account_id), movement_date=VALUES(movement_date), label=VALUES(label), debit=VALUES(debit), credit=VALUES(credit)');
        $stmt->execute([ge_current_tenant_id(),$bankAccountId,date('Y-m-d'),$label,$sourceType,$sourceId,$debit,$credit]);
    }catch(Throwable $e){}
}
function ge_erp_update_bank_balances(): void {
    $accounts=data_read('bank_accounts', []); if(!$accounts) return;
    try{ $pdo=db(); ge_erp_ensure_tables($pdo); }catch(Throwable $e){ return; }
    foreach($accounts as &$a){
        $id=(int)($a['id'] ?? 0); if($id<=0) continue;
        $stmt=$pdo->prepare('SELECT COALESCE(SUM(debit-credit),0) FROM ge_bank_movements WHERE bank_account_id=? AND tenant_id=?'); $stmt->execute([$id, ge_current_tenant_id()]);
        $a['current_balance']=ge_decimal($a['opening_balance'] ?? 0)+(float)$stmt->fetchColumn();
    }
    unset($a); data_write('bank_accounts',$accounts,false);
}
function ge_erp_sync_finance_payment_side_effects(string $collection, array $row): void {
    try{ $pdo=db(); ge_erp_ensure_tables($pdo); }catch(Throwable $e){ return; }
    $status=(string)($row['status'] ?? 'Brouillon');
    $active=in_array($status, ['Confirmé','Rapproché'], true);
    $reconciled=$status==='Rapproché';
    if($collection==='payments'){
        if($active){
            ge_erp_make_bank_movement($pdo,'payment',(int)($row['id'] ?? 0),(int)($row['bank_account_id'] ?? 0) ?: null,'Paiement client '.($row['ref'] ?? ''),ge_decimal($row['amount'] ?? 0),0);
            $stmt=$pdo->prepare('UPDATE ge_bank_movements SET reconciled=? WHERE source_type=? AND source_id=? AND tenant_id=?');
            $stmt->execute([$reconciled?1:0,'payment',(int)($row['id'] ?? 0), ge_current_tenant_id()]);
        } else {
            ge_erp_remove_bank_movement('payment',(int)($row['id'] ?? 0));
        }
    }
    if($collection==='supplier_payments'){
        if($active){
            ge_erp_make_bank_movement($pdo,'supplier_payment',(int)($row['id'] ?? 0),(int)($row['bank_account_id'] ?? 0) ?: null,'Paiement fournisseur '.($row['ref'] ?? ''),0,ge_decimal($row['amount'] ?? 0));
            $stmt=$pdo->prepare('UPDATE ge_bank_movements SET reconciled=? WHERE source_type=? AND source_id=? AND tenant_id=?');
            $stmt->execute([$reconciled?1:0,'supplier_payment',(int)($row['id'] ?? 0), ge_current_tenant_id()]);
        } else {
            ge_erp_remove_bank_movement('supplier_payment',(int)($row['id'] ?? 0));
        }
    }
    ge_erp_update_bank_balances();
}
function ge_approval_min_amount(string $type): float {
    $defaults=['devis'=>50000,'commande'=>50000,'facture'=>50000,'achat'=>20000,'avoir'=>10000,'stock'=>0];
    try{
        $pdo=db(); ge_erp_ensure_tables($pdo);
        $stmt=$pdo->prepare('SELECT min_amount FROM ge_approval_rules WHERE object_type=? AND active=1 AND tenant_id=? LIMIT 1'); $stmt->execute([$type, ge_current_tenant_id()]);
        $v=$stmt->fetchColumn(); if($v!==false) return (float)$v;
    }catch(Throwable $e){}
    return (float)($defaults[$type] ?? 0);
}
function ge_erp_request_approval(PDO $pdo, string $type, int $objectId, string $objectRef, float $amount, string $title=''): void {
    if($objectId<=0) return;
    $min=ge_approval_min_amount($type); if($min<=0 || $amount<$min) return;
    ge_erp_ensure_tables($pdo);
    $stmt=$pdo->prepare("SELECT id FROM ge_approval_requests WHERE object_type=? AND object_id=? AND tenant_id=? AND status='En attente' LIMIT 1");
    $stmt->execute([$type,$objectId, ge_current_tenant_id()]); if($stmt->fetchColumn()) return;
    $title=$title ?: 'Validation '.$type.' '.$objectRef;
    $ins=$pdo->prepare('INSERT INTO ge_approval_requests(tenant_id,title,object_type,object_id,object_ref,requested_by,status,amount,priority,reason) VALUES(?,?,?,?,?,?,?,?,?,?)');
    $ins->execute([ge_current_tenant_id(),$title,$type,$objectId,$objectRef,(int)(current_user()['id'] ?? 0) ?: null,'En attente',$amount,'Normale','Validation automatique: montant supérieur à '.money($min).' MAD']);
    audit_log('approval_auto_created', $title.' | '.money($amount));
}
function ge_erp_auto_approvals_from_rows(string $collection, array $rows): void {
    $map=['quotes'=>'devis','orders'=>'commande','invoices'=>'facture']; if(!isset($map[$collection])) return;
    try{ $pdo=db(); ge_erp_ensure_tables($pdo); }catch(Throwable $e){ return; }
    foreach($rows as $r){
        $id=(int)($r['id'] ?? 0); if($id<=0) continue;
        $amount=ge_decimal($r['total_ttc'] ?? $r['amount_ttc'] ?? $r['amount_ht'] ?? $r['total_ht'] ?? 0);
        ge_erp_request_approval($pdo,$map[$collection],$id,(string)($r['ref'] ?? '#'.$id),$amount);
    }
}
function ge_route_module_key(string $page): ?string {
    $map=[
        'products'=>'products','product_'=>'products','tiers'=>'tiers','tier'=>'tiers','clients'=>'tiers','client_'=>'tiers','supplier_payments'=>'finance','supplier_payment_'=>'finance','supplier_invoices'=>'purchases','supplier_invoice_'=>'purchases','suppliers'=>'tiers','supplier_'=>'tiers',
        'quotes'=>'sales','quote_'=>'sales','orders'=>'sales','order_'=>'sales','invoices'=>'sales','invoice_'=>'sales','expeditions'=>'sales','expedition_'=>'sales','receptions'=>'purchases','reception_'=>'purchases',
        'purchases'=>'purchases','purchase_'=>'purchases','supplier_invoice_'=>'purchases','credit_notes'=>'sales','credit_note_'=>'sales','approvals'=>'settings','approval_'=>'settings',
        'warehouses'=>'stock','warehouse_'=>'stock','bank_accounts'=>'finance','bank_account_'=>'finance','payment_modes'=>'finance','payments'=>'finance','payment_'=>'finance','finance_payment_'=>'finance','supplier_payments'=>'finance','supplier_payment_'=>'finance','accounting'=>'accounting',
        'documents'=>'documents','signatures'=>'signatures','signature_'=>'signatures','projects'=>'projects','agenda'=>'agenda','pos'=>'pos','manufacturing'=>'manufacturing','api'=>'api','api_'=>'api','reports'=>'reports','analytics'=>'analytics','users'=>'settings','user_'=>'settings','custom_fields'=>'settings','workflow'=>'settings','import_export'=>'reports'
    ];
    foreach($map as $prefix=>$module){ if($page===$prefix || str_starts_with($page,$prefix)) return $module; }
    return null;
}

function ge_erp_mark_supplier_invoice_paid(PDO $pdo, int $invoiceId): void {
    ge_erp_ensure_tables($pdo);
    $stmt=$pdo->prepare('SELECT * FROM ge_supplier_invoices WHERE id=? AND tenant_id=?');
    $stmt->execute([$invoiceId, ge_current_tenant_id()]);
    $inv=$stmt->fetch();
    if(!$inv) return;
    $existing=data_read('supplier_payments', []);
    foreach($existing as $p){ if((int)($p['supplier_invoice_id'] ?? 0)===$invoiceId && !in_array((string)($p['status'] ?? ''), ['Annulé','Brouillon'], true)) return; }
    $id=next_id($existing);
    $existing[]=[
        'id'=>$id,
        'ref'=>'PF'.date('ymd').'-'.str_pad((string)$id,5,'0',STR_PAD_LEFT),
        'supplier_invoice_id'=>$invoiceId,
        'supplier_invoice_ref'=>$inv['ref'] ?? '',
        'supplier_id'=>(int)($inv['supplier_id'] ?? 0) ?: null,
        'supplier_name'=>$inv['supplier_name'] ?? '',
        'date'=>date('Y-m-d'),
        'amount'=>ge_decimal($inv['amount_ttc'] ?? 0),
        'mode'=>'Manuel',
        'status'=>'Confirmé',
        'note'=>'Paiement créé automatiquement depuis facture fournisseur payée',
        'created_at'=>date('Y-m-d H:i:s'),
    ];
    data_write('supplier_payments', $existing, false);
    if(function_exists('ge_finance_update_supplier_invoice_payment')) ge_finance_update_supplier_invoice_payment($invoiceId);
    ge_erp_sync_finance_payment_side_effects('supplier_payments', end($existing));
}

function ge_erp_post_customer_invoice_accounting(int $invoiceId): void {
    $inv=find_row_by_id(data_read('invoices', []), $invoiceId);
    if(!$inv || in_array((string)($inv['status'] ?? ''), ['Brouillon','Annulée','Abandonnée'], true)) return;
    $ht=ge_decimal($inv['total_ht'] ?? $inv['amount_ht'] ?? 0);
    $ttc=ge_decimal($inv['total_ttc'] ?? $inv['amount_ttc'] ?? $ht);
    $tva=max(0,$ttc-$ht);
    ge_accounting_post_lines('invoice',$invoiceId,'VT','Facture client '.($inv['ref'] ?? $invoiceId),[
        ['account'=>'411000','amount'=>$ttc,'debit'=>$ttc,'credit'=>0],
        ['account'=>'711000','amount'=>$ht,'debit'=>0,'credit'=>$ht],
        ['account'=>'445500','amount'=>$tva,'debit'=>0,'credit'=>$tva],
    ]);
}
function ge_erp_mark_customer_invoice_paid(int $invoiceId): void {
    $inv=find_row_by_id(data_read('invoices', []), $invoiceId); if(!$inv) return;
    $existing=data_read('payments', []);
    foreach($existing as $p){ if((int)($p['invoice_id'] ?? 0)===$invoiceId && !in_array((string)($p['status'] ?? ''), ['Annulé','Brouillon'], true)) return; }
    $id=next_id($existing);
    $amount=ge_decimal($inv['remaining_amount'] ?? $inv['total_ttc'] ?? $inv['amount_ttc'] ?? 0);
    if($amount<=0) $amount=ge_decimal($inv['total_ttc'] ?? $inv['amount_ttc'] ?? 0);
    $row=[
        'id'=>$id,'ref'=>'PC'.date('ymd').'-'.str_pad((string)$id,5,'0',STR_PAD_LEFT),
        'invoice_id'=>$invoiceId,'invoice_ref'=>$inv['ref'] ?? '', 'client_id'=>(int)($inv['client_id'] ?? $inv['tier_id'] ?? 0) ?: null,
        'client_name'=>$inv['client_name'] ?? $inv['tier_name'] ?? $inv['client'] ?? '', 'date'=>date('Y-m-d'), 'amount'=>$amount,
        'mode'=>'Manuel','status'=>'Confirmé','note'=>'Paiement créé automatiquement depuis facture payée','created_at'=>date('Y-m-d H:i:s')
    ];
    $existing[]=$row; data_write('payments',$existing,false);
    ge_finance_update_invoice_payment($invoiceId);
    ge_erp_sync_finance_payment_side_effects('payments',$row);
}

/* -------------------------------------------------------------------------
   Final connected workflow helpers for Finance / Accounting / Advanced Stock / Documents
   ------------------------------------------------------------------------- */
if(!function_exists('ge_erp_row_label')){
function ge_erp_row_label(array $row, array $keys=['ref','name','label']): string {
    $parts=[];
    foreach($keys as $k){ $v=trim((string)($row[$k] ?? '')); if($v!=='' && !in_array($v,$parts,true)) $parts[]=$v; }
    return $parts ? implode(' — ', $parts) : ('#'.(int)($row['id'] ?? 0));
}}
if(!function_exists('ge_erp_options')){
function ge_erp_options(string $collection, array $labelKeys=['ref','name','label'], bool $activeOnly=false): array {
    $out=[];
    foreach(data_read($collection, []) as $row){
        if(!is_array($row)) continue;
        $id=(int)($row['id'] ?? 0); if($id<=0) continue;
        if($activeOnly && isset($row['status']) && !in_array((string)$row['status'], ['Actif','Validé','Validée','Disponible',''], true)) continue;
        $out[$id]=ge_erp_row_label($row,$labelKeys);
    }
    return $out;
}}
if(!function_exists('ge_erp_find_collection_row')){
function ge_erp_find_collection_row(string $collection, int $id): ?array {
    if($id<=0) return null;
    foreach(data_read($collection, []) as $row){ if((int)($row['id'] ?? 0)===$id) return $row; }
    return null;
}}
if(!function_exists('ge_erp_bank_account_options')){
function ge_erp_bank_account_options(): array { return ge_erp_options('bank_accounts',['ref','name','bank_name'], false); }
}
if(!function_exists('ge_erp_invoice_options')){
function ge_erp_invoice_options(): array {
    $out=[];
    foreach(data_read('invoices', []) as $r){
        $id=(int)($r['id'] ?? 0); if($id<=0) continue;
        $total=ge_decimal($r['total_ttc'] ?? $r['amount_ttc'] ?? 0);
        $remain=ge_decimal($r['remaining_amount'] ?? $total);
        $client=$r['client_name'] ?? $r['client'] ?? $r['tier_name'] ?? '';
        $out[$id]=trim(($r['ref'] ?? ('#'.$id)).' — '.$client.' — Reste '.money($remain));
    }
    return $out;
}}
if(!function_exists('ge_erp_supplier_invoice_options')){
function ge_erp_supplier_invoice_options(): array {
    $out=[];
    try{ $pdo=db(); ge_erp_ensure_tables($pdo); $rows=$pdo->query('SELECT * FROM ge_supplier_invoices WHERE tenant_id='.ge_current_tenant_id().' ORDER BY id ASC')->fetchAll(); }
    catch(Throwable $e){ $rows=[]; }
    foreach($rows as $r){
        $id=(int)($r['id'] ?? 0); if($id<=0) continue;
        $out[$id]=trim(($r['ref'] ?? ('#'.$id)).' — '.($r['supplier_name'] ?? '').' — '.money($r['amount_ttc'] ?? 0));
    }
    return $out;
}}
if(!function_exists('ge_erp_product_options')){
function ge_erp_product_options(): array { return ge_erp_options('products',['ref','label','name'], false); }
}
if(!function_exists('ge_erp_warehouse_options')){
function ge_erp_warehouse_options(): array { return ge_erp_options('warehouses',['ref','name'], false); }
}
if(!function_exists('ge_erp_object_type_options')){
function ge_erp_object_type_options(): array {
    return [
        'client'=>'Client','supplier'=>'Fournisseur','product'=>'Produit','quote'=>'Devis','order'=>'Commande client','invoice'=>'Facture client',
        'purchase_order'=>'Bon de commande fournisseur','supplier_invoice'=>'Facture fournisseur','credit_note'=>'Avoir client','project'=>'Projet'
    ];
}}
if(!function_exists('ge_erp_object_url')){
function ge_erp_object_url(string $type, int $id): string {
    $map=[
        'client'=>'tiers_show','supplier'=>'tiers_show','tier'=>'tiers_show','product'=>'product_show','quote'=>'quote_show','order'=>'order_show','invoice'=>'invoice_show',
        'purchase_order'=>'purchase_order_show','supplier_invoice'=>'supplier_invoice_show','credit_note'=>'credit_note_show','project'=>'projects','stock'=>'warehouses'
    ];
    $page=$map[$type] ?? '';
    if($page==='') return '#';
    if($page==='projects' || $page==='warehouses') return 'index.php?page='.$page;
    return 'index.php?page='.$page.'&id='.$id;
}}
if(!function_exists('ge_erp_document_target_options')){
function ge_erp_document_target_options(): array {
    $options=[];
    foreach(data_read('clients', []) as $r){ $id=(int)($r['id']??0); if($id) $options['client:'.$id]='Client — '.ge_erp_row_label($r,['ref','name']); }
    foreach(data_read('suppliers', []) as $r){ $id=(int)($r['id']??0); if($id) $options['supplier:'.$id]='Fournisseur — '.ge_erp_row_label($r,['ref','name']); }
    foreach(data_read('products', []) as $r){ $id=(int)($r['id']??0); if($id) $options['product:'.$id]='Produit — '.ge_erp_row_label($r,['ref','label','name']); }
    foreach(data_read('quotes', []) as $r){ $id=(int)($r['id']??0); if($id) $options['quote:'.$id]='Devis — '.($r['ref']??('#'.$id)).' — '.($r['client']??''); }
    foreach(data_read('orders', []) as $r){ $id=(int)($r['id']??0); if($id) $options['order:'.$id]='Commande — '.($r['ref']??('#'.$id)).' — '.($r['client']??''); }
    foreach(data_read('invoices', []) as $r){ $id=(int)($r['id']??0); if($id) $options['invoice:'.$id]='Facture — '.($r['ref']??('#'.$id)).' — '.($r['client']??''); }
    try{ $pdo=db(); ge_erp_ensure_tables($pdo); foreach($pdo->query('SELECT id,ref,supplier_name FROM ge_purchase_orders WHERE tenant_id='.ge_current_tenant_id().' ORDER BY id ASC') as $r){ $options['purchase_order:'.(int)$r['id']]='BC fournisseur — '.$r['ref'].' — '.$r['supplier_name']; } foreach($pdo->query('SELECT id,ref,supplier_name FROM ge_supplier_invoices WHERE tenant_id='.ge_current_tenant_id().' ORDER BY id ASC') as $r){ $options['supplier_invoice:'.(int)$r['id']]='Facture fournisseur — '.$r['ref'].' — '.$r['supplier_name']; } }catch(Throwable $e){}
    return $options;
}}
if(!function_exists('ge_erp_find_product_for_adjust')){
function ge_erp_find_product_for_adjust(array &$products, int $productId): ?array {
    foreach($products as $i=>$p){ if((int)($p['id'] ?? 0)===$productId) return ['index'=>$i,'row'=>$p]; }
    return null;
}}
if(!function_exists('ge_erp_movement_exists')){
function ge_erp_movement_exists(string $sourceType, int $sourceId, string $movementType=''): bool {
    foreach(data_read('warehouse_movements', []) as $m){
        if((string)($m['source_type'] ?? '')===$sourceType && (int)($m['source_id'] ?? 0)===$sourceId){
            if($movementType==='' || (string)($m['movement_type'] ?? '')===$movementType) return true;
        }
    }
    return false;
}}
if(!function_exists('ge_erp_adjust_product_stock')){
function ge_erp_adjust_product_stock(int $productId, float $delta, int $warehouseId, string $movementLabel, string $movementType, string $sourceType, int $sourceId, string $note=''): bool {
    if($productId<=0 || abs($delta) < 0.000001) return false;
    if($sourceType!=='' && $sourceId>0 && ge_erp_movement_exists($sourceType,$sourceId,$movementType)) return false;
    $products=data_read('products', []); $found=ge_erp_find_product_for_adjust($products,$productId); if(!$found) return false;
    $idx=$found['index']; $p=$found['row'];
    $warehouseId=ge_erp_default_warehouse_id($p,$warehouseId);
    $oldPhysical=ge_decimal($p['physical_stock'] ?? $p['stock'] ?? 0);
    $oldVirtual=ge_decimal($p['virtual_stock'] ?? $oldPhysical);
    $products[$idx]['physical_stock']=$oldPhysical+$delta;
    $products[$idx]['stock']=$products[$idx]['physical_stock'];
    $products[$idx]['virtual_stock']=$oldVirtual+$delta;
    if($warehouseId>0){
        $map=ge_erp_normalize_warehouse_stock_map($p['warehouse_stock'] ?? []);
        if(!$map && !empty($p['warehouse_id'])) $map[(int)$p['warehouse_id']]=$oldPhysical;
        $map[$warehouseId]=ge_decimal($map[$warehouseId] ?? 0)+$delta;
        $products[$idx]['warehouse_stock']=$map;
        if(empty($products[$idx]['warehouse_id'])) $products[$idx]['warehouse_id']=$warehouseId;
    }
    $movements=data_read('warehouse_movements', []); $mid=next_id($movements);
    $movements[]=[
        'id'=>$mid,'warehouse_id'=>$warehouseId ?: null,'warehouse_name'=>ge_erp_get_warehouse_name($warehouseId),
        'product_id'=>$productId,'product_ref'=>(string)($p['ref'] ?? ''),'product_label'=>(string)($p['label'] ?? $p['name'] ?? ''),
        'qty'=>$delta,'type'=>$movementLabel,'movement_type'=>$movementType,'source_type'=>$sourceType,'source_id'=>$sourceId,
        'note'=>$note,'date'=>date('Y-m-d H:i:s'),'created_at'=>date('Y-m-d H:i:s')
    ];
    data_write_batch(['products'=>$products,'warehouse_movements'=>$movements], false);
    return true;
}}
if(!function_exists('ge_erp_get_warehouse_name')){
function ge_erp_get_warehouse_name(int $warehouseId): string {
    foreach(data_read('warehouses', []) as $w){ if((int)($w['id'] ?? 0)===$warehouseId) return (string)($w['name'] ?? $w['ref'] ?? ''); }
    return '';
}}
if(!function_exists('ge_erp_product_stock_at_warehouse')){
function ge_erp_product_stock_at_warehouse(int $productId, int $warehouseId=0): float {
    foreach(data_read('products', []) as $p){
        if((int)($p['id'] ?? 0)!==$productId) continue;
        if($warehouseId>0){ $map=ge_erp_normalize_warehouse_stock_map($p['warehouse_stock'] ?? []); if(isset($map[$warehouseId])) return ge_decimal($map[$warehouseId]); }
        return ge_decimal($p['physical_stock'] ?? $p['stock'] ?? 0);
    }
    return 0.0;
}}
if(!function_exists('ge_stock_lot_apply_to_stock')){
function ge_stock_lot_apply_to_stock(int $lotId): bool {
    $lots=data_read('stock_lots', []); $changed=false; $ok=false;
    foreach($lots as &$lot){
        if((int)($lot['id'] ?? 0)!==$lotId) continue;
        if(!empty($lot['stock_applied_at'])) return false;
        $pid=(int)($lot['product_id'] ?? 0); $qty=ge_decimal($lot['qty'] ?? 0); $wid=(int)($lot['warehouse_id'] ?? 0);
        if($pid>0 && $qty>0){
            $ok=ge_erp_adjust_product_stock($pid,$qty,$wid,'Entrée lot','stock_lot','stock_lot',$lotId,'Ajout stock depuis lot '.($lot['lot_number'] ?? ''));
            if($ok){ $lot['stock_applied_at']=date('Y-m-d H:i:s'); $lot['status']=$lot['status'] ?: 'Disponible'; $changed=true; }
        }
        break;
    }
    unset($lot);
    if($changed) data_write('stock_lots',$lots,false);
    return $ok;
}}
if(!function_exists('ge_inventory_apply_to_stock')){
function ge_inventory_apply_to_stock(int $inventoryId): bool {
    $inventories=data_read('inventories', []); $invIdx=-1; $inventory=null;
    foreach($inventories as $i=>$inv){ if((int)($inv['id'] ?? 0)===$inventoryId){ $invIdx=$i; $inventory=$inv; break; } }
    if(!$inventory || !empty($inventory['applied_at'])) return false;
    $lines=data_read('inventory_lines', []); $applied=0; $now=date('Y-m-d H:i:s');
    foreach($lines as &$line){
        if((int)($line['inventory_id'] ?? 0)!==$inventoryId || !empty($line['stock_applied_at'])) continue;
        $pid=(int)($line['product_id'] ?? 0); if($pid<=0) continue;
        $wid=(int)($inventory['warehouse_id'] ?? 0);
        $system=ge_decimal($line['system_qty'] ?? ge_erp_product_stock_at_warehouse($pid,$wid));
        $counted=ge_decimal($line['counted_qty'] ?? 0);
        $diff=$counted-$system;
        $line['system_qty']=$system; $line['difference_qty']=$diff;
        if(abs($diff)>0.000001){
            if(ge_erp_adjust_product_stock($pid,$diff,$wid,'Correction inventaire','inventory_adjustment','inventory_line',(int)($line['id'] ?? 0),'Inventaire '.($inventory['ref'] ?? '#'.$inventoryId))) $applied++;
        }
        $line['stock_applied_at']=$now;
    }
    unset($line);
    if($applied>=0){
        $inventories[$invIdx]['status']='Appliqué'; $inventories[$invIdx]['applied_at']=$now;
        data_write_batch(['inventory_lines'=>$lines,'inventories'=>$inventories], false);
        return true;
    }
    return false;
}}
if(!function_exists('ge_finance_bank_movements')){
function ge_finance_bank_movements(int $bankAccountId=0, int $limit=200): array {
    try{ $pdo=db(); ge_erp_ensure_tables($pdo); $params=[ge_current_tenant_id()]; $sql='SELECT * FROM ge_bank_movements WHERE tenant_id=?'; if($bankAccountId>0){ $sql.=' AND bank_account_id=?'; $params[]=$bankAccountId; } $sql.=' ORDER BY movement_date DESC, id DESC LIMIT '.max(1,$limit); $stmt=$pdo->prepare($sql); $stmt->execute($params); return $stmt->fetchAll() ?: []; }catch(Throwable $e){ return []; }
}}
if(!function_exists('ge_finance_reconcile_movement')){
function ge_finance_reconcile_movement(int $movementId, bool $reconciled=true): void {
    try{ $pdo=db(); ge_erp_ensure_tables($pdo); $stmt=$pdo->prepare('UPDATE ge_bank_movements SET reconciled=? WHERE id=? AND tenant_id=?'); $stmt->execute([$reconciled?1:0,$movementId, ge_current_tenant_id()]); }catch(Throwable $e){}
}}
if(!function_exists('ge_accounting_seed_defaults')){
function ge_accounting_seed_defaults(): void {
    $accounts=data_read('accounting_accounts', []);
    if(!$accounts){
        $defs=[
            ['code'=>'111000','label'=>'Capital social','type'=>'Passif'],['code'=>'211000','label'=>'Immobilisations','type'=>'Actif'],['code'=>'345520','label'=>'TVA récupérable','type'=>'Actif'],['code'=>'401000','label'=>'Fournisseurs','type'=>'Passif'],['code'=>'411000','label'=>'Clients','type'=>'Actif'],['code'=>'445500','label'=>'TVA facturée','type'=>'Passif'],['code'=>'512000','label'=>'Banque','type'=>'Actif'],['code'=>'611000','label'=>'Achats marchandises','type'=>'Charge'],['code'=>'711000','label'=>'Ventes marchandises','type'=>'Produit'],['code'=>'709000','label'=>'Rabais/remises/avoirs accordés','type'=>'Produit']
        ];
        $i=1; foreach($defs as &$d){ $d['id']=$i; $d['ref']='ACC-'.str_pad((string)$i,3,'0',STR_PAD_LEFT); $d['status']='Actif'; $d['created_at']=date('Y-m-d H:i:s'); $i++; } unset($d);
        data_write('accounting_accounts',$defs,false);
    }
    $journals=data_read('accounting_journals', []);
    if(!$journals){
        $defs=[['code'=>'VT','label'=>'Journal ventes','type'=>'Vente'],['code'=>'AC','label'=>'Journal achats','type'=>'Achat'],['code'=>'BQ','label'=>'Journal banque','type'=>'Banque'],['code'=>'OD','label'=>'Opérations diverses','type'=>'OD']];
        $i=1; foreach($defs as &$d){ $d['id']=$i; $d['ref']='JRN-'.str_pad((string)$i,3,'0',STR_PAD_LEFT); $d['status']='Actif'; $d['created_at']=date('Y-m-d H:i:s'); $i++; } unset($d);
        data_write('accounting_journals',$defs,false);
    }
}}
if(!function_exists('ge_accounting_balance_rows')){
function ge_accounting_balance_rows(): array {
    $labels=[]; foreach(data_read('accounting_accounts',[]) as $a){ $labels[(string)($a['code'] ?? '')]=(string)($a['label'] ?? ''); }
    $bal=[]; foreach(data_read('accounting_entries',[]) as $e){ $acc=(string)($e['account'] ?? ''); if($acc==='') continue; if(!isset($bal[$acc])) $bal[$acc]=['account'=>$acc,'label'=>$labels[$acc] ?? '','debit'=>0.0,'credit'=>0.0,'balance'=>0.0]; $bal[$acc]['debit']+=ge_decimal($e['debit'] ?? 0); $bal[$acc]['credit']+=ge_decimal($e['credit'] ?? 0); $bal[$acc]['balance']=$bal[$acc]['debit']-$bal[$acc]['credit']; }
    ksort($bal); return array_values($bal);
}}
if(!function_exists('ge_accounting_tva_summary')){
function ge_accounting_tva_summary(): array {
    $out=['collected'=>0.0,'deductible'=>0.0,'net'=>0.0];
    foreach(data_read('accounting_entries', []) as $e){
        $acc=(string)($e['account'] ?? '');
        if(str_starts_with($acc,'445')) $out['collected'] += ge_decimal($e['credit'] ?? 0) - ge_decimal($e['debit'] ?? 0);
        if(str_starts_with($acc,'345')) $out['deductible'] += ge_decimal($e['debit'] ?? 0) - ge_decimal($e['credit'] ?? 0);
    }
    $out['net']=$out['collected']-$out['deductible']; return $out;
}}

if(!function_exists('ge_erp_remove_bank_movement')){
function ge_erp_remove_bank_movement(string $sourceType, int $sourceId): void {
    try{ $pdo=db(); ge_erp_ensure_tables($pdo); $stmt=$pdo->prepare('DELETE FROM ge_bank_movements WHERE source_type=? AND source_id=? AND tenant_id=?'); $stmt->execute([$sourceType,$sourceId, ge_current_tenant_id()]); ge_erp_update_bank_balances(); }catch(Throwable $e){}
}}


if(!function_exists('ge_finance_payment_is_active')){
function ge_finance_payment_is_active(string $status): bool {
    return in_array($status, ['Confirmé','Rapproché'], true);
}}

if(!function_exists('ge_finance_status_class')){
function ge_finance_status_class(string $status): string {
    $status=trim($status);
    if($status==='Confirmé') return 'badge-green';
    if($status==='Rapproché') return 'badge-blue';
    if($status==='Annulé') return 'badge-red';
    return 'badge-gray';
}}

if(!function_exists('ge_finance_apply_payment_logic')){
function ge_finance_apply_payment_logic(string $collection, array $row): void {
    $id=(int)($row['id'] ?? 0);
    if($id<=0) return;
    if($collection==='payments'){
        $invoiceId=(int)($row['invoice_id'] ?? 0);
        if($invoiceId>0) ge_finance_update_invoice_payment($invoiceId);
        ge_erp_sync_finance_payment_side_effects('payments', $row);
        if(ge_finance_payment_is_active((string)($row['status'] ?? ''))){
            ge_accounting_post_lines('payment',$id,'BQ','Paiement client '.($row['ref'] ?? ''),[
                ['account'=>'512000','amount'=>($row['amount'] ?? 0),'debit'=>($row['amount'] ?? 0),'credit'=>0],
                ['account'=>'411000','amount'=>($row['amount'] ?? 0),'debit'=>0,'credit'=>($row['amount'] ?? 0)],
            ]);
        }else{
            ge_accounting_post_lines('payment',$id,'BQ','',[]);
        }
        if($invoiceId>0) ge_finance_update_invoice_payment($invoiceId);
    }
    if($collection==='supplier_payments'){
        $invoiceId=(int)($row['supplier_invoice_id'] ?? 0);
        if($invoiceId>0 && function_exists('ge_finance_update_supplier_invoice_payment')) ge_finance_update_supplier_invoice_payment($invoiceId);
        ge_erp_sync_finance_payment_side_effects('supplier_payments', $row);
        if(ge_finance_payment_is_active((string)($row['status'] ?? ''))){
            ge_accounting_post_lines('supplier_payment',$id,'BQ','Paiement fournisseur '.($row['ref'] ?? ''),[
                ['account'=>'401000','amount'=>($row['amount'] ?? 0),'debit'=>($row['amount'] ?? 0),'credit'=>0],
                ['account'=>'512000','amount'=>($row['amount'] ?? 0),'debit'=>0,'credit'=>($row['amount'] ?? 0)],
            ]);
        }else{
            ge_accounting_post_lines('supplier_payment',$id,'BQ','',[]);
        }
        if($invoiceId>0 && function_exists('ge_finance_update_supplier_invoice_payment')) ge_finance_update_supplier_invoice_payment($invoiceId);
    }
}}

if(!function_exists('ge_finance_set_payment_status')){
function ge_finance_set_payment_status(string $collection, int $id, string $status): bool {
    $allowed=ge_status_options('payment');
    if(!in_array($status, $allowed, true)) return false;
    if(!in_array($collection, ['payments','supplier_payments'], true)) return false;
    $rows=data_read($collection, []);
    $changed=false; $row=null;
    foreach($rows as &$r){
        if((int)($r['id'] ?? 0)===$id){
            $r['status']=$status;
            $r['updated_at']=date('Y-m-d H:i:s');
            if($status==='Rapproché' && empty($r['reconciled_at'])) $r['reconciled_at']=date('Y-m-d H:i:s');
            if($status==='Confirmé' && empty($r['confirmed_at'])) $r['confirmed_at']=date('Y-m-d H:i:s');
            if($status==='Annulé' && empty($r['cancelled_at'])) $r['cancelled_at']=date('Y-m-d H:i:s');
            $row=$r; $changed=true; break;
        }
    }
    unset($r);
    if(!$changed || !$row) return false;
    data_write($collection, $rows, false);
    ge_finance_apply_payment_logic($collection, $row);
    audit_log('finance_status', $collection.' #'.$id.' => '.$status);
    return true;
}}

if(!function_exists('ge_finance_payment_by_id')){
function ge_finance_payment_by_id(string $collection, int $id): array {
    foreach(data_read($collection, []) as $r){ if((int)($r['id'] ?? 0)===$id) return $r; }
    return [];
}}

if(!function_exists('ge_finance_bank_movement_for')){
function ge_finance_bank_movement_for(string $sourceType, int $sourceId): array {
    try{ $pdo=db(); ge_erp_ensure_tables($pdo); $stmt=$pdo->prepare('SELECT * FROM ge_bank_movements WHERE source_type=? AND source_id=? AND tenant_id=? LIMIT 1'); $stmt->execute([$sourceType,$sourceId, ge_current_tenant_id()]); return $stmt->fetch() ?: []; }catch(Throwable $e){ return []; }
}}

if(!function_exists('ge_finance_accounting_entries_for')){
function ge_finance_accounting_entries_for(string $sourceType, int $sourceId): array {
    $out=[]; foreach(data_read('accounting_entries', []) as $e){ if((string)($e['source_type'] ?? '')===$sourceType && (int)($e['source_id'] ?? 0)===$sourceId) $out[]=$e; } return $out;
}}

if(!function_exists('ge_finance_bank_account_detail')){
function ge_finance_bank_account_detail(int $id): array {
    foreach(data_read('bank_accounts', []) as $r){ if((int)($r['id'] ?? 0)===$id) return $r; }
    return [];
}}
