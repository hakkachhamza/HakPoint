<?php $notes=trim((string)($product['notes'] ?? $product['note'] ?? '')); ?>
<div class="dol-section"><div class="dol-lines full"><div><span>Notes internes</span><b><?= $notes!=='' ? nl2br(e($notes)) : 'Aucune note enregistrée.' ?></b></div></div></div>
