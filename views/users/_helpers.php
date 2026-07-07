<?php
function user_titles(){ return [''=>'','Madame'=>'Madame','Monsieur'=>'Monsieur','Mademoiselle'=>'Mademoiselle','Maître'=>'Maître','Docteur'=>'Docteur']; }
function user_genders(){ return [''=>'','Homme'=>'Homme','Femme'=>'Femme','Autre'=>'Autre']; }
function user_roles(){ return ['Administrateur','Manager','Commercial','Comptable','Magasinier','Utilisateur']; }
function user_statuses(){ return ['Actif','Désactivé']; }
function user_departments(){ if(function_exists('tier_morocco_departments')) return tier_morocco_departments(); require_once __DIR__.'/../tiers/_helpers.php'; return tier_morocco_departments(); }
function user_countries(){ if(function_exists('tier_countries')) return tier_countries(); require_once __DIR__.'/../tiers/_helpers.php'; return tier_countries(); }
function user_ref($id){ return 'USR'.date('ym').'-'.str_pad((string)$id,5,'0',STR_PAD_LEFT); }
function random_password($len=12){ $chars='abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789@$%'; $out=''; for($i=0;$i<$len;$i++) $out.=$chars[random_int(0,strlen($chars)-1)]; return $out; }
function user_collect_post($old=[],$id=0){
    $password = trim($_POST['password_plain'] ?? '');
    $row = array_merge($old,[
        'id'=>$id ?: (int)($old['id']??0),
        'ref'=>$old['ref'] ?? user_ref($id ?: (int)($old['id']??0)),
        'civility'=>$_POST['civility'] ?? ($old['civility']??''),
        'name'=>trim($_POST['name'] ?? ($old['name']??'')),
        'firstname'=>trim($_POST['firstname'] ?? ($old['firstname']??'')),
        'username'=>trim($_POST['username'] ?? ($old['username']??'')),
        'gender'=>$_POST['gender'] ?? ($old['gender']??''),
        'employee'=>!empty($_POST['employee']),
        'manager_id'=>$_POST['manager_id'] ?? ($old['manager_id']??''),
        'expense_validator'=>$_POST['expense_validator'] ?? ($old['expense_validator']??''),
        'external_user'=>$_POST['external_user'] ?? ($old['external_user']??'Interne'),
        'valid_from'=>trim($_POST['valid_from'] ?? ($old['valid_from']??'')),
        'valid_to'=>trim($_POST['valid_to'] ?? ($old['valid_to']??'')),
        'address'=>trim($_POST['address'] ?? ($old['address']??'')),
        'zip'=>trim($_POST['zip'] ?? ($old['zip']??'')),
        'city'=>trim($_POST['city'] ?? ($old['city']??'')),
        'country'=>$_POST['country'] ?? ($old['country']??'Maroc (MA)'),
        'state'=>$_POST['state'] ?? ($old['state']??''),
        'phone'=>trim($_POST['phone'] ?? ($old['phone']??'')),
        'mobile'=>trim($_POST['mobile'] ?? ($old['mobile']??'')),
        'fax'=>trim($_POST['fax'] ?? ($old['fax']??'')),
        'email'=>trim($_POST['email'] ?? ($old['email']??'')),
        'signature'=>trim($_POST['signature'] ?? ($old['signature']??'')),
        'note_public'=>trim($_POST['note_public'] ?? ($old['note_public']??'')),
        'note_private'=>trim($_POST['note_private'] ?? ($old['note_private']??'')),
        'job'=>trim($_POST['job'] ?? ($old['job']??'')),
        'weekly_hours'=>trim($_POST['weekly_hours'] ?? ($old['weekly_hours']??'')),
        'hire_date'=>trim($_POST['hire_date'] ?? ($old['hire_date']??'')),
        'birth_date'=>trim($_POST['birth_date'] ?? ($old['birth_date']??'')),
        'salary'=>trim($_POST['salary'] ?? ($old['salary']??'')),
        'hourly_rate'=>trim($_POST['hourly_rate'] ?? ($old['hourly_rate']??'')),
        'daily_rate'=>trim($_POST['daily_rate'] ?? ($old['daily_rate']??'')),
        'company'=>trim($_POST['company'] ?? ($old['company']??'Utilisateur interne')),
        'role'=>$_POST['role'] ?? ($old['role']??'Utilisateur'),
        'status'=>$_POST['status'] ?? ($old['status']??'Actif'),
        'updated_at'=>date('Y-m-d H:i:s')
    ]);
    if($password !== ''){ $row['password']=password_hash($password, PASSWORD_DEFAULT); }
    elseif(empty($row['password'])){ $generated=random_password(); $row['password']=password_hash($generated, PASSWORD_DEFAULT); }
    unset($row['plain_password'], $row['password_plain']);
    if($row['username']==='') $row['username']=$row['email'] ?: strtolower(trim(($row['firstname']?:'user').'.'.($row['name']?:$row['id'])));
    if($row['email']==='') $row['email']=$row['username'];
    return $row;
}
function user_display_name($u){ return trim(($u['firstname']??'').' '.($u['name']??'')) ?: ($u['name']??$u['email']??'Utilisateur'); }
function user_status_badge($s){ return $s==='Actif' ? 'badge-green' : 'badge-gray'; }


function user_find_user($users,$id){ foreach($users as $u){ if((string)($u['id']??'')===(string)$id) return $u; } return null; }
function user_permission_groups(){ return [
    'Tableau de bord'=>[
        'dashboard.view'=>'Voir le tableau de bord',
        'dashboard.charts'=>'Voir les graphiques et indicateurs',
        'dashboard.export'=>'Exporter les statistiques du tableau de bord'
    ],
    'Recherche globale'=>[
        'search.view'=>'Utiliser la barre de recherche globale'
    ],
    'Tiers'=>[
        'tiers.view'=>'Voir tous les tiers',
        'tiers.create'=>'Créer et modifier les tiers',
        'tiers.clone'=>'Cloner un tiers',
        'tiers.merge'=>'Fusionner deux tiers',
        'tiers.email'=>'Envoyer un email à un tiers',
        'tiers.delete'=>'Supprimer un tiers',
        'tiers.export'=>'Exporter les tiers',
        'tiers.all'=>'Accès complet à tous les tiers et leurs objets'
    ],
    'Prospects'=>[
        'prospects.view'=>'Voir les prospects',
        'prospects.create'=>'Créer et modifier les prospects',
        'prospects.convert'=>'Convertir un prospect en client',
        'prospects.delete'=>'Supprimer les prospects',
        'prospects.export'=>'Exporter les prospects'
    ],
    'Clients'=>[
        'clients.view'=>'Voir les clients',
        'clients.create'=>'Créer et modifier les clients',
        'clients.delete'=>'Supprimer les clients',
        'clients.export'=>'Exporter les clients',
        'clients.prices'=>'Voir les tarifs et conditions clients'
    ],
    'Fournisseurs'=>[
        'suppliers.view'=>'Voir les fournisseurs',
        'suppliers.create'=>'Créer et modifier les fournisseurs',
        'suppliers.delete'=>'Supprimer les fournisseurs',
        'suppliers.export'=>'Exporter les fournisseurs',
        'suppliers.prices'=>'Voir les prix d’achat fournisseurs'
    ],
    'Produits'=>[
        'products.view'=>'Voir les produits',
        'products.create'=>'Créer, modifier et cloner les produits',
        'products.prices_sale'=>'Modifier les prix de vente',
        'products.prices_purchase'=>'Modifier les prix d’achat',
        'products.stats'=>'Voir les statistiques produits',
        'products.delete'=>'Supprimer les produits',
        'products.export'=>'Exporter les produits'
    ],
    'Stock'=>[
        'stock.view'=>'Voir le stock et les mouvements',
        'stock.move'=>'Transférer ou créer des mouvements de stock',
        'stock.adjust'=>'Corriger le stock',
        'stock.inventory'=>'Réaliser les inventaires',
        'stock.export'=>'Exporter les mouvements de stock'
    ],
    'Entrepôts'=>[
        'warehouses.view'=>'Voir les entrepôts',
        'warehouses.create'=>'Créer et modifier les entrepôts',
        'warehouses.delete'=>'Supprimer les entrepôts'
    ],
    'Devis'=>[
        'quotes.view'=>'Voir les devis',
        'quotes.create'=>'Créer et modifier les devis',
        'quotes.status'=>'Changer le statut des devis',
        'quotes.validate'=>'Valider, signer ou refuser les devis',
        'quotes.clone'=>'Cloner les devis',
        'quotes.email'=>'Envoyer les devis par email',
        'quotes.pdf'=>'Générer les PDF des devis',
        'quotes.delete'=>'Supprimer les devis',
        'quotes.export'=>'Exporter les devis'
    ],
    'Commandes clients'=>[
        'orders.view'=>'Voir les commandes clients',
        'orders.create'=>'Créer et modifier les commandes clients',
        'orders.status'=>'Changer le statut des commandes',
        'orders.validate'=>'Valider ou passer les commandes en cours',
        'orders.deliver'=>'Classer les commandes livrées',
        'orders.cancel'=>'Annuler les commandes',
        'orders.clone'=>'Cloner les commandes',
        'orders.email'=>'Envoyer les commandes par email',
        'orders.pdf'=>'Générer les PDF des commandes',
        'orders.delete'=>'Supprimer les commandes',
        'orders.export'=>'Exporter les commandes'
    ],
    'Factures clients'=>[
        'invoices.view'=>'Voir les factures clients',
        'invoices.create'=>'Créer et modifier les factures clients',
        'invoices.status'=>'Changer le statut des factures',
        'invoices.payment'=>'Ajouter des règlements',
        'invoices.email'=>'Envoyer les factures par email',
        'invoices.pdf'=>'Générer les PDF des factures',
        'invoices.delete'=>'Supprimer les factures',
        'invoices.export'=>'Exporter les factures'
    ],
    'Expéditions'=>[
        'shipments.view'=>'Voir les expéditions',
        'shipments.create'=>'Créer et modifier les expéditions',
        'shipments.status'=>'Changer le statut des expéditions',
        'shipments.validate'=>'Valider les expéditions',
        'shipments.close'=>'Classer les expéditions livrées',
        'shipments.cancel'=>'Annuler les expéditions',
        'shipments.email'=>'Envoyer les expéditions par email',
        'shipments.pdf'=>'Générer les bons d’expédition',
        'shipments.delete'=>'Supprimer les expéditions',
        'shipments.export'=>'Exporter les expéditions'
    ],
    'Réceptions'=>[
        'receptions.view'=>'Voir les réceptions',
        'receptions.create'=>'Créer et modifier les réceptions',
        'receptions.status'=>'Changer le statut des réceptions',
        'receptions.validate'=>'Valider les réceptions',
        'receptions.email'=>'Envoyer les réceptions par email',
        'receptions.pdf'=>'Générer les bons de réception',
        'receptions.delete'=>'Supprimer les réceptions',
        'receptions.export'=>'Exporter les réceptions'
    ],
    'Utilisateurs'=>[
        'users.view'=>'Voir les utilisateurs et leurs profils',
        'users.create'=>'Créer et modifier les utilisateurs',
        'users.permissions'=>'Modifier les permissions des utilisateurs',
        'users.password'=>'Modifier le mot de passe des autres utilisateurs',
        'users.email'=>'Envoyer un email à un utilisateur',
        'users.delete'=>'Supprimer ou désactiver les utilisateurs',
        'users.info'=>'Modifier ses propres informations',
        'users.own_password'=>'Modifier son propre mot de passe',
        'users.export'=>'Exporter les utilisateurs'
    ],
    'Messages'=>[
        'messages.view'=>'Voir le centre de messages',
        'messages.compose'=>'Préparer les messages et emails depuis les documents',
        'messages.send'=>'Envoyer les messages et emails',
        'messages.history'=>'Voir l’historique des communications'
    ],    'API'=>[
        'api.view'=>'Voir la section API et sa documentation',
        'api.test'=>'Tester la connexion API',
        'api.manage'=>'Activer, désactiver ou régénérer la clé API',
        'api.status'=>'Lire le statut public de connexion',
        'api.summary'=>'Lire le résumé public sécurisé'
    ],
    'Paramètres'=>[
        'settings.view'=>'Voir les paramètres',
        'settings.language'=>'Modifier la langue et l’affichage',
        'settings.security'=>'Modifier les paramètres de sécurité',
        'settings.profile'=>'Modifier le profil connecté',
        'settings.company'=>'Modifier les informations de la société',
        'settings.numbering'=>'Modifier les numérotations et références',
        'settings.templates'=>'Modifier les modèles de documents',
        'settings.database'=>'Gérer la base de données et les sauvegardes'
    ],
    'Documents & Fichiers'=>[
        'documents.view'=>'Voir les documents et fichiers joints',
        'documents.upload'=>'Ajouter des documents et fichiers joints',
        'documents.download'=>'Télécharger les documents',
        'documents.delete'=>'Supprimer les documents et fichiers joints'
    ],
    'Signatures électroniques'=>[
        'signatures.view'=>'Voir les demandes de signature',
        'signatures.send'=>'Envoyer un PDF pour signature',
        'signatures.manage'=>'Suivre les liens signés et non signés',
        'signatures.export'=>'Exporter les signatures'
    ],
    'Rapports & Exports'=>[
        'reports.view'=>'Voir les rapports',
        'reports.sales'=>'Voir les rapports de ventes',
        'reports.stock'=>'Voir les rapports de stock',
        'reports.finance'=>'Voir les rapports financiers',
        'reports.export'=>'Exporter les rapports'
    ],
    'Finance'=>[
        'finance.view'=>'Voir banque, caisse et soldes',
        'finance.manage'=>'Créer et modifier les comptes bancaires et modes de paiement',
        'finance.payments'=>'Créer, modifier et rapprocher les paiements clients/fournisseurs',
        'finance.export'=>'Exporter les données financières'
    ],
    'Comptabilité'=>[
        'accounting.view'=>'Voir le plan comptable, les journaux et écritures',
        'accounting.write'=>'Créer des écritures comptables',
        'accounting.export'=>'Exporter la comptabilité'
    ],
    'Projets & Agenda'=>[
        'projects.view'=>'Voir les projets',
        'projects.manage'=>'Créer et modifier les projets et tâches',
        'agenda.view'=>'Voir l’agenda et les relances',
        'agenda.manage'=>'Créer et modifier les événements'
    ],
    'Entreprise avancée'=>[
        'settings.modules'=>'Activer/désactiver les modules',
        'settings.workflow'=>'Voir les workflows de statuts',
        'settings.custom_fields'=>'Gérer les champs personnalisés',
        'pos.view'=>'Voir la caisse POS',
        'pos.manage'=>'Créer et modifier les ventes POS',
        'manufacturing.view'=>'Voir la fabrication et les BOM',
        'manufacturing.manage'=>'Créer et modifier la fabrication',
        'import_export.manage'=>'Importer et exporter les données'
    ],
    'Achats & Validation'=>[
        'purchases.view'=>'Voir et saisir les achats fournisseurs',
        'purchases.manage'=>'Gérer les bons de commande et factures fournisseurs',
        'credit_notes.view'=>'Voir et saisir les avoirs clients',
        'credit_notes.manage'=>'Valider et suivre les avoirs clients',
        'approvals.view'=>'Voir les demandes de validation',
        'approvals.manage'=>'Approuver ou refuser les demandes de validation'
    ]
]; }

function user_permission_group_icons(){ return [
    'Tableau de bord'=>'fa-chart-pie',
    'Recherche globale'=>'fa-magnifying-glass',
    'Tiers'=>'fa-city',
    'Prospects'=>'fa-user-plus',
    'Clients'=>'fa-handshake',
    'Fournisseurs'=>'fa-truck-field',
    'Produits'=>'fa-boxes-stacked',
    'Stock'=>'fa-arrow-right-arrow-left',
    'Entrepôts'=>'fa-warehouse',
    'Devis'=>'fa-file-pen',
    'Commandes clients'=>'fa-file-invoice',
    'Factures clients'=>'fa-file-invoice-dollar',
    'Expéditions'=>'fa-dolly',
    'Réceptions'=>'fa-cart-flatbed',
    'Utilisateurs'=>'fa-users-gear',
    'Messages'=>'fa-envelope-open-text',
    'API'=>'fa-code',
    'Paramètres'=>'fa-gear',
    'Documents & Fichiers'=>'fa-folder-open',
    'Signatures électroniques'=>'fa-signature',
    'Rapports & Exports'=>'fa-chart-line',
    'Finance'=>'fa-money-bill-transfer',
    'Comptabilité'=>'fa-scale-balanced',
    'Projets & Agenda'=>'fa-calendar-check',
    'Entreprise avancée'=>'fa-sliders',
    'Achats & Validation'=>'fa-clipboard-check'
]; }

function user_all_permission_keys(){
    $keys=[];
    foreach(user_permission_groups() as $items){ foreach($items as $key=>$label){ $keys[]=$key; } }
    return array_values(array_unique($keys));
}
