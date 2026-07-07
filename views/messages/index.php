<?php
$title='Messages';
include __DIR__.'/../layouts/header.php';

function ge_msg_recent_docs($collection, $showPage, $emailPage, $icon, $label){
    $rows = data_read($collection, []);
    usort($rows, fn($a,$b)=> (int)($b['id']??0) <=> (int)($a['id']??0));
    $out=[];
    foreach(array_slice($rows,0,8) as $r){
        $id=(int)($r['id'] ?? 0);
        if($id<=0) continue;
        $out[]=[
            'type'=>$label,
            'icon'=>$icon,
            'ref'=>$r['ref'] ?? ('#'.$id),
            'client'=>$r['client'] ?? $r['tier_name'] ?? $r['name'] ?? '',
            'status'=>$r['status'] ?? '',
            'date'=>$r['date'] ?? $r['created_at'] ?? '',
            'show'=>'index.php?page='.$showPage.'&id='.$id,
            'email'=>'index.php?page='.$emailPage.'&id='.$id,
        ];
    }
    return $out;
}

$docs = array_merge(
    ge_msg_recent_docs('quotes','quote_show','quote_email','fa-file-pen','Devis'),
    ge_msg_recent_docs('orders','order_show','order_email','fa-file-invoice','Commande'),
    ge_msg_recent_docs('invoices','invoice_show','invoice_email','fa-file-invoice-dollar','Facture'),
    ge_msg_recent_docs('expeditions','expedition_show','expedition_email','fa-dolly','Expédition'),
    ge_msg_recent_docs('receptions','reception_show','reception_email','fa-cart-flatbed','Réception')
);
usort($docs, fn($a,$b)=> strcmp((string)$b['date'], (string)$a['date']));
$docs = array_slice($docs,0,15);
$usersCount=count(data_read('users',[]));
$tiersCount=count(data_read('tiers',[]));
?>
<div class="message-page ge-simple-section">
  <section class="ge-section-hero">
    <div>
      <div class="ge-eyebrow"><i class="fa-solid fa-envelope-open-text"></i> Centre de messages</div>
      <h1>Messages</h1>
      <p>Préparez rapidement les emails des devis, commandes, factures, expéditions, réceptions et utilisateurs depuis un seul endroit.</p>
    </div>
    <div class="ge-hero-actions">
      <a class="btn primary" href="index.php?page=users"><i class="fa-solid fa-user-group"></i> Utilisateurs</a>
      <a class="btn secondary" href="index.php?page=tiers"><i class="fa-solid fa-city"></i> Tiers</a>
    </div>
  </section>

  <div class="ge-stat-grid">
    <a class="ge-stat-card" href="index.php?page=users"><i class="fa-solid fa-users-gear"></i><span>Utilisateurs</span><strong><?=e($usersCount)?></strong></a>
    <a class="ge-stat-card" href="index.php?page=tiers"><i class="fa-solid fa-city"></i><span>Tiers / Contacts</span><strong><?=e($tiersCount)?></strong></a>
    <a class="ge-stat-card" href="index.php?page=quotes"><i class="fa-solid fa-file-pen"></i><span>Devis à envoyer</span><strong><?=e(count(data_read('quotes',[])))?></strong></a>
    <a class="ge-stat-card" href="index.php?page=invoices"><i class="fa-solid fa-file-invoice-dollar"></i><span>Factures</span><strong><?=e(count(data_read('invoices',[])))?></strong></a>
  </div>

  <div class="panel ge-panel-clean">
    <div class="ge-panel-title"><div><h2><i class="fa-solid fa-paper-plane"></i> Documents prêts à envoyer</h2><p>Ouvrez un document ou préparez son email directement.</p></div></div>
    <div class="dol-table-wrap">
      <table class="dol-table ge-list-table">
        <thead><tr><th>Type</th><th>Référence</th><th>Contact</th><th>Date</th><th>Statut</th><th>Actions</th></tr></thead>
        <tbody>
        <?php if(!$docs): ?>
          <tr><td colspan="6" class="muted-text">Aucun document disponible pour le moment.</td></tr>
        <?php endif; ?>
        <?php foreach($docs as $d): ?>
          <tr>
            <td><i class="fa-solid <?=e($d['icon'])?> text-green"></i> <?=e($d['type'])?></td>
            <td><a class="ref" href="<?=e($d['show'])?>"><?=e($d['ref'])?></a></td>
            <td><?=e($d['client'])?></td>
            <td><?=e($d['date'])?></td>
            <td><span class="badge badge-gray"><?=e($d['status'] ?: '—')?></span></td>
            <td><a class="btn small" href="<?=e($d['show'])?>">Ouvrir</a> <a class="btn small primary" href="<?=e($d['email'])?>">Email</a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="ge-two-cols">
    <div class="panel ge-panel-clean">
      <h3><i class="fa-solid fa-bolt"></i> Raccourcis messages</h3>
      <div class="ge-quick-list">
        <a href="index.php?page=quote_new"><i class="fa-solid fa-file-pen"></i><span>Nouveau devis</span></a>
        <a href="index.php?page=order_new"><i class="fa-solid fa-file-invoice"></i><span>Nouvelle commande</span></a>
        <a href="index.php?page=invoice_new"><i class="fa-solid fa-file-invoice-dollar"></i><span>Nouvelle facture</span></a>
        <a href="index.php?page=user_new"><i class="fa-solid fa-user-plus"></i><span>Nouvel utilisateur</span></a>
      </div>
    </div>
    <div class="panel ge-panel-clean">
      <h3><i class="fa-solid fa-circle-info"></i> Comment ça marche ?</h3>
      <p class="muted-text">Chaque bouton Email ouvre la page email du document sélectionné. Le système garde les informations du client, la référence et le contenu du document pour faciliter l’envoi.</p>
    </div>
  </div>
</div>
<?php include __DIR__.'/../layouts/footer.php'; ?>
