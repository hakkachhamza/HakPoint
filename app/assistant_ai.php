<?php
/**
 * hakpoint AI
 * Local ERP assistant with optional Gemini API reasoning.
 * Deterministic ERP actions stay inside PHP for safety; Gemini receives a site context
 * and answers in Arabic, French, or English when no local command is required.
 */

function ge_assistant_creator(): string {
    return 'I was created by cybersecurity/development guy Hamza Hakkache.';
}

function ge_assistant_detect_language(string $text): string {
    if (preg_match('/[\x{0600}-\x{06FF}]/u', $text)) return 'ar';
    $t = strtolower($text);
    foreach (['comment','créer','facture','devis','produit','client','fournisseur','rapport','aujourd','supprimer','paramètre','fonctionne'] as $w) {
        if (str_contains($t, $w)) return 'fr';
    }
    return 'en';
}

function ge_assistant_msg(string $key, string $lang='fr', array $vars=[]): string {
    $messages = [
        'hello' => [
            'ar' => "مرحبا، أنا مساعد hakpoint AI. نقدر نشرح لك الأقسام، نخلق منتج/عميل/فاتورة، نخرج PDF، نعطيك rapport، وندير فحص للموقع.",
            'fr' => "Bonjour, je suis l’assistant hakpoint AI. Je peux expliquer les sections, créer produit/client/facture, générer PDF, donner des rapports et vérifier le site.",
            'en' => "Hello, I am hakpoint AI assistant. I can explain sections, create product/client/invoice, generate PDFs, give reports, and check the site."
        ],
        'not_understood' => [
            'ar' => "ما فهمتش الأمر مزيان. جرّب مثلا: كيفاش نخلق منتج؟ / أعطني تقرير اليوم / أعطني facture ديال client Ahmed PDF / افحص الموقع.",
            'fr' => "Je n’ai pas bien compris. Essayez: comment créer un produit ? / rapport aujourd’hui / donne-moi la facture du client Ahmed en PDF / vérifier le site.",
            'en' => "I did not fully understand. Try: how to create a product? / today's report / give me invoice PDF for client Ahmed / check the site."
        ],
        'permission_denied' => [
            'ar' => "ما عندكش الصلاحية الكافية باش ندير هاد العملية.",
            'fr' => "Vous n’avez pas la permission suffisante pour cette action.",
            'en' => "You do not have enough permission for this action."
        ],
        'confirm_delete' => [
            'ar' => "الحذف عملية خطيرة. عاود نفس الأمر وزيد كلمة confirm باش نمسح.",
            'fr' => "La suppression est dangereuse. Répétez la commande avec le mot confirm pour supprimer.",
            'en' => "Delete is dangerous. Repeat the command with the word confirm to delete."
        ],
        'no_invoice' => [
            'ar' => "ما لقيتش حتى فاتورة لهذا العميل.",
            'fr' => "Je n’ai trouvé aucune facture pour ce client.",
            'en' => "I found no invoice for this client."
        ],
    ];
    $out = $messages[$key][$lang] ?? $messages[$key]['fr'] ?? $key;
    foreach ($vars as $k=>$v) $out = str_replace('{'.$k.'}', (string)$v, $out);
    return $out;
}

function ge_assistant_normalize(string $s): string {
    $s = trim($s);
    $s = preg_replace('/\s+/u', ' ', $s);
    return $s ?: '';
}

function ge_assistant_response(string $reply, array $links=[], array $meta=[]): array {
    return ['ok'=>true, 'reply'=>$reply, 'links'=>$links, 'meta'=>$meta, 'created_at'=>date('Y-m-d H:i:s')];
}

function ge_assistant_log(string $message, array $response): void {
    try {
        $rows = data_read('assistant_logs', []);
        $rows[] = [
            'id'=>next_id($rows),
            'user_id'=>(int)(current_user()['id'] ?? 0),
            'message'=>$message,
            'reply'=>$response['reply'] ?? '',
            'links'=>$response['links'] ?? [],
            'created_at'=>date('Y-m-d H:i:s'),
        ];
        if (count($rows) > 300) $rows = array_slice($rows, -300);
        data_write('assistant_logs', $rows, false);
    } catch (Throwable $e) {}
}

function ge_assistant_help_text(string $message, string $lang): ?array {
    $t = mb_strtolower($message, 'UTF-8');
    $section = '';
    $map = [
        'product'=>'products','produit'=>'products','منتج'=>'products','produits'=>'products',
        'client'=>'clients','clients'=>'clients','زبون'=>'clients','عميل'=>'clients',
        'supplier'=>'suppliers','fournisseur'=>'suppliers','مورد'=>'suppliers',
        'quote'=>'quotes','devis'=>'quotes','عرض'=>'quotes',
        'order'=>'orders','commande'=>'orders','طلبية'=>'orders',
        'invoice'=>'invoices','facture'=>'invoices','فاتورة'=>'invoices',
        'purchase'=>'purchases','achat'=>'purchases','achats'=>'purchases','شراء'=>'purchases',
        'stock'=>'stock','warehouse'=>'stock','entrepôt'=>'stock','مخزون'=>'stock',
        'validation'=>'approvals','validations'=>'approvals','موافقة'=>'approvals',
        'avoir'=>'credit_notes','credit note'=>'credit_notes','إشعار'=>'credit_notes',
        'report'=>'reports','rapport'=>'reports','تقارير'=>'reports',
        'settings'=>'settings','paramètres'=>'settings','إعدادات'=>'settings',
    ];
    foreach ($map as $k=>$v) { if (str_contains($t, $k)) { $section=$v; break; } }
    $isHelp = preg_match('/\b(how|comment|explain|help|aide|fonctionne|create|cr[eé]er|add|ajouter)\b/u', $t) || preg_match('/كيف|شرح|اشرح|نخلق|نصايب|نزيد/u', $t);
    if (!$isHelp && !$section) return null;
    if (!$section) $section = 'general';

    $texts = [
        'products'=>[
            'ar'=>"قسم المنتجات: ادخل إلى Produits → Nouveau produit، عمر المرجع والاسم والسعر والستوك والـ entrepôt، ثم Enregistrer. تقدر تقول لي: create product Batterie 200Ah price 1200 stock 5، ونخلقو لك مباشرة.",
            'fr'=>"Section Produits : allez à Produits → Nouveau produit, remplissez référence, libellé, prix, stock et entrepôt puis Enregistrer. Vous pouvez aussi me dire: créer produit Batterie 200Ah prix 1200 stock 5.",
            'en'=>"Products: go to Products → New product, fill reference, label, price, stock and warehouse, then Save. You can also say: create product Battery 200Ah price 1200 stock 5."
        ],
        'clients'=>[
            'ar'=>"قسم العملاء: Tiers → Nouveau client، دخل الاسم، الهاتف، الإيميل، المدينة، ICE إذا كاين، ثم حفظ. تقدر تقول: create client Ahmed email ahmed@test.com.",
            'fr'=>"Clients : Tiers → Nouveau client, saisissez nom, téléphone, email, ville, ICE si disponible puis enregistrer. Vous pouvez dire: créer client Ahmed email ahmed@test.com.",
            'en'=>"Clients: Tiers → New client, enter name, phone, email, city, ICE if available, then save. You can say: create client Ahmed email ahmed@test.com."
        ],
        'suppliers'=>[
            'ar'=>"قسم fournisseurs: Tiers → Nouveau fournisseur، دخل بيانات المورد. من بعد تقدر تربطو مع Achats fournisseurs.",
            'fr'=>"Fournisseurs : Tiers → Nouveau fournisseur, saisissez les informations. Ensuite vous pouvez lier ce fournisseur aux achats.",
            'en'=>"Suppliers: Tiers → New supplier, enter supplier data, then link it with supplier purchases."
        ],
        'invoices'=>[
            'ar'=>"الفاتورات: Factures clients → Nouvelle facture، اختار client، زيد lignes، validé ثم PDF. تقدر تقول: donne-moi facture PDF client Ahmed باش نخرج آخر فاتورة PDF.",
            'fr'=>"Factures : Factures clients → Nouvelle facture, choisissez le client, ajoutez les lignes, validez puis générez le PDF. Dites: donne-moi facture PDF client Ahmed pour obtenir le PDF.",
            'en'=>"Invoices: Customer invoices → New invoice, choose client, add lines, validate and generate PDF. Say: give me invoice PDF for client Ahmed."
        ],
        'purchases'=>[
            'ar'=>"Achats: كتسجل bon de commande fournisseur مع المنتجات. ملي status تولي Reçue، stock كيزيد تلقائياً. من بعد تقدر تخلق facture fournisseur وتخلصها.",
            'fr'=>"Achats : créez un bon de commande fournisseur avec les lignes produits. Quand le statut devient Reçue, le stock augmente automatiquement. Ensuite créez la facture fournisseur et le paiement.",
            'en'=>"Purchases: create a supplier order with product lines. When status becomes Received, stock increases automatically. Then create supplier invoice and payment."
        ],
        'approvals'=>[
            'ar'=>"Validations: أي document كبير حسب rules يقدر يمشي للموافقة. المدير يضغط Approuver أو Refuser، والنظام يطبق القرار على document lié.",
            'fr'=>"Validations : tout document important peut aller en validation selon les règles. Le manager approuve ou refuse, et le système applique la décision au document lié.",
            'en'=>"Approvals: important documents can require approval depending on rules. Manager approves/refuses and the system applies the decision to the linked document."
        ],
        'credit_notes'=>[
            'ar'=>"Avoirs clients: كتخلق avoir مربوط بفاتورة، وعند Validé/Appliqué كينقص remaining amount ديال الفاتورة ويتسجل accounting.",
            'fr'=>"Avoirs clients : créez un avoir lié à une facture. À Validé/Appliqué, il réduit le reste à payer de la facture et crée l’écriture comptable.",
            'en'=>"Credit notes: create a credit note linked to an invoice. When validated/applied, it reduces invoice balance and creates accounting."
        ],
        'stock'=>[
            'ar'=>"Stock: المنتجات مربوطة بالـ warehouse. الحركات كتدخل من achats/receptions/expeditions أو correction stock. راقب Stock avancé وProduits → Stocks.",
            'fr'=>"Stock : les produits sont liés aux entrepôts. Les mouvements viennent des achats, réceptions, expéditions ou corrections. Consultez Stock avancé et Produits → Stocks.",
            'en'=>"Stock: products are linked to warehouses. Movements come from purchases, receipts, shipments or adjustments. Check Advanced Stock and Products → Stock."
        ],
        'reports'=>[
            'ar'=>"Rapports كيعطيك totals، statuts، ventes، achats، stock، validations. قول لي: أعطني تقرير اليوم، أو report sales.",
            'fr'=>"Rapports donne les totaux, statuts, ventes, achats, stock et validations. Dites: rapport aujourd’hui ou rapport ventes.",
            'en'=>"Reports show totals, statuses, sales, purchases, stock and approvals. Say: today report or sales report."
        ],
        'settings'=>[
            'ar'=>"Paramètres فيه الشركة، sécurité، language، backup، modules ON/OFF. أي module طافي ما يبانش وما يدخلش حتى direct URL.",
            'fr'=>"Paramètres contient société, sécurité, langue, sauvegardes et modules ON/OFF. Un module désactivé est caché et bloque aussi l’accès direct URL.",
            'en'=>"Settings contains company, security, language, backups and modules ON/OFF. Disabled modules are hidden and direct URL is blocked."
        ],
        'general'=>[
            'ar'=>"نقدر نعاونك فـ: شرح أي section، create product/client/facture، generate PDF، report اليوم، check/fix site. مثال: أعطني تقرير اليوم.",
            'fr'=>"Je peux aider à expliquer une section, créer produit/client/facture, générer PDF, rapport du jour, vérifier/corriger le site. Exemple: donne-moi le rapport d’aujourd’hui.",
            'en'=>"I can explain sections, create product/client/invoice, generate PDF, today's report, and check/fix the site. Example: give me today report."
        ],
    ];
    return ge_assistant_response($texts[$section][$lang] ?? $texts[$section]['fr']);
}

function ge_assistant_report(string $message, string $lang): array {
    $products = data_read('products', []);
    $tiers = data_read('tiers', []);
    $clients = data_read('clients', []);
    $invoices = data_read('invoices', []);
    $quotes = data_read('quotes', []);
    $orders = data_read('orders', []);
    $today = date('Y-m-d');
    $sumToday = 0; $invoiceToday=0; $paid=0; $unpaid=0;
    foreach ($invoices as $inv) {
        $d = substr((string)($inv['invoice_date'] ?? $inv['date'] ?? $inv['created_at'] ?? ''),0,10);
        $amount = (float)($inv['total_ttc'] ?? $inv['amount_ttc'] ?? $inv['total_ht'] ?? $inv['amount'] ?? 0);
        if ($d === $today) { $invoiceToday++; $sumToday += $amount; }
        $s = (string)($inv['status'] ?? '');
        if (str_contains(mb_strtolower($s,'UTF-8'), 'pay')) $paid += $amount; else $unpaid += $amount;
    }
    $lowStock=[];
    foreach($products as $p){
        $qty = function_exists('ge_product_current_stock') ? ge_product_current_stock($p) : (float)($p['physical_stock'] ?? $p['stock'] ?? 0);
        $alert=(float)($p['alert_stock'] ?? $p['stock_alert'] ?? 0);
        if($alert>0 && $qty <= $alert) $lowStock[] = ($p['ref'] ?? '').' '.($p['label'] ?? $p['name'] ?? 'Produit').' ('.$qty.'/'.$alert.')';
    }
    $pendingApprovals=0; $purchaseTotal=0; $supplierInvoices=0;
    try{
        $pdo=db(); if(function_exists('ge_erp_ensure_tables')) ge_erp_ensure_tables($pdo); else db_install_enterprise_tables($pdo);
        $pendingApprovals=ge_count_tenant_rows($pdo,'ge_approval_requests',"status='En attente'");
        $purchaseTotal=(float)array_sum(array_map(function($r){return (float)($r['amount_ttc'] ?? $r['amount_ht'] ?? 0);}, ge_fetch_tenant_rows($pdo,'ge_purchase_orders','id DESC',20000)));
        $supplierInvoices=(float)array_sum(array_map(function($r){return (float)($r['amount_ttc'] ?? 0);}, ge_fetch_tenant_rows($pdo,'ge_supplier_invoices','id DESC',20000)));
    }catch(Throwable $e){}

    if($lang==='ar'){
        $reply = "تقرير اليوم ".date('d/m/Y').":\n"
            ."- المنتجات: ".count($products)."\n"
            ."- العملاء/tiers: ".(count($tiers)+count($clients))."\n"
            ."- devis: ".count($quotes)."\n"
            ."- commandes: ".count($orders)."\n"
            ."- factures اليوم: {$invoiceToday} بمجموع ".money($sumToday)."\n"
            ."- مجموع المدفوع تقريباً: ".money($paid)."\n"
            ."- غير المدفوع تقريباً: ".money($unpaid)."\n"
            ."- achats fournisseurs: ".money($purchaseTotal)."\n"
            ."- factures fournisseurs: ".money($supplierInvoices)."\n"
            ."- validations en attente: {$pendingApprovals}\n"
            ."- stock alerts: ".count($lowStock).(count($lowStock)?"\n".implode("\n", array_slice($lowStock,0,8)):"");
    } elseif($lang==='en'){
        $reply = "Today's report ".date('d/m/Y').":\n"
            ."- Products: ".count($products)."\n"
            ."- Clients/tiers: ".(count($tiers)+count($clients))."\n"
            ."- Quotes: ".count($quotes)."\n"
            ."- Orders: ".count($orders)."\n"
            ."- Invoices today: {$invoiceToday}, total ".money($sumToday)."\n"
            ."- Paid approx.: ".money($paid)."\n"
            ."- Unpaid approx.: ".money($unpaid)."\n"
            ."- Supplier purchases: ".money($purchaseTotal)."\n"
            ."- Supplier invoices: ".money($supplierInvoices)."\n"
            ."- Pending approvals: {$pendingApprovals}\n"
            ."- Stock alerts: ".count($lowStock).(count($lowStock)?"\n".implode("\n", array_slice($lowStock,0,8)):"");
    } else {
        $reply = "Rapport du jour ".date('d/m/Y').":\n"
            ."- Produits : ".count($products)."\n"
            ."- Clients/tiers : ".(count($tiers)+count($clients))."\n"
            ."- Devis : ".count($quotes)."\n"
            ."- Commandes : ".count($orders)."\n"
            ."- Factures aujourd’hui : {$invoiceToday}, total ".money($sumToday)."\n"
            ."- Payé environ : ".money($paid)."\n"
            ."- Impayé environ : ".money($unpaid)."\n"
            ."- Achats fournisseurs : ".money($purchaseTotal)."\n"
            ."- Factures fournisseurs : ".money($supplierInvoices)."\n"
            ."- Validations en attente : {$pendingApprovals}\n"
            ."- Alertes stock : ".count($lowStock).(count($lowStock)?"\n".implode("\n", array_slice($lowStock,0,8)):"");
    }
    return ge_assistant_response($reply, [['label'=>$lang==='ar'?'فتح التقارير':($lang==='en'?'Open reports':'Ouvrir rapports'), 'url'=>'index.php?page=reports']]);
}

function ge_assistant_find_client_name(string $message): string {
    $m = trim($message);
    $patterns = [
        '/client\s+(.+?)(\s+pdf|\s+facture|$)/iu',
        '/for\s+client\s+(.+?)(\s+pdf|$)/iu',
        '/du\s+client\s+(.+?)(\s+pdf|$)/iu',
        '/ديال\s+(.+?)(\s+pdf|$)/u',
        '/ل\s+(.+?)(\s+pdf|$)/u'
    ];
    foreach($patterns as $p){ if(preg_match($p,$m,$mm)) return trim($mm[1]); }
    return '';
}

function ge_assistant_generate_invoice_pdf_for_client(string $message, string $lang): ?array {
    $t=mb_strtolower($message,'UTF-8');
    $asksPdf = (str_contains($t,'pdf') || str_contains($t,'facture') || str_contains($t,'invoice') || str_contains($t,'فاتورة'));
    if(!$asksPdf) return null;
    $clientName = ge_assistant_find_client_name($message);
    if($clientName==='') {
        if($lang==='ar') return ge_assistant_response('عطيني اسم العميل. مثال: أعطني facture PDF ديال Ahmed');
        if($lang==='en') return ge_assistant_response('Give me the client name. Example: give me invoice PDF for client Ahmed');
        return ge_assistant_response('Donnez-moi le nom du client. Exemple: donne-moi la facture PDF du client Ahmed');
    }
    $invoices = data_read('invoices', []);
    $matches=[];
    foreach($invoices as $inv){
        $name = (string)($inv['client'] ?? $inv['client_name'] ?? $inv['tier_name'] ?? '');
        if($name && mb_stripos($name, $clientName, 0, 'UTF-8') !== false) $matches[]=$inv;
    }
    if(!$matches) return ge_assistant_response(ge_assistant_msg('no_invoice',$lang));
    usort($matches, fn($a,$b)=>(int)($b['id']??0) <=> (int)($a['id']??0));
    $doc=$matches[0]; $id=(int)($doc['id']??0);
    if($id<=0) return ge_assistant_response(ge_assistant_msg('no_invoice',$lang));
    try{
        require_once __DIR__.'/pdf_docs.php';
        require_once __DIR__.'/../views/invoices/_helpers.php';
        $dates=['Date facturation'=>($doc['invoice_date']??''),'Date échéance'=>($doc['due_date']??'')];
        if(!empty($doc['order_id'])) $dates['Commande source']=$doc['order_ref'] ?? ('#'.$doc['order_id']);
        $info=ge_pdf_money_doc($doc,'Facture',$doc['ref']??invoice_ref($id),trim((string)($doc['client']??$doc['client_name']??'')),$dates,$doc['lines']??[],__DIR__.'/../uploads/invoices','Facture','invoice_id','invoice_show','invoice_documents',$id,true);
        $url=$info['url'] ?? ('uploads/invoices/'.($info['filename']??''));
        $reply = $lang==='ar' ? 'وجدت آخر فاتورة لهذا العميل وولدت PDF.' : ($lang==='en' ? 'I found the latest invoice for this client and generated the PDF.' : 'J’ai trouvé la dernière facture de ce client et généré le PDF.');
        return ge_assistant_response($reply, [
            ['label'=>'PDF '.$doc['ref'], 'url'=>$url],
            ['label'=>$lang==='ar'?'فتح الفاتورة':($lang==='en'?'Open invoice':'Ouvrir facture'), 'url'=>'index.php?page=invoice_show&id='.$id]
        ]);
    }catch(Throwable $e){
        return ge_assistant_response(($lang==='ar'?'وقع مشكل ف PDF: ':($lang==='en'?'PDF error: ':'Erreur PDF : ')).$e->getMessage());
    }
}

function ge_assistant_extract_number(string $message, array $keys): ?float {
    $t = str_replace(',', '.', $message);
    foreach($keys as $key){
        $p = '/'.preg_quote($key,'/').'\s*[:=]?\s*([0-9]+(?:\.[0-9]+)?)/iu';
        if(preg_match($p,$t,$m)) return (float)$m[1];
    }
    if(preg_match('/([0-9]+(?:\.[0-9]+)?)/', $t, $m)) return (float)$m[1];
    return null;
}

function ge_assistant_extract_name_after(string $message, array $keys): string {
    $m = trim($message);
    foreach($keys as $key){
        $p = '/'.preg_quote($key,'/').'\s+(.+?)(\s+(price|prix|stock|email|phone|tel|amount|montant|qt[eé]|qty)\b|$)/iu';
        if(preg_match($p,$m,$mm)) return trim($mm[1]);
    }
    return '';
}

function ge_assistant_create_product(string $message, string $lang): ?array {
    $t=mb_strtolower($message,'UTF-8');
    $create = preg_match('/\b(create|add|cr[eé]er|ajouter)\b/u',$t) || preg_match('/خلق|زيد|صايب/u',$t);
    $product = preg_match('/product|produit|منتج/u',$t);
    if(!$create || !$product) return null;
    if(!has_permission('products.create')) return ge_assistant_response(ge_assistant_msg('permission_denied',$lang));
    $name=ge_assistant_extract_name_after($message, ['product','produit','منتج']);
    if($name==='') $name = $lang==='ar' ? 'منتج جديد' : 'Nouveau produit';
    $price=ge_assistant_extract_number($message,['price','prix','sale','vente']) ?? 0;
    $stock=ge_assistant_extract_number($message,['stock','qty','qte','quantité','كمية']) ?? 0;
    $products=data_read('products', []); $id=next_id($products);
    $row=['id'=>$id,'ref'=>'PR'.date('ym').'-'.str_pad((string)$id,5,'0',STR_PAD_LEFT),'label'=>$name,'name'=>$name,'sale_price'=>$price,'buy_price'=>0,'physical_stock'=>$stock,'virtual_stock'=>$stock,'alert_stock'=>0,'status'=>'Actif','site_visible'=>'yes','created_at'=>date('Y-m-d H:i:s')];
    $products[]=$row; data_write('products',$products);
    $reply = $lang==='ar' ? "تم إنشاء المنتج: {$name}" : ($lang==='en' ? "Product created: {$name}" : "Produit créé : {$name}");
    return ge_assistant_response($reply, [['label'=>$name, 'url'=>'index.php?page=product_show&id='.$id]]);
}

function ge_assistant_create_tier(string $message, string $lang): ?array {
    $t=mb_strtolower($message,'UTF-8');
    $create = preg_match('/\b(create|add|cr[eé]er|ajouter)\b/u',$t) || preg_match('/خلق|زيد|صايب/u',$t);
    $isClient = preg_match('/client|customer|زبون|عميل/u',$t);
    $isSupplier = preg_match('/supplier|fournisseur|مورد/u',$t);
    if(!$create || (!$isClient && !$isSupplier)) return null;
    if(!has_permission($isSupplier ? 'suppliers.create' : 'clients.create')) return ge_assistant_response(ge_assistant_msg('permission_denied',$lang));
    $name=ge_assistant_extract_name_after($message, $isSupplier ? ['supplier','fournisseur','مورد'] : ['client','customer','زبون','عميل']);
    if($name==='') $name=$isSupplier?'Nouveau fournisseur':'Nouveau client';
    $email=''; if(preg_match('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i',$message,$em)) $email=$em[0];
    $collection=$isSupplier?'suppliers':'clients';
    $rows=data_read($collection,[]); $id=next_id($rows);
    $prefix=$isSupplier?'SU':'CU';
    $rows[]=['id'=>$id,'ref'=>$prefix.date('ym').'-'.str_pad((string)$id,5,'0',STR_PAD_LEFT),'name'=>$name,'type'=>$isSupplier?'supplier':'client','email'=>$email,'status'=>'Actif','created_at'=>date('Y-m-d H:i:s')];
    data_write($collection,$rows);
    $reply=$lang==='ar' ? ($isSupplier?"تم إنشاء المورد: {$name}":"تم إنشاء العميل: {$name}") : ($lang==='en' ? ($isSupplier?"Supplier created: {$name}":"Client created: {$name}") : ($isSupplier?"Fournisseur créé : {$name}":"Client créé : {$name}"));
    return ge_assistant_response($reply, [['label'=>$name,'url'=>'index.php?page=tiers_show&id='.$id]]);
}

function ge_assistant_create_invoice(string $message, string $lang): ?array {
    $t=mb_strtolower($message,'UTF-8');
    $create = preg_match('/\b(create|add|cr[eé]er|ajouter)\b/u',$t) || preg_match('/خلق|زيد|صايب/u',$t);
    $invoice = preg_match('/invoice|facture|فاتورة/u',$t);
    if(!$create || !$invoice) return null;
    if(!has_permission('invoices.create')) return ge_assistant_response(ge_assistant_msg('permission_denied',$lang));
    $client=ge_assistant_find_client_name($message);
    if($client==='') $client=ge_assistant_extract_name_after($message, ['invoice','facture','فاتورة']);
    if($client==='') $client='Client';
    $amount=ge_assistant_extract_number($message,['amount','montant','total','prix','price']) ?? 0;
    $invoices=data_read('invoices',[]); $id=next_id($invoices);
    $ref='FA'.date('ym').'-'.str_pad((string)$id,5,'0',STR_PAD_LEFT);
    $line=['id'=>1,'description'=>'Service','label'=>'Service','qty'=>1,'pu_ht'=>$amount,'tva'=>20,'total_ht'=>$amount];
    $invoices[]=['id'=>$id,'ref'=>$ref,'client'=>$client,'client_name'=>$client,'invoice_date'=>date('Y-m-d'),'due_date'=>date('Y-m-d',strtotime('+30 days')),'status'=>'Brouillon','lines'=>[$line],'total_ht'=>$amount,'total_tva'=>$amount*0.20,'total_ttc'=>$amount*1.20,'created_at'=>date('Y-m-d H:i:s')];
    data_write('invoices',$invoices);
    $links=[['label'=>$ref,'url'=>'index.php?page=invoice_show&id='.$id]];
    try{
        require_once __DIR__.'/pdf_docs.php'; require_once __DIR__.'/../views/invoices/_helpers.php';
        $doc=find_row_by_id(data_read('invoices',[]),$id);
        $info=ge_pdf_money_doc($doc,'Facture',$ref,$client,['Date facturation'=>date('Y-m-d'),'Date échéance'=>date('Y-m-d',strtotime('+30 days'))],$doc['lines']??[],__DIR__.'/../uploads/invoices','Facture','invoice_id','invoice_show','invoice_documents',$id,true);
        if(!empty($info['url'])) $links[]=['label'=>'PDF '.$ref,'url'=>$info['url']];
    }catch(Throwable $e){}
    $reply=$lang==='ar' ? "تم إنشاء الفاتورة {$ref} للعميل {$client}" : ($lang==='en' ? "Invoice {$ref} created for {$client}" : "Facture {$ref} créée pour {$client}");
    return ge_assistant_response($reply,$links);
}

function ge_assistant_delete_command(string $message, string $lang): ?array {
    $t=mb_strtolower($message,'UTF-8');
    $delete = preg_match('/\b(delete|remove|supprimer|effacer)\b/u',$t) || preg_match('/مسح|حيد|حذف/u',$t);
    if(!$delete) return null;
    $confirmed = str_contains($t,'confirm') || str_contains($t,'confirmer') || str_contains($t,'تأكيد');
    if(!$confirmed) return ge_assistant_response(ge_assistant_msg('confirm_delete',$lang));
    $collections = [
        'product'=>['products','products.delete',['product','produit','منتج']],
        'client'=>['clients','clients.delete',['client','customer','زبون','عميل']],
        'supplier'=>['suppliers','suppliers.delete',['supplier','fournisseur','مورد']],
        'invoice'=>['invoices','invoices.delete',['invoice','facture','فاتورة']],
    ];
    foreach($collections as $type=>$meta){
        [$collection,$perm,$keys]=$meta;
        $hit=false; foreach($keys as $k){ if(str_contains($t, mb_strtolower($k,'UTF-8'))) $hit=true; }
        if(!$hit) continue;
        if(!has_permission($perm)) return ge_assistant_response(ge_assistant_msg('permission_denied',$lang));
        $name=ge_assistant_extract_name_after($message,$keys);
        $rows=data_read($collection,[]); $deleted=null; $out=[];
        foreach($rows as $r){
            $label=(string)($r['ref'] ?? '').' '.(string)($r['label'] ?? $r['name'] ?? $r['client'] ?? $r['client_name'] ?? '');
            if($name!=='' && mb_stripos($label,$name,0,'UTF-8')!==false){ $deleted=$label; continue; }
            $out[]=$r;
        }
        if($deleted){ data_write($collection,$out); return ge_assistant_response(($lang==='ar'?'تم الحذف: ':($lang==='en'?'Deleted: ':'Supprimé : ')).$deleted); }
        return ge_assistant_response($lang==='ar'?'ما لقيتش العنصر باش نمسحو.':($lang==='en'?'Item not found.':'Élément introuvable.'));
    }
    return ge_assistant_response(ge_assistant_msg('confirm_delete',$lang));
}

function ge_assistant_site_check(string $message, string $lang): ?array {
    $t=mb_strtolower($message,'UTF-8');
    $check = preg_match('/check|diagnostic|fix|repair|problem|probl[eè]me|v[eé]rifier|corriger/u',$t) || preg_match('/افحص|صلح|مشكل|مشكلة/u',$t);
    if(!$check) return null;
    if(!has_permission('settings.database') && !is_admin()) return ge_assistant_response(ge_assistant_msg('permission_denied',$lang));
    $fix = preg_match('/fix|repair|corriger|صلح/u',$t);
    $lines=[];
    try{
        $pdo=db(); db_install_core($pdo); if(function_exists('ge_erp_ensure_tables')) ge_erp_ensure_tables($pdo); if(function_exists('db_install_enterprise_tables')) db_install_enterprise_tables($pdo);
        $lines[]='DB: OK';
    }catch(Throwable $e){ $lines[]='DB ERROR: '.$e->getMessage(); }
    foreach(['storage','storage/backups','uploads','uploads/invoices','uploads/products','uploads/documents'] as $dir){
        $path=__DIR__.'/../'.$dir;
        if(!is_dir($path) && $fix) @mkdir($path,0775,true);
        $lines[]=$dir.': '.(is_dir($path)?(is_writable($path)?'OK':'not writable'):'missing');
    }
    $counts=[];
    foreach(['products','clients','suppliers','quotes','orders','invoices','warehouses'] as $c){ $counts[]=$c.'='.count(data_read($c,[])); }
    $lines[]='Counts: '.implode(', ',$counts);
    $pending=0; try{ $pdoAi=db(); $pending=ge_count_tenant_rows($pdoAi,'ge_approval_requests',"status='En attente'"); }catch(Throwable $e){}
    $lines[]='Pending approvals='.$pending;
    $prefix=$fix?($lang==='ar'?'تم الفحص ومحاولة الإصلاح:':($lang==='en'?'Checked and auto-fixed when possible:':'Vérifié et corrigé si possible :')):($lang==='ar'?'نتيجة فحص الموقع:':($lang==='en'?'Site check result:':'Résultat diagnostic site :'));
    return ge_assistant_response($prefix."\n".implode("\n",$lines));
}

function ge_assistant_site_context(string $lang='fr'): string {
    $collections = ['products','clients','suppliers','tiers','quotes','orders','invoices','warehouses','payments','supplier_payments'];
    $counts = [];
    foreach ($collections as $c) {
        try { $counts[$c] = count(data_read($c, [])); } catch (Throwable $e) { $counts[$c] = 0; }
    }

    $lowStock = [];
    foreach (data_read('products', []) as $p) {
        $qty = function_exists('ge_product_current_stock') ? ge_product_current_stock($p) : (float)($p['physical_stock'] ?? $p['stock'] ?? 0);
        $alert = (float)($p['alert_stock'] ?? $p['stock_alert'] ?? 0);
        if ($alert > 0 && $qty <= $alert) {
            $lowStock[] = trim(($p['ref'] ?? '').' '.($p['label'] ?? $p['name'] ?? 'Produit')).' stock='.$qty.' alert='.$alert;
        }
    }

    $recentInvoices = [];
    $invoices = data_read('invoices', []);
    usort($invoices, function($a, $b){ return strcmp((string)($b['created_at'] ?? $b['invoice_date'] ?? ''), (string)($a['created_at'] ?? $a['invoice_date'] ?? '')); });
    foreach (array_slice($invoices, 0, 6) as $inv) {
        $recentInvoices[] = trim(($inv['ref'] ?? '#'.($inv['id'] ?? '')).' | '.($inv['client'] ?? $inv['client_name'] ?? '').' | '.($inv['status'] ?? '').' | '.money($inv['total_ttc'] ?? $inv['amount_ttc'] ?? $inv['total_ht'] ?? 0));
    }

    $pendingApprovals = 0;
    $purchaseTotal = 0;
    $supplierInvoiceTotal = 0;
    try {
        $pdo = db();
        if (function_exists('ge_erp_ensure_tables')) ge_erp_ensure_tables($pdo);
        $pendingApprovals = ge_count_tenant_rows($pdo,'ge_approval_requests',"status='En attente'");
        $purchaseTotal = (float)array_sum(array_map(function($r){return (float)($r['amount_ttc'] ?? $r['amount_ht'] ?? 0);}, ge_fetch_tenant_rows($pdo,'ge_purchase_orders','id DESC',20000)));
        $supplierInvoiceTotal = (float)array_sum(array_map(function($r){return (float)($r['amount_ttc'] ?? 0);}, ge_fetch_tenant_rows($pdo,'ge_supplier_invoices','id DESC',20000)));
    } catch (Throwable $e) {}

    $modulesLine = 'unknown';
    if (function_exists('ge_modules_state') && function_exists('ge_default_modules')) {
        $state = ge_modules_state();
        $parts = [];
        foreach (ge_default_modules() as $key => $label) $parts[] = $key.'='.(!empty($state[$key]) ? 'ON' : 'OFF');
        $modulesLine = implode(', ', $parts);
    }

    return "ERP live context for Global Energie:\n"
        ."Date: ".date('Y-m-d H:i:s')."\n"
        ."Counts: ".json_encode($counts, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n"
        ."Pending approvals: {$pendingApprovals}\n"
        ."Supplier purchases total: ".money($purchaseTotal)."\n"
        ."Supplier invoices total: ".money($supplierInvoiceTotal)."\n"
        ."Low stock: ".($lowStock ? implode(' ; ', array_slice($lowStock,0,10)) : 'none')."\n"
        ."Recent invoices: ".($recentInvoices ? implode(' ; ', $recentInvoices) : 'none')."\n"
        ."Modules: {$modulesLine}\n"
        ."Available local actions already handled by PHP: explain sections, create product/client/supplier/invoice, generate invoice PDF by client name, reports, site check/fix, delete only with confirm.\n";
}

function ge_assistant_gemini_settings(): array {
    $cfg = app_config()['ai'] ?? [];
    $key = trim((string)($cfg['gemini_api_key'] ?? ''));
    if ($key === '') $key = trim((string)getenv('GE_GEMINI_API_KEY'));
    if ($key === '') $key = trim((string)getenv('GEMINI_API_KEY'));
    $model = trim((string)($cfg['gemini_model'] ?? ''));
    if ($model === '') $model = trim((string)getenv('GE_GEMINI_MODEL'));
    if ($model === '') $model = 'gemini-2.5-flash';
    if (str_starts_with($model, 'models/')) $model = substr($model, 7);
    return ['key'=>$key, 'model'=>$model];
}

function ge_assistant_gemini_http(string $url, string $apiKey, array $payload): array {
    $body = json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'x-goog-api-key: '.$apiKey],
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_TIMEOUT => 30,
        ]);
        $raw = curl_exec($ch);
        $err = curl_error($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return [$code, (string)$raw, (string)$err];
    }
    $ctx = stream_context_create(['http'=>[
        'method'=>'POST',
        'header'=>"Content-Type: application/json\r\nx-goog-api-key: {$apiKey}\r\n",
        'content'=>$body,
        'timeout'=>30,
        'ignore_errors'=>true,
    ]]);
    $raw = @file_get_contents($url, false, $ctx);
    $code = 0;
    if (!empty($http_response_header) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) $code = (int)$m[1];
    return [$code, (string)$raw, ''];
}

function ge_assistant_try_gemini_ai(string $message, string $lang): ?array {
    $settings = ge_assistant_gemini_settings();
    if ($settings['key'] === '') {
        $txt = $lang === 'ar'
            ? 'hakpoint AI جاهز، ولكن Gemini API غير مفعّل. زيد GE_GEMINI_API_KEY في environment variables باش نجاوب بذكاء على أي سؤال. الأوامر المحلية بحال report، create product، PDF و check site خدامة.'
            : ($lang === 'en'
                ? 'hakpoint AI is ready, but Gemini API is not configured. Add GE_GEMINI_API_KEY in environment variables to enable full AI answers. Local ERP commands like report, create product, PDF and site check still work.'
                : 'hakpoint AI est prêt, mais Gemini API n’est pas configuré. Ajoutez GE_GEMINI_API_KEY dans les variables d’environnement pour activer les réponses IA complètes. Les commandes locales comme rapport, création produit, PDF et diagnostic restent actives.');
        return ge_assistant_response($txt);
    }

    $system = "You are hakpoint AI inside an ERP/CRM panel. Reply in the same language as the user: Arabic, French, or English. Be practical and concise. You have read-only awareness of the live ERP context below. For destructive actions, tell the user that deletion requires the word confirm. If asked who created you, answer exactly: I was created by cybersecurity/development guy Hamza Hakkache. Do not claim you executed database changes unless the local PHP command did it.\n\n".ge_assistant_site_context($lang);
    $payload = [
        'systemInstruction' => ['parts' => [['text' => $system]]],
        'contents' => [[
            'role' => 'user',
            'parts' => [['text' => $message]],
        ]],
        'generationConfig' => [
            'temperature' => 0.25,
            'topP' => 0.9,
            'maxOutputTokens' => 1200,
        ],
    ];

    $model = rawurlencode($settings['model']);
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/'.$model.':generateContent';
    [$code, $raw, $err] = ge_assistant_gemini_http($url, $settings['key'], $payload);
    if ($err !== '') {
        return ge_assistant_response('Gemini API error: '.$err);
    }
    $json = json_decode($raw, true);
    if ($code < 200 || $code >= 300) {
        $msg = $json['error']['message'] ?? ('HTTP '.$code);
        return ge_assistant_response('Gemini API error: '.$msg);
    }
    $parts = $json['candidates'][0]['content']['parts'] ?? [];
    $reply = '';
    foreach ($parts as $part) if (isset($part['text'])) $reply .= $part['text'];
    $reply = trim($reply);
    return $reply !== '' ? ge_assistant_response($reply) : null;
}

function ge_assistant_handle(string $message): array {
    $message = ge_assistant_normalize($message);
    $lang = ge_assistant_detect_language($message);
    if($message==='') return ge_assistant_response(ge_assistant_msg('hello',$lang));
    $t=mb_strtolower($message,'UTF-8');

    if(preg_match('/who\s+(created|made|create)\s+you|qui\s+t[’\']?a\s+cr[eé][eé]|qui\s+vous\s+a\s+cr[eé][eé]|من\s+صنعك|شكون\s+صايبك|من\s+خلقك/u',$t)){
        $reply = $lang==='ar' ? 'تم إنشائي من طرف cybersecurity/development guy Hamza Hakkache.' : ge_assistant_creator();
        return ge_assistant_response($reply);
    }
    if(in_array($t, ['hi','hello','salam','السلام عليكم','سلام','bonjour','bonsoir'], true) || preg_match('/\b(hello|hi|salam|bonjour)\b/u',$t)) return ge_assistant_response(ge_assistant_msg('hello',$lang));

    $handlers = [
        'ge_assistant_site_check',
        'ge_assistant_generate_invoice_pdf_for_client',
        'ge_assistant_create_product',
        'ge_assistant_create_tier',
        'ge_assistant_create_invoice',
        'ge_assistant_delete_command',
        'ge_assistant_help_text',
    ];
    if(preg_match('/report|rapport|today|aujourd|اليوم|تقرير|what happen|ماذا حدث/u',$t)) return ge_assistant_report($message,$lang);
    foreach($handlers as $fn){ $r=$fn($message,$lang); if(is_array($r)) return $r; }

    $external = ge_assistant_try_gemini_ai($message,$lang);
    if($external) return $external;
    return ge_assistant_response(ge_assistant_msg('not_understood',$lang));
}
