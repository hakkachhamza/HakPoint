<?php
// Universal Excel export for list pages. Outputs an HTML .xls file readable by Excel/LibreOffice.
$type = preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)($_GET['type'] ?? ''));

function ge_xls_clean($value){
    if(is_array($value) || is_object($value)) $value = json_encode($value, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    $value = html_entity_decode(strip_tags((string)$value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $value = str_replace(["\r\n","\r","\n"], ' ', $value);
    return trim($value);
}
function ge_xls_filename($label){
    $base = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $label);
    $base = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $base ?: 'export'));
    $base = trim($base, '-') ?: 'export';
    return $base.'-'.date('Ymd-His').'.xls';
}
function ge_xls_auto_columns($rows, $preferred=[]){
    $exclude = ['password','password_hash','twofa_secret','reset_token','reset_expires','plain_password','password_plain','payload','extra_json','path','csrf_token'];
    $keys=[];
    foreach((array)$preferred as $k=>$label){ $keys[$k]=$label; }
    foreach((array)$rows as $r){
        if(!is_array($r)) continue;
        foreach($r as $k=>$v){
            if(in_array($k,$exclude,true) || str_starts_with((string)$k,'_')) continue;
            if(!isset($keys[$k])) $keys[$k]=ucfirst(str_replace('_',' ',(string)$k));
        }
    }
    return $keys;
}
function ge_xls_output($label, $columns, $rows){
    while(ob_get_level()>0){ @ob_end_clean(); }
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="'.ge_xls_filename($label).'"');
    header('Cache-Control: max-age=0');
    echo "\xEF\xBB\xBF";
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>';
    echo 'table{border-collapse:collapse;width:100%;font-family:Arial,Helvetica,sans-serif;font-size:12px}';
    echo 'th{background:#eaf3fb;color:#0f172a;font-weight:700;border:1px solid #b6c5d6;padding:7px;text-align:left}';
    echo 'td{border:1px solid #d9e2ec;padding:6px;mso-number-format:"\\@";}';
    echo 'tr:nth-child(even) td{background:#f8fafc}.title{font-size:18px;font-weight:700;margin-bottom:10px}.meta{font-size:11px;color:#475569;margin-bottom:12px}';
    echo '</style></head><body>';
    echo '<div class="title">'.htmlspecialchars($label, ENT_QUOTES, 'UTF-8').'</div>';
    echo '<div class="meta">Exporté le '.htmlspecialchars(date('Y-m-d H:i:s'), ENT_QUOTES, 'UTF-8').' — '.count((array)$rows).' lignes</div>';
    echo '<table><thead><tr>';
    foreach($columns as $key=>$head) echo '<th>'.htmlspecialchars((string)$head, ENT_QUOTES, 'UTF-8').'</th>';
    echo '</tr></thead><tbody>';
    foreach((array)$rows as $r){
        echo '<tr>';
        foreach($columns as $key=>$head){
            $v = is_array($r) ? ($r[$key] ?? '') : '';
            echo '<td>'.htmlspecialchars(ge_xls_clean($v), ENT_QUOTES, 'UTF-8').'</td>';
        }
        echo '</tr>';
    }
    if(!$rows){ echo '<tr><td colspan="'.max(1,count($columns)).'">Aucune donnée</td></tr>'; }
    echo '</tbody></table></body></html>';
    exit;
}
function ge_xls_collection_rows($collection){
    $rows = data_read($collection, []);
    return function_exists('ge_list_sort_oldest_first') ? ge_list_sort_oldest_first($rows) : $rows;
}

$label = 'Export';
$rows = [];
$columns = [];

switch($type){
    case 'products':
    case 'product_stock':
        require_permission('products.export');
        $label = $type === 'product_stock' ? 'Stock produits' : 'Produits';
        $rows = ge_xls_collection_rows('products');
        $columns = [
            'ref'=>'Réf.','label'=>'Libellé','type'=>'Type','brand'=>'Marque','sale_price'=>'Prix vente','buy_price'=>'Prix achat','desired_stock'=>'Stock désiré','physical_stock'=>'Stock physique','virtual_stock'=>'Stock virtuel','alert_stock'=>'Alerte stock','sale_status'=>'État vente','buy_status'=>'État achat','site_visible'=>'Visible site'
        ];
        break;
    case 'tiers':
    case 'prospects':
    case 'clients':
    case 'suppliers':
        $perm = ['tiers'=>'tiers.export','prospects'=>'prospects.export','clients'=>'clients.export','suppliers'=>'suppliers.export'][$type];
        require_permission($perm);
        require_once __DIR__.'/tiers/_helpers.php';
        $filter = ['prospects'=>'prospect','clients'=>'client','suppliers'=>'supplier'][$type] ?? '';
        $rows = tiers_all();
        if($filter) $rows = array_values(array_filter($rows, fn($t)=>(($t['type']??'')===$filter || !empty($t['is_'.$filter]))));
        $label = ['tiers'=>'Tiers','prospects'=>'Prospects','clients'=>'Clients','suppliers'=>'Fournisseurs'][$type];
        $columns = ['ref'=>'Réf.','name'=>'Nom','alias'=>'Nom alternatif','email'=>'Email','phone'=>'Téléphone','city'=>'Ville','zip'=>'Code postal','country'=>'Pays','type'=>'Type','tier_type'=>'Nature','status'=>'Statut','ice'=>'ICE','code_client'=>'Code client','code_supplier'=>'Code fournisseur'];
        break;
    case 'warehouses':
        require_permission('stock.export');
        $label='Entrepôts'; $rows=ge_xls_collection_rows('warehouses');
        $columns=['ref'=>'Réf.','name'=>'Entrepôt','city'=>'Ville','country'=>'Pays','status'=>'Statut','manager'=>'Responsable','note'=>'Note'];
        break;
    case 'warehouse_movements':
        require_permission('stock.export');
        $label='Mouvements stock'; $rows=ge_xls_collection_rows('warehouse_movements');
        $columns=['date'=>'Date','product_ref'=>'Réf. produit','product_label'=>'Produit','warehouse_name'=>'Entrepôt','type'=>'Type','qty'=>'Quantité','source_type'=>'Source','source_id'=>'ID source','note'=>'Note'];
        break;
    case 'quotes':
        require_permission('quotes.export');
        $label='Devis'; $rows=ge_xls_collection_rows('quotes');
        $columns=['ref'=>'Réf.','tier_name'=>'Client','client_name'=>'Client','date'=>'Date','valid_until'=>'Validité','status'=>'Statut','total_ht'=>'Total HT','total_tva'=>'TVA','total_ttc'=>'Total TTC','note_public'=>'Note'];
        break;
    case 'orders':
        require_permission('orders.export');
        $label='Commandes clients'; $rows=ge_xls_collection_rows('orders');
        $columns=['ref'=>'Réf.','tier_name'=>'Client','client_name'=>'Client','date'=>'Date','delivery_date'=>'Livraison','status'=>'Statut','total_ht'=>'Total HT','total_tva'=>'TVA','total_ttc'=>'Total TTC','note_public'=>'Note'];
        break;
    case 'invoices':
        require_permission('invoices.export');
        $label='Factures clients'; $rows=ge_xls_collection_rows('invoices');
        $columns=['ref'=>'Réf.','tier_name'=>'Client','client_name'=>'Client','date'=>'Date','due_date'=>'Échéance','status'=>'Statut','total_ht'=>'Total HT','total_tva'=>'TVA','total_ttc'=>'Total TTC','paid_amount'=>'Payé','remaining_amount'=>'Reste à payer'];
        break;
    case 'expeditions':
        require_permission('shipments.export');
        $label='Expéditions'; $rows=ge_xls_collection_rows('expeditions');
        $columns=['ref'=>'Réf.','tier_name'=>'Client','client_name'=>'Client','date'=>'Date','delivery_date'=>'Livraison','status'=>'Statut','amount_ht'=>'HT','amount_ttc'=>'TTC','note_public'=>'Note'];
        break;
    case 'receptions':
        require_permission('receptions.export');
        $label='Réceptions'; $rows=ge_xls_collection_rows('receptions');
        $columns=['ref'=>'Réf.','tier_name'=>'Fournisseur','supplier_name'=>'Fournisseur','date'=>'Date','status'=>'Statut','amount_ht'=>'HT','amount_ttc'=>'TTC','note_public'=>'Note'];
        break;
    case 'users':
        require_permission('users.export');
        $label='Utilisateurs'; $rows=ge_xls_collection_rows('users');
        $columns=['username'=>'Utilisateur','name'=>'Nom','email'=>'Email','role'=>'Rôle','status'=>'Statut','twofa_enabled'=>'2FA','last_login'=>'Dernière connexion','created_at'=>'Créé'];
        break;
    case 'purchase_orders':
        require_permission('purchases.view');
        require_once __DIR__.'/purchases/_helpers.php';
        $pdo=db(); ge_purchase_ensure_tables($pdo);
        $rows=ge_fetch_tenant_rows($pdo, 'ge_purchase_orders', 'id ASC', 20000);
        $label='Bons de commande fournisseur';
        $columns=['ref'=>'Réf.','supplier_name'=>'Fournisseur','order_date'=>'Date','status'=>'Statut','amount_ht'=>'HT','amount_tva'=>'TVA','amount_ttc'=>'TTC','supplier_invoice_ref'=>'Facture fournisseur','created_at'=>'Créé'];
        break;
    case 'supplier_invoices':
        require_permission('purchases.view');
        require_once __DIR__.'/purchases/_helpers.php';
        $pdo=db(); ge_purchase_ensure_tables($pdo);
        $rows=ge_fetch_tenant_rows($pdo, 'ge_supplier_invoices', 'id ASC', 20000);
        $label='Factures fournisseurs';
        $columns=['ref'=>'Réf.','supplier_name'=>'Fournisseur','invoice_date'=>'Date','due_date'=>'Échéance','status'=>'Statut','amount_ht'=>'HT','amount_tva'=>'TVA','amount_ttc'=>'TTC','purchase_order_ref'=>'Commande','paid_at'=>'Payée le'];
        break;
    case 'credit_notes':
        require_permission('credit_notes.view');
        require_once __DIR__.'/credit_notes/_helpers.php';
        $pdo=db(); ge_credit_ensure_tables($pdo);
        $rows=ge_fetch_tenant_rows($pdo, 'ge_credit_notes', 'id ASC', 20000);
        $label='Avoirs clients';
        $columns=['ref'=>'Réf.','invoice_ref'=>'Facture','client_name'=>'Client','credit_date'=>'Date','status'=>'Statut','amount_ht'=>'HT','amount_tva'=>'TVA','amount_ttc'=>'TTC','reason'=>'Motif','created_at'=>'Créé'];
        break;
    case 'approvals':
        require_permission('approvals.view');
        require_once __DIR__.'/approvals/_helpers.php';
        $pdo=db(); ge_approval_ensure_tables($pdo);
        $rows=ge_fetch_tenant_rows($pdo, 'ge_approval_requests', 'id ASC', 20000);
        $label='Validations';
        $columns=['title'=>'Objet','object_type'=>'Type','object_ref'=>'Référence','amount'=>'Montant','priority'=>'Priorité','status'=>'Statut','requested_by'=>'Demandé par','decided_by'=>'Décidé par','created_at'=>'Créé','decided_at'=>'Décidé le'];
        break;
    case 'bank_accounts':
        require_permission('finance.export');
        $label='Comptes bancaires'; $rows=ge_xls_collection_rows('bank_accounts');
        $columns=['ref'=>'Réf.','name'=>'Compte','bank_name'=>'Banque','rib'=>'RIB / IBAN','currency'=>'Devise','opening_balance'=>'Solde initial','current_balance'=>'Solde actuel','status'=>'Statut','note'=>'Note'];
        break;
    case 'bank_movements':
        require_permission('finance.export');
        $label='Mouvements bancaires'; $rows=function_exists('ge_finance_bank_movements') ? ge_finance_bank_movements(0, 100000) : [];
        $columns=['movement_date'=>'Date','bank_account_id'=>'ID compte','label'=>'Libellé','source_type'=>'Source','source_id'=>'ID source','debit'=>'Débit','credit'=>'Crédit','reconciled'=>'Rapproché'];
        break;
    case 'payment_modes':
        require_permission('finance.export');
        $label='Modes de paiement'; $rows=ge_xls_collection_rows('payment_modes');
        $columns=['ref'=>'Réf.','label'=>'Libellé','type'=>'Type','status'=>'Statut','note'=>'Note'];
        break;
    case 'payments':
        require_permission('finance.export');
        $label='Paiements clients'; $rows=ge_xls_collection_rows('payments');
        $columns=['ref'=>'Réf.','invoice_ref'=>'Facture','client_name'=>'Client','date'=>'Date','amount'=>'Montant','mode'=>'Mode','bank_account_id'=>'ID compte','status'=>'Statut','reference'=>'Référence paiement'];
        break;
    case 'supplier_payments':
        require_permission('finance.export');
        $label='Paiements fournisseurs'; $rows=ge_xls_collection_rows('supplier_payments');
        $columns=['ref'=>'Réf.','supplier_invoice_ref'=>'Facture fournisseur','supplier_name'=>'Fournisseur','date'=>'Date','amount'=>'Montant','mode'=>'Mode','bank_account_id'=>'ID compte','status'=>'Statut','reference'=>'Référence paiement'];
        break;
    case 'accounting_entries':
    case 'accounting':
        require_permission('accounting.export');
        $label='Écritures comptables'; $rows=ge_xls_collection_rows('accounting_entries');
        $columns=['date'=>'Date','journal'=>'Journal','account'=>'Compte','label'=>'Libellé','debit'=>'Débit','credit'=>'Crédit','source_type'=>'Source','source_id'=>'ID source'];
        break;
    case 'accounting_accounts':
        require_permission('accounting.export');
        $label='Plan comptable'; $rows=ge_xls_collection_rows('accounting_accounts');
        $columns=['code'=>'Code','label'=>'Libellé','type'=>'Type','status'=>'Statut'];
        break;
    case 'documents':
        require_permission('documents.view');
        $label='Documents'; $rows=ge_xls_collection_rows('documents');
        $columns=['ref'=>'Réf.','title'=>'Titre','object_type'=>'Type objet','object_id'=>'ID objet','object_ref'=>'Objet','filename'=>'Fichier','size_bytes'=>'Taille octets','status'=>'Statut','created_at'=>'Date'];
        break;
    case 'projects':
        require_permission('projects.view');
        $label='Projets'; $rows=ge_xls_collection_rows('projects');
        $columns=['ref'=>'Réf.','title'=>'Projet','client_name'=>'Client','start_date'=>'Début','end_date'=>'Fin','budget'=>'Budget','status'=>'Statut','note'=>'Note'];
        break;
    case 'agenda':
    case 'agenda_events':
        require_permission('agenda.view');
        $label='Agenda'; $rows=ge_xls_collection_rows('agenda_events');
        $columns=['ref'=>'Réf.','title'=>'Événement','object_type'=>'Objet','object_id'=>'ID objet','event_date'=>'Date','event_time'=>'Heure','assigned_to'=>'Responsable','status'=>'Statut','note'=>'Note'];
        break;
    case 'pos':
    case 'pos_sales':
        require_permission('pos.view');
        $label='Caisse POS'; $rows=ge_xls_collection_rows('pos_sales');
        $columns=['ref'=>'Réf.','client_name'=>'Client','date'=>'Date','amount_ht'=>'HT','amount_ttc'=>'TTC','payment_mode'=>'Paiement','status'=>'Statut','note'=>'Note'];
        break;
    case 'manufacturing':
    case 'manufacturing_orders':
        require_permission('manufacturing.view');
        $label='Fabrication BOM'; $rows=ge_xls_collection_rows('manufacturing_orders');
        $columns=['ref'=>'Réf.','product_id'=>'ID produit','product_ref'=>'Réf produit','product_label'=>'Produit fini','qty'=>'Quantité','start_date'=>'Début','end_date'=>'Fin','cost'=>'Coût','status'=>'Statut','note'=>'Note'];
        break;
    case 'currencies':
        require_permission('settings.company');
        $label='Devises'; $rows=ge_xls_collection_rows('currencies');
        $columns=['ref'=>'Réf.','code'=>'Code','name'=>'Nom','symbol'=>'Symbole','rate'=>'Taux','status'=>'Statut'];
        break;
    case 'custom_fields':
        require_permission('settings.custom_fields');
        $label='Champs personnalisés'; $rows=ge_xls_collection_rows('custom_fields');
        $columns=['ref'=>'Réf.','object_type'=>'Objet','field_key'=>'Clé','label'=>'Libellé','field_type'=>'Type','required'=>'Obligatoire','status'=>'Statut','options'=>'Options'];
        break;
    default:
        http_response_code(404);
        echo 'Export non disponible.';
        exit;
}

$columns = ge_xls_auto_columns($rows, $columns);
ge_xls_output($label, $columns, $rows);
